<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use SymPress\DoctrineFixturesBundle\Version\ExecutedFixture;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'doctrine:fixtures:up', description: 'Execute pending fixtures without purging the database')]
final class UpCommand extends AbstractFixturesCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manager = $this->manager($input);
        $storage = new FixtureVersionStorage($manager->getConnection());
        $executed = $storage->executed();
        $pending = array_values(array_filter(
            $this->loader->getFixtures($this->groups($input)),
            static fn (FixtureInterface $fixture): bool => !isset($executed[$fixture::class]),
        ));

        if ($pending === []) {
            $io->success('No pending fixtures.');

            return self::SUCCESS;
        }

        $storage->ensureTable();
        $executor = new ORMExecutor($manager);
        $references = new ReferenceRepository($manager);
        $executor->setReferenceRepository($references);
        $this->restoreReferences($references, $manager, $executed);
        $batch = $storage->nextBatch();

        foreach ($pending as $fixture) {
            $io->text(sprintf('  <comment>></comment> loading <info>%s</info>', $fixture::class));
            $before = $references->getIdentitiesByClass();
            // One transaction per fixture (the executor nests its own): a failure leaves this one pending.
            $manager->wrapInTransaction(static function () use ($executor, $fixture, $storage, $batch, $references, $before): void {
                $executor->execute([$fixture], true);
                $storage->record($fixture::class, $batch, self::addedReferences($before, $references->getIdentitiesByClass()));
            });
        }

        $io->success(sprintf('Executed %d fixture(s) in batch %d.', count($pending), $batch));

        return self::SUCCESS;
    }

    /**
     * Upstream only resolves names it holds an object for, so restore lazy proxies (no query until used).
     *
     * @param array<string, ExecutedFixture> $executed
     */
    private function restoreReferences(
        ReferenceRepository $references,
        EntityManagerInterface $manager,
        array $executed,
    ): void {

        foreach ($executed as $run) {
            foreach ($run->getReferences() as $name => $reference) {
                $proxy = $manager->getReference($reference['class'], $reference['identity']);
                if ($proxy === null) {
                    continue;
                }

                $references->setReference($name, $proxy);
            }
        }
    }

    /**
     * shortcut: identities are stored as JSON, so object identities (e.g. Uid) round-trip as their JSON form;
     * upgrade to typed identity storage if a custom identifier type cannot be rebuilt from that.
     *
     * @param array<class-string, array<string, mixed>> $before
     * @param array<class-string, array<string, mixed>> $after
     * @return array<string, array{class: class-string, identity: mixed}>
     */
    private static function addedReferences(array $before, array $after): array
    {
        $added = [];
        foreach ($after as $class => $identities) {
            foreach ($identities as $name => $identity) {
                if (array_key_exists($name, $before[$class] ?? []) && $before[$class][$name] === $identity) {
                    continue;
                }

                $added[$name] = ['class' => $class, 'identity' => $identity];
            }
        }

        return $added;
    }
}
