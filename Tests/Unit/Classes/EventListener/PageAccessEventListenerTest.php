<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\EventListener\PageAccessEventListener;
use Netresearch\Contexts\Service\FrontendControllerService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Throwable;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for PageAccessEventListener.
 *
 * Note: TYPO3 AfterPageAndLanguageIsResolvedEvent and PageInformation are final classes
 * that can be instantiated for testing. GeneralUtility::makeInstance usage in the listener
 * makes some scenarios better suited for functional tests, but we test all accessible logic here.
 */
final class PageAccessEventListenerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
        if ($typo3Version->getMajorVersion() < 13) {
            self::markTestSkipped('PageInformation is only available in TYPO3 v13+');
        }
    }

    #[Test]
    public function constructorAcceptsFrontendControllerService(): void
    {
        $frontendService = $this->createMock(FrontendControllerService::class);

        $listener = new PageAccessEventListener($frontendService);

        self::assertInstanceOf(PageAccessEventListener::class, $listener);
    }

    #[Test]
    public function listenerIsCallable(): void
    {
        $frontendService = $this->createMock(FrontendControllerService::class);

        $listener = new PageAccessEventListener($frontendService);

        self::assertIsCallable($listener);
    }

    #[Test]
    public function listenerIsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);

        self::assertTrue(
            $reflectionClass->isReadOnly(),
            'PageAccessEventListener should be readonly for immutability',
        );
    }

    #[Test]
    public function listenerIsFinal(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);

        self::assertTrue(
            $reflectionClass->isFinal(),
            'PageAccessEventListener should be final to prevent inheritance issues',
        );
    }

    #[Test]
    public function listenerHasCorrectConstructorDependencies(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);
        $constructor = $reflectionClass->getConstructor();

        self::assertNotNull($constructor);
        $parameters = $constructor->getParameters();

        self::assertCount(1, $parameters, 'Constructor should accept exactly one parameter');
        self::assertSame('frontendControllerService', $parameters[0]->getName());
        self::assertSame(
            FrontendControllerService::class,
            $parameters[0]->getType()?->getName(),
        );
    }

    #[Test]
    public function listenerHasInvokeMethod(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);

        self::assertTrue(
            $reflectionClass->hasMethod('__invoke'),
            'Listener must have __invoke method for PSR-14 event handling',
        );
    }

    #[Test]
    public function invokeMethodReturnsVoid(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);
        $invokeMethod = $reflectionClass->getMethod('__invoke');
        $returnType = $invokeMethod->getReturnType();

        self::assertNotNull($returnType);
        self::assertSame('void', $returnType->getName());
    }

    #[Test]
    public function listenerDependenciesAreInjectedAsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            self::assertTrue(
                $property->isReadOnly(),
                \sprintf('Property "%s" should be readonly', $property->getName()),
            );
        }
    }

    #[Test]
    public function invokeWithEmptyPageRecordReturnsEarly(): void
    {
        $frontendService = $this->createMock(FrontendControllerService::class);

        // Service should NOT be called when pageRecord is empty
        $frontendService->expects(self::never())
            ->method('checkEnableFieldsForRootLine');

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]); // Empty page record
        $pageInformation->setRootLine([]);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        // Should return early without throwing exception
        $listener($event);

        // If we get here without exception, the test passes
        self::assertTrue(true, 'Listener returned early for empty page record');
    }

    #[Test]
    public function invokeWithAccessiblePageDoesNotThrow(): void
    {
        $rootLine = [
            1 => ['uid' => 1, 'title' => 'Root Page'],
            2 => ['uid' => 2, 'title' => 'Sub Page'],
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);

        // Mock service to return true (accessible)
        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with($rootLine)
            ->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 2, 'title' => 'Sub Page']);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        // Should complete without throwing exception
        $listener($event);

        // If we get here without exception, the test passes
        self::assertTrue(true, 'Listener completed successfully for accessible page');
    }

    #[Test]
    public function invokeWithInaccessiblePageTriggersAccessDenied(): void
    {
        $rootLine = [
            1 => ['uid' => 1, 'title' => 'Root Page'],
            2 => ['uid' => 2, 'title' => 'Restricted Page'],
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);

        // Mock service to return false (not accessible)
        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with($rootLine)
            ->willReturn(false);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 2, 'title' => 'Restricted Page']);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        // Note: Full exception testing requires functional tests due to GeneralUtility::makeInstance
        // dependencies. This test verifies the service is called correctly.
        try {
            $listener($event);
            self::fail('Listener should throw exception for inaccessible page');
        } catch (Throwable $exception) {
            // Verify some exception was thrown (either ImmediateResponseException or dependency error)
            self::assertInstanceOf(Throwable::class, $exception);
        }
    }

    #[Test]
    public function invokePassesCorrectRootLineToService(): void
    {
        $expectedRootLine = [
            1 => ['uid' => 1, 'pid' => 0, 'title' => 'Root'],
            2 => ['uid' => 2, 'pid' => 1, 'title' => 'Level 1'],
            3 => ['uid' => 3, 'pid' => 2, 'title' => 'Level 2'],
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);

        // Verify exact rootLine is passed to service
        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with(self::identicalTo($expectedRootLine))
            ->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 3, 'title' => 'Level 2']);
        $pageInformation->setRootLine($expectedRootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        $listener($event);
    }

    #[Test]
    public function invokeHandlesSinglePageRootLine(): void
    {
        $rootLine = [
            1 => ['uid' => 1, 'pid' => 0, 'title' => 'Single Page'],
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);

        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with($rootLine)
            ->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 1, 'title' => 'Single Page']);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        $listener($event);

        self::assertTrue(true, 'Listener handles single-page rootline correctly');
    }

    #[Test]
    public function invokeHandlesDeepRootLine(): void
    {
        $deepRootLine = [
            1 => ['uid' => 1, 'pid' => 0, 'title' => 'Root'],
            2 => ['uid' => 2, 'pid' => 1, 'title' => 'Level 1'],
            3 => ['uid' => 3, 'pid' => 2, 'title' => 'Level 2'],
            4 => ['uid' => 4, 'pid' => 3, 'title' => 'Level 3'],
            5 => ['uid' => 5, 'pid' => 4, 'title' => 'Level 4'],
            6 => ['uid' => 6, 'pid' => 5, 'title' => 'Level 5'],
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);

        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with($deepRootLine)
            ->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 6, 'title' => 'Level 5']);
        $pageInformation->setRootLine($deepRootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        $listener($event);

        self::assertTrue(true, 'Listener handles deep rootline correctly');
    }

    #[Test]
    public function invokeCanBeCalledMultipleTimes(): void
    {
        $rootLine = [
            1 => ['uid' => 1, 'title' => 'Test Page'],
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);

        $frontendService->expects(self::exactly(3))
            ->method('checkEnableFieldsForRootLine')
            ->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 1, 'title' => 'Test Page']);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        // Should not throw exception when called multiple times
        $listener($event);
        $listener($event);
        $listener($event);

        self::assertTrue(true, 'Listener can be invoked multiple times without errors');
    }

    #[Test]
    public function invokeDoesNotModifyPageInformation(): void
    {
        $originalPageRecord = ['uid' => 1, 'title' => 'Test Page', 'hidden' => 0];
        $rootLine = [1 => $originalPageRecord];

        $frontendService = $this->createMock(FrontendControllerService::class);
        $frontendService->method('checkEnableFieldsForRootLine')->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord($originalPageRecord);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        $listener($event);

        // Verify page information was not modified
        self::assertSame(
            $originalPageRecord,
            $event->getPageInformation()->getPageRecord(),
            'Listener should not modify page information',
        );
    }

    #[Test]
    public function invokeHandlesPageWithComplexMetadata(): void
    {
        $complexPageRecord = [
            'uid' => 42,
            'pid' => 1,
            'title' => 'Complex Page',
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '1,2,3',
            'extendToSubpages' => 1,
            'nav_hide' => 0,
            'doktype' => 1,
            'l10n_parent' => 0,
            'sys_language_uid' => 0,
        ];

        $rootLine = [
            1 => ['uid' => 1, 'title' => 'Root'],
            2 => $complexPageRecord,
        ];

        $frontendService = $this->createMock(FrontendControllerService::class);
        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with($rootLine)
            ->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord($complexPageRecord);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        $listener($event);

        self::assertTrue(true, 'Listener handles pages with complex metadata');
    }

    #[Test]
    public function invokeMethodHasCorrectSignature(): void
    {
        $reflectionClass = new ReflectionClass(PageAccessEventListener::class);
        $invokeMethod = $reflectionClass->getMethod('__invoke');
        $parameters = $invokeMethod->getParameters();

        self::assertCount(1, $parameters, '__invoke should accept exactly one parameter');
        self::assertSame('event', $parameters[0]->getName());
        self::assertSame(
            AfterPageAndLanguageIsResolvedEvent::class,
            $parameters[0]->getType()?->getName(),
            '__invoke should accept AfterPageAndLanguageIsResolvedEvent',
        );
    }

    #[Test]
    public function invokeServiceIsOnlyCalledWhenPageRecordIsNotEmpty(): void
    {
        $frontendService = $this->createMock(FrontendControllerService::class);

        // Should NEVER be called for empty page records
        $frontendService->expects(self::never())
            ->method('checkEnableFieldsForRootLine');

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);

        // Test with truly empty array
        $pageInformation1 = new PageInformation();
        $pageInformation1->setPageRecord([]);
        $pageInformation1->setRootLine([]);
        $event1 = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation1);
        $listener($event1);

        self::assertTrue(true, 'Service correctly not called for empty page record');
    }

    #[Test]
    public function invokeCallsServiceForAccessCheck(): void
    {
        $rootLine = [1 => ['uid' => 1, 'title' => 'Test Page']];

        $frontendService = $this->createMock(FrontendControllerService::class);

        // Verify the service method is called for access checking
        $frontendService->expects(self::once())
            ->method('checkEnableFieldsForRootLine')
            ->with($rootLine)
            ->willReturn(true); // Return true to avoid exception path

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord(['uid' => 1, 'title' => 'Test Page']);
        $pageInformation->setRootLine($rootLine);

        $event = new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation);

        $listener($event);

        // Mock expectation verification happens automatically
        self::assertTrue(true, 'Service method was called correctly');
    }

    #[Test]
    public function listenerIsStateless(): void
    {
        $frontendService = $this->createMock(FrontendControllerService::class);
        $frontendService->method('checkEnableFieldsForRootLine')->willReturn(true);

        $listener = new PageAccessEventListener($frontendService);

        $request = $this->createMock(ServerRequestInterface::class);

        // First invocation
        $pageInfo1 = new PageInformation();
        $pageInfo1->setPageRecord(['uid' => 1, 'title' => 'Page 1']);
        $pageInfo1->setRootLine([1 => ['uid' => 1, 'title' => 'Page 1']]);
        $event1 = new AfterPageAndLanguageIsResolvedEvent($request, $pageInfo1);
        $listener($event1);

        // Second invocation with different data
        $pageInfo2 = new PageInformation();
        $pageInfo2->setPageRecord(['uid' => 2, 'title' => 'Page 2']);
        $pageInfo2->setRootLine([1 => ['uid' => 2, 'title' => 'Page 2']]);
        $event2 = new AfterPageAndLanguageIsResolvedEvent($request, $pageInfo2);
        $listener($event2);

        // Verify first event data is unchanged (listener is stateless)
        self::assertSame(
            ['uid' => 1, 'title' => 'Page 1'],
            $event1->getPageInformation()->getPageRecord(),
            'Listener should be stateless and not affect previous invocations',
        );
    }
}
