<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;

/**
 * Check if a session variable is set or not
 *
 * @author  Andre Hähnel <andre.haehnel@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class SessionContext extends AbstractContext
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return bool True if the context is active, false if not
     */
    public function match(array $arDependencies = []): bool
    {
        if ($this->getTypoScriptFrontendController() === null) {
            return false;
        }

        $session = $this->getTypoScriptFrontendController()
            ->fe_user
            ->getKey(
                'ses',
                $this->getConfValue('field_variable'),
            );

        return $this->invert($session !== null);
    }
}
