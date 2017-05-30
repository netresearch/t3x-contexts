<?php

$lf = 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml';

return [
    'ctrl' => [
        'title'          => $lf.':tx_contexts_contexts',
        'label'          => 'title',
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'cruser_id'      => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete'         => 'deleted',
        'enablecolumns'  => [
            'disabled' => 'disabled',
        ],
        'requestUpdate' => 'type',
        'adminOnly'     => 1,
        'rootLevel'     => -1,
        'iconfile'      => 'EXT:contexts/Resources/Public/Icons/tx_contexts_contexts.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,alias,type,invert,use_session,disabled,hide_in_backend',
    ],
    'columns' => [
        'title' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.title',
            'config'  => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ],
        ],
        'disabled' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.disable',
            'config'  => [
                'type' => 'check',
            ],
        ],
        'hide_in_backend' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.hide_in_backend',
            'config'  => [
                'type' => 'check',
            ],
        ],
        'alias' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.alias',
            'config'  => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'alphanum_x,nospace,unique,lower',
            ],
        ],
        'type' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.type',
            'config'  => [
                'type' => 'select',
//                'items' => array(
//                    array($lf . ':tx_contexts_contexts.type.select_type', '')
//                ),
                'size'     => 1,
                'maxitems' => 1,
            ],
        ],
        'type_conf' => [
            'exclude'     => 0,
            'displayCond' => 'FIELD:type:REQ:true',
            'label'       => $lf.':tx_contexts_contexts.type_conf',
            'config'      => [
                'type'            => 'flex',
                'ds_pointerField' => 'type',
                'ds'              => [],
            ],
        ],
        'invert' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.invert',
            'config'  => [
                'type'    => 'check',
                'default' => 0,
            ],
        ],
        'use_session' => [
            'exclude' => 0,
            'label'   => $lf.':tx_contexts_contexts.use_session',
            'config'  => [
                'type'    => 'check',
                'default' => 1,
            ],
        ],
        'default_settings' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '--div--;'.$lf.':tx_contexts_contexts.general'
                .',title'
                .',--palette--;'.$lf.':tx_contexts_contexts.visibility;visibility'
                .',alias,type,type_conf,invert,'
                .'use_session'
                .',--div--;'.$lf.':tx_contexts_contexts.defaults',
        ],
    ],
    'palettes' => [
        'visibility' => ['showitem' => 'disabled,hide_in_backend', 'canNotCollapse' => 1],
    ],
];
