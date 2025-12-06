<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Api;

use Netresearch\Contexts\Api\Record;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Setting;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for Record API class.
 *
 * Record provides static methods to check if records are enabled/disabled
 * by the current contexts. This is the main API for context-aware content
 * filtering.
 */
final class RecordTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset container singleton before each test
        Container::reset();

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
            $GLOBALS['TCA']['tt_content'],
        );
        Container::reset();
        parent::tearDown();
    }

    // ========================================
    // isEnabled() tests - Line 45 mutant target
    // ========================================

    #[Test]
    public function isEnabledReturnsTrueWhenNoEnableSettingsConfigured(): void
    {
        // Target mutant: Line 45 count($enableSettings) === 0
        // No enableSettings configured for this table
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [],
            'columns' => [],
        ];

        $row = ['uid' => 1, 'title' => 'Test Record'];

        // Should return true when count is 0
        self::assertTrue(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isEnabledReturnsTrueWhenEmptyEnableSettings(): void
    {
        // Target mutant: Line 45 count($enableSettings) === 0
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => [],
                ],
            ],
            'columns' => [],
        ];

        $row = ['uid' => 1, 'title' => 'Test Record'];

        // Should return true when count is 0
        self::assertTrue(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isEnabledReturnsTrueWhenSingleSettingEnabled(): void
    {
        // Target mutant: Line 49 foreach iteration with single setting
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

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
        ];

        // Should iterate once and return true
        self::assertTrue(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isEnabledReturnsFalseWhenSingleSettingDisabled(): void
    {
        // Target mutant: Line 50-52 (if !isSettingEnabled return false)
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                ],
            ],
            'columns' => [],
        ];

        // Row without uid and no flat settings = disabled
        $row = ['title' => 'Test Record']; // No uid

        self::assertFalse(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isEnabledHandlesMultipleSettings(): void
    {
        // Target mutant: Line 49 foreach with multiple iterations
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts', 'tx_visibility', 'tx_access'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                        'tx_visibility' => ['tx_visibility_disable', 'tx_visibility_enable'],
                        'tx_access' => ['tx_access_disable', 'tx_access_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
            'tx_visibility_disable' => '',
            'tx_visibility_enable' => '',
            'tx_access_disable' => '',
            'tx_access_enable' => '',
        ];

        // Should iterate through all three settings
        self::assertTrue(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isEnabledReturnsFalseWhenFirstOfMultipleSettingsDisabled(): void
    {
        // Target mutant: Line 50-52 (early return on first disabled)
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts', 'tx_visibility'],
                ],
            ],
            'columns' => [],
        ];

        // First setting has no uid = disabled
        $row = ['title' => 'Test Record']; // No uid

        // Should return false on first setting check
        self::assertFalse(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isEnabledReturnsFalseWhenSecondOfMultipleSettingsDisabled(): void
    {
        // Target mutant: Line 49 foreach - tests continuation after first success
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts', 'tx_visibility'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                        // tx_visibility intentionally has no flat settings
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            // uid missing - will fail on tx_visibility check
            'title' => 'Test Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
        ];

        // First setting passes via flat, second fails (no uid)
        self::assertFalse(Record::isEnabled('test_table', $row));
    }

    // ========================================
    // isSettingEnabled() tests
    // ========================================

    #[Test]
    public function isSettingEnabledReturnsTrueWhenNoContextsActive(): void
    {
        // No contexts in the container
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [],
            'columns' => [],
        ];

        $row = ['uid' => 1, 'title' => 'Test Record'];

        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledReturnsFalseWhenRowHasNoUid(): void
    {
        // Target mutant: Line 78-80 (uid check)
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [],
            'columns' => [],
        ];

        $row = ['title' => 'Test Record']; // No uid

        self::assertFalse(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledReturnsTrueWithFlatSettingsNotContainingContext(): void
    {
        // Configure flat settings for the table
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        // Row with flat columns that don't contain any context UIDs
        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
        ];

        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFallsThroughWhenFlatColumnsNotInRow(): void
    {
        // Target mutant: Line 124 array_key_exists check
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        // Row without the flat columns - should fall through to context-based check
        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            // Missing: tx_contexts_disable, tx_contexts_enable
        ];

        // With no contexts active, should return true
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFallsThroughWhenFlatColumnIsNull(): void
    {
        // Target mutant: Line 124 null value check
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => null, // null value
            'tx_contexts_enable' => '',
        ];

        // Falls through to context-based check, no contexts = true
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledHandlesCommaSeparatedContextUids(): void
    {
        // Target mutant: Line 127 explode logic
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        // Row with multiple context UIDs in flat column
        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '1,2,3', // Multiple UIDs
            'tx_contexts_enable' => '',
        ];

        // With no contexts active, the check for context UIDs won't match
        // so it returns true (no active context is in the disable list)
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledReturnsFalseWhenActiveContextIsInDisableList(): void
    {
        // Target mutant: Line 138-140 (context check in flat columns)
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        // Create a mock context
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(2);

        // Add context to container
        $container = Container::get();
        $container->append($mockContext);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '1,2,3', // Contains context UID 2
            'tx_contexts_enable' => '',
        ];

        // Should return false because context 2 is in disable list
        self::assertFalse(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledWithContextInEnableListStillChecksDisable(): void
    {
        // Target mutant: Line 138 - checks disable list first
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $container = Container::get();
        $container->append($mockContext);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '5', // Context in enable list
        ];

        // Should return true - not in disable list
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    // ========================================
    // isSettingEnabledFlat() tests - Lines 120-134 mutants
    // ========================================

    #[Test]
    public function isSettingEnabledFlatReturnsNullWhenNoFlatColumns(): void
    {
        // Target mutant: Line 116 count($flatColumns) === 0
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    // No flatSettings configured
                ],
            ],
            'columns' => [],
        ];

        $row = ['uid' => 1, 'title' => 'Test Record'];

        // Should return null via isSettingEnabledFlat, then fall through
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatReturnsNullWhenFlatColumnMissing(): void
    {
        // Target mutant: Line 124 !array_key_exists check
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '', // First column present
            // tx_contexts_enable missing
        ];

        // Should detect missing column and return null, then fall through
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatReturnsNullWhenFirstFlatColumnNull(): void
    {
        // Target mutant: Line 124 null value check
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => null, // First column is null
            'tx_contexts_enable' => '',
        ];

        // Should detect null and return null via rowValid = false
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatReturnsNullWhenSecondFlatColumnNull(): void
    {
        // Target mutant: Line 124 null check in loop iteration
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => null, // Second column is null
        ];

        // Should detect null and return null via rowValid = false
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatHandlesEmptyString(): void
    {
        // Target mutant: Line 126 empty string check
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '', // Empty string
            'tx_contexts_enable' => '', // Empty string
        ];

        // Should handle empty strings and return true (no contexts to check)
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatParsesCommaSeparatedValues(): void
    {
        // Target mutant: Line 127 explode and array_flip
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '10,20,30', // Multiple values
            'tx_contexts_enable' => '40,50',
        ];

        // Should parse comma-separated values correctly
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatDetectsContextInFirstColumn(): void
    {
        // Target mutant: Line 138 array_key_exists on flatColumnContents[0]
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(10);

        $container = Container::get();
        $container->append($mockContext);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '10,20', // Context 10 in disable list
            'tx_contexts_enable' => '',
        ];

        // Should find context 10 in index 0 and return false
        self::assertFalse(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatIteratesThroughAllContexts(): void
    {
        // Target mutant: Line 137 foreach (Container::get())
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        // Create multiple mock contexts
        $mockContext1 = $this->createMock(AbstractContext::class);
        $mockContext1->method('getUid')->willReturn(1);

        $mockContext2 = $this->createMock(AbstractContext::class);
        $mockContext2->method('getUid')->willReturn(2);

        $mockContext3 = $this->createMock(AbstractContext::class);
        $mockContext3->method('getUid')->willReturn(15); // This one is in disable list

        $container = Container::get();
        $container->append($mockContext1);
        $container->append($mockContext2);
        $container->append($mockContext3);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '15,20,25', // Context 15 is disabled
            'tx_contexts_enable' => '',
        ];

        // Should iterate through all contexts and find 15 in disable list
        self::assertFalse(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatReturnsTrueWhenNoContextsInDisableList(): void
    {
        // Target mutant: Line 143 return true
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(99);

        $container = Container::get();
        $container->append($mockContext);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '1,2,3', // Context 99 NOT in list
            'tx_contexts_enable' => '',
        ];

        // Should check all contexts and return true (none found in disable list)
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    // ========================================
    // Edge cases and additional coverage
    // ========================================

    #[Test]
    public function isEnabledReturnsTrueForUnconfiguredTable(): void
    {
        // Table not in TCA at all
        $row = ['uid' => 1, 'title' => 'Test Record'];

        self::assertTrue(Record::isEnabled('unconfigured_table', $row));
    }

    #[Test]
    public function isSettingEnabledReturnsTrueForUnconfiguredTable(): void
    {
        $row = ['uid' => 1, 'title' => 'Test Record'];

        self::assertTrue(Record::isSettingEnabled('unconfigured_table', 'any_setting', $row));
    }

    #[Test]
    public function isEnabledWithZeroUidIsHandledCorrectly(): void
    {
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

        $row = [
            'uid' => 0,
            'title' => 'Default Record',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
        ];

        self::assertTrue(Record::isEnabled('test_table', $row));
    }

    #[Test]
    public function isSettingEnabledWithStringUidIsCastCorrectly(): void
    {
        // Target mutant: Line 82 type casting
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [],
            'columns' => [],
        ];

        // UID as string (common from database results)
        $row = ['uid' => '42', 'title' => 'Test Record'];

        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatHandlesSingleValueInColumn(): void
    {
        // Target mutant: Line 127 - single value (no commas)
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(7);

        $container = Container::get();
        $container->append($mockContext);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '7', // Single value, no comma
            'tx_contexts_enable' => '',
        ];

        // Should parse single value and find context 7
        self::assertFalse(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatHandlesMultipleFlatColumnsArrays(): void
    {
        // Target mutant: Line 123 foreach loop with index tracking
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '1,2,3',
            'tx_contexts_enable' => '4,5,6',
        ];

        // Should process both columns with correct indexing
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatReturnsNullWhenRowValidIsFalse(): void
    {
        // Target mutant: Line 133-135 rowValid check
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            // Both columns missing - rowValid = false
        ];

        // Should return null when rowValid is false
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }

    #[Test]
    public function isSettingEnabledFlatInitializesEmptyArrayForEmptyColumn(): void
    {
        // Target mutant: Line 129 else branch (empty array initialization)
        $GLOBALS['TCA']['test_table'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(99);

        $container = Container::get();
        $container->append($mockContext);

        $row = [
            'uid' => 1,
            'title' => 'Test Record',
            'tx_contexts_disable' => '', // Empty = should initialize empty array
            'tx_contexts_enable' => '', // Empty = should initialize empty array
        ];

        // Should initialize empty arrays and not find any context UIDs
        self::assertTrue(Record::isSettingEnabled('test_table', 'tx_contexts', $row));
    }
}
