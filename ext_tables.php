<?php

defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_contexts_contexts.type_conf.combination',
    'EXT:contexts/Resources/Private/csh/Combination.xml'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_contexts_contexts');

// override icon before version 7.5
if (class_exists('TYPO3\CMS\Backend\Sprite\SpriteManager')) {
    $icons = [
        'status-overlay-contexts' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/overlay-contexts.png',
    ];
    \TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, 'contexts');
    $GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'][150] = 'contexts';
    $GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames']['contexts']
        = 'extensions-contexts-status-overlay-contexts';
}
// override icon after 7.5
if (class_exists('TYPO3\CMS\Core\Imaging\IconRegistry')) {
    /* @var $iconRegistry \TYPO3\CMS\Core\Imaging\IconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        'TYPO3\CMS\Core\Imaging\IconRegistry'
    );
    $iconRegistry->registerIcon(
        'extensions-contexts-status-overlay-contexts',
        'TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider',
        ['source' => 'EXT:contexts/Resources/Public/Icons/overlay-contexts.png']
    );
}

$GLOBALS['TBE_STYLES']['skins']['contexts'] = [
    'name'                  => 'contexts',
    'stylesheetDirectories' => [
        'css' => 'EXT:contexts/Resources/Public/css/typo3_backend/',
    ],
];
