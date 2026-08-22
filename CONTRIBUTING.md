# Contributing

Thanks for considering a contribution to `vheins/laravel-module-generator`.

Please read the [Code of Conduct](CODE_OF_CONDUCT.md) before participating.

## Table of Contents

- [Local Setup](#local-setup)
- [Testing](#testing)
- [Code Style & Static Analysis](#code-style--static-analysis)
- [Branch & PR Flow](#branch--pr-flow)
- [Commit Convention](#commit-convention)
- [Adding or Changing a Generator Command](#adding-or-changing-a-generator-command)

## Local Setup

Requirements: PHP `^8.2 || ^8.3 || ^8.4`, Composer 2.x.

```bash
git clone https://github.com/vheins/laravel-module-generator.git
cd laravel-module-generator
composer install
```

The project uses [Orchestra Testbench](https://packages.tools/testbench.html) so it
boots a real Laravel application in tests without a host app. Configuration lives in
`testbench.yaml` and `tests/TestCase.php`.

## Testing

Run the full suite (always with `--no-coverage`):

```bash
vendor/bin/phpunit --no-coverage
```

`--no-coverage` is required — `phpunit.xml.dist` has `failOnWarning="true"` and the
coverage text reporter emits a warning when no coverage driver is installed.

Useful filters:

```bash
# Single test class/file
vendor/bin/phpunit --no-coverage --filter Laravel13CompatibilityTest
vendor/bin/phpunit --no-coverage tests/Unit/Console/CreateModuleModelTest.php

# Compatibility group only
vendor/bin/phpunit --no-coverage --group compatibility
```

Test layout:

- `tests/Unit/Compatibility/Laravel13CompatibilityTest.php` — 17 version-constraint
  and provider/stub contract tests.
- `tests/Unit/Console/*Test.php` — per-command generation tests (one file per command
  class in `Console/`).
- `tests/TestCase.php` + `testbench.yaml` — harness (SQLite in-memory, testing-only
  dummy `APP_KEY`).

Do not commit generated artefacts under `modules/` or `.blueprint/` fixtures.

## Code Style & Static Analysis

```bash
# Check style (do not auto-fix in CI — CI runs --test)
vendor/bin/pint --test

# Auto-fix locally
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse
```

CI enforces `pint --test` and `phpstan` — run both before pushing.

## Branch & PR Flow

1. Fork the repository and create a feature branch from `master`:

   ```bash
   git checkout -b feat/your-feature
   # or fix/bug-description
   ```

2. Make focused, atomic commits (see [Commit Convention](#commit-convention)).
3. Ensure tests, style, and static analysis pass:

   ```bash
   vendor/bin/phpunit --no-coverage
   vendor/bin/pint --test
   vendor/bin/phpstan analyse
   ```

4. Push and open a pull request against `master`. Fill out the PR template
   (`.github/PULL_REQUEST_TEMPLATE.md`) — include description, testing notes,
   and checklist confirmations.
5. Address review feedback with additional commits (do not force-push after review
   has started unless requested).

Branch naming: `feat/*`, `fix/*`, `docs/*`, `chore/*`, `refactor/*`.

## Commit Convention

This project follows [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): description

# Examples
feat(generator): add --plain flag to create:module:factory
fix(stubs): correct factory faker call for Laravel 13
docs(readme): document create:permission options
chore(ci): run tests on php 8.4 + laravel 13
```

Types: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `perf`, `build`, `ci`.

Keep commits atomic — one logical change per commit. Reference issues with
`Refs #N` or `Fixes #N` in the body where applicable.

## Adding or Changing a Generator Command

1. Add the command class under `Console/` (extend `Nwidart\Modules\Commands\Make\GeneratorCommand`
   or `Illuminate\Console\Command` as appropriate).
2. Register it in `Providers/LaravelModuleGeneratorServiceProvider.php::COMMANDS` —
   this constant is the single source of truth and is asserted by
   `Laravel13CompatibilityTest::test_provider_command_classes_exist` and
   `test_provider_registered_commands_are_registered_on_artisan`.
3. Add a stub under `stubs/` or `stubs/modular/` if needed.
4. Add a test under `tests/Unit/Console/` (one file per command).
5. Update `README.md` (Usage / All 19 Commands Reference) and `CHANGELOG.md`.
