<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context;

/**
 * Class Setting
 */

/**
 * Class Setting
 */
final class Setting
{
    /**
     * @var AbstractContext
     */
    protected AbstractContext $context;

    /**
     * The uid of the setting record
     * @var int
     */
    protected int $uid;

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
    protected int $foreignUid;

    /**
     * The name of the setting
     * @var string
     */
    protected $name;

    /**
     * Whether the record is enabled by this setting
     * @var bool
     */
    protected bool $enabled;

    /**
     * @param AbstractContext $context
     * @param array           $row
     */
    public function __construct(AbstractContext $context, array $row)
    {
        $this->context = $context;
        $this->uid = (int) $row['uid'];
        $this->foreignTable = $row['foreign_table'];
        $this->name = $row['name'];
        $this->foreignUid = (int) $row['foreign_uid'];
        $this->enabled = (bool)$row['enabled'];
    }

    /**
     * Create a context settings object from flat data
     *
     * @return Setting|null NULL when not enabled/disabled
     */
    public static function fromFlatData(
        AbstractContext $context,
        $table, $setting, $arFlatColumns, $arRow
    ): ?Setting {
        $bDisabled = str_contains(
            ',' . $arRow[$arFlatColumns[0]] . ',',
            ',' . $context->getUid() . ','
        );
        $bEnabled = str_contains(
            ',' . $arRow[$arFlatColumns[1]] . ',',
            ',' . $context->getUid() . ','
        );

        if (!$bEnabled && !$bDisabled) {
            return null;
        }

        $arDummyRow = [
            'uid'  => null,
            'name' => $setting,
            'foreign_table' => $table,
            'foreign_uid'   => null,
            'enabled' => $bEnabled
        ];
        return new self($context, $arDummyRow);
    }

    /**
     * @return bool
     */
    public function isDefaultSetting(): bool
    {
        return !$this->uid;
    }

    /**
     * @return AbstractContext
     */
    public function getContext(): AbstractContext
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /**
     * @return int
     */
    public function getForeignUid(): int
    {
        return $this->foreignUid;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }
}
