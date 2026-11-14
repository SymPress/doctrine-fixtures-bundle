# SymPress Doctrine Fixtures Bundle

Doctrine data fixtures for the SymPress ecosystem. Uses the original
[DoctrineFixturesBundle](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
on top of [`sympress/doctrine-bundle`](https://github.com/SymPress/doctrine-bundle).
Requires PHP 8.5 and `sympress/doctrine-bundle` >= 1.0.3.

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
tables, are not touched. Use `--purge-exclusions` to keep individual mapped
tables.

## Production safety

`doctrine:fixtures:load` is not registered when the kernel environment is `prod`
or `production`. Keep the package in `require-dev` so production builds
(`composer install --no-dev`) do not install it at all.

## Development

```bash
composer install
composer qa
```

The suite boots the real SymPress kernel and runs the native command against
SQLite.

License: GPL-2.0-or-later. Upstream dependencies retain their original licenses.
