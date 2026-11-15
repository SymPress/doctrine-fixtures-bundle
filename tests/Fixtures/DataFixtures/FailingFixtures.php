<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;

/** Writes a row and then fails when SYMPRESS_FIXTURES_FAIL is set; otherwise it does nothing. */
final class FailingFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        if (getenv('SYMPRESS_FIXTURES_FAIL') !== '1') {
            return;
        }

        $manager->persist(new Product('Failing'));
        $manager->flush();
        throw new \RuntimeException('Fixture failed on purpose.');
    }

    /** @return list<string> */
    public static function getGroups(): array
    {
        return ['failing'];
    }
}
