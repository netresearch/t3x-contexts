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

use Doctrine\DBAL\Result;
use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Service\DataHandlerService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Tests for DataHandlerService.
 *
 * DataHandlerService handles TYPO3 DataHandler hooks for persisting
 * context settings to the settings table and flat columns.
 *
 * Public methods under test:
 * - processDatamap_preProcessFieldArray()
 * - processDatamap_afterDatabaseOperations()
 */
final class DataHandlerServiceTest extends TestCase
{
    private ConnectionPool&MockObject $connectionPool;

    private DataHandler&MockObject $dataHandler;

    private DataHandlerService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->dataHandler = $this->createMock(DataHandler::class);
        $this->subject = new DataHandlerService($this->connectionPool);

        // Initialize minimal TCA structure required by Configuration::getFlatColumns
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
            $GLOBALS['TCA']['pages'],
            $GLOBALS['TCA']['tt_content'],
            $GLOBALS['TCA']['some_table'],
        );
        parent::tearDown();
    }

    // =========================================================================
    // Data providers
    // =========================================================================

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function settingValueProvider(): array
    {
        return [
            'enabled setting (1) inserts record' => ['pages', '1', ['enabled' => 1]],
            'disabled setting (0) inserts record' => ['pages', '0', ['enabled' => 0]],
        ];
    }

    // =========================================================================
    // processDatamap_preProcessFieldArray() tests
    // =========================================================================

    #[Test]
    public function preProcessFieldArrayExtractsDefaultSettingsForContextsTable(): void
    {
        // Arrange
        $defaultSettings = ['pages' => ['tx_contexts' => '1'], 'tt_content' => ['tx_contexts' => '0']];
        $incomingFieldArray = [
            'title' => 'My Context',
            'default_settings' => $defaultSettings,
        ];

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '42',
            $this->dataHandler,
        );

        // Assert: default_settings removed from field array
        self::assertArrayNotHasKey('default_settings', $incomingFieldArray);
        // Other fields remain intact
        self::assertSame('My Context', $incomingFieldArray['title']);
    }

    #[Test]
    public function preProcessFieldArrayDoesNotModifyOtherFieldsForContextsTable(): void
    {
        // Arrange
        $incomingFieldArray = [
            'title' => 'Test Context',
            'description' => 'A description',
            'default_settings' => ['pages' => ['tx_contexts' => '1']],
        ];

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '1',
            $this->dataHandler,
        );

        // Assert
        self::assertSame('Test Context', $incomingFieldArray['title']);
        self::assertSame('A description', $incomingFieldArray['description']);
    }

    #[Test]
    public function preProcessFieldArrayIgnoresContextsTableWhenDefaultSettingsNotSet(): void
    {
        // Arrange: no default_settings key in the array
        $incomingFieldArray = [
            'title' => 'My Context',
        ];
        $originalArray = $incomingFieldArray;

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '42',
            $this->dataHandler,
        );

        // Assert: array is unchanged
        self::assertSame($originalArray, $incomingFieldArray);
    }

    #[Test]
    public function preProcessFieldArrayIgnoresContextsTableWhenDefaultSettingsIsNotArray(): void
    {
        // Arrange: default_settings is a string, not an array
        $incomingFieldArray = [
            'title' => 'My Context',
            'default_settings' => 'not-an-array',
        ];
        $originalArray = $incomingFieldArray;

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '42',
            $this->dataHandler,
        );

        // Assert: array is unchanged (string default_settings is not extracted)
        self::assertSame($originalArray, $incomingFieldArray);
    }

    #[Test]
    public function preProcessFieldArrayExtractsRecordSettingsColumnForNonContextsTable(): void
    {
        // Arrange
        $recordSettings = [1 => ['tx_contexts' => '1'], 2 => ['tx_contexts' => '0']];
        $incomingFieldArray = [
            'title' => 'A Page',
            Configuration::RECORD_SETTINGS_COLUMN => $recordSettings,
        ];

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '99',
            $this->dataHandler,
        );

        // Assert: record settings column removed
        self::assertArrayNotHasKey(Configuration::RECORD_SETTINGS_COLUMN, $incomingFieldArray);
        self::assertSame('A Page', $incomingFieldArray['title']);
    }

    #[Test]
    public function preProcessFieldArrayDoesNotModifyArrayWhenRecordSettingsColumnAbsent(): void
    {
        // Arrange: no RECORD_SETTINGS_COLUMN in field array
        $incomingFieldArray = [
            'title' => 'A Page',
            'hidden' => 0,
        ];
        $originalArray = $incomingFieldArray;

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '5',
            $this->dataHandler,
        );

        // Assert: unchanged
        self::assertSame($originalArray, $incomingFieldArray);
    }

    #[Test]
    public function preProcessFieldArrayDoesNotExtractRecordSettingsWhenOnContextsTableWithDefaultSettings(): void
    {
        // Arrange: contexts table with both default_settings and record settings column
        // The contexts table branch should return early, leaving RECORD_SETTINGS_COLUMN intact
        $incomingFieldArray = [
            'default_settings' => ['pages' => ['tx_contexts' => '1']],
            Configuration::RECORD_SETTINGS_COLUMN => [1 => ['tx_contexts' => '1']],
        ];

        // Act
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '1',
            $this->dataHandler,
        );

        // Assert: early return means RECORD_SETTINGS_COLUMN is NOT processed
        self::assertArrayNotHasKey('default_settings', $incomingFieldArray);
        self::assertArrayHasKey(Configuration::RECORD_SETTINGS_COLUMN, $incomingFieldArray);
    }

    // =========================================================================
    // processDatamap_afterDatabaseOperations() – no currentSettings
    // =========================================================================

    #[Test]
    public function afterDatabaseOperationsDoesNothingWhenNoCurrentSettings(): void
    {
        // Arrange: no currentSettings have been captured
        $this->connectionPool->expects(self::never())->method('getQueryBuilderForTable');
        $this->connectionPool->expects(self::never())->method('getConnectionForTable');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '1',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsDoesNothingWhenCurrentSettingsIsEmpty(): void
    {
        // Arrange: call pre-process with an empty array to set empty currentSettings
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => []];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '1',
            $this->dataHandler,
        );

        $this->connectionPool->expects(self::never())->method('getQueryBuilderForTable');
        $this->connectionPool->expects(self::never())->method('getConnectionForTable');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '1',
            [],
            $this->dataHandler,
        );
    }

    // =========================================================================
    // processDatamap_afterDatabaseOperations() – contexts table (saveDefaultSettings)
    // =========================================================================

    #[Test]
    public function afterDatabaseOperationsSavesDefaultSettingsForContextsTable(): void
    {
        // Arrange: capture default settings via pre-process
        $defaultSettings = [
            'pages' => ['tx_contexts' => '1'],
        ];
        $incomingFieldArray = ['default_settings' => $defaultSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '5',
            $this->dataHandler,
        );

        // Set up mocks for saveDefaultSettings
        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult([]);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool
            ->expects(self::once())
            ->method('getQueryBuilderForTable')
            ->with('tx_contexts_settings')
            ->willReturn($queryBuilder);

        $this->connectionPool
            ->expects(self::once())
            ->method('getConnectionForTable')
            ->with('tx_contexts_settings')
            ->willReturn($connection);

        // No existing settings: expect insert
        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_contexts_settings',
                self::callback(static fn(array $data): bool => $data['context_uid'] === 5
                        && $data['foreign_table'] === 'pages'
                        && $data['name'] === 'tx_contexts'
                        && $data['foreign_uid'] === 0
                        && $data['enabled'] === 1),
            );

        $connection->expects(self::never())->method('update');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            'tx_contexts_contexts',
            '5',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsUpdatesExistingDefaultSettings(): void
    {
        // Arrange: capture default settings
        $defaultSettings = ['pages' => ['tx_contexts' => '0']];
        $incomingFieldArray = ['default_settings' => $defaultSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '3',
            $this->dataHandler,
        );

        // Existing setting found for this field
        $existingSettings = [
            ['uid' => 77, 'foreign_table' => 'pages', 'name' => 'tx_contexts', 'enabled' => 1],
        ];
        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult($existingSettings);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool
            ->expects(self::once())
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $this->connectionPool
            ->expects(self::once())
            ->method('getConnectionForTable')
            ->willReturn($connection);

        // Should update, not insert
        $connection->expects(self::once())
            ->method('update')
            ->with(
                'tx_contexts_settings',
                ['enabled' => 0],
                ['uid' => 77],
            );

        $connection->expects(self::never())->method('insert');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'tx_contexts_contexts',
            '3',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsResolvesNewIdForContextsTable(): void
    {
        // Arrange: capture default settings with a NEW-id
        $defaultSettings = ['pages' => ['tx_contexts' => '1']];
        $incomingFieldArray = ['default_settings' => $defaultSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            'NEW123',
            $this->dataHandler,
        );

        // DataHandler resolves the NEW id to a real integer id
        $this->dataHandler->substNEWwithIDs = ['NEW123' => 42];

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult([]);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_contexts_settings',
                self::callback(
                    // Should use resolved id (42), not the string 'NEW123'
                    static fn(array $data): bool => $data['context_uid'] === 42,
                ),
            );

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            'tx_contexts_contexts',
            'NEW123',
            [],
            $this->dataHandler,
        );
    }

    // =========================================================================
    // processDatamap_afterDatabaseOperations() – non-contexts table
    // =========================================================================

    #[Test]
    public function afterDatabaseOperationsSavesRecordSettingsAndFlatSettingsForOtherTables(): void
    {
        // Arrange: table without flat columns configured
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [1 => ['some_field' => '1']];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '10',
            $this->dataHandler,
        );

        // For saveRecordSettings: query for existing setting (returns false)
        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResultFalse();
        $connection = $this->createMock(Connection::class);

        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->with('tx_contexts_contexts')
            ->willReturn($queryBuilder);

        $this->connectionPool
            ->method('getConnectionForTable')
            ->willReturn($connection);

        // Setting value '1' triggers insert
        $connection->expects(self::once())
            ->method('insert')
            ->with('tx_contexts_settings');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '10',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsResolvesNewIdForNonContextsTable(): void
    {
        // Arrange: table without flat columns
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [1 => ['some_field' => '1']];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            'NEWabc',
            $this->dataHandler,
        );

        $this->dataHandler->substNEWwithIDs = ['NEWabc' => 77];

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResultFalse();
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_contexts_settings',
                self::callback(static fn(array $data): bool => $data['foreign_uid'] === 77),
            );

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            'pages',
            'NEWabc',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsClearsCurrentSettingsAfterSave(): void
    {
        // Arrange: capture settings once
        $defaultSettings = ['pages' => ['tx_contexts' => '1']];
        $incomingFieldArray = ['default_settings' => $defaultSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '1',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult([]);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        // DB should only be called once (from first afterDatabaseOperations invocation)
        $connection->expects(self::once())->method('insert');

        // Act: first call – processes currentSettings
        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            'tx_contexts_contexts',
            '1',
            [],
            $this->dataHandler,
        );

        // Act: second call – currentSettings already unset, so no additional DB calls.
        // The expects(self::once()) on insert above would fail if called again.
        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            'tx_contexts_contexts',
            '1',
            [],
            $this->dataHandler,
        );
    }

    // =========================================================================
    // saveRecordSettings() – tested indirectly via afterDatabaseOperations
    // =========================================================================

    #[Test]
    public function afterDatabaseOperationsUpdatesExistingRecordSetting(): void
    {
        // Arrange: existing record found (row exists)
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [5 => ['visible_field' => '0']]; // setting = 0 triggers update
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '20',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult([['uid' => 99]]);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        // Existing row found + setting 0 → update
        $connection->expects(self::once())
            ->method('update')
            ->with(
                'tx_contexts_settings',
                ['enabled' => 0],
                ['uid' => 99],
            );

        $connection->expects(self::never())->method('insert');
        $connection->expects(self::never())->method('delete');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '20',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsDeletesRecordSettingWhenValueIsNeitherZeroNorOne(): void
    {
        // Arrange: setting cast to int is not 0 or 1 (e.g. '2', 'n/a') and an existing
        // row is found → the elseif branch executes and deletes the row.
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [3 => ['some_field' => '2']]; // (int)'2' = 2, triggers delete branch
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '30',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult([['uid' => 55]]);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        // Existing row + non-0/non-1 setting → delete
        $connection->expects(self::once())
            ->method('delete')
            ->with(
                'tx_contexts_settings',
                ['uid' => 55],
            );

        $connection->expects(self::never())->method('insert');
        $connection->expects(self::never())->method('update');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '30',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsDoesNothingWhenSettingIsNeitherZeroNorOneAndNoExistingRecord(): void
    {
        // Arrange: non-0/non-1 setting and no existing row → elseif branch is false, nothing happens
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [3 => ['some_field' => '2']]; // would delete, but no row found
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '31',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResultFalse();
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        $connection->expects(self::never())->method('delete');
        $connection->expects(self::never())->method('insert');
        $connection->expects(self::never())->method('update');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '31',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsSkipsFlatSettingColumnsInRecordSettings(): void
    {
        // Arrange: table with flat column configured for 'tx_contexts'
        // Fields that are flat settings should be skipped in saveRecordSettings
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
        ];

        // 'tx_contexts' is a flat setting column – should be skipped in saveRecordSettings
        $recordSettings = [1 => ['tx_contexts' => '1', 'other_field' => '0']];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '40',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResultFalse();
        $connectionForSettings = $this->createMock(Connection::class);
        $connectionForTable = $this->createMock(Connection::class);

        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->with('tx_contexts_contexts')
            ->willReturn($queryBuilder);

        $this->connectionPool
            ->method('getConnectionForTable')
            ->willReturnCallback(static function (string $table) use ($connectionForSettings, $connectionForTable): Connection {
                if ($table === 'tx_contexts_settings') {
                    return $connectionForSettings;
                }

                return $connectionForTable;
            });

        // Only 'other_field' is not a flat setting, so one insert expected
        $connectionForSettings->expects(self::once())
            ->method('insert')
            ->with('tx_contexts_settings');

        // saveFlatSettings will call update on the table
        $connectionForTable->expects(self::once())
            ->method('update')
            ->with('pages');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '40',
            [],
            $this->dataHandler,
        );
    }

    // =========================================================================
    // saveFlatSettings() – tested indirectly via afterDatabaseOperations
    // =========================================================================

    #[Test]
    public function afterDatabaseOperationsSavesFlatSettingsWhenFlatColumnsConfigured(): void
    {
        // Arrange: table with flat columns for 'tx_contexts'
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
        ];

        // Context 1 sets tx_contexts to '1' (enabled), context 2 sets to '0' (disabled)
        $recordSettings = [
            1 => ['tx_contexts' => '1'],
            2 => ['tx_contexts' => '0'],
        ];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '50',
            $this->dataHandler,
        );

        // saveRecordSettings makes no DB calls (only flat settings)
        $queryBuilder = $this->createFullQueryBuilderMock();
        $connectionForSettings = $this->createMock(Connection::class);
        $connectionForTable = $this->createMock(Connection::class);

        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $this->connectionPool
            ->method('getConnectionForTable')
            ->willReturnCallback(static function (string $table) use ($connectionForSettings, $connectionForTable): Connection {
                if ($table === 'tx_contexts_settings') {
                    return $connectionForSettings;
                }

                return $connectionForTable;
            });

        // saveFlatSettings should call update with flat column values
        $connectionForTable->expects(self::once())
            ->method('update')
            ->with(
                'pages',
                self::callback(
                    // tx_contexts_enable should contain context 1 (set to '1')
                    // tx_contexts_disable should contain context 2 (set to '0')
                    static fn(array $values): bool => isset($values['tx_contexts_enable'])
                    && isset($values['tx_contexts_disable'])
                    && str_contains((string) $values['tx_contexts_enable'], '1')
                    && str_contains((string) $values['tx_contexts_disable'], '2'),
                ),
                ['uid' => 50],
            );

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '50',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsDoesNotUpdateTableWhenNoFlatColumnsConfigured(): void
    {
        // Arrange: no flat columns → saveFlatSettings produces no values
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [1 => ['some_field' => '1']];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '60',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResultFalse();
        $connectionForSettings = $this->createMock(Connection::class);

        // Only getConnectionForTable('tx_contexts_settings') is expected
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $this->connectionPool
            ->method('getConnectionForTable')
            ->with('tx_contexts_settings')
            ->willReturn($connectionForSettings);

        // No update on the 'pages' table itself
        $connectionForSettings->method('insert');

        // Act (no assertion on update, just verify no exception)
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '60',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsHandlesUndecidedFlatSettingValues(): void
    {
        // Arrange: flat setting with blank value '' → not added to either flat column
        $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings'] = [
            'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
        ];

        $recordSettings = [
            1 => ['tx_contexts' => ''],  // Undecided – should not appear in either column
        ];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'pages',
            '70',
            $this->dataHandler,
        );

        $queryBuilder = $this->createFullQueryBuilderMock();
        $connectionForTable = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool
            ->method('getConnectionForTable')
            ->willReturn($connectionForTable);

        $connectionForTable->expects(self::once())
            ->method('update')
            ->with(
                'pages',
                self::callback(
                    // Both flat columns should be empty strings
                    static fn(array $values): bool => $values['tx_contexts_disable'] === ''
                    && $values['tx_contexts_enable'] === '',
                ),
                ['uid' => 70],
            );

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'pages',
            '70',
            [],
            $this->dataHandler,
        );
    }

    // =========================================================================
    // saveDefaultSettings() – multiple fields and tables
    // =========================================================================

    #[Test]
    public function afterDatabaseOperationsSavesDefaultSettingsForMultipleTablesAndFields(): void
    {
        // Arrange: context with default settings for multiple tables
        $defaultSettings = [
            'pages' => ['tx_contexts' => '1', 'menu_visibility' => '0'],
            'tt_content' => ['tx_contexts' => '1'],
        ];
        $incomingFieldArray = ['default_settings' => $defaultSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '10',
            $this->dataHandler,
        );

        // No existing settings
        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult([]);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        // Three fields across two tables → three inserts
        $connection->expects(self::exactly(3))
            ->method('insert')
            ->with('tx_contexts_settings');

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'tx_contexts_contexts',
            '10',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    public function afterDatabaseOperationsMatchesExistingSettingsByTableAndName(): void
    {
        // Arrange: existing settings for pages.tx_contexts but not for tt_content.tx_contexts
        $defaultSettings = [
            'pages' => ['tx_contexts' => '0'],
            'tt_content' => ['tx_contexts' => '1'],
        ];
        $incomingFieldArray = ['default_settings' => $defaultSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tx_contexts_contexts',
            '7',
            $this->dataHandler,
        );

        // Existing setting for pages.tx_contexts
        $existingSettings = [
            ['uid' => 100, 'foreign_table' => 'pages', 'name' => 'tx_contexts', 'enabled' => 1],
        ];
        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResult($existingSettings);
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        // pages.tx_contexts: update existing
        // tt_content.tx_contexts: insert new
        $connection->expects(self::once())
            ->method('update')
            ->with(
                'tx_contexts_settings',
                ['enabled' => 0],
                ['uid' => 100],
            );

        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_contexts_settings',
                self::callback(static fn(array $data): bool => $data['foreign_table'] === 'tt_content'
                        && $data['name'] === 'tx_contexts'
                        && $data['enabled'] === 1),
            );

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'update',
            'tx_contexts_contexts',
            '7',
            [],
            $this->dataHandler,
        );
    }

    #[Test]
    #[DataProvider('settingValueProvider')]
    public function afterDatabaseOperationsInsertsRecordForEnabledOrDisabledSetting(
        string $table,
        string $settingValue,
        array $expectedData,
    ): void {
        // Arrange
        $GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['flatSettings'] = [];

        $recordSettings = [1 => ['some_field' => $settingValue]];
        $incomingFieldArray = [Configuration::RECORD_SETTINGS_COLUMN => $recordSettings];
        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            $table,
            '100',
            $this->dataHandler,
        );

        [$queryBuilder, $result] = $this->createQueryBuilderMockWithResultFalse();
        $connection = $this->createMock(Connection::class);

        $this->connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);

        $connection->expects(self::once())
            ->method('insert')
            ->with(
                'tx_contexts_settings',
                self::callback(static fn(array $data): bool => $data['enabled'] === $expectedData['enabled']),
            );

        // Act
        $this->subject->processDatamap_afterDatabaseOperations(
            'new',
            $table,
            '100',
            [],
            $this->dataHandler,
        );
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Creates a QueryBuilder mock that returns the given rows via fetchAllAssociative().
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: QueryBuilder&MockObject, 1: Result&MockObject}
     */
    private function createQueryBuilderMockWithResult(array $rows): array
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);
        $result->method('fetchAssociative')->willReturn($rows[0] ?? false);

        return [$this->createQueryBuilderMockReturning($result), $result];
    }

    /**
     * Creates a QueryBuilder mock whose fetchAssociative() returns false (no row found).
     *
     * @return array{0: QueryBuilder&MockObject, 1: Result&MockObject}
     */
    private function createQueryBuilderMockWithResultFalse(): array
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(false);
        $result->method('fetchAllAssociative')->willReturn([]);

        return [$this->createQueryBuilderMockReturning($result), $result];
    }

    /**
     * Builds a complete fluent QueryBuilder mock returning the given Result.
     */
    private function createQueryBuilderMockReturning(Result $result): QueryBuilder&MockObject
    {
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('1=1');
        $expressionBuilder->method('in')->willReturn('1=1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('createNamedParameter')->willReturnCallback(
            static fn(mixed $value): string => (string) $value,
        );
        $queryBuilder->method('executeQuery')->willReturn($result);

        return $queryBuilder;
    }

    /**
     * Builds a fluent QueryBuilder mock for use when no DB result is checked
     * (e.g., when only flat settings are exercised and record settings are
     * entirely flat columns that get skipped).
     */
    private function createFullQueryBuilderMock(): QueryBuilder&MockObject
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn(false);
        $result->method('fetchAllAssociative')->willReturn([]);

        return $this->createQueryBuilderMockReturning($result);
    }
}
