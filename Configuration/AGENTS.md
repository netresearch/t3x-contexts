<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md - Configuration/

TYPO3 configuration files for the Contexts extension (TCA, FlexForms, Services, TypoScript).

## Overview

```
Configuration/
├── TCA/                  # Table Configuration Array
│   ├── tx_contexts_contexts.php     # Main context records table
│   └── Overrides/                   # TCA overrides for core tables
│       ├── pages.php                # Context visibility for pages
│       ├── tt_content.php           # Context visibility for content
│       └── tx_contexts_contexts.php # Context type registration
├── FlexForms/            # Dynamic form configurations
│   └── ContextType/      # Per-type configuration forms
│       ├── GetParam.xml  # GET parameter context
│       ├── Domain.xml    # Domain context
│       ├── Ip.xml        # IP address context
│       ├── HttpHeader.xml # HTTP header context
│       ├── Session.xml   # Session context
│       ├── Combination.xml # Combination context
│       └── Empty.xml     # Empty/default context
├── Sets/Contexts/        # TYPO3 v13+ Site Sets
│   ├── config.yaml       # Set metadata
│   ├── settings.yaml     # Configurable settings
│   └── setup.typoscript  # TypoScript setup
├── Services.yaml         # Symfony DI configuration
├── Icons.php             # Icon registry
├── RequestMiddlewares.php # PSR-15 middleware config
└── ExpressionLanguage.php # Symfony ExpressionLanguage providers
```

## Build & Tests

```bash
# Validate TCA
ddev exec vendor/bin/typo3 cache:flush

# Test FlexForm parsing
ddev exec vendor/bin/typo3 cache:warmup
```

## Code Style & Conventions

### TCA Patterns

```php
// Use modern TCA (TYPO3 12+)
return [
    'ctrl' => [
        // TYPO3 v13: Use security array instead of ExtensionManagementUtility
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'type' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
            ],
            'onChange' => 'reload',  // Reload form when type changes
        ],
        'type_conf' => [
            'displayCond' => 'FIELD:type:REQ:true',  // Show only when type selected
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'type',  // FlexForm source depends on type
                'ds' => [],  // Populated dynamically
            ],
        ],
    ],
];
```

### FlexForm Patterns

```xml
<?xml version="1.0" encoding="utf-8"?>
<T3DataStructure>
    <meta>
        <langDisable>1</langDisable>
    </meta>
    <sheets>
        <sDEF>
            <ROOT>
                <type>array</type>
                <el>
                    <field_name>
                        <label>LLL:EXT:contexts/Resources/Private/Language/flexform.xlf:key</label>
                        <config>
                            <type>input</type>
                            <size>30</size>
                            <eval>trim</eval>
                        </config>
                    </field_name>
                </el>
            </ROOT>
        </sDEF>
    </sheets>
</T3DataStructure>
```

### Registering Context Types

New context types must be registered in `TCA/Overrides/tx_contexts_contexts.php`:

```php
// Register context type in select dropdown
$GLOBALS['TCA']['tx_contexts_contexts']['columns']['type']['config']['items'][] = [
    'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.type.ip',
    'value' => Netresearch\Contexts\Context\Type\IpContext::class,
];

// Register FlexForm data source for type configuration
$GLOBALS['TCA']['tx_contexts_contexts']['columns']['type_conf']['config']['ds'][
    Netresearch\Contexts\Context\Type\IpContext::class
] = 'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Ip.xml';
```

### Services.yaml (Dependency Injection)

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    Netresearch\Contexts\:
        resource: '../Classes/*'
        exclude:
            - '../Classes/Domain/Model/*'

    # PSR-14 event listeners auto-configured via #[AsEventListener] attributes
    # Public services for TYPO3's service locator
    Netresearch\Contexts\Service\ContextService:
        public: true
```

### Site Sets (TYPO3 v13+)

```yaml
# Configuration/Sets/Contexts/config.yaml
name: contexts
label: Contexts - Multi-channel content visibility
dependencies:
  - typo3/fluid-styled-content

# Configuration/Sets/Contexts/settings.yaml
settings:
  contexts:
    enableCache: true
```

## Security & Safety

- Never expose database credentials in TCA/FlexForms
- Use LLL: references for all labels (no hardcoded strings)
- Validate user input via TCA eval rules

## PR/Commit Checklist

- [ ] TCA uses modern syntax (TYPO3 12+ compatible)
- [ ] FlexForms have language file references
- [ ] New context types registered in TCA/Overrides
- [ ] Services.yaml changes tested with `cache:flush`
- [ ] No deprecated TCA features (itemsProcFunc, etc.)

## Good vs Bad Examples

### Context Type Registration

```php
// Good: Use FQCN as type identifier
'value' => Netresearch\Contexts\Context\Type\IpContext::class,

// Bad: Use magic string
'value' => 'ip',
```

### FlexForm Labels

```xml
<!-- Good: Language file reference -->
<label>LLL:EXT:contexts/Resources/Private/Language/flexform.xlf:field.label</label>

<!-- Bad: Hardcoded string -->
<label>My Field Label</label>
```

### TCA Security

```php
// Good: TYPO3 v13+ security array
'security' => [
    'ignorePageTypeRestriction' => true,
],

// Bad: Deprecated method call
ExtensionManagementUtility::allowTableOnStandardPages('tx_contexts_contexts');
```

## When Stuck

- TCA Reference: https://docs.typo3.org/m/typo3/reference-tca/main/en-us/
- FlexForms: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/FlexForms/
- Site Sets: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SiteHandling/SiteSets.html

## House Rules

- All new context types need both TCA registration and FlexForm
- Keep FlexForm fields minimal - complex logic belongs in PHP
- Use `onChange => 'reload'` for type selectors
- Test configuration changes in both v12 and v13
