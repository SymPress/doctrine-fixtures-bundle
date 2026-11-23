<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Command;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ManagerException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use SymPress\DoctrineFixturesBundle\Version\ExecutedFixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Shared --group and --em handling of the migration-style fixture commands. */
abstract class AbstractFixturesCommand extends Command
{
    public function __construct(
        protected readonly SymfonyFixturesLoader $loader,
        private readonly ManagerRegistry $registry,
    ) {

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'group',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Only use fixtures of these groups.',
            )
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use.');
    }

    /** @return list<string> */
    protected function groups(InputInterface $input): array
    {
        $groups = $input->getOption('group');

        return is_array($groups) ? array_values(array_filter($groups, 'is_string')) : [];
    }

    /**
     * Upstream only resolves names it holds an object for, so restore lazy proxies (no query until used).
     * A stored reference to a removed entity class or without identity is skipped, so it cannot block every run.
     *
     * @param array<string, ExecutedFixture> $executed
     */
    protected function restoreReferences(
        ReferenceRepository $references,
        EntityManagerInterface $manager,
        array $executed,
        SymfonyStyle $io,
    ): void {

        $stale = [];
        foreach ($executed as $run) {
            foreach ($run->getReferences() as $name => $reference) {
                try {
                    $proxy = $manager->getReference($reference['class'], $reference['identity']);
                } catch (MappingException | ManagerException) {
                    $stale[] = $name;
                    continue;
                }

                if ($proxy === null) {
                    continue;
                }

                $references->setReference($name, $proxy);
            }
        }

        if ($stale === []) {
            return;
        }

        $io->warning(sprintf(
            'Skipped stale references: %s. Their entity class is no longer mapped or their identity is invalid.',
            implode(', ', $stale),
        ));
    }

    protected function manager(InputInterface $input): EntityManagerInterface
    {
        $name = $input->getOption('em');
        $manager = $this->registry->getManager(is_string($name) ? $name : null);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException('Migration-style fixtures require a Doctrine ORM entity manager.');
        }

        return $manager;
    }
}
