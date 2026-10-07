<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->

# Security Assurance Case

This document states what the Contexts extension does and does not guarantee in terms of security, the threat model it is built against, and how the code counters the weaknesses that apply to it. Every claim names the file that implements it. Components and data flow are described in [ARCHITECTURE.md](ARCHITECTURE.md); how to report a vulnerability is in [SECURITY.md](../SECURITY.md).

## What the extension is for

Contexts decides per frontend request which configured conditions ("contexts") match, and hides or shows pages and content elements accordingly. It is a content personalisation mechanism. It is not an authentication or authorisation mechanism.

## What users can expect

- **Records restricted to a context are not rendered in the frontend when the context does not match.** `Classes/Query/Restriction/ContextRestriction.php` implements `EnforceableQueryRestrictionInterface` and returns `true` from `isEnforced()`, so the restriction stays on every frontend query of a table with context settings, including queries that remove the default restrictions. `Classes/EventListener/PageAccessEventListener.php` checks the current page and, where "extend to subpages" is set, its parents (`FrontendControllerService::checkEnableFieldsForRootLine()`), and answers a denied page with the site's access-denied response (`ErrorController::accessDeniedAction()`). `Classes/EventListener/MenuItemFilterEventListener.php` filters menu items through `PageService::filterMenuItems()`.
- **Page cache entries are kept apart per context.** `Classes/EventListener/PageCacheIdentifierEventListener.php` adds the active context combination and the values of the GET parameters that contexts evaluate to the page cache identifier, so a page variant rendered for one context is not served to a visitor in another.
- **Database access is parameterised.** Values reach SQL through `createNamedParameter()` with `Connection::PARAM_*` types (`Classes/Context/AbstractContext.php`, `Classes/Service/DataHandlerService.php`) or through `Connection::insert()`, `update()` and `delete()` (`DataHandlerService`), which bind their values. `ContextRestriction` builds its constraints with the `ExpressionBuilder`; the only values it passes are context UIDs, which are integers.
- **Little visitor data is stored.** When a context has "Store result in user session" set, `AbstractContext::storeInSession()` writes one boolean per context into the TYPO3 frontend user session, under the key `contexts-<uid>-<tstamp>`. The extension does not store IP addresses, header values or parameter values.

## What users cannot expect

- **Contexts do not protect confidential content.** Every input a context type matches on comes from the request or from the deployment:
  - `DomainContext` matches the host of the request URI.
  - `IpContext` matches the address `NormalizedParams::getRemoteAddress()` returns (`AbstractContext::getRemoteAddress()`). That value honours `$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']`: behind a reverse proxy that is not configured there, the extension sees the proxy's address; for a proxy configured there, the forwarded client address is used. The constant `AbstractContext::HTTP_X_FORWARDED_FOR` is not used for matching.
  - `HttpHeaderContext` matches any request header the integrator names, including `User-Agent`, `Accept-Language` and `X-Forwarded-For`. A visitor sets these freely.
  - `QueryParameterContext` matches a GET parameter the visitor sets.
  - `SessionContext` checks whether a key exists in the frontend user session.

  A visitor can therefore make a header or parameter context match on purpose. Content that must stay confidential belongs behind TYPO3's frontend user groups or another access control, not behind a context.
- **A stored match outlives the request.** With "Store result in user session" set, `QueryParameterContext` keeps a match for the rest of the frontend session, also on requests without the parameter (`AbstractContext::getMatchFromSession()`).
- **The restriction applies to frontend requests only.** `ContextRestriction::isEnvironmentInFrontendMode()` returns false for backend and CLI requests; there, records are visible to whoever may see them under TYPO3's own permissions.
- **A hidden page is answered as access denied, not as missing.** `PageAccessEventListener` uses the access-denied handler, so a visitor can tell that a page exists.
- **The extension trusts its configuration.** Context records (`tx_contexts_contexts`), their FlexForm configuration and the per-record settings are written by backend users through the TYPO3 backend and DataHandler. Who may do that is decided by TYPO3's backend permissions, not by this extension.

## Threat model

