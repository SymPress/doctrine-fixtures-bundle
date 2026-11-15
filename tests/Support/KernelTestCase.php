<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\FixtureKernel;
use SymPress\Kernel\App;
use SymPress\Kernel\Console\ConsoleApplicationFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

abstract class KernelTestCase extends TestCase
{
    protected ?FixtureKernel $kernel = null;
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

    protected function boot(string $environment = 'test'): void
    {
        $this->kernel = new FixtureKernel($this->cacheRoot, $environment);
        App::new($this->kernel)->boot();
        if ($environment === 'production') {
            return;
        }

        $manager = $this->manager();
        (new SchemaTool($manager))->createSchema($manager->getMetadataFactory()->getAllMetadata());
    }

    protected function manager(): EntityManagerInterface
    {
        $manager = $this->kernel?->getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    protected function console(): Application
    {
        $factory = $this->kernel?->getContainer()->get(ConsoleApplicationFactory::class);
        self::assertInstanceOf(ConsoleApplicationFactory::class, $factory);

        return $factory->create();
    }

    /** @param array<string, mixed> $options */
    protected function command(string $name, array $options = []): CommandTester
    {
        $this->manager()->clear();
        $tester = new CommandTester($this->console()->find($name));
        $tester->execute($options, ['interactive' => false]);

        return $tester;
    }
}
