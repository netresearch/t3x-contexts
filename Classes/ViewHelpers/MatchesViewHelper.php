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
 * context match view helper class.
 *
 * Example:
 *
 *     <div xmlns="http://www.w3.org/1999/xhtml"
 *         xmlns:contexts="http://typo3.org/ns/Tx_Contexts_ViewHelpers">
 *
 *         <f:if condition="{contexts:matches(alias:'mobile')}">
 *             <f:then>is Mobile</f:then>
 *             <f:else>is not Mobile</f:else>
 *         </f:if>
 *     </div>
 *
 * @package    Contexts
 * @subpackage ViewHelper
 * @author     André Hähnel <andre.haehnel@netresearch.de>
 */
class Tx_Contexts_ViewHelpers_MatchesViewHelper
    extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Initializes arguments (attributes)
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'alias', 'string', 'Context alias name', true
        );
    }

    /**
     * Render the context match method
     *
     * @return integer 1 if context match or 0
     */
    public function render()
    {
        if (isset($this->arguments['alias'])) {
            return (int) Tx_Contexts_Api_ContextMatcher::getInstance()
                ->matches($this->arguments['alias']);
        }
        return 0;
    }
}
?>
