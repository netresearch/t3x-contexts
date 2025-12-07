<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-12-08 -->

# AGENTS.md — Classes/

Backend PHP code for the Contexts extension.

## Overview

This directory contains the core PHP implementation:
- **Context/**: Context type implementations (GetParam, Domain, IP, etc.)
- **Service/**: Business logic (matching, data handling)
- **Form/**: Backend form elements for TCA
- **Middleware/**: PSR-15 middleware for context initialization

## Setup & Environment

```bash
composer install
ddev start && ddev install-v13
```

## Build & Tests

```bash
composer lint              # PHP_CodeSniffer
composer analyze           # PHPStan level 8
composer test:unit         # Unit tests for this code
```

## Code Style & Conventions

### PSR-12 + TYPO3 CGL

- Strict types: `declare(strict_types=1);`
- Final classes by default (unless designed for extension)
- Constructor property promotion where applicable
- Return types on all methods

### Namespace Pattern

```php
namespace Netresearch\Contexts\Context\Type;
namespace Netresearch\Contexts\Service;
```

### Dependency Injection

Prefer constructor injection via `Services.yaml`:

```php
public function __construct(
    private readonly ConnectionPool $connectionPool,
    private readonly Context $context,
) {}
```

### Database Access

```php
// Always use TYPO3's Connection constants (DBAL 4.x compatible)
use TYPO3\CMS\Core\Database\Connection;

$queryBuilder->createNamedParameter($value, Connection::PARAM_INT);
$queryBuilder->createNamedParameter($value, Connection::PARAM_STR);

// Never use PDO::PARAM_* directly
```

### TSFE Access

```php
// Safe access pattern (may be null in CLI/backend contexts)
$tsfe = $GLOBALS['TSFE'] ?? null;
if ($tsfe instanceof TypoScriptFrontendController) {
    // ...
}
```

## Security & Safety

- Validate all user input before database queries
- Use `createNamedParameter()` for all query values
- Never trust `$_GET`/`$_POST` directly — use PSR-7 request
- Context matching must not leak sensitive information

## PR/Commit Checklist

- [ ] `composer lint` passes
- [ ] `composer analyze` passes
- [ ] Unit tests added/updated for new functionality
- [ ] No `PDO::PARAM_*` constants (use `Connection::PARAM_*`)
- [ ] Strict types declared
- [ ] Return types on all methods

## Good vs Bad Examples

### Database Parameter Types

```php
// Good: TYPO3 Connection constants
$qb->createNamedParameter($uid, Connection::PARAM_INT)

// Bad: PDO constants (breaks DBAL 4.x)
$qb->createNamedParameter($uid, \PDO::PARAM_INT)
```

### Request Parameter Access

```php
// Good: PSR-7 request
$value = $request->getQueryParams()['param'] ?? null;

// Bad: Deprecated GeneralUtility
$value = GeneralUtility::_GET('param');

// Acceptable fallback (when no request available)
$value = $_GET['param'] ?? null;
```

### Form Element IDs

```php
// Good: Generate from itemFormElName (v12+ compatible)
$baseId = str_replace(['[', ']'], '_', $parameterArray['itemFormElName']);
$baseId = trim($baseId, '_');

// Bad: itemFormElID removed in v12
$id = $parameterArray['itemFormElID']; // Undefined!
```

## When Stuck

- TYPO3 Core API: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/
- Extension issues: https://github.com/netresearch/t3x-contexts/issues
- TYPO3 Slack: #typo3-cms

## House Rules

- Context types must work in both frontend and backend contexts
- All public APIs must remain backwards-compatible within major versions
- FlexForm configurations go in `Configuration/FlexForms/`
