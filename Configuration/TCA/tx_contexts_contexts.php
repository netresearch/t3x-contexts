<?php
$lf = 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml';

return array(
    'ctrl' => array(
        'title'     => $lf . ':tx_contexts_contexts',
        'label'     => 'title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'disabled'
        ),
        'requestUpdate' => 'type',
        'adminOnly' => 1,
        'rootLevel' => -1,
        'iconfile'  => 'EXT:contexts/Resources/Public/Icons/tx_contexts_contexts.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'title,alias,type,invert,use_session,disabled,hide_in_backend'
    ),
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
        'disabled' => array(
            'exclude' => 0,
            'label' => $lf . ':tx_contexts_contexts.disable',
            'config' => array(
                'type' => 'check',
            )
        ),
        'hide_in_backend' => array(
            'exclude' => 0,
            'label' => $lf . ':tx_contexts_contexts.hide_in_backend',
            'config' => array(
                'type' => 'check',
            )
        ),
        'alias' => array(
            'exclude' => 0,
            'label' => $lf . ':tx_contexts_contexts.alias',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'alphanum_x,nospace,unique,lower',
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
        'invert' => array(
            'exclude' => 0,
            'label'   => $lf . ':tx_contexts_contexts.invert',
            'config'  => array(
                'type'    => 'check',
                'default' => 0
            )
        ),
        'use_session' => array(
            'exclude' => 0,
            'label'   => $lf . ':tx_contexts_contexts.use_session',
            'config'  => array(
                'type'    => 'check',
                'default' => 1
            )
        ),
        'default_settings' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        )
    ),
    'types' => array(
        '0' => array(
            'showitem'
                => '--div--;' . $lf . ':tx_contexts_contexts.general'
                . ',title'
                . ',--palette--;' . $lf . ':tx_contexts_contexts.visibility;visibility'
                . ',alias,type,type_conf,invert,'
                . 'use_session'
                . ',--div--;' . $lf . ':tx_contexts_contexts.defaults'
        )
    ),
    'palettes' => array(
        'visibility' => array('showitem' => 'disabled,hide_in_backend', 'canNotCollapse' => 1)
    )
);
