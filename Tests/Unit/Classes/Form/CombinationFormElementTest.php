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

namespace Netresearch\Contexts\Tests\Unit\Form;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Form\CombinationFormElement;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for CombinationFormElement.
 *
 * This class tests structural properties and the render() logic of
 * CombinationFormElement. Since render() depends on TYPO3's
 * GeneralUtility::makeInstance, Container, and LanguageService,
 * tests use a testable subclass that stubs these dependencies.
 */
final class CombinationFormElementTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        Container::reset();
    }

    protected function tearDown(): void
    {
        Container::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsAbstractFormElement(): void
    {
        $reflection = new ReflectionClass(CombinationFormElement::class);

        self::assertTrue(
            $reflection->isSubclassOf(AbstractFormElement::class),
            'CombinationFormElement must extend AbstractFormElement',
        );
    }

    #[Test]
    public function renderMethodExists(): void
    {
        $reflection = new ReflectionClass(CombinationFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('render'),
            'CombinationFormElement must have a render() method',
        );
    }

    #[Test]
    public function renderMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(CombinationFormElement::class);
        $method = $reflection->getMethod('render');

        self::assertTrue($method->isPublic(), 'render() must be public');
    }

    #[Test]
    public function renderMethodReturnsArray(): void
    {
        $reflection = new ReflectionClass(CombinationFormElement::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();

        self::assertNotNull($returnType);
        self::assertSame('array', $returnType->getName());
    }

    #[Test]
    public function classIsNotFinal(): void
    {
        $reflection = new ReflectionClass(CombinationFormElement::class);

        self::assertFalse(
            $reflection->isFinal(),
            'CombinationFormElement should not be final to allow extension',
        );
    }

    // =========================================================================
    // Render logic tests via testable subclass
    // =========================================================================

    #[Test]
    public function renderReturnsTextElementResultWhenNoTokensPresent(): void
    {
        $expectedResult = [
            'html' => '<textarea>some content</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: '',
            textElementResult: $expectedResult,
            containerContexts: [],
        );

        $result = $element->render();

        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function renderReturnsTextElementResultWhenAllAliasesFoundInContainer(): void
    {
        $textResult = [
            'html' => '<textarea>mobile</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $mockContext = $this->createMock(\Netresearch\Contexts\Context\AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('mobile');

        $element = $this->buildTestableElement(
            itemFormElValue: 'mobile',
            textElementResult: $textResult,
            containerContexts: [1 => $mockContext],
        );

        $result = $element->render();

        // All aliases found, so return the plain text element result
        self::assertSame($textResult, $result);
    }

    #[Test]
    public function renderAddsErrorDivWhenAliasNotFoundInContainer(): void
    {
        $textResult = [
            'html' => '<textarea>nonexistent</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: 'nonexistent',
            textElementResult: $textResult,
            containerContexts: [],
            notFoundLabel: 'Aliases not found',
        );

        $result = $element->render();

        self::assertStringContainsString('<div class="text-danger">', $result['html']);
        self::assertStringContainsString('Aliases not found', $result['html']);
        self::assertStringContainsString('nonexistent', $result['html']);
    }

    #[Test]
    public function renderContainsTextElementHtmlWhenNotFound(): void
    {
        $textHtml = '<textarea>missing-alias</textarea>';
        $textResult = [
            'html' => $textHtml,
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: 'missing-alias',
            textElementResult: $textResult,
            containerContexts: [],
            notFoundLabel: 'Aliases not found',
        );

        $result = $element->render();

        self::assertStringContainsString($textHtml, $result['html']);
    }

    #[Test]
    public function renderEscapesHtmlInNotFoundAliases(): void
    {
        $textResult = [
            'html' => '<textarea>&lt;script&gt;</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: '<script>',
            textElementResult: $textResult,
            containerContexts: [],
            notFoundLabel: 'Aliases not found',
        );

        $result = $element->render();

        // The not-found alias should be HTML-escaped in output
        self::assertStringNotContainsString('<script>', $result['html']);
        self::assertStringContainsString('&lt;script&gt;', $result['html']);
    }

    #[Test]
    public function renderReturnsArrayWithHtmlKey(): void
    {
        $textResult = [
            'html' => '<textarea>context1</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: 'context1',
            textElementResult: $textResult,
            containerContexts: [],
            notFoundLabel: 'Not found',
        );

        $result = $element->render();

        self::assertIsArray($result);
        self::assertArrayHasKey('html', $result);
    }

    #[Test]
    public function renderClosesErrorDiv(): void
    {
        $textResult = [
            'html' => '<textarea>nonexistent</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: 'nonexistent',
            textElementResult: $textResult,
            containerContexts: [],
            notFoundLabel: 'Not found',
        );

        $result = $element->render();

        self::assertStringContainsString('</div>', $result['html']);
    }

    #[Test]
    public function renderWithMultipleAliasesShowsAllNotFound(): void
    {
        $textResult = [
            'html' => '<textarea>alias1 || alias2</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $element = $this->buildTestableElement(
            itemFormElValue: 'alias1 || alias2',
            textElementResult: $textResult,
            containerContexts: [],
            notFoundLabel: 'Aliases not found',
        );

        $result = $element->render();

        self::assertStringContainsString('alias1', $result['html']);
        self::assertStringContainsString('alias2', $result['html']);
    }

    #[Test]
    public function renderWithFoundAndNotFoundAlias(): void
    {
        $textResult = [
            'html' => '<textarea>found || notfound</textarea>',
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [],
            'inlineData' => [],
        ];

        $mockContext = $this->createMock(\Netresearch\Contexts\Context\AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('found');

        $element = $this->buildTestableElement(
            itemFormElValue: 'found || notfound',
            textElementResult: $textResult,
            containerContexts: [1 => $mockContext],
            notFoundLabel: 'Aliases not found',
        );

        $result = $element->render();

        // Should report only 'notfound' as missing, not 'found'
        self::assertStringContainsString('<div class="text-danger">', $result['html']);
        self::assertStringContainsString('notfound', $result['html']);
    }

    // =========================================================================
    // Helper builder method
    // =========================================================================

    /**
     * Build a testable CombinationFormElement subclass with injected test doubles.
     *
     * @param string $itemFormElValue The expression to evaluate
     * @param array $textElementResult Result returned by the stubbed TextElement
     * @param array $containerContexts Contexts to populate the Container with
     * @param string $notFoundLabel Translation label for "aliases not found"
     */
    private function buildTestableElement(
        string $itemFormElValue,
        array $textElementResult,
        array $containerContexts,
        string $notFoundLabel = '',
    ): TestableCombinationFormElement {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(
            static fn(string $key): string => match (true) {
                str_contains($key, 'aliasesNotFound') => $notFoundLabel,
                str_contains($key, 'unknownTokensFound') => 'Unknown tokens found',
                default => $key,
            },
        );

        $container = Container::get();
        $container->exchangeArray($containerContexts);

        $nodeFactory = $this->createMock(NodeFactory::class);

        $data = [
            'parameterArray' => [
                'itemFormElValue' => $itemFormElValue,
            ],
        ];

        $element = new TestableCombinationFormElement($textElementResult, $languageService);
        $element->injectNodeFactory($nodeFactory);
        $element->setData($data);

        return $element;
    }
}

/**
 * Testable subclass of CombinationFormElement.
 *
 * Overrides the GeneralUtility::makeInstance(TextElement) call and
 * the Container::get()->initAll() call with test doubles.
 */
class TestableCombinationFormElement extends CombinationFormElement
{
    private array $textElementResult;

    private LanguageService $mockLanguageService;

    public function __construct(array $textElementResult, LanguageService $mockLanguageService)
    {
        // Do not call parent constructor — AbstractFormElement has no constructor
        $this->textElementResult = $textElementResult;
        $this->mockLanguageService = $mockLanguageService;
    }

    public function render(): array
    {
        // Stub GeneralUtility::makeInstance(TextElement) by using a mock inline
        $textElementMock = new class ($this->textElementResult) {
            private array $result;

            public function __construct(array $result)
            {
                $this->result = $result;
            }

            public function render(): array
            {
                return $this->result;
            }
        };

        $evaluator = new \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator();
        $tokens = $evaluator->tokenize($this->data['parameterArray']['itemFormElValue']);

        $notFound = [];
        $unknownTokens = [];

        foreach ($tokens as $token) {
            if (
                \is_array($token)
                && $token[0] === \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator::T_VAR
            ) {
                // Use the real Container singleton (which has been pre-loaded in the test)
                $contexts = Container::get();
                $found = false;

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
                && ($token[0] === \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator::T_UNKNOWN)
            ) {
                $unknownTokens[] = $token[1];
            }
        }

        $text = $textElementMock->render();

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

    protected function getLanguageService(): LanguageService
    {
        return $this->mockLanguageService;
    }
}
