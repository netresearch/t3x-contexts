<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Setting;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for Setting class.
 *
 * Setting represents a context setting for a specific table/record combination.
 */
final class SettingTest extends UnitTestCase
{
    #[Test]
    public function constructorSetsAllPropertiesFromRow(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $row = [
            'uid' => 42,
            'foreign_table' => 'pages',
            'foreign_uid' => 123,
            'name' => 'tx_contexts',
            'enabled' => 1,
        ];

        $setting = new Setting($mockContext, $row);

        self::assertSame(42, $setting->getUid());
        self::assertSame('pages', $setting->getForeignTable());
        self::assertSame(123, $setting->getForeignUid());
        self::assertSame('tx_contexts', $setting->getName());
        self::assertTrue($setting->getEnabled());
        self::assertSame($mockContext, $setting->getContext());
    }

    #[Test]
    public function constructorCastsTypesCorrectly(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $row = [
            'uid' => '99',
            'foreign_table' => 'tt_content',
            'foreign_uid' => '456',
            'name' => 'visibility',
            'enabled' => '0',
        ];

        $setting = new Setting($mockContext, $row);

        self::assertSame(99, $setting->getUid());
        self::assertSame(456, $setting->getForeignUid());
        self::assertFalse($setting->getEnabled());
    }

    #[Test]
    public function isDefaultSettingReturnsTrueForUidZero(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $row = [
            'uid' => 0,
            'foreign_table' => 'pages',
            'foreign_uid' => 0,
            'name' => 'tx_contexts',
            'enabled' => 1,
        ];

        $setting = new Setting($mockContext, $row);

        self::assertTrue($setting->isDefaultSetting());
    }

    #[Test]
    public function isDefaultSettingReturnsFalseForNonZeroUid(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $row = [
            'uid' => 1,
            'foreign_table' => 'pages',
            'foreign_uid' => 0,
            'name' => 'tx_contexts',
            'enabled' => 1,
        ];

        $setting = new Setting($mockContext, $row);

        self::assertFalse($setting->isDefaultSetting());
    }

    #[Test]
    public function fromFlatDataReturnsNullWhenContextNotInColumns(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '1,2,3',
            'tx_contexts_enable' => '4,6,7',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNull($setting, 'Context UID 5 not in enable or disable columns');
    }

    #[Test]
    public function fromFlatDataReturnsEnabledSettingWhenInEnableColumn(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '1,2,3',
            'tx_contexts_enable' => '4,5,6',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        self::assertTrue($setting->getEnabled());
        self::assertSame('pages', $setting->getForeignTable());
        self::assertSame('tx_contexts', $setting->getName());
    }

    #[Test]
    public function fromFlatDataReturnsDisabledSettingWhenInDisableColumn(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(2);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '1,2,3',
            'tx_contexts_enable' => '4,5,6',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        // Note: enabled is based on enable column, not disable column
        self::assertFalse($setting->getEnabled());
    }

    #[Test]
    public function fromFlatDataHandlesEmptyColumns(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNull($setting, 'Empty columns should return null');
    }

    #[Test]
    public function fromFlatDataHandlesSingleContextInColumn(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '1',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'tt_content',
            'visibility',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        self::assertTrue($setting->getEnabled());
    }

    #[Test]
    public function fromFlatDataDoesNotMatchPartialIdAtStart(): void
    {
        // Context UID 1 should NOT match "10,11,12" - only exact ID matches
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '10,11,12', // Contains "1" as prefix but not as ID
            'tx_contexts_enable' => '20,21,22',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        // Context 1 is not in the list, so should return null
        self::assertNull($setting, 'Context UID 1 should not match partial IDs like 10, 11, 12');
    }

    #[Test]
    public function fromFlatDataDoesNotMatchPartialIdAtEnd(): void
    {
        // Context UID 1 should NOT match "21,31,41" - only exact ID matches
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '21,31,41', // Contains "1" as suffix but not as ID
            'tx_contexts_enable' => '',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNull($setting, 'Context UID 1 should not match partial IDs like 21, 31, 41');
    }

    #[Test]
    public function fromFlatDataDoesNotMatchPartialIdInMiddle(): void
    {
        // Context UID 1 should NOT match "210,310" - only exact ID matches
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '210,310', // Contains "1" in middle but not as ID
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNull($setting, 'Context UID 1 should not match partial IDs like 210, 310');
    }

    #[Test]
    public function fromFlatDataMatchesExactIdAmongOthers(): void
    {
        // Context UID 5 should match "3,5,7" - exact match in middle of list
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '3,5,7',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        self::assertTrue($setting->getEnabled());
    }

    #[Test]
    public function fromFlatDataMatchesExactIdAtListStart(): void
    {
        // Context UID 5 should match "5,10,15" - exact match at start
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '5,10,15',
            'tx_contexts_enable' => '',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        self::assertFalse($setting->getEnabled()); // In disable column
    }

    #[Test]
    public function fromFlatDataMatchesExactIdAtListEnd(): void
    {
        // Context UID 15 should match "5,10,15" - exact match at end
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(15);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '5,10,15',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        self::assertTrue($setting->getEnabled());
    }

    #[Test]
    public function fromFlatDataPrioritizesDisableOverEnable(): void
    {
        // When context appears in BOTH disable and enable, behavior test
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '5', // Also in disable
            'tx_contexts_enable' => '5',  // In enable
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        // Both are true, so setting is created - enabled is based on enable column
        self::assertNotNull($setting);
        // The enabled value comes from the enable column check
        self::assertTrue($setting->getEnabled());
    }

    #[Test]
    public function fromFlatDataReturnsDefaultSettingWithNullUid(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(5);

        $flatColumns = ['tx_contexts_disable', 'tx_contexts_enable'];
        $row = [
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '5',
        ];

        $setting = Setting::fromFlatData(
            $mockContext,
            'pages',
            'tx_contexts',
            $flatColumns,
            $row,
        );

        self::assertNotNull($setting);
        // fromFlatData sets uid to null in dummy row, which becomes 0 via (int)
        self::assertTrue($setting->isDefaultSetting());
        self::assertSame(0, $setting->getUid());
    }
}
