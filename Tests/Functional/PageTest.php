<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional;

use Error;
use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Query\Restriction\ContextRestriction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
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

    /**
     * Exclude 'test' parameter from cHash validation to avoid "cHash empty" errors in tests.
     */
    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'excludedParameters' => ['test'],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the context container singleton to ensure fresh state between tests
        Container::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_contexts.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_settings.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');

        // Create site configuration
        $siteConfigPath = $this->instancePath . '/typo3conf/sites/website-local';
        GeneralUtility::mkdir_deep($siteConfigPath);
        $siteConfig = [
            'rootPageId' => 1,
            'base' => 'http://localhost/',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'English',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                    'flag' => 'us',
                ],
            ],
        ];
        file_put_contents(
            $siteConfigPath . '/config.yaml',
            \Symfony\Component\Yaml\Yaml::dump($siteConfig, 99, 2),
        );

        // Set up TypoScript template for frontend rendering
        $this->setUpFrontendRootPage(1, [
            'EXT:contexts/Tests/Functional/Fixtures/TypoScript/Basic.typoscript',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up $_GET after each test
        unset($_GET['test']);

        // Only call parent tearDown if setup completed successfully
        // (instancePath is set during setUp)
        try {
            parent::tearDown();
        } catch (Error) {
            // Setup didn't complete, nothing to tear down
        }
    }

    /**
     * @todo This test requires ContextRestriction to properly integrate with TYPO3 v12+ PageRepository.
     *       The restriction is registered and context matching works, but page visibility isn't enforced
     *       during page resolution. This may require migrating from SC_OPTIONS hooks to PSR-14 events.
     */
    #[Test]
    #[Group('pending')]
    public function pageWithDisabledContextThrowsException(): void
    {
        self::markTestSkipped(
            'Page restriction integration with TYPO3 v12+ PageRepository requires additional implementation',
        );

        $this->activateDisableContext();

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('The requested page does not exist');

        // Use withQueryParameters for proper PSR-7 request handling
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

    /**
     * @todo This test requires ContextRestriction to properly integrate with TYPO3 v12+ PageRepository.
     *       The restriction is registered and context matching works, but page visibility isn't enforced
     *       during page resolution. This may require migrating from SC_OPTIONS hooks to PSR-14 events.
     */
    #[Test]
    #[Group('pending')]
    public function pageWithEnabledContextNoParameterThrowsException(): void
    {
        self::markTestSkipped(
            'Page restriction integration with TYPO3 v12+ PageRepository requires additional implementation',
        );

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

    #[Test]
    public function contextMatchesWithQueryParameter(): void
    {
        // Ensure clean state before testing Container directly
        Container::reset();

        // Create a mock request with the query parameter using withQueryParams()
        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        // Set the request and initialize context matching
        Container::get()
            ->setRequest($request)
            ->initMatching();

        // Verify context 1 is in the matched container
        self::assertNotNull(
            Container::get()->find(1),
            'Context 1 should be matched when test=1 parameter is present',
        );
    }

    #[Test]
    public function contextDoesNotMatchWithoutQueryParameter(): void
    {
        // Ensure clean state before testing Container directly
        Container::reset();

        // Create a mock request without the query parameter
        $request = new ServerRequest(
            'http://localhost/',
            'GET',
        );

        // Set the request and initialize context matching
        Container::get()
            ->setRequest($request)
            ->initMatching();

        // Verify context 1 is NOT in the matched container
        self::assertNull(
            Container::get()->find(1),
            'Context 1 should not be matched without test parameter',
        );
    }

    #[Test]
    public function tcaConfigurationIsCorrect(): void
    {
        // Verify TCA configuration for context settings
        $enableSettings = Configuration::getEnableSettings('pages');
        self::assertContains(
            'tx_contexts',
            $enableSettings,
            'pages table should have tx_contexts in enableSettings',
        );

        $flatColumns = Configuration::getFlatColumns('pages', 'tx_contexts');
        self::assertCount(
            2,
            $flatColumns,
            'pages table should have flatColumns for tx_contexts',
        );
        self::assertSame('tx_contexts_disable', $flatColumns[0]);
        self::assertSame('tx_contexts_enable', $flatColumns[1]);
    }

    #[Test]
    public function restrictionIsRegistered(): void
    {
        // Verify the ContextRestriction is registered in TYPO3 config
        self::assertArrayHasKey(
            ContextRestriction::class,
            $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'] ?? [],
            'ContextRestriction should be registered in additionalQueryRestrictions',
        );
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
