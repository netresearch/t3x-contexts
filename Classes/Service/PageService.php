<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Service;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Api\Record;
use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuFilterPagesHookInterface;

/**
 * Hook into enableFields() to hide pages and elements that are
 * may not be shown for the current contexts.
 *
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class PageService implements
    AbstractMenuFilterPagesHookInterface,
    SingletonInterface
{
    /**
     * Modify the cache hash
     *
     * @param array &$params Array of parameters: hashParameters,
     *                       createLockHashBase
     * @param null   $ref    Reference object
     *
     * @return void
     */
    public function createHashBase(array &$params, $ref): void
    {
        $params['hashParameters']['tx_contexts-contexts']
            = $this->getHashString();
    }

    /**
     * Creates a string that can be used to identify the current
     * context combination.
     * Used for cache hash modification.
     *
     * @return string Hash modificator
     */
    protected function getHashString(): string
    {
        $keys = array_keys(
            Container::get()->getArrayCopy()
        );
        sort($keys, SORT_NUMERIC);
        return implode(',', $keys);
    }

    /**
     * Checks if a page is OK to include in the final menu item array.
     *
     * @param array                     &$data       Array of menu items
     * @param array                     $banUidArray Array of page uids which are to be excluded
     * @param bool                      $spacer      If set, then the page is a spacer.
     * @param AbstractMenuContentObject $obj         The menu object
     *
     * @return bool Returns TRUE if the page can be safely included.
     *
     * @throws DBALException
     * @throws Exception
     */
    public function processFilter(
        array &$data,
        array $banUidArray,
        $spacer,
        AbstractMenuContentObject $obj
    ): bool {
        return Record::isEnabled('pages', $data)
            && Record::isSettingEnabled('pages', 'tx_contexts_nav', $data);
    }
}
