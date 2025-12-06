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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for FrontendControllerService.
 */
final class FrontendControllerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the singleton container for each test
        $reflection = new ReflectionClass(Container::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);
    }

    /**
     * @return array<string, array{0: array<int, array<string, mixed>>, 1: bool}>
     */
    public static function rootLineProvider(): array
    {
        return [
            'empty rootline allows access' => [
                [],
                true,
            ],
            'rootline without context fields allows access' => [
                [
                    ['uid' => 1, 'title' => 'Home', 'extendToSubpages' => 0],
                ],
                true,
            ],
            'rootline with empty context fields allows access' => [
                [
                    [
                        'uid' => 1,
                        'title' => 'Home',
                        'extendToSubpages' => 1,
                        'tx_contexts_enable' => '',
                        'tx_contexts_disable' => '',
                    ],
                ],
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('rootLineProvider')]
    public function checkEnableFieldsForRootLineReturnsExpectedResult(array $rootLine, bool $expectedResult): void
    {
        $service = new FrontendControllerService();

        $result = $service->checkEnableFieldsForRootLine($rootLine);

        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function checkEnableFieldsForRootLineSkipsNonExtendedPages(): void
    {
        // A page without extendToSubpages=1 should be skipped
        $rootLine = [
            [
                'uid' => 1,
                'title' => 'Home',
                'extendToSubpages' => 0,
                'tx_contexts_enable' => '999', // Non-existent context
                'tx_contexts_disable' => '',
            ],
        ];

        $service = new FrontendControllerService();
        $result = $service->checkEnableFieldsForRootLine($rootLine);

        // Should return true because extendToSubpages is 0
        self::assertTrue($result);
    }

    #[Test]
    public function checkEnableFieldsForRootLineProcessesExtendedPages(): void
    {
        // A page with extendToSubpages=1 that requires a non-existent context
        $rootLine = [
            [
                'uid' => 1,
                'title' => 'Home',
                'extendToSubpages' => 1,
                'tx_contexts_enable' => '999', // Non-existent context
                'tx_contexts_disable' => '',
            ],
        ];

        $service = new FrontendControllerService();
        $result = $service->checkEnableFieldsForRootLine($rootLine);

        // Should return false because the required context 999 is not active
        self::assertFalse($result);
    }

    #[Test]
    public function registerQueryParameterStoresParameter(): void
    {
        // Reset static properties
        $reflection = new ReflectionClass(FrontendControllerService::class);

        $paramsProperty = $reflection->getProperty('params');
        $paramsProperty->setValue(null, []);

        $linkVarParamsProperty = $reflection->getProperty('linkVarParams');
        $linkVarParamsProperty->setValue(null, []);

        $hooksProperty = $reflection->getProperty('hooksRegistered');
        $hooksProperty->setValue(null, false);

        // Call the method
        FrontendControllerService::registerQueryParameter('testParam', 'testValue', true);

        // Verify the parameter was stored
        $params = $paramsProperty->getValue(null);
        self::assertArrayHasKey('testParam', $params);
        self::assertSame('testValue', $params['testParam']);

        $linkVarParams = $linkVarParamsProperty->getValue(null);
        self::assertArrayHasKey('testParam', $linkVarParams);
    }
}
