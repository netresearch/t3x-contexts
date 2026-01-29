# Copilot Instructions for t3x-contexts

This repository contains a TYPO3 extension for multi-channel contexts that show/hide content based on configurable rules (screen size, location, browser, GET parameters, etc.).

## Code Standards and Style

### PHP Requirements
- **PHP Version**: 8.2+ minimum, with strict typing
- **Always include**: `declare(strict_types=1);` at the top of every PHP file after the opening tag
- **Namespace**: All classes use `Netresearch\Contexts\` namespace following PSR-4 autoloading
- **Coding Standard**: PSR-12 / PER-CS (enforced via PHP-CS-Fixer and PHPCodeSniffer)

### File Structure
- **Classes/**: Main source code following TYPO3 extension structure
  - `Context/`: Context implementations and factory
  - `Context/Type/`: Concrete context type classes extending AbstractContext
  - `Api/`: Public API classes
  - `Service/`: Service layer classes
  - `ViewHelpers/`: Fluid template helpers
  - `Form/`: Backend form elements
  - `Middleware/`: PSR-15 middleware
  - `EventListener/`: PSR-14 event listeners
  - `ExpressionLanguage/`: TypoScript condition providers
  - `Query/Restriction/`: Doctrine DBAL query restrictions
- **Tests/**: PHPUnit tests (Unit/, Functional/, Fuzz/)
- **Configuration/**: TYPO3 configuration files
- **Resources/**: Static resources (CSS, JS, templates)
- **Documentation/**: RST documentation for docs.typo3.org

### Naming Conventions
- **Classes**: PascalCase (e.g., `AbstractContext`, `LogicalExpressionEvaluator`)
- **Methods/Variables**: camelCase (e.g., `createFromDb`, `arRow`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `HTTP_X_FORWARDED_FOR`)
- **Database fields**: snake_case following TYPO3 conventions

## TYPO3-Specific Patterns

### Extension Structure
- This is a TYPO3 v12.4/v13.4 extension (`typo3/cms-core: ^12.4 || ^13.4`)
- Follow TYPO3 extension conventions for file naming and structure
- Use TYPO3's dependency injection and service container
- Implement TYPO3 interfaces where appropriate (e.g., `SingletonInterface`)
- Use PHP 8 attributes for event listeners (`#[AsEventListener]`)

### Database and ORM
- Use TYPO3's Doctrine DBAL 4.x for database operations
- Context records are stored in `tx_contexts_contexts` table
- Use prepared statements and parameterized queries for security
- Handle database exceptions (`DBALException`, `Driver\Exception`)
- **IMPORTANT**: Use `Connection::PARAM_INT`/`Connection::PARAM_STR` constants, NOT `PDO::PARAM_*`

### Database Parameter Types

```php
// Good: TYPO3 Connection constants (DBAL 4.x compatible)
use TYPO3\CMS\Core\Database\Connection;
$qb->createNamedParameter($uid, Connection::PARAM_INT);
$qb->createNamedParameter($title, Connection::PARAM_STR);

// Bad: PDO constants (breaks DBAL 4.x)
$qb->createNamedParameter($uid, \PDO::PARAM_INT); // DON'T USE
```

### Frontend Integration
- Contexts integrate with TYPO3 frontend rendering
- Use `TypoScriptFrontendController` for frontend operations (null-safe access)
- Provide Fluid ViewHelpers for template integration
- Support TypoScript conditions via ExpressionLanguage

## Quality Assurance

### Static Analysis
- **PHPStan**: Level 9 analysis with strict rules (see `Build/phpstan.neon`)
- Run: `composer analyze`
- Address all PHPStan errors before committing

### Code Style
- **PHP-CS-Fixer**: PER-CS + PHP 8.2 migration rules
- **PHP_CodeSniffer**: PSR-12 standard enforcement
- Run: `composer lint` (check) or `composer lint:fix` (auto-fix)

### Testing
- **PHPUnit**: 10.5+/11/12 supported
- **Unit Tests**: `composer test:unit`
- **Functional Tests**: `composer test:functional` (requires database)
- **Coverage**: `composer test:coverage`
- **Mutation Testing**: `composer test:mutation` (Infection)
- **Fuzz Testing**: See `Tests/Fuzz/`

### Refactoring
- **Rector**: Automated refactoring for PHP/TYPO3 upgrades (see `Build/rector.php`)
- **Fractor**: TYPO3-specific migrations

### Pre-commit Quality Gates
- **GrumPHP**: Automated quality checks on commit (see `grumphp.yml`)
- Runs composer validation, YAML/JSON/XML linting, PHPStan, and PHPCS
- All checks must pass before code can be committed

## Testing

