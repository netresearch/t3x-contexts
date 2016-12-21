<?php
namespace Netresearch\Contexts\Tests\Functional;

use \TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Response;

class PageTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    protected $testExtensionsToLoad = array('typo3conf/ext/contexts');


    public function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/tx_contexts_contexts.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/tx_contexts_settings.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/pages.xml');

        $this->setUpFrontendRootPage(
            1,
            array('EXT:contexts/Tests/Functional/Fixtures/page.ts')
        );

        $this->setUpFrontendRootPage(
            2,
            array('EXT:contexts/Tests/Functional/Fixtures/page.ts')
        );
    }


    /**
     * Test Page with QueryParam Context parameter given
     */
    public function testPageWithContext()
    {
        $response = $this->getFrontendResponseOveride(1, array('test' => 1));
        $this->assertContains("<p>Hello world!</p>",
            $response->getContent());
    }

    /**
     * Test Page with QueryParam Context no parameter given
     *
     * @expectedException        \PHPUnit_Framework_AssertionFailedError
     * @expectedExceptionMessage  The requested page does not exist!
     */
    public function testPageWithContextNoParameter()
    {
        $this->getFrontendResponse(1);
    }

    /**
     * Test Page without Context
     */
    public function testPageWithOutContext()
    {
        $response = $this->getFrontendResponse(2);
        $this->assertContains("<p>Hello world!</p>",
            $response->getContent());
    }

    /**
     * Overwrite getFrontendResponse from parent to pass own parameter
     *
     * @param integer $pageId
     * @param array   $arAdditionalArguments query param array
     * @param bool    $failOnFailure
     *
     * @return array|Response
     */
    protected function getFrontendResponseOveride($pageId, $arAdditionalArguments = array(), $failOnFailure = TRUE)
    {
        $pageId = (int)$pageId;

        $arguments = array(
            'documentRoot' => (method_exists($this, 'getInstancePath') ? $this->getInstancePath() : $this->instancePath),
            'requestUrl' => 'http://localhost/?id=' . $pageId . ($arAdditionalArguments ? '&' . http_build_query($arAdditionalArguments) : ''),
        );

        $template = new \Text_Template(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/request.tpl');
        $template->setVar(
            array(
                'arguments' => var_export($arguments, TRUE),
                'originalRoot' => ORIGINAL_ROOT,
            )
        );

        $php = \PHPUnit_Util_PHP::factory();
        $response = $php->runJob($template->render());
        $result = json_decode($response['stdout'], TRUE);

        if ($result === NULL) {
            $this->fail('Frontend Response is empty');
        }

        if ($failOnFailure && $result['status'] === Response::STATUS_Failure) {
            $this->fail('Frontend Response has failure:' . LF . $result['error']);
        }

        $response = new Response($result['status'], $result['content'], $result['error']);
        return $response;
    }
}
?>
