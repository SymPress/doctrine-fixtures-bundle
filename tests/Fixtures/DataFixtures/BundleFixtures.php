<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SymPress\DoctrineFixturesBundle\RevertibleFixtureInterface;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity\Product;

final class BundleFixtures extends Fixture implements DependentFixtureInterface, RevertibleFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $bundle = new Product('Bundle');
        $bundle->bundleWith($this->getReference(ProductFixtures::BASE, Product::class));
        $manager->persist($bundle);
        $manager->flush();
    }

    public function down(ObjectManager $manager): void
    {
        foreach ($manager->getRepository(Product::class)->findBy(['name' => 'Bundle']) as $bundle) {
            $manager->remove($bundle);
        }
    }

    /** @return list<class-string<\Doctrine\Common\DataFixtures\FixtureInterface>> */
    public function getDependencies(): array
    {
        return [ProductFixtures::class];
    }
}
