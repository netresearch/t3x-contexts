<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
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
 * Matches on a HTTP header with a certain value
 *
 * @package    Contexts
 * @subpackage Contexts_Type
 * @author     Michael Schams <schams.net>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_HttpHeader extends Tx_Contexts_Context_Abstract
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return boolean True if the context is active, false if not
     */
    public function match(array $arDependencies = array())
    {
        // determine which HTTP header has been configured
        $httpHeaderName = trim(strtolower($this->getConfValue('field_name')));

        // check, if header exists in HTTP request
        foreach ($_SERVER as $header => $value) {
            if (strtolower($header) === $httpHeaderName) {

                // header exists - check if any configured values match
                return $this->invert($this->storeInSession(
                    $this->matchValues($value)
                ));
            }
        }

        // HTTP header does not exist
        return $this->invert(false);
    }

    /**
     * Checks if the given value is one of the configured allowed values
     *
     * @param string $value Current parameter value
     *
     * @return boolean True if the current paramter value is one of the
     *                 configured values
     */
    protected function matchValues($value)
    {
        $arValues = explode("\n", trim($this->getConfValue('field_values')));

        //empty value list, so we allow any value
        if (count($arValues) == 1 && $arValues[0] == '') {
            return $value !== '';
        }

        $arValues = array_map('trim', $arValues);
        return in_array($value, $arValues, true);
    }
}