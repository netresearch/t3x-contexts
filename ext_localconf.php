<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

defined('TYPO3_MODE') || die('Access denied.');


$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_contexts_enable,tx_contexts_disable';

// this is needed for the context fields in the page tree
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::class] = [
    'className' => \Netresearch\Contexts\Xclass\Backend\Tree\Repository\PageTreeRepository::class
];

//hook into record saving
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contexts']
    = \Netresearch\Contexts\Service\DataHandlerService::class;

// contexts query restriction
if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Netresearch\Contexts\Query\Restriction\ContextRestriction::class])) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Netresearch\Contexts\Query\Restriction\ContextRestriction::class] = [];
}

//override page menu visibility
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][]
    = \Netresearch\Contexts\Service\PageService::class;

//override page hash generation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][]
    = 'Netresearch\Contexts\Service\PageService->createHashBase';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Imaging\IconFactory::class]['overrideIconOverlay'][]
    = \Netresearch\Contexts\Service\IconService::class;


// Register custom form elements
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][12345] = [
    'nodeName' => 'defaultSettingsFormElement',
    'priority' => 40,
    'class'    => \Netresearch\Contexts\Form\DefaultSettingsFormElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][12346] = [
    'nodeName' => 'combinationFormElement',
    'priority' => 40,
    'class'    => \Netresearch\Contexts\Form\CombinationFormElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][12347] = [
    'nodeName' => 'recordSettingsFormElement',
    'priority' => 40,
    'class'    => \Netresearch\Contexts\Form\RecordSettingsFormElement::class,
];



// add some hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']['contexts']
    = 'Netresearch\Contexts\Service\FrontendControllerService->checkEnableFields';


/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
    'tablesDefinitionIsBeingBuilt',
    \Netresearch\Contexts\Service\InstallService::class,
    'appendTableDefinitions'
);
