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
     * Constructor - set the values (override init() to something
     * on __construct)
     *
     * @param array $row
     * @return void
     */
    final public function __construct($row)
    {
        $this->uid = (int) $row['uid'];
        $this->type = $row['type'];
        $this->title = $row['title'];
        $this->alias = $row['alias'];
        $this->conf = $row['type_conf'];
    }

    /**
     * Override this instead of __construct
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Query a setting record and retrieve the value object
     * if one was found
     *
     * @param string $table
     * @param string $uid
     * @param string $field
     * @return Tx_Contexts_Context_Setting|null
     */
    final public function getSetting($table, $uid, $field = Tx_Contexts_Api_Configuration::DEFAULT_FIELD)
    {
        $settingKey = $table . '-' . $field . '-' . $uid;
        if (array_key_exists($settingKey, $this->settings)) {
            return $this->settings[$settingKey];
        }

        /* @var $db t3lib_db */
        $db = $GLOBALS['TYPO3_DB'];

        $where = 'context_uid = ' . $this->uid;
        $where .= " AND foreign_table = '$table' AND foreign_field = '$field' AND foreign_uid = '$uid'";
        $row = $db->exec_SELECTgetSingleRow('*', 'tx_contexts_settings', $where);

        if ($row) {
            $setting = new Tx_Contexts_Context_Setting($this, $row);
        } else {
            $setting = null;
        }

        return $this->settings[$settingKey] = $setting;
    }

    /**
     * Determines whether a setting exists for this record
     *
     * @param string $table
     * @param string $uid
     * @param string $field
     * @return boolean
     */
    final public function hasSetting($table, $uid, $field = Tx_Contexts_Api_Configuration::DEFAULT_FIELD)
    {
        return $this->getSetting($table, $uid, $field) ? true : false;
    }

    /**
     * This function gets called when the current contexts are
     * determined. Returns
     * * true, when your context matches,
     * * false, when your context doesn't match
     * * null, when your context is not ready to decide that yet
     *   (eg. to let other contexts match first)
     *
     * @return boolean|null
     */
    abstract public function match();

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
}
?>