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

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\EventListener\FlexFormDataStructureEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;

/**
 * Tests for FlexFormDataStructureEventListener.
 *
 * The listener replaces the "ds_pointerField" mechanism TYPO3 v14 removed
 * (#107047): it maps a context record to the FlexForm file its context type
 * was registered with.
 */
final class FlexFormDataStructureEventListenerTest extends TestCase
{
    private FlexFormDataStructureEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new FlexFormDataStructureEventListener();

        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'] = [
            'default' => ['title' => 'Select a type', 'class' => '', 'flexFile' => 'FILE:EXT:contexts/Empty.xml'],
            'getparam' => ['title' => 'GET parameter', 'class' => '', 'flexFile' => 'FILE:EXT:contexts/GetParam.xml'],
            'session' => ['title' => 'Session', 'class' => '', 'flexFile' => 'FILE:EXT:contexts/Session.xml'],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA']['tx_contexts_contexts']);

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function fallbackRowDataProvider(): array
    {
        return [
            'unknown type' => [['uid' => 1, 'type' => 'no-such-type']],
            'empty type' => [['uid' => 1, 'type' => '']],
            'missing type column' => [['uid' => 1]],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function foreignFieldDataProvider(): array
    {
        return [
            'other table' => ['tt_content', 'pi_flexform'],
            'other field of the same table' => ['tx_contexts_contexts', 'type'],
        ];
    }

    // =========================================================================
    // initializeIdentifier()
    // =========================================================================

    #[Test]
    public function identifierCarriesTheContextTypeOfTheRecord(): void
    {
        $event = $this->identifierEvent('tx_contexts_contexts', 'type_conf', ['uid' => 1, 'type' => 'getparam']);

        $this->subject->initializeIdentifier($event);

        self::assertSame(
            ['type' => 'tx-contexts-context-type', 'contextType' => 'getparam'],
            $event->getIdentifier(),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    #[Test]
    #[DataProvider('fallbackRowDataProvider')]
    public function identifierFallsBackToTheDefaultContextType(array $row): void
    {
        $event = $this->identifierEvent('tx_contexts_contexts', 'type_conf', $row);

        $this->subject->initializeIdentifier($event);

        self::assertSame(
            ['type' => 'tx-contexts-context-type', 'contextType' => 'default'],
            $event->getIdentifier(),
        );
    }

    #[Test]
    #[DataProvider('foreignFieldDataProvider')]
    public function identifierOfForeignFlexFormFieldsIsNotTouched(string $table, string $field): void
    {
        $event = $this->identifierEvent($table, $field, ['uid' => 1, 'type' => 'getparam']);

        $this->subject->initializeIdentifier($event);

        self::assertNull($event->getIdentifier(), 'Only tx_contexts_contexts.type_conf may be claimed');
    }

    // =========================================================================
    // provideDataStructure()
    // =========================================================================

    #[Test]
    public function dataStructureIsTheFlexFormFileOfTheContextType(): void
    {
        $event = new BeforeFlexFormDataStructureParsedEvent([
            'type' => 'tx-contexts-context-type',
            'contextType' => 'session',
        ]);

        $this->subject->provideDataStructure($event);

        self::assertSame('FILE:EXT:contexts/Session.xml', $event->getDataStructure());
    }

    #[Test]
    public function dataStructureFallsBackToTheDefaultContextType(): void
    {
        $event = new BeforeFlexFormDataStructureParsedEvent([
            'type' => 'tx-contexts-context-type',
            'contextType' => 'gone-with-its-extension',
        ]);

        $this->subject->provideDataStructure($event);

        self::assertSame('FILE:EXT:contexts/Empty.xml', $event->getDataStructure());
    }

    #[Test]
    public function dataStructureFallsBackToTheShippedEmptyFormWithoutAnyRegistration(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'] = [];

        $event = new BeforeFlexFormDataStructureParsedEvent([
            'type' => 'tx-contexts-context-type',
            'contextType' => 'getparam',
        ]);

        $this->subject->provideDataStructure($event);

        self::assertSame(
            'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Empty.xml',
            $event->getDataStructure(),
        );
    }

    #[Test]
    public function dataStructureOfForeignIdentifiersIsNotTouched(): void
    {
        $event = new BeforeFlexFormDataStructureParsedEvent([
            'type' => 'tca',
            'tableName' => 'tt_content',
            'fieldName' => 'pi_flexform',
            'dataStructureKey' => 'default',
        ]);

        $this->subject->provideDataStructure($event);

        self::assertNull($event->getDataStructure(), 'Only our own identifiers may be answered');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function identifierEvent(
        string $table,
        string $field,
        array $row,
    ): BeforeFlexFormDataStructureIdentifierInitializedEvent {
        return new BeforeFlexFormDataStructureIdentifierInitializedEvent(
            ['config' => ['type' => 'flex']],
            $table,
            $field,
            $row,
        );
    }
}
