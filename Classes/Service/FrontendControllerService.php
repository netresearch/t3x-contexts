<?php
namespace Netresearch\Contexts\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class FrontendControllerService implements SingletonInterface
{
    protected static $hooksRegistered = false;

    /**
     * Parameters that modify the page hash.
     * Key is the parameter name, value its value.
     *
     * @var array
     */
    protected static $params = array();

    /**
     * GET parameters that are carried across all URLs.
     * Key is the parameter name, value is ignored
     *
     * @var array
     */
    protected static $linkVarParams = array();

    /**
     * Check subpages for context from rootline
     * method is called from hook "hook_checkEnableFields"
     *
     * @param array $arData key: pObj value: TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController (required)
     *                      key: row  value: page record to evaluate
     *                      key: bypassGroupCheck  value: boolean
     *
     * @return bool false if context from root does not match
     */
    public function checkEnableFields($arData)
    {
        $contexts = Container::get();
        $bFindExtendSubPage = 0;
        foreach (array_reverse($arData['pObj']->rootLine) as $page) {
            //check extendtosubpages
            if ($page['extendToSubpages'] == '0' && $bFindExtendSubPage == 0) {
                continue;
            } else {
                $bFindExtendSubPage = 1;
            }

            $arEnable = explode(',', $page['tx_contexts_enable']);
            foreach ($arEnable as $nContextId) {
                if (!isset($contexts[$nContextId]) && $nContextId) {
                    return false;
                }
            }

            $arDisable = explode(',', $page['tx_contexts_disable']);
            foreach ($arDisable as $nContextId) {
                if (isset($contexts[$nContextId]) && $nContextId) {
                    return false;
                }
            }
        }
    }

    /**
     * methods related to the QueryParameterContext
     */

    /**
     * Add an additional parameter to cHash so that caches are specific
     * to the current context combination.
     *
     * @param array &$params Array of parameters
     * @param null  $ref     Empty reference object
     * @return void
     */
    public function createHashBase(&$params, $ref)
    {
        ksort(self::$params);
        $params['hashParameters'][strtolower(__CLASS__)] = serialize(self::$params);
    }

    /**
     * Add current param names to config.linkVars
     *
     * @param array    &$params
     * @param TypoScriptFrontendController $tsfe
     * @return void
     */
    public function configArrayPostProc(&$params, $tsfe)
    {
        $linkVars = $params['config']['linkVars'] . ',' . implode(',', array_keys(self::$linkVarParams));
        $params['config']['linkVars'] = trim($linkVars, ',');
    }

    /**
     * Register a param and it's value and hook into configArrayPostProc and
     * createHashBase/cHashParamsHook
     *
     * @param string $param
     * @param mixed  $value
     * @return void
     */
    public static function registerQueryParameter($param, $value, $addToLinkVars)
    {
        self::$params[$param] = $value;
        if ($addToLinkVars) {
            self::$linkVarParams[$param] = true;
        }

        if (self::$hooksRegistered) {
            return;
        }

        self::$hooksRegistered = true;
        $conf = &$GLOBALS['TSFE']->TYPO3_CONF_VARS;
        $conf['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'][__CLASS__] =
            __CLASS__ . '->configArrayPostProc';
        // override page hash generation
        $conf['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][__CLASS__] =
            __CLASS__ . '->createHashBase';
    }
}
