# Copilot Instructions for t3x-contexts

This repository contains a TYPO3 extension for multi-channel contexts that show/hide content based on configurable rules (screen size, location, browser, GET parameters, etc.).

## Code Standards and Style

### PHP Requirements
- **PHP Version**: 7.4+ minimum, with strict typing
- **Always include**: `declare(strict_types=1);` at the top of every PHP file after the opening tag
- **Namespace**: All classes use `Netresearch\Contexts\` namespace following PSR-4 autoloading
- **Coding Standard**: PSR-12 (enforced via PHPCodeSniffer)

### File Structure
- **Classes/**: Main source code following TYPO3 extension structure
  - `Context/`: Context implementations and factory
  - `Api/`: Public API classes
  - `Service/`: Service layer classes
  - `ViewHelpers/`: Fluid template helpers
  - `Form/`: Backend form elements
  - `Middleware/`: TYPO3 middleware
- **Tests/**: PHPUnit tests (Unit/ and Functional/)
- **Configuration/**: TYPO3 configuration files
- **Resources/**: Static resources (CSS, JS, templates)

### Naming Conventions
- **Classes**: PascalCase (e.g., `AbstractContext`, `LogicalExpressionEvaluator`)
- **Methods/Variables**: camelCase (e.g., `createFromDb`, `arRow`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `HTTP_X_FORWARDED_FOR`)
- **Database fields**: snake_case following TYPO3 conventions

## TYPO3-Specific Patterns

### Extension Structure
- This is a TYPO3 v11 extension (`typo3/cms-core: ^11.5`)
- Follow TYPO3 extension conventions for file naming and structure
- Use TYPO3's dependency injection and service container
- Implement TYPO3 interfaces where appropriate (e.g., `SingletonInterface`)

### Database and ORM
- Use TYPO3's Doctrine DBAL for database operations
- Context records are stored in `tx_contexts_contexts` table
- Use prepared statements and parameterized queries for security
- Handle database exceptions (`DBALException`, `Driver\Exception`)

### Frontend Integration
- Contexts integrate with TYPO3 frontend rendering
- Use `TypoScriptFrontendController` for frontend operations
- Provide Fluid ViewHelpers for template integration
- Support TypoScript conditions

## Quality Assurance

### Static Analysis
- **PHPStan**: Level 9 analysis with strict rules (see `phpstan.neon`)
- Run: `vendor/bin/phpstan analyse --configuration phpstan.neon`
- Address all PHPStan errors before committing

### Code Style
- **PHP_CodeSniffer**: PSR-12 standard enforcement
- Run: `vendor/bin/phpcs Classes/ --standard=PSR12`
- Fix automatically: `vendor/bin/phpcbf Classes/ --standard=PSR12`

### Refactoring
- **Rector**: Automated refactoring for PHP/TYPO3 upgrades (see `rector.php`)
- Run dry-run: `vendor/bin/rector --dry-run`
- Targets PHP 7.4 and TYPO3 v11 patterns

### Pre-commit Quality Gates
- **GrumPHP**: Automated quality checks on commit (see `grumphp.yml`)
- Runs composer validation, YAML/JSON/XML linting, PHPStan, and PHPCS
- All checks must pass before code can be committed

## Testing

### Test Structure
- **Unit Tests**: `Tests/Unit/` for isolated component testing
- **Functional Tests**: `Tests/Functional/` for integration testing
- Use PHPUnit 9.x (`vendor/bin/phpunit`)
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

### Context Evaluation
- Be careful with logical expression evaluation to prevent code injection
- Validate context configuration before execution
- Handle malformed context rules gracefully

## Development Workflow

### Build Process
1. `composer install` - Install dependencies
2. `vendor/bin/grumphp run` - Run all quality checks
3. `vendor/bin/phpunit` - Run tests
4. Manual testing in TYPO3 backend/frontend

### Adding New Context Types
1. Extend `AbstractContext` class
2. Implement required abstract methods
3. Register in `Configuration::getContextTypes()`
4. Add corresponding database fields if needed
5. Create backend form configuration
6. Write comprehensive tests

## Performance Considerations

- Context evaluation happens on every page request
- Keep context logic lightweight and efficient
- Use appropriate caching strategies for expensive operations
- Consider database query optimization for context lookups
- Avoid complex operations in hot code paths

## Compatibility

- Maintain TYPO3 v11.5+ compatibility
- Support PHP 7.4+ (current platform constraint)
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

## Agent Workflow Integration

### Issue Assignment and Handling
- When assigned an issue, start by exploring the repository structure and understanding the current state
- Create an implementation plan using **report_progress** before making changes
- Make small, incremental changes with frequent progress reports
- Use the existing quality tools (PHPStan, PHPCS, PHPUnit) to validate changes

### Code Review Process
- Run **code_review** tool before finalizing changes to get automated feedback
- Address valid review comments and re-run if significant changes are made
- Use **codeql_checker** for security analysis after code reviews
- Include a Security Summary for any vulnerabilities discovered

### Git and Branch Management
- Work on feature branches (never directly on main/master)
- Use meaningful commit messages that follow conventional commit patterns
- Keep commits focused and atomic
- Use **report_progress** for committing and pushing changes

## Environment and Limitations

### Sandboxed Environment
- Repository is cloned locally in a sandboxed environment
- Can read, edit, and create files within the repository
- Can run local commands and tools via bash
- Cannot directly push to GitHub (use **report_progress** instead)

### Quality Gates
- All quality checks must pass before finalizing work
- PHPStan level 9 analysis is enforced
- PSR-12 coding standards are mandatory
- GrumPHP hooks ensure code quality on commits
- Address any failing tests or quality issues

### Testing Requirements
- Unit tests for new public methods and classes
- Functional tests for TYPO3 integration and database operations
- Test both success and failure scenarios
- Follow existing test patterns and extend appropriate base classes

### Common Patterns and Examples

#### File Header Template
```php
<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\[Subnamespace];

// imports...
```

#### Context Implementation Pattern
```php
// Context classes extend AbstractContext
class MyContext extends AbstractContext
{
    // Implement required abstract methods
    // Use proper type hints and return types
    // Include comprehensive PHPDoc
}
```

#### Database Query Pattern
```php
try {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_contexts_contexts');
    
    $result = $queryBuilder
        ->select('*')
        ->from('tx_contexts_contexts')
        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contextUid, PDO::PARAM_INT)))
        ->executeQuery();
} catch (DBALException | Exception $e) {
    // Handle database exceptions appropriately
    $this->logger->error('Database error: ' . $e->getMessage());
}
```

## Repository Specific Guidance

### Key Extension Points
- **New Context Types**: Extend `AbstractContext` and register in `Configuration::getContextTypes()`
- **ViewHelpers**: Create Fluid helpers in `Classes/ViewHelpers/` for template integration
- **Middleware**: Add HTTP middleware in `Classes/Middleware/` for request processing
- **Services**: Implement business logic in `Classes/Service/` with proper dependency injection

### TYPO3 Integration Patterns
- Use TYPO3's dependency injection container for service registration
- Implement `SingletonInterface` for stateless services
- Use `LoggerAwareInterface` and inject PSR-3 loggers
- Follow TYPO3 v11 patterns and deprecation guidelines
- Register services in `Configuration/Services.yaml` when needed