<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['contexts'] = 'EXT:contexts/Classes/Service/Tcemain.php:Tx_Contexts_Service_Tcemain';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns']['contexts'] = 'EXT:contexts/Classes/Service/Page.php:&Tx_Contexts_Service_Page->enableFields';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['contexts'] = 'EXT:contexts/Classes/Service/Tsfe.php:&Tx_Contexts_Service_Tsfe->checkAlternativeIdMethodsPostProc';
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates']['contexts'] = 'EXT:contexts/Classes/Service/Install.php:Tx_Contexts_Service_Install';
?>