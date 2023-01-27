<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


namespace Netresearch\Contexts\Tests\Functional;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;


class PageTest extends AbstractTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/contexts'];
    
    public function setUp()
    {
        parent::setUp();

        $this->importDataSet('EXT:contexts/Tests/Functional/Fixtures/tx_contexts_contexts.xml');
        $this->importDataSet('EXT:contexts/Tests/Functional/Fixtures/tx_contexts_settings.xml');
        $this->importDataSet('EXT:contexts/Tests/Functional/Fixtures/pages.xml');
        $this->setUpFrontendRootPage(
            1,
            [
                'setup' => ['EXT:contexts/Tests/Functional/Fixtures/page.ts']
            ]
        );
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/')
            ]
        );

    }

    /**
     * @return void
     */
    protected function activateEnableContext()
    {
        $conntection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $conntection->update('pages', ['tx_contexts_enable' => 1], ['uid' => 1]);
    }

    /**
     * @return void
     */
    protected function activateDisableContext()
    {
        $conntection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $conntection->update('pages', ['tx_contexts_disable' => 1], ['uid' => 1]);
    }

    /**
     * Test Page with QueryParam Context parameter given
     *
     * @expectedException        \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @expectedExceptionMessage  The requested page does not exist
     */
    public function testPageWithDisabledContext()
    {
        $this->activateDisableContext();
        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'test' => 1
            ])
        );
    }

    /**
     * Test Page with QueryParam Context no parameter given
     *
     */
    public function testPageWithDisbledContextNoParameter()
    {
        $this->activateDisableContext();
        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1
            ])
        );
        $this->assertContains("<p>Hello world!</p>",
            (string)$response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test Page with QueryParam Context parameter given
     */
    public function testPageWithEnabledContext()
    {
        $this->activateEnableContext();
        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1,
                'test' => 1
            ])
        );
        $this->assertContains("<p>Hello world!</p>",
            (string)$response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test Page with QueryParam Context no parameter given
     *
     * @expectedException        \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @expectedExceptionMessage  The requested page does not exist
     */
    public function testPageWithEnabledContextNoParameter()
    {
        $this->activateEnableContext();
        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1
            ])
        );
    }

    /**
     * Test Page without Context
     */
    public function testPageWithOutContext()
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => 1
            ])
        );
        $this->assertContains("<p>Hello world!</p>",
            (string)$response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

}
