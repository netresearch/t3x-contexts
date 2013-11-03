<?php
require_once __DIR__ . '/../../TestBase.php';
require_once __DIR__ . '/../../../Classes/Api/Record.php';
require_once __DIR__ . '/../../../Classes/Api/Configuration.php';
require_once __DIR__ . '/../../../Classes/Context/Container.php';

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Test for the records API
 *
 * @package    Contexts
 * @subpackage Api
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Api_RecordTest extends TestBase
{    
    const TABLE = 'table';
    const SETTING = 'setting';
    const UID = 3;
    
    /**
     * Register test table and test setting
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        
        Tx_Contexts_Api_Configuration::enableContextsForTable(
            'contexts',
            self::TABLE,
            array(
                self::SETTING => array(
                    'label' => '',
                    'flatten' => true,
                    'enables' => true
                )
            ),
            false
        );
    }


    /**
     * Tests for the methods
     *  - {@see Tx_Contexts_Api_Record::isEnabled()} and
     *  - {@see Tx_Contexts_Api_Record::isSettingEnabled()}
     *  - and indirectly also for
     *  -- {@see Tx_Contexts_Api_Record::getFlatColumnContents} and 
     *  -- {@see Tx_Contexts_Api_Record::evaluateFlatColumnContents}
     * 
     * @dataProvider provideContexts
     * 
     * @param string $expectedResult
     * @param array $contexts
     */
    public function testIsEnabledAndIsSettingsEnabled($expectedResult, array $contexts) {
        Tx_Contexts_Context_Container::get()->exchangeArray(
            $this->createContextStubs($contexts)
        );
        
        // Prepare the flat columns for the arguments array
        $flatColumns = Tx_Contexts_Api_Configuration::getFlatColumns(self::TABLE, self::SETTING);
        $flatColumnValues = array(0 => array(), 1 => array());
        foreach ($contexts as $i => $conf) {
            $enabled = is_array($conf) ? $conf[0] : $conf;
            if ($enabled !== null) {
                $flatColumnValues[$enabled ? 1 : 0][] = $i;
            }
        }
        
        // Create the possible parameter types: uid, row with uid and row with flat columns
        $arguments = array(
            self::UID, 
            array('uid' => self::UID),
            array(
                $flatColumns[0] => implode(',', $flatColumnValues[0]),
                $flatColumns[1] => implode(',', $flatColumnValues[1])
            )
        );
        
        foreach ($arguments as $argument) {
            // Test Tx_Contexts_Api_Record::isEnabled()
            $this->assertEquals(
                $expectedResult, 
                Tx_Contexts_Api_Record::isEnabled(self::TABLE, $argument)
            );
            // Test Tx_Contexts_Api_Record::isSettingEnabled()
            $this->assertEquals(
                $expectedResult, 
                Tx_Contexts_Api_Record::isSettingEnabled(self::TABLE, self::SETTING, $argument)
            );
        }
    }

    /**
     * Create the mocks and stubs from a simple boolean/array context array
     * 
     * @see Tx_Contexts_Api_RecordTest::provideContexts()
     * 
     * @param array $contexts
     * @return array
     */
    protected function createContextStubs($contexts) {
        $contextStubs = array();
        foreach ($contexts as $i => $conf) {
            $contextStub = $this->getMock('Tx_Contexts_Context_Default', 
                array('getUid', 'getSetting', 'getNoIsVoidable'));
            $contextStub->expects($this->any())
                ->method('getUid')
                ->will($this->returnValue($i));
            
            if (is_array($conf)) {
                $enabled = $conf[0];
                $noVoidable = $conf[1];
            } else {
                $enabled = $conf;
                $noVoidable = false;
            }
            
            if ($enabled !== NULL) {
                $setting = $this->getMock('Tx_Contexts_Context_Setting', array('getEnabled'));
                $setting
                    ->expects($this->any())
                    ->method('getEnabled')
                    ->will($this->returnValue($enabled));
            } else {
                $setting = null;
            }
            
            $contextStub->expects($this->atLeastOnce())
                ->method('getNoIsVoidable')
                ->will($this->returnValue($noVoidable));
            
            $contextStub->expects($this->atLeastOnce())
                ->method('getSetting')
                ->will($this->returnValueMap(array(
                    array(self::TABLE, self::SETTING, (int) self::UID, $setting),
                    array(self::TABLE, self::SETTING, (string) self::UID, $setting)
                )));
            
            $contextStubs[] = $contextStub;
        }
        return $contextStubs;
    }

    /**
     * Example context provider
     * 
     * @return array
     */
    public static function provideContexts() {
        return array(
            // Disabled for default context, enabled for another
            array(
                // Expected result:
                true,
                array(
                    // Default context with noIsVoidable = true
                    array(false, true),
                    // Another regular context that voids the NO
                    true
                )
            ),
            // Disabled for default context, no rules for others
            array(
                // Expected result:
                false,
                array(
                    // Default context with noIsVoidable = true
                    array(false, true),
                    // Another regular context without a rule that voids the NO
                    null
                )
            ),
            // No rule for default context, no rules for others
            array(
                // Expected result:
                true,
                array(
                    // No rule for default
                    array(null, true),
                    // Another regular context without a rule
                    null
                )
            ),
            // No rule for default context, no other active contexts
            array(
                // Expected result:
                true,
                array(
                    // No rule for default
                    array(null, true),
                )
            ),
            // Disabled for default context, no other active contexts
            array(
                // Expected result:
                false,
                array(
                    // No rule for default
                    array(false, true),
                )
            ),
            // No rule for default context, other active contexts with some disabled
            array(
                // Expected result:
                false,
                array(
                    // No rule for default
                    array(null, true),
                    null,
                    false,
                    false,
                    null,
                    true
                )
            ),
            // Enabled for default context, other active contexts with some disabled
            array(
                // Expected result:
                false,
                array(
                    // No rule for default
                    array(true, true),
                    null,
                    false,
                    false,
                    null,
                    true
                )
            ),
        );
    }
}