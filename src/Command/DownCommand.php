<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Command;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use SymPress\DoctrineFixturesBundle\RevertibleFixtureInterface;
use SymPress\DoctrineFixturesBundle\Version\ExecutedFixture;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function assert;

#[AsCommand(name: 'doctrine:fixtures:down', description: 'Revert executed fixtures, the last batch by default')]
final class DownCommand extends AbstractFixturesCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(
                'fixtures',
                InputArgument::IS_ARRAY,
                'Fixture classes to revert, with the fixtures that depend on them.',
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Revert every executed fixture.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manager = $this->manager($input);
        $storage = new FixtureVersionStorage($manager->getConnection());
        $executed = $storage->executed();
        $fixtures = [];
        foreach ($this->loader->getFixtures() as $fixture) {
            $fixtures[$fixture::class] = $fixture;
        }

        $targets = $this->targets($input, $executed);
        if ($targets === []) {
            $io->success('No executed fixtures to revert.');

            return self::SUCCESS;
        }

        $plan = $this->plan($targets, $executed, $fixtures);
        $blocked = array_values(array_filter(
            $plan,
            static fn (string $class): bool => !(($fixtures[$class] ?? null) instanceof RevertibleFixtureInterface),
        ));
        if ($blocked !== []) {
            $io->error('These fixtures cannot be reverted (unavailable or without down()); nothing was reverted:');
            $io->listing($blocked);

            return self::FAILURE;
        }

        $io->listing($plan);
        // Like doctrine:migrations:migrate, --no-interaction proceeds without asking.
        if ($input->isInteractive() && !$io->confirm(sprintf('Revert these %d fixture(s)?', count($plan)), false)) {
            $io->warning('Nothing reverted.');

            return self::SUCCESS;
        }

        $references = new ReferenceRepository($manager);
        $this->restoreReferences($references, $manager, $executed, $io);
        foreach ($plan as $class) {
            $fixture = $fixtures[$class];
            assert($fixture instanceof RevertibleFixtureInterface);
            if ($fixture instanceof SharedFixtureInterface) {
                $fixture->setReferenceRepository($references);
            }

            $io->text(sprintf('  <comment>></comment> reverting <info>%s</info>', $class));
            // One transaction per fixture: a failure keeps it executed and stops the run.
            $manager->wrapInTransaction(static function () use ($fixture, $manager, $storage, $class): void {
                $fixture->down($manager);
                $storage->remove($class);
            });
        }

        $io->success(sprintf('Reverted %d fixture(s).', count($plan)));

        return self::SUCCESS;
    }

    /**
     * @param array<string, ExecutedFixture> $executed
     * @return list<string>
     */
    private function targets(InputInterface $input, array $executed): array
    {
        $requested = $input->getArgument('fixtures');
        if (is_array($requested) && $requested !== []) {
            $unknown = array_diff($requested, array_keys($executed));
            if ($unknown !== []) {
                throw new \InvalidArgumentException(sprintf('Not executed: %s', implode(', ', $unknown)));
            }

            return array_values(array_filter($requested, 'is_string'));
        }

        if ($input->getOption('all') === true) {
            return array_keys($executed);
        }

        if ($this->groups($input) !== []) {
            return array_values(array_intersect(array_keys($executed), $this->groupMembers($this->groups($input))));
        }

        $batches = array_map(static fn (ExecutedFixture $run): int => $run->getBatch(), $executed);
        $last = $batches === [] ? 0 : max($batches);

        return array_keys(array_filter($batches, static fn (int $batch): bool => $batch === $last));
    }

    /**
     * getFixtures($groups) also returns the groups' dependencies, which down must keep. Only the upstream loader
     * knows every group (class name, getGroups() and the service tag's "group"), so read its private mapping.
     *
     * @param list<string> $groups
     * @return list<string>
     */
    private function groupMembers(array $groups): array
    {
        /** @var array<string, array<string, bool>> $mapping */
        $mapping = \Closure::bind(
            fn (): array => $this->groupsFixtureMapping,
            $this->loader,
            SymfonyFixturesLoader::class,
        )();

        return array_keys(array_merge(...array_map(static fn (string $group): array => $mapping[$group] ?? [], $groups)));
    }

    /**
     * Adds executed fixtures that depend on a target and orders dependents first.
     *
     * @param list<string> $targets
     * @param array<string, ExecutedFixture> $executed
     * @param array<string, FixtureInterface> $fixtures
     * @return list<string>
     */
    private function plan(array $targets, array $executed, array $fixtures): array
    {
        $selected = array_fill_keys($targets, true);
        do {
            $added = false;
            foreach (array_keys($executed) as $class) {
                $fixture = $fixtures[$class] ?? null;
                if (isset($selected[$class]) || !$fixture instanceof DependentFixtureInterface) {
                    continue;
                }

                if (array_intersect_key(array_flip($fixture->getDependencies()), $selected) === []) {
                    continue;
                }

                $selected[$class] = true;
                $added = true;
            }
        } while ($added);

        // Dependents first: the reverse of the loader's dependency order.
        $order = array_flip(array_keys($fixtures));
        $plan = array_keys($selected);
        usort($plan, static fn (string $left, string $right): int => ($order[$right] ?? -1) <=> ($order[$left] ?? -1));

        return $plan;
    }
}
