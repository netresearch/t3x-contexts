<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\ExpressionLanguage;

use Netresearch\Contexts\ExpressionLanguage\FunctionsProvider\ContextFunctionsProvider;
use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;

/**
 * Class ContextConditionProvider
 * @package Netresearch\Contexts\ExpressionLanguage
 */
class ContextConditionProvider extends AbstractProvider
{
    /**
     * ContextConditionProvider constructor.
     * @return void
     */
    public function __construct()
    {
        $this->expressionLanguageProviders[] = ContextFunctionsProvider::class;
    }
}
