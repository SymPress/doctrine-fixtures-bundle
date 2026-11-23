# SymPress Doctrine Fixtures Bundle

Doctrine data fixtures for the SymPress ecosystem. Uses the original
[DoctrineFixturesBundle](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
on top of [`sympress/doctrine-bundle`](https://github.com/SymPress/doctrine-bundle).
Requires PHP 8.5 and `sympress/doctrine-bundle` >= 1.0.7.

## Installation

Install it as a development dependency in the consuming application's root:

```sh
composer require --dev sympress/doctrine-fixtures-bundle
```

The SymPress kernel discovers the bundle through `extra.kernel` and registers the
Doctrine bundle it requires. Symfony Flex is not required.

## Writing fixtures

Fixtures are plain upstream fixtures. Extend `Doctrine\Bundle\FixturesBundle\Fixture`
(or implement `ORMFixtureInterface`) and register the classes as autoconfigured
services in your kernel-loaded config:

```php
// config/services.php
$services = $container->services()->defaults()->autowire()->autoconfigure();
$services->load('App\\DataFixtures\\', '%kernel.project_dir%/src/DataFixtures');
```

```php
namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist(new Product('Demo product'));
        $manager->flush();
    }
}
```

References, `DependentFixtureInterface`, `FixtureGroupInterface` and custom purger
factories work exactly as documented upstream.

## Loading fixtures

Run the native command through the SymPress kernel console (WP-CLI bridge):

```sh
wp console doctrine:fixtures:load
wp console doctrine:fixtures:load --group=catalog --append
```

Without `--append` the command purges every table mapped by Doctrine before
loading. Tables that Doctrine does not map, such as WordPress core and plugin
tables, are not touched. Tables with the WordPress table prefix are never purged
either, even when an entity maps them (quoted or not); outside WordPress, set
`DB_TABLE_PREFIX`. Use `--purge-exclusions` to keep individual mapped tables.

## Migration-style fixtures: up, down and status

Next to the upstream `load` (purge and reload everything), the bundle tracks
executed fixtures in `doctrine_fixture_versions`, like Doctrine Migrations
tracks migrations:

```sh
wp console doctrine:fixtures:status            # executed, pending, unavailable
wp console doctrine:fixtures:up                # run pending fixtures, no purge
wp console doctrine:fixtures:down              # revert the last up batch
wp console doctrine:fixtures:down 'App\DataFixtures\ProductFixtures'
wp console doctrine:fixtures:down --all
```

- `up` runs only fixtures that were not executed yet, in dependency order. All
  fixtures of one run form a batch. Each fixture runs in its own transaction
  together with its record, so a failure leaves it pending.
- References from earlier batches are restored, so a new fixture can use
  `getReference()` on objects created by an already executed fixture.
- `down` reverts the last batch, the given fixtures or `--all`. Executed fixtures
  that depend on a reverted fixture are reverted first. It asks for confirmation;
  `--no-interaction` proceeds like `doctrine:migrations:migrate -n`.
- All commands accept `--group` and `--em`. `down --group` reverts the group's
  own fixtures (and their dependents), never the fixtures they depend on.

A fixture becomes revertible by implementing `RevertibleFixtureInterface`:

```php
use SymPress\DoctrineFixturesBundle\RevertibleFixtureInterface;

final class ProductFixtures extends Fixture implements RevertibleFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $product = new Product('Demo product');
        $manager->persist($product);
        $manager->flush();
        $this->addReference('product.demo', $product);
    }

    public function down(ObjectManager $manager): void
    {
        $manager->remove($this->getReference('product.demo', Product::class));
    }
}
```

`down` refuses to run, and reverts nothing, if the plan contains a fixture
without `down()` or one whose class no longer exists. The tracking table is
created on first use and hidden from schema diffs on every connection, so
`doctrine:migrations:diff` never proposes dropping it. `load` does not use the
tracking table; use `down --all` followed by `up` for a migration-style rebuild.

## Production safety

`doctrine:fixtures:load`, `up` and `down` are not registered when the kernel
environment is `prod` or `production`; `status` stays available. Keep the
package in `require-dev` so production builds (`composer install --no-dev`) do
not install it at all.

## Development

```bash
composer install
composer qa
```

The suite boots the real SymPress kernel and runs the native command against
SQLite.

License: GPL-2.0-or-later. Upstream dependencies retain their original licenses.
