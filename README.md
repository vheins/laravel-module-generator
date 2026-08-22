# Laravel Module Generator

[![Packagist Version](https://img.shields.io/packagist/v/vheins/laravel-module-generator?style=flat-square)](https://packagist.org/packages/vheins/laravel-module-generator)
[![Packagist Downloads](https://img.shields.io/packagist/dt/vheins/laravel-module-generator?style=flat-square)](https://packagist.org/packages/vheins/laravel-module-generator)
[![License: MIT](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/github/actions/workflow/status/vheins/laravel-module-generator/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/vheins/laravel-module-generator/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2%20%7C%7C%20%5E8.3%20%7C%7C%20%5E8.4-777BB4?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11%20%7C%7C%20%5E12%20%7C%7C%20%5E13-FF2D20?style=flat-square)](https://laravel.com)

Generate Laravel modules from opinionated templates — scaffold models, migrations, factories, seeders, requests, actions, controllers, Vue components/pages/stores, permissions, and full blueprint-driven module trees in seconds.

Built on top of [`nwidart/laravel-modules`](https://github.com/nWidart/laravel-modules) (`^v10.0 || ^v11.0 || ^v12.0 || ^v13.0`). Extends its `module:make` workflow with 19 additional `create:*` artisan commands and a `stubs/modular` template layer that also wires routes, dashboard links, and API resources automatically.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Publishing Stubs](#publishing-stubs)
- [Usage](#usage)
  - [Blueprint-Driven Generation](#blueprint-driven-generation)
  - [Sub-Module Scaffold](#sub-module-scaffold)
  - [Model, Migration, Factory, Seeder, Request, Action](#model-migration-factory-seeder-request-action)
  - [Controller](#controller)
  - [Vue Components, Pages & Stores](#vue-components-pages--stores)
  - [Permissions & API CRUD (non-modular)](#permissions--api-crud-non-modular)
  - [All 19 Commands Reference](#all-19-commands-reference)
- [Stub Customization](#stub-customization)
- [Testing](#testing)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)

---

## Requirements

| Dependency | Constraint | Notes |
|---|---|---|
| PHP | `^8.2 \|\| ^8.3 \|\| ^8.4` | As declared in `composer.json` |
| Laravel Framework | `^11.0 \|\| ^12.0 \|\| ^13.0` | Includes Laravel 13 support |
| `nwidart/laravel-modules` | `^v10.0 \|\| ^v11.0 \|\| ^v12.0 \|\| ^v13.0` | |
| `lorisleiva/laravel-actions` | `^v2.7.1` | |
| `symfony/yaml` | `^7.4 \|\| ^8.0` | For `.blueprint` YAML parsing |

> Supported matrix verified by `tests/Unit/Compatibility/Laravel13CompatibilityTest.php` (17 compatibility contract tests covering version constraints, provider registration, and stub API surface).

---

## Installation

Install via Composer (package name is `vheins/laravel-module-generator` — see `composer.json`):

```bash
composer require vheins/laravel-module-generator
```

The service provider `Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider` is auto-discovered via `extra.laravel.providers` in `composer.json` — no manual registration required.

If auto-discovery is disabled, register it manually in `config/app.php`:

```php
'providers' => [
    Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider::class,
],
```

---

## Configuration

The package ships two config files:

- `laravel-module-generator.php` — package-specific settings (`name => 'LaravelModuleGenerator'`).
- `modules.php` — full `nwidart/laravel-modules` configuration (namespace, stubs, paths, generators, activators, etc.).

Publish them with:

```bash
php artisan vendor:publish --provider="Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider" --tag=config
```

This copies `laravel-module-generator.php` to `config/laravel-module-generator.php` and `modules.php` to `config/modules.php`.

Key `modules.php` settings to be aware of:

- `namespace` defaults to `Vheins` — change to your organization namespace.
- `stubs.path` defaults to `base_path('stubs/modular')` — see [Stub Customization](#stub-customization).
- `paths.modules` defaults to `base_path('modules')` — generated modules land here.
- `composer.vendor` defaults to `vheins` and `composer.author` to `Muhammad Rheza Alfin` — update for your own scaffolds.

---

## Publishing Stubs

Publish the full stub tree to `base_path('stubs')` for local customization:

```bash
php artisan vendor:publish --provider="Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider" --tag=stubs
```

Stubs published include:

- Top-level stubs (`stubs/*.stub`) — controllers, models, factories, migrations, actions, etc.
- Modular stubs (`stubs/modular/**`) — Vue components (`component.filter`, `component.form.*`, `component.link`, `icontab`), Vue pages (`page.create`, `page.index`, `page.[id]`), `store.pinia`, migrations, routes, scaffold config, etc.

Edit any published stub in place; subsequent generator runs will use your modified version.

---

## Usage

All commands are registered by `LaravelModuleGeneratorServiceProvider::COMMANDS` (the canonical 19-command inventory) and are available only when `runningInConsole()` or `runningUnitTests()`.

### Blueprint-Driven Generation

Generate an entire module tree from a YAML blueprint file placed in `.blueprint/`:

```bash
php artisan create:module --blueprint=example.yaml
```

The command reads `.blueprint/{file}` via `symfony/yaml`, iterates `module → subModule → tables`, and for each entry:

- calls `create:module:sub` (with `fillable` and `db-only` derived from `Fillable`/`CRUD` keys),
- runs `CreateRelation` when `Relation` is present,
- runs `CreateQuery` when `Query: true` and collects query slugs for `FixQueryApi`.

Blueprint shape (keys are case-sensitive as parsed):

```yaml
Invoice:
  InvoiceHeader:
    Fillable:
      number: string
      total: decimal
      company_id: foreignUuid
    CRUD: true
    Relation:
      # relation definition consumed by CreateRelation
    Query: true
  InvoiceLine:
    Fillable:
      invoice_header_id: foreignUuid
      amount: decimal
    CRUD: false   # db-only — no Vue/controllers/forms
```

Ends with `optimize:clear`.

### Sub-Module Scaffold

The heavy lifter — creates a complete sub-module inside a parent module (creates the parent via `module:make --api` if it does not exist):

```bash
php artisan create:module:sub Invoice InvoiceLine --fillable="title:string,amount:decimal,company_id:foreignUuid"
php artisan create:module:sub Invoice InvoiceLine --fillable="title:string" --db-only
```

- `module` (required) — parent module name (StudlyCase).
- `name` (required) — sub-module / entity name.
- `--fillable` — comma-separated `field:type` pairs (e.g., `title:string,amount:decimal`).
- `--db-only` — when present, skips Vue components, controllers, requests, actions, routes, dashboard links, and store/pages.

When not `--db-only`, also generates Vue tab/link components, model + migration + factory + seeder, API controller, request, Store/Update/Delete actions, API route, dashboard link/tab entries, and Vue store + pages + form + filter, then runs `optimize:clear`.

### Model, Migration, Factory, Seeder, Request, Action

```bash
# Model (with optional chained generators)
php artisan create:module:model Product Invoice --fillable="title:string,price:decimal"
php artisan create:module:model Product Invoice --fillable="title:string" --migration --controller --seed --request
# -m / --migration  also creates migration
# -c / --controller also creates controller
# -s / --seed       also creates seeder
# -r / --request    also creates request

# Migration
php artisan create:module:migration create_products_table create_products Invoice --fields="title:string,price:decimal"
php artisan create:module:migration create_products_table create_products Invoice --plain
# basename (arg 1), name (arg 2), module (arg 3 optional)

# Factory
php artisan create:module:factory Product Invoice --fillable="title:string,price:decimal"

# Seeder
php artisan create:module:seeder Product Invoice
php artisan create:module:seeder Product Invoice --master   # appends Database suffix

# Request
php artisan create:module:request ProductRequest Invoice --fillable="title:string,price:decimal"

# Action (lorisleiva/laravel-actions style)
php artisan create:module:action Product/Store Invoice
php artisan create:module:action Product/Update Invoice
```

### Controller

```bash
php artisan create:module:controller Product Invoice
php artisan create:module:controller Product Invoice --api     # API resource controller
php artisan create:module:controller Product Invoice --plain   # plain controller (-p)
```

### Vue Components, Pages & Stores

All Vue generators accept `{name} {module?} [--fillable=]` where `name` may be a compound like `InvoiceProduct`:

```bash
# Components
php artisan create:module:vue:component:tab   InvoiceProduct Invoice --fillable="title:string"
php artisan create:module:vue:component:link  InvoiceProduct Invoice --fillable="title:string"
php artisan create:module:vue:component:form  InvoiceProduct Invoice --fillable="title:string,amount:decimal"
php artisan create:module:vue:component:filter InvoiceProduct Invoice --fillable="title:string"

# Pages
php artisan create:module:vue:page:index InvoiceProduct Invoice --fillable="title:string"
php artisan create:module:vue:page:new   InvoiceProduct Invoice --fillable="title:string"
php artisan create:module:vue:page:view  InvoiceProduct Invoice --fillable="title:string"

# Pinia store
php artisan create:module:vue:store InvoiceProduct Invoice --fillable="title:string"
```

Generated paths (relative to `modules/{Module}/`):

- Components → `Vue/components/`
- Pages → `Vue/pages/`
- Stores → `Vue/store/` (or `vue/stores` per `modules.php` generator config)

### Permissions & API CRUD (non-modular)

These two commands operate on the host application (not inside `modules/`):

```bash
# Create 5 CRUD permissions for a resource (index/create/view/edit/delete)
php artisan create:permission posts
# creates: posts-index, posts-create, posts-view, posts-edit, posts-delete
# via App\Models\Permission::firstOrCreate()

# Scaffold a plain (non-modular) API CRUD: model + migration, requests, actions, controller
php artisan create:api:crud Post
php artisan create:api:crud Post --action   # also generates Store/Update/Delete actions
```

### All 19 Commands Reference

| # | Command | Class | Arguments | Options |
|---|---|---|---|---|
| 1 | `create:module` | `CreateModule` | — | `--blueprint=` (required file under `.blueprint/`) |
| 2 | `create:module:sub` | `CreateModuleSub` | `module` (required), `name` (required) | `--fillable=` `field:type,…`, `--db-only` |
| 3 | `create:module:model` | `CreateModuleModel` | `model` (required), `module` (optional) | `--fillable=` `--migration` `-m` `--controller` `-c` `--seed` `-s` `--request` `-r` |
| 4 | `create:module:controller` | `CreateModuleController` | `controller` (required), `module` (optional) | `--plain` `-p`, `--api` |
| 5 | `create:module:migration` | `CreateModuleMigration` | `basename` (required), `name` (required), `module` (optional) | `--fields=` `--plain` |
| 6 | `create:module:factory` | `CreateModuleFactory` | `name` (required), `module` (required) | `--fillable=` |
| 7 | `create:module:seeder` | `CreateModuleSeeder` | `name` (required), `module` (optional) | `--master` |
| 8 | `create:module:request` | `CreateModuleRequest` | `name` (required), `module` (optional) | `--fillable=` |
| 9 | `create:module:action` | `CreateModuleAction` | `name` (required), `module` (optional) | — |
| 10 | `create:module:vue:component:tab` | `CreateModuleVueComponentTab` | `name` (required), `module` (optional) | `--fillable=` |
| 11 | `create:module:vue:component:link` | `CreateModuleVueComponentLink` | `name` (required), `module` (optional) | `--fillable=` |
| 12 | `create:module:vue:component:form` | `CreateModuleVueComponentForm` | `name` (required), `module` (optional) | `--fillable=` |
| 13 | `create:module:vue:component:filter` | `CreateModuleVueComponentFilter` | `name` (required), `module` (optional) | `--fillable=` |
| 14 | `create:module:vue:page:index` | `CreateModuleVuePageIndex` | `name` (required), `module` (optional) | `--fillable=` |
| 15 | `create:module:vue:page:new` | `CreateModuleVuePageCreate` | `name` (required), `module` (optional) | `--fillable=` |
| 16 | `create:module:vue:page:view` | `CreateModuleVuePageView` | `name` (required), `module` (optional) | `--fillable=` |
| 17 | `create:module:vue:store` | `CreateModuleVueStore` | `name` (required), `module` (optional) | `--fillable=` |
| 18 | `create:permission` | `CreatePermission` | `name` (required) | — |
| 19 | `create:api:crud` | `CreateApiCrud` | `name` (required) | `--action` |

> Command list verified against `Providers/LaravelModuleGeneratorServiceProvider.php::COMMANDS` and individual `Console/*.php` signatures. File `CreateModuleVuePageCreate` registers as `create:module:vue:page:new` (not `create`).

---

## Stub Customization

After publishing stubs:

```bash
php artisan vendor:publish --provider="Vheins\LaravelModuleGenerator\Providers\LaravelModuleGeneratorServiceProvider" --tag=stubs
```

- Edit any file under `stubs/` or `stubs/modular/` — the generators resolve stubs through `Nwidart\Modules\Support\Stub`, which prefers published paths.
- `modules.php` key `stubs.replacements` controls token replacements (`LOWER_NAME`, `STUDLY_NAME`, `MODULE_NAMESPACE`, etc.).
- Generator destinations are controlled by `modules.php` → `paths.generator.*` (`model → Models`, `controller → Controllers`, `factory → database/factories`, `vue → vue`, `vue-components → Vue/components`, etc.).
- Set a generator's `generate` flag to `false` to skip that path entirely.

---

## Testing

```bash
composer install
vendor/bin/phpunit --no-coverage
```

`--no-coverage` is required because `phpunit.xml.dist` sets `failOnWarning="true"` — the coverage text reporter emits a warning when no coverage driver is present, which would otherwise fail the run. CI uses the same flag.

Test suite layout:

- `tests/Unit/Compatibility/Laravel13CompatibilityTest.php` — 17 tests: version-constraint matrix, provider/command registration, stub API guards.
- `tests/Unit/Console/*Test.php` — per-command generation tests (one file per command class).
- `tests/TestCase.php` + `testbench.yaml` — Orchestra Testbench harness (SQLite in-memory, `APP_KEY` is a testing-only dummy).

Additional tooling:

```bash
vendor/bin/pint --test        # style check (Laravel Pint)
vendor/bin/phpstan analyse    # static analysis (PHPStan)
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full history. This project follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/).

---

## Security

If you discover a security vulnerability, please follow the process in [SECURITY.md](SECURITY.md) — do **not** open a public issue.

---

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) and the [Code of Conduct](CODE_OF_CONDUCT.md) before opening a PR.

Quick start:

```bash
composer install
vendor/bin/phpunit --no-coverage
vendor/bin/pint
vendor/bin/phpstan analyse
```

---

## License

MIT — see [LICENSE](LICENSE).

---

## Credits

- Original author: [Muhammad Rheza Alfin](mailto:m.rheza.alfin@gmail.com) (`composer.json` author field).
- Maintained by [Vheins](https://github.com/vheins).
- Built on [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules) and [lorisleiva/laravel-actions](https://github.com/lorisleiva/laravel-actions).
