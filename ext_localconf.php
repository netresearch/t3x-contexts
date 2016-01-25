<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$strContextsPath = t3lib_extMgm::extPath($_EXTKEY);

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']
    .= ',tx_contexts_enable,tx_contexts_disable';

//hook into record saving
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contexts']
    = 'EXT:contexts/Classes/Service/Tcemain.php:'
    . 'Tx_Contexts_Service_Tcemain';

//override enableFields
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns']['contexts']
    = 'EXT:contexts/Classes/Service/Page.php:'
    . '&Tx_Contexts_Service_Page->enableFields';
//override page access control
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'][]
    = 'EXT:contexts/Classes/Service/Page.php:'
    . '&Tx_Contexts_Service_Page';
//override page menu visibility
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][]
    = 'EXT:contexts/Classes/Service/Page.php:'
    . '&Tx_Contexts_Service_Page';

//override page hash generation
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][]
    = 'EXT:contexts/Classes/Service/Page.php:'
    . '&Tx_Contexts_Service_Page->createHashBase';

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['contexts']
    = 'EXT:contexts/Classes/Service/Tsfe.php:'
    . '&Tx_Contexts_Service_Tsfe->initFEuser';
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates']['contexts'] = 'EXT:contexts/Classes/Service/Install.php:Tx_Contexts_Service_Install';


//add tree icons
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][]
    = 'EXT:contexts/Classes/Service/Icon.php:'
    . '&Tx_Contexts_Service_Icon';

if (isset($TYPO3_CONF_VARS['SYS']['compat_version'])
    && t3lib_div::int_from_ver($TYPO3_CONF_VARS['SYS']['compat_version']) >= 6002000
) {
    // add some hooks
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']['contexts']
        = 'EXT:contexts/Classes/Service/Tsfe.php:'
        . '&Tx_Contexts_Service_Tsfe->checkEnableFields';

} else if (isset($TYPO3_CONF_VARS['SYS']['compat_version'])
    && t3lib_div::int_from_ver($TYPO3_CONF_VARS['SYS']['compat_version']) < 6002000
    && t3lib_div::int_from_ver($TYPO3_CONF_VARS['SYS']['compat_version']) > 4007000
) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController'] = array(
        'className' => 'ux_tslib_fe',
    );
} else {
    // XCLASS subclassing override class.tslib_fe
    // versions lower than 6.2 provide no hook for checkEnableFields
    $TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_fe.php']
        = $strContextsPath . 'class.ux_tslib_fe.php';
}


if (TYPO3_MODE == 'FE') {
    //we load that file in ext_tables.php for the backend
    require_once t3lib_extMgm::extPath($_EXTKEY) . 'ext_contexts.php';
    require_once t3lib_extMgm::extPath($_EXTKEY) . 'Library/ts_connector.php';
}
?>
