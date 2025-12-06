<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional;

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for content element context visibility.
 */
final class ContentTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_contexts.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_settings.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content.csv');
    }

    #[Test]
    public function tcaConfigurationForContentIsCorrect(): void
    {
        $enableSettings = Configuration::getEnableSettings('tt_content');
        self::assertContains(
            'tx_contexts',
            $enableSettings,
            'tt_content table should have tx_contexts in enableSettings',
        );

        $flatColumns = Configuration::getFlatColumns('tt_content', 'tx_contexts');
        self::assertCount(
            2,
            $flatColumns,
            'tt_content table should have flatColumns for tx_contexts',
        );
        self::assertSame('tx_contexts_disable', $flatColumns[0]);
        self::assertSame('tx_contexts_enable', $flatColumns[1]);
    }

    #[Test]
    public function contentElementsCanBeRetrieved(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');

        $result = $connection->select(['uid', 'header'], 'tt_content', ['pid' => 1]);
        $rows = $result->fetchAllAssociative();

        self::assertCount(3, $rows);
    }

    #[Test]
    public function contentWithContextEnableFieldIsStored(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');

        $result = $connection->select(
            ['uid', 'tx_contexts_enable'],
            'tt_content',
            ['uid' => 2],
        );
        $row = $result->fetchAssociative();

        self::assertSame('1', (string) $row['tx_contexts_enable']);
    }

    #[Test]
    public function contentWithContextDisableFieldIsStored(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');

        $result = $connection->select(
            ['uid', 'tx_contexts_disable'],
            'tt_content',
            ['uid' => 3],
        );
        $row = $result->fetchAssociative();

        self::assertSame('1', (string) $row['tx_contexts_disable']);
    }

    #[Test]
    public function contextSettingsColumnExistsInTca(): void
    {
        self::assertArrayHasKey(
            Configuration::RECORD_SETTINGS_COLUMN,
            $GLOBALS['TCA']['tt_content']['columns'],
            'tt_content should have tx_contexts_settings column',
        );
    }
}
