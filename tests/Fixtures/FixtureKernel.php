<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Fixtures;

use SymPress\Kernel\EnvConfig;
use SymPress\Kernel\Kernel\AbstractKernel;
use SymPress\Kernel\WpContext;
use Symfony\Component\Filesystem\Filesystem;

final class FixtureKernel extends AbstractKernel
{
    public function __construct(private readonly string $cacheRoot, string $environment = 'test')
    {
        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__ . '/Project', $this->cacheRoot . '/project');
        parent::__construct($this->cacheRoot . '/project', $environment, false, new EnvConfig(), WpContext::new()->force(WpContext::CORE));
        $filesystem->mkdir($this->getCacheDir());
        $_ENV['DOCTRINE_FIXTURES_TEST_URL'] = 'sqlite:///' . $this->getCacheDir() . '/fixtures.sqlite';
    }

    public function getCacheDir(): string
    {
        return $this->cacheRoot . '/cache';
    }

    public function getBuildDir(): string
    {
        return $this->cacheRoot . '/build';
    }

    public function getLogDir(): string
    {
        return $this->cacheRoot . '/log';
    }
}
