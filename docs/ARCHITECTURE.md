# Architecture

Agent-facing component map for the Contexts extension. For usage documentation see `Documentation/` (rendered at docs.typo3.org).

## System Overview

Contexts is a TYPO3 extension that evaluates configurable conditions ("contexts") per request and uses the result to control record visibility. A PSR-15 middleware initializes the context container early in the frontend request; matched contexts then drive query restrictions (flat columns `tx_contexts_enable`/`tx_contexts_disable` on `pages` and `tt_content`), page access checks, menu filtering, cache segmentation, and TypoScript conditions.

## Components

| Component | Path | Responsibility |
|-----------|------|----------------|
| Public API | `Classes/Api/` | `Configuration` (context type registry), `ContextMatcher` (`matches('alias')` for TypoScript/code), `Record` (record-level enable/disable checks) |
| Context base | `Classes/Context/AbstractContext.php` | Base class for all context types: FlexForm config access, inversion, session caching, PSR-7 request access |
| Context container | `Classes/Context/Container.php` | Singleton holding all matched contexts for the current request; dependency-ordered evaluation |
| Context factory | `Classes/Context/Factory.php` | Instantiates context objects from `tx_contexts_contexts` rows |
| Context types | `Classes/Context/Type/` | `DomainContext`, `IpContext`, `QueryParameterContext`, `HttpHeaderContext`, `SessionContext`, `CombinationContext` (+ `Combination/LogicalExpressionEvaluator` for AND/OR/NOT expressions) |
| Middleware | `Classes/Middleware/ContainerInitialization.php` | PSR-15 middleware (registered in `Configuration/RequestMiddlewares.php`) that initializes the container with the frontend request |
| Event listeners | `Classes/EventListener/` | PSR-14 listeners: FlexForm data structure resolution, icon overlay, menu item filtering, page access, page cache identifier, TypoScript config |
| Services | `Classes/Service/` | `DataHandlerService` (flat column sync on save), `FrontendControllerService`, `PageService`, `QueryParameterService`, `IconService`, `InstallService` |
| Query restriction | `Classes/Query/Restriction/ContextRestriction.php` | `EnforceableQueryRestrictionInterface` implementation filtering records by matched contexts |
| Expression language | `Classes/ExpressionLanguage/` | `contextMatch()` TypoScript condition function (registered via `Configuration/ExpressionLanguage.php`) |
| ViewHelpers | `Classes/ViewHelpers/MatchesViewHelper.php` | `contexts:matches` for Fluid templates |
| XCLASS | `Classes/Xclass/Backend/Tree/Repository/PageTreeRepository.php` | Backend page tree integration |
| Configuration | `Configuration/` | TCA (`tx_contexts_contexts` + overrides for `pages`/`tt_content`), FlexForms per context type, `Services.yaml`, Site Set (`Sets/Contexts/`) |

## Dependency Rules

Enforced by PHPat in `Tests/Architecture/LayerTest.php` (runs with the unit test suite):

- Classes in `Netresearch\Contexts\Context\Type` must extend `AbstractContext` (excluding the `LogicalExpressionEvaluator` helper and its exception).
- Classes in `Netresearch\Contexts\Event` must be final. (Namespace currently has no classes; the rule guards future additions.)
- Classes in `Netresearch\Contexts\Dto` must be readonly. (Namespace currently has no classes; the rule guards future additions.)

## Data Flow

1. **Editing time**: `DataHandlerService` reacts to record saves and syncs each context's enable/disable settings into the flat columns of `pages`/`tt_content`.
2. **Request time**: `ContainerInitialization` middleware initializes `Container`, which loads active `tx_contexts_contexts` rows via `Factory` and evaluates `match()` per context (dependency-ordered, session-cached where configured).
3. **Rendering**: `ContextRestriction` filters queries by the matched context UIDs; event listeners adjust page access, menus, and the page cache identifier; TypoScript conditions and Fluid ViewHelpers query `ContextMatcher`.

## Key Decisions

- Modernization plan and rationale: `docs/plans/2026-02-27-gold-standard-modernization-design.md` and `docs/plans/2026-02-27-gold-standard-modernization.md`
- v13/v14 upgrade plan: `docs/plans/2026-01-28-contexts-family-upgrade-v13.md`
- Migration notes for released versions: `Documentation/Migration/Index.rst`
