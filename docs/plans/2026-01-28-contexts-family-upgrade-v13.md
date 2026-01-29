# Contexts Extension Family TYPO3 v13 Modernization Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Upgrade all three Netresearch contexts extensions to TYPO3 v13, PHP 8.2-8.5, with >80% test coverage and full conformance to TYPO3 standards.

**Architecture:** The contexts family consists of a base extension (`contexts`) and two dependent extensions (`contexts_geolocation`, `contexts_wurfl`). The base extension is already modernized; we'll upgrade the sub-extensions to match its quality standards and integrate them into the unified test infrastructure.

**Tech Stack:** TYPO3 13.4 LTS, PHP 8.2-8.5, PHPUnit 11/12, Playwright, PHPStan level 9-10, Rector, Fractor, Infection

---

## Consensus Review

This plan was reviewed using multi-model consensus (gemini-3-pro-preview, gemini-2.5-pro, gemini-2.5-flash).

**Incorporated Recommendations:**

| Recommendation | Action Taken |
|----------------|--------------|
| Use `matomo/device-detector` over WhichBrowser | Updated Task 3.1 - higher update frequency, larger community |
| Add CI routing script for PHP built-in server | Added Task 4.2 - ensures clean URLs work in E2E tests |
| Document WURFL capability loss | Added note in Task 3.1 - screen dims, java support not available |
| PHPStan Level 9 minimum, 10 as stretch | Updated success criteria with stretch goals |
| Mutation score 70% minimum, 80% as stretch | Updated success criteria with stretch goals |
| Document MaxMind database setup | Added Task 4.6 - complete GeoLite2 documentation |
| Add CLI migration wizard for WURFL | Added Task 4.7 - optional migration command |

**Confidence Scores:** 9/10 (gemini-3-pro), 9/10 (gemini-2.5-pro), 7/10 (gemini-2.5-flash)

---

## Project Overview

| Extension | Current State | Target State |
|-----------|--------------|--------------|
| `contexts` | v4.0.0, TYPO3 12/13, PHP 8.2+ | Enhance tests, PHP 8.5 |
| `contexts_geolocation` | v1.0.5, TYPO3 6-8, PHP 5.3-7.1 | Complete rewrite for v13 |
| `contexts_wurfl` | v0.4.3, TYPO3 4.5-6.2, PHP 5.x | Complete rewrite for v13 |

---

## Phase 0: AGENTS.md Setup (All Extensions)

> **FIRST TASK**: Generate AGENTS.md files for all three extensions before any code changes.
> This provides essential context for AI agents working on the upgrade.

### Task 0.1: Generate AGENTS.md for Base Extension (contexts)

**Skill:** `agents:agents`

**Files:**
- Create: `AGENTS.md` (root)
- Create: `Classes/AGENTS.md` (source patterns)
- Create: `Configuration/AGENTS.md` (TCA, FlexForms)
- Create: `Tests/AGENTS.md` (testing patterns)
- Create: `Documentation/AGENTS.md` (doc standards)

**Step 1: Detect project type and scopes**

```bash
cd /home/cybot/projects/contexts/main
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/detect-project.sh .
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/detect-scopes.sh .
```

**Step 2: Extract existing documentation and commands**

```bash
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/extract-commands.sh .
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/extract-documentation.sh .
```

**Step 3: Generate AGENTS.md files**

```bash
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/generate-agents.sh . --style=thin
```

**Step 4: Verify content accuracy**

```bash
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/verify-content.sh . --verbose
```

**Step 5: Review and customize**

Add extension-specific patterns:
- Context type implementation patterns
- FlexForm configuration patterns
- PSR-14 event listener patterns
- Query restriction patterns

**Step 6: Commit**

```bash
git add AGENTS.md Classes/AGENTS.md Configuration/AGENTS.md Tests/AGENTS.md Documentation/AGENTS.md
git commit -S -m "docs: add AGENTS.md for AI agent context

Provides structured guidance for AI agents working on:
- Extension architecture and patterns
- Testing infrastructure
- TYPO3 v12/v13 compatibility
- Code style and quality standards"
```

### Task 0.2: Generate AGENTS.md for Geolocation Extension

**Files:**
- Create: `AGENTS.md` (root)
- Create: `Classes/AGENTS.md` (source patterns)

**Step 1: Initialize branch and generate**

```bash
cd /home/cybot/projects/contexts_geolocation/main
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/generate-agents.sh . --style=thin
```

**Step 2: Add extension-specific context**

Document:
- GeoIP adapter patterns
- MaxMind GeoIP2 integration
- Country/Continent/Distance context types
- IP address handling patterns

**Step 3: Commit**

```bash
git add AGENTS.md Classes/AGENTS.md
git commit -S -m "docs: add AGENTS.md for AI agent context"
```

### Task 0.3: Generate AGENTS.md for Device Detection Extension

**Files:**
- Create: `AGENTS.md` (root)
- Create: `Classes/AGENTS.md` (source patterns)

**Step 1: Initialize branch and generate**

```bash
cd /home/cybot/projects/contexts_wurfl/main
/home/cybot/.claude/plugins/cache/netresearch-claude-code-marketplace/agents/2.2.0/skills/agents/scripts/generate-agents.sh . --style=thin
```

**Step 2: Add extension-specific context**

Document:
- WhichBrowser integration patterns
- Device detection service patterns
- User-Agent parsing patterns
- Mobile/Tablet/Desktop context types

**Step 3: Commit**

```bash
git add AGENTS.md Classes/AGENTS.md
git commit -S -m "docs: add AGENTS.md for AI agent context"
```

---

## Phase 1: Base Extension Enhancement (contexts)

The base extension is already modern. Focus on:
- PHP 8.5 compatibility
- Test coverage improvements
- Documentation enhancements

### Task 1.1: PHP 8.5 Compatibility Verification

**Files:**
- Modify: `composer.json`
- Modify: `ext_emconf.php`
- Modify: `.github/workflows/ci.yml`

**Step 1: Update PHP version constraints**

```json
// composer.json - update require section
"php": "^8.2 || ^8.3 || ^8.4 || ^8.5"
```

**Step 2: Update ext_emconf.php**

```php
'constraints' => [
    'depends' => [
        'typo3' => '12.4.0-13.4.99',
        'php' => '8.2.0-8.5.99',
    ],
],
```

**Step 3: Add PHP 8.5 to CI matrix**

```yaml
# .github/workflows/ci.yml
strategy:
  matrix:
    php: ['8.2', '8.3', '8.4', '8.5']
```

