<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\ProductFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', ['secret' => 'fixtures-test-secret-for-disposable-tests-only']);
    $container->extension('doctrine', [
        'dbal' => ['url' => '%env(DOCTRINE_FIXTURES_TEST_URL)%'],
        'orm' => ['mappings' => ['Fixture' => [
            'type' => 'attribute', 'is_bundle' => false,
            'dir' => dirname((string) (new ReflectionClass(Product::class))->getFileName()),
            'prefix' => 'SymPress\\DoctrineFixturesBundle\\Tests\\Fixtures\\Entity',
        ]]],
    ]);
    $container->extension('doctrine_migrations', ['enable_profiler' => false]);
    $services = $container->services()->defaults()->autowire()->autoconfigure();
    $services->load(
        'SymPress\\DoctrineFixturesBundle\\Tests\\Fixtures\\DataFixtures\\',
        dirname((string) (new ReflectionClass(ProductFixtures::class))->getFileName()),
    );
    $services->alias(EntityManagerInterface::class, 'doctrine.orm.entity_manager')->public();
};
