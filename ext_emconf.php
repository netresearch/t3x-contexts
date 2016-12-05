<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "contexts".
 *
 * Auto generated 21-04-2016 07:58
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Multi-channel contexts',
	'description' => 'Contexts for TYPO3 - can be used to modify page behaviour in the frontend based on several conditions',
	'category' => 'misc',
	'author' => 'Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag, Benni Mack',
	'author_email' => 'typo3.org@netresearch.de',
	'priority' => 'top',
	'state' => 'beta',
	'clearCacheOnLoad' => 1,
	'author_company' => 'Netresearch GmbH & Co.KG',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-8.9.99',
			'php'   => '5.3.0-7.0.12'
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'contexts_geolocation' => '',
			'contexts_wurfl' => '',
		),
	),
	'suggests' => array(
	),
);
