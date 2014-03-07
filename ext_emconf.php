<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "contexts".
 *
 * Auto generated 07-03-2014 14:35
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Multi-channel contexts',
	'description' => 'Contexts for TYPO3 - can be used to modify page behaviour in the frontend based on several conditions',
	'category' => 'misc',
	'author' => 'Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag',
	'author_email' => 'typo3.org@netresearch.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'top',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'Netresearch GmbH & Co.KG',
	'version' => '0.4.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'contexts_geolocation' => '',
			'contexts_wurfl' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:48:{s:9:"ChangeLog";s:4:"e058";s:21:"class.ux_tslib_fe.php";s:4:"5f58";s:16:"ext_autoload.php";s:4:"f838";s:16:"ext_contexts.php";s:4:"098d";s:12:"ext_icon.gif";s:4:"d6c7";s:17:"ext_localconf.php";s:4:"e09b";s:14:"ext_tables.php";s:4:"6cd8";s:14:"ext_tables.sql";s:4:"c85c";s:29:"icon_tx_contexts_contexts.gif";s:4:"d6c7";s:10:"README.rst";s:4:"e0f9";s:12:"Settings.yml";s:4:"ae55";s:7:"tca.php";s:4:"eb80";s:19:"Classes/Backend.php";s:4:"b19a";s:21:"Classes/Exception.php";s:4:"b002";s:29:"Classes/Api/Configuration.php";s:4:"7e56";s:22:"Classes/Api/Record.php";s:4:"6327";s:28:"Classes/Context/Abstract.php";s:4:"9f89";s:29:"Classes/Context/Container.php";s:4:"2c0d";s:27:"Classes/Context/Factory.php";s:4:"14cb";s:27:"Classes/Context/Setting.php";s:4:"cee6";s:36:"Classes/Context/Type/Combination.php";s:4:"3468";s:32:"Classes/Context/Type/Default.php";s:4:"2541";s:31:"Classes/Context/Type/Domain.php";s:4:"4432";s:33:"Classes/Context/Type/GetParam.php";s:4:"0fdb";s:27:"Classes/Context/Type/Ip.php";s:4:"149c";s:63:"Classes/Context/Type/Combination/LogicalExpressionEvaluator.php";s:4:"a962";s:73:"Classes/Context/Type/Combination/LogicalExpressionEvaluator/Exception.php";s:4:"582b";s:45:"Classes/Context/Type/GetParam/TsfeService.php";s:4:"3107";s:27:"Classes/Service/Install.php";s:4:"957e";s:24:"Classes/Service/Page.php";s:4:"1643";s:23:"Classes/Service/Tca.php";s:4:"6b1a";s:27:"Classes/Service/Tcemain.php";s:4:"82d6";s:24:"Classes/Service/Tsfe.php";s:4:"40e2";s:50:"Configuration/flexform/ContextType/Combination.xml";s:4:"a821";s:45:"Configuration/flexform/ContextType/Domain.xml";s:4:"9b14";s:47:"Configuration/flexform/ContextType/GetParam.xml";s:4:"40b6";s:41:"Configuration/flexform/ContextType/Ip.xml";s:4:"0d38";s:39:"Resources/Private/Language/flexform.xml";s:4:"f066";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"2029";s:37:"Resources/Private/csh/Combination.xml";s:4:"0601";s:34:"Resources/Public/StyleSheet/be.css";s:4:"1db5";s:17:"tests/phpunit.xml";s:4:"368c";s:18:"tests/TestBase.php";s:4:"cc35";s:38:"tests/Classes/Context/AbstractTest.php";s:4:"e3e6";s:46:"tests/Classes/Context/Type/CombinationTest.php";s:4:"3103";s:43:"tests/Classes/Context/Type/GetParamTest.php";s:4:"a241";s:37:"tests/Classes/Context/Type/IpTest.php";s:4:"4194";s:73:"tests/Classes/Context/Type/Combination/LogicalExpressionEvaluatorTest.php";s:4:"b508";}',
	'comment' => 'First public version',
	'suggests' => array(
	),
);

?>