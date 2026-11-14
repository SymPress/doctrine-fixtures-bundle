# SymPress Doctrine Fixtures Bundle

Adapts the original DoctrineFixturesBundle to the SymPress kernel on top of
`sympress/doctrine-bundle`.

## Invariants

- Delegate to the upstream bundle; do not copy its services, loader, purger or command.
- Keep the native `doctrine_fixtures` alias, `doctrine.fixture.orm` tag and
  `doctrine:fixtures:load` command.
- Never purge or load data during bundle discovery, boot or request handling.
- Never touch tables that are not mapped by Doctrine (WordPress-owned data).

## Verification

- Full: `composer qa` (strict lint, coding standard, PHPStan and PHPUnit).