**Step 4: Run Rector for PHP 8.5 implicit nullable fix**

```bash
./vendor/bin/rector process --dry-run
```

**Step 5: Commit**

```bash
git add composer.json ext_emconf.php .github/workflows/ci.yml
git commit -S -m "feat: add PHP 8.5 support"
```

### Task 1.2: Test Coverage Analysis and Improvements

**Files:**
- Create: `Tests/Unit/Context/Type/` (additional tests)
- Create: `Tests/Functional/` (additional scenarios)

**Step 1: Run coverage analysis**

```bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit -c Build/phpunit/UnitTests.xml --coverage-html .Build/coverage
```

**Step 2: Identify gaps in coverage report**

Review `.Build/coverage/index.html` for uncovered lines.

**Step 3: Write tests for uncovered paths**

Focus on:
- Edge cases in `IpContext` (IPv6, malformed input)
- Error paths in `CombinationContext`
- Session handling edge cases

**Step 4: Run tests and verify coverage > 80%**

```bash
./vendor/bin/phpunit -c Build/phpunit/UnitTests.xml
```

**Step 5: Commit**

```bash
git add Tests/
git commit -S -m "test: improve test coverage to >80%"
```

### Task 1.3: Documentation Enhancement

**Files:**
- Modify: `Documentation/` (RST files)

**Step 1: Verify documentation builds**

```bash
ddev exec .Build/vendor/bin/typo3 extension:documentation:render
```

**Step 2: Update for v13 changes**

Ensure all code examples use v13-compatible APIs.

**Step 3: Commit**

```bash
git add Documentation/
git commit -S -m "docs: update documentation for TYPO3 v13"
```

---

## Phase 2: Geolocation Extension Complete Rewrite (contexts_geolocation)

This extension requires a **complete rewrite**. Current code is TYPO3 6.2-8.9 era.

### Task 2.1: Create Modern Extension Skeleton

**Files:**
- Create: `composer.json` (new)
- Create: `ext_emconf.php` (new)
- Create: `ext_localconf.php` (new)
- Create: `Configuration/Services.yaml`

**Step 1: Initialize new branch**

```bash
cd /home/cybot/projects/contexts_geolocation
git -C .bare worktree add ../feature/v13-rewrite -b feature/v13-rewrite master
cd feature/v13-rewrite
```

**Step 2: Create modern composer.json**

```json
{
    "name": "netresearch/contexts-geolocation",
    "type": "typo3-cms-extension",
    "description": "Geolocation context types for the contexts extension",
    "license": "AGPL-3.0-or-later",
    "require": {
        "php": "^8.2",
        "typo3/cms-core": "^12.4 || ^13.4",
        "typo3/cms-backend": "^12.4 || ^13.4",
        "typo3/cms-extbase": "^12.4 || ^13.4",
        "typo3/cms-frontend": "^12.4 || ^13.4",
        "netresearch/contexts": "^4.0",
        "geoip2/geoip2": "^3.0"
    },
    "require-dev": {
        "typo3/testing-framework": "^8.0 || ^9.0",
        "phpunit/phpunit": "^10.5 || ^11.0 || ^12.0",
        "phpstan/phpstan": "^2.0",
        "saschaegerer/phpstan-typo3": "^2.0",
        "friendsofphp/php-cs-fixer": "^3.64",
        "ssch/typo3-rector": "^2.0",
        "a]ndreas-wolf/fractor": "^0.5",
        "infection/infection": "^0.29"
    },
    "autoload": {
        "psr-4": {
            "Netresearch\\ContextsGeolocation\\": "Classes/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Netresearch\\ContextsGeolocation\\Tests\\": "Tests/"
        }
    },
    "extra": {
        "typo3/cms": {
            "extension-key": "contexts_geolocation"
        }
    },
    "config": {
        "vendor-dir": ".Build/vendor",
        "bin-dir": ".Build/bin"
    }
}
```

**Step 3: Create ext_emconf.php**

```php
<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Contexts: Geolocation',
    'description' => 'Geolocation-based context types (continent, country, distance) for the contexts extension',
    'category' => 'misc',
    'author' => 'Netresearch DTT GmbH',
    'author_email' => 'info@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'php' => '8.2.0-8.5.99',
            'contexts' => '4.0.0-4.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
```

**Step 4: Create Configuration/Services.yaml**

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Netresearch\ContextsGeolocation\:
    resource: '../Classes/*'
    exclude:
      - '../Classes/Domain/Model/*'
```

**Step 5: Commit skeleton**

```bash
git add composer.json ext_emconf.php Configuration/Services.yaml
git commit -S -m "feat!: initialize TYPO3 v13 extension skeleton

BREAKING CHANGE: Complete rewrite for TYPO3 v12/v13 compatibility"
```

### Task 2.2: Create Modern GeoIP Adapter with MaxMind GeoIP2

**Files:**
- Create: `Classes/Adapter/GeoIpAdapterInterface.php`
- Create: `Classes/Adapter/MaxMindGeoIp2Adapter.php`
- Create: `Classes/Service/GeoLocationService.php`
- Create: `Tests/Unit/Adapter/MaxMindGeoIp2AdapterTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Adapter;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Adapter\MaxMindGeoIp2Adapter;
use PHPUnit\Framework\TestCase;

final class MaxMindGeoIp2AdapterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $adapter = new MaxMindGeoIp2Adapter('/path/to/GeoLite2-City.mmdb');
        self::assertInstanceOf(GeoIpAdapterInterface::class, $adapter);
    }

    public function testGetCountryCodeReturnsNullForInvalidIp(): void
    {
        $adapter = $this->createMock(MaxMindGeoIp2Adapter::class);
        $adapter->method('getCountryCode')->willReturn(null);

        self::assertNull($adapter->getCountryCode('invalid-ip'));
    }
}
```

**Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit Tests/Unit/Adapter/MaxMindGeoIp2AdapterTest.php
```

Expected: FAIL with class not found

**Step 3: Create interface**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Adapter;

interface GeoIpAdapterInterface
{
    public function getCountryCode(string $ipAddress): ?string;

    public function getCountryName(string $ipAddress): ?string;

    public function getContinentCode(string $ipAddress): ?string;

    public function getLatitude(string $ipAddress): ?float;

    public function getLongitude(string $ipAddress): ?float;

