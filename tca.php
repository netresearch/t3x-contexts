<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_contexts_contexts'] = array(
	'ctrl' => $TCA['tx_contexts_contexts']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,alias'
	),
	'feInterface' => $TCA['tx_contexts_contexts']['feInterface'],
	'columns' => array(
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'alias' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.alias',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'alphanum,nospace,unique',
			)
		),
		'default_rules' => array(
		    'config' => array(
		        'type' => 'passthrough'
		    )
		)
	),
	'types' => array(
		'0' => array('showitem' => '--div--;LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.general,title;;;;2-2-2, alias;;;;3-3-3, --div--;LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.defaults')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>