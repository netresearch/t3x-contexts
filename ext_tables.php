<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_contexts_contexts.type_conf.combination',
    'EXT:contexts/Resources/Private/csh/Combination.xml'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_contexts_contexts');


$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    'TYPO3\CMS\Core\Imaging\IconRegistry'
);
$iconRegistry->registerIcon(
    'extensions-contexts-status-overlay-contexts',
    'TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider',
    array('source' => 'EXT:contexts/Resources/Public/Icons/overlay-contexts.png')
);



$GLOBALS['TBE_STYLES']['skins']['contexts'] = array (
    'name' => 'contexts',
    'stylesheetDirectories' => array(
        'css' => 'EXT:contexts/Resources/Public/StyleSheet'
    )
);
