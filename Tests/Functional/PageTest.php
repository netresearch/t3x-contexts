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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for page context visibility.
 */
final class PageTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected array $coreExtensionsToLoad = [
        'frontend',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_contexts.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_settings.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');

        $this->writeSiteConfiguration(
            'website-local',
            [
                'rootPageId' => 1,
                'base' => 'http://localhost/',
            ],
            [
                [
                    'languageId' => 0,
                    'title' => 'English',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ],
            ],
        );
    }

    #[Test]
    public function pageWithDisabledContextThrowsException(): void
    {
        $this->activateDisableContext();

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('The requested page does not exist');

        $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'test' => 1,
            ]),
        );
    }

    #[Test]
    public function pageWithDisabledContextNoParameterReturnsPage(): void
    {
        $this->activateDisableContext();

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
            ]),
        );

        self::assertStringContainsString(
            '<p>Hello world!</p>',
            (string) $response->getBody(),
        );
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function pageWithEnabledContextReturnsPage(): void
    {
        $this->activateEnableContext();

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'test' => 1,
            ]),
        );

        self::assertStringContainsString(
            '<p>Hello world!</p>',
            (string) $response->getBody(),
        );
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function pageWithEnabledContextNoParameterThrowsException(): void
    {
        $this->activateEnableContext();

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('The requested page does not exist');

        $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
            ]),
        );
    }

    #[Test]
    public function pageWithoutContextReturnsPage(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
            ]),
        );

        self::assertStringContainsString(
            '<p>Hello world!</p>',
            (string) $response->getBody(),
        );
        self::assertSame(200, $response->getStatusCode());
    }

    protected function activateEnableContext(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        $connection->update('pages', ['tx_contexts_enable' => 1], ['uid' => 1]);
    }

    protected function activateDisableContext(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        $connection->update('pages', ['tx_contexts_disable' => 1], ['uid' => 1]);
    }
}
