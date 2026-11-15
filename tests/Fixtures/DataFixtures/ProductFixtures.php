<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use SymPress\DoctrineFixturesBundle\RevertibleFixtureInterface;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;

final class ProductFixtures extends Fixture implements FixtureGroupInterface, RevertibleFixtureInterface
{
    public const string BASE = 'product.base';

    public function load(ObjectManager $manager): void
    {
        $product = new Product('Base product');
        $manager->persist($product);
        $manager->flush();
        $this->addReference(self::BASE, $product);
    }

    public function down(ObjectManager $manager): void
    {
        $manager->remove($this->getReference(self::BASE, Product::class));
    }

    /** @return list<string> */
    public static function getGroups(): array
    {
        return ['catalog'];
    }
}
