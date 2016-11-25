<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_contexts_enable,tx_contexts_disable';

//hook into record saving
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contexts']
    = 'Netresearch\Contexts\Service\DataHandlerService';

//override enableFields
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns']['contexts']
    = 'Netresearch\Contexts\Service\PageService->enableFields';

//override page access control
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'][]
    = 'Netresearch\Contexts\Service\PageService';

//override page menu visibility
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][]
    = 'Netresearch\Contexts\Service\PageService';

//override page hash generation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][]
    = 'Netresearch\Contexts\Service\PageService->createHashBase';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['contexts']
    = 'Netresearch\Contexts\Service\FrontendControllerService->initFEuser';

// Add tree icons before TYPO3 7.5
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][]
    = 'Netresearch\Contexts\Service\IconService';
//Add tree icons after TYPO3 7.5
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Core\Imaging\IconFactory']['overrideIconOverlay'][]
    = 'Netresearch\Contexts\Service\IconService';



// add some hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']['contexts']
    = 'Netresearch\Contexts\Service\FrontendControllerService->checkEnableFields';


// this is required for install, before register context classes enable contexts for tables
$GLOBALS['TCA']['tx_contexts_contexts']
    = include \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('contexts', 'Configuration/TCA/tx_contexts_contexts.php');

// register context classes
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('contexts', 'Configuration/TCA/Overrides/tx_contexts_contexts.php');
// enable contexts for tables
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('contexts', 'Configuration/TCA/Overrides/pages.php');
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('contexts', 'Configuration/TCA/Overrides/tt_content.php');

// load the custom typoscript condition here
if (TYPO3_MODE == 'FE') {
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('contexts', 'Resources/Private/PHP/TypoScriptConditionMatcher.php');
}

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');
$signalSlotDispatcher->connect(
    'TYPO3\CMS\Extensionmanager\Utility\InstallUtility',
    'tablesDefinitionIsBeingBuilt',
    'Netresearch\Contexts\Service\InstallService',
    'appendTableDefinitions'
);
