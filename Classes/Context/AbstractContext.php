<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Api\Configuration;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use function array_key_exists;
use function is_array;

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
    public const HTTP_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';

    /**
     * Key for the ip remote address
     *
     * @var string
     */
    public const REMOTE_ADDR = 'REMOTE_ADDR';

    /**
     * Uid of context.
     *
     * @var int
     */
    protected int $uid;

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
    protected bool $invert = false;

    /**
     * Store match result in user session.
     *
     * @var bool
     */
    protected bool $use_session = true;

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
    private array $settings = [];

    /**
     * Constructor - set the values from database row.
     *
     * @var bool
     */
    protected $disabled;

    /**
     * Hide Context in backend
     *
     * @var bool
     */
    protected bool $bHideInBackend = false;

    /**
     * Constructor - set the values from database row.
     *
     * @param array $arRow Database context row
     */
    public function __construct(array $arRow = [])
    {
        if (!empty($arRow)) {
            $this->uid            = (int) $arRow['uid'];
            $this->type           = $arRow['type'];
            $this->title          = $arRow['title'];
            $this->alias          = $arRow['alias'];
            $this->tstamp         = $arRow['tstamp'];
            $this->invert         = (bool) $arRow['invert'];
            $this->use_session    = (bool) $arRow['use_session'];
            $this->disabled       = (bool) $arRow['disabled'];
            $this->bHideInBackend = (bool) $arRow['hide_in_backend'];

            if ($arRow['type_conf'] !== '') {
                $this->conf = GeneralUtility::xml2array($arRow['type_conf']);
            }
        }
    }

    /**
     * Get a configuration value.
     *
     * @param string      $fieldName Name of the field
     * @param null|string $default   The value to use when none was found
     * @param string      $sheet     Sheet pointer, eg. "sDEF
     * @param string      $lang      Language pointer, eg. "lDEF
     * @param string      $value     Value pointer, eg. "vDEF
     *
     * @return string The content
     */
    protected function getConfValue(
        string $fieldName,
        string $default = null,
        string $sheet   = 'sDEF',
        string $lang    = 'lDEF',
        string $value   = 'vDEF'
    ): ?string {
        if (!isset($this->conf['data'][$sheet][$lang])) {
            return $default;
        }

        $ldata = $this->conf['data'][$sheet][$lang];

        return $ldata[$fieldName][$value] ?? $default;
    }

    /**
     * Query a setting record and retrieve the value object
     * if one was found.
     *
     * @param string     $table   Database table name
     * @param string     $setting Setting name
     * @param int        $uid     Record UID
     * @param null|array $arRow   Database row for the given UID. Useful for flat settings.
     *
     * @return null|Setting NULL when not enabled and not disabled
     *
     * @throws DBALException
     * @throws Exception
     */
    final public function getSetting(string $table, string $setting, int $uid, array $arRow = null): ?Setting
    {
        if ($arRow !== null) {
            // If it's a flat column, use the settings directly from the
            // database row instead of relying on the tx_contexts_settings
            // table
            $arFlatColumns = Configuration::getFlatColumns(
                $table, $setting
            );

            if (isset($arRow[$arFlatColumns[0]], $arRow[$arFlatColumns[1]])
            ) {
                return Setting::fromFlatData(
                    $this, $table, $setting, $arFlatColumns, $arRow
                );
            }
        }

        $settings = $this->getSettings($table, $uid);

        return $settings[$setting] ?? null;
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
     *
     * @throws DBALException
     * @throws Exception
     */
    final public function getSettings(string $table, int $uid): array
    {
        $settingsKey = $table . '.' . $uid;

        if (array_key_exists($settingsKey, $this->settings)) {
            return $this->settings[$settingsKey];
        }

        $uids = [ $uid ];

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
                    $queryBuilder->createNamedParameter($this->uid, PDO::PARAM_INT)
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
            ->fetchAllAssociative();

        foreach ($uids as $uidValue) {
            $this->settings[$table . '.' . $uidValue] = [];
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
     *
     * @throws DBALException
     * @throws Exception
     */
    final public function hasSetting(string $table, string $setting, int $uid): bool
    {
        return $this->getSetting($table, $setting, $uid) !== null;
    }

    /**
     * This function gets called when the current contexts are determined.
     *
     * @param array $arDependencies Array of context objects that are
     *                              dependencies of this context
     *
     * @return bool True when your context matches, false if not
     */
    abstract public function match(array $arDependencies = []): bool;

    /**
     * Get the uid of this context.
     *
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Get the type of this context.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the title of this context.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the alias of this context.
     *
     * @return string
     */
    public function getAlias(): string
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
    public function getDependencies(array $arContexts): array
    {
        return [];
    }

    /**
     * Get the disabled status of this context
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Get hide in backend
     *
     * @return bool true if the context not shown in backend
     */
    public function getHideInBackend(): bool
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
    protected function getMatchFromSession(): array
    {
        $bUseSession = $this->use_session;

        if (!$bUseSession) {
            return [false, null];
        }

        $res = $this->getSession();

        if ($res === null) {
            //not set yet
            return [false, null];
        }

        return [true, (bool) $res];
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
    protected function storeInSession(bool $bMatch): bool
    {
        if (!($this->use_session)) {
            return $bMatch;
        }

        /* @var TypoScriptFrontendController $GLOBALS['TSFE'] */
        $GLOBALS['TSFE']->fe_user->setKey(
            'ses', 'contexts-' . $this->uid . '-' . $this->tstamp, $bMatch
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();
        return $bMatch;
    }

    /**
     * Inverts the current match setting if inverting is activated.
     *
     * @param bool $bMatch If the context matches
     *
     * @return bool
     */
    protected function invert(bool $bMatch): bool
    {
        if ($this->invert) {
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
    public function setInvert(bool $bInvert): void
    {
        $this->invert = $bInvert;
    }

    /**
     * Set use session flag.
     *
     * @param bool $bUseSession True or false
     *
     * @return void
     */
    public function setUseSession(bool $bUseSession): void
    {
        $this->use_session = $bUseSession;
    }

    /**
     * Returns the value for the passed key
     *
     * @param string $strKey the key, e.g. REMOTE_ADDR
     *
     * @return string
     */
    protected function getIndpEnv(string $strKey): string
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
    protected function getRemoteAddress(): string
    {
        return $this->getIndpEnv(
            self::REMOTE_ADDR
        );
    }
}
