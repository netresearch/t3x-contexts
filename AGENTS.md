<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md

**Project:** netresearch/contexts — Multi-channel content visibility for TYPO3
**Type:** TYPO3 CMS Extension (PHP 8.2–8.5, TYPO3 13.4/14.3; version: see `ext_emconf.php`)

## Overview

The Contexts extension lets editors control which content elements, pages, and records are displayed based on configurable context conditions: domain/hostname, IP address ranges, GET/POST parameters, HTTP headers, session values, and logical combinations (AND/OR/NOT). Context types are extensible — developers create custom types by extending `AbstractContext` (see `Classes/AGENTS.md`).

## Setup / Getting Started

```bash
composer install
ddev start
ddev install-all          # Install TYPO3 v13 and v14
ddev install-v13          # or a single version
ddev install-v14
ddev render-docs          # Render documentation locally

# Access (credentials: admin / joh316)
https://v13.contexts.ddev.site/typo3/    # TYPO3 v13 backend
https://v14.contexts.ddev.site/typo3/    # TYPO3 v14 backend
https://docs.contexts.ddev.site/         # Local documentation
```

## Commands

```bash
# Pre-commit checks (run automatically via CaptainHook)
composer ci:test:php:cgl      # PHP-CS-Fixer check (PSR-12 + strict types)
composer ci:test:php:phpstan  # PHPStan level 10

# Testing
composer ci:test:php:unit        # PHPUnit unit tests
composer ci:test:php:functional  # PHPUnit functional tests (needs DB)
composer test:coverage           # Coverage report (needs PCOV/Xdebug)
composer test:mutation           # Infection mutation testing
composer test:fuzz               # Fuzz-testing instructions

# Fix commands
composer ci:cgl               # Fix code style
composer ci:rector:fix        # Apply Rector fixes

make help                     # Makefile wrappers: up, ci, test, docs, clean, ...
```

## Development

Workflow: branch from `main`, run `composer ci:cgl`, `ci:test:php:phpstan`, and `ci:test:php:unit` before committing, use Conventional Commits, push and open a PR — CI runs the full matrix.

### Code Quality Tools

| Tool | Config | Purpose |
|------|--------|---------|
| PHP-CS-Fixer | `Build/php-cs-fixer.php` | Code style (PSR-12) |
| PHPStan | `Build/phpstan.neon` | Static analysis (level 10) |
| PHPUnit | `Build/phpunit/*.xml` | Unit & functional tests |
| CaptainHook | `Build/captainhook.json` | Git hooks (installed via `captainhook/hook-installer`) |
| Rector | `Build/rector.php` | Automated refactoring |
| Fractor | `fractor.php` | TYPO3-specific migrations |

### Extension Best Practices

- **No composer.lock**: not committed (gitignored), per TYPO3 extension convention
- **Flat columns**: `tx_contexts_enable`/`tx_contexts_disable` drive context visibility

## Critical Constraints

- **Keep PRs small** (~300 net LOC)
- **Conventional Commits**: `type(scope): subject`
- **Ask before**: heavy dependencies, architecture changes, new context types
- **Never commit** secrets, credentials, or PII
- **Database queries**: Always use `Connection::PARAM_*` (not `PDO::PARAM_*`)
- **Testing**: Functional tests need database credentials (auto-detected in DDEV)

## Precedence

The **closest AGENTS.md** to changed files wins. This root file holds global defaults only. User prompts override files.

## Index of scoped AGENTS.md

| Path | Purpose |
|------|---------|
| [Classes/AGENTS.md](./Classes/AGENTS.md) | PHP backend code, context types, services |
| [Configuration/AGENTS.md](./Configuration/AGENTS.md) | TCA, FlexForms, Services.yaml, Site Sets |
| [Tests/AGENTS.md](./Tests/AGENTS.md) | Testing patterns, fixtures, functional test setup |
| [Documentation/AGENTS.md](./Documentation/AGENTS.md) | RST documentation standards |

## Project Structure

```
Classes/           # PHP source (Api, Context/Type, Service, Form, Middleware,
                   #   EventListener, ExpressionLanguage, Query, ViewHelpers, Xclass)
Tests/             # Unit/, Functional/, Architecture/ (PHPat), Fuzz/
Configuration/     # TCA, FlexForms, Services.yaml, Sets/ (Site Sets)
Documentation/     # RST documentation for docs.typo3.org
Build/             # Tooling configs (phpstan, phpunit, rector, captainhook, Scripts/)
Resources/         # Frontend assets, language files
docs/              # Agent-facing docs: ARCHITECTURE.md, plans/, exec-plans/
```

Component map and dependency rules: see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## CI Workflows

| Workflow | Purpose |
|----------|---------|
| `ci.yml` | Test matrix via reusable `netresearch/typo3-ci-workflows` (PHP 8.2–8.5 × TYPO3 ^13.4/^14.3, MySQL functional tests, coverage upload) |
| `checks.yml` | Consolidated security/quality gate (gitleaks, zizmor, fuzz, license check, CodeQL, Scorecard, dependency review, PR quality) |
| `harness-verify.yml` | Agent-harness consistency (AGENTS.md budget, references, docs/ structure) |
| `docs.yml` | Render RST documentation |
| `release.yml` | Signed release with SBOM + cosign on tag `v*` |
| `publish-to-ter.yml` | Publish to TYPO3 Extension Repository on release |

Full list in `.github/workflows/` (labeler, community, template drift, republish, auto-merge-deps, and standalone security workflows).

## Key Conventions

- New context types extend `AbstractContext`, implement `match()`, and are configured via FlexForms in `Configuration/FlexForms/ContextType/` — full pattern in [Classes/AGENTS.md](Classes/AGENTS.md)
- PSR-14 event listeners use the `#[AsEventListener]` attribute (no manual registration)
- `ContextRestriction` (implements `EnforceableQueryRestrictionInterface`) filters `pages`/`tt_content` automatically via the flat columns

## Resources

- [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- [Extension Documentation](https://docs.typo3.org/p/netresearch/contexts/main/en-us/)
- [GitHub Issues](https://github.com/netresearch/t3x-contexts/issues)

## Commit Signing

Signed commits are required: `git commit -S --signoff`. The `require-signed-commits` ruleset rejects unsigned commits at merge time; the DCO check additionally requires the `Signed-off-by` trailer. Quickest setup is SSH signing: register your SSH key as a *signing key* on GitHub, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
