<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for TCA configuration.
 */
final class TcaTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    #[Test]
    public function tcaHasContextTypes(): void
    {
        self::assertArrayHasKey('domain', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('getparam', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('ip', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('httpheader', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('combination', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        self::assertArrayHasKey('session', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
    }

    #[Test]
    public function tcaHasExtensionFlatSettings(): void
    {
        self::assertArrayHasKey('extensionFlatSettings', $GLOBALS['TCA']['tx_contexts_contexts']);
        self::assertSame(
            'tx_contexts',
            $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['pages'][0],
        );
        self::assertSame(
            'tx_contexts_nav',
            $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['pages'][1],
        );
        self::assertSame(
            'tx_contexts',
            $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['tt_content'][0],
        );
    }

    #[Test]
    public function tcaHasPagesContextConfiguration(): void
    {
        self::assertArrayHasKey('enableSettings', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']);
        self::assertContains('tx_contexts', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['enableSettings']);
        self::assertArrayHasKey('flatSettings', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']);
        self::assertSame(
            'tx_contexts_disable',
            $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][0],
        );
        self::assertSame(
            'tx_contexts_enable',
            $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][1],
        );
        self::assertSame(
            'tx_contexts_nav_disable',
            $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts_nav'][0],
        );
        self::assertSame(
            'tx_contexts_nav_enable',
            $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts_nav'][1],
        );
    }

    #[Test]
    public function tcaHasContentContextConfiguration(): void
    {
        self::assertArrayHasKey('enableSettings', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']);
        self::assertContains('tx_contexts', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['enableSettings']);
        self::assertArrayHasKey('flatSettings', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']);
        self::assertSame(
            'tx_contexts_disable',
            $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][0],
        );
        self::assertSame(
            'tx_contexts_enable',
            $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][1],
        );
    }
}
