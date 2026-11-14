<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Unit;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle as NativeFixturesBundle;
use PHPUnit\Framework\TestCase;
use SymPress\DoctrineBundle\DoctrineBundle;
use SymPress\DoctrineFixturesBundle\DoctrineFixturesBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;

final class BundleContractTest extends TestCase
{
    public function testAdapterKeepsNativeExtensionAndCompilerPasses(): void
    {
        $adapter = new DoctrineFixturesBundle();
        $native = new NativeFixturesBundle();
        $nativeExtension = $native->getContainerExtension();
        $extension = $adapter->getContainerExtension();
        self::assertNotNull($nativeExtension);
        self::assertNotNull($extension);
        self::assertSame($nativeExtension::class, $extension::class);
        self::assertSame('doctrine_fixtures', $extension->getAlias());

        $expected = new ContainerBuilder();
        $actual = new ContainerBuilder();
        $native->build($expected);
        $adapter->build($actual);
        self::assertSame($this->passes($expected), $this->passes($actual));
        self::assertSame($native->getPath(), $adapter->getPath());
        self::assertSame($native->getNamespace(), $adapter->getNamespace());
        self::assertSame($native->getName(), $adapter->getName());
    }

    public function testKernelMetadataPointsAtThisPackage(): void
    {
        $adapter = new DoctrineFixturesBundle();
        self::assertSame(DoctrineFixturesBundle::class, $adapter->id());
        self::assertSame(dirname(__DIR__, 2), $adapter->path());
        $requirements = array_map(
            static fn (\ReflectionAttribute $attribute): string => $attribute->newInstance()->class,
            (new \ReflectionClass($adapter))->getAttributes(RequiredBundle::class),
        );
        self::assertSame([DoctrineBundle::class], $requirements);
    }

    /** @return array<class-string> */
    private function passes(ContainerBuilder $container): array
    {
        return array_map(static fn (object $pass): string => $pass::class, $container->getCompilerPassConfig()->getPasses());
    }
}
