<?php
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
 * Overwrite checkEnableFields()
 *
 * @category TYPO3-Extensions
 * @package  Contexts
 * @author   André Hähnel <andre.haehnel@netresearch.de>
 * @license  http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link     http://github.com/netresearch/contexts
 */
class ux_tslib_fe extends tslib_fe
{
    /**
     * Checks page record for enableFields
     * First we use the parent method.
     * If the page is not disabled we check subpages for contexts.
     *
     * @param array   $row              The page record to evaluate
     * @param boolean $bypassGroupCheck Bypass group-check
     *
     * @return boolean
     *
     * @see tslib_fe::checkEnableFields($row, $bypassGroupCheck)
     * @see Tx_Contexts_Service_Tsfe::checkEnableFields
     */
    public function checkEnableFields($row, $bypassGroupCheck = false)
    {
        $bResult = parent::checkEnableFields($row, $bypassGroupCheck);

        if ($bResult !== true) {
            return $bResult;
        }

        $arParams = array(
           'pObj' => &$this,
        );

        $test = new Tx_Contexts_Service_Tsfe();
        $bResult = $test->checkEnableFields($arParams);

        if ($bResult === false) {
            return null;
        } else {
            return true;
        }

    }
}

$xFile = 'typo3conf/ext/contexts/class.ux_tslib_fe.php';

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS'][$xFile]) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS'][$xFile]);
}
?>
