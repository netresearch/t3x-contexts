<?php
namespace Netresearch\Contexts\Context;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use Netresearch\Contexts\Api\Configuration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Abstract context - must be extended by the context types
 *
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
abstract class AbstractContext
{
    /**
     * Key for the ip forward header
     *
     * @var string
     */
    const HTTP_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';

    /**
     * Key for the ip remote address
     *
     * @var string
     */
    const REMOTE_ADDR = 'REMOTE_ADDR';

    /**
     * Uid of context.
     *
     * @var int
     */
    protected $uid;

    /**
     * Type of context.
     *
     * @var string
     */
    protected $type;

    /**
     * Title of context.
     *
     * @var string
     */
    protected $title;

    /**
     * Alias of context.
     *
     * @var string
     */
    protected $alias;

    /**
     * Unix timestamp of last record modification.
     *
     * @var int
     */
    protected $tstamp;

    /**
     * Invert the match result.
     *
     * @var bool
     */
    protected $invert = false;

    /**
     * Store match result in user session.
     *
     * @var bool
     */
    protected $use_session = true;

    /**
     * Context configuration.
     *
     * @var array
     */
    protected $conf;

    /**
     * List of all context settings.
     *
     * @var array
     */
    private $settings = array();

    /**
     * Constructor - set the values from database row.
     * @var bool
     */
    protected $disabled;

    /**
     * Hide Context in backend
     *
     * @var bool
     */
    protected $bHideInBackend = false;

    /**
     * Constructor - set the values from database row.
     *
     * @param array $arRow Database context row
     */
    public function __construct($arRow = array())
    {
        //check TSFE is set
        //prevent Exceptions in eID
        $this->initTsfe();

        if (!empty($arRow)) {
            $this->uid            = (int) $arRow['uid'];
            $this->type           = $arRow['type'];
            $this->title          = $arRow['title'];
            $this->alias          = $arRow['alias'];
            $this->tstamp         = $arRow['tstamp'];
            $this->invert         = $arRow['invert'];
            $this->use_session    = $arRow['use_session'];
            $this->disabled       = $arRow['disabled'];
            $this->bHideInBackend = (bool) $arRow['hide_in_backend'];

            if ($arRow['type_conf'] != '') {
                $this->conf = GeneralUtility::xml2array($arRow['type_conf']);
            }
        }
    }

    /**
     * Get a configuration value.
     *
     * @param string $fieldName Name of the field
     * @param string $default   The value to use when none was found
     * @param string $sheet     Sheet pointer, eg. "sDEF
     * @param string $lang      Language pointer, eg. "lDEF
     * @param string $value     Value pointer, eg. "vDEF
     *
     * @return string The content
     */
    protected function getConfValue(
        $fieldName,
        $default = null,
        $sheet   = 'sDEF',
        $lang    = 'lDEF',
        $value   = 'vDEF'
    ) {
        if (!isset($this->conf['data'][$sheet][$lang])) {
            return $default;
        }

        $ldata = $this->conf['data'][$sheet][$lang];

        if (!isset($ldata[$fieldName][$value])) {
            return $default;
        }

        return $ldata[$fieldName][$value];
    }

    /**
     * Query a setting record and retrieve the value object
     * if one was found.
     *
     * @param string $table   Database table name
     * @param string $setting Setting name
     * @param string $uid     Record UID
     * @param array  $arRow   Database row for the given UID.
     *                        Useful for flat settings.
     *
     * @return Setting|null NULL when not enabled
     *                                          and not disabled
     */
    final public function getSetting($table, $setting, $uid, $arRow = null)
    {
        if ($arRow !== null) {
            //if it's a flat column, use the settings directly from the
            // database row instead of relying on the tx_contexts_settings
            // table
            $arFlatColumns = Configuration::getFlatColumns(
                $table, $setting
            );
            if (isset($arRow[$arFlatColumns[0]])
                && isset($arRow[$arFlatColumns[1]])
            ) {
                return Setting::fromFlatData(
                    $this, $table, $setting, $arFlatColumns, $arRow
                );
            }
        }

        $settings = $this->getSettings($table, $uid);

        return array_key_exists($setting, $settings)
            ? $settings[$setting]
            : null;
    }

    /**
     * Get all settings of one record.
     *
     * @param string $table Database table
     * @param int    $uid   Record UID
     *
     * @return array Array of settings
     *               Key is the context column name (e.g. "tx_contexts_nav")
     *               Value is a Tx_Contexts_Context_Setting object
     */
    final public function getSettings($table, $uid)
    {
        $settingsKey = $table . '.' . $uid;

        if (array_key_exists($settingsKey, $this->settings)) {
            return $this->settings[$settingsKey];
        }

        $uids = array($uid);
        if ($uid && !array_key_exists($table . '.0', $this->settings)) {
            $uids[] = 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_contexts_settings');
        $rows = $queryBuilder->select('*')
            ->from('tx_contexts_settings')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int)$this->uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'foreign_table',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->in(
                    'foreign_uid',
                    $uids
                )
            )
            ->execute()
            ->fetchAll();

        foreach ($uids as $uid) {
            $this->settings[$table . '.' . $uid] = array();
        }

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->settings[$table . '.' . $row['foreign_uid']][$row['name']]
                    = new Setting($this, $row);
            }
        }

        return $this->settings[$settingsKey];
    }

    /**
     * Determines whether a setting exists for this record.
     *
     * @param string $table   Database table
     * @param string $setting Setting name
     * @param int    $uid     Record UID
     *
     * @return bool
     */
    final public function hasSetting($table, $setting, $uid)
    {
        return $this->getSetting($table, $setting, $uid) ? true : false;
    }

    /**
     * This function gets called when the current contexts are determined.
     *
     * @param array $arDependencies Array of context objects that are
     *                              dependencies of this context
     *
     * @return bool True when your context matches, false if not
     */
    abstract public function match(array $arDependencies = array());

    /**
     * Get the uid of this context.
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Get the type of this context.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the title of this context.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the alias of this context.
     *
     * @return string
     */
    public function getAlias()
    {
        return strtolower($this->alias);
    }

    /**
     * Return all context UIDs this context depends on.
     *
     * @param array $arContexts the available contexts
     *
     * @return array Array of context uids this context depends on.
     *               Key is the UID, value is "true"
     */
    public function getDependencies($arContexts)
    {
        return array();
    }

    /**
     * Get the disabled status of this context
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Get hide in backend
     *
     * @return bool true if the context not shown in backend
     */
    public function getHideInBackend()
    {
        return $this->bHideInBackend;
    }

    /**
     * Loads match() result from session if the context is configured so.
     *
     * @return array Array with two values:
     *               0: true: Use the second value as return,
     *                  false: calculate it
     *               1: Return value when 0 is true
     */
    protected function getMatchFromSession()
    {
        $bUseSession = (bool) $this->use_session;

        if (!$bUseSession) {
            return array(false, null);
        }

        $res = $this->getSession();

        if ($res === null) {
            //not set yet
            return array(false, null);
        }
        return array(true, (bool) $res);
    }

    /**
     * Get the contextsession.
     *
     * @return mixed boolean match or null
     */
    protected function getSession()
    {
        return $GLOBALS['TSFE']->fe_user->getKey(
            'ses', 'contexts-' . $this->uid . '-' . $this->tstamp
        );
    }

    /**
     * Stores the current match setting in the session if the type
     * is configured that way.
     *
     * @param bool $bMatch If the context matches
     *
     * @return bool $bMatch value
     */
    protected function storeInSession($bMatch)
    {
        if (!((bool) $this->use_session)) {
            return $bMatch;
        }

        /* @var $GLOBALS['TSFE'] tslib_feuserauth */
        $GLOBALS['TSFE']->fe_user->setKey(
            'ses', 'contexts-' . $this->uid . '-' . $this->tstamp, $bMatch
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();
        return $bMatch;
    }

    /**
     * Init TSFE with FE user
     *
     * @return void
     */
    protected function initTsfe()
    {
        if (!isset($GLOBALS['TSFE']) && TYPO3_MODE === 'FE') {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController',
                $GLOBALS['TYPO3_CONF_VARS'], 0, 0
            );
            $GLOBALS['TSFE']->initFEuser();
        }
    }

    /**
     * Inverts the current match setting if inverting is activated.
     *
     * @param bool $bMatch If the context matches
     *
     * @return bool
     */
    protected function invert($bMatch)
    {
        if ((bool) $this->invert) {
            return !$bMatch;
        }

        return $bMatch;
    }

    /**
     * Set invert flag.
     *
     * @param bool $bInvert True or false
     *
     * @return void
     */
    public function setInvert($bInvert)
    {
        $this->invert = (bool) $bInvert;
    }

    /**
     * Set use session flag.
     *
     * @param bool $bUseSession True or false
     *
     * @return void
     */
    public function setUseSession($bUseSession)
    {
        $this->use_session = (bool) $bUseSession;
    }

    /**
     * Returns the value for the passed key
     *
     * @param string $strKey the key, e.g. REMOTE_ADDR
     *
     * @return string
     */
    protected function getIndpEnv($strKey)
    {
        return GeneralUtility::getIndpEnv(
            $strKey
        );
    }

    /**
     * Returns the clients remote address.
     *
     * @return string
     */
    protected function getRemoteAddress()
    {
        return $this->getIndpEnv(
            self::REMOTE_ADDR
        );
    }
}
