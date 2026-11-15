<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle as UpstreamFixturesBundle;
use SymPress\DoctrineBundle\DoctrineBundle;
use SymPress\DoctrineBundle\NativeBundleAdapter;
use SymPress\DoctrineFixturesBundle\Command\StatusCommand;
use SymPress\DoctrineFixturesBundle\Command\UpCommand;
use SymPress\DoctrineFixturesBundle\DependencyInjection\ProductionGuardPass;
use SymPress\DoctrineFixturesBundle\DependencyInjection\SchemaFilterPass;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionsSchemaFilter;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Reference;

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
        $container->register(SchemaFilterPass::FILTER_ID, FixtureVersionsSchemaFilter::class);
        foreach (['status' => StatusCommand::class, 'up' => UpCommand::class] as $name => $class) {
            $container->register('sympress.doctrine_fixtures.' . $name . '_command', $class)
                ->setArguments([new Reference('doctrine.fixtures.loader'), new Reference('doctrine')])
                ->addTag('console.command', ['command' => 'doctrine:fixtures:' . $name]);
        }
        // Must run before DoctrineBundle turns doctrine.dbal.schema_filter tags into filter managers.
        $container->addCompilerPass(new SchemaFilterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1024);
        // Must run before the kernel collects console commands, so production never exposes data-changing fixture commands.
        $container->addCompilerPass(new ProductionGuardPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1024);
    }

    // The inherited trait resolves its path inside sympress/doctrine-bundle.
    public function path(): string
    {
        return dirname(__DIR__);
    }
}
