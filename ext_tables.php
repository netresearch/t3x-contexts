<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
$TCA['tx_contexts_contexts'] = array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts',
        'label'     => 'title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'requestUpdate' => 'type',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'dividers2tabs' => 1,
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_contexts_contexts.gif',
    ),
);
require_once t3lib_extMgm::extPath($_EXTKEY) . 'tca.php';
if (TYPO3_MODE == 'BE') {
    //all other modes did load it already
    require_once t3lib_extMgm::extPath($_EXTKEY) . 'ext_contexts.php';
}


Tx_Contexts_Api_Configuration::addToTca($_EXTKEY, 'pages', array(
	'menu_visibility' => 'LLL:'.Tx_Contexts_Api_Configuration::LANG_FILE.':tx_contexts_menu_visibility'
));
Tx_Contexts_Api_Configuration::addToTca($_EXTKEY, 'tt_content');
?>