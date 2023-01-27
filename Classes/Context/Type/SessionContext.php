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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Check if a session variable is set or not
 *
 * @author     Andre Hähnel <andre.haehnel@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
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
        /* @var TypoScriptFrontendController $GLOBALS['TSFE'] */
        $session = $GLOBALS['TSFE']->fe_user->getKey(
            'ses', $this->getConfValue('field_variable')
        );

        return $this->invert($session !== null);
    }
}