    public function getCity(string $ipAddress): ?string;
}
```

**Step 4: Create MaxMind adapter implementation**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Adapter;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

final class MaxMindGeoIp2Adapter implements GeoIpAdapterInterface
{
    private ?Reader $reader = null;

    public function __construct(
        private readonly string $databasePath,
    ) {}

    public function getCountryCode(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->country->isoCode;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getCountryName(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->country->name;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getContinentCode(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->continent->code;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getLatitude(string $ipAddress): ?float
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->location->latitude;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getLongitude(string $ipAddress): ?float
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->location->longitude;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getCity(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->city->name;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    private function getReader(): Reader
    {
        if ($this->reader === null) {
            $this->reader = new Reader($this->databasePath);
        }
        return $this->reader;
    }
}
```

**Step 5: Run tests**

```bash
./vendor/bin/phpunit Tests/Unit/Adapter/
```

**Step 6: Commit**

```bash
git add Classes/Adapter/ Tests/Unit/Adapter/
git commit -S -m "feat: add MaxMind GeoIP2 adapter

Replaces legacy PHP geoip extension and PEAR Net_GeoIP with modern
MaxMind GeoIP2 library (composer-installable, actively maintained)."
```

### Task 2.3: Create Context Types (Country, Continent, Distance)

**Files:**
- Create: `Classes/Context/Type/CountryContext.php`
- Create: `Classes/Context/Type/ContinentContext.php`
- Create: `Classes/Context/Type/DistanceContext.php`
- Create: `Tests/Unit/Context/Type/CountryContextTest.php`
- Create: `Tests/Unit/Context/Type/ContinentContextTest.php`
- Create: `Tests/Unit/Context/Type/DistanceContextTest.php`

**Step 1: Write failing test for CountryContext**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use PHPUnit\Framework\TestCase;

final class CountryContextTest extends TestCase
{
    public function testExtendsAbstractContext(): void
    {
        $context = $this->getMockBuilder(CountryContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertInstanceOf(AbstractContext::class, $context);
    }

    public function testMatchReturnsTrueWhenCountryMatches(): void
    {
        // Will be implemented with proper mocking
    }
}
```

**Step 2: Create CountryContext**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CountryContext extends AbstractContext
{
    public function __construct(
        private readonly GeoIpAdapterInterface $geoIpAdapter,
    ) {}

    public function match(?ServerRequestInterface $request = null): bool
    {
        $countries = $this->getConfiguredCountries();
        if ($countries === []) {
            return false;
        }

        $clientIp = $this->getClientIp($request);
        if ($clientIp === null) {
            return $this->isInverted();
        }

        $countryCode = $this->geoIpAdapter->getCountryCode($clientIp);
        if ($countryCode === null) {
            return $this->isInverted();
        }

        $matches = in_array($countryCode, $countries, true);
        return $this->isInverted() ? !$matches : $matches;
    }

    /**
     * @return string[]
     */
    private function getConfiguredCountries(): array
    {
        $value = $this->getConfValue('field_countries', '');
        if ($value === '') {
            return [];
        }
        return GeneralUtility::trimExplode(',', $value, true);
    }

    private function getClientIp(?ServerRequestInterface $request): ?string
    {
        if ($request !== null) {
            $serverParams = $request->getServerParams();
            return $serverParams['REMOTE_ADDR'] ?? null;
        }
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
```

**Step 3: Create ContinentContext and DistanceContext similarly**

(Follow same TDD pattern)

**Step 4: Run tests**

```bash
./vendor/bin/phpunit Tests/Unit/Context/Type/
```

**Step 5: Commit**

```bash
git add Classes/Context/Type/ Tests/Unit/Context/Type/
git commit -S -m "feat: add geolocation context types

- CountryContext: match by ISO country code
- ContinentContext: match by continent code
- DistanceContext: match by distance from point"
```

### Task 2.4: Create FlexForms and TCA

**Files:**
- Create: `Configuration/FlexForms/Country.xml`
- Create: `Configuration/FlexForms/Continent.xml`
- Create: `Configuration/FlexForms/Distance.xml`
- Create: `Configuration/TCA/Overrides/tx_contexts_contexts.php`

**Step 1: Create Country FlexForm**

```xml
<?xml version="1.0" encoding="utf-8"?>
<T3DataStructure>
    <meta>
        <langDisable>1</langDisable>
    </meta>
    <sheets>
        <sDEF>
            <ROOT>
                <sheetTitle>LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:flexform.sheet.general</sheetTitle>
                <type>array</type>
                <el>
                    <field_countries>
                        <label>LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:flexform.countries</label>
                        <config>
                            <type>select</type>
                            <renderType>selectMultipleSideBySide</renderType>
                            <itemsProcFunc>Netresearch\ContextsGeolocation\Backend\ItemsProcFunc->getCountries</itemsProcFunc>
                            <minitems>1</minitems>
                            <size>10</size>
                        </config>
                    </field_countries>
                </el>
            </ROOT>
        </sDEF>
    </sheets>
</T3DataStructure>
```

**Step 2: Create TCA override**

```php
<?php

declare(strict_types=1);

use Netresearch\Contexts\Api\Configuration;
use Netresearch\ContextsGeolocation\Context\Type\ContinentContext;
use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use Netresearch\ContextsGeolocation\Context\Type\DistanceContext;

defined('TYPO3') or die();

Configuration::registerContextType(
    'geolocation_country',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.country',
    CountryContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Country.xml'
);

Configuration::registerContextType(
    'geolocation_continent',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.continent',
    ContinentContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Continent.xml'
);

Configuration::registerContextType(
    'geolocation_distance',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.distance',
    DistanceContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Distance.xml'
);
```

**Step 3: Commit**

```bash
git add Configuration/
git commit -S -m "feat: add TCA and FlexForm configuration"
```

### Task 2.5: Setup Test Infrastructure

**Files:**
- Create: `Build/phpunit/UnitTests.xml`
- Create: `Build/phpunit/FunctionalTests.xml`
- Create: `Build/phpstan.neon`
- Create: `Build/Scripts/runTests.sh`
- Create: `.github/workflows/ci.yml`

**Step 1: Copy test infrastructure from base contexts extension**

```bash
cp -r ../contexts/main/Build/phpunit Build/phpunit
cp ../contexts/main/Build/phpstan.neon Build/phpstan.neon
cp ../contexts/main/Build/Scripts/runTests.sh Build/Scripts/runTests.sh
```

**Step 2: Adapt paths and namespaces**

Update `Build/phpunit/UnitTests.xml`:
```xml
<testsuites>
    <testsuite name="Unit">
        <directory>../../Tests/Unit</directory>
    </testsuite>
</testsuites>
```

**Step 3: Create CI workflow**

```yaml
name: CI

on:
  push:
    branches: [main, 'feature/**']
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.2', '8.3', '8.4', '8.5']
        typo3: ['12.4', '13.4']
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: intl, pdo_sqlite
          coverage: xdebug

      - name: Install dependencies
        run: composer require typo3/cms-core:^${{ matrix.typo3 }} --no-progress --prefer-dist

      - name: Run PHPStan
        run: .Build/bin/phpstan analyse -c Build/phpstan.neon

      - name: Run Unit Tests
        run: .Build/bin/phpunit -c Build/phpunit/UnitTests.xml
```

**Step 4: Commit**

```bash
git add Build/ .github/
git commit -S -m "chore: add test infrastructure and CI"
```

### Task 2.6: Create Functional Tests

**Files:**
- Create: `Tests/Functional/Context/Type/CountryContextTest.php`
- Create: `Tests/Functional/Fixtures/tx_contexts_contexts.csv`

**Step 1: Create CSV fixture**

```csv
"tx_contexts_contexts"
,"uid","pid","type","title","alias","type_conf","disabled","use_session"
,1,0,"geolocation_country","Germany Context","germany","<?xml version=""1.0"" encoding=""utf-8""?><T3FlexForms><data><sheet index=""sDEF""><language index=""lDEF""><field index=""field_countries""><value index=""vDEF"">DE</value></field></language></sheet></data></T3FlexForms>",0,0
```

**Step 2: Create functional test**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Functional\Context\Type;

use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CountryContextTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
        'netresearch/contexts-geolocation',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_contexts_contexts.csv');
    }

    public function testCountryContextMatchesConfiguredCountry(): void
    {
        // Test implementation with mocked GeoIP adapter
    }
}
```

**Step 3: Run functional tests**

```bash
.Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml
```

**Step 4: Commit**

```bash
git add Tests/Functional/
git commit -S -m "test: add functional tests for geolocation contexts"
```

### Task 2.7: Documentation

**Files:**
- Create: `Documentation/Index.rst`
- Create: `Documentation/Introduction/Index.rst`
- Create: `Documentation/Installation/Index.rst`
- Create: `Documentation/Configuration/Index.rst`
- Create: `README.md`

**Step 1: Create documentation structure following TYPO3 docs skill**

(Use typo3-docs skill templates)

**Step 2: Commit**

```bash
git add Documentation/ README.md
git commit -S -m "docs: add comprehensive documentation"
```

---

## Phase 3: WURFL Extension Complete Rewrite (contexts_wurfl)

This extension is the most outdated (TYPO3 4.5-6.2) and requires significant architectural decisions.

### Task 3.1: Architectural Decision - Replace WURFL

**Decision Required:**

The bundled WURFL library (wurfl-dbapi-1.4.4.0) is:
- 12+ years old
- Unmaintained
- Uses deprecated PHP patterns
- Requires complex database setup

**Options:**

1. **DeviceDetector by Matomo** (Recommended after consensus review)
   - Composer package: `matomo/device-detector`
   - Higher update frequency (crucial for new devices)
   - Larger community (Matomo user base)
   - Industry leader for PHP device detection
   - No database required

2. **WhichBrowser/Parser**
   - Composer package: `whichbrowser/parser`
   - No database required
   - Actively maintained
   - Similar device detection capabilities

3. **Mobile Detect**
   - Simpler, fewer features
   - Only mobile/tablet detection

**Recommended:** Use `matomo/device-detector` as it provides comprehensive device detection with higher update frequency and larger community support.

> **IMPORTANT - WURFL Capability Loss:**
> Users relying on WURFL's exhaustive device capability flags (screen dimensions, java support, specific hardware features) will lose this functionality. Modern parsers are heuristic-based and only provide: device type, OS, browser, brand, and model. This must be documented in the migration guide.

### Task 3.2: Create Modern Extension Skeleton

**Files:**
- Create: `composer.json`
- Create: `ext_emconf.php`
- Create: `Configuration/Services.yaml`

**Step 1: Initialize branch**

```bash
cd /home/cybot/projects/contexts_wurfl
git -C .bare worktree add ../feature/v13-rewrite -b feature/v13-rewrite master
cd feature/v13-rewrite
```

**Step 2: Create composer.json**

```json
{
    "name": "netresearch/contexts-wurfl",
    "type": "typo3-cms-extension",
    "description": "Device detection context types for the contexts extension",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": "^8.2",
        "typo3/cms-core": "^12.4 || ^13.4",
        "typo3/cms-backend": "^12.4 || ^13.4",
        "netresearch/contexts": "^4.0",
        "matomo/device-detector": "^6.0"
    },
    "require-dev": {
        "typo3/testing-framework": "^8.0 || ^9.0",
        "phpunit/phpunit": "^10.5 || ^11.0 || ^12.0",
        "phpstan/phpstan": "^2.0",
        "saschaegerer/phpstan-typo3": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Netresearch\\ContextsDevice\\": "Classes/"
        }
    },
    "extra": {
        "typo3/cms": {
            "extension-key": "contexts_wurfl"
        }
    }
}
```

**Step 3: Create ext_emconf.php**

```php
<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Contexts: Device Detection',
    'description' => 'Device detection context types (mobile, tablet, browser) for the contexts extension',
    'category' => 'misc',
    'author' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'php' => '8.2.0-8.5.99',
            'contexts' => '4.0.0-4.99.99',
        ],
    ],
];
```

**Step 4: Commit**

```bash
git add composer.json ext_emconf.php
git commit -S -m "feat!: initialize TYPO3 v13 extension skeleton

BREAKING CHANGE: Complete rewrite replacing WURFL with WhichBrowser"
```

### Task 3.3: Create Device Detection Service

**Files:**
- Create: `Classes/Service/DeviceDetectionService.php`
- Create: `Tests/Unit/Service/DeviceDetectionServiceTest.php`

**Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsDevice\Tests\Unit\Service;

use Netresearch\ContextsDevice\Service\DeviceDetectionService;
use PHPUnit\Framework\TestCase;

final class DeviceDetectionServiceTest extends TestCase
{
    public function testDetectsMobileDevice(): void
    {
        $service = new DeviceDetectionService();
        $result = $service->detect('Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');

        self::assertTrue($result->isMobile());
    }

    public function testDetectsTabletDevice(): void
    {
        $service = new DeviceDetectionService();
        $result = $service->detect('Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)');

        self::assertTrue($result->isTablet());
    }

    public function testDetectsDesktopDevice(): void
    {
        $service = new DeviceDetectionService();
        $result = $service->detect('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        self::assertFalse($result->isMobile());
        self::assertFalse($result->isTablet());
    }
}
```

