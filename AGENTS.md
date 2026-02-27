<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-02-27 -->

# AGENTS.md

**Project:** netresearch/contexts — Multi-channel content visibility for TYPO3
**Type:** TYPO3 CMS Extension (PHP 8.2+, TYPO3 12.4/13.4)

## Overview

The Contexts extension provides multi-channel content visibility for TYPO3 CMS. It allows content editors to control which content elements, pages, and records are displayed based on configurable context conditions such as:

- Domain/hostname matching
- IP address ranges
- GET/POST parameters
- Cookies
- HTTP headers
- Session values
- Logical combinations (AND/OR/NOT)

Context types are extensible - developers can create custom context implementations by extending `AbstractContext`.

## Setup / Getting Started

```bash
# DDEV setup (recommended)
cd main && composer install
ddev start
ddev install-all          # Install TYPO3 v12 and v13

# Or install specific version
ddev install-v12          # TYPO3 v12 only
ddev install-v13          # TYPO3 v13 only

# Render documentation locally
ddev render-docs

# Access
https://v12.contexts.ddev.site/typo3/    # TYPO3 v12 backend
https://v13.contexts.ddev.site/typo3/    # TYPO3 v13 backend
https://docs.contexts.ddev.site/         # Local documentation

# Credentials: admin / joh316
```

## Commands

```bash
# Pre-commit checks (automatic via CaptainHook)
composer ci:test:php:cgl      # PHP-CS-Fixer (PSR-12 + strict types)
composer ci:test:php:phpstan  # PHPStan level 10

# Testing
composer ci:test:php:unit        # PHPUnit unit tests
composer ci:test:php:functional  # PHPUnit functional tests (needs DB)
composer test:coverage           # Coverage report (needs PCOV/Xdebug)

# Fix commands (for local development)
composer ci:cgl               # Fix code style
composer ci:rector:fix        # Apply Rector fixes
```

## Development

### Development Workflow

1. **Create feature branch** from `main`
2. **Make changes** following conventions in this file
3. **Run local checks** before committing:
   ```bash
   composer ci:cgl                # Fix code style
   composer ci:test:php:phpstan   # Static analysis
   composer ci:test:php:unit      # Unit tests
   ```
4. **Commit** using conventional commits format
5. **Push** and create PR - CI will run full test suite

### PHPUnit Configuration

PHPUnit configs are located in `Build/phpunit/`:
- `Build/UnitTests.xml` -> `Build/phpunit/UnitTests.xml`
- `Build/FunctionalTests.xml` -> `Build/phpunit/FunctionalTests.xml`

Run tests directly via:
```bash
./vendor/bin/phpunit -c Build/UnitTests.xml
./vendor/bin/phpunit -c Build/FunctionalTests.xml
```

### Code Quality Tools

| Tool | Config | Purpose |
|------|--------|---------|
| PHP-CS-Fixer | `.php-cs-fixer.dist.php` | Code style (PSR-12) |
| PHPStan | `Build/phpstan.neon` | Static analysis (level 10) |
| PHPUnit | `Build/phpunit/*.xml` | Unit & functional tests |
| CaptainHook | `Build/captainhook.json` | Git hooks (pre-commit, commit-msg) |
| Rector | `rector.php` | Automated refactoring |
| Fractor | `fractor.php` | TYPO3-specific migrations |

### Extension Best Practices

- **No composer.lock**: Extensions should not commit `composer.lock` (it is gitignored)
- **Environment files**: `.env` files are gitignored for security
- **Flat columns**: Use `tx_contexts_enable`/`tx_contexts_disable` for context visibility

## Critical Constraints

- **Keep PRs small** (~300 net LOC)
- **Conventional Commits**: `type(scope): subject`
- **Ask before**: heavy dependencies, architecture changes, new context types
- **Never commit** secrets, credentials, or PII
- **CaptainHook** runs pre-commit checks automatically
- **Database queries**: Always use `Connection::PARAM_*` (not `PDO::PARAM_*`)
- **Testing**: Functional tests need database credentials (auto-detected in DDEV)

