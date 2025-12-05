# TYPO3 v12/v13 Upgrade Plan for netresearch/contexts

## Executive Summary

**Current State**: Extension already targets TYPO3 v12.4/v13.4 with modern architecture
**Conformance Score**: 72/100 (Good foundation, gaps in quality tooling and test coverage)
**Target Score**: 95+/100

This plan outlines a comprehensive upgrade to achieve enterprise-grade quality while fully supporting TYPO3 v12.4 LTS and v13.4 LTS (dropping v11 support).

---

## Current State Assessment

### Strengths (Already Modern)

| Area | Status | Notes |
|------|--------|-------|
| Version constraints | ✅ | `^12.4 \|\| ^13.4` in composer.json |
| PHP requirement | ✅ | `^8.2` |
| Site Sets | ✅ | `Configuration/Sets/` present |
| Icon registration | ✅ | `Configuration/Icons.php` |
| Dependency Injection | ✅ | `Services.yaml` |
| Event system | ✅ | 5 PSR-14 EventListeners |
| Deprecated files | ✅ | No `ext_typoscript_*.typoscript` |
| CI/CD | ✅ | Multi-version matrix (PHP 8.2-8.4, TYPO3 12.4/13.4) |
| DDEV | ✅ | Configuration exists |
| Documentation | ✅ | RST format in `Documentation/` |

### Gaps Identified

| Area | Current | Target | Priority |
|------|---------|--------|----------|
| PHPStan level | 5 | 10 | HIGH |
| Test coverage | ~23% (8/35 classes) | 80%+ | HIGH |
| PHPStan exclusions | 8 files excluded | 0 exclusions | MEDIUM |
| Deprecated APIs | Several in use | All migrated | HIGH |
| Enterprise tooling | Basic | SLSA L2, SBOMs | MEDIUM |

### Deprecated APIs Found

1. **Doctrine DBAL 4.x**: `execute()` → `executeQuery()`/`executeStatement()`
2. **ExpressionBuilder**: `orX()`/`andX()` → `or()`/`and()`
3. **GeneralUtility**: `::_GET()` deprecation warnings
4. **EventListener imports**: Some PSR-14 event classes may be incorrect

---

## Workstream 1: DDEV Environment Setup

**Skill**: `typo3-ddev`

### Tasks

1. **Validate Existing Configuration**
   - Review `.ddev/config.yaml`
   - Ensure PHP 8.2+ and MariaDB/MySQL 8.0+
   - Validate Apache vhost configuration

2. **Create Multi-Version Test Environment**
   - TYPO3 12.4 LTS instance
   - TYPO3 13.4 LTS instance
   - Shared database for testing

3. **Custom DDEV Commands**
   - `ddev test-unit` - Run unit tests
   - `ddev test-functional` - Run functional tests
   - `ddev phpstan` - Run PHPStan analysis
   - `ddev cs-fix` - Run PHP-CS-Fixer

### Deliverables
- [ ] Validated DDEV configuration
- [ ] Custom commands in `.ddev/commands/`
- [ ] Working multi-version environment

---

## Workstream 2: Code Modernization

**Skill**: `typo3-conformance`

### Tasks

#### 2.1 Doctrine DBAL 4.x Migration

**Files to check**: All files using `QueryBuilder`

```php
// BEFORE (deprecated)
$result = $queryBuilder->execute();

// AFTER (v12/v13 compatible)
$result = $queryBuilder->executeQuery();  // For SELECT
$affectedRows = $queryBuilder->executeStatement();  // For INSERT/UPDATE/DELETE
```

#### 2.2 ExpressionBuilder Migration

```php
// BEFORE (deprecated)
$expr->orX($condition1, $condition2);
$expr->andX($condition1, $condition2);

// AFTER (v12/v13 compatible)
$expr->or($condition1, $condition2);
$expr->and($condition1, $condition2);
```

#### 2.3 GeneralUtility::_GET Migration

```php
// BEFORE (deprecated in v12+)
$value = GeneralUtility::_GET('param');

// AFTER (v12/v13 compatible)
$request = $GLOBALS['TYPO3_REQUEST'] ?? null;
$value = $request?->getQueryParams()['param'] ?? null;
```

#### 2.4 PSR-14 EventListener Fixes

Review and fix event class imports in:
- `Classes/EventListener/DataHandlerEventListener.php`
- `Classes/EventListener/MenuItemFilterEventListener.php`
- `Classes/EventListener/PageAccessEventListener.php`
- `Classes/EventListener/IconOverlayEventListener.php`
- `Classes/EventListener/CacheHashEventListener.php`

### Deliverables
- [ ] All deprecated APIs migrated
- [ ] Zero deprecation warnings in v12/v13
- [ ] All EventListeners with correct imports

---

## Workstream 3: Static Analysis Enhancement

**Skill**: `enterprise`

### Tasks

#### 3.1 PHPStan Level Progression

| Step | Level | Expected Issues |
|------|-------|-----------------|
| 1 | 6 | Type inference issues |
| 2 | 7 | Union types, nullability |
| 3 | 8 | Method signatures |
| 4 | 9 | PHPDoc type mismatches |
| 5 | 10 | Maximum strictness |

#### 3.2 Remove PHPStan Exclusions

Currently excluded (must be fixed):
- `Tests/Unit/Classes/Context/AbstractTest.php`
- `Tests/Unit/Classes/Context/Type/QueryParameterContextTest.php`
- `Tests/Functional/PageTest.php`
- `Classes/EventListener/DataHandlerEventListener.php`
- `Classes/EventListener/MenuItemFilterEventListener.php`
- `Classes/EventListener/PageAccessEventListener.php`
- `Classes/EventListener/IconOverlayEventListener.php`

