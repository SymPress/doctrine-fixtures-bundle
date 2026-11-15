# Changelog

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
