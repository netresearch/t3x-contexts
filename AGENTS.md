<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-12-08 -->

# AGENTS.md

**Project:** netresearch/contexts — Multi-channel content visibility for TYPO3
**Type:** TYPO3 CMS Extension (PHP 8.2+, TYPO3 12.4/13.4)

## Precedence

The **closest AGENTS.md** to changed files wins. This root file holds global defaults only.

## Global Rules

- Keep PRs small (~300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy dependencies, architecture changes, new context types
- Never commit secrets, credentials, or PII
- GrumPHP runs pre-commit checks automatically

## Pre-Commit Checks (GrumPHP)

```bash
# Automatic on commit (via GrumPHP):
composer lint          # PHP_CodeSniffer (PSR-12 + TYPO3 CGL)
composer analyze       # PHPStan level 8

# Manual testing:
composer test:unit        # PHPUnit unit tests
composer test:functional  # PHPUnit functional tests (needs DB)
composer test:coverage    # Coverage report (needs PCOV/Xdebug)
```

## Development Environment

```bash
# DDEV setup (recommended)
ddev start
ddev install-all          # Install TYPO3 v11, v12, v13

# Access
https://v12.contexts.ddev.site/typo3/    # TYPO3 v12 backend
https://v13.contexts.ddev.site/typo3/    # TYPO3 v13 backend
https://docs.contexts.ddev.site/         # Local documentation

# Credentials: admin / Password:joh316
```

## CI Workflows

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `ci.yml` | push/PR | Full test suite (unit, functional, lint, phpstan) |
| `phpstan.yml` | push/PR | Static analysis |
| `phpcs.yml` | push/PR | Code style |
| `security.yml` | schedule | Dependency vulnerability scan |
| `publish-to-ter.yml` | tag | Publish to TYPO3 Extension Repository |

## Project Structure

```
Classes/           # PHP source code (34 files)
├── Context/       # Context type implementations
├── Service/       # Business logic services
└── Form/          # Backend form elements
Tests/             # Test suite (41 files)
├── Unit/          # Unit tests
└── Functional/    # Functional tests (needs TYPO3)
Configuration/     # TYPO3 configuration (TCA, TypoScript, FlexForms)
Documentation/     # RST documentation for docs.typo3.org
Build/             # Build tooling configs (phpstan, phpunit, phpcs)
Resources/         # Frontend assets, language files
```

## Index of Scoped AGENTS.md

| Path | Purpose |
|------|---------|
| `Classes/AGENTS.md` | PHP backend code, context types, services |
| `Tests/AGENTS.md` | Testing patterns, fixtures, functional test setup |
| `Documentation/AGENTS.md` | RST documentation standards |

## Key Conventions

### Context Types

New context types extend `AbstractContext` and implement:
- `match()`: Determine if context is active
- Configuration via FlexForms in `Configuration/FlexForms/`

### Database Queries

```php
// Always use Connection::PARAM_* (not PDO::PARAM_*)
$queryBuilder->createNamedParameter($value, Connection::PARAM_INT)
```

### Testing

```php
// Functional tests need database credentials (auto-detected in DDEV)
// Use session isolation in fixtures: use_session=0
```

## When Instructions Conflict

Nearest AGENTS.md wins. User prompts override files.

## Resources

- [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- [Extension Documentation](https://docs.typo3.org/p/netresearch/contexts/main/en-us/)
- [GitHub Issues](https://github.com/netresearch/t3x-contexts/issues)
