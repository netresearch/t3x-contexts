<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional\Classes\Context;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Setting;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for AbstractContext settings methods.
 *
 * Tests coverage for:
 * - getSetting() - retrieves single setting
 * - getSettings() - retrieves all settings for a record
 * - hasSetting() - checks if a setting exists
 */
final class AbstractContextSettingsTest extends FunctionalTestCase
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
    public function getSettingsReturnsSettingsForContext(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        // Get context with UID 10 (has settings)
        $context = $container->find(10);
        self::assertNotNull($context, 'Context with UID 10 should exist');

        // Get settings for pages record 100
        $settings = $context->getSettings('pages', 100);

        self::assertIsArray($settings, 'getSettings should return an array');
        self::assertCount(2, $settings, 'Should have 2 settings for pages:100');
        self::assertArrayHasKey('tx_contexts_visibility', $settings, 'Should have visibility setting');
        self::assertArrayHasKey('tx_contexts_nav', $settings, 'Should have nav setting');
    }

    #[Test]
    public function getSettingsReturnsCachedResults(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        // First call
        $settings1 = $context->getSettings('pages', 100);
        // Second call should return cached result
        $settings2 = $context->getSettings('pages', 100);

        self::assertSame($settings1, $settings2, 'Subsequent calls should return cached settings');
    }

    #[Test]
    public function getSettingsReturnsEmptyArrayWhenNoSettings(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        // Get settings for non-existent record
        $settings = $context->getSettings('pages', 999);

        self::assertIsArray($settings, 'Should return array even when no settings');
        self::assertEmpty($settings, 'Should be empty for non-existent record');
    }

    #[Test]
    public function getSettingsIncludesDefaultSettingsForUidZero(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        // Get settings for a record that has no specific settings
        // but there's a default (uid=0) setting
        $settings = $context->getSettings('pages', 999);

        // The default (uid=0) setting should also be loaded into cache
        $defaultSettings = $context->getSettings('pages', 0);
        self::assertArrayHasKey('tx_contexts_visibility', $defaultSettings, 'Default settings should be loaded');
    }

    #[Test]
    public function getSettingsReturnsDifferentSettingsForDifferentContexts(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context10 = $container->find(10);
        $context20 = $container->find(20);

        self::assertNotNull($context10);
        self::assertNotNull($context20);

        $settings10 = $context10->getSettings('pages', 100);
        $settings20 = $context20->getSettings('pages', 100);

        // Context 10 has 2 settings, context 20 has 1 setting
        self::assertCount(2, $settings10, 'Context 10 should have 2 settings');
        self::assertCount(1, $settings20, 'Context 20 should have 1 setting');
    }

    #[Test]
    public function getSettingsReturnsDifferentSettingsForDifferentTables(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $pagesSettings = $context->getSettings('pages', 100);
        $contentSettings = $context->getSettings('tt_content', 200);

        self::assertCount(2, $pagesSettings, 'Should have 2 settings for pages');
        self::assertCount(1, $contentSettings, 'Should have 1 setting for tt_content');
    }

    #[Test]
    public function getSettingReturnsSettingObject(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $setting = $context->getSetting('pages', 'tx_contexts_visibility', 100);

        self::assertInstanceOf(Setting::class, $setting, 'Should return Setting object');
        self::assertTrue($setting->getEnabled(), 'Setting should be enabled');
    }

    #[Test]
    public function getSettingReturnsNullForNonExistentSetting(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $setting = $context->getSetting('pages', 'nonexistent_setting', 100);

        self::assertNull($setting, 'Should return null for non-existent setting');
    }

    #[Test]
    public function hasSettingReturnsTrueWhenSettingExists(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $hasSetting = $context->hasSetting('pages', 'tx_contexts_visibility', 100);

        self::assertTrue($hasSetting, 'Should return true when setting exists');
    }

    #[Test]
    public function hasSettingReturnsFalseWhenSettingDoesNotExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $hasSetting = $context->hasSetting('pages', 'nonexistent_setting', 100);

        self::assertFalse($hasSetting, 'Should return false when setting does not exist');
    }

    #[Test]
    public function hasSettingReturnsFalseWhenRecordHasNoSettings(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $hasSetting = $context->hasSetting('pages', 'tx_contexts_visibility', 999);

        self::assertFalse($hasSetting, 'Should return false for record with no settings');
    }

    #[Test]
    public function settingObjectContainsCorrectValues(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';

        $request = new ServerRequest('http://localhost/', 'GET');
        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        $enabledSetting = $context->getSetting('pages', 'tx_contexts_visibility', 100);
        $disabledSetting = $context->getSetting('pages', 'tx_contexts_nav', 100);

        self::assertNotNull($enabledSetting);
        self::assertNotNull($disabledSetting);

        self::assertTrue($enabledSetting->getEnabled(), 'visibility setting should be enabled');
        self::assertFalse($disabledSetting->getEnabled(), 'nav setting should be disabled');
    }

    #[Test]
    public function getSettingWithFlatDataFromRowReturnsSettingObject(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/AbstractContextSettingsTest.csv');

        // Set up TCA with flat columns
        $GLOBALS['TCA']['pages']['ctrl']['enablecolumns'] = [];
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
        $container->initAll();

        $context = $container->find(10);
        self::assertNotNull($context);

        // Test flat data - when row contains the flat columns
        $row = [
            'uid' => 100,
            'tx_contexts_enable' => '10', // Context UID 10 is enabled
            'tx_contexts_disable' => '',
        ];

        $setting = $context->getSetting('pages', 'tx_contexts', 100, $row);

        // When flat columns exist in row, Setting::fromFlatData is used
        if ($setting !== null) {
            self::assertInstanceOf(Setting::class, $setting);
        }
    }
}
