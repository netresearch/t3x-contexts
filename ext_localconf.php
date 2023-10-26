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
use Netresearch\Contexts\Service\DataHandlerService;
use Netresearch\Contexts\Service\IconService;
use Netresearch\Contexts\Service\PageService;
use Netresearch\Contexts\Xclass\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Container\Container;

defined('TYPO3') || die('Access denied.');


$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_contexts_enable,tx_contexts_disable';


// XClass: This is needed for the context fields in the page tree
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::class] = [
    'className' => PageTreeRepository::class
];

// Register extended classes in dependency injection container (TYPO3 9.5 - 11.5, not required in TYPO3 12)
GeneralUtility::makeInstance(Container::class)
    ->registerImplementation(
        \TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::class,
        PageTreeRepository::class
    );


// Hook into record saving
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
    = DataHandlerService::class;

// Contexts query restriction
if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][ContextRestriction::class])) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][ContextRestriction::class] = [];
}


// Override page menu visibility
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][]
    = PageService::class;

// Override page hash generation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][]
    = 'Netresearch\Contexts\Service\PageService->createHashBase';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][IconFactory::class]['overrideIconOverlay'][]
    = IconService::class;


// Register custom form elements
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][12345] = [
    'nodeName' => 'defaultSettingsFormElement',
    'priority' => 40,
    'class'    => DefaultSettingsFormElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][12346] = [
    'nodeName' => 'combinationFormElement',
    'priority' => 40,
    'class'    => CombinationFormElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][12347] = [
    'nodeName' => 'recordSettingsFormElement',
    'priority' => 40,
    'class'    => RecordSettingsFormElement::class,
];


// Add some hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']['contexts']
    = 'Netresearch\Contexts\Service\FrontendControllerService->checkEnableFields';


// Signal is no longer available since TYPO3 v10, Rework somehow if really required or remove stuff
//$signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
//$signalSlotDispatcher->connect(
//    \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
//    'tablesDefinitionIsBeingBuilt',
//    \Netresearch\Contexts\Service\InstallService::class,
//    'appendTableDefinitions'
//);
