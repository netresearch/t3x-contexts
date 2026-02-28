# 4.0.0 (2026-03-01)

## Breaking Changes

- Dropped TYPO3 v11 support
- Requires PHP 8.2 or higher
- Replaced GrumPHP with CaptainHook for git hooks
- Moved tooling config files to `Build/` directory

## Features

- Full TYPO3 v12.4 LTS and v13.4 LTS support
- PHP 8.4 and 8.5 compatibility
- PHPStan level 10 compliance
- PSR-7 header support and substring matching in HttpHeaderContext
- Page-level context access control (current page always checked)
- Docker-based `runTests.sh` following TYPO3 core testing pattern
- DDEV development environment with multi-version testing
- Comprehensive demo contexts and content for DDEV
- Mutation testing with Infection (MSI > 80%)
- Architecture tests with phpat
- Playwright E2E test infrastructure
- Cross-extension functional tests
- CodeQL security scanning
- SBOM generation for supply chain security
- Codecov coverage reporting

## Bug Fixes

- Fixed page access check: current page context restrictions are now always enforced regardless of `extendToSubpages`
- Fixed HttpHeaderContext to support PSR-7 standard header names (e.g., `User-Agent`) alongside `$_SERVER` keys
- Fixed HttpHeaderContext value matching to use case-insensitive substring matching instead of exact match
- Fixed XSS vulnerability: escape user input and labels in form elements
- Replaced direct `$_SERVER` superglobal access with PSR-7 request

## Technical Changes

- Updated PSR-14 event listeners to TYPO3 v12/v13 APIs:
  - `FilterMenuItemsEvent`: Use `getFilteredMenuItems()`/`setFilteredMenuItems()`
  - `AfterPageAndLanguageIsResolvedEvent`: Use `getPageInformation()->getRootLine()`
  - `ModifyRecordOverlayIconIdentifierEvent`: Correct event for database records
- Migrated Doctrine DBAL to v4 API (`fetchAssociative()`, `executeQuery()`)
- Replaced deprecated `ExpressionBuilder::orX()/andX()` with `or()/and()`
- Removed deprecated ExtensionManagementUtility calls
- Centralized CI workflows with enterprise security controls
- 719 unit tests, comprehensive functional test coverage

---

# 3.1.1

- Add github action for publishing to TER
- Add extension icon

# 3.0.0

## FEATURE

- [FEATURE] Rework code for TYPO v11 compatibility


## Contributors

- Achim Fritz
- Andre Hähnel
- Axel Kummer
- Benni Mack
- Christian Opitz
- Christian Weiske
- David Steeb
- Marian Pollzien
- Mathias Brodala
- Michael Schams
- Rico Sonntag
- Sebastian Mendel
- Thomas Schöne
- Tobias Hein
- copitz

