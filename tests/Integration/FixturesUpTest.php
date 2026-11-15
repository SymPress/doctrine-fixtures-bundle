<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Integration;

use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\BundleFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\FailingFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\ProductFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;
use SymPress\DoctrineFixturesBundle\Tests\Support\KernelTestCase;
use SymPress\DoctrineFixturesBundle\Version\ExecutedFixture;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionStorage;

final class FixturesUpTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        putenv('SYMPRESS_FIXTURES_FAIL');
        parent::tearDown();
    }

    public function testUpExecutesPendingFixturesOnceInOneBatch(): void
    {
        $this->boot();
        $connection = $this->manager()->getConnection();
        $connection->executeStatement('CREATE TABLE wp_posts (id INT NOT NULL PRIMARY KEY)');
        $connection->executeStatement('INSERT INTO wp_posts VALUES (1)');

        $display = $this->command('doctrine:fixtures:up')->getDisplay();
        self::assertStringContainsString('Executed 3 fixture(s) in batch 1.', $display);
        self::assertStringContainsString('No pending fixtures.', $this->command('doctrine:fixtures:up')->getDisplay());

        $executed = $this->storage()->executed();
        self::assertSame([1, 1, 1], array_values(array_map(static fn (ExecutedFixture $run): int => $run->getBatch(), $executed)));
        self::assertSame(['product.base'], array_keys($executed[ProductFixtures::class]->getReferences()));
        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM fixture_product'));
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM wp_posts'));
    }

    public function testLaterBatchResolvesReferencesFromEarlierBatch(): void
    {
        $this->boot();
        self::assertStringContainsString(
            'Executed 1 fixture(s) in batch 1.',
            $this->command('doctrine:fixtures:up', ['--group' => ['catalog']])->getDisplay(),
        );

        $display = $this->command('doctrine:fixtures:up')->getDisplay();
        self::assertStringContainsString('Executed 2 fixture(s) in batch 2.', $display);
        self::assertSame(2, $this->storage()->executed()[BundleFixtures::class]->getBatch());
        $bundle = $this->manager()->getRepository(Product::class)->findOneBy(['name' => 'Bundle']);
        self::assertSame('Base product', $bundle?->getBundledWith()?->getName());
    }

    public function testFailingFixtureIsRolledBackAndStaysPending(): void
    {
        $this->boot();
        putenv('SYMPRESS_FIXTURES_FAIL=1');

        try {
            $this->command('doctrine:fixtures:up', ['--group' => ['failing']]);
            self::fail('The failing fixture must abort the run.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Fixture failed on purpose.', $exception->getMessage());
        }

        // The entity manager is closed after the failure; the connection still reads the committed state.
        self::assertArrayNotHasKey(FailingFixtures::class, $this->storage()->executed());
        $count = $this->manager()->getConnection()->fetchOne('SELECT COUNT(*) FROM fixture_product');
        self::assertSame(0, (int) $count);
    }

    public function testProductionContainerHasNoUpCommand(): void
    {
        $this->boot('production');

        self::assertFalse($this->console()->has('doctrine:fixtures:up'));
        self::assertTrue($this->console()->has('doctrine:fixtures:status'));
    }

    private function storage(): FixtureVersionStorage
    {
        return new FixtureVersionStorage($this->manager()->getConnection());
    }
}
