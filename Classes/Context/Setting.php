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
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
final class Setting
{
    /**
     */
    protected AbstractContext $context;

    /**
     * The uid of the setting record
     */
    protected int $uid;

    /**
     * The name of table the setting is for
     */
    protected string $foreignTable;

    /**
     * The uid of the record the setting is for
     * (0 for default setting)
     */
    protected int $foreignUid;

    /**
     * The name of the setting
     */
    protected string $name;

    /**
     * Whether the record is enabled by this setting
     */
    protected bool $enabled;

    /**
     */
    public function __construct(AbstractContext $context, array $row)
    {
        $this->context = $context;
        $this->uid = (int) $row['uid'];
        $this->foreignTable = $row['foreign_table'];
        $this->name = $row['name'];
        $this->foreignUid = (int) $row['foreign_uid'];
        $this->enabled = (bool) $row['enabled'];
    }

    /**
     * Create a context settings object from flat data
     *
     * @param string          $table         Database table name
     * @param string          $setting       Setting name
     * @param array           $arRow         Database row
     *
     * @return Setting|null NULL when not enabled/disabled
     */
    public static function fromFlatData(
        AbstractContext $context,
        string $table,
        string $setting,
        array $arFlatColumns,
        array $arRow,
    ): ?Setting {
        $bDisabled = str_contains(
            ',' . $arRow[$arFlatColumns[0]] . ',',
            ',' . $context->getUid() . ',',
        );
        $bEnabled = str_contains(
            ',' . $arRow[$arFlatColumns[1]] . ',',
            ',' . $context->getUid() . ',',
        );

        if (!$bEnabled && !$bDisabled) {
            return null;
        }

        $arDummyRow = [
            'uid' => null,
            'name' => $setting,
            'foreign_table' => $table,
            'foreign_uid' => null,
            'enabled' => $bEnabled,
        ];

        return new self($context, $arDummyRow);
    }

    /**
     */
    public function isDefaultSetting(): bool
    {
        return $this->uid === 0;
    }

    /**
     */
    public function getContext(): AbstractContext
    {
        return $this->context;
    }

    /**
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /**
     */
    public function getForeignUid(): int
    {
        return $this->foreignUid;
    }

    /**
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }
}
