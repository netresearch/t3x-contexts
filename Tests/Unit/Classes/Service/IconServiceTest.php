<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Service;

use Netresearch\Contexts\Service\IconService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IconService.
 */
final class IconServiceTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function overlayProvider(): array
    {
        return [
            'enable set returns contexts overlay' => [
                ['tx_contexts_enable' => '1,2', 'tx_contexts_disable' => ''],
                'default-icon',
                'extensions-contexts-status-overlay-contexts',
            ],
            'disable set returns contexts overlay' => [
                ['tx_contexts_enable' => '', 'tx_contexts_disable' => '3'],
                'default-icon',
                'extensions-contexts-status-overlay-contexts',
            ],
            'both set returns contexts overlay' => [
                ['tx_contexts_enable' => '1', 'tx_contexts_disable' => '2'],
                'default-icon',
                'extensions-contexts-status-overlay-contexts',
            ],
            'neither set returns original icon' => [
                ['tx_contexts_enable' => '', 'tx_contexts_disable' => ''],
                'default-icon',
                'default-icon',
            ],
            'missing fields returns original icon' => [
                ['other_field' => 'value'],
                'default-icon',
                'default-icon',
            ],
            'empty row returns original icon' => [
                [],
                'some-overlay',
                'some-overlay',
            ],
        ];
    }

    #[Test]
    #[DataProvider('overlayProvider')]
    public function postOverlayPriorityLookupReturnsExpectedIcon(
        array $row,
        string $currentIcon,
        string $expectedIcon,
    ): void {
        $service = new IconService();
        $status = [];

        $result = $service->postOverlayPriorityLookup('tt_content', $row, $status, $currentIcon);

        self::assertSame($expectedIcon, $result);
    }

    #[Test]
    public function postOverlayPriorityLookupWorksWithDifferentTables(): void
    {
        $service = new IconService();
        $status = [];
        $row = ['tx_contexts_enable' => '5'];

        $result = $service->postOverlayPriorityLookup('pages', $row, $status, 'page-default');

        self::assertSame('extensions-contexts-status-overlay-contexts', $result);
    }
}
