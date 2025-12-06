<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Api;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * General configuration API
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class Configuration
{
    /**
     * The language file used for labels, added by contexts' extension.
     *
     * @var string
     */
    public const LANG_FILE = 'EXT:contexts/Resources/Private/Language/locallang_db.xlf';

    /**
     * The name of the contexts settings column in TCA
     * (This is a USER column and will be handled by the TCEmain-
     * hooks in Tx_Contexts_Service_Tcemain)
     *
     * @var string
     */
    public const RECORD_SETTINGS_COLUMN = 'tx_contexts_settings';

    /**
     * Add context settings to a specific table. $settings can be
     * any (boolean) settings, each with the name as key and array
     * containing the label, if the setting should be flattened and
     * if it should be used to enable/disable records:
     *
     * <code title="Adding the visibility setting only">
     * \Netresearch\Contexts\Api\Configuration::addToTca($_EXTKEY, 'some_table');
     * </code>
     *
     * <code title="Adding the visibility and another setting">
     * \Netresearch\Contexts\Api\Configuration::addToTca($_EXTKEY, 'some_table', array(
     *     'some_setting' => array(
     *         'label' => 'LLL:langfile:some_setting',
     *         // if the setting should also be stored directly into the records
     *         // 'flatten' => true,
     *         // if the setting is meant to control access to records
     *         // 'enables' => true
     *     )
     * ));
     * </code>
     *
     * The extKey is necessary as it will be collected when you add flat
     * settings. A hook for the EM will then add the required columns on $table
     * on installation/update of your extension in EM.
     * (@param string $extKey      Extension key that ena the table
     *
     * @param string     $table       Table to add settings to
     * @param array|null $settings    Array of settings to register.
     *                                Key is the setting name, value its config
     * @param bool       $addDefaults If an "enableSetting" is added that is
     *                                used to hide/show elements
     *
     *@see Tx_Contexts_Service_Install::appendTableDefinitions())
     *
     */
    public static function enableContextsForTable(
        string $extKey,
        string $table,
        ?array $settings = null,
        bool $addDefaults = true,
    ): void {
        $defaultSettings = [
            'tx_contexts' => [
                'label' => 'LLL:' . self::LANG_FILE . ':tx_contexts_visibility',
                'flatten' => true,
                'enables' => true,
            ],
        ];

        if (!\is_array($settings)) {
            $settings = $addDefaults ? $defaultSettings : [];
        } elseif ($addDefaults) {
            $settings = array_merge($defaultSettings, $settings);
        }

        self::addToEnableSettings($table, $settings);
        self::addToFlatColumns($table, $settings);

        self::addToTcaColumns($table, $settings);
        self::addToExtensionFlatSettings($extKey, $table, $settings);
    }

    /**
     * Registers a context type with the context extension.
     * Makes it available in the context type dropdown
     * in the context record editor in the backend.
     *
     * @param string $key      Key used to assign the new type inside the
     *                         context list
     * @param string $title    Title of context inside dropdown
     * @param string $class    Name of class implementing context type
     * @param string $flexFile Flexform configuration file of context type
     *
     */
    public static function registerContextType(
        string $key,
        string $title,
        string $class,
        string $flexFile,
    ): void {
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'][$key] = [
            'title' => $title,
            'class' => $class,
            'flexFile' => $flexFile,
        ];

        if (isset($GLOBALS['TCA']['tx_contexts_contexts']['columns']['type'])) {
            $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type']['config']
                ['items'][] = ['label' => $title, 'value' => $key];
            $GLOBALS['TCA']['tx_contexts_contexts']['columns']['type_conf']['config']
                ['ds'][$key] = $flexFile;
        }
    }

    /**
     * Get the registered context types.
     *
     */
    public static function getContextTypes(): array
    {
        return (array) $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'];
    }

    /**
     * Get the flat columns for the flat settings by table or table and setting.
     * The flat columns array will contain the disabled column in key 0 and the
     * enabled column in key 1
     *
     * @param string      $table   Table name
     * @param string|null $setting Setting name
     *
     * @return array When $setting is NULL: Array of arrays.
     *                   Each array contains a pair of column names.
     *                   First name is the disable column,
     *                   second the enable column name.
     *               When $setting is given, a pair of column names is
     *                   returned.
     *                   First name is the disable column,
     *                   second the enable column name.
     */
    public static function getFlatColumns(string $table, ?string $setting = null): array
    {
        if (isset($GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['flatSettings'])) {
            if ($setting !== null) {
                return (array) ($GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['flatSettings'][$setting] ?? []);
            }

            return (array) $GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['flatSettings'];
        }

        return [];
    }

    /**
     * Getter for $extensionFlatSettings
     *
     * @param string $strExtKey extension key
     *
     */
    public static function getExtensionFlatSettings(string $strExtKey): array
    {
        if (!isset($GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'][$strExtKey])) {
            return [];
        }

        return (array) $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'];
    }

    /**
     * Get the settings names which should control access to records.
     *
     * @param string $table Table name
     *
     */
    public static function getEnableSettings(string $table): array
    {
        return (array) ($GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['enableSettings'] ?? []);
    }

    /**
     * Determine if a setting is a setting to flatten
     * (enableSettings currently require to be flattened)
     *
     * @param array $config Configuration
     *
     */
    protected static function isFlatSetting(array $config): bool
    {
        return isset($config['flatten']) || isset($config['enables']);
    }

    /**
     * Determine which of the settings should be flattened and add them to the
     * $table's $flatSettings
     *
     * @param string $table    Table name
     * @param array  $settings Settings
     *
     */
    protected static function addToFlatColumns(string $table, array $settings): void
    {
        $flatSettings = $GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['flatSettings'] ?? [];

        foreach ($settings as $setting => $config) {
            if (self::isFlatSetting($config)) {
                $flatSettings[$setting] = [
                    0 => $setting . '_disable',
                    1 => $setting . '_enable',
                ];
            }
        }

        $GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['flatSettings'] = $flatSettings;
    }

    /**
     * Same as self::addToFlatSettings() but also tracks the $extKey (needed to
     * determine the database columns that need to be created on extension
     * installation/update)
     *
     * @param string $extKey   Extension key
     * @param string $table    Table name
     * @param array  $settings Settings
     *
     */
    protected static function addToExtensionFlatSettings(
        string $extKey,
        string $table,
        array $settings,
    ): void {
        $flatSettings = [];

        foreach ($settings as $setting => $config) {
            if (self::isFlatSetting($config)) {
                $flatSettings[] = $setting;
            }
        }

        $extensionFlatSettings = $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'] ?? [];

        if (!\array_key_exists($extKey, $extensionFlatSettings)) {
            $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'][$extKey] = [
                $table => $flatSettings,
            ];
        } elseif (\array_key_exists($table, $extensionFlatSettings[$extKey] ?? [])) {
            $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'][$extKey][$table]
                = array_unique(
                    array_merge(
                        $extensionFlatSettings[$extKey][$table],
                        $settings,
                    ),
                );
        } else {
            $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings'][$extKey][$table] = $flatSettings;
        }
    }

    /**
     * Set internal enableSettings variable that is used by the record and
     * page API.
     *
     * @param string $table    Table name
     * @param array  $settings Settings
     *
     */
    protected static function addToEnableSettings(string $table, array $settings): void
    {
        $enableSettings = $GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['enableSettings'] ?? [];

        foreach ($settings as $setting => $config) {
            if (
                isset($config['enables'])
                && !\in_array($setting, $enableSettings, true)
            ) {
                $enableSettings[] = $setting;
            }
        }

        $GLOBALS['TCA'][$table]['ctrl']['tx_contexts']['enableSettings'] = $enableSettings;
    }

    /**
     * Add setting columns to the TCA.
     *
     * @param string $table    Table to add settings to
     * @param array  $settings Array of settings to register.
     *                         Key is the setting name, value its title
     *
     */
    protected static function addToTcaColumns(string $table, array $settings): void
    {
        if (!isset($GLOBALS['TCA'][$table])) {
            return;
        }

        if (\array_key_exists(self::RECORD_SETTINGS_COLUMN, $GLOBALS['TCA'][$table]['columns'])) {
            $GLOBALS['TCA'][$table]['columns'][self::RECORD_SETTINGS_COLUMN]['config']['settings']
                = array_merge(
                    $GLOBALS['TCA'][$table]['columns'][self::RECORD_SETTINGS_COLUMN]
                    ['config']['settings'],
                    $settings,
                );
        } else {
            $recordSettingsConf = [
                'exclude' => true,
                'label' => 'LLL:' . self::LANG_FILE . ':tabname',
                'config' => [
                    'type' => 'user',
                    'renderType' => 'recordSettingsFormElement',
                    'size' => 30,
                    'settings' => $settings,
                ],
            ];
            $arColumns = [
                self::RECORD_SETTINGS_COLUMN => $recordSettingsConf,
            ];
            $arFlatColumns = self::getFlatColumns($table);

            if (\count($arFlatColumns) > 0) {
                // add passthrough fields to keep settings when copying records
                foreach ($arFlatColumns as $arSetting) {
                    foreach ($arSetting as $columnName) {
                        $arColumns[$columnName] = [
                            'config' => ['type' => 'passthrough'],
                        ];
                    }
                }
            }

            ExtensionManagementUtility::addTCAcolumns($table, $arColumns);

            switch ($table) {
                case 'pages':
                    ExtensionManagementUtility::addToAllTCAtypes(
                        $table,
                        '--div--;LLL:' . self::LANG_FILE . ':tabname,' . self::RECORD_SETTINGS_COLUMN,
                        implode(
                            ',',
                            [
                                PageRepository::DOKTYPE_DEFAULT,
                                PageRepository::DOKTYPE_SHORTCUT,
                                PageRepository::DOKTYPE_LINK,
                            ],
                        ),
                        'after:fe_group',
                    );

                    ExtensionManagementUtility::addToAllTCAtypes(
                        $table,
                        '--div--;LLL:' . self::LANG_FILE . ':tabname,' . self::RECORD_SETTINGS_COLUMN,
                        (string) PageRepository::DOKTYPE_SYSFOLDER,
                        'after:editlock',
                    );

                    break;
                case 'tt_content':
                    ExtensionManagementUtility::addToAllTCAtypes(
                        $table,
                        '--div--;LLL:' . self::LANG_FILE . ':tabname,' . self::RECORD_SETTINGS_COLUMN,
                        '',
                        'after:fe_group',
                    );

                    break;
            }
        }

        $defaultSettingsColumn = 'default_settings_' . $table;

        if (\array_key_exists($defaultSettingsColumn, $GLOBALS['TCA']['tx_contexts_contexts']['columns'])) {
            $GLOBALS['TCA']['tx_contexts_contexts']['columns'][$defaultSettingsColumn]['config']['settings']
                = array_merge(
                    $GLOBALS['TCA']['tx_contexts_contexts']['columns'][$defaultSettingsColumn]
                    ['config']['settings'],
                    $settings,
                );
        } else {
            $defaultSettingsConf = [
                'exclude' => true,
                'label' => $GLOBALS['TCA'][$table]['ctrl']['title'],
                'config' => [
                    'type' => 'user',
                    'size' => 30,
                    'renderType' => 'defaultSettingsFormElement',
                    'table' => $table,
                    'settings' => $settings,
                ],
            ];

            ExtensionManagementUtility::addTCAcolumns(
                'tx_contexts_contexts',
                [
                    $defaultSettingsColumn => $defaultSettingsConf,
                ],
            );

            ExtensionManagementUtility::addToAllTCAtypes(
                'tx_contexts_contexts',
                $defaultSettingsColumn,
            );
        }
    }
}
