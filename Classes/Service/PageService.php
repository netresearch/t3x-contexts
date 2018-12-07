<?php
namespace Netresearch\Contexts\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
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
class PageService
    implements AbstractMenuFilterPagesHookInterface, SingletonInterface
{

    /**
     * Modify the cache hash
     *
     * @param array &$params Array of parameters: hashParameters,
     *                       createLockHashBase
     * @param null  $ref     Reference object
     * @return void
     */
    public function createHashBase(&$params, $ref)
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
    protected function getHashString()
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
     * @param array &$data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param bool $spacer If set, then the page is a spacer.
     * @param AbstractMenuContentObject $obj The menu object
     * @return bool Returns TRUE if the page can be safely included.
     */
    public function processFilter(
        array &$data, array $banUidArray, $spacer, AbstractMenuContentObject $obj
    ) {
        return
        Record::isEnabled('pages', $data) &&
        Record::isSettingEnabled('pages', 'tx_contexts_nav', $data);
    }
}
