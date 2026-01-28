<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Netresearch\Contexts\Form\CombinationFormElement;
use Netresearch\Contexts\Form\DefaultSettingsFormElement;
use Netresearch\Contexts\Form\RecordSettingsFormElement;
use Netresearch\Contexts\Query\Restriction\ContextRestriction;
use Netresearch\Contexts\Xclass\Backend\Tree\Repository\PageTreeRepository;

defined('TYPO3') || die('Access denied.');

// Add context fields to rootline
// TYPO3 v13: addRootLineFields may not be initialized by default
if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'])) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';
}
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] =
    (string) $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']
    . ',tx_contexts_enable,tx_contexts_disable';

// XClass: This is needed for the context fields in the page tree
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::class] = [
    'className' => PageTreeRepository::class,
];

// Contexts query restriction - applied to all database queries
if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][ContextRestriction::class])) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][ContextRestriction::class] = [];
}

// Register custom form elements for TCA
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1700000001] = [
    'nodeName' => 'defaultSettingsFormElement',
    'priority' => 40,
    'class' => DefaultSettingsFormElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1700000002] = [
    'nodeName' => 'combinationFormElement',
    'priority' => 40,
    'class' => CombinationFormElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1700000003] = [
    'nodeName' => 'recordSettingsFormElement',
    'priority' => 40,
    'class' => RecordSettingsFormElement::class,
];

// DataHandler hooks for processing context settings during record save/update
// Note: TYPO3 v12/v13 DataHandler still uses SC_OPTIONS hooks, not PSR-14 events
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contexts']
    = Netresearch\Contexts\Service\DataHandlerService::class;
