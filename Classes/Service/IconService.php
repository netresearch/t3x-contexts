<?php
namespace Netresearch\Contexts\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Hook methods used in TYPO3 for Icons
 *
 * @author André Hähnel <andre.haehnel@netresearch.de>
 */
class IconService implements SingletonInterface
{
    /**
     * Add a "contexts" icon to the standard page/content element item
     * when we have a configuration.
     * hook method before TYPO3 7.5
     *
     * @param string $table   Name of the table to inspect.
     * @param array  $row     The row of the actuall element.
     * @param array  &$status The actually status which already is set.
     *
     * @return void
     */
    public function overrideIconOverlay($table, $row, &$status)
    {
        if (isset($row['tx_contexts_enable']) && $row['tx_contexts_enable'] != '' ||
            isset($row['tx_contexts_disable']) && $row['tx_contexts_disable'] != '') {
            $status['contexts'] = true;
        }
    }


    /**
     * Add a "contexts" icon to the standard page/content element item
     * when we have a configuration.
     * hook method after TYPO3 7.5
     *
     * @param string $table    Name of the table to inspect.
     * @param array  $row      The row of the actuall element.
     * @param array  &$status  The actually status which already is set.
     * @param string $iconName icon name
     *
     * @return string the registered icon name
     */
    public function postOverlayPriorityLookup($table, $row, &$status, $iconName)
    {
        if (isset($row['tx_contexts_enable']) && $row['tx_contexts_enable'] != '' ||
            isset($row['tx_contexts_disable']) && $row['tx_contexts_disable'] != '') {
            $status['contexts'] = true;
            return 'extensions-contexts-status-overlay-contexts';
        }
        return $iconName;
    }
}
