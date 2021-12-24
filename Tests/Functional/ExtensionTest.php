<?php
namespace Netresearch\Contexts\Tests\Functional;

class ExtensionTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected $testExtensionsToLoad = array('typo3conf/ext/contexts');


    public function testIsExtensionLoaded()
    {
        $this->assertTrue(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(
                'contexts'
            )
        );
    }
}
