<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** Keeps data-changing fixture commands out of production containers. */
final class ProductionGuardPass implements CompilerPassInterface
{
    public const array COMMAND_IDS = [
        'doctrine.fixtures_load_command',
        'sympress.doctrine_fixtures.up_command',
        'sympress.doctrine_fixtures.down_command',
    ];

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
        ) {
            return;
        }

        foreach (self::COMMAND_IDS as $id) {
            // Upstream passes still configure the service; untagged, it is never exposed and is removed as unused.
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $container->getDefinition($id)->clearTag('console.command');
        }
    }
}
