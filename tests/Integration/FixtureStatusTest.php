<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Integration;

use Doctrine\ORM\Tools\SchemaTool;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\BundleFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\ProductFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;
use SymPress\DoctrineFixturesBundle\Tests\Support\KernelTestCase;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionStorage;

final class FixtureStatusTest extends KernelTestCase
{
    public function testStatusListsPendingExecutedAndUnavailableFixtures(): void
    {
        $this->boot();
        $display = $this->command('doctrine:fixtures:status')->getDisplay();
        self::assertMatchesRegularExpression('~ProductFixtures\s+pending~', $display);
        self::assertMatchesRegularExpression('~BundleFixtures\s+pending~', $display);

        $storage = new FixtureVersionStorage($this->manager()->getConnection());
        $references = ['product.base' => ['class' => Product::class, 'identity' => ['id' => 1]]];
        $storage->record(ProductFixtures::class, 1, $references);
        $storage->record('App\\DataFixtures\\RemovedFixtures', 1, []);

        $display = $this->command('doctrine:fixtures:status')->getDisplay();
        self::assertMatchesRegularExpression('~ProductFixtures\s+executed\s+1~', $display);
        self::assertMatchesRegularExpression('~BundleFixtures\s+pending~', $display);
        self::assertMatchesRegularExpression('~RemovedFixtures\s+unavailable~', $display);
        self::assertSame($references, $storage->executed()[ProductFixtures::class]->getReferences());
        self::assertSame(2, $storage->nextBatch());

        $display = $this->command('doctrine:fixtures:status', ['--group' => ['catalog']])->getDisplay();
        self::assertStringNotContainsString(BundleFixtures::class, $display);
        self::assertStringNotContainsString('RemovedFixtures', $display);
    }

    public function testTrackingTableIsHiddenFromSchemaDiffs(): void
    {
        $this->boot();
        $manager = $this->manager();
        (new FixtureVersionStorage($manager->getConnection()))->record(ProductFixtures::class, 1, []);

        $sql = (new SchemaTool($manager))->getUpdateSchemaSql($manager->getMetadataFactory()->getAllMetadata());
        self::assertStringNotContainsString(FixtureVersionStorage::TABLE, implode("\n", $sql));
    }

    public function testRemoveForgetsAnExecutedFixture(): void
    {
        $this->boot();
        $storage = new FixtureVersionStorage($this->manager()->getConnection());
        self::assertSame([], $storage->executed());
        $storage->record(ProductFixtures::class, 1, []);
        $storage->remove(ProductFixtures::class);

        self::assertSame([], $storage->executed());
        self::assertSame(1, $storage->nextBatch());
    }
}
