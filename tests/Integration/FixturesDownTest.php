<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle\Tests\Integration;

use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\BundleFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\FailingFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Fixtures\DataFixtures\ProductFixtures;
use SymPress\DoctrineFixturesBundle\Tests\Support\KernelTestCase;
use SymPress\DoctrineFixturesBundle\Version\FixtureVersionStorage;
use Symfony\Component\Console\Tester\CommandTester;

final class FixturesDownTest extends KernelTestCase
{
    public function testDownRevertsOnlyTheLastBatch(): void
    {
        $this->boot();
        $this->command('doctrine:fixtures:up', ['--group' => ['catalog']]);
        $this->command('doctrine:fixtures:up', ['--group' => ['BundleFixtures']]);

        $display = $this->command('doctrine:fixtures:down')->getDisplay();
        self::assertStringContainsString('Reverted 1 fixture(s).', $display);
        self::assertSame([ProductFixtures::class], array_keys($this->storage()->executed()));
        self::assertSame(['Base product'], $this->names());
    }

    public function testRevertingAFixtureRevertsItsDependentsFirst(): void
    {
        $this->boot();
        $this->command('doctrine:fixtures:up', ['--group' => ['BundleFixtures']]);

        $display = $this->command('doctrine:fixtures:down', ['fixtures' => [ProductFixtures::class]])->getDisplay();
        self::assertLessThan(
            strpos($display, 'reverting ' . ProductFixtures::class),
            (int) strpos($display, 'reverting ' . BundleFixtures::class),
        );
        self::assertSame([], $this->storage()->executed());
        self::assertSame([], $this->names());
        $display = $this->command('doctrine:fixtures:up', ['--group' => ['BundleFixtures']])->getDisplay();
        self::assertStringContainsString('Executed 2 fixture(s)', $display);
    }

    public function testIrreversibleFixtureBlocksTheWholeRun(): void
    {
        $this->boot();
        $this->command('doctrine:fixtures:up');

        $tester = $this->command('doctrine:fixtures:down', ['--all' => true]);
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString(FailingFixtures::class, $tester->getDisplay());
        self::assertCount(3, $this->storage()->executed());
        self::assertCount(2, $this->names());
    }

    public function testDeclinedConfirmationRevertsNothing(): void
    {
        $this->boot();
        $this->command('doctrine:fixtures:up', ['--group' => ['catalog']]);

        $tester = new CommandTester($this->console()->find('doctrine:fixtures:down'));
        $tester->setInputs(['no']);
        $tester->execute([]);

        self::assertStringContainsString('Nothing reverted.', $tester->getDisplay());
        self::assertSame([ProductFixtures::class], array_keys($this->storage()->executed()));
    }

    public function testRevertingAFixtureThatWasNotExecutedFails(): void
    {
        $this->boot();
        $this->expectException(\InvalidArgumentException::class);
        $this->command('doctrine:fixtures:down', ['fixtures' => [BundleFixtures::class]]);
    }

    public function testProductionContainerHasNoDownCommand(): void
    {
        $this->boot('production');

        self::assertFalse($this->console()->has('doctrine:fixtures:down'));
    }

    /** @return list<mixed> */
    private function names(): array
    {
        return $this->manager()->getConnection()->fetchFirstColumn('SELECT name FROM fixture_product ORDER BY id');
    }

    private function storage(): FixtureVersionStorage
    {
        return new FixtureVersionStorage($this->manager()->getConnection());
    }
}
