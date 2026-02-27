# Compensating Security Controls

This document describes the compensating controls for the solo-maintainer auto-approval workflow,
as required by OpenSSF Scorecard's Code-Review check.

## Solo Maintainer Model

This project uses automated quality gates as compensating controls for human code review:

1. **PHPStan Level 9** — Strict static analysis catches type errors and logic bugs
2. **PHP-CS-Fixer** — Enforces consistent code style (PSR-12)
3. **PHPUnit** — Unit and functional tests with coverage reporting
4. **Architecture Tests** — PHPat enforces layer boundaries
5. **Mutation Testing** — Infection verifies test quality
6. **CodeQL** — Automated security vulnerability scanning
7. **Gitleaks** — Secret scanning in code and history
8. **Dependency Review** — License and vulnerability checking
9. **GrumPHP** — Pre-commit hooks enforce local quality gates

## Auto-Approval Workflow

The `pr-quality.yml` workflow auto-approves PRs after all quality gates pass.
This provides the GitHub Actions bot approval that satisfies Scorecard's review requirement
while maintaining security through comprehensive automated checks.