| Actor | Trust | Can influence |
|-------|-------|---------------|
| Frontend visitor | Untrusted | Request host (within TYPO3's `trustedHostsPattern` and the site configuration), headers, query parameters, the frontend session bound to the visitor's cookie |
| Reverse proxy | Trusted if listed in `reverseProxyIP` | The client address the extension sees |
| Backend editor | Trusted within the TYPO3 permissions granted | Context records, their configuration, per-record visibility settings |
| Integrator / developer | Trusted | Registered context types (`Configuration::registerContextType()`), TypoScript conditions, Fluid templates, system configuration |

Trust boundaries:

1. **HTTP request to extension.** `Classes/Middleware/ContainerInitialization.php` (registered in `Configuration/RequestMiddlewares.php`) hands the PSR-7 request to the context container; context types read from that request.
2. **Backend form to database.** `Classes/Form/*FormElement.php` render the settings fields; `Classes/Service/DataHandlerService.php` receives the submitted values through DataHandler hooks and writes `tx_contexts_settings` and the flat columns.
3. **Database to context objects.** `Classes/Context/Factory.php` turns `tx_contexts_contexts` rows into objects.
4. **Extension to rendered output.** Query restriction, page access, menus, page cache identifier, the `contextMatch()` TypoScript condition and the `contexts:matches` ViewHelper.

## Weaknesses and how the code counters them

| Weakness | Where it could arise | Countermeasure |
|----------|---------------------|----------------|
| SQL injection (CWE-89) | Settings reads and writes, frontend restriction | Named parameters with `Connection::PARAM_*` and `Connection` calls that bind their values (`AbstractContext::getSettings()`, `DataHandlerService::saveRecordSettings()`, `saveDefaultSettings()`, `saveFlatSettings()`); `ContextRestriction` passes only integer context UIDs to `ExpressionBuilder::inSet()`, which quotes the value as a literal |
| Code injection (CWE-94) | Logical combinations such as `mobile && !tablet` | `Classes/Context/Type/Combination/LogicalExpressionEvaluator.php` tokenises and parses the expression itself and evaluates it against a map of context results; PHP code is never generated or evaluated. Covered by `Tests/Unit/Classes/Context/Type/Combination/LogicalExpressionEvaluatorTest.php` and, with generated expressions, by `Tests/Fuzz/ContextInputFuzzTest.php` in CI; `Tests/Fuzz/CombinationExpressionTarget.php` is a php-fuzzer target for local runs |
| Unsafe reflection (CWE-470) | Instantiating a context type from a database row | `Factory::createFromDb()` looks the type up in the registry filled by `Configuration::registerContextType()` and never takes a class name from the row; an unknown type falls back to `default`, which is registered without a class and yields no object. The instance must extend `AbstractContext` and must not be a singleton |
| Cross-site scripting in the backend (CWE-79) | Settings form elements | Editor-entered values are escaped with `htmlspecialchars()`: context titles in `RecordSettingsFormElement::getRecordPreview()`, unknown aliases and tokens in `CombinationFormElement::render()`. Setting labels in `DefaultSettingsFormElement::render()` are escaped too; the column labels in `RecordSettingsFormElement::render()` come from language files and TCA configuration |
| Caching a variant for the wrong audience (CWE-524) | Frontend page cache | `PageCacheIdentifierEventListener` (see above); `Tests/Unit/Classes/EventListener/PageCacheIdentifierEventListenerTest.php` |
| Spoofed client address (CWE-348) | `IpContext` | Address from `NormalizedParams::getRemoteAddress()`, which applies forwarded headers only for proxies in `reverseProxyIP`; an address that is neither valid IPv4 nor IPv6 never falls within a configured range: `IpContext::match()` evaluates it as a non-match, which the context's `invert` option then negates like any other result |
| Over-broad host match | `DomainContext` with a leading-dot pattern | `DomainContext::matchDomain()` matches `.example.org` against `example.org` and hosts ending in `.example.org` only; `Tests/Unit/Classes/Context/Type/DomainContextTest.php` includes hosts that merely share the suffix |

## Secure design principles applied

- **Complete mediation**: the frontend restriction is an enforced query restriction (`ContextRestriction::isEnforced()`), and page access is checked on the resolved page and its rootline (`PageAccessEventListener`), not only in menus.
- **Fail-safe defaults**: an empty header name, an invalid client address or an empty IP list are evaluated as a non-match before the context's `invert` option is applied (`HttpHeaderContext::match()`, `IpContext::match()`, `AbstractContext::invert()`); a GET-parameter context without a parameter name throws an exception instead of matching (`QueryParameterContext::match()`).
- **Economy of mechanism**: the combination grammar knows aliases and six operators (`&&`, `||`, `><`, `!`, `(`, `)`), implemented in one class.
- **Least privilege in automation**: every workflow in `.github/workflows/` sets `permissions: {}` or `contents: read` at the top and grants further scopes per job.

## Verification

The checks that run on every pull request, and how to run the tests locally, are listed in [CONTRIBUTING.md](../CONTRIBUTING.md#governance-and-policies). Vulnerabilities are reported and handled as described in [SECURITY.md](../SECURITY.md).
