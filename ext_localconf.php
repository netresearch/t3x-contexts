<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_contexts_enable,tx_contexts_disable';

// this is needed for the context fields in the page tree
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::class] = [
    'className' => \Netresearch\Contexts\Xclass\Backend\Tree\Repository\PageTreeRepository::class
];

//hook into record saving
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contexts']
    = 'Netresearch\Contexts\Service\DataHandlerService';

// contexts query restriction
if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Netresearch\Contexts\Query\Restriction\ContextRestriction::class])) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Netresearch\Contexts\Query\Restriction\ContextRestriction::class] = [];
}

//override page menu visibility
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][]
    = 'Netresearch\Contexts\Service\PageService';

//override page hash generation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][]
    = 'Netresearch\Contexts\Service\PageService->createHashBase';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Core\Imaging\IconFactory']['overrideIconOverlay'][]
    = 'Netresearch\Contexts\Service\IconService';



// add some hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']['contexts']
    = 'Netresearch\Contexts\Service\FrontendControllerService->checkEnableFields';


/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');
$signalSlotDispatcher->connect(
    'TYPO3\CMS\Extensionmanager\Utility\InstallUtility',
    'tablesDefinitionIsBeingBuilt',
    'Netresearch\Contexts\Service\InstallService',
    'appendTableDefinitions'
);
