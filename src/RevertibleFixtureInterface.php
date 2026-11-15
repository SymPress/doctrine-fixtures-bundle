<?php

declare(strict_types=1);

namespace SymPress\DoctrineFixturesBundle;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/** A fixture that doctrine:fixtures:down can revert, like a migration's down(). */
interface RevertibleFixtureInterface extends FixtureInterface
{
    /**
     * Remove what load() created. Runs in a transaction that is flushed and committed afterwards;
     * references set by executed fixtures are available through getReference().
     */
    public function down(ObjectManager $manager): void;
}
