# TYPO3 v12/v13 Upgrade Plan for netresearch/contexts

**Version**: Draft 1.0
**Target Version**: 4.0.0
**Created**: Session Planning Phase

## Executive Summary

Comprehensive upgrade of the contexts extension from TYPO3 v11 to v12/v13 LTS, including:
- PHP 8.2+ requirement (dropping 7.4-8.1)
- TYPO3 12.4 and 13.4 LTS support
- Modern testing framework (PHPUnit 10/11)
- Enterprise-grade tooling
- Complete documentation

## Current State Assessment

### Version Matrix
| Component | Current | Target |
|-----------|---------|--------|
| PHP | 7.4 platform | ^8.2 |
| TYPO3 | ^11.5 | ^12.4 \|\| ^13.4 |
| PHPUnit | ^9.5 | ^10.5 \|\| ^11.0 |
| Extension | 3.1.1 | 4.0.0 |

### Issues Found
1. **Deprecated Patterns** (ext_localconf.php):
   - Container::registerImplementation() - remove (v12+ uses Services.yaml only)
   - Multiple SC_OPTIONS hooks - research PSR-14 alternatives
   - XClass registration outdated

2. **Test Framework**:
   - PHPUnit 9.x patterns ($this->at() removed in v10)
   - Missing declare(strict_types=1)
   - No TYPO3 TestingFramework usage
   - ~15% estimated coverage

3. **Missing v12/v13 Features**:
   - No Site Sets (Configuration/Sets/)
   - No Build/ directory structure
   - No DDEV configuration
   - No runTests.sh

## Phase Breakdown

### Phase 2: DDEV Environment
- .ddev/ configuration
- Multi-version support (v12/v13)
- PHP 8.2+ runtime
- Custom DDEV commands

### Phase 3: Version Updates
- composer.json constraints
- ext_emconf.php constraints
- typo3/testing-framework addition
- PHPUnit upgrade

### Phase 4: Code Migration
- Rector automated migrations
- Deprecated API replacements
- Services.yaml enhancements
- PHP 8.2+ syntax

### Phase 5: Testing Framework
- PHPUnit 10/11 configuration
- Test code migration
- Coverage expansion (>70%)
- CI test integration

### Phase 6: Enterprise Tooling
- PHPStan level 10
- php-cs-fixer
- runTests.sh
- CI/CD matrix testing

### Phase 7: Documentation
- RST structure
- Migration guide
- API documentation

### Phase 8: Validation
- Conformance check
- Matrix testing
- Release preparation

## Risk Assessment

### High Risk
- SC_OPTIONS hooks without PSR-14 equivalents
- XClass compatibility changes
- PHPUnit mock API migration

### Validation Gates
1. composer install + PHPStan passes
2. Extension loads in v12
3. All tests pass
4. CI pipeline green
5. Release ready

## Decision Points
1. Support v12 AND v13, or v13 only?
2. Hook handling strategy
3. Site Sets adoption (required vs optional)
4. Test coverage target
