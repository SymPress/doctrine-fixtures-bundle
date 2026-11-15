<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Command;

use SymPress\DoctrineFixturesBundle\Version\ExecutedFixture;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'doctrine:fixtures:status', description: 'Show which fixtures are executed and which are pending')]
final class StatusCommand extends AbstractFixturesCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $executed = (new FixtureVersionStorage($this->manager($input)->getConnection()))->executed();
        $rows = [];

        foreach ($this->loader->getFixtures($this->groups($input)) as $fixture) {
            $run = $executed[$fixture::class] ?? null;
            unset($executed[$fixture::class]);
            $rows[] = $run === null ? [$fixture::class, 'pending', '', ''] : $this->row($run, 'executed');
        }

        // Recorded fixtures whose class no longer exists, like unavailable migrations.
        if ($this->groups($input) === []) {
            foreach ($executed as $run) {
                $rows[] = $this->row($run, 'unavailable');
            }
        }

        if ($rows === []) {
            $io->warning('Could not find any fixture services.');

            return self::SUCCESS;
        }

        $io->table(['Fixture', 'Status', 'Batch', 'Executed at'], $rows);

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function row(ExecutedFixture $run, string $status): array
    {
        return [$run->getFixture(), $status, (string) $run->getBatch(), $run->getExecutedAt()->format('Y-m-d H:i:s')];
    }
}
