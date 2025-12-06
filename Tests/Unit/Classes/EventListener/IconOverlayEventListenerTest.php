<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\EventListener\IconOverlayEventListener;
use Netresearch\Contexts\Service\IconService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for IconOverlayEventListener.
 *
 * Note: ModifyRecordOverlayIconIdentifierEvent is final but can be instantiated,
 * allowing us to test the actual listener logic.
 */
final class IconOverlayEventListenerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
        if ($typo3Version->getMajorVersion() < 13) {
            self::markTestSkipped('ModifyRecordOverlayIconIdentifierEvent is only available in TYPO3 v13+');
        }
    }

    #[Test]
    public function constructorAcceptsIconService(): void
    {
        $iconService = $this->createMock(IconService::class);

        $listener = new IconOverlayEventListener($iconService);

        self::assertInstanceOf(IconOverlayEventListener::class, $listener);
    }

    #[Test]
    public function listenerIsCallable(): void
    {
        $iconService = $this->createMock(IconService::class);

        $listener = new IconOverlayEventListener($iconService);

        self::assertIsCallable($listener);
    }

    #[Test]
    public function invokeWithEmptyTableDoesNotModifyEvent(): void
    {
        $iconService = $this->createMock(IconService::class);
        $iconService->expects(self::never())->method('postOverlayPriorityLookup');

        $listener = new IconOverlayEventListener($iconService);

        $event = new ModifyRecordOverlayIconIdentifierEvent(
            'overlay-default',
            '', // empty table
            ['uid' => 1],
            [],
        );

        $listener($event);

        self::assertSame('overlay-default', $event->getOverlayIconIdentifier());
    }

    #[Test]
    public function invokeWithEmptyRowDoesNotModifyEvent(): void
    {
        $iconService = $this->createMock(IconService::class);
        $iconService->expects(self::never())->method('postOverlayPriorityLookup');

        $listener = new IconOverlayEventListener($iconService);

        $event = new ModifyRecordOverlayIconIdentifierEvent(
            'overlay-default',
            'pages',
            [], // empty row
            [],
        );

        $listener($event);

        self::assertSame('overlay-default', $event->getOverlayIconIdentifier());
    }

    #[Test]
    public function invokeCallsIconServiceWithCorrectParameters(): void
    {
        $table = 'pages';
        $row = ['uid' => 42, 'title' => 'Test Page'];
        $status = ['deleted' => false];
        $originalOverlay = 'overlay-default';

        $iconService = $this->createMock(IconService::class);
        $iconService->expects(self::once())
            ->method('postOverlayPriorityLookup')
            ->with($table, $row, $status, $originalOverlay)
            ->willReturn($originalOverlay);

        $listener = new IconOverlayEventListener($iconService);

        $event = new ModifyRecordOverlayIconIdentifierEvent(
            $originalOverlay,
            $table,
            $row,
            $status,
        );

        $listener($event);
    }

    #[Test]
    public function invokeDoesNotModifyEventWhenServiceReturnsSameOverlay(): void
    {
        $originalOverlay = 'overlay-default';

        $iconService = $this->createMock(IconService::class);
        $iconService->method('postOverlayPriorityLookup')
            ->willReturn($originalOverlay);

        $listener = new IconOverlayEventListener($iconService);

        $event = new ModifyRecordOverlayIconIdentifierEvent(
            $originalOverlay,
            'pages',
            ['uid' => 1],
            [],
        );

        $listener($event);

        self::assertSame($originalOverlay, $event->getOverlayIconIdentifier());
    }

    #[Test]
    public function invokeModifiesEventWhenServiceReturnsDifferentOverlay(): void
    {
        $originalOverlay = 'overlay-default';
        $modifiedOverlay = 'overlay-context-restricted';

        $iconService = $this->createMock(IconService::class);
        $iconService->method('postOverlayPriorityLookup')
            ->willReturn($modifiedOverlay);

        $listener = new IconOverlayEventListener($iconService);

        $event = new ModifyRecordOverlayIconIdentifierEvent(
            $originalOverlay,
            'pages',
            ['uid' => 1],
            [],
        );

        $listener($event);

        self::assertSame($modifiedOverlay, $event->getOverlayIconIdentifier());
    }

    #[Test]
    public function listenerIsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(IconOverlayEventListener::class);

        self::assertTrue(
            $reflectionClass->isReadOnly(),
            'IconOverlayEventListener should be readonly for immutability',
        );
    }

    #[Test]
    public function listenerIsFinal(): void
    {
        $reflectionClass = new ReflectionClass(IconOverlayEventListener::class);

        self::assertTrue(
            $reflectionClass->isFinal(),
            'IconOverlayEventListener should be final to prevent inheritance issues',
        );
    }

    #[Test]
    public function listenerDependenciesAreInjectedAsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(IconOverlayEventListener::class);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            self::assertTrue(
                $property->isReadOnly(),
                \sprintf('Property "%s" should be readonly', $property->getName()),
            );
        }
    }
}
