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
	'version' => '0.5.0',
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
	'_md5_values_when_last_written' => 'a:59:{s:9:"build.xml";s:4:"64f8";s:9:"ChangeLog";s:4:"4c6e";s:21:"class.ux_tslib_fe.php";s:4:"5fa3";s:16:"ext_autoload.php";s:4:"d8e2";s:16:"ext_contexts.php";s:4:"3b91";s:12:"ext_icon.gif";s:4:"d6c7";s:17:"ext_localconf.php";s:4:"f1d4";s:14:"ext_tables.php";s:4:"8dc2";s:14:"ext_tables.sql";s:4:"087f";s:29:"icon_tx_contexts_contexts.gif";s:4:"d6c7";s:20:"overlay-contexts.png";s:4:"9252";s:10:"README.rst";s:4:"c4ef";s:12:"Settings.yml";s:4:"ae55";s:7:"tca.php";s:4:"6b8b";s:19:"Classes/Backend.php";s:4:"b19a";s:21:"Classes/Exception.php";s:4:"b002";s:29:"Classes/Api/Configuration.php";s:4:"6878";s:30:"Classes/Api/ContextMatcher.php";s:4:"4524";s:22:"Classes/Api/Record.php";s:4:"6327";s:28:"Classes/Context/Abstract.php";s:4:"39fa";s:29:"Classes/Context/Container.php";s:4:"a668";s:27:"Classes/Context/Factory.php";s:4:"14cb";s:27:"Classes/Context/Setting.php";s:4:"0e31";s:36:"Classes/Context/Type/Combination.php";s:4:"3468";s:32:"Classes/Context/Type/Default.php";s:4:"2541";s:31:"Classes/Context/Type/Domain.php";s:4:"4432";s:33:"Classes/Context/Type/GetParam.php";s:4:"0fdb";s:35:"Classes/Context/Type/HttpHeader.php";s:4:"f487";s:27:"Classes/Context/Type/Ip.php";s:4:"149c";s:32:"Classes/Context/Type/Session.php";s:4:"bb72";s:63:"Classes/Context/Type/Combination/LogicalExpressionEvaluator.php";s:4:"9234";s:73:"Classes/Context/Type/Combination/LogicalExpressionEvaluator/Exception.php";s:4:"582b";s:45:"Classes/Context/Type/GetParam/TsfeService.php";s:4:"3107";s:24:"Classes/Service/Icon.php";s:4:"831d";s:27:"Classes/Service/Install.php";s:4:"957e";s:24:"Classes/Service/Page.php";s:4:"1643";s:23:"Classes/Service/Tca.php";s:4:"3c86";s:27:"Classes/Service/Tcemain.php";s:4:"bc8d";s:24:"Classes/Service/Tsfe.php";s:4:"40e2";s:41:"Classes/ViewHelpers/MatchesViewHelper.php";s:4:"b6dd";s:50:"Configuration/flexform/ContextType/Combination.xml";s:4:"a821";s:45:"Configuration/flexform/ContextType/Domain.xml";s:4:"9b14";s:47:"Configuration/flexform/ContextType/GetParam.xml";s:4:"40b6";s:49:"Configuration/flexform/ContextType/HttpHeader.xml";s:4:"4699";s:41:"Configuration/flexform/ContextType/Ip.xml";s:4:"0d38";s:46:"Configuration/flexform/ContextType/Session.xml";s:4:"b5db";s:24:"Library/ts_connector.php";s:4:"952c";s:39:"Resources/Private/Language/flexform.xml";s:4:"388c";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"ff3e";s:37:"Resources/Private/csh/Combination.xml";s:4:"0601";s:34:"Resources/Public/StyleSheet/be.css";s:4:"1db5";s:19:"tests/bootstrap.php";s:4:"4b70";s:17:"tests/phpunit.xml";s:4:"3704";s:18:"tests/TestBase.php";s:4:"7770";s:38:"tests/Classes/Context/AbstractTest.php";s:4:"cea2";s:46:"tests/Classes/Context/Type/CombinationTest.php";s:4:"0655";s:43:"tests/Classes/Context/Type/GetParamTest.php";s:4:"6e3c";s:37:"tests/Classes/Context/Type/IpTest.php";s:4:"0e00";s:73:"tests/Classes/Context/Type/Combination/LogicalExpressionEvaluatorTest.php";s:4:"73bd";}',
	'comment' => 'First public version',
	'suggests' => array(
	),
);

?>