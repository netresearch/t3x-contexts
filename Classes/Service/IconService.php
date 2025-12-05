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
 * @author  André Hähnel <andre.haehnel@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class IconService implements SingletonInterface
{
    /**
     * Add a "contexts" icon to the standard page/content element item
     * when we have a configuration.
     *
     * @param string               $table    Name of the table to inspect
     * @param array<string, mixed> $row      The row of the actual element
     * @param array<string, mixed> $status   The current status (unused in PSR-14 events)
     * @param string               $iconName Current icon name
     *
     * @return string The icon identifier (modified if context settings exist)
     */
    public function postOverlayPriorityLookup(string $table, array $row, array $status, string $iconName): string
    {
        if (
            (isset($row['tx_contexts_enable']) && $row['tx_contexts_enable'] !== '')
            || (isset($row['tx_contexts_disable']) && $row['tx_contexts_disable'] !== '')
        ) {
            return 'extensions-contexts-status-overlay-contexts';
        }

        return $iconName;
    }
}
