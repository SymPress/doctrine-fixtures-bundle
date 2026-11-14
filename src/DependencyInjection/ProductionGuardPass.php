<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** Keeps the purging load command out of production containers. */
final class ProductionGuardPass implements CompilerPassInterface
{
    public const string COMMAND_ID = 'doctrine.fixtures_load_command';

    private const array PRODUCTION_ENVIRONMENTS = ['prod', 'production'];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.environment')) {
            return;
        }

        $environment = $container->getParameter('kernel.environment');
        if (
            !is_string($environment)
            || !in_array(strtolower($environment), self::PRODUCTION_ENVIRONMENTS, true)
            || !$container->hasDefinition(self::COMMAND_ID)
        ) {
            return;
        }

        // Upstream passes still configure the service; without the tag it is never exposed and gets removed as unused.
        $container->getDefinition(self::COMMAND_ID)->clearTag('console.command');
    }
}