#### 3.3 Enterprise Tooling

1. **SLSA Level 2 Compliance**
   - Signed releases
   - Provenance attestations
   - Reproducible builds

2. **SBOM Generation**
   - CycloneDX format
   - Integrated into CI/CD

3. **Security Scanning**
   - Dependency vulnerability scanning
   - CodeQL analysis (already exists)

### Deliverables
- [ ] PHPStan level 10 passing
- [ ] Zero file exclusions
- [ ] SLSA Level 2 compliance
- [ ] SBOM generation in CI

---

## Workstream 4: Testing Enhancement

**Skill**: `typo3-testing`

### Current Coverage Analysis

| Directory | Classes | Tests | Coverage |
|-----------|---------|-------|----------|
| Api | 1+ | 0 | 0% |
| Context | 5+ | 5 | ~60% |
| EventListener | 5 | 0 | 0% |
| ExpressionLanguage | 1+ | 0 | 0% |
| Form | 3 | 0 | 0% |
| Middleware | 1+ | 0 | 0% |
| Query | 2+ | 0 | 0% |
| Service | 2+ | 0 | 0% |
| ViewHelpers | 1+ | 0 | 0% |
| Xclass | 1+ | 0 | 0% |

### Tasks

#### 4.1 Unit Tests to Add

Priority order:
1. `Context/Type/*` - Complete existing coverage
2. `Query/Restriction/ContextRestriction.php` - Core functionality
3. `Service/*` - Service layer
4. `EventListener/*` - Event handling
5. `Form/*` - Form elements
6. `ViewHelpers/*` - Template integration
7. `Middleware/*` - Request handling
8. `ExpressionLanguage/*` - Condition evaluation
9. `Api/*` - API endpoints
10. `Xclass/*` - TYPO3 overrides

#### 4.2 Functional Tests

- Context matching scenarios
- TCA field rendering
- Menu filtering
- Page access control
- Icon overlays

#### 4.3 PHPUnit Compatibility

Ensure all tests use:
- `#[Test]` attribute (not `@test`)
- `#[DataProvider('methodName')]` (not `@dataProvider`)
- `$this->expectException()` (not `@expectedException`)
- PHPUnit 11/12 compatible assertions

### Deliverables
- [ ] 80%+ code coverage
- [ ] Tests for all major components
- [ ] PHPUnit 11/12 full compatibility
- [ ] Coverage reporting in CI

---

## Workstream 5: Documentation

**Skill**: `typo3-docs`

### Tasks

#### 5.1 Update Existing Documentation

- `Documentation/Index.rst` - Version update to 4.x
- `Documentation/Installation/Index.rst` - v12/v13 requirements
- `Documentation/Migration/Index.rst` - v11 → v12/v13 migration guide
- `Documentation/Configuration/Index.rst` - Site Set configuration

#### 5.2 New Documentation

- API documentation for public interfaces
- EventListener documentation
- Developer guide for extending contexts

#### 5.3 TYPO3 Standards Compliance

- Validate with `docker compose run docs`
- Fix any rendering issues
- Add version compatibility badges

### Deliverables
- [ ] Updated version information
- [ ] Migration guide from v11
- [ ] Renders without errors
- [ ] API documentation

---

## Implementation Order

```
Workstream 1 (DDEV)
       │
       ▼
Workstream 2 (Code Modernization)
       │
       ├────────────┬────────────┐
       ▼            ▼            ▼
Workstream 3   Workstream 4   Workstream 5
(PHPStan)      (Testing)      (Docs)
       │            │            │
       └────────────┴────────────┘
                    │
                    ▼
            Final Validation
```

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Breaking changes in API migration | Medium | High | Test each change in both TYPO3 versions |
| PHPStan level 10 reveals many issues | High | Medium | Incremental level increase |
| Test coverage takes significant effort | High | Low | Prioritize critical paths first |
| Documentation rendering issues | Low | Low | Use local Docker rendering |

---

## Validation Criteria

### Per Workstream

1. **DDEV**: All commands work, both TYPO3 versions accessible
2. **Code**: Zero deprecation warnings, PHPStan passes without exclusions
3. **Analysis**: PHPStan level 10, all files included
4. **Testing**: 80%+ coverage, all tests pass
5. **Docs**: Renders without errors, content accurate

### Final Validation

- [ ] `composer install` succeeds
- [ ] `vendor/bin/phpstan analyse` passes at level 10
- [ ] `vendor/bin/php-cs-fixer fix --dry-run` finds no issues
- [ ] `vendor/bin/phpunit` - all tests pass
- [ ] CI/CD pipeline green on all matrix combinations
- [ ] Documentation renders correctly
- [ ] No deprecated API usage warnings

---

## Questions for Consensus

Before proceeding, please confirm:

1. **Scope**: Should we maintain backward compatibility with v11, or complete removal?
   - **Recommended**: Complete removal (cleaner codebase)

2. **Test Coverage Target**: 80% as target, or higher?
   - **Recommended**: 80% minimum, aim for 90%

3. **PHPStan Level**: Target level 10, or stop at level 8?
   - **Recommended**: Level 10 for maximum type safety

4. **SLSA Compliance**: Full SLSA Level 2, or basic security only?
   - **Recommended**: SLSA Level 2 for enterprise users

5. **Documentation**: Minimal updates or comprehensive rewrite?
   - **Recommended**: Comprehensive update with migration guide

---

## Next Steps

1. Review this plan
2. Provide feedback on questions above
3. Confirm consensus
4. Begin implementation in workstream order

*Plan created: 2024-12-03*
*Skills to be used: typo3-conformance, typo3-ddev, typo3-testing, typo3-docs, enterprise*
