<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\FixtureKernel;
use SymPress\Kernel\App;
use SymPress\Kernel\Console\ConsoleApplicationFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class LoadFixturesTest extends TestCase
{
    private ?FixtureKernel $kernel = null;
    private string $cacheRoot;

    protected function setUp(): void
    {
        $this->cacheRoot = sys_get_temp_dir() . '/sympress-fixtures-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        (new Filesystem())->remove($this->cacheRoot);
    }

    public function testLoadPurgesOnlyMappedTablesAndResolvesReferences(): void
    {
        $this->boot('test');
        $connection = $this->manager()->getConnection();
        $connection->executeStatement('CREATE TABLE wp_posts (id INT NOT NULL PRIMARY KEY, title VARCHAR(100))');
        $connection->executeStatement("INSERT INTO wp_posts VALUES (1, 'WordPress content')");

        self::assertSame(0, $this->load([]));
        self::assertSame(0, $this->load([]));

        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM fixture_product'));
        $bundle = $this->manager()->getRepository(Product::class)->findOneBy(['name' => 'Bundle']);
        self::assertSame('Base product', $bundle?->getBundledWith()?->getName());
        self::assertSame('WordPress content', $connection->fetchOne('SELECT title FROM wp_posts WHERE id = 1'));
    }

    public function testAppendAndGroupsUseNativeOptions(): void
    {
        $this->boot('test');
        self::assertSame(0, $this->load(['--group' => ['catalog']]));
        self::assertSame(0, $this->load(['--group' => ['catalog'], '--append' => true]));

        $names = $this->manager()->getConnection()->fetchFirstColumn('SELECT name FROM fixture_product');
        self::assertSame(['Base product', 'Base product'], $names);
    }

    public function testProductionContainerHasNoLoadCommand(): void
    {
        $this->boot('production');

        self::assertFalse($this->console()->has('doctrine:fixtures:load'));
        self::assertTrue($this->console()->has('doctrine:schema:validate'));
    }

    /** @param array<string, mixed> $options */
    private function load(array $options): int
    {
        $this->manager()->clear();

        return (new CommandTester($this->console()->find('doctrine:fixtures:load')))
            ->execute($options, ['interactive' => false]);
    }

    private function boot(string $environment): void
    {
        $this->kernel = new FixtureKernel($this->cacheRoot, $environment);
        App::new($this->kernel)->boot();
        if ($environment === 'production') {
            return;
        }

        $manager = $this->manager();
        (new SchemaTool($manager))->createSchema($manager->getMetadataFactory()->getAllMetadata());
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->kernel?->getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function console(): Application
    {
        $factory = $this->kernel?->getContainer()->get(ConsoleApplicationFactory::class);
        self::assertInstanceOf(ConsoleApplicationFactory::class, $factory);

        return $factory->create();
    }
}
