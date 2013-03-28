<?php
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

/**
 * Abstract context - must be extended by the context types
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
abstract class Tx_Contexts_Context_Abstract
{
    /**
     * @var int
     */
    protected $uid;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    protected $conf;

    private $settings = array();

    /**
     * Constructor - set the values from database row
     *
     * @param array $arRow Database context row
     * @return void
     */
    public function __construct($arRow = array())
    {
        if (!empty($arRow)) {
            $this->uid   = (int) $arRow['uid'];
            $this->type  = $arRow['type'];
            $this->title = $arRow['title'];
            $this->alias = $arRow['alias'];
            if ($arRow['type_conf'] != '') {
                $this->conf  = t3lib_div::xml2array(
                    $arRow['type_conf']
                );
            }
        }
    }

    /**
     * Get a configuration value
     *
     * @param string $fieldName Name of the field
     * @param string $default   The value to use when none was found
     * @param string $sheet     Sheet pointer, eg. "sDEF
     * @param string $lang      Language pointer, eg. "lDEF
     * @param string $value     Value pointer, eg. "vDEF
     * @return string The content
     */
    protected function getConfValue(
        $fieldName, $default = null,
        $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF'
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
     * if one was found
     *
     * @param string $table
     * @param string $setting
     * @param string $uid
     * @return Tx_Contexts_Context_Setting|null
     */
    final public function getSetting($table, $setting, $uid) {
        $settings = $this->getSettings($table, $uid);
        return array_key_exists($setting, $settings) ? $settings[$setting] : null;
    }

    /**
     * Get all settings of one record
     *
     * @param string $table
     * @param int    $uid
     * @return array
     */
    final public function getSettings($table, $uid) {
        $settingsKey = $table . '.' . $uid;

        if (array_key_exists($settingsKey, $this->settings)) {
            return $this->settings[$settingsKey];
        }

        $uids = array($uid);
        if ($uid && !array_key_exists($table . '.0', $this->settings)) {
            $uids[] = 0;
        }
        $where = 'context_uid = ' . $this->uid;
        $where .= " AND foreign_table = '$table'";
        $where .= " AND foreign_uid IN ('" . implode("','", $uids) . "')";
        $rows = (array) Tx_Contexts_Api_Configuration::getDb()->exec_SELECTgetRows('*', 'tx_contexts_settings', $where);

        foreach ($uids as $uid) {
            $this->settings[$table . '.' . $uid] = array();
        }

        foreach ($rows as $row) {
            $this->settings[$table . '.' . $row['foreign_uid']][$row['name']] =
                new Tx_Contexts_Context_Setting($this, $row);
        }

        return $this->settings[$settingsKey];
    }

    /**
     * Determines whether a setting exists for this record
     *
     * @param string $table
     * @param string $setting
     * @param int    $uid
     * @return boolean
     */
    final public function hasSetting($table, $setting, $uid) {
        return $this->getSetting($table, $setting, $uid) ? true : false;
    }

    /**
     * This function gets called when the current contexts are determined.
     *
     * @param array $arDependencies Array of context objects that are
     *                              dependencies of this context
     * @return boolean True when your context matches, false if not
     */
    abstract public function match($arDependencies);

    /**
     * Get the uid of this context
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Get the type of this context
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the title of this context
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the alias of this context
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Return all context UIDs this context depends on.
     *
     * @return array Array of context uids this context depends on.
     *               Key is the UID, value is "true"
     */
    public function getDependencies()
    {
        return array();
    }
}
?>