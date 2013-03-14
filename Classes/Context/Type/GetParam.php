<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
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
 * Matches on a GET parameter with a certain value
 *
 * @package Contexts
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_GetParam extends Tx_Contexts_Context_Abstract
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return boolean True if the context is active, false if not
     */
    public function match($arDependencies)
    {
        $strParamName = trim($this->getConfValue('field_name'));
        if (isset($_GET[$strParamName])) {
            $bMatch = $this->matchParameters($_GET[$strParamName]);
            $this->storeInSession($bMatch);
        } else {
            $bMatch = $this->loadFromSession();
        }

        return $bMatch;
    }

    /**
     * Checks if the given value is one of the configured allowed values
     *
     * @param string $value Current parameter value
     *
     * @return boolean True if the current paramter value is one of the
     *                 configured values
     */
    protected function matchParameters($value)
    {
        $arValues = explode("\n", $this->getConfValue('field_values'));
        $arValues = array_map('trim', $arValues);
        return in_array($value, $arValues, true);
    }

    /**
     * Stores the current match setting in the session
     *
     * @param boolean $bMatch If the context matches
     *
     * @return void
     */
    protected function storeInSession($bMatch)
    {
        $GLOBALS['TSFE']->fe_user->setKey(
            'ses', 'contexts-getparam-' . $this->uid, $bMatch
        );
        $GLOBALS['TSFE']->storeSessionData();
    }

    /**
     * Loads from session if the context matches
     *
     * @return boolean True if the context is active, false if not
     */
    protected function loadFromSession()
    {
        return (bool) $GLOBALS['TSFE']->fe_user->getKey(
            'ses', 'contexts-getparam-' . $this->uid
        );
    }
}
?>
