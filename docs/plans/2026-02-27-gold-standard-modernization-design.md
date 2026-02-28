# Gold Standard Modernization — t3x-contexts

**Date**: 2026-02-27
**Goal**: Align netresearch/t3x-contexts with the t3x-nr-llm gold standard for project setup, workflows, enterprise readiness, documentation, and badges.
**Scope**: Full gold standard alignment — infrastructure, workflows, security, docs. PHP code only where beneficial (no breaking changes).

## Current State

- v4.0.0, TYPO3 v12.4/v13.4, PHP 8.2–8.5
- PHPStan 9, Rector, PHP-CS-Fixer, GrumPHP, phpat, infection, fuzz testing
- Reusable CI via `netresearch/typo3-ci-workflows`
- Codecov coverage, basic security workflows (CodeQL, dependency-review, scorecard)
- GitHub repo: `netresearch/t3x-contexts`, composer package: `netresearch/contexts`

## Gaps vs. Gold Standard (t3x-nr-llm)

### 1. GitHub Project Setup
- README badges use wrong repo slug (`netresearch/contexts` → `netresearch/t3x-contexts`)
- Missing workflows: pr-quality, security (gitleaks+audit), docs, greetings, labeler, lock, stale, license-check
- Missing: `.github/labeler.yml` config

### 2. Enterprise Readiness
- No SLSA Level 3 provenance
- No cosign keyless signing for releases
- No SBOM generation (SPDX + CycloneDX)
- No build provenance attestation
- Release workflow lacks signed artifact pipeline
- OpenSSF Best Practices: placeholder ID (XXXXX) — needs registration

### 3. TYPO3 Conformance
- Site Sets present, verify completeness
- Check for remaining deprecated API usage
- Verify TCA patterns match latest standards

### 4. PHP Modernization
- Rector UP_TO_PHP_82 configured
- Opportunities: readonly properties, constructor promotion (non-breaking)
- PHPStan stays at 9 (10 too aggressive for v12 compat)

### 5. DDEV Setup
- Exists but may need modernization
- Verify multi-version testing (v12 + v13) works

### 6. Documentation
- No docs build workflow (`docs.yml`)
- README/RST docs sync verification needed
- Missing badges: Documentation build, Contributor Covenant, SLSA 3, PHP version, TYPO3 version

### 7. Badge Alignment
Missing vs. gold standard:
- Documentation build status badge
- PHP version badge
- TYPO3 version badge
- Contributor Covenant badge
- SLSA 3 badge
- Infection MSI badge (optional)

## Approach

Incremental, independently committable phases:

1. **Fix fundamentals** — README badge URLs, register OpenSSF Best Practices
2. **Add missing workflows** — PR quality gates, security, docs, community
3. **Upgrade release pipeline** — SBOM, cosign, SLSA, attestation
4. **Modernize README & badges** — Match gold standard layout
5. **TYPO3 conformance** — TCA, deprecations, Site Sets
6. **DDEV modernization** — Multi-version testing
7. **PHP code review** — Minor modernization (non-breaking)
8. **Documentation sync** — RST ↔ README alignment

## Skills Used

- `github-project` — Workflow setup, PR quality gates
- `enterprise-readiness` — SLSA, SBOM, cosign, OpenSSF
- `typo3-conformance` — Extension standards check
- `php-modernization` — Code modernization
- `typo3-ddev` — DDEV setup
- `typo3-docs` — Documentation
- `typo3-testing` — Test infrastructure
