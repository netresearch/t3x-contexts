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
        'enablecolumns' => array(
            'disabled' => 'disabled'
        ),
        'requestUpdate' => 'type',
        'adminOnly' => 1,
        'rootLevel' => -1,
        'dividers2tabs' => 1,
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_contexts_contexts.gif',
    ),
);
t3lib_extMgm::addLLrefForTCAdescr(
    'tx_contexts_contexts.type_conf.combination',
    'EXT:contexts/Resources/Private/csh/Combination.xml'
);
t3lib_extMgm::allowTableOnStandardPages('tx_contexts_contexts');

require_once t3lib_extMgm::extPath($_EXTKEY) . 'tca.php';
if (TYPO3_MODE == 'BE') {
    //all other modes did load it already
    include_once t3lib_extMgm::extPath($_EXTKEY) . 'ext_contexts.php';
}

$dir = t3lib_extMgm::extRelPath($_EXTKEY);
$icons = array(
    'status-overlay-contexts'     => $dir . 'overlay-contexts.png',
);
t3lib_SpriteManager::addSingleIcons($icons, 'contexts');
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'][150] = 'contexts';
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames']['contexts']
    = 'extensions-contexts-status-overlay-contexts';
?>
