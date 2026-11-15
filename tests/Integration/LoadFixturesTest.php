<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Integration;

use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;
use SymPress\DoctrineFixturesBundle\Tests\Support\KernelTestCase;

final class LoadFixturesTest extends KernelTestCase
{
    public function testLoadPurgesOnlyMappedTablesAndResolvesReferences(): void
    {
        $this->boot();
        $connection = $this->manager()->getConnection();
        $connection->executeStatement('CREATE TABLE wp_posts (id INT NOT NULL PRIMARY KEY, title VARCHAR(100))');
        $connection->executeStatement("INSERT INTO wp_posts VALUES (1, 'WordPress content')");

        self::assertSame(0, $this->command('doctrine:fixtures:load')->getStatusCode());
        self::assertSame(0, $this->command('doctrine:fixtures:load')->getStatusCode());

        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM fixture_product'));
        $bundle = $this->manager()->getRepository(Product::class)->findOneBy(['name' => 'Bundle']);
        self::assertSame('Base product', $bundle?->getBundledWith()?->getName());
        self::assertSame('WordPress content', $connection->fetchOne('SELECT title FROM wp_posts WHERE id = 1'));
    }

    public function testAppendAndGroupsUseNativeOptions(): void
    {
        $this->boot();
        self::assertSame(0, $this->command('doctrine:fixtures:load', ['--group' => ['catalog']])->getStatusCode());
        $tester = $this->command('doctrine:fixtures:load', ['--group' => ['catalog'], '--append' => true]);
        self::assertSame(0, $tester->getStatusCode());

        $names = $this->manager()->getConnection()->fetchFirstColumn('SELECT name FROM fixture_product');
        self::assertSame(['Base product', 'Base product'], $names);
    }

    public function testProductionContainerHasNoLoadCommand(): void
    {
        $this->boot('production');

        self::assertFalse($this->console()->has('doctrine:fixtures:load'));
        self::assertTrue($this->console()->has('doctrine:schema:validate'));
    }
}
