<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** Applies the tracking-table filter to every DBAL connection, not only the default one. */
final class SchemaFilterPass implements CompilerPassInterface
{
    public const string FILTER_ID = 'sympress.doctrine_fixtures.schema_filter';

    public function process(ContainerBuilder $container): void
    {
        $connections = $container->hasParameter('doctrine.connections')
            ? $container->getParameter('doctrine.connections')
            : [];
        if (!is_array($connections) || !$container->hasDefinition(self::FILTER_ID)) {
            return;
        }

        $filter = $container->getDefinition(self::FILTER_ID);
        foreach (array_keys($connections) as $connection) {
            $filter->addTag('doctrine.dbal.schema_filter', ['connection' => (string) $connection]);
        }
    }
}
