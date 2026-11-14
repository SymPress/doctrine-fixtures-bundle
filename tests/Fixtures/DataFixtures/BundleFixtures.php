<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;

final class BundleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $bundle = new Product('Bundle');
        $bundle->bundleWith($this->getReference(ProductFixtures::BASE, Product::class));
        $manager->persist($bundle);
        $manager->flush();
    }

    /** @return list<class-string<\Doctrine\Common\DataFixtures\FixtureInterface>> */
    public function getDependencies(): array
    {
        return [ProductFixtures::class];
    }
}
