<?php
final class Tx_Contexts_Context_Setting
{
    /**
     * @var Tx_Contexts_Context_Abstract
     */
    protected $context;

	/**
	 * The uid of the setting record
	 * @var int
	 */
	protected $uid;

	/**
	 * The name of table the setting is for
	 * @var string
	 */
	protected $foreignTable;

	/**
	 * The uid of the record the setting is for
	 * (0 for default setting)
	 * @var int
	 */
	protected $foreignUid;

	/**
	 * The name of the setting
	 * @var string
	 */
	protected $name;

	/**
	 * Whether the record is enabled by this setting
	 * @var boolean
	 */
	protected $enabled;

	public function __construct(Tx_Contexts_Context_Abstract $context, array $row)
	{
	    $this->context = $context;
	    $this->uid = (int) $row['uid'];
	    $this->foreignTable = $row['foreign_table'];
	    $this->name = $row['name'];
	    $this->foreignUid = (int) $row['foreign_uid'];
	    $this->enabled = $row['enabled'] ? true : false;
	}

	public function isDefaultSetting()
	{
	    return !$this->uid;
	}

	/**
     * @return Tx_Contexts_Context_Abstract
     */
    public function getContext()
    {
        return $this->context;
    }

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
    public function getForeignTable()
    {
        return $this->foreignTable;
    }

	/**
     * @return int
     */
    public function getForeignUid()
    {
        return $this->foreignUid;
    }

	/**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

	/**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}