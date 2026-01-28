# Contributing to TYPO3 Contexts Extension

Thank you for your interest in contributing to the TYPO3 Contexts extension!

## Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`

## Code Quality

Before submitting changes, ensure:

- **PHPStan passes**: `composer analyze`
- **Code style is correct**: `composer lint`
- **Tests pass**: `composer test`

To auto-fix code style issues: `composer lint:fix`

## Testing

- **Unit tests**: `composer test:unit`
- **Functional tests**: `composer test:functional` (requires database)
- **Coverage report**: `composer test:coverage`
- **Mutation testing**: `composer test:mutation`

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`feature/my-feature`)
3. Make your changes with tests
4. Ensure all quality checks pass
5. Submit a pull request

## Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` new feature
- `fix:` bug fix
- `docs:` documentation only
- `chore:` maintenance tasks
- `refactor:` code refactoring
- `test:` adding tests

## Reporting Issues

Please use [GitHub Issues](https://github.com/netresearch/t3x-contexts/issues) to report bugs or request features.

## License

By contributing, you agree that your contributions will be licensed under the AGPL-3.0-or-later license.