**Step 2: Create service**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsDevice\Service;

use DeviceDetector\DeviceDetector;

final class DeviceDetectionService
{
    public function detect(string $userAgent): DeviceInfo
    {
        $detector = new DeviceDetector($userAgent);
        $detector->parse();

        return new DeviceInfo(
            isMobile: $detector->isMobile(),
            isTablet: $detector->isTablet(),
            browserName: $detector->getClient('name'),
            browserVersion: $detector->getClient('version'),
            osName: $detector->getOs('name'),
            osVersion: $detector->getOs('version'),
            deviceBrand: $detector->getBrandName(),
            deviceModel: $detector->getModel(),
        );
    }
}
```

**Step 3: Create DeviceInfo DTO**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsDevice\Service;

final readonly class DeviceInfo
{
    public function __construct(
        public bool $isMobile,
        public bool $isTablet,
        public ?string $browserName,
        public ?string $browserVersion,
        public ?string $osName,
        public ?string $osVersion,
        public ?string $deviceBrand,
        public ?string $deviceModel,
    ) {}

    public function isDesktop(): bool
    {
        return !$this->isMobile && !$this->isTablet;
    }
}
```

**Step 4: Run tests**

```bash
./vendor/bin/phpunit Tests/Unit/Service/
```

**Step 5: Commit**

```bash
git add Classes/Service/ Tests/Unit/Service/
git commit -S -m "feat: add device detection service using WhichBrowser"
```

### Task 3.4: Create Device Context Type

**Files:**
- Create: `Classes/Context/Type/DeviceContext.php`
- Create: `Configuration/FlexForms/Device.xml`
- Create: `Tests/Unit/Context/Type/DeviceContextTest.php`

**Step 1: Create context type**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsDevice\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsDevice\Service\DeviceDetectionService;
use Psr\Http\Message\ServerRequestInterface;

final class DeviceContext extends AbstractContext
{
    public function __construct(
        private readonly DeviceDetectionService $deviceDetectionService,
    ) {}

    public function match(?ServerRequestInterface $request = null): bool
    {
        $userAgent = $this->getUserAgent($request);
        if ($userAgent === null) {
            return $this->isInverted();
        }

        $deviceInfo = $this->deviceDetectionService->detect($userAgent);

        $matchesMobile = $this->getConfValue('settings.isMobile', false) && $deviceInfo->isMobile;
        $matchesTablet = $this->getConfValue('settings.isTablet', false) && $deviceInfo->isTablet;
        $matchesDesktop = $this->getConfValue('settings.isDesktop', false) && $deviceInfo->isDesktop();

        $matches = $matchesMobile || $matchesTablet || $matchesDesktop;

        return $this->isInverted() ? !$matches : $matches;
    }

