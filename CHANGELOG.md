# 5.0.0

## Breaking Changes

- Dropped TYPO3 v12.4 support - requires TYPO3 v13.4 LTS or v14.3 LTS
- Removed `ext_tables.php` (deprecated in TYPO3 v14.3, #109438); the v13+ equivalent is `ctrl.security.ignorePageTypeRestriction` in the TCA
- Removed `FrontendControllerService::registerQueryParameter()`, `::createHashBase()` and `::configArrayPostProc()` - replaced by `QueryParameterService` plus PSR-14 listeners
- Removed `PageService::createHashBase()` - replaced by the public `PageService::getHashString()`
- Removed the empty `CacheHashEventListener`
- Removed the protected `AbstractContext::getTypoScriptFrontendController()` and `AbstractContext::getIndpEnv()` - custom context types use `getRequest()`, `getFrontendUser()` and `getNormalizedParams()` instead
- Dropped the unused `typo3/cms-extbase` and `typo3/cms-extensionmanager` requirements; no class of either package is referenced. On TYPO3 v14 they were pulled into the package dependency graph and forced both system extensions to be loaded

## Bug Fixes

- Fixed the session context and the `use_session` persistence of the GET parameter context: they read the frontend user from the `frontend.user` request attribute instead of the removed `TypoScriptFrontendController` (#107831), so they match again
- Fixed context-aware page caching: the active context combination and the values of all context GET parameters are added to the page cache identifier via `BeforePageCacheIdentifierIsHashedEvent`, replacing the `createHashBase` hook removed in TYPO3 v13.0 (Breaking #102932). Context variants of a page no longer share one cache entry
- Fixed `config.linkVars`: the GET parameters of all query parameter contexts are appended via `ModifyTypoScriptConfigEvent`, replacing the removed `configArrayPostProc` hook, so the active channel survives following a link
- Fixed the `PageTreeRepository` XCLASS constructor signature, which silently dropped the third `$additionalQueryRestrictions` parameter of TYPO3 v13.4/v14.3 and thereby `options.pageTree.excludeDoktypes` for the whole installation
- Removed the write to `$TYPO3_CONF_VARS['FE']['addRootLineFields']`, removed in TYPO3 v13.2 (#103752)

## Technical Changes

- `IconFactory::getIconForRecord()` is called with the `IconSize` enum instead of the string `small`
- `AfterPageAndLanguageIsResolvedEvent` is consumed via `getPageInformation()` only; the TYPO3 v12 `getController()` fallback is gone
- Event listeners are registered exclusively via `#[AsEventListener]`; the duplicated `Services.yaml` registrations for TYPO3 v12 are gone
- `composer.json` declares `extra.typo3/cms.version` and `extra.typo3/cms.Package.providesPackages` to satisfy TYPO3 v14.3 package metadata (#108345)
- CI matrix runs TYPO3 `^13.4` and `^14.3` against PHP 8.2 - 8.5
- Removed the TYPO3 v12 PHPStan ignore entries; static analysis stays at level 10 with no new ignores
- DDEV development environment provides TYPO3 v13 and v14 instances

---

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

