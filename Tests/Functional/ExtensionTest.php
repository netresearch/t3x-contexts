<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
