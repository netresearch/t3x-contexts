<p align="center">
  <a href="https://www.netresearch.de/">
    <img src="Resources/Public/Icons/Extension.svg" alt="Netresearch" width="80" height="80">
  </a>
</p>

<h1 align="center">Multi-channel Contexts</h1>

<p align="center">
  <strong>Content visibility control for TYPO3 based on configurable contexts</strong>
</p>

<p align="center">
  <a href="https://github.com/netresearch/t3x-contexts/releases"><img src="https://img.shields.io/github/v/release/netresearch/t3x-contexts?sort=semver" alt="Latest version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/github/license/netresearch/t3x-contexts" alt="License"></a>
  <a href="https://github.com/netresearch/t3x-contexts/actions/workflows/ci.yml"><img src="https://github.com/netresearch/t3x-contexts/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://github.com/netresearch/t3x-contexts/actions/workflows/phpstan.yml"><img src="https://github.com/netresearch/t3x-contexts/actions/workflows/phpstan.yml/badge.svg" alt="PHPStan"></a>
</p>

---

Show and hide pages and content elements based on configurable "contexts".
With the use of contexts, TYPO3 is able to do multichannel output.

## Requirements

| Version | TYPO3       | PHP        |
|---------|-------------|------------|
| 4.x     | 12.4, 13.4  | 8.2 - 8.4  |
| 3.x     | 11.5        | 7.4 - 8.1  |

## Context Examples

- User IP matches a given rule
- User entered website with GET-Parameter `affID=foo`
- Domain the user visits
- HTTP header values (User-Agent, Accept-Language, etc.)
- A session variable is set
- A combination of any other rules

**With companion extensions:**
- Screen size < 500px (`contexts_wurfl`)
- Browser runs on a tablet or mobile phone (`contexts_wurfl`)
- Location is 15km around a certain place (`contexts_geolocation`)
- User is from one of certain countries (`contexts_geolocation`)

Apart from the context rules, this extension also provides an API to use
contexts in your own extensions.

## Table of Contents

- [Setup](#setup)
- [Creating and Using Contexts](#creating-and-using-contexts)
- [Context Types](#context-types)
- [Integration](#integration)
- [Development](#development)

## Setup

1. Install and activate extension `contexts`
2. Clear TYPO3 cache

```bash
composer require netresearch/contexts
vendor/bin/typo3 extension:activate contexts
vendor/bin/typo3 cache:flush
```

**Optional Extensions:**
- `contexts_geolocation` - Location-based rules (continent, country, area)
- `contexts_wurfl` - Device-based rules (phone, tablet, TV, screen sizes)

## Creating and Using Contexts

### Creating a Context

1. Log into the TYPO3 backend as administrator
2. Go to Web/List view, root page (ID 0)
3. Create a new record: TYPO3 contexts → Context
4. Configure:
   - Title: e.g., "Affiliate ID: foo"
   - Type: "GET parameter"
   - Parameter name: `affID`
   - Parameter value: `foo`
   - Enable "Store result in user session"
5. Save and close

### Using a Context

1. Go to Web/Page, select a page
2. Edit a content element
3. Select the "Contexts" tab
4. For your context, select "Visible: yes"
5. Save

The content element is now only visible when the context matches.

## Context Types

### Domain

Match based on the accessed domain name.

- One domain per line
- Without leading dot: exact match only (`www.example.org` ≠ `example.org`)
- With leading dot: matches all subdomains (`.example.org` matches `www.example.org`)

### GET Parameter

Match based on URL query parameters.

- Enable "Store result in user session" to persist across pages
- Leave value empty to match any non-empty parameter value

### IP Address

Match the user's IP address. IPv4 and IPv6 supported.

```
80.76.201.32          # Full address
80.76.201.32/27       # CIDR notation
FE80::/16             # IPv6 prefix
80.76.201.*           # Wildcard
80.76.*.*             # Multiple wildcards
```

### HTTP Header

Match HTTP request headers (User-Agent, Accept-Language, X-Forwarded-For, etc.)

- Enable "Store result in user session" to persist across pages
- Leave value empty to match any non-empty header value

### Session Variable

Match based on session data. Checks if a session variable with the given name exists.

### Logical Combination

Combine multiple contexts with logical operators:

| Operator | Description |
|----------|-------------|
| `&&` | Logical AND |
| `||` | Logical OR |
| `!` | Negation |
| `(...)` | Grouping |

Example: `mobile && !tablet`

## Integration

### Fluid Templates

```html
<html xmlns:contexts="http://typo3.org/ns/Netresearch/Contexts/ViewHelpers">
    <f:if condition="{contexts:matches(alias:'mobile')}">
        <f:then>Mobile content</f:then>
        <f:else>Desktop content</f:else>
    </f:if>
</html>
```

### TypoScript Conditions

```typoscript
[contextMatch("mobile")]
    page.10.template = EXT:site/Resources/Private/Templates/Mobile.html
[END]
```

### PHP API

```php
use Netresearch\Contexts\Api\ContextMatcher;

if (ContextMatcher::getInstance()->matches('mobile')) {
    // Mobile-specific logic
}
```

## Development

### Testing

```bash
composer install

# Run unit tests
composer test:unit

# Run functional tests (requires database)
composer test:functional

# Run with coverage
composer test:coverage
```

### Code Quality

```bash
# Static analysis (level 8)
composer analyze

# Code style check
composer lint

# Code style fix
composer lint:fix
```

| Tool | Version | Purpose |
|------|---------|---------|
| PHPUnit | 10/11/12 | Unit and functional tests |
| PHPStan | 2.x | Static analysis (level 8) |
| PHP-CS-Fixer | 3.x | Code style (PSR-12) |

## Documentation

Full documentation available at [docs.typo3.org](https://docs.typo3.org/p/netresearch/contexts/main/en-us/).

## License

This project is licensed under the [AGPL-3.0-or-later](LICENSE).

## Credits

Developed and maintained by [Netresearch DTT GmbH](https://www.netresearch.de/).

**Contributors:** Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag, Benni Mack, and [others](https://github.com/netresearch/t3x-contexts/graphs/contributors).
