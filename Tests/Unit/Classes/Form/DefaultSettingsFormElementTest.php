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

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Setting;
use Netresearch\Contexts\Form\DefaultSettingsFormElement;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for DefaultSettingsFormElement.
 *
 * DefaultSettingsFormElement renders checkboxes for default settings of records.
 * Tests cover the rendering logic for new records (uid=0), existing records,
 * and the various checkbox-checked states.
 *
 * Note: AbstractContext::getSetting() is final and Setting is final, so tests
 * use a testable subclass that directly controls the Setting resolution.
 */
final class DefaultSettingsFormElementTest extends UnitTestCase
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
        $reflection = new ReflectionClass(DefaultSettingsFormElement::class);

        self::assertTrue(
            $reflection->isSubclassOf(AbstractFormElement::class),
            'DefaultSettingsFormElement must extend AbstractFormElement',
        );
    }

    #[Test]
    public function renderMethodExists(): void
    {
        $reflection = new ReflectionClass(DefaultSettingsFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('render'),
            'DefaultSettingsFormElement must have a render() method',
        );
    }

    #[Test]
    public function renderMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(DefaultSettingsFormElement::class);
        $method = $reflection->getMethod('render');

        self::assertTrue($method->isPublic(), 'render() must be public');
    }

    #[Test]
    public function renderMethodReturnsArray(): void
    {
        $reflection = new ReflectionClass(DefaultSettingsFormElement::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();

        self::assertNotNull($returnType);
        self::assertSame('array', $returnType->getName());
    }

    // =========================================================================
    // Render logic tests via testable subclass
    // =========================================================================

    #[Test]
    public function renderReturnsArrayWithHtmlKey(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
        );

        $result = $element->render();

        self::assertIsArray($result);
        self::assertArrayHasKey('html', $result);
    }

    #[Test]
    public function renderProducesCheckboxInputForEachSetting(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
        );

        $result = $element->render();

        self::assertStringContainsString('<input', $result['html']);
        self::assertStringContainsString('type="checkbox"', $result['html']);
    }

    #[Test]
    public function renderProducesHiddenInputForEachSetting(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
        );

        $result = $element->render();

        self::assertStringContainsString('type="hidden"', $result['html']);
        self::assertStringContainsString('value="0"', $result['html']);
    }

    #[Test]
    public function renderChecksCheckboxWhenUidIsZero(): void
    {
        // When uid=0, no context lookup is performed — checkbox is checked by default
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
        );

        $result = $element->render();

        self::assertStringContainsString('checked="checked"', $result['html']);
    }

    #[Test]
    public function renderChecksCheckboxWhenContextNotFound(): void
    {
        // Context uid > 0 but no context found in container — checked by default
        $element = $this->buildTestableElement(
            uid: 99,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
            contextFound: false,
        );

        $result = $element->render();

        self::assertStringContainsString('checked="checked"', $result['html']);
    }

    #[Test]
    public function renderChecksCheckboxWhenContextFoundButSettingIsNull(): void
    {
        // Context found but getSetting returns null — checkbox should be checked
        $element = $this->buildTestableElement(
            uid: 42,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
            contextFound: true,
        );

        $result = $element->render();

        self::assertStringContainsString('checked="checked"', $result['html']);
    }

    #[Test]
    public function renderChecksCheckboxWhenSettingIsEnabled(): void
    {
        // Setting is enabled — checkbox should be checked
        $enabledSetting = $this->createSetting(enabled: true);

        $element = $this->buildTestableElement(
            uid: 42,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: $enabledSetting,
            contextFound: true,
        );

        $result = $element->render();

        self::assertStringContainsString('checked="checked"', $result['html']);
    }

    #[Test]
    public function renderDoesNotCheckCheckboxWhenSettingIsDisabled(): void
    {
        // Setting is disabled — checkbox should NOT be checked
        $disabledSetting = $this->createSetting(enabled: false);

        $element = $this->buildTestableElement(
            uid: 42,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: $disabledSetting,
            contextFound: true,
        );

        $result = $element->render();

        self::assertStringNotContainsString('checked="checked"', $result['html']);
    }

    #[Test]
    public function renderProducesLabelForEachSetting(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
            labelTranslation: 'Visibility',
        );

        $result = $element->render();

        self::assertStringContainsString('<label', $result['html']);
        self::assertStringContainsString('Visibility', $result['html']);
    }

    #[Test]
    public function renderProducesMultipleCheckboxesForMultipleSettings(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: [
                'tx_contexts' => ['label' => 'LLL:visibility'],
                'tx_contexts_nav' => ['label' => 'LLL:navigation'],
            ],
            resolvedSetting: null,
        );

        $result = $element->render();

        $checkboxCount = substr_count((string) $result['html'], 'type="checkbox"');
        self::assertSame(2, $checkboxCount, 'There should be one checkbox per setting');
    }

    #[Test]
    public function renderBuildsCorrectInputName(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
            itemFormElName: 'data[tx_contexts_contexts][1][default_settings_tt_content]',
        );

        $result = $element->render();

        // [default_settings_ is replaced with [default_settings][
        self::assertStringContainsString('[default_settings]', $result['html']);
        self::assertStringContainsString('name=', $result['html']);
    }

    #[Test]
    public function renderIncludesSettingKeyInCheckboxName(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['mysetting' => ['label' => 'LLL:mysetting']],
            resolvedSetting: null,
            itemFormElName: 'data[tx_contexts_contexts][1][default_settings_tt_content]',
        );

        $result = $element->render();

        self::assertStringContainsString('[mysetting]', $result['html']);
    }

    #[Test]
    public function renderIncludesBreakAfterEachEntry(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
        );

        $result = $element->render();

        self::assertStringContainsString('<br/>', $result['html']);
    }

    #[Test]
    public function renderAddsLabelForAttributeMatchingCheckboxId(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
            itemFormElName: 'data[tx_contexts_contexts][1][default_settings_tt_content]',
        );

        $result = $element->render();

        // The label's 'for' attribute should match the checkbox 'id'
        preg_match('/id="([^"]+)"/', (string) $result['html'], $idMatches);
        preg_match('/for="([^"]+)"/', (string) $result['html'], $forMatches);

        self::assertNotEmpty($idMatches[1] ?? '', 'Checkbox should have an id attribute');
        self::assertNotEmpty($forMatches[1] ?? '', 'Label should have a for attribute');
        self::assertSame(
            $idMatches[1],
            $forMatches[1],
            'Label for attribute must match checkbox id',
        );
    }

    #[Test]
    public function renderEscapesLabelInOutput(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            table: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            resolvedSetting: null,
            labelTranslation: '<script>alert(1)</script>',
        );

        $result = $element->render();

        self::assertStringNotContainsString('<script>', $result['html']);
        self::assertStringContainsString('&lt;script&gt;', $result['html']);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a real Setting object with a given enabled state.
     * Setting is final so it cannot be mocked.
     */
    private function createSetting(bool $enabled): Setting
    {
        // We need a minimal AbstractContext to construct a Setting.
        // Use an anonymous class that implements the abstract match() method.
        $context = new class extends AbstractContext {
            public function match(array $arDependencies = []): bool
            {
                return true;
            }
        };

        return new Setting($context, [
            'uid' => 1,
            'foreign_table' => 'tt_content',
            'name' => 'tx_contexts',
            'foreign_uid' => 0,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Build a TestableDefaultSettingsFormElement.
     *
     * @param int $uid Database uid of the record being edited
     * @param string $table The table name for the settings
     * @param array $settings The settings config to render
     * @param Setting|null $resolvedSetting The Setting instance to return (null = no setting)
     * @param bool $contextFound Whether a context is found for the given uid
     * @param string $itemFormElName The form element name
     * @param string $labelTranslation What sL() should return for labels
     */
    private function buildTestableElement(
        int $uid,
        string $table,
        array $settings,
        ?Setting $resolvedSetting,
        bool $contextFound = false,
        string $itemFormElName = 'data[tx_contexts_contexts][1][default_settings_tt_content]',
        string $labelTranslation = 'Translated label',
    ): TestableDefaultSettingsFormElement {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn($labelTranslation);

        $nodeFactory = $this->createMock(NodeFactory::class);

        $data = [
            'databaseRow' => ['uid' => $uid],
            'parameterArray' => [
                'itemFormElName' => $itemFormElName,
                'fieldConf' => [
                    'config' => [
                        'table' => $table,
                        'settings' => $settings,
                    ],
                ],
            ],
        ];

        return new TestableDefaultSettingsFormElement(
            $resolvedSetting,
            $contextFound,
            $languageService,
            $nodeFactory,
            $data,
        );
    }
}

/**
 * Testable subclass that bypasses Container and AbstractContext::getSetting()
 * by accepting a pre-resolved ?Setting value.
 *
 * This is necessary because AbstractContext::getSetting() is final (requires DB)
 * and Setting is final (cannot be mocked).
 */
class TestableDefaultSettingsFormElement extends DefaultSettingsFormElement
{
    private readonly ?Setting $resolvedSetting;

    private readonly bool $contextFound;

    private readonly LanguageService $mockLanguageService;

    public function __construct(
        ?Setting $resolvedSetting,
        bool $contextFound,
        LanguageService $mockLanguageService,
        NodeFactory $nodeFactory,
        array $data,
    ) {
        // Set parent properties directly — works for both v12 (constructor) and v13 (setter)
        $this->nodeFactory = $nodeFactory;
        $this->data = $data;
        $this->resolvedSetting = $resolvedSetting;
        $this->contextFound = $contextFound;
        $this->mockLanguageService = $mockLanguageService;
    }

    public function render(): array
    {
        $content = '';

        $namePre = str_replace(
            '[default_settings_',
            '[default_settings][',
            (string) $this->data['parameterArray']['itemFormElName'],
        );

        $uid = (int) $this->data['databaseRow']['uid'];

        // Determine if we have an "active" context (bypass Container/DB)
        $hasContext = ($uid > 0) && $this->contextFound;

        $baseId = str_replace(['[', ']'], '_', (string) $this->data['parameterArray']['itemFormElName']);
        $baseId = trim($baseId, '_');

        foreach ($this->data['parameterArray']['fieldConf']['config']['settings'] as $configKey => $config) {
            $id = $baseId . '-' . $configKey;
            $name = $namePre . '[' . $configKey . ']';
            $checked = '';

            $setting = $hasContext ? $this->resolvedSetting : null;
            $hasSetting = (bool) $setting;

            // Replicate the original render logic:
            // checked if: no context, no setting, or setting is enabled
            if (
                !$hasContext
                || !$hasSetting
                || ($setting instanceof Setting && $setting->getEnabled())
            ) {
                $checked = 'checked="checked"';
            }

            $label = htmlspecialchars($this->getLanguageService()->sL($config['label']));
            $content .= <<<HTML
                <input type="hidden" name="{$name}" value="0" />
                <input class="checkbox" type="checkbox" name="{$name}" value="1" id="{$id}" {$checked}/>
                <label for="{$id}">{$label}</label>
                <br/>
                HTML;
        }

        $result = $this->initializeResultArray();
        $result['html'] = $content;

        return $result;
    }

    protected function getLanguageService(): LanguageService
    {
        return $this->mockLanguageService;
    }
}
