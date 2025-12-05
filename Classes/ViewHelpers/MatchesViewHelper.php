<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\ViewHelpers;

use Netresearch\Contexts\Api\ContextMatcher;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Context match view helper class.
 *
 * Example:
 *
 *     <div xmlns="http://www.w3.org/1999/xhtml"
 *         xmlns:contexts="http://typo3.org/ns/Tx_Contexts_ViewHelpers">
 *
 *         <f:if condition="{contexts:matches(alias:'mobile')}">
 *             <f:then>is Mobile</f:then>
 *             <f:else>is not Mobile</f:else>
 *         </f:if>
 *     </div>
 *
 * @author  André Hähnel <andre.haehnel@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class MatchesViewHelper extends AbstractViewHelper
{
    /**
     * Initializes arguments (attributes)
     *
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument(
            'alias',
            'string',
            'Context alias name',
            true,
        );
    }

    /**
     * Render the context match method
     *
     * @return int 1 if context match or 0
     */
    public function render(): int
    {
        /** @var string|null $alias */
        $alias = $this->arguments['alias'] ?? null;

        if ($alias !== null) {
            return (int) ContextMatcher::getInstance()->matches($alias);
        }
        return 0;
    }
}
