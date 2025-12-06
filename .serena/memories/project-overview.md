# t3x-nr-contexts Project Overview

## Project Identity
- **Package**: `netresearch/contexts`
- **Type**: TYPO3 CMS Extension
- **Version**: 3.1.1
- **License**: AGPL-3.0-or-later
- **Extension Key**: `contexts`

## Purpose
Multi-channel contexts for TYPO3 - enables showing/hiding pages and content elements based on configurable conditions. Supports multichannel output based on various user/request conditions.

## TYPO3 Compatibility
- Core: ^11.5
- Frontend: ^11.5 || ^14.0
- PHP Platform: 7.4+

## Architecture

### Core Components
- `Classes/Context/AbstractContext.php` - Base context class
- `Classes/Context/Container.php` - Context container (holds all active contexts)
- `Classes/Context/Factory.php` - Context factory (creates context instances)
- `Classes/Context/Setting.php` - Context settings management

### Context Types (`Classes/Context/Type/`)
| Type | File | Description |
|------|------|-------------|
| Domain | DomainContext.php | Match by domain/subdomain |
| IP | IpContext.php | Match by IP address (IPv4/IPv6, CIDR, wildcards) |
| GET Parameter | QueryParameterContext.php | Match by URL query parameters |
| HTTP Header | HttpHeaderContext.php | Match by HTTP headers |
| Session | SessionContext.php | Match by session variables |
| Combination | CombinationContext.php | Logical combinations (&&, ||, !) |

### API (`Classes/Api/`)
- `ContextMatcher.php` - Public API for context matching
- `Configuration.php` - Configuration access
- `Record.php` - Context record handling

### Services (`Classes/Service/`)
- `IconService.php` - Icon management
- `InstallService.php` - Installation utilities
- `DataHandlerService.php` - TCA data handler hooks
- `FrontendControllerService.php` - Frontend controller integration
- `PageService.php` - Page-related operations

### Integration Points
- `Classes/Middleware/ContainerInitialization.php` - PSR-15 middleware
- `Classes/Query/Restriction/ContextRestriction.php` - Doctrine DBAL restriction
- `Classes/ExpressionLanguage/ContextConditionProvider.php` - TypoScript conditions
- `Classes/ViewHelpers/MatchesViewHelper.php` - Fluid template integration
- `Classes/Xclass/Backend/Tree/Repository/PageTreeRepository.php` - Backend page tree

### Configuration
- TCA: `Configuration/TCA/tx_contexts_contexts.php`
- TCA Overrides: pages, tt_content, tx_contexts_contexts
- FlexForms: Per context type configuration
- Services.yaml: Dependency injection
- RequestMiddlewares.php: Middleware registration
- ExpressionLanguage.php: TypoScript condition registration

## Database
- Main table: `tx_contexts_contexts` (context definitions)
- Related: `tx_contexts_settings` (context settings per record)

## Testing
- Unit Tests: `Tests/Unit/`
- Functional Tests: `Tests/Functional/`
- PHPUnit 9.5, PHPStan, PHP_CodeSniffer

## Quality Tools
- PHPStan (phpstan.neon)
- PHP_CodeSniffer (PSR-12)
- Rector (rector.php)
- GrumPHP (grumphp.yml)

## Usage Patterns
1. **Fluid**: `{contexts:matches(alias:'mobile')}`
2. **TypoScript**: `[contextMatch("mobile")]`
3. **API**: Via ContextMatcher class
