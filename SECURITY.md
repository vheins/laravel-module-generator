# Security Policy

## Supported Versions

| Version | PHP | Laravel | Status |
|---|---|---|---|
| 1.x | `^8.2 \|\| ^8.3 \|\| ^8.4` | `^11.0 \|\| ^12.0 \|\| ^13.0` | Supported |
| < 1.0 | — | — | Unsupported |

The PHP/Laravel matrix above mirrors `composer.json` (`require.php` and
`require.laravel/framework`). `nwidart/laravel-modules` `^v10 \|\| ^v11 \|\| ^v12 \|\| ^v13` is
supported across all listed versions.

## Reporting a Vulnerability

**Do not open a public GitHub issue for security reports.**

Instead, report privately via one of:

- Email the maintainer listed in `composer.json` (`m.rheza.alfin@gmail.com`) with
  subject `[SECURITY] laravel-module-generator`.
- GitHub: use **Security → Report a vulnerability** (private advisory) on this
  repository.

Include:

- Affected version / commit
- Steps to reproduce (or proof-of-concept)
- Impact assessment
- Suggested fix if you have one

You will receive an acknowledgement within **72 hours**. If the report is
confirmed, a fix will be prepared and released under embargo; you will be
credited in the advisory unless you request otherwise.

## What Not to Report Publicly

- The dummy `APP_KEY` value `base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=`
  in `phpunit.xml.dist` / `testbench.yaml` — this is intentionally a 32-zero-byte
  placeholder for the test suite and is annotated as not a production secret.
