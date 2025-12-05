# 4.0.0

## Breaking Changes

- Dropped TYPO3 v11 support
- Requires PHP 8.2 or higher

## Features

- Full TYPO3 v12.4 LTS support
- Full TYPO3 v13.4 LTS support
- PHP 8.4 compatibility
- PHPStan level 8 compliance
- SBOM generation for supply chain security
- Comprehensive test coverage

## Technical Changes

- Updated PSR-14 event listeners to TYPO3 v12/v13 APIs:
  - `FilterMenuItemsEvent`: Use `getFilteredMenuItems()`/`setFilteredMenuItems()`
  - `AfterPageAndLanguageIsResolvedEvent`: Use `getPageInformation()->getRootLine()`
  - `ModifyRecordOverlayIconIdentifierEvent`: Correct event for database records
- Migrated Doctrine DBAL to v4 API (`fetchAssociative()`, `executeQuery()`)
- Replaced deprecated `ExpressionBuilder::orX()/andX()` with `or()/and()`
- Removed deprecated ExtensionManagementUtility calls

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

