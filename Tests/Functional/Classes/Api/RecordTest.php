<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional\Classes\Api;

use Netresearch\Contexts\Api\Record;
use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for Record API class.
 *
 * Tests coverage for:
 * - isEnabled() - checks if record is enabled for current contexts
 * - isSettingEnabled() - checks if a specific setting is enabled
 */
final class RecordTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Container::reset();
    }

    protected function tearDown(): void
    {
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function isEnabledReturnsTrueWhenNoEnableSettings(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        $row = ['uid' => 100];

        // Table without enable settings should always return true
        self::assertTrue(
            Record::isEnabled('pages', $row),
            'isEnabled should return true when no enable settings configured',
        );
    }

    #[Test]
    public function isSettingEnabledReturnsFalseWhenUidMissing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Row without uid should return false
        $row = ['title' => 'Test'];

        $result = Record::isSettingEnabled('pages', 'tx_contexts_visibility', $row);

        self::assertFalse($result, 'isSettingEnabled should return false when uid is missing');
    }

    #[Test]
    public function isSettingEnabledReturnsTrueWhenNoContextsMatch(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'nonexistent.local';

        $request = new ServerRequest('http://nonexistent.local/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // When no contexts match, settings should be enabled by default
        $row = ['uid' => 100];

        $result = Record::isSettingEnabled('pages', 'tx_contexts_visibility', $row);

        self::assertTrue($result, 'isSettingEnabled should return true when no contexts match');
    }

    #[Test]
    public function isSettingEnabledReturnsTrueWithMatchingContextAndNoSettings(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Context matches but no specific settings are defined for this record
        $row = ['uid' => 999];

        $result = Record::isSettingEnabled('pages', 'tx_contexts_visibility', $row);

        self::assertTrue($result, 'isSettingEnabled should return true when no settings defined for record');
    }

    #[Test]
    public function isSettingEnabledReturnsFalseWhenSettingDisabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestSettingsDisabled.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Row with disabled visibility setting
        $row = ['uid' => 200];

        $result = Record::isSettingEnabled('pages', 'tx_contexts_visibility', $row);

        self::assertFalse($result, 'isSettingEnabled should return false when setting is disabled');
    }

    #[Test]
    public function isSettingEnabledReturnsFalseWhenDefaultSettingDisabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestDefaultDisabled.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Row that has no specific setting but default (uid=0) is disabled
        $row = ['uid' => 888];

        $result = Record::isSettingEnabled('pages', 'tx_contexts_visibility', $row);

        self::assertFalse($result, 'isSettingEnabled should return false when default setting is disabled');
    }

    #[Test]
    public function isSettingEnabledWithFlatColumnsReturnsCorrectValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');

        // Configure flat columns for the table
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'pages' => [
                'tx_contexts' => [
                    'flatten' => true,
                    'flatColumns' => [
                        'tx_contexts_enable' => ['context_enable', 'enableField'],
                        'tx_contexts_disable' => ['context_disable', 'disableField'],
                    ],
                ],
            ],
        ];

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Verify context 10 is matching
        $context = $container->find(10);
        self::assertNotNull($context, 'Context 10 should exist and match');

        // Row with flat column data - context UID 10 is in disable list
        $row = [
            'uid' => 100,
            'tx_contexts_enable' => '',
            'tx_contexts_disable' => '10', // Context 10 disables this record
        ];

        $result = Record::isSettingEnabled('pages', 'tx_contexts', $row);

        // When flat column contains context UID in disable column, record is disabled
        self::assertFalse($result, 'isSettingEnabled should return false when context is in disable flat column');
    }

    #[Test]
    public function isSettingEnabledWithFlatColumnsReturnsNullForMissingColumns(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/RecordTestContexts.csv');

        // Configure flat columns for the table
        $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] = [
            'pages' => [
                'tx_contexts' => [
                    'flatten' => true,
                    'flatColumns' => [
                        'tx_contexts_enable' => ['context_enable', 'enableField'],
                        'tx_contexts_disable' => ['context_disable', 'disableField'],
                    ],
                ],
            ],
        ];

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Row WITHOUT flat columns - should fall through to database lookup
        $row = [
            'uid' => 100,
            // Missing tx_contexts_enable and tx_contexts_disable
        ];

        // This should return true because no settings in database and no flat columns
        $result = Record::isSettingEnabled('pages', 'tx_contexts', $row);

        self::assertTrue($result, 'isSettingEnabled should return true when flat columns not in row');
    }
}
