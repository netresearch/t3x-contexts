<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Service;

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
     * hook method after TYPO3 7.5
     *
     * @param string  $table    Name of the table to inspect.
     * @param array   $row      The row of the actuall element.
     * @param array  &$status   The actual status which already is set.
     * @param string  $iconName icon name
     *
     * @return string the registered icon name
     */
    public function postOverlayPriorityLookup(string $table, array $row, array &$status, string $iconName): string
    {
        if ((isset($row['tx_contexts_enable']) && $row['tx_contexts_enable'] !== '') ||
            (isset($row['tx_contexts_disable']) && $row['tx_contexts_disable'] !== '')) {
            $status['contexts'] = true;
            return 'extensions-contexts-status-overlay-contexts';
        }
        return $iconName;
    }
}
