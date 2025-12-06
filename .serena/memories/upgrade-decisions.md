# Upgrade Decisions - Confirmed

**Date**: Session active
**Target Version**: 4.0.0

## Confirmed Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Version Support | v12 + v13 | Both LTS, standard practice |
| PHP Minimum | 8.2+ | Required for v13, works with v12 |
| Test Coverage | 80%+ | Enterprise-grade quality |
| Site Sets | Optional | Backward compatibility with TypoScript |
| Major Version | 4.0.0 | Breaking changes warrant major bump |

## Implementation Strategy

1. Use compatibility layers for v12/v13 differences
2. PSR-14 events with hook fallbacks for v12
3. Site Sets with TypoScript fallback
4. TYPO3 TestingFramework for all tests
5. Matrix CI: PHP 8.2/8.3/8.4 × TYPO3 12/13
