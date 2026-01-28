<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Form;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use Netresearch\Contexts\ContextException;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\Element\TextElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides methods used in the backend by flexforms.
 *
 * @author  Marian Pollzien <marian.pollzien@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class CombinationFormElement extends AbstractFormElement
{
    /**
     * Display a textarea with validation for the entered aliases and expressions
     *
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function render(): array
    {
        $textElement = GeneralUtility::makeInstance(
            TextElement::class,
            $this->nodeFactory,
            $this->data,
        );

        $text = $textElement->render();

        $evaluator = new LogicalExpressionEvaluator();
        $tokens = $evaluator->tokenize($this->data['parameterArray']['itemFormElValue']);

        $notFound = [];
        $unknownTokens = [];

        foreach ($tokens as $token) {
            if (
                \is_array($token)
                && $token[0] === LogicalExpressionEvaluator::T_VAR
            ) {
                $contexts = Container::get()->initAll();
                $found = false;

                /** @var AbstractContext $context */
                foreach ($contexts as $context) {
                    if ($context->getAlias() === $token[1]) {
                        $found = true;
                    }
                }

                if (!$found) {
                    $notFound[] = $token[1];
                }
            } elseif (
                \is_array($token)
                && ($token[0] === LogicalExpressionEvaluator::T_UNKNOWN)
            ) {
                $unknownTokens[] = $token[1];
            }
        }

        if ((\count($notFound) === 0) && (\count($unknownTokens) === 0)) {
            return $text;
        }

        $html = <<<HTML
            {$text['html']}
            <div class="text-danger">
            HTML;
        if (\count($notFound) > 0) {
            $notFoundText = htmlspecialchars(implode(', ', $notFound));
            $aliasesNotFoundLabel = $this->getLanguageService()->sL(
                'LLL:EXT:contexts/Resources/Private/Language/flexform.xlf:aliasesNotFound',
            );
            $html .= <<<HTML
                <p>
                    {$aliasesNotFoundLabel}: {$notFoundText}
                </p>
                HTML;
        }

        if (\count($unknownTokens) > 0) {
            $unknownTokensText = htmlspecialchars(implode(', ', $unknownTokens));
            $unknownTokensLabel = $this->getLanguageService()->sL(
                'LLL:EXT:contexts/Resources/Private/Language/flexform.xlf:unknownTokensFound',
            );
            $html .= <<<HTML
                <p>
                    {$unknownTokensLabel}: {$unknownTokensText}
                </p>
                HTML;
        }

        $html .= <<<HTML
            </div>
            HTML;

        $result = $this->initializeResultArray();
        $result['html'] = $html;

        return $result;
    }
}
