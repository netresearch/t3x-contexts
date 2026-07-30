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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The FlexForm of a context record must resolve from its context type.
 *
 * TYPO3 v14 removed "ds_pointerField" and the multi-entry "ds" array
 * (#107047), which is what this extension used to select the per-type
 * FlexForm. Without a replacement, every read of the data structure throws
 * InvalidTcaException on v14.3 - which means context records can neither be
 * rendered nor saved in the backend.
 *
 * FlexFormDataStructureEventListener resolves the data structure through the
 * two PSR-14 events instead. Those are dispatched by FlexFormTools for every
 * caller (FormEngine, DataHandler, ReferenceIndex) and have an identical API
 * in v13.4 and v14.3, so these assertions hold on both.
 */
final class FlexFormDataStructureTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function contextTypeDataProvider(): array
    {
        return [
            'GET parameter context' => ['getparam', 'field_name'],
            'session context' => ['session', 'field_variable'],
            'domain context' => ['domain', 'field_domains'],
            'IP context' => ['ip', 'field_ip'],
        ];
    }

    #[Test]
    #[DataProvider('contextTypeDataProvider')]
    public function dataStructureIsResolvedFromTheContextType(string $type, string $expectedField): void
    {
        self::assertContains(
            $expectedField,
            $this->resolveSheetFieldNames(['uid' => 1, 'type' => $type]),
            'The FlexForm of the "' . $type . '" context type must be used',
        );
    }

    #[Test]
    public function unknownContextTypeFallsBackToTheEmptyDataStructure(): void
    {
        self::assertSame(
            ['placeholder'],
            $this->resolveSheetFieldNames(['uid' => 1, 'type' => 'no-such-type']),
            'An unknown context type must not break, it falls back to the empty data structure',
        );
    }

    #[Test]
    public function newRecordWithoutATypeFallsBackToTheEmptyDataStructure(): void
    {
        self::assertSame(
            ['placeholder'],
            $this->resolveSheetFieldNames(['uid' => 'NEW1234', 'type' => '']),
            'A record that has no type selected yet must not break the form',
        );
    }

    /**
     * Field names of the default sheet of the data structure TYPO3 resolves
     * for the given context record.
     *
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private function resolveSheetFieldNames(array $row): array
    {
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

        $identifier = $flexFormTools->getDataStructureIdentifier(
            (array) $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type_conf'],
            'tx_contexts_contexts',
            'type_conf',
            $row,
        );

        $dataStructure = $flexFormTools->parseDataStructureByIdentifier($identifier);
        $elements = $dataStructure['sheets']['sDEF']['ROOT']['el'] ?? null;

        self::assertIsArray($elements, 'The data structure must have a default sheet');

        return array_map(static fn(int|string $name): string => (string) $name, array_keys($elements));
    }
}
