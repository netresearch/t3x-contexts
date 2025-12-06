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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test to verify extension is properly loaded.
 */
final class ExtensionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    #[Test]
    public function extensionIsLoaded(): void
    {
        self::assertTrue(
            ExtensionManagementUtility::isLoaded('contexts'),
            'Extension "contexts" should be loaded',
        );
    }
}
