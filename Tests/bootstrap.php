<?php

const TYPO3_MODE = 'BE';

//we have E_STRICT all over the place :/
error_reporting(error_reporting() & ~E_STRICT);

define('TEST_PATH', __DIR__.'/');

define(
    'PATH_site',
    realpath(TEST_PATH.'../../../../').'/'
);

require_once TEST_PATH.'../../../../typo3/sysext/core/Classes/Core/Bootstrap.php';

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
    ->baseSetup('')
    ->loadConfigurationAndInitialize()
    ->loadTypo3LoadedExtAndExtLocalconf(true)
    ->applyAdditionalConfigurationSettings()
    ->initializeTypo3DbGlobal()
    ->loadExtensionTables();
