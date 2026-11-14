<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle as UpstreamFixturesBundle;
use SymPress\DoctrineBundle\DoctrineBundle;
use SymPress\DoctrineBundle\NativeBundleAdapter;
use SymPress\DoctrineFixturesBundle\DependencyInjection\ProductionGuardPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;

#[RequiredBundle(DoctrineBundle::class)]
final class DoctrineFixturesBundle extends NativeBundleAdapter
{
    public function __construct()
    {
        parent::__construct(new UpstreamFixturesBundle());
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        // Must run before the kernel collects console commands, so production never exposes the purge.
        $container->addCompilerPass(new ProductionGuardPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1024);
    }

    // The inherited trait resolves its path inside sympress/doctrine-bundle.
    public function path(): string
    {
        return dirname(__DIR__);
    }
}
