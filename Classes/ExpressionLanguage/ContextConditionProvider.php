<?php
namespace Netresearch\Contexts\ExpressionLanguage;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
