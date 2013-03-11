<?php
final class Tx_Contexts_Context_Rule
{
    /**
     * @var Tx_Contexts_Context_Abstract
     */
    protected $context;

	/**
	 * The uid of the rule record
	 * @var int
	 */
	protected $uid;

	/**
	 * The name of table the rule is for
	 * @var string
	 */
	protected $foreignTable;

	/**
	 * The name of the field the rule is for
	 * @var string
	 */
	protected $foreignField;

	/**
	 * The uid of the record the rule is for
	 * (0 for default rule)
	 * @var int
	 */
	protected $foreignUid;

	/**
	 * Whether the record is enabled by this rule
	 * @var boolean
	 */
	protected $enabled;

	public function __construct(Tx_Contexts_Context_Abstract $context, array $row)
	{
	    $this->context = $context;
	    $this->uid = (int) $row['uid'];
	    $this->foreignTable = $row['foreign_table'];
	    $this->foreignField = $row['foreign_field'];
	    $this->foreignUid = (int) $row['foreign_uid'];
	    $this->enabled = $row['enabled'] ? true : false;
	}

	public function isDefaultRule()
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
     * @return string
     */
    public function getForeignField()
    {
        return $this->foreignField;
    }

	/**
     * @return int
     */
    public function getForeignUid()
    {
        return $this->foreignUid;
    }

	/**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}