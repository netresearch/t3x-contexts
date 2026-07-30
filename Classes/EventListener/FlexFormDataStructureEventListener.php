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

namespace Netresearch\Contexts\EventListener;

use Netresearch\Contexts\Api\Configuration;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;

/**
 * Resolves the FlexForm data structure of "tx_contexts_contexts.type_conf"
 * from the context type of the record.
 *
 * TYPO3 v14 removed the "ds_pointerField" mechanism and the multi-entry "ds"
 * array that this used to rely on (#107047). The replacement core suggests -
 * record types plus "types.<type>.columnsOverrides" - is not an option here:
 * TYPO3 v13 resolves the data structure from the raw TCA in DataHandler and
 * ReferenceIndex, where "columnsOverrides" is not applied, so context records
 * would no longer be saveable on v13.
 *
 * These two PSR-14 events are dispatched by FlexFormTools in both v13.4 and
 * v14.3 with an identical API and cover every caller (FormEngine, DataHandler,
 * ReferenceIndex), which makes them the one mechanism that works on both.
 *
 * @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-107047-RemovePointerFieldFunctionalityOfTCAFlex.html
 */
final readonly class FlexFormDataStructureEventListener
{
    /**
     * Table holding the context records.
     */
    private const TABLE = 'tx_contexts_contexts';

    /**
     * FlexForm field holding the type specific configuration.
     */
    private const FIELD = 'type_conf';

    /**
     * Marks a data structure identifier as being owned by this extension.
     */
    private const IDENTIFIER_TYPE = 'tx-contexts-context-type';

    /**
     * Context type used when a record carries no or an unknown type.
     */
    private const FALLBACK_TYPE = 'default';

    /**
     * Data structure used when not even the fallback type is registered.
     */
    private const FALLBACK_DATA_STRUCTURE = 'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Empty.xml';

    /**
     * Point the identifier at the context type of the record instead of at a
     * key of the (removed) "ds" array.
     */
    #[AsEventListener(
        identifier: 'contexts/flexform-data-structure-identifier',
        event: BeforeFlexFormDataStructureIdentifierInitializedEvent::class,
    )]
    public function initializeIdentifier(BeforeFlexFormDataStructureIdentifierInitializedEvent $event): void
    {
        if ($event->getTableName() !== self::TABLE || $event->getFieldName() !== self::FIELD) {
            return;
        }

        $row = $event->getRow();
        $contextType = isset($row['type']) ? (string) $row['type'] : '';

        if (!\array_key_exists($contextType, Configuration::getContextTypes())) {
            $contextType = self::FALLBACK_TYPE;
        }

        $event->setIdentifier([
            'type' => self::IDENTIFIER_TYPE,
            'contextType' => $contextType,
        ]);
    }

    /**
     * Hand out the FlexForm file the context type was registered with.
     */
    #[AsEventListener(
        identifier: 'contexts/flexform-data-structure',
        event: BeforeFlexFormDataStructureParsedEvent::class,
    )]
    public function provideDataStructure(BeforeFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();

        if (($identifier['type'] ?? null) !== self::IDENTIFIER_TYPE) {
            return;
        }

        $contextType = isset($identifier['contextType']) ? (string) $identifier['contextType'] : '';
        $contextTypes = Configuration::getContextTypes();
        $flexFile = $contextTypes[$contextType]['flexFile']
            ?? $contextTypes[self::FALLBACK_TYPE]['flexFile']
            ?? self::FALLBACK_DATA_STRUCTURE;

        $event->setDataStructure((string) $flexFile);
    }
}
