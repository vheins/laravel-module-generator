# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Full open-source community files: `README.md`, `LICENSE`, `SECURITY.md`,
  `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `.gitattributes`, `.editorconfig`,
  CI workflow (`.github/workflows/tests.yml`), PR/issue templates, and `FUNDING.yml`.

## [1.0.0] - 2026-08-22

### Added

- Initial public release as `vheins/laravel-module-generator` — Laravel module
  generator built on `nwidart/laravel-modules`.
- 19 artisan commands via `LaravelModuleGeneratorServiceProvider::COMMANDS`:
  `create:module`, `create:module:sub`, `create:module:model`,
  `create:module:controller`, `create:module:migration`, `create:module:factory`,
  `create:module:seeder`, `create:module:request`, `create:module:action`,
  `create:module:vue:component:tab`, `create:module:vue:component:link`,
  `create:module:vue:component:form`, `create:module:vue:component:filter`,
  `create:module:vue:page:index`, `create:module:vue:page:new`,
  `create:module:vue:page:view`, `create:module:vue:store`, `create:permission`,
  `create:api:crud`.
- Support for PHP `^8.2 || ^8.3 || ^8.4` and Laravel `^11.0 || ^12.0 || ^13.0`
  (including Laravel 13) with `nwidart/laravel-modules` `^v10.0 || ^v11.0 || ^v12.0 || ^v13.0`.
- Blueprint-driven scaffolding (`.blueprint/*.yaml` via `symfony/yaml`).
- Vue component/page/store (Pinia) generation with `stubs/modular/vue/*` templates.
- Orchestra Testbench + PHPUnit harness (`tests/Unit/Compatibility` and `tests/Unit/Console`).

### Changed

- feat(generator): TASK-001 — Laravel 13 compatibility: widened `laravel/framework`
  constraint to admit 13.x, `php` to 8.3/8.4, `symfony/yaml` to `^7.4 || ^8.0`,
  `orchestra/testbench` to `^9 || ^10 || ^11`; added compatibility contract tests
  (`tests/Unit/Compatibility/Laravel13CompatibilityTest.php`); verified factory and
  migration stubs against the Laravel 13 API surface.

### Fixed

- fix(security): replaced any hardcoded or placeholder real `APP_KEY` values with
  the testing-only dummy `base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=`
  (base64 of 32 zero bytes) in `phpunit.xml.dist` and `testbench.yaml`. The value
  is explicitly annotated as NOT a production secret.

### Chore

- chore: expanded `.gitignore` to cover Composer artefacts, `/.blueprint/`,
  `.phpunit.cache`, `coverage/`, `build/`, `.env*`, and IDE/OS files; added
  `/modules/` for generated workbench modules.

[Unreleased]: https://github.com/vheins/laravel-module-generator/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/vheins/laravel-module-generator/releases/tag/v1.0.0
