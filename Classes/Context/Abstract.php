<?php
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

	private $settings = array();

	public function __construct($row)
	{
	    $this->uid = (int) $row['uid'];
	    $this->type = $row['type'];
	    $this->title = $row['title'];
	    $this->alias = $row['alias'];
	}

	final public function getSetting($table, $uid, $field = Tx_Contexts_Api_Configuration::DEFAULT_FIELD)
	{
	    $settingKey = $table.'-'.$field.'-'.$uid;
	    if (array_key_exists($settingKey, $this->settings)) {
	        return $this->settings[$settingKey];
	    }

    	/* @var $db t3lib_db */
	    $db = $GLOBALS['TYPO3_DB'];

	    $where = 'context_uid = '.$this->uid;
	    $where .= " AND foreign_table = '$table' AND foreign_field = '$field' AND foreign_uid = '$uid'";
        $row = $db->exec_SELECTgetSingleRow('*', 'tx_contexts_settings', $where);

        if ($row) {
            $setting = new Tx_Contexts_Context_Setting($this, $row);
        } else {
            $setting = null;
        }

        return $this->settings[$settingKey] = $setting;
	}

	final public function hasSetting($table, $uid, $field = Tx_Contexts_Api_Configuration::DEFAULT_FIELD)
	{
	    return $this->getSetting($table, $uid, $field) ? true : false;
	}

	abstract public function match();

	/**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

	/**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

	/**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

	/**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}