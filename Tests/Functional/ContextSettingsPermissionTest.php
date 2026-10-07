<?php

/*
 * Copyright (c) 2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional;

use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Context visibility is stored by DataHandlerService, which takes the
 * settings out of the submitted record before DataHandler checks fields.
 * These tests run a real DataHandler with non-admin editors and check that
 * the settings follow the same rules as every other field: an exclude field
 * needs the editor's permission, and the settings belong to the record they
 * were submitted with. Context records themselves are admin-only (TCA
 * "adminOnly"), so their default settings are not reachable by editors.
 */
final class ContextSettingsPermissionTest extends FunctionalTestCase
{
    private const EDITOR_WITHOUT_CONTEXT_FIELDS = 2;

    private const EDITOR_WITH_CONTEXT_FIELDS = 3;

    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContextSettingsPermissions.csv');
    }

    protected function tearDown(): void
    {
        Container::reset();

        parent::tearDown();
    }

    #[Test]
    public function recordSettingsAreIgnoredWithoutPermissionForTheField(): void
    {
        $dataHandler = $this->runDataMap(self::EDITOR_WITHOUT_CONTEXT_FIELDS, [
            'pages' => [
                1 => [
                    'title' => 'Edited page',
                    'tx_contexts_settings' => [11 => ['tx_contexts' => '0', 'tx_contexts_nav' => '']],
                ],
            ],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        $page = $this->fetchPage(1);
        self::assertSame('Edited page', $page['title'], 'Precondition: the editor may edit the page itself');
        self::assertSame('', $page['tx_contexts_disable']);
        self::assertSame(0, $this->countSettingRows());
    }

    #[Test]
    public function recordSettingsAreStoredWithPermissionForTheField(): void
    {
        $this->runDataMap(self::EDITOR_WITH_CONTEXT_FIELDS, [
            'pages' => [
                1 => [
                    'tx_contexts_settings' => [11 => ['tx_contexts' => '0', 'tx_contexts_nav' => '']],
                ],
            ],
        ]);

        self::assertSame('11', $this->fetchPage(1)['tx_contexts_disable']);
    }

    #[Test]
    public function settingsOfARecordTheEditorMayNotChangeStayWithThatRecord(): void
    {
        // Page 2 is locked for the editor, so DataHandler skips it after the
        // hook has already read its settings. Page 1 carries no settings.
        $dataHandler = $this->runDataMap(self::EDITOR_WITH_CONTEXT_FIELDS, [
            'pages' => [
                2 => [
                    'tx_contexts_settings' => [11 => ['tx_contexts' => '0', 'tx_contexts_nav' => '0']],
                ],
                1 => [
                    'title' => 'Edited page',
                ],
            ],
        ]);

        self::assertNotSame([], $dataHandler->errorLog, 'Precondition: page 2 must be refused');
        $page = $this->fetchPage(1);
        self::assertSame('Edited page', $page['title']);
        self::assertSame('', $page['tx_contexts_disable']);
        self::assertSame('', $page['tx_contexts_nav_disable']);
        self::assertSame('', $this->fetchPage(2)['tx_contexts_disable']);
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $dataMap
     */
    private function runDataMap(int $backendUserUid, array $dataMap): DataHandler
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, [], $backendUser);
        $dataHandler->process_datamap();

        return $dataHandler;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPage(int $uid): array
    {
        $row = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->select(['*'], 'pages', ['uid' => $uid])
            ->fetchAssociative();

        self::assertIsArray($row);

        return $row;
    }

    private function countSettingRows(): int
    {
        return (int) GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_contexts_settings')
            ->count('*', 'tx_contexts_settings', ['context_uid' => 11]);
    }
}
