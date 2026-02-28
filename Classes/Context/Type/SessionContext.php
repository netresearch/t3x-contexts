<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
        $tsfe = $this->getTypoScriptFrontendController();
        if (!$tsfe instanceof TypoScriptFrontendController) {
            return false;
        }

        /** @var FrontendUserAuthentication|null $feUser */
        $feUser = $tsfe->fe_user ?? null;
        if ($feUser === null) {
            return false;
        }

        $session = $feUser->getKey(
            'ses',
            $this->getConfValue('field_variable'),
        );

        return $this->invert($session !== null);
    }
}
