# Changelog

## 1.1.1

- Requires `sympress/doctrine-bundle` 1.0.5: purges never touch tables with the
  WordPress prefix. Outside WordPress, set `DB_TABLE_PREFIX` before running
  fixture commands.

## 1.1.0

- Migration-style fixtures: `doctrine:fixtures:status`, `doctrine:fixtures:up`
  and `doctrine:fixtures:down`, tracked in `doctrine_fixture_versions`.
- `RevertibleFixtureInterface` with `down()` for revertible fixtures.
- References from earlier batches are restored for later fixtures.
- `up` and `down` are hidden in production like `load`.
- Requires `doctrine/dbal` 4.5.

## 1.0.0

- Native DoctrineFixturesBundle integration for the SymPress kernel, discovered
  through `extra.kernel` on top of `sympress/doctrine-bundle`.
- `doctrine:fixtures:load` is not registered in `prod`/`production` kernels.
