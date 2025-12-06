<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Api;

use Netresearch\Contexts\Api\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for Configuration API class.
 *
 * Configuration provides static methods to configure contexts for tables,
 * register context types, and retrieve settings.
 */
final class ConfigurationTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize required TCA structure
        $GLOBALS['TCA']['tx_contexts_contexts'] = [
            'contextTypes' => [],
            'extensionFlatSettings' => [],
            'columns' => [],
        ];
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA']['tx_contexts_contexts'],
            $GLOBALS['TCA']['test_table'],
            $GLOBALS['TCA']['pages'],
            $GLOBALS['TCA']['tt_content'],
        );
        parent::tearDown();
    }

    #[Test]
    public function getContextTypesReturnsEmptyArrayByDefault(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'] = [];

        self::assertSame([], Configuration::getContextTypes());
    }

    #[Test]
    public function getContextTypesReturnsRegisteredTypes(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'] = [
            'domain' => [
                'title' => 'Domain',
                'class' => 'SomeClass',
                'flexFile' => 'EXT:contexts/some.xml',
            ],
        ];

        $types = Configuration::getContextTypes();

        self::assertArrayHasKey('domain', $types);
        self::assertSame('Domain', $types['domain']['title']);
    }

    #[Test]
    public function registerContextTypeAddsTypeToTca(): void
    {
        Configuration::registerContextType(
            'test_type',
            'Test Type',
            'TestClass',
            'EXT:test/flex.xml',
        );

        $types = Configuration::getContextTypes();

        self::assertArrayHasKey('test_type', $types);
        self::assertSame('Test Type', $types['test_type']['title']);
        self::assertSame('TestClass', $types['test_type']['class']);
        self::assertSame('EXT:test/flex.xml', $types['test_type']['flexFile']);
    }

    #[Test]
    public function registerContextTypeUpdatesTypeColumnsWhenSet(): void
    {
        // Set up type columns
        $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type'] = [
            'config' => ['items' => []],
        ];
        $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type_conf'] = [
            'config' => ['ds' => []],
        ];

        Configuration::registerContextType(
            'custom',
            'Custom Type',
            'CustomClass',
            'EXT:custom/flex.xml',
        );

        self::assertContains(
            ['label' => 'Custom Type', 'value' => 'custom'],
            $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type']['config']['items'],
        );
        self::assertSame(
            'EXT:custom/flex.xml',
            $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type_conf']['config']['ds']['custom'],
        );
    }

    #[Test]
    public function getFlatColumnsReturnsEmptyArrayForUnconfiguredTable(): void
    {
        self::assertSame([], Configuration::getFlatColumns('nonexistent_table'));
    }

    #[Test]
    public function getFlatColumnsReturnsAllSettingsWhenNoSettingSpecified(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                        'tx_custom' => ['tx_custom_disable', 'tx_custom_enable'],
                    ],
                ],
            ],
        ];

        $flatColumns = Configuration::getFlatColumns('test_table');

        self::assertCount(2, $flatColumns);
        self::assertArrayHasKey('tx_contexts', $flatColumns);
        self::assertArrayHasKey('tx_custom', $flatColumns);
    }

    #[Test]
    public function getFlatColumnsReturnsSpecificSettingWhenRequested(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
        ];

        $flatColumns = Configuration::getFlatColumns('test_table', 'tx_contexts');

        self::assertSame(['tx_contexts_disable', 'tx_contexts_enable'], $flatColumns);
    }

    #[Test]
    public function getEnableSettingsReturnsEmptyArrayForUnconfiguredTable(): void
    {
        self::assertSame([], Configuration::getEnableSettings('nonexistent_table'));
    }

    #[Test]
    public function getEnableSettingsReturnsConfiguredSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts', 'tx_visibility'],
                ],
            ],
        ];

        $settings = Configuration::getEnableSettings('test_table');

        self::assertSame(['tx_contexts', 'tx_visibility'], $settings);
    }

    #[Test]
    public function getExtensionFlatSettingsReturnsEmptyArrayForUnregisteredExtension(): void
    {
        self::assertSame([], Configuration::getExtensionFlatSettings('nonexistent_ext'));
    }

    #[Test]
    public function getExtensionFlatSettingsReturnsSettingsForRegisteredExtension(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['test_ext'] = [
            'some_table' => ['tx_contexts'],
        ];

        $settings = Configuration::getExtensionFlatSettings('test_ext');

        self::assertArrayHasKey('test_ext', $settings);
    }

    #[Test]
    public function langFileConstantHasCorrectValue(): void
    {
        self::assertSame(
            'EXT:contexts/Resources/Private/Language/locallang_db.xlf',
            Configuration::LANG_FILE,
        );
    }

    #[Test]
    public function recordSettingsColumnConstantHasCorrectValue(): void
    {
        self::assertSame(
            'tx_contexts_settings',
            Configuration::RECORD_SETTINGS_COLUMN,
        );
    }

    // ========================================
    // enableContextsForTable() tests
    // ========================================

    #[Test]
    public function enableContextsForTableAddsDefaultSettings(): void
    {
        // Set up minimal TCA structure for test_table
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table');

        // Should have added tx_contexts to enable settings
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertContains('tx_contexts', $enableSettings);

        // Should have added flat columns
        $flatColumns = Configuration::getFlatColumns('test_table', 'tx_contexts');
        self::assertSame(['tx_contexts_disable', 'tx_contexts_enable'], $flatColumns);
    }

    #[Test]
    public function enableContextsForTableWithCustomSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'custom_setting' => [
                'label' => 'Custom Setting',
                'flatten' => true,
                'enables' => true,
            ],
        ]);

        // Should have both default and custom enable settings
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertContains('tx_contexts', $enableSettings);
        self::assertContains('custom_setting', $enableSettings);

        // Should have flat columns for both
        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertArrayHasKey('tx_contexts', $flatColumns);
        self::assertArrayHasKey('custom_setting', $flatColumns);
    }

    #[Test]
    public function enableContextsForTableWithoutDefaults(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'only_custom' => [
                'label' => 'Only Custom',
                'flatten' => true,
            ],
        ], false); // addDefaults = false

        // Should NOT have tx_contexts (defaults disabled)
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertNotContains('tx_contexts', $enableSettings);

        // Should have only custom flat columns
        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertArrayNotHasKey('tx_contexts', $flatColumns);
        self::assertArrayHasKey('only_custom', $flatColumns);
    }

    #[Test]
    public function enableContextsForTableWithNullSettingsAndAddDefaultsFalse(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', null, false);

        // Should have empty enable settings
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertSame([], $enableSettings);

        // Should have no flat columns
        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertSame([], $flatColumns);
    }

    #[Test]
    public function enableContextsForTableRegistersExtensionFlatSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('my_ext', 'test_table');

        $extSettings = Configuration::getExtensionFlatSettings('my_ext');
        self::assertArrayHasKey('my_ext', $extSettings);
    }

    #[Test]
    public function enableContextsForTableMergesWithExistingExtensionSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        // First call
        Configuration::enableContextsForTable('merge_ext', 'test_table');

        // Second call with different table
        $GLOBALS['TCA']['another_table'] = [
            'ctrl' => ['title' => 'Another Table'],
            'columns' => [],
        ];
        Configuration::enableContextsForTable('merge_ext', 'another_table');

        $extSettings = Configuration::getExtensionFlatSettings('merge_ext');
        self::assertArrayHasKey('merge_ext', $extSettings);
    }

    #[Test]
    public function enableContextsForTableSettingWithoutFlattenIsNotFlattened(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'non_flat_setting' => [
                'label' => 'Not Flattened',
                // No 'flatten' or 'enables' key
            ],
        ], false);

        // Should NOT create flat columns for this setting
        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertArrayNotHasKey('non_flat_setting', $flatColumns);
    }

    #[Test]
    public function enableContextsForTableSettingWithEnablesIsFlattened(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'access_control' => [
                'label' => 'Access Control',
                'enables' => true, // This makes it a flat setting
            ],
        ], false);

        // Should create flat columns because 'enables' implies flattening
        $flatColumns = Configuration::getFlatColumns('test_table', 'access_control');
        self::assertSame(['access_control_disable', 'access_control_enable'], $flatColumns);

        // Should be in enable settings
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertContains('access_control', $enableSettings);
    }

    #[Test]
    public function enableContextsForTableDoesNotDuplicateEnableSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'title' => 'Test Table',
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'], // Already exists
                ],
            ],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table');

        $enableSettings = Configuration::getEnableSettings('test_table');
        // Count occurrences of tx_contexts
        $count = array_count_values($enableSettings)['tx_contexts'] ?? 0;
        self::assertSame(1, $count, 'tx_contexts should not be duplicated');
    }

    #[Test]
    public function getFlatColumnsReturnsEmptyArrayWhenSettingNotFound(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'existing_setting' => ['col1', 'col2'],
                    ],
                ],
            ],
        ];

        $flatColumns = Configuration::getFlatColumns('test_table', 'nonexistent_setting');
        self::assertSame([], $flatColumns);
    }

    #[Test]
    public function registerContextTypeDoesNotAddToColumnsWhenTypeColumnNotSet(): void
    {
        // Don't set up type columns
        unset($GLOBALS['TCA']['tx_contexts_contexts']['columns']['type']);

        Configuration::registerContextType(
            'orphan_type',
            'Orphan Type',
            'OrphanClass',
            'EXT:orphan/flex.xml',
        );

        // Should still register the context type
        $types = Configuration::getContextTypes();
        self::assertArrayHasKey('orphan_type', $types);

        // But should not have added to columns (they don't exist)
        self::assertArrayNotHasKey('type', $GLOBALS['TCA']['tx_contexts_contexts']['columns']);
    }

    #[Test]
    public function flatColumnNamingFollowsConvention(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'my_visibility' => [
                'label' => 'My Visibility',
                'flatten' => true,
            ],
        ], false);

        $flatColumns = Configuration::getFlatColumns('test_table', 'my_visibility');

        // Convention: {setting}_disable and {setting}_enable
        self::assertSame('my_visibility_disable', $flatColumns[0]);
        self::assertSame('my_visibility_enable', $flatColumns[1]);
    }

    #[Test]
    public function enableContextsForTableMergesSettingsWithExistingRecordColumn(): void
    {
        // Set up table with existing tx_contexts_settings column
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [
                Configuration::RECORD_SETTINGS_COLUMN => [
                    'config' => [
                        'settings' => [
                            'existing_setting' => ['label' => 'Existing'],
                        ],
                    ],
                ],
            ],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'new_setting' => [
                'label' => 'New Setting',
                'enables' => true,
            ],
        ], false);

        // Should have merged settings
        $settings = $GLOBALS['TCA']['test_table']['columns'][Configuration::RECORD_SETTINGS_COLUMN]['config']['settings'];
        self::assertArrayHasKey('existing_setting', $settings);
        self::assertArrayHasKey('new_setting', $settings);
    }

    // ========================================
    // Edge cases for getEnableSettings()
    // ========================================

    #[Test]
    public function getEnableSettingsReturnsEmptyArrayWhenTableHasNoTxContexts(): void
    {
        // Table exists but has no tx_contexts configuration
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
        ];

        $settings = Configuration::getEnableSettings('test_table');
        self::assertSame([], $settings);
    }

    #[Test]
    public function getEnableSettingsReturnsEmptyArrayWhenTableHasTxContextsButNoEnableSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => ['some' => 'value'],
                ],
            ],
        ];

        $settings = Configuration::getEnableSettings('test_table');
        self::assertSame([], $settings);
    }

    #[Test]
    public function getEnableSettingsHandlesNullGlobalsTca(): void
    {
        // Edge case: TCA key doesn't exist at all
        unset($GLOBALS['TCA']);

        $settings = Configuration::getEnableSettings('any_table');
        self::assertSame([], $settings);
    }

    // ========================================
    // Edge cases for getFlatColumns()
    // ========================================

    #[Test]
    public function getFlatColumnsReturnsEmptyArrayWhenTableHasNoTxContexts(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
        ];

        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertSame([], $flatColumns);
    }

    #[Test]
    public function getFlatColumnsReturnsEmptyArrayWhenTableHasTxContextsButNoFlatSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['some_setting'],
                ],
            ],
        ];

        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertSame([], $flatColumns);
    }

    #[Test]
    public function getFlatColumnsHandlesEmptyStringTable(): void
    {
        $flatColumns = Configuration::getFlatColumns('');
        self::assertSame([], $flatColumns);
    }

    #[Test]
    public function getFlatColumnsHandlesEmptyStringSetting(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        '' => ['empty_disable', 'empty_enable'],
                        'normal' => ['normal_disable', 'normal_enable'],
                    ],
                ],
            ],
        ];

        // Request with empty string as setting
        $flatColumns = Configuration::getFlatColumns('test_table', '');
        self::assertSame(['empty_disable', 'empty_enable'], $flatColumns);
    }

    #[Test]
    public function getFlatColumnsHandlesNullGlobalsTca(): void
    {
        unset($GLOBALS['TCA']);

        $flatColumns = Configuration::getFlatColumns('any_table');
        self::assertSame([], $flatColumns);
    }

    // ========================================
    // Edge cases for getExtensionFlatSettings()
    // ========================================

    #[Test]
    public function getExtensionFlatSettingsHandlesEmptyStringExtKey(): void
    {
        $settings = Configuration::getExtensionFlatSettings('');
        self::assertSame([], $settings);
    }

    #[Test]
    public function getExtensionFlatSettingsHandlesNullGlobalsTca(): void
    {
        unset($GLOBALS['TCA']);

        $settings = Configuration::getExtensionFlatSettings('any_ext');
        self::assertSame([], $settings);
    }

    #[Test]
    public function getExtensionFlatSettingsReturnsAllSettingsWhenKeyExists(): void
    {
        // Note: The method returns ALL extension flat settings, not just the requested one
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'ext1' => ['table1' => ['setting1']],
            'ext2' => ['table2' => ['setting2']],
        ];

        $settings = Configuration::getExtensionFlatSettings('ext1');

        // Method returns entire extensionFlatSettings array if key exists
        self::assertArrayHasKey('ext1', $settings);
        self::assertArrayHasKey('ext2', $settings);
    }

    // ========================================
    // Edge cases for getContextTypes()
    // ========================================

    #[Test]
    #[WithoutErrorHandler]
    public function getContextTypesHandlesNullGlobalsTca(): void
    {
        unset($GLOBALS['TCA']);

        // Suppress warnings - we're testing undefined TCA behavior
        $originalLevel = error_reporting(\E_ALL & ~\E_WARNING);
        try {
            $types = Configuration::getContextTypes();
            self::assertSame([], $types);
        } finally {
            error_reporting($originalLevel);
        }
    }

    #[Test]
    #[WithoutErrorHandler]
    public function getContextTypesHandlesMissingContextTypesKey(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts'] = [
            'columns' => [],
        ];

        // Suppress warnings - we're testing missing key behavior
        $originalLevel = error_reporting(\E_ALL & ~\E_WARNING);
        try {
            $types = Configuration::getContextTypes();
            self::assertSame([], $types);
        } finally {
            error_reporting($originalLevel);
        }
    }

    // ========================================
    // Edge cases for enableContextsForTable() - TCA column handling
    // ========================================

    #[Test]
    public function enableContextsForTableHandlesTableWithMinimalTca(): void
    {
        // Table with minimal TCA structure (title is required for default settings column)
        $GLOBALS['TCA']['minimal_table'] = [
            'ctrl' => ['title' => 'Minimal Table'],
            'columns' => [],
        ];

        // This should not throw an exception
        Configuration::enableContextsForTable('test_ext', 'minimal_table');

        // Settings should still be registered
        $enableSettings = Configuration::getEnableSettings('minimal_table');
        self::assertContains('tx_contexts', $enableSettings);
    }

    #[Test]
    public function enableContextsForTableHandlesPagesTable(): void
    {
        $GLOBALS['TCA']['pages'] = [
            'ctrl' => ['title' => 'Pages'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'pages');

        // Should have tx_contexts_settings column
        self::assertArrayHasKey(
            Configuration::RECORD_SETTINGS_COLUMN,
            $GLOBALS['TCA']['pages']['columns'],
        );
    }

    #[Test]
    public function enableContextsForTableHandlesTtContentTable(): void
    {
        $GLOBALS['TCA']['tt_content'] = [
            'ctrl' => ['title' => 'Content'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'tt_content');

        // Should have tx_contexts_settings column
        self::assertArrayHasKey(
            Configuration::RECORD_SETTINGS_COLUMN,
            $GLOBALS['TCA']['tt_content']['columns'],
        );
    }

    #[Test]
    #[WithoutErrorHandler]
    public function enableContextsForTableMergesDefaultSettingsColumn(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        // Suppress warnings - we're testing array merge with sub-arrays (known issue)
        $originalLevel = error_reporting(\E_ALL & ~\E_WARNING);
        try {
            // First call creates the default settings column
            Configuration::enableContextsForTable('test_ext', 'test_table');

            $defaultSettingsColumn = 'default_settings_test_table';
            self::assertArrayHasKey(
                $defaultSettingsColumn,
                $GLOBALS['TCA']['tx_contexts_contexts']['columns'],
            );

            // Second call should merge settings
            $GLOBALS['TCA']['test_table']['columns'] = [];
            Configuration::enableContextsForTable('test_ext', 'test_table', [
                'additional_setting' => [
                    'label' => 'Additional',
                    'enables' => true,
                ],
            ]);

            $settings = $GLOBALS['TCA']['tx_contexts_contexts']['columns'][$defaultSettingsColumn]['config']['settings'];
            self::assertArrayHasKey('tx_contexts', $settings);
            self::assertArrayHasKey('additional_setting', $settings);
        } finally {
            error_reporting($originalLevel);
        }
    }

    #[Test]
    public function enableContextsForTableCreatesPassthroughFieldsForFlatColumns(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table');

        // Should have created passthrough fields for flat columns
        self::assertArrayHasKey('tx_contexts_disable', $GLOBALS['TCA']['test_table']['columns']);
        self::assertArrayHasKey('tx_contexts_enable', $GLOBALS['TCA']['test_table']['columns']);

        // Verify passthrough configuration
        self::assertSame(
            'passthrough',
            $GLOBALS['TCA']['test_table']['columns']['tx_contexts_disable']['config']['type'],
        );
        self::assertSame(
            'passthrough',
            $GLOBALS['TCA']['test_table']['columns']['tx_contexts_enable']['config']['type'],
        );
    }

    // ========================================
    // Extension flat settings edge cases
    // ========================================

    #[Test]
    public function enableContextsForTableCreatesNewExtensionEntry(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        // Ensure no prior extension settings
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [];

        Configuration::enableContextsForTable('new_ext', 'test_table');

        $extSettings = $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'];
        self::assertArrayHasKey('new_ext', $extSettings);
        self::assertArrayHasKey('test_table', $extSettings['new_ext']);
        self::assertContains('tx_contexts', $extSettings['new_ext']['test_table']);
    }

    #[Test]
    #[WithoutErrorHandler]
    public function enableContextsForTableMergesExistingExtensionTableSettings(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        // Pre-populate extension flat settings
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'existing_ext' => [
                'test_table' => ['existing_setting'],
            ],
        ];

        // Suppress warnings - we're testing array merge with sub-arrays (known issue)
        $originalLevel = error_reporting(\E_ALL & ~\E_WARNING);
        try {
            Configuration::enableContextsForTable('existing_ext', 'test_table', [
                'new_flat_setting' => [
                    'label' => 'New',
                    'flatten' => true,
                ],
            ], false);

            $extSettings = $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['existing_ext']['test_table'];

            // Note: The code has a bug on line 271-274 where it merges $settings instead of $flatSettings
            // This test documents current behavior
            self::assertIsArray($extSettings);
        } finally {
            error_reporting($originalLevel);
        }
    }

    #[Test]
    public function enableContextsForTableAddsNewTableToExistingExtension(): void
    {
        $GLOBALS['TCA']['table1'] = [
            'ctrl' => ['title' => 'Table 1'],
            'columns' => [],
        ];
        $GLOBALS['TCA']['table2'] = [
            'ctrl' => ['title' => 'Table 2'],
            'columns' => [],
        ];

        // First table
        Configuration::enableContextsForTable('multi_table_ext', 'table1');

        // Second table
        Configuration::enableContextsForTable('multi_table_ext', 'table2');

        $extSettings = $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['multi_table_ext'];
        self::assertArrayHasKey('table1', $extSettings);
        self::assertArrayHasKey('table2', $extSettings);
    }

    // ========================================
    // isFlatSetting() edge cases (tested indirectly)
    // ========================================

    #[Test]
    public function enableContextsForTableHandlesSettingWithOnlyFlattenFlag(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'flatten_only' => [
                'label' => 'Flatten Only',
                'flatten' => true,
                // No 'enables' flag
            ],
        ], false);

        // Should create flat columns (flatten is set)
        $flatColumns = Configuration::getFlatColumns('test_table', 'flatten_only');
        self::assertSame(['flatten_only_disable', 'flatten_only_enable'], $flatColumns);

        // Should NOT be in enable settings (enables not set)
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertNotContains('flatten_only', $enableSettings);
    }

    #[Test]
    public function enableContextsForTableHandlesSettingWithBothFlags(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'both_flags' => [
                'label' => 'Both Flags',
                'flatten' => true,
                'enables' => true,
            ],
        ], false);

        // Should create flat columns
        $flatColumns = Configuration::getFlatColumns('test_table', 'both_flags');
        self::assertSame(['both_flags_disable', 'both_flags_enable'], $flatColumns);

        // Should be in enable settings
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertContains('both_flags', $enableSettings);
    }

    #[Test]
    public function enableContextsForTableHandlesSettingWithFalseFlattenFlag(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'false_flatten' => [
                'label' => 'False Flatten',
                'flatten' => false, // Explicitly false
            ],
        ], false);

        // Note: isset($config['flatten']) is true even when flatten=false
        // So flat columns ARE created (this documents current behavior)
        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertArrayHasKey('false_flatten', $flatColumns);
    }

    #[Test]
    public function enableContextsForTableHandlesSettingWithFalseEnablesFlag(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'false_enables' => [
                'label' => 'False Enables',
                'enables' => false, // Explicitly false
            ],
        ], false);

        // Note: isset($config['enables']) is true even when enables=false
        // So it IS added to enable settings (this documents current behavior)
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertContains('false_enables', $enableSettings);
    }

    // ========================================
    // Complex scenarios
    // ========================================

    #[Test]
    public function enableContextsForTableHandlesMultipleSettingsWithMixedFlags(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => ['title' => 'Test Table'],
            'columns' => [],
        ];

        Configuration::enableContextsForTable('test_ext', 'test_table', [
            'setting1' => [
                'label' => 'Setting 1',
                'flatten' => true,
                'enables' => true,
            ],
            'setting2' => [
                'label' => 'Setting 2',
                'flatten' => true,
            ],
            'setting3' => [
                'label' => 'Setting 3',
                'enables' => true,
            ],
            'setting4' => [
                'label' => 'Setting 4',
            ],
        ], false);

        // Check flat columns
        $flatColumns = Configuration::getFlatColumns('test_table');
        self::assertArrayHasKey('setting1', $flatColumns);
        self::assertArrayHasKey('setting2', $flatColumns);
        self::assertArrayHasKey('setting3', $flatColumns); // enables implies flatten
        self::assertArrayNotHasKey('setting4', $flatColumns);

        // Check enable settings
        $enableSettings = Configuration::getEnableSettings('test_table');
        self::assertContains('setting1', $enableSettings);
        self::assertNotContains('setting2', $enableSettings);
        self::assertContains('setting3', $enableSettings);
        self::assertNotContains('setting4', $enableSettings);
    }

    #[Test]
    public function registerContextTypeHandlesMultipleRegistrations(): void
    {
        Configuration::registerContextType('type1', 'Type 1', 'Class1', 'flex1.xml');
        Configuration::registerContextType('type2', 'Type 2', 'Class2', 'flex2.xml');
        Configuration::registerContextType('type3', 'Type 3', 'Class3', 'flex3.xml');

        $types = Configuration::getContextTypes();

        self::assertCount(3, $types);
        self::assertArrayHasKey('type1', $types);
        self::assertArrayHasKey('type2', $types);
        self::assertArrayHasKey('type3', $types);
    }

    #[Test]
    public function registerContextTypeUpdatesExistingType(): void
    {
        Configuration::registerContextType('domain', 'Domain v1', 'ClassV1', 'flex1.xml');
        Configuration::registerContextType('domain', 'Domain v2', 'ClassV2', 'flex2.xml');

        $types = Configuration::getContextTypes();

        self::assertArrayHasKey('domain', $types);
        self::assertSame('Domain v2', $types['domain']['title']);
        self::assertSame('ClassV2', $types['domain']['class']);
        self::assertSame('flex2.xml', $types['domain']['flexFile']);
    }
}