### Test Structure
- **Unit Tests**: `Tests/Unit/` for isolated component testing
- **Functional Tests**: `Tests/Functional/` for integration testing
- **Fuzz Tests**: `Tests/Fuzz/` for property-based testing
- Use PHPUnit 10.5+ (`vendor/bin/phpunit`)
- Follow TYPO3 testing patterns and extend TYPO3 test base classes

### Test Requirements
- Write unit tests for all new public methods and classes
- Include functional tests for database operations and TYPO3 integration
- Test both positive and negative scenarios
- Mock external dependencies appropriately

## Documentation and Comments

### Code Documentation
- Use PHPDoc blocks for all public methods, classes, and properties
- Include `@param`, `@return`, and `@throws` annotations
- Document complex business logic with inline comments
- Follow the existing documentation style in the codebase

### File Headers
- Include the standard file header with copyright notice:
```php
<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);
```

## Error Handling and Logging

### Exception Handling
- Use typed exceptions (`ContextException` for domain-specific errors)
- Catch and handle TYPO3/Doctrine exceptions appropriately
- Always use try-catch blocks for database operations and external calls

### Logging
- Implement `LoggerAwareInterface` for classes that need logging
- Use PSR-3 logging levels appropriately (debug, info, warning, error)
- Log context evaluation errors and configuration issues

## Security Considerations

### Input Validation
- Validate all user inputs, especially GET/POST parameters and headers
- Use parameterized database queries to prevent SQL injection
- Sanitize data before storing in session or database
- Use PSR-7 request objects instead of direct `$_GET`/`$_POST` access

### Context Evaluation
- Be careful with logical expression evaluation to prevent code injection
- Validate context configuration before execution
- Handle malformed context rules gracefully

## Development Workflow

### Build Process
1. `composer install` - Install dependencies
2. `composer lint` - Check code style
3. `composer analyze` - Run static analysis
4. `composer test:unit` - Run unit tests
5. `composer test:functional` - Run functional tests (requires database)

### DDEV Environment
```bash
ddev start
ddev install-v13  # or ddev install-v12
```

### Adding New Context Types
1. Extend `AbstractContext` class in `Classes/Context/Type/`
2. Implement `match(array $arDependencies = []): bool` method
3. Use `getConfValue()` to read FlexForm configuration
4. Use `invert()` to support result inversion
5. Use `storeInSession()`/`getMatchFromSession()` for session caching
6. Add FlexForm configuration in `Configuration/FlexForms/`
7. Write comprehensive tests

## Performance Considerations

- Context evaluation happens on every page request
- Keep context logic lightweight and efficient
- Use appropriate caching strategies for expensive operations
- Consider database query optimization for context lookups
- Avoid complex operations in hot code paths

## Compatibility

- Maintain TYPO3 v12.4+ and v13.4+ compatibility
- Support PHP 8.2 - 8.5
- Test with different TYPO3 configurations
- Consider backwards compatibility when making changes

When making changes, always run the full quality suite and ensure all tests pass before submitting code.

## Tool Usage and Efficiency

### Parallel Tool Calling
- When performing multiple independent operations, ALWAYS call tools simultaneously rather than sequentially
- Examples: reading multiple files, running git status + git diff, viewing different directories
- Only call tools sequentially when later calls depend on results from earlier calls

### Repository Exploration
- Use parallel tool calls when exploring codebase: view multiple directories and files simultaneously
- Batch file operations when possible to improve efficiency
- Use command chains for dependent bash operations: `command1 && command2`

## Repository Specific Guidance

### Key Extension Points
- **New Context Types**: Extend `AbstractContext` and implement `match()` method
- **ViewHelpers**: Create Fluid helpers in `Classes/ViewHelpers/` for template integration
- **Middleware**: Add HTTP middleware in `Classes/Middleware/` for request processing
- **Services**: Implement business logic in `Classes/Service/` with proper dependency injection
- **Event Listeners**: Use `#[AsEventListener]` attribute for PSR-14 events

### TYPO3 Integration Patterns
- Use TYPO3's dependency injection container for service registration
- Implement `SingletonInterface` for stateless services
- Use `LoggerAwareInterface` and inject PSR-3 loggers
- Follow TYPO3 v12/v13 patterns and deprecation guidelines
- Register services in `Configuration/Services.yaml` when needed

### PSR-14 Event Listeners

```php
// Use PHP 8 attributes - no Services.yaml entry needed
#[AsEventListener(
    identifier: 'contexts/my-event',
    event: SomeEvent::class,
)]
final readonly class MyEventListener
{
    public function __construct(
        private SomeService $service,
    ) {}

    public function __invoke(SomeEvent $event): void
    {
        // Handle event
    }
}
```
