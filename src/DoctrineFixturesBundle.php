<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle as UpstreamFixturesBundle;
use SymPress\DoctrineBundle\DoctrineBundle;
use SymPress\DoctrineBundle\NativeBundleAdapter;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;

#[RequiredBundle(DoctrineBundle::class)]
final class DoctrineFixturesBundle extends NativeBundleAdapter
{
    public function __construct()
    {
        parent::__construct(new UpstreamFixturesBundle());
    }

    // The inherited trait resolves its path inside sympress/doctrine-bundle.
    public function path(): string
    {
        return dirname(__DIR__);
    }
}
