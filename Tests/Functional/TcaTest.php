<?php
namespace Netresearch\Contexts\Tests\Functional;

class TcaTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected $testExtensionsToLoad = array('typo3conf/ext/contexts');

    /**
     * Test the tca configuration
     */
    public function testHasTcaEntries()
    {
        //contexts types
        $this->assertArrayHasKey('domain', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        $this->assertArrayHasKey('getparam', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        $this->assertArrayHasKey('ip', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        $this->assertArrayHasKey('httpheader', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        $this->assertArrayHasKey('combination', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        $this->assertArrayHasKey('session', $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);

        //contexts flat settings fo pages/tt_content
        $this->assertArrayHasKey('extensionFlatSettings', $GLOBALS['TCA']['tx_contexts_contexts']);
        $this->assertSame('tx_contexts', $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['pages'][0]);
        $this->assertSame('tx_contexts_nav', $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['pages'][1]);
        $this->assertSame('tx_contexts', $GLOBALS['TCA']['tx_contexts_contexts']['extensionFlatSettings']['contexts']['tt_content'][0]);

        //pages
        $this->assertArrayHasKey('enableSettings', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']);
        $this->assertSame('tx_contexts', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['enableSettings']);
        $this->assertArrayHasKey('flatSettings', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']);
        $this->assertSame('tx_contexts_disable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][0]);
        $this->assertSame('tx_contexts_enable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][1]);
        $this->assertSame('tx_contexts_nav_disable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts_nav'][0]);
        $this->assertSame('tx_contexts_nav_enable', $GLOBALS['TCA']['pages']['ctrl']['tx_contexts']['flatSettings']['tx_contexts_nav'][1]);

        //tt_content
        $this->assertArrayHasKey('enableSettings', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']);
        $this->assertSame('tx_contexts', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['enableSettings']);
        $this->assertArrayHasKey('flatSettings', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']);
        $this->assertSame('tx_contexts_disable', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][0]);
        $this->assertSame('tx_contexts_enable', $GLOBALS['TCA']['tt_content']['ctrl']['tx_contexts']['flatSettings']['tx_contexts'][1]);
    }
}
