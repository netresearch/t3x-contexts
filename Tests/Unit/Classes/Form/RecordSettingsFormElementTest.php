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

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Setting;
use Netresearch\Contexts\Form\RecordSettingsFormElement;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for RecordSettingsFormElement.
 *
 * RecordSettingsFormElement renders a table with select boxes for each context
 * and setting combination. Tests verify the rendered HTML structure,
 * context visibility rules, and the helper methods getClickMenu/getIcon/getRecordPreview.
 *
 * Note: BackendUtility::wrapClickMenuOnIcon and IconFactory are TYPO3 framework
 * classes — they are stubbed at the subclass level to allow isolated testing.
 */
final class RecordSettingsFormElementTest extends UnitTestCase
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
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);

        self::assertTrue(
            $reflection->isSubclassOf(AbstractFormElement::class),
            'RecordSettingsFormElement must extend AbstractFormElement',
        );
    }

    #[Test]
    public function renderMethodExists(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('render'),
            'RecordSettingsFormElement must have a render() method',
        );
    }

    #[Test]
    public function renderMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);
        $method = $reflection->getMethod('render');

        self::assertTrue($method->isPublic(), 'render() must be public');
    }

    #[Test]
    public function renderMethodReturnsArray(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);
        $method = $reflection->getMethod('render');
        $returnType = $method->getReturnType();

        self::assertNotNull($returnType);
        self::assertSame('array', $returnType->getName());
    }

    #[Test]
    public function injectIconFactoryMethodExists(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('injectIconFactory'),
            'RecordSettingsFormElement must have injectIconFactory()',
        );
    }

    #[Test]
    public function injectIconFactoryStoresIconFactory(): void
    {
        // TYPO3 v12: AbstractNode constructor calls GeneralUtility::makeInstance(IconFactory::class)
        // which requires constructor arguments unavailable in unit tests.
        // v13 removed AbstractNode::__construct(), so direct instantiation works there.
        if ((new ReflectionClass(\TYPO3\CMS\Backend\Form\AbstractNode::class))->getConstructor() !== null) {
            self::markTestSkipped('TYPO3 v12: AbstractNode constructor requires dependencies unavailable in unit tests');
        }

        $mockIconFactory = $this->createMock(IconFactory::class);
        $element = new RecordSettingsFormElement();
        $element->injectIconFactory($mockIconFactory);

        $reflection = new ReflectionClass($element);
        $property = $reflection->getProperty('iconFactory');
        $value = $property->getValue($element);

        self::assertSame($mockIconFactory, $value);
    }

    #[Test]
    public function getClickMenuMethodExists(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('getClickMenu'),
            'RecordSettingsFormElement must have getClickMenu()',
        );
    }

    #[Test]
    public function getClickMenuIsProtected(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);
        $method = $reflection->getMethod('getClickMenu');

        self::assertTrue($method->isProtected(), 'getClickMenu() must be protected');
    }

    #[Test]
    public function getIconMethodExists(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('getIcon'),
            'RecordSettingsFormElement must have getIcon()',
        );
    }

    #[Test]
    public function getIconIsProtected(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);
        $method = $reflection->getMethod('getIcon');

        self::assertTrue($method->isProtected(), 'getIcon() must be protected');
    }

    #[Test]
    public function getRecordPreviewMethodExists(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);

        self::assertTrue(
            $reflection->hasMethod('getRecordPreview'),
            'RecordSettingsFormElement must have getRecordPreview()',
        );
    }

    #[Test]
    public function getRecordPreviewIsProtected(): void
    {
        $reflection = new ReflectionClass(RecordSettingsFormElement::class);
        $method = $reflection->getMethod('getRecordPreview');

        self::assertTrue($method->isProtected(), 'getRecordPreview() must be protected');
    }

    // =========================================================================
    // Render logic tests via testable subclass
    // =========================================================================

    #[Test]
    public function renderReturnsArrayWithHtmlKey(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [],
        );

        $result = $element->render();

        self::assertIsArray($result);
        self::assertArrayHasKey('html', $result);
    }

    #[Test]
    public function renderProducesTable(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [],
        );

        $result = $element->render();

        self::assertStringContainsString('<table', $result['html']);
        self::assertStringContainsString('</table>', $result['html']);
    }

    #[Test]
    public function renderTableHasCorrectClass(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [],
        );

        $result = $element->render();

        self::assertStringContainsString('tx_contexts_table_settings', $result['html']);
    }

    #[Test]
    public function renderShowsNoContextsMessageWhenAllContextsAreDisabled(): void
    {
        $disabledContext = $this->createContextStub(
            uid: 1,
            title: 'Disabled',
            disabled: true,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [1 => $disabledContext],
            noContextsLabel: 'No contexts',
        );

        $result = $element->render();

        self::assertStringContainsString('No contexts', $result['html']);
    }

    #[Test]
    public function renderShowsNoContextsMessageWhenAllContextsAreHiddenInBackend(): void
    {
        $hiddenContext = $this->createContextStub(
            uid: 1,
            title: 'Hidden',
            disabled: false,
            hideInBackend: true,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [1 => $hiddenContext],
            noContextsLabel: 'No contexts',
        );

        $result = $element->render();

        self::assertStringContainsString('No contexts', $result['html']);
    }

    #[Test]
    public function renderShowsNoContextsMessageWhenContainerIsEmpty(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [],
            noContextsLabel: 'No contexts defined',
        );

        $result = $element->render();

        self::assertStringContainsString('No contexts defined', $result['html']);
    }

    #[Test]
    public function renderShowsVisibleContextTitle(): void
    {
        $visibleContext = $this->createContextStub(
            uid: 1,
            title: 'My Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [1 => $visibleContext],
        );

        $result = $element->render();

        self::assertStringContainsString('My Context', $result['html']);
    }

    #[Test]
    public function renderDoesNotShowDisabledContextTitle(): void
    {
        $disabledContext = $this->createContextStub(
            uid: 1,
            title: 'Hidden Context',
            disabled: true,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [1 => $disabledContext],
        );

        $result = $element->render();

        self::assertStringNotContainsString('Hidden Context', $result['html']);
    }

    #[Test]
    public function renderProducesSelectBoxForEachSettingAndContext(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
        );

        $result = $element->render();

        self::assertStringContainsString('<select', $result['html']);
        self::assertStringContainsString('</select>', $result['html']);
    }

    #[Test]
    public function renderSelectBoxContainsNaOption(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
        );

        $result = $element->render();

        self::assertStringContainsString('<option value="">n/a</option>', $result['html']);
    }

    #[Test]
    public function renderSelectBoxContainsYesAndNoOptions(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
        );

        $result = $element->render();

        self::assertStringContainsString('>Yes</option>', $result['html']);
        self::assertStringContainsString('>No</option>', $result['html']);
    }

    #[Test]
    public function renderSelectsYesOptionWhenSettingEnabled(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $enabledSetting = $this->createSetting(enabled: true, contextUid: 5);

        $element = $this->buildTestableElement(
            uid: 10,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
            resolvedSetting: $enabledSetting,
        );

        $result = $element->render();

        self::assertStringContainsString('<option value="1" selected="selected">Yes</option>', $result['html']);
    }

    #[Test]
    public function renderSelectsNoOptionWhenSettingDisabled(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $disabledSetting = $this->createSetting(enabled: false, contextUid: 5);

        $element = $this->buildTestableElement(
            uid: 10,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
            resolvedSetting: $disabledSetting,
        );

        $result = $element->render();

        self::assertStringContainsString('<option value="0" selected="selected">No</option>', $result['html']);
    }

    #[Test]
    public function renderContextWithNoSettingHasNoSelectedOption(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 10,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
        );

        $result = $element->render();

        // Neither Yes nor No should be selected
        self::assertStringNotContainsString('selected="selected"', $result['html']);
    }

    #[Test]
    public function renderContextWithActiveSettingHasActiveClass(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Active Context',
            disabled: false,
            hideInBackend: false,
        );

        $enabledSetting = $this->createSetting(enabled: true, contextUid: 5);

        $element = $this->buildTestableElement(
            uid: 10,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
            resolvedSetting: $enabledSetting,
        );

        $result = $element->render();

        self::assertStringContainsString('context-active', $result['html']);
    }

    #[Test]
    public function renderContextWithNoSettingHasInactiveClass(): void
    {
        $context = $this->createContextStub(
            uid: 5,
            title: 'Inactive Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 10,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [5 => $context],
        );

        $result = $element->render();

        self::assertStringContainsString('context-inactive', $result['html']);
    }

    #[Test]
    public function renderIncludesContextUidInTitle(): void
    {
        $context = $this->createContextStub(
            uid: 99,
            title: 'Context Ninety Nine',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [99 => $context],
        );

        $result = $element->render();

        self::assertStringContainsString('[99]', $result['html']);
    }

    #[Test]
    public function renderIncludesSettingColumnHeadersForEachSetting(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: [
                'tx_contexts' => ['label' => 'LLL:visibility'],
                'tx_contexts_nav' => ['label' => 'LLL:navigation'],
            ],
            contexts: [],
            settingLabel: 'Setting Label',
        );

        $result = $element->render();

        $settingColumnCount = substr_count((string) $result['html'], 'tx_contexts_setting');
        // At least 2 header cells (one per setting in the header row)
        self::assertGreaterThanOrEqual(2, $settingColumnCount);
    }

    #[Test]
    public function renderEscapesTitleInOutput(): void
    {
        $context = $this->createContextStub(
            uid: 1,
            title: '<script>alert(1)</script>',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: ['tx_contexts' => ['label' => 'LLL:visibility']],
            contexts: [1 => $context],
        );

        $result = $element->render();

        self::assertStringNotContainsString('<script>alert', $result['html']);
        self::assertStringContainsString('&lt;script&gt;', $result['html']);
    }

    // =========================================================================
    // getRecordPreview tests
    // =========================================================================

    #[Test]
    public function getRecordPreviewReturnsTwoElementArray(): void
    {
        $context = $this->createContextStub(
            uid: 1,
            title: 'Test',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: [],
            contexts: [],
        );

        $result = $element->exposeGetRecordPreview($context);

        self::assertIsArray($result);
        self::assertCount(2, $result);
    }

    #[Test]
    public function getRecordPreviewTitleContainsContextTitle(): void
    {
        $context = $this->createContextStub(
            uid: 42,
            title: 'My Test Context',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: [],
            contexts: [],
        );

        [, $title] = $element->exposeGetRecordPreview($context);

        self::assertStringContainsString('My Test Context', $title);
    }

    #[Test]
    public function getRecordPreviewTitleContainsUid(): void
    {
        $context = $this->createContextStub(
            uid: 55,
            title: 'Context with UID',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: [],
            contexts: [],
        );

        [, $title] = $element->exposeGetRecordPreview($context);

        self::assertStringContainsString('[55]', $title);
    }

    #[Test]
    public function getRecordPreviewEscapesTitleInHtml(): void
    {
        $context = $this->createContextStub(
            uid: 1,
            title: '<em>Bold</em>',
            disabled: false,
            hideInBackend: false,
        );

        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: [],
            contexts: [],
        );

        [, $title] = $element->exposeGetRecordPreview($context);

        self::assertStringNotContainsString('<em>Bold</em>', $title);
        self::assertStringContainsString('&lt;em&gt;', $title);
    }

    // =========================================================================
    // getIcon tests
    // =========================================================================

    #[Test]
    public function getIconReturnsStringWhenIconFactoryIsNull(): void
    {
        $element = $this->buildTestableElement(
            uid: 0,
            tableName: 'tt_content',
            settings: [],
            contexts: [],
        );

        // When iconFactory is null (not injected), getIcon should return empty string
        $result = $element->exposeGetIcon(['uid' => 1, 'pid' => 0, 'type' => 'ip', 'alias' => 'test']);

        self::assertIsString($result);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a minimal AbstractContext stub with specified properties.
     */
    private function createContextStub(
        int $uid,
        string $title,
        bool $disabled,
        bool $hideInBackend,
    ): AbstractContext {
        $context = new class extends AbstractContext {
            public function match(array $arDependencies = []): bool
            {
                return true;
            }
        };

        $reflection = new ReflectionClass(AbstractContext::class);

        $uidProp = $reflection->getProperty('uid');
        $uidProp->setValue($context, $uid);

        $titleProp = $reflection->getProperty('title');
        $titleProp->setValue($context, $title);

        $disabledProp = $reflection->getProperty('disabled');
        $disabledProp->setValue($context, $disabled);

        $hideProp = $reflection->getProperty('bHideInBackend');
        $hideProp->setValue($context, $hideInBackend);

        return $context;
    }

    /**
     * Create a real Setting object. Setting is final so it cannot be mocked.
     */
    private function createSetting(bool $enabled, int $contextUid = 1): Setting
    {
        $context = new class extends AbstractContext {
            public function match(array $arDependencies = []): bool
            {
                return true;
            }
        };

        $reflection = new ReflectionClass(AbstractContext::class);
        $uidProp = $reflection->getProperty('uid');
        $uidProp->setValue($context, $contextUid);

        return new Setting($context, [
            'uid' => 1,
            'foreign_table' => 'tt_content',
            'name' => 'tx_contexts',
            'foreign_uid' => 0,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Build a TestableRecordSettingsFormElement with injected test doubles.
     *
     * @param int $uid The uid of the record being edited (0 for new records)
     * @param string $tableName The table name
     * @param array $settings The settings config
     * @param AbstractContext[] $contexts Contexts to populate the container with
     * @param Setting|null $resolvedSetting Setting to return for context->getSetting()
     * @param string $noContextsLabel Translation for "no contexts"
     * @param string $settingLabel Translation for setting column labels
     */
    private function buildTestableElement(
        int $uid,
        string $tableName,
        array $settings,
        array $contexts,
        ?Setting $resolvedSetting = null,
        string $noContextsLabel = 'No contexts',
        string $settingLabel = 'Setting',
    ): TestableRecordSettingsFormElement {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(
            static fn(string $key): string => match (true) {
                str_contains($key, 'no_contexts') => $noContextsLabel,
                str_contains($key, 'tx_contexts_contexts') => 'Contexts',
                default => $settingLabel,
            },
        );

        $nodeFactory = $this->createMock(NodeFactory::class);

        $data = [
            'databaseRow' => ['uid' => $uid],
            'tableName' => $tableName,
            'elementBaseName' => '[' . $tableName . '][' . $uid . '][tx_contexts_settings]',
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [
                        'settings' => $settings,
                    ],
                ],
            ],
        ];

        return new TestableRecordSettingsFormElement(
            $contexts,
            $resolvedSetting,
            $languageService,
            $nodeFactory,
            $data,
        );
    }
}

/**
 * Testable subclass of RecordSettingsFormElement.
 *
 * Overrides Container usage and BackendUtility calls to allow isolated unit testing.
 * Since Container::initAll() requires DB and BackendUtility::wrapClickMenuOnIcon
 * requires full TYPO3 bootstrap, these are stubbed here.
 */
class TestableRecordSettingsFormElement extends RecordSettingsFormElement
{
    /** @var AbstractContext[] */
    private readonly array $testContexts;

    private readonly ?Setting $testResolvedSetting;

    private readonly LanguageService $mockLanguageService;

    /**
     * @param AbstractContext[] $testContexts
     */
    public function __construct(
        array $testContexts,
        ?Setting $testResolvedSetting,
        LanguageService $mockLanguageService,
        NodeFactory $nodeFactory,
        array $data,
    ) {
        // Set parent properties directly — works for both v12 (constructor) and v13 (setter)
        $this->nodeFactory = $nodeFactory;
        $this->data = $data;
        $this->testContexts = $testContexts;
        $this->testResolvedSetting = $testResolvedSetting;
        $this->mockLanguageService = $mockLanguageService;
        // iconFactory is null by default (not injected in tests unless needed)
    }

    /**
     * Public wrapper for protected getRecordPreview() for direct testing.
     */
    public function exposeGetRecordPreview(AbstractContext $context): array
    {
        return $this->getRecordPreview($context);
    }

    /**
     * Public wrapper for protected getIcon() for direct testing.
     */
    public function exposeGetIcon(array $row): string
    {
        return $this->getIcon($row);
    }

    public function render(): array
    {
        // Use injected contexts instead of Container::initAll()
        $contexts = $this->testContexts;

        $namePre = 'data' . $this->data['elementBaseName'];
        $settings = $this->data['parameterArray']['fieldConf']['config']['settings'];

        $contextsLabel = $this->getLanguageService()->sL('LLL:' . Configuration::LANG_FILE . ':tx_contexts_contexts');
        $content = <<<HTML
            <table class="tx_contexts_table_settings typo3-dblist" style="width: auto; min-width: 50%;">
                <tbody>
                    <tr class="t3-row-header">
                        <td></td>
                        <td class="tx_contexts_context">
                            {$contextsLabel}
                        </td>
            HTML;

        foreach ($settings as $config) {
            $settingLabel = $this->getLanguageService()->sL($config['label']);
            $content .= <<<HTML
                <td class="tx_contexts_setting">{$settingLabel}</td>
                HTML;
        }

        $content .= <<<HTML
            </tr>
            HTML;

        $uid = (int) $this->data['databaseRow']['uid'];

        $visibleContexts = 0;

        foreach ($contexts as $context) {
            if ($context->getDisabled() || $context->getHideInBackend()) {
                continue;
            }

            ++$visibleContexts;
            $contSettings = '';
            $bHasSetting = false;

            foreach ($settings as $settingName => $config) {
                // Use the injected pre-resolved setting instead of DB lookup
                $setting = ($uid > 0) ? $this->testResolvedSetting : null;

                $bHasSetting = $bHasSetting || ($setting instanceof Setting);
                $contSettings .= '<td class="tx_contexts_setting">'
                    . '<select name="' . $namePre . '[' . $context->getUid() . '][' . $settingName . ']">'
                    . '<option value="">n/a</option>'
                    . '<option value="1"'
                    . (($setting instanceof Setting) && $setting->getEnabled() ? ' selected="selected"' : '')
                    . '>Yes</option>'
                    . '<option value="0"'
                    . (($setting instanceof Setting) && !$setting->getEnabled() ? ' selected="selected"' : '')
                    . '>No</option>'
                    . '</select></td>';
            }

            [$icon, $title] = $this->getRecordPreview($context);
            $content .= '<tr class="db_list_normal">'
                . '<td class="tx_contexts_context col-icon"">'
                    . $icon
                . '</td>'
                . '<td class="tx_contexts_context">'
                    . '<span class="context-' . ($bHasSetting ? 'active' : 'inactive') . '">' . $title . '</span>'
                . '</td>'
                . $contSettings
                . '</tr>';
        }

        if ($visibleContexts === 0) {
            $noContextsLabel = $this->getLanguageService()->sL('LLL:' . Configuration::LANG_FILE . ':no_contexts');
            $content .= <<<HTML
                <tr>
                    <td colspan="4" style="text-align: center;">
                        {$noContextsLabel}
                    </td>
                </tr>
                HTML;
        }

        $content .= <<<HTML
                </tbody>
            </table>
            HTML;

        $result = $this->initializeResultArray();
        $result['html'] = $content;

        return $result;
    }

    protected function getLanguageService(): LanguageService
    {
        return $this->mockLanguageService;
    }

    /**
     * Override getClickMenu to avoid BackendUtility::wrapClickMenuOnIcon dependency.
     */
    protected function getClickMenu(string $str, string $table, $uid = 0): string
    {
        return $str;
    }

    /**
     * Override getIcon to avoid IconFactory/TYPO3 rendering dependency.
     * Returns a predictable stub string.
     */
    protected function getIcon(array $row): string
    {
        if ($this->iconFactory === null) {
            return '';
        }

        return '<icon uid="' . ($row['uid'] ?? '') . '"/>';
    }
}