## Precedence

The **closest AGENTS.md** to changed files wins. This root file holds global defaults only.

## Index of Scoped AGENTS.md

| Path | Purpose |
|------|---------|
| `Classes/AGENTS.md` | PHP backend code, context types, services |
| `Configuration/AGENTS.md` | TCA, FlexForms, Services.yaml, Site Sets |
| `Tests/AGENTS.md` | Testing patterns, fixtures, functional test setup |
| `Documentation/AGENTS.md` | RST documentation standards |

## Project Structure

```
Classes/           # PHP source code (34 files)
├── Context/       # Context type implementations
├── Service/       # Business logic services
└── Form/          # Backend form elements
Tests/             # Test suite
├── Unit/          # Unit tests
├── Functional/    # Functional tests (needs TYPO3)
├── Architecture/  # PHPat layer tests
└── Fuzz/          # Property-based fuzz testing
Configuration/     # TYPO3 configuration (TCA, TypoScript, FlexForms)
Documentation/     # RST documentation for docs.typo3.org
Build/             # Build tooling configs (phpstan, phpunit, phpcs)
Resources/         # Frontend assets, language files
```

## CI Workflows

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `ci.yml` | push/PR | Full test suite (unit, functional, lint, phpstan) |
| `codeql.yml` | push/PR/schedule | CodeQL security analysis |
| `dependency-review.yml` | PR | Dependency vulnerability & license review |
| `docs.yml` | push/PR (Documentation/**) | Render RST documentation |
| `greetings.yml` | issue/PR opened | Welcome first-time contributors |
| `labeler.yml` | PR | Auto-label PRs by changed files |
| `license-check.yml` | push/PR/schedule | PHP dependency license audit |
| `lock.yml` | schedule | Lock resolved threads after 365 days |
| `pr-quality.yml` | PR | PR size check + solo-maintainer auto-approve |
| `publish-to-ter.yml` | release | Publish to TYPO3 Extension Repository |
| `release.yml` | tag v* | Create signed release with SBOM + cosign |
| `scorecard.yml` | push/schedule | OpenSSF Scorecard security scan |
| `security.yml` | push/PR/schedule | Gitleaks + composer audit |
| `stale.yml` | schedule | Close stale issues/PRs after 60 days |
| `auto-merge-deps.yml` | PR | Auto-merge Renovate dependency updates |

## Key Conventions

### Context Types

New context types extend `AbstractContext` and implement:
- `match()`: Determine if context is active
- Configuration via FlexForms in `Configuration/FlexForms/`

```php
// Context type implementation pattern
class MyContext extends AbstractContext
{
    public function match(array $arDependencies = []): bool
    {
        // 1. Check session cache first (if use_session enabled)
        [$fromSession, $result] = $this->getMatchFromSession();
        if ($fromSession) {
            return $result;
        }

        // 2. Implement matching logic
        $configValue = $this->getConfValue('field_name');
        $matches = /* your logic here */;

        // 3. Apply inversion and store in session
        return $this->storeInSession($this->invert($matches));
    }
}
```

### PSR-14 Event Listeners

```php
// Use PHP 8 attributes for event listener registration
#[AsEventListener(
    identifier: 'contexts/my-listener',
    event: AfterPageAndLanguageIsResolvedEvent::class,
)]
final readonly class MyEventListener
{
    public function __invoke(AfterPageAndLanguageIsResolvedEvent $event): void
    {
        // Handle event
    }
}
```

### Query Restrictions

Context-based query restrictions filter records automatically:

```php
// ContextRestriction implements EnforceableQueryRestrictionInterface
// Applied automatically to pages, tt_content via flat columns
// Flat columns: tx_contexts_enable, tx_contexts_disable
```

## When Instructions Conflict

Nearest AGENTS.md wins. User prompts override files.

## Resources

- [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- [Extension Documentation](https://docs.typo3.org/p/netresearch/contexts/main/en-us/)
- [GitHub Issues](https://github.com/netresearch/t3x-contexts/issues)
