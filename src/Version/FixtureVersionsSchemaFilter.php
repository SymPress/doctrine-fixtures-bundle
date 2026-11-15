<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Version;

/** Keeps the tracking table out of schema diffs, so migrations never propose dropping it. */
final class FixtureVersionsSchemaFilter
{
    // DBAL 4 passes asset names as strings, optionally qualified as "schema.table".
    public function __invoke(mixed $asset): bool
    {
        if (!is_string($asset)) {
            return true;
        }

        return strtolower((string) preg_replace('~^.*\.~', '', $asset)) !== FixtureVersionStorage::TABLE;
    }
}
