<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md — Tests/

Test suite for the Contexts extension.

## Overview

```
Tests/
├── Unit/           # Fast, isolated unit tests
└── Functional/     # Integration tests requiring TYPO3 + database
```

## Setup & Environment

```bash
# Install dependencies
composer install

# For functional tests in DDEV:
ddev start
# Database credentials auto-detected

# For functional tests outside DDEV:
export typo3DatabaseDriver=mysqli
export typo3DatabaseHost=127.0.0.1
export typo3DatabaseName=typo3_test
export typo3DatabaseUsername=root
export typo3DatabasePassword=root
```

## Build & Tests

```bash
# Run all tests
composer test

# Run specific suites
composer test:unit           # Unit tests only (~fast)
composer test:functional     # Functional tests (~slower, needs DB)

# Coverage (requires PCOV or Xdebug)
composer test:coverage

# Advanced testing
composer test:mutation       # Infection mutation testing
composer test:fuzz           # Fuzz testing
```

## Code Style & Conventions

### Test Class Naming

```php
// Unit test
Tests\Unit\Context\Type\QueryParameterContextTest

// Functional test
Tests\Functional\PageAccessTest
```

### Test Method Naming

Use descriptive names with `test` prefix:

```php
public function testMatchReturnsTrueWhenParameterMatches(): void
public function testMatchReturnsFalseWhenParameterMissing(): void
```

### Assertions

```php
// Prefer specific assertions
self::assertTrue($result);
self::assertSame('expected', $actual);
self::assertInstanceOf(Context::class, $object);

// Avoid generic assertEquals for strict comparisons
```

## Security & Safety

- Never use production data in tests
- Test fixtures must not contain real credentials
- Functional tests run in isolated database

## PR/Commit Checklist

- [ ] New functionality has corresponding tests
- [ ] All tests pass: `composer test`
- [ ] No skipped tests without justification
- [ ] Fixtures use `use_session=0` for session-based features

## Good vs Bad Examples

### Session State Isolation

```php
// Good: Disable session in fixtures to prevent test pollution
// In tx_contexts_contexts.csv:
// "uid","pid","title","use_session","disabled"
// 1,1,"Test Context",0,0

// Bad: Session state leaks between tests
// "uid","pid","title","use_session","disabled"
// 1,1,"Test Context",1,0
```

### Container Reset for Singletons

```php
// Good: Reset container before tests using singletons
protected function setUp(): void
{
    parent::setUp();
    GeneralUtility::resetSingletonInstances([]);
}

// Bad: Tests depend on singleton state from previous tests
```

### Functional Test Base Class

```php
// Good: Use TYPO3 testing framework base class
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageAccessTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];
}
```

### Database Fixture Loading

```php
// Good: Import fixtures in setUp
protected function setUp(): void
{
    parent::setUp();
    $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
    $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_contexts.csv');
}
```

## When Stuck

- TYPO3 Testing Framework: https://github.com/TYPO3/testing-framework
- PHPUnit Docs: https://docs.phpunit.de/
- Extension issues: https://github.com/netresearch/t3x-contexts/issues

## Extension-Specific Testing Patterns

### Testing Context Type Matching

```php
final class IpContextTest extends UnitTestCase
{
    public function testMatchReturnsTrueForMatchingIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $context = new IpContext([
            'uid' => 1,
            'type' => IpContext::class,
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => false,
            'use_session' => false,
            'disabled' => false,
            'hide_in_backend' => false,
            'type_conf' => '<T3FlexForms>...</T3FlexForms>',
        ]);

        self::assertTrue($context->match());
    }
}
```

### Testing with Context Container

```php
// Reset singleton between tests
protected function setUp(): void
{
    parent::setUp();
    GeneralUtility::resetSingletonInstances([]);
}
```

### Functional Test Fixtures

```csv
# Tests/Functional/Fixtures/tx_contexts_contexts.csv
"uid","pid","title","type","alias","use_session","disabled"
1,0,"IP Context","Netresearch\Contexts\Context\Type\IpContext","ip-test",0,0
```

### Testing Page Access with Contexts

```php
final class PageAccessTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['netresearch/contexts'];

    public function testPageHiddenByContext(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_contexts.csv');

        // Test that page is inaccessible when context doesn't match
    }
}
```

## House Rules

- Unit tests must not require database or TYPO3 bootstrap
- Functional tests must clean up after themselves
- Coverage target: maintain or improve current coverage
- No `@group slow` without justification
- Context tests should disable session (`use_session=0`) to avoid pollution
- Mock `$_SERVER['REMOTE_ADDR']` for IP context tests
