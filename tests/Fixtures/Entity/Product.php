<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'fixture_product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?Product $bundledWith = null;

    public function __construct(
        #[ORM\Column(length: 100)]
        private string $name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function bundleWith(Product $product): void
    {
        $this->bundledWith = $product;
    }

    public function getBundledWith(): ?Product
    {
        return $this->bundledWith;
    }
}
