<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$lf = 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml';
$TCA['tx_contexts_contexts'] = array(
    'ctrl' => $TCA['tx_contexts_contexts']['ctrl'],
    'interface' => array(
        'showRecordFieldList' => 'title,alias,type'
    ),
    'feInterface' => $TCA['tx_contexts_contexts']['feInterface'],
    'columns' => array(
        'title' => array(
            'exclude' => 0,
            'label' => $lf . ':tx_contexts_contexts.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            )
        ),
        'alias' => array(
            'exclude' => 0,
            'label' => $lf . ':tx_contexts_contexts.alias',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'alphanum,nospace,unique',
            )
        ),
        'type' => array(
            'exclude' => 0,
            'label' => $lf . ':tx_contexts_contexts.type',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array($lf . ':tx_contexts_contexts.type.select_type', '')
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'type_conf' => array(
            'exclude' => 0,
            'displayCond' => 'FIELD:type:REQ:true',
            'label' => $lf . ':tx_contexts_contexts.type_conf',
            'config' => array(
                'type' => 'flex',
                'ds_pointerField' => 'type',
                'ds' => array()
            )
        ),
        'default_settings' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        )
    ),
    'types' => array(
        '0' => array('showitem' => '--div--;LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.general,title;;;;2-2-2, alias;;;;3-3-3,type,type_conf, --div--;LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.defaults')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
?>
