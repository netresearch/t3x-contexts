<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Query\Restriction;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Query\Restriction\ContextRestriction;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for ContextRestriction class.
 *
 * ContextRestriction is a TYPO3 query restriction that filters database records
 * based on context settings. It adds WHERE clauses to queries to exclude records
 * that should not be visible in the current context.
 */
final class ContextRestrictionTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset container singleton before each test
        Container::reset();

        // Clear any existing request
        unset($GLOBALS['TYPO3_REQUEST']);

        // Initialize required TCA structure
        $GLOBALS['TCA']['tx_contexts_contexts'] = [
            'contextTypes' => [],
            'extensionFlatSettings' => [],
            'columns' => [],
        ];
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA']['tx_contexts_contexts'],
            $GLOBALS['TCA']['test_table'],
            $GLOBALS['TCA']['pages'],
            $GLOBALS['TYPO3_REQUEST'],
        );
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function implementsQueryRestrictionInterface(): void
    {
        $restriction = new ContextRestriction();

        self::assertInstanceOf(QueryRestrictionInterface::class, $restriction);
    }

    #[Test]
    public function implementsEnforceableQueryRestrictionInterface(): void
    {
        $restriction = new ContextRestriction();

        self::assertInstanceOf(EnforceableQueryRestrictionInterface::class, $restriction);
    }

    #[Test]
    public function isEnforcedReturnsTrue(): void
    {
        $restriction = new ContextRestriction();

        self::assertTrue($restriction->isEnforced());
    }

    #[Test]
    public function buildExpressionReturnsEmptyConstraintsWhenNotInFrontend(): void
    {
        // No TYPO3_REQUEST set = not frontend
        unset($GLOBALS['TYPO3_REQUEST']);

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        $expressionBuilder->expects(self::once())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertSame($compositeExpression, $result);
    }

    #[Test]
    public function buildExpressionReturnsEmptyConstraintsForTableWithoutEnableSettings(): void
    {
        // Table without enable settings
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        $expressionBuilder->expects(self::once())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertSame($compositeExpression, $result);
    }

    #[Test]
    public function buildExpressionHandlesEmptyTablesArray(): void
    {
        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        $expressionBuilder->expects(self::once())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression([], $expressionBuilder);

        self::assertSame($compositeExpression, $result);
    }

    #[Test]
    public function buildExpressionHandlesMultipleTables(): void
    {
        // Multiple tables without settings
        $GLOBALS['TCA']['table_one'] = ['ctrl' => [], 'columns' => []];
        $GLOBALS['TCA']['table_two'] = ['ctrl' => [], 'columns' => []];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        $expressionBuilder->expects(self::once())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['table_one', 'table_two'], $expressionBuilder);

        self::assertSame($compositeExpression, $result);
    }

    #[Test]
    public function buildExpressionReturnsCompositeExpression(): void
    {
        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        $expressionBuilder->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertInstanceOf(CompositeExpression::class, $result);
    }

    #[Test]
    public function buildExpressionSkipsTablesWithoutFlatColumns(): void
    {
        // Table with enableSettings but no flatSettings
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                    // No flatSettings - should be skipped
                ],
            ],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        // Only final and() call should happen, no intermediate calls
        $expressionBuilder->expects(self::once())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertInstanceOf(CompositeExpression::class, $result);
    }

    #[Test]
    public function buildExpressionInFrontendModeWithNoContexts(): void
    {
        // Set up frontend mode
        $this->setupFrontendMode();

        // Table with flatColumns configured
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);
        $orExpression = $this->createMock(CompositeExpression::class);

        // With no contexts, only enable column constraints are built (isNull, eq on enable column)
        // No disable constraints are added because disableConstraints array is empty
        $expressionBuilder->expects(self::once())
            ->method('isNull')
            ->with('tx_contexts_enable')
            ->willReturn('IS NULL');

        $expressionBuilder->expects(self::once())
            ->method('eq')
            ->with('tx_contexts_enable', '\'\'')
            ->willReturn('= \'\'');

        $expressionBuilder->expects(self::once())
            ->method('literal')
            ->with('')
            ->willReturn('\'\'');

        // Should create OR expression for enable constraints only
        $expressionBuilder->expects(self::once())
            ->method('or')
            ->willReturn($orExpression);

        // Final AND expression
        $expressionBuilder->expects(self::once())
            ->method('and')
            ->with($orExpression)
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertInstanceOf(CompositeExpression::class, $result);
    }

    #[Test]
    public function buildExpressionInFrontendModeWithActiveContext(): void
    {
        // Set up frontend mode
        $this->setupFrontendMode();

        // Create mock context
        $mockContext = $this->createMockContext(123);
        Container::get()->append($mockContext);

        // Table with flatColumns configured
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);
        $orExpression = $this->createMock(CompositeExpression::class);
        $andExpression = $this->createMock(CompositeExpression::class);

        // Should build enable constraints (isNull, eq on enable column)
        // AND disable constraints (isNull, eq on disable column)
        $expressionBuilder->expects(self::exactly(2))
            ->method('isNull')
            ->willReturn('IS NULL');

        $expressionBuilder->expects(self::exactly(2))
            ->method('eq')
            ->willReturn('= \'\'');

        $expressionBuilder->expects(self::exactly(2))
            ->method('literal')
            ->with('')
            ->willReturn('\'\'');

        // Should build inSet for both enable and disable columns
        $expressionBuilder->expects(self::exactly(2))
            ->method('inSet')
            ->willReturnCallback(fn ($column, $value) => "FIND_IN_SET({$value}, {$column})");

        // Should create OR expressions: one for enable, one for disable
        $expressionBuilder->expects(self::exactly(2))
            ->method('or')
            ->willReturn($orExpression);

        // Should create AND expressions: one for disable constraints, one final
        $expressionBuilder->expects(self::exactly(2))
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertInstanceOf(CompositeExpression::class, $result);
    }

    #[Test]
    public function buildExpressionInFrontendModeWithMultipleContexts(): void
    {
        // Set up frontend mode
        $this->setupFrontendMode();

        // Create multiple mock contexts
        $mockContext1 = $this->createMockContext(123);
        $mockContext2 = $this->createMockContext(456);
        Container::get()->append($mockContext1);
        Container::get()->append($mockContext2);

        // Table with flatColumns configured
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        // Should build inSet for both contexts (enable and disable)
        $expressionBuilder->expects(self::exactly(4))
            ->method('inSet')
            ->willReturn('FIND_IN_SET()');

        // Should handle multiple constraint building
        $expressionBuilder->expects(self::atLeastOnce())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertInstanceOf(CompositeExpression::class, $result);
    }

    #[Test]
    public function buildExpressionHandlesMultipleEnableSettings(): void
    {
        // Set up frontend mode
        $this->setupFrontendMode();

        // Create mock context
        $mockContext = $this->createMockContext(123);
        Container::get()->append($mockContext);

        // Table with multiple enable settings
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts', 'tx_visibility'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                        'tx_visibility' => ['tx_visibility_disable', 'tx_visibility_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);

        // Should process both settings
        $expressionBuilder->expects(self::atLeastOnce())
            ->method('isNull')
            ->willReturn('IS NULL');

        $expressionBuilder->expects(self::atLeastOnce())
            ->method('and')
            ->willReturn($compositeExpression);

        $result = $restriction->buildExpression(['test_table'], $expressionBuilder);

        self::assertInstanceOf(CompositeExpression::class, $result);
    }

    #[Test]
    public function buildExpressionAddsDisableConstraintsCorrectly(): void
    {
        // Set up frontend mode
        $this->setupFrontendMode();

        // Create mock context
        $mockContext = $this->createMockContext(789);
        Container::get()->append($mockContext);

        // Table with flatColumns configured
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $restriction = new ContextRestriction();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $compositeExpression = $this->createMock(CompositeExpression::class);
        $orExpression = $this->createMock(CompositeExpression::class);

        // Track calls to inSet to verify disable column is used
        $inSetCalls = [];
        $expressionBuilder->expects(self::exactly(2))
            ->method('inSet')
            ->willReturnCallback(function ($column, $value) use (&$inSetCalls) {
                $inSetCalls[] = ['column' => $column, 'value' => $value];
                return "FIND_IN_SET({$value}, {$column})";
            });

        $expressionBuilder->method('isNull')->willReturn('IS NULL');
        $expressionBuilder->method('eq')->willReturn('= \'\'');
        $expressionBuilder->method('literal')->willReturn('\'\'');
        $expressionBuilder->method('or')->willReturn($orExpression);
        $expressionBuilder->method('and')->willReturn($compositeExpression);

        $restriction->buildExpression(['test_table'], $expressionBuilder);

        // Verify both enable and disable columns were checked
        $columns = array_column($inSetCalls, 'column');
        self::assertContains('tx_contexts_enable', $columns);
        self::assertContains('tx_contexts_disable', $columns);
    }

    /**
     * Set up frontend mode simulation
     */
    private function setupFrontendMode(): void
    {
        if (!\defined('TYPO3')) {
            \define('TYPO3', true);
        }

        // Create mock request with proper frontend type
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->with('applicationType')
            ->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * Create a mock context with specified UID
     */
    private function createMockContext(int $uid): object
    {
        $context = $this->getMockBuilder(\Netresearch\Contexts\Context\AbstractContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getUid')->willReturn($uid);

        return $context;
    }
}
