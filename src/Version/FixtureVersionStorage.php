<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

/** Tracks executed fixtures like doctrine_migration_versions tracks migrations. */
final readonly class FixtureVersionStorage
{
    public const string TABLE = 'doctrine_fixture_versions';

    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string, ExecutedFixture> Executed fixtures by class, oldest first. */
    public function executed(): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT fixture, batch, executed_at, fixture_references FROM ' . self::TABLE
            . ' ORDER BY batch, executed_at, fixture',
        );

        $executed = [];
        foreach ($rows as $row) {
            $fixture = (string) $row['fixture'];
            $executed[$fixture] = new ExecutedFixture(
                $fixture,
                (int) $row['batch'],
                new \DateTimeImmutable((string) $row['executed_at']),
                $this->decodeReferences((string) $row['fixture_references']),
            );
        }

        return $executed;
    }

    public function nextBatch(): int
    {
        $batches = array_map(static fn (ExecutedFixture $fixture): int => $fixture->getBatch(), $this->executed());

        return $batches === [] ? 1 : max($batches) + 1;
    }

    /** @param array<string, array{class: class-string, identity: mixed}> $references */
    public function record(string $fixture, int $batch, array $references): void
    {
        $row = [
            'fixture'            => $fixture,
            'batch'              => $batch,
            'executed_at'        => new \DateTimeImmutable(),
            'fixture_references' => json_encode($references, JSON_THROW_ON_ERROR),
        ];
        $types = ['batch' => Types::INTEGER, 'executed_at' => Types::DATETIME_IMMUTABLE];

        $this->connection->insert(self::TABLE, $row, $types);
    }

    /** Call before opening a transaction: DDL commits implicitly on MySQL. */
    public function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        $this->connection->createSchemaManager()->createTable($this->table());
    }

    public function remove(string $fixture): void
    {
        $this->connection->delete(self::TABLE, ['fixture' => $fixture]);
    }

    /**
     * Checks introspection instead of catching TableNotFoundException: mysqli under WordPress runs with
     * MYSQLI_REPORT_OFF, where DBAL cannot convert a missing table into an exception.
     */
    private function tableExists(): bool
    {
        $configuration = $this->connection->getConfiguration();
        $filter = $configuration->getSchemaAssetsFilter();
        // This bundle's own schema filter hides the table; look past every filter for this one check.
        $configuration->setSchemaAssetsFilter(static fn (): bool => true);

        try {
            return $this->connection->createSchemaManager()->tableExists(self::TABLE);
        } finally {
            $configuration->setSchemaAssetsFilter($filter);
        }
    }

    private function table(): Table
    {
        return Table::editor()
            ->setUnquotedName(self::TABLE)
            ->setColumns(
                Column::editor()->setUnquotedName('fixture')->setTypeName(Types::STRING)->setLength(255)->create(),
                Column::editor()->setUnquotedName('batch')->setTypeName(Types::INTEGER)->create(),
                Column::editor()->setUnquotedName('executed_at')->setTypeName(Types::DATETIME_IMMUTABLE)->create(),
                Column::editor()->setUnquotedName('fixture_references')->setTypeName(Types::TEXT)->create(),
            )
            ->setPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('fixture')->create())
            ->create();
    }

    /** @return array<string, array{class: class-string, identity: mixed}> */
    private function decodeReferences(string $json): array
    {
        $references = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($references)) {
            return [];
        }

        $valid = [];
        foreach ($references as $name => $reference) {
            if (!is_string($name) || !is_array($reference) || !is_string($reference['class'] ?? null)) {
                continue;
            }

            /** @var class-string $class */
            $class = $reference['class'];
            $valid[$name] = ['class' => $class, 'identity' => $reference['identity'] ?? null];
        }

        return $valid;
    }
}
