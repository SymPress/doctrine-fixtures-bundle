<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Command;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

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
