<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Contributing to TYPO3 Contexts Extension

Thank you for your interest in contributing to the TYPO3 Contexts extension!

## Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer ci:test:php:unit` (or `make test` inside DDEV)

## Code Quality

Before submitting changes, ensure:

- **PHPStan passes**: `composer ci:test:php:phpstan`
- **Code style is correct**: `composer ci:test:php:cgl`
- **Tests pass**: `composer ci:test:php:unit`

To auto-fix code style issues: `composer ci:cgl`

## Testing

- **Unit tests**: `composer ci:test:php:unit`
- **Functional tests**: `composer ci:test:php:functional` (requires database)
- **Coverage report**: `composer test:coverage`
- **Mutation testing**: `composer test:mutation`

PHPUnit and the other development tools are installed by Composer from `require-dev` in `composer.json` (partly through `netresearch/typo3-ci-workflows`). The repository ships no tool binaries, and `composer.lock` is not committed.

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

## Governance and policies

This extension follows the organisation-wide Netresearch policies:

- [Governance](https://github.com/netresearch/.github/blob/main/GOVERNANCE.md): ownership, roles, how decisions are made and conflicts resolved.
- [Roadmap](https://github.com/netresearch/.github/blob/main/ROADMAP.md): planned and excluded work for the next twelve months.
- [Handling of dependency and code analysis findings](https://github.com/netresearch/.github/blob/main/SECURITY.md#handling-of-dependency-and-code-analysis-findings): which vulnerability, licence and static-analysis findings must be fixed, by when, and how exceptions are recorded.
- [Secret management](https://github.com/netresearch/.github/blob/main/SECURITY.md#secret-management): where project, CI and release credentials are stored, who may use them, and how they are rotated or revoked.
- [Access roster](https://github.com/netresearch/.github/blob/main/docs/access-roster.md): the people and teams with administrative or write access to this repository.

What the extension guarantees in terms of security, and what it does not, is described in [docs/SECURITY-ASSURANCE.md](docs/SECURITY-ASSURANCE.md).

Checks that run on every pull request in this repository:

- `.github/workflows/checks.yml` (gate `All security checks`): Composer Audit (fails on any advisory for an installed package) and Opengrep SAST (fails on findings of severity WARNING or higher), both through `typo3-ci-workflows`' `security.yml`; Dependency Review (fails on newly added dependencies with a vulnerability of severity high or higher); PHP License Audit (fails on an SSPL or BSL licensed Composer dependency); CodeQL; Betterleaks secret scanning; zizmor for the workflow files.
- `.github/workflows/ci.yml`: PHP lint, code style (`Build/php-cs-fixer.php`), PHPStan (`Build/phpstan.neon`), Rector, unit tests and functional tests against MySQL for PHP 8.2 to 8.5 and TYPO3 13.4 and 14.3, and a documentation render.
- `.github/workflows/harness-verify.yml` and `check-template-drift.yml`: consistency of the agent documentation and of the files managed by the organisation's TYPO3 extension template.

The one recorded exception to the Composer Audit is `config.audit.ignore` in `composer.json`, with its reason next to the advisory ID. Fuzz targets in `Tests/Fuzz/` and mutation testing (`composer test:mutation`) are run locally, not in CI.

## License

By contributing, you agree that your contributions will be licensed under the AGPL-3.0-or-later license.

## Commit Signing

All commits must be cryptographically signed and carry a DCO sign-off: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch enforces the signature (the "Verified" badge on GitHub); the DCO check enforces the `Signed-off-by` trailer — these are two different things and both are required. Quickest setup is SSH signing: register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
