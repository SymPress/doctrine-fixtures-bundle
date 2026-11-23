# Changelog

## 1.1.2 — 2026-11-23

- Requires `sympress/doctrine-bundle` 1.0.7: purges also keep WordPress tables
  that an entity maps with a quoted name such as `` `wp_users` ``.
- `doctrine:fixtures:down --group` reverts only the fixtures of the group, not
  their dependencies and the fixtures that depend on those.
- `up` and `down` skip stored references to removed entity classes or without
  identity and warn about them, instead of failing until the tracking row is
  fixed by hand.
- CI: pin the shared `sympress/workflows` workflows to v1.3.2 and let Dependabot
  update GitHub Actions pins.

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
