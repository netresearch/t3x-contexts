<?php
namespace Netresearch\Contexts\Context;

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

final class Setting
{
    /**
     * @var AbstractContext
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
     * @var bool
     */
    protected $enabled;

    public function __construct(AbstractContext $context, array $row)
    {
        $this->context = $context;
        $this->uid = (int) $row['uid'];
        $this->foreignTable = $row['foreign_table'];
        $this->name = $row['name'];
        $this->foreignUid = (int) $row['foreign_uid'];
        $this->enabled = $row['enabled'] ? true : false;
    }

    /**
     * Create a context settings object from flat data
     *
     * @return Setting|null NULL when not enabled/disabled
     */
    public static function fromFlatData(
        AbstractContext $context,
        $table, $setting, $arFlatColumns, $arRow
    ) {
        $bDisabled = strpos(
            ',' . $arRow[$arFlatColumns[0]] . ',',
            ',' . $context->getUid() . ','
        ) !== false;
        $bEnabled = strpos(
            ',' . $arRow[$arFlatColumns[1]] . ',',
            ',' . $context->getUid() . ','
        ) !== false;

        if (!$bEnabled && !$bDisabled) {
            return null;
        }

        $arDummyRow = array(
            'uid'  => null,
            'name' => $setting,
            'foreign_table' => $table,
            'foreign_uid'   => null,
            'enabled' => $bEnabled
        );
        return new self($context, $arDummyRow);
    }

    public function isDefaultSetting()
    {
        return !$this->uid;
    }

    /**
     * @return AbstractContext
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
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}
