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
 * Context Typoscript Connector
 *
 * This function is for usage in Typo3 TypoScript to get match context directly.
 *
 * @param string $strContext context alias
 *
 * @return boolean True if it matches, false if not
 */
function user_contexts_matches($strContext)
{
    static $initialized = false;
    if (!$initialized) {
        //load and resolve all contexts
        Tx_Contexts_Context_Container::get()->initMatching();
        $initialized = true;
    }

    return Tx_Contexts_Api_ContextMatcher::getInstance()
        ->matches($strContext);
}
?>
