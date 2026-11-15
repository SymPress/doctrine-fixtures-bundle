<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Version;

final readonly class ExecutedFixture
{
    /** @param array<string, array{class: class-string, identity: mixed}> $references */
    public function __construct(
        private string $fixture,
        private int $batch,
        private \DateTimeImmutable $executedAt,
        private array $references,
    ) {
    }

    public function getFixture(): string
    {
        return $this->fixture;
    }

    public function getBatch(): int
    {
        return $this->batch;
    }

    public function getExecutedAt(): \DateTimeImmutable
    {
        return $this->executedAt;
    }

    /** @return array<string, array{class: class-string, identity: mixed}> */
    public function getReferences(): array
    {
        return $this->references;
    }
}
