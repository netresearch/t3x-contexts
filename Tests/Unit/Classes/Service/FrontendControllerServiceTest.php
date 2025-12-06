<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Service;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Service\FrontendControllerService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for FrontendControllerService.
 *
 * FrontendControllerService provides methods for:
 * - Query parameter registration for context-based caching
 * - Rootline-based page access checking
 * - Cache hash modification
 */
final class FrontendControllerServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        // Initialize required TCA structure
        $GLOBALS['TCA']['tx_contexts_contexts'] = [
            'contextTypes' => [],
            'extensionFlatSettings' => [],
            'columns' => [],
        ];

        // Reset static state
        $this->resetStaticProperties();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA']['tx_contexts_contexts'],
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'],
        );
        Container::reset();
        $this->resetStaticProperties();
        parent::tearDown();
    }

    #[Test]
    public function implementsSingletonInterface(): void
    {
        $service = new FrontendControllerService();

        self::assertInstanceOf(SingletonInterface::class, $service);
    }

    #[Test]
    public function checkEnableFieldsForRootLineReturnsTrueForEmptyRootLine(): void
    {
        $service = new FrontendControllerService();

        self::assertTrue($service->checkEnableFieldsForRootLine([]));
    }

    #[Test]
    public function checkEnableFieldsForRootLineReturnsTrueWhenNoExtendToSubpages(): void
    {
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 0],
            ['uid' => 2, 'title' => 'Subpage', 'extendToSubpages' => 0],
        ];

        $service = new FrontendControllerService();

        self::assertTrue($service->checkEnableFieldsForRootLine($rootLine));
    }

    #[Test]
    public function checkEnableFieldsForRootLineChecksPagesWithExtendToSubpages(): void
    {
        // No contexts active
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 1, 'tx_contexts_enable' => '', 'tx_contexts_disable' => ''],
            ['uid' => 2, 'title' => 'Subpage', 'extendToSubpages' => 0],
        ];

        $service = new FrontendControllerService();

        // With no contexts and no restrictions, should return true
        self::assertTrue($service->checkEnableFieldsForRootLine($rootLine));
    }

    #[Test]
    public function checkEnableFieldsForRootLineReturnsFalseWhenRequiredContextMissing(): void
    {
        // Page requires context ID 999 which doesn't exist
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 1, 'tx_contexts_enable' => '999', 'tx_contexts_disable' => ''],
        ];

        $service = new FrontendControllerService();

        // Context 999 is required but not active, should return false
        self::assertFalse($service->checkEnableFieldsForRootLine($rootLine));
    }

    #[Test]
    public function checkEnableFieldsForRootLineHandlesEmptyEnableDisableStrings(): void
    {
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 1, 'tx_contexts_enable' => '', 'tx_contexts_disable' => ''],
        ];

        $service = new FrontendControllerService();

        self::assertTrue($service->checkEnableFieldsForRootLine($rootLine));
    }

    #[Test]
    public function checkEnableFieldsForRootLineHandlesMultipleContextIds(): void
    {
        // Page requires multiple contexts that don't exist
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 1, 'tx_contexts_enable' => '1,2,3', 'tx_contexts_disable' => ''],
        ];

        $service = new FrontendControllerService();

        // First missing context should trigger false
        self::assertFalse($service->checkEnableFieldsForRootLine($rootLine));
    }

    #[Test]
    public function registerQueryParameterStoresParam(): void
    {
        // Initialize TYPO3_CONF_VARS if not set
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] ??= [];

        FrontendControllerService::registerQueryParameter('test_param', 'test_value', false);

        // Check that hooks were registered
        self::assertArrayHasKey(
            FrontendControllerService::class,
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'] ?? [],
        );
    }

    #[Test]
    public function registerQueryParameterAddsToLinkVarsWhenRequested(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] ??= [];

        FrontendControllerService::registerQueryParameter('link_param', 'value', true);

        self::assertArrayHasKey(
            FrontendControllerService::class,
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'] ?? [],
        );
    }

    #[Test]
    public function createHashBaseSerializesParams(): void
    {
        $this->resetStaticProperties();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] ??= [];

        FrontendControllerService::registerQueryParameter('hash_test', 'hash_value', false);

        $params = ['hashParameters' => []];

        $service = new FrontendControllerService();
        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);

        $service->createHashBase($params, $mockTsfe);

        $expectedKey = strtolower(FrontendControllerService::class);
        self::assertArrayHasKey($expectedKey, $params['hashParameters']);
        self::assertStringContainsString('hash_test', $params['hashParameters'][$expectedKey]);
    }

    #[Test]
    public function configArrayPostProcAddsLinkVars(): void
    {
        $this->resetStaticProperties();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] ??= [];

        FrontendControllerService::registerQueryParameter('link_var_test', 'value', true);

        $params = ['config' => ['linkVars' => 'L']];

        $service = new FrontendControllerService();
        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);

        $service->configArrayPostProc($params, $mockTsfe);

        self::assertStringContainsString('link_var_test', $params['config']['linkVars']);
        self::assertStringContainsString('L', $params['config']['linkVars']);
    }

    #[Test]
    public function configArrayPostProcHandlesEmptyLinkVars(): void
    {
        $this->resetStaticProperties();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] ??= [];

        FrontendControllerService::registerQueryParameter('only_param', 'value', true);

        $params = ['config' => []]; // No existing linkVars

        $service = new FrontendControllerService();
        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);

        $service->configArrayPostProc($params, $mockTsfe);

        self::assertStringContainsString('only_param', $params['config']['linkVars']);
    }

    #[Test]
    public function checkEnableFieldsForRootLineProcessesRootLineInReverse(): void
    {
        // The method uses array_reverse, so first item in rootline is processed last
        // This means extendToSubpages from root page affects all children
        $rootLine = [
            ['uid' => 1, 'title' => 'Root', 'extendToSubpages' => 1, 'tx_contexts_enable' => '', 'tx_contexts_disable' => ''],
            ['uid' => 2, 'title' => 'Level1', 'extendToSubpages' => 0],
            ['uid' => 3, 'title' => 'Level2', 'extendToSubpages' => 0],
        ];

        $service = new FrontendControllerService();

        // With extendToSubpages on root, and no context requirements, should pass
        self::assertTrue($service->checkEnableFieldsForRootLine($rootLine));
    }

    #[Test]
    public function checkEnableFieldsDelegatesToCheckEnableFieldsForRootLine(): void
    {
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 1, 'tx_contexts_enable' => '', 'tx_contexts_disable' => ''],
        ];

        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        $mockTsfe->rootLine = $rootLine;

        $data = [
            'pObj' => $mockTsfe,
            'row' => ['uid' => 1],
            'bypassGroupCheck' => false,
        ];

        $service = new FrontendControllerService();

        // The deprecated method should return the same as the new method
        self::assertTrue($service->checkEnableFields($data));
    }

    #[Test]
    public function checkEnableFieldsReturnsFalseWhenContextMissing(): void
    {
        $rootLine = [
            ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 1, 'tx_contexts_enable' => '999', 'tx_contexts_disable' => ''],
        ];

        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        $mockTsfe->rootLine = $rootLine;

        $data = [
            'pObj' => $mockTsfe,
            'row' => ['uid' => 1],
            'bypassGroupCheck' => false,
        ];

        $service = new FrontendControllerService();

        // Context 999 required but not active
        self::assertFalse($service->checkEnableFields($data));
    }

    /**
     * Reset static properties using reflection.
     */
    private function resetStaticProperties(): void
    {
        $reflection = new ReflectionClass(FrontendControllerService::class);

        $hooksRegistered = $reflection->getProperty('hooksRegistered');
        $hooksRegistered->setValue(null, false);

        $params = $reflection->getProperty('params');
        $params->setValue(null, []);

        $linkVarParams = $reflection->getProperty('linkVarParams');
        $linkVarParams->setValue(null, []);
    }
}