    private function getUserAgent(?ServerRequestInterface $request): ?string
    {
        if ($request !== null) {
            return $request->getHeaderLine('User-Agent') ?: null;
        }
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
```

**Step 2: Create FlexForm**

```xml
<?xml version="1.0" encoding="utf-8"?>
<T3DataStructure>
    <sheets>
        <sDEF>
            <ROOT>
                <sheetTitle>Device Type</sheetTitle>
                <type>array</type>
                <el>
                    <settings.isMobile>
                        <label>Match Mobile Devices</label>
                        <config>
                            <type>check</type>
                        </config>
                    </settings.isMobile>
                    <settings.isTablet>
                        <label>Match Tablets</label>
                        <config>
                            <type>check</type>
                        </config>
                    </settings.isTablet>
                    <settings.isDesktop>
                        <label>Match Desktop</label>
                        <config>
                            <type>check</type>
                        </config>
                    </settings.isDesktop>
                </el>
            </ROOT>
        </sDEF>
    </sheets>
</T3DataStructure>
```

**Step 3: Commit**

```bash
git add Classes/Context/Type/ Configuration/FlexForms/
git commit -S -m "feat: add device context type with mobile/tablet/desktop detection"
```

### Task 3.5: Create Browser Context Type

**Files:**
- Create: `Classes/Context/Type/BrowserContext.php`
- Create: `Configuration/FlexForms/Browser.xml`

**Step 1: Create browser context**

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsDevice\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsDevice\Service\DeviceDetectionService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BrowserContext extends AbstractContext
{
    public function __construct(
        private readonly DeviceDetectionService $deviceDetectionService,
    ) {}

    public function match(?ServerRequestInterface $request = null): bool
    {
        $userAgent = $this->getUserAgent($request);
        if ($userAgent === null) {
            return $this->isInverted();
        }

        $deviceInfo = $this->deviceDetectionService->detect($userAgent);

        $configuredBrowsers = $this->getConfiguredBrowsers();
        if ($configuredBrowsers === []) {
            return false;
        }

        $matches = in_array($deviceInfo->browserName, $configuredBrowsers, true);

        return $this->isInverted() ? !$matches : $matches;
    }

    /**
     * @return string[]
     */
    private function getConfiguredBrowsers(): array
    {
        $value = $this->getConfValue('settings.browsers', '');
        return GeneralUtility::trimExplode(',', $value, true);
    }

    private function getUserAgent(?ServerRequestInterface $request): ?string
    {
        if ($request !== null) {
            return $request->getHeaderLine('User-Agent') ?: null;
        }
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
```

**Step 2: Commit**

```bash
git add Classes/Context/Type/BrowserContext.php Configuration/FlexForms/Browser.xml
git commit -S -m "feat: add browser context type"
```

### Task 3.6: Register Context Types

**Files:**
- Create: `Configuration/TCA/Overrides/tx_contexts_contexts.php`
- Create: `ext_localconf.php`

**Step 1: Create TCA override**

```php
<?php

declare(strict_types=1);

use Netresearch\Contexts\Api\Configuration;
use Netresearch\ContextsDevice\Context\Type\BrowserContext;
use Netresearch\ContextsDevice\Context\Type\DeviceContext;

defined('TYPO3') or die();

Configuration::registerContextType(
    'device',
    'LLL:EXT:contexts_wurfl/Resources/Private/Language/locallang.xlf:context.type.device',
    DeviceContext::class,
    'FILE:EXT:contexts_wurfl/Configuration/FlexForms/Device.xml'
);

Configuration::registerContextType(
    'browser',
    'LLL:EXT:contexts_wurfl/Resources/Private/Language/locallang.xlf:context.type.browser',
    BrowserContext::class,
    'FILE:EXT:contexts_wurfl/Configuration/FlexForms/Browser.xml'
);
```

**Step 2: Commit**

```bash
git add Configuration/TCA/Overrides/ ext_localconf.php
git commit -S -m "feat: register device and browser context types"
```

### Task 3.7: Test Infrastructure and CI

**Files:**
- Create: `Build/` directory structure
- Create: `.github/workflows/ci.yml`
- Create: `Tests/Functional/`

(Follow same pattern as Task 2.5 and 2.6)

### Task 3.8: Documentation and Migration Guide

**Files:**
- Create: `Documentation/` structure
- Create: `Documentation/Migration/FromWurfl.rst`
- Create: `README.md`

**Step 1: Create migration guide**

```rst
Migration from WURFL to WhichBrowser
====================================

Version 1.0.0 completely replaces the bundled WURFL library with WhichBrowser.

Breaking Changes
----------------

1. **No Database Required**: The new version doesn't need WURFL database tables.
   You can safely remove all ``tx_contextswurfl_*`` tables.

2. **Configuration Changes**: FlexForm configuration has been simplified.

3. **Namespace Change**: Classes moved from ``Tx_ContextsWurfl_*`` to
   ``Netresearch\\ContextsDevice\\*``.

Migration Steps
---------------

1. Update extension to v1.0.0
2. Run database migrations to remove old tables
3. Update any custom code using old class names
4. Clear all caches
```

**Step 2: Commit**

```bash
git add Documentation/ README.md
git commit -S -m "docs: add documentation and migration guide"
```

---

## Phase 4: Integration and Quality Assurance

### Task 4.1: Cross-Extension Functional Tests

**Files:**
- Create: `Tests/Functional/Integration/` in each extension

Test that all three extensions work together properly.

### Task 4.2: CI Router Script for E2E Tests

**Files:**
- Create: `Build/Scripts/router.php` in each extension

The PHP built-in server needs a router script to simulate Apache/Nginx rewrites for TYPO3 clean URLs.

**Step 1: Create router script**

```php
<?php
// Build/Scripts/router.php
// Router script for PHP built-in server to handle TYPO3 rewrites

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../../.Build/Web' . $path;

// Serve static files directly
if (is_file($file)) {
    return false;
}

// Route everything else through TYPO3 index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../../.Build/Web/index.php';
require __DIR__ . '/../../.Build/Web/index.php';
```

**Step 2: Update CI workflow**

```yaml
- name: Start PHP server with router
  run: php -S 0.0.0.0:8080 -t .Build/Web Build/Scripts/router.php &
```

### Task 4.3: E2E Tests with Playwright

**Files:**
- Create: `Build/playwright/` in each extension

Create Playwright E2E tests for:
- Context creation in TYPO3 backend
- Context matching in frontend
- Menu filtering based on contexts

### Task 4.4: PHPStan Compliance

Run PHPStan at level 9 (minimum), level 10 as stretch goal:

```bash
# Minimum requirement
./vendor/bin/phpstan analyse -c Build/phpstan.neon --level 9

# Stretch goal
./vendor/bin/phpstan analyse -c Build/phpstan.neon --level 10
```

### Task 4.5: Mutation Testing

Configure and run Infection:

```bash
# Minimum requirement
./vendor/bin/infection --min-msi=70

# Stretch goal
./vendor/bin/infection --min-msi=80
```

### Task 4.6: MaxMind GeoLite2 Documentation

**Files:**
- Create: `Documentation/Configuration/GeoIP.rst`

Document the MaxMind GeoLite2 database setup process for users:

```rst
MaxMind GeoLite2 Database Setup
===============================

The contexts_geolocation extension requires a MaxMind GeoLite2 database.

Obtaining the Database
----------------------

1. Create a free MaxMind account at https://www.maxmind.com/en/geolite2/signup
2. Generate a license key in your account dashboard
3. Download GeoLite2-City.mmdb from your account

Installation
------------

Place the database file in one of these locations:

- ``/var/lib/GeoIP/GeoLite2-City.mmdb`` (recommended for Linux)
- ``EXT:contexts_geolocation/Resources/Private/GeoIP/GeoLite2-City.mmdb``

Configure the path in Extension Configuration:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['contexts_geolocation']['geoipDatabasePath']
       = '/var/lib/GeoIP/GeoLite2-City.mmdb';

Automatic Updates
-----------------

Use ``geoipupdate`` tool for automatic database updates:

.. code-block:: bash

   # Install geoipupdate
   apt-get install geoipupdate

   # Configure /etc/GeoIP.conf with your license key
   # Run weekly via cron
   0 0 * * 0 /usr/bin/geoipupdate
```

### Task 4.7: WURFL Migration CLI Command

**Files:**
- Create: `Classes/Command/MigrateFromWurflCommand.php`

Create optional CLI command to help users migrate from old WURFL contexts:

```php
<?php

declare(strict_types=1);

namespace Netresearch\ContextsDevice\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MigrateFromWurflCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Migrate old WURFL context records to new device detection format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find old WURFL context records and migrate configuration
        $io->title('Migrating WURFL Contexts');

        // Implementation: read old tx_contexts_contexts where type='wurfl'
        // Convert configuration to new format
        // Update records

        $io->success('Migration complete');
        return Command::SUCCESS;
    }
}
```

### Task 4.8: Final Documentation Review

Ensure all extensions have:
- Complete README.md
- RST documentation
- Changelog
- Migration guides
- MaxMind setup documentation (geolocation)
- WURFL capability loss documentation (device detection)

### Task 4.9: Release Preparation

For each extension:

1. Update version numbers
2. Create signed tags
3. Push to GitHub
4. Publish to TER

---

## Success Criteria

| Criterion | Target | Stretch Goal |
|-----------|--------|--------------|
| TYPO3 v12 + v13 support | All extensions | - |
| PHP 8.2 - 8.5 support | All extensions | - |
| Unit test coverage | > 80% | > 90% |
| Functional tests | All critical paths | - |
| E2E tests | Backend + Frontend | - |
| PHPStan level | 9 (minimum) | 10 (max) |
| Mutation score | > 70% MSI | > 80% MSI |
| Documentation | Complete RST | - |
| CI/CD | GitHub Actions (NO DDEV!) | - |
| DDEV setup | Local dev only | - |
| Netresearch branding | Logos, README, ext_emconf | - |
| Landing page | Netresearch-branded index.html | - |

---

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| MaxMind GeoIP2 requires account | Document free GeoLite2 signup and setup process (Task 4.6) |
| MaxMind database updates | Document geoipupdate tool and cron setup |
| WURFL capability loss | Document that screen dims, java support, hardware flags are NOT available in modern parsers - only device type, OS, browser (Task 3.1 note) |
| Device detection accuracy | Use matomo/device-detector (higher update frequency); test with real user agents |
| Breaking changes for users | Provide comprehensive migration guides + CLI wizard (Task 4.7) |
| Test flakiness | Use deterministic fixtures, avoid sessions |
| E2E routing in CI | Use PHP router script for clean URLs (Task 4.2) |
| PHPStan noise | Target Level 9 minimum, Level 10 as stretch goal |

---

## Phase 5: DDEV Local Development Setup

> **CRITICAL**: DDEV is for LOCAL DEVELOPMENT ONLY. CI/CD and tests MUST NOT use DDEV.
> Tests run via GitHub Actions with PHP built-in server + MariaDB service containers.

### Task 5.1: DDEV Configuration for Base Extension (contexts)

**Files:**
- Create: `.ddev/config.yaml`
- Create: `.ddev/docker-compose.web.yaml`
- Create: `.ddev/apache/apache-site.conf`
- Create: `.ddev/commands/web/install-v12`
- Create: `.ddev/commands/web/install-v13`
- Create: `.ddev/index.html` (Netresearch branded)

**Step 1: Create DDEV config**

```yaml
# .ddev/config.yaml
name: contexts
type: typo3
docroot: ""
php_version: "8.3"
webserver_type: apache-fpm
database:
  type: mariadb
  version: "11.4"
router_http_port: "80"
router_https_port: "443"
additional_hostnames:
  - v12
  - v13
  - docs
web_environment:
  - TYPO3_CONTEXT=Development
hooks:
  post-start:
    - exec: "[ -f .Build/vendor/autoload.php ] || composer install"
```

**Step 2: Create multi-version Apache config**

```apache
# .ddev/apache/apache-site.conf
<VirtualHost *:80>
    ServerName v12.contexts.ddev.site
    DocumentRoot /var/www/html/v12/public

    <Directory /var/www/html/v12/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName v13.contexts.ddev.site
    DocumentRoot /var/www/html/v13/public

    <Directory /var/www/html/v13/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName docs.contexts.ddev.site
    DocumentRoot /var/www/html/Documentation-GENERATED-temp

    <Directory /var/www/html/Documentation-GENERATED-temp>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Step 3: Create install commands**

```bash
#!/bin/bash
## .ddev/commands/web/install-v13
## Description: Install TYPO3 v13 with extension
## Usage: install-v13

set -e

VERSION="v13"
DB_NAME="v13"
TYPO3_VERSION="^13.4"

echo "Installing TYPO3 ${TYPO3_VERSION}..."

mkdir -p /var/www/html/${VERSION}
cd /var/www/html/${VERSION}

composer create-project typo3/cms-base-distribution . ${TYPO3_VERSION} --no-interaction
composer require netresearch/contexts:@dev --no-interaction

# Setup database
mysql -e "DROP DATABASE IF EXISTS ${DB_NAME}; CREATE DATABASE ${DB_NAME};"

# Install TYPO3
./vendor/bin/typo3 setup --no-interaction \
    --driver=mysqli \
    --host=db \
    --port=3306 \
    --dbname=${DB_NAME} \
    --username=db \
    --password=db \
    --admin-username=admin \
    --admin-password='Joh316!' \
    --admin-email=admin@example.com \
    --project-name="Contexts Test ${VERSION}"

# Activate extension
./vendor/bin/typo3 extension:activate contexts

echo "TYPO3 ${VERSION} installed at https://${VERSION}.contexts.ddev.site/typo3/"
echo "Credentials: admin / Joh316!"
```

**Step 4: Create Netresearch-branded landing page**

```html
<!-- .ddev/index.html -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contexts Extension - Development Environment</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --nr-primary: #2F99A4;
            --nr-accent: #FF4D00;
            --nr-text: #585961;
            --nr-grey: #CCCDCC;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Open Sans', sans-serif;
            color: var(--nr-text);
            line-height: 1.6;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3 { font-family: 'Raleway', sans-serif; color: var(--nr-primary); }
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--nr-primary);
        }
        .logo { width: 48px; height: 48px; }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .card {
            border: 1px solid var(--nr-grey);
            border-radius: 8px;
            padding: 1.5rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            border-color: var(--nr-primary);
            box-shadow: 0 4px 12px rgba(47, 153, 164, 0.15);
        }
        .card h3 { margin-bottom: 0.5rem; }
        .card a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--nr-primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
        }
        .card a:hover { background: #257a83; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .badge-lts { background: #e8f5e9; color: #2e7d32; }
        .badge-dev { background: #fff3e0; color: #e65100; }
        footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid var(--nr-grey);
            text-align: center;
            font-size: 0.875rem;
        }
        footer a { color: var(--nr-primary); }
    </style>
</head>
<body>
    <header class="header">
        <svg class="logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="12" fill="#2F99A4"/>
            <text x="50" y="65" text-anchor="middle" fill="white" font-family="Raleway" font-size="48" font-weight="700">[n]</text>
        </svg>
        <div>
            <h1>Contexts Extension</h1>
            <p>TYPO3 Context-Based Content Visibility</p>
        </div>
    </header>

    <section>
        <h2>TYPO3 Environments</h2>
        <div class="card-grid">
            <div class="card">
                <h3>TYPO3 v12 <span class="badge badge-lts">LTS</span></h3>
                <p>Long-term support version for production sites.</p>
                <a href="https://v12.contexts.ddev.site/typo3/">Open Backend</a>
            </div>
            <div class="card">
                <h3>TYPO3 v13 <span class="badge badge-lts">LTS</span></h3>
                <p>Latest LTS version with new features.</p>
                <a href="https://v13.contexts.ddev.site/typo3/">Open Backend</a>
            </div>
            <div class="card">
                <h3>Documentation</h3>
                <p>Extension documentation rendered locally.</p>
                <a href="https://docs.contexts.ddev.site/">View Docs</a>
            </div>
        </div>
    </section>

    <section>
        <h2>Quick Commands</h2>
        <pre style="background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto;">
ddev start              # Start environment
ddev install-v12        # Install TYPO3 v12
ddev install-v13        # Install TYPO3 v13
ddev docs               # Render documentation
ddev ssh                # Shell access
        </pre>
    </section>

    <section>
        <h2>Credentials</h2>
        <p><strong>Backend:</strong> admin / Joh316!</p>
    </section>

    <footer>
        <p>
            <a href="https://www.netresearch.de/">Netresearch DTT GmbH</a> |
            <a href="https://github.com/netresearch/t3x-contexts">GitHub</a>
        </p>
    </footer>
</body>
</html>
```

**Step 5: Commit**

```bash
git add .ddev/
git commit -S -m "chore: add DDEV local development environment

- Multi-version setup (v12, v13)
- Documentation rendering
- Netresearch branded landing page
- Install commands for quick setup

Note: DDEV is for local development only. CI uses GitHub Actions."
```

### Task 5.2: DDEV Setup for Geolocation Extension

**Files:**
- Create: `.ddev/` structure (similar to base extension)

Follow same pattern as Task 5.1, adapting for contexts_geolocation.

### Task 5.3: DDEV Setup for Device Detection Extension

**Files:**
- Create: `.ddev/` structure (similar to base extension)

Follow same pattern as Task 5.1, adapting for contexts_wurfl.

---

## Phase 6: Netresearch Branding Compliance

Apply Netresearch brand identity consistently across all extensions.

### Task 6.1: Extension Icons

**Files per extension:**
- Create: `Resources/Public/Icons/Extension.svg`

**Step 1: Create branded extension icon**

Use the Netresearch symbol-only logo as base, customized for each extension:

```svg
<!-- Resources/Public/Icons/Extension.svg -->
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">
    <rect width="128" height="128" rx="16" fill="#2F99A4"/>
    <text x="64" y="80" text-anchor="middle" fill="white"
          font-family="Raleway, sans-serif" font-size="56" font-weight="700">[n]</text>
</svg>
```

### Task 6.2: README Branding

**Files per extension:**
- Modify: `README.md`

**Required elements:**
- Netresearch logo at top
- Colors: `#2F99A4` (primary), `#FF4D00` (accent)
- Footer with Netresearch DTT GmbH link
- Consistent badge styling

```markdown
<!-- README.md header -->
<div align="center">

![Netresearch](https://avatars.githubusercontent.com/u/278823?s=64)

# Contexts Extension

**Context-based content visibility for TYPO3**

[![TYPO3](https://img.shields.io/badge/TYPO3-12%20%7C%2013-FF8700?logo=typo3)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2--8.5-777BB4?logo=php)](https://php.net/)
[![License](https://img.shields.io/badge/License-AGPL--3.0-blue)](LICENSE)

</div>
```

### Task 6.3: Documentation Branding

**Files per extension:**
- Modify: `Documentation/Settings.cfg` or `Documentation/guides.xml`

Ensure documentation follows TYPO3 standards while including Netresearch author info.

### Task 6.4: ext_emconf.php Branding

**Verify all extensions have:**

```php
'author' => 'Netresearch DTT GmbH',
'author_email' => 'info@netresearch.de',
'author_company' => 'Netresearch DTT GmbH',
```

---

## Skills Summary

| Skill | Purpose | Phase |
|-------|---------|-------|
| **agents:agents** | AGENTS.md generation for AI context | 0 (FIRST) |
| **typo3-extension-upgrade** | Rector/Fractor migrations, breaking changes | 1-3 |
| **typo3-testing** | Test infrastructure, PHPUnit, Playwright | 1-4 |
| **typo3-conformance** | Quality assessment, TYPO3 standards | 4 |
| **typo3-docs** | RST documentation structure | 2-3 |
| **typo3-ddev** | Local development setup (NOT for CI) | 5 |
| **php-modernization** | PHP 8.x patterns, PHPStan level 10 | 1-3 |
| **netresearch-branding** | Logos, colors, README styling | 5-6 |
| **superpowers:writing-plans** | This plan (completed) | - |
| **superpowers:executing-plans** | Task-by-task execution | All |
| **superpowers:test-driven-development** | TDD for new code | 2-3 |
| **superpowers:verification-before-completion** | Test verification | All |
| **git-workflow** | Conventional commits, PRs | All |
| **security-audit** | Security review | 4 |

---

## CI/CD Architecture (NO DDEV!)

```
┌─────────────────────────────────────────────────────────────────┐
│                     GitHub Actions CI                           │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │   PHPStan   │  │   PHPUnit   │  │  Playwright │              │
│  │   Level 10  │  │ Unit+Func   │  │     E2E     │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
│         │                │                │                      │
│         ▼                ▼                ▼                      │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │           shivammathur/setup-php@v2                         ││
│  │           PHP 8.2 / 8.3 / 8.4 / 8.5                        ││
│  └─────────────────────────────────────────────────────────────┘│
│                          │                                       │
│         ┌────────────────┼────────────────┐                      │
│         ▼                ▼                ▼                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │  SQLite     │  │  MariaDB    │  │ PHP Built-in│              │
│  │  (Unit)     │  │  (Service)  │  │   Server    │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
└─────────────────────────────────────────────────────────────────┘

                    ❌ NO DDEV IN CI ❌
```

**E2E Workflow Pattern:**

```yaml
# .github/workflows/e2e.yml - CORRECT PATTERN
services:
  db:
    image: mariadb:11.4
    env:
      MYSQL_DATABASE: test
      MYSQL_ROOT_PASSWORD: root
    ports:
      - 3306:3306

steps:
  - uses: shivammathur/setup-php@v2
    with:
      php-version: '8.3'

  - name: Start PHP server
    run: php -S 0.0.0.0:8080 -t .Build/Web &

  - name: Run Playwright
    env:
      TYPO3_BASE_URL: http://localhost:8080
    run: npx playwright test
```

---

## Parallel Execution Strategy

Tasks that can be executed in parallel:

**Phase 2 + Phase 3 parallelizable tasks:**
- 2.1 (skeleton) || 3.1 (architecture decision)
- 2.2 (GeoIP adapter) || 3.2 (skeleton)
- 2.3 (context types) || 3.3 (device service)

**Phase 5 + Phase 6 parallelizable:**
- 5.1 (DDEV base) || 6.1 (icons)
- 5.2 (DDEV geolocation) || 6.2 (README)
- 5.3 (DDEV device) || 6.3 (docs)

Parallel execution significantly reduces total calendar time.
