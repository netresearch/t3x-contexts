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

namespace Netresearch\Contexts\Tests\Functional;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Service\DataHandlerService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A changed context record must invalidate the frontend caches.
 *
 * TypoScriptConfigEventListener writes "config.linkVars" into the TypoScript
 * cache (FrontendTypoScriptFactory dispatches ModifyTypoScriptConfigEvent
 * *before* it caches the result), and that cache identifier is derived from the
 * TypoScript sources - never from tx_contexts_contexts. Without an explicit
 * flush, adding, renaming or deleting a "getparam" context would not reach
 * config.linkVars until somebody flushed the caches by hand.
 *
 * The "pages" cache group is the one that carries the affected caches: hash,
 * pages, rootline and typoscript. Of those only "typoscript" keeps a real
 * backend in functional tests (the testing framework swaps hash, pages and
 * rootline for a NullBackend), so it is the one asserted on here.
 */
final class ContextCacheInvalidationTest extends FunctionalTestCase
{
    /**
     * Marker entry used to detect that the cache group was flushed.
     */
    private const CACHE_ENTRY = 'contexts-cache-invalidation-probe';

    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/contexts_session.csv');
    }

    protected function tearDown(): void
    {
        Container::reset();

        parent::tearDown();
    }

    #[Test]
    public function dataHandlerHooksAreRegisteredForBothDataAndCommandMap(): void
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php'] ?? [];

        self::assertSame(
            DataHandlerService::class,
            $hooks['processDatamapClass']['contexts'] ?? null,
            'ext_localconf.php must wire the data map hook',
        );
        self::assertSame(
            DataHandlerService::class,
            $hooks['processCmdmapClass']['contexts'] ?? null,
            'ext_localconf.php must wire the command map hook, otherwise deleting a context leaves stale caches',
        );
    }

    #[Test]
    public function theHookClassIsResolvableWithAllItsDependencies(): void
    {
        // DataHandler instantiates the hook via GeneralUtility::makeInstance(),
        // so the added CacheManager/QueryParameterService constructor arguments
        // have to be autowirable.
        self::assertInstanceOf(
            DataHandlerService::class,
            GeneralUtility::makeInstance(DataHandlerService::class),
        );
    }

    #[Test]
    public function editingAContextRecordFlushesTheTypoScriptCache(): void
    {
        $this->primeCache();

        $backendUser = $this->setUpBackendUser(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['tx_contexts_contexts' => [11 => ['title' => 'renamed channel param']]],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        self::assertFalse(
            $this->cache()->has(self::CACHE_ENTRY),
            'Renaming a context record must flush the cached TypoScript config',
        );
    }

    #[Test]
    public function deletingAContextRecordFlushesTheTypoScriptCache(): void
    {
        $this->primeCache();

        $backendUser = $this->setUpBackendUser(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [],
            ['tx_contexts_contexts' => [11 => ['delete' => 1]]],
            $backendUser,
        );
        $dataHandler->process_cmdmap();

        self::assertSame([], $dataHandler->errorLog);
        self::assertFalse(
            $this->cache()->has(self::CACHE_ENTRY),
            'Deleting a context record must flush the cached TypoScript config',
        );
    }

    #[Test]
    public function editingAnUnrelatedRecordKeepsTheTypoScriptCache(): void
    {
        $this->primeCache();

        $backendUser = $this->setUpBackendUser(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            ['be_users' => [1 => ['realName' => 'Administrator']]],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();

        self::assertTrue(
            $this->cache()->has(self::CACHE_ENTRY),
            'Only context records may flush the frontend caches',
        );
    }

    private function cache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('typoscript');
    }

    private function primeCache(): void
    {
        $cache = $this->cache();
        $cache->set(self::CACHE_ENTRY, 'return 1;');

        self::assertTrue($cache->has(self::CACHE_ENTRY), 'Precondition: the probe entry must be cached');
    }
}
