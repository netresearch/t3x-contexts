<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Service;

use Netresearch\Contexts\Service\InstallService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for InstallService.
 *
 * InstallService appends SQL table definitions for the flat settings columns
 * (tx_contexts_enable, tx_contexts_disable) that context-enabled tables require.
 * It is invoked as a hook during extension installation/update.
 */
final class InstallServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal TCA baseline required by Configuration::getExtensionFlatSettings
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
        );
        parent::tearDown();
    }

    // ========================================
    // appendTableDefinitions() — no matching extension key
    // ========================================

    #[Test]
    public function appendTableDefinitionsReturnsEmptyArrayWhenExtKeyNotRegistered(): void
    {
        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'nonexistent_ext');

        self::assertSame([], $result);
    }

    #[Test]
    public function appendTableDefinitionsReturnsEmptyArrayWhenExtensionFlatSettingsIsEmpty(): void
    {
        // extensionFlatSettings exists but the key is absent
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'any_ext');

        self::assertSame([], $result);
    }

    #[Test]
    public function appendTableDefinitionsReturnsEmptyArrayWhenExtKeyMissingFromFlatSettings(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'other_ext' => ['some_table' => ['tx_contexts']],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'missing_ext');

        self::assertSame([], $result);
    }

    // ========================================
    // appendTableDefinitions() — matching extension key with flat columns
    // ========================================

    #[Test]
    public function appendTableDefinitionsReturnsSqlStringAndExtensionKeyForRegisteredExtension(): void
    {
        // Register flat settings via TCA globals so Configuration::getExtensionFlatSettings
        // and Configuration::getFlatColumns both return the correct data.
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['tx_contexts'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [
                0 => 'tx_contexts_disable',
                1 => 'tx_contexts_enable',
            ],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        self::assertArrayHasKey('sqlString', $result);
        self::assertArrayHasKey('extensionKey', $result);
        self::assertSame('my_ext', $result['extensionKey']);
        self::assertIsArray($result['sqlString']);
    }

    #[Test]
    public function appendTableDefinitionsSqlContainsCreateTableStatement(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['tx_contexts'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [
                0 => 'tx_contexts_disable',
                1 => 'tx_contexts_enable',
            ],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        $sql = $result['sqlString'][0];
        self::assertStringContainsString('CREATE TABLE test_table', $sql);
    }

    #[Test]
    public function appendTableDefinitionsSqlContainsEnableAndDisableColumns(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['tx_contexts'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [
                0 => 'tx_contexts_disable',
                1 => 'tx_contexts_enable',
            ],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        $sql = $result['sqlString'][0];
        self::assertStringContainsString('tx_contexts_disable tinytext', $sql);
        self::assertStringContainsString('tx_contexts_enable tinytext', $sql);
    }

    #[Test]
    public function appendTableDefinitionsSqlEndsWithSemicolon(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['tx_contexts'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [
                0 => 'tx_contexts_disable',
                1 => 'tx_contexts_enable',
            ],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        $sql = $result['sqlString'][0];
        self::assertStringEndsWith(');', $sql);
    }

    // ========================================
    // appendTableDefinitions() — multiple settings per table
    // ========================================

    #[Test]
    public function appendTableDefinitionsSqlContainsColumnsForAllFlatSettings(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['tx_contexts', 'custom_visibility'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [
                0 => 'tx_contexts_disable',
                1 => 'tx_contexts_enable',
            ],
            'custom_visibility' => [
                0 => 'custom_visibility_disable',
                1 => 'custom_visibility_enable',
            ],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        $sql = $result['sqlString'][0];
        self::assertStringContainsString('tx_contexts_disable tinytext', $sql);
        self::assertStringContainsString('tx_contexts_enable tinytext', $sql);
        self::assertStringContainsString('custom_visibility_disable tinytext', $sql);
        self::assertStringContainsString('custom_visibility_enable tinytext', $sql);
    }

    // ========================================
    // appendTableDefinitions() — multiple tables for one extension
    // ========================================

    #[Test]
    public function appendTableDefinitionsSqlContainsCreateTableForEachTable(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'table_a' => ['tx_contexts'],
                'table_b' => ['tx_contexts'],
            ],
        ];
        $GLOBALS['TCA']['table_a']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [0 => 'tx_contexts_disable', 1 => 'tx_contexts_enable'],
        ];
        $GLOBALS['TCA']['table_b']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [0 => 'tx_contexts_disable', 1 => 'tx_contexts_enable'],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        $sql = $result['sqlString'][0];
        self::assertStringContainsString('CREATE TABLE table_a', $sql);
        self::assertStringContainsString('CREATE TABLE table_b', $sql);
    }

    // ========================================
    // appendTableDefinitions() — array settings are skipped
    // ========================================

    #[Test]
    public function appendTableDefinitionsSkipsArraySettingsInFlatSettingsList(): void
    {
        // When a setting value in the list is itself an array, it should be skipped.
        // This happens when Configuration::addToExtensionFlatSettings merges sub-arrays.
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => [
                    'tx_contexts',                 // string: normal setting — processed
                    ['nested' => 'array_value'],   // array: must be skipped
                ],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [0 => 'tx_contexts_disable', 1 => 'tx_contexts_enable'],
        ];

        $service = new InstallService();

        // Should not throw; array entries are silently skipped
        $result = $service->appendTableDefinitions([], 'my_ext');

        self::assertArrayHasKey('sqlString', $result);
        $sql = $result['sqlString'][0];
        self::assertStringContainsString('tx_contexts_disable tinytext', $sql);
    }

    // ========================================
    // appendTableDefinitions() — $arSignalReturn passthrough
    // ========================================

    #[Test]
    public function appendTableDefinitionsIgnoresSignalReturnArrayWhenNoMatch(): void
    {
        $service = new InstallService();

        $signalReturn = ['some' => 'existing', 'data' => 42];
        $result = $service->appendTableDefinitions($signalReturn, 'missing_ext');

        self::assertSame([], $result);
    }

    #[Test]
    public function appendTableDefinitionsDoesNotMergeSignalReturnIntoResult(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['tx_contexts'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => [0 => 'tx_contexts_disable', 1 => 'tx_contexts_enable'],
        ];

        $service = new InstallService();

        // The signal return is passed in but the method builds its own return value
        $signalReturn = ['original' => 'data'];
        $result = $service->appendTableDefinitions($signalReturn, 'my_ext');

        self::assertArrayNotHasKey('original', $result);
        self::assertArrayHasKey('sqlString', $result);
        self::assertArrayHasKey('extensionKey', $result);
    }

    // ========================================
    // appendTableDefinitions() — column name format verification
    // ========================================

    #[Test]
    public function appendTableDefinitionsColumnNamesUseDisableEnableSuffix(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'my_ext' => [
                'test_table' => ['my_setting'],
            ],
        ];
        $GLOBALS['TCA']['test_table']['ctrl']['tx_contexts']['flatSettings'] = [
            'my_setting' => [
                0 => 'my_setting_disable',
                1 => 'my_setting_enable',
            ],
        ];

        $service = new InstallService();

        $result = $service->appendTableDefinitions([], 'my_ext');

        $sql = $result['sqlString'][0];
        // Convention: {setting}_disable and {setting}_enable, each typed as tinytext
        self::assertStringContainsString('my_setting_disable tinytext', $sql);
        self::assertStringContainsString('my_setting_enable tinytext', $sql);
    }
}
