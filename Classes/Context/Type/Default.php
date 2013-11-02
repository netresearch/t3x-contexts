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
 * Default context - seperately instantiated before all other contexts
 * 
 * @see Tx_Contexts_Context_Container::loadAvailable()
 *
 * @package    Contexts
 * @subpackage Contexts_Type
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_Default extends Tx_Contexts_Context_Abstract
{
    /**
     * Override constructor - we do our own stuff here
     */
    public function __construct()
    {
        $this->uid = 0;
        $this->type = __CLASS__;
        $this->title = $GLOBALS['LANG']->sL('LLL:EXT:contexts/Resources/Private/Language/locallang_db.xml:tx_contexts_contexts.default');
        $this->noIsVoidable = true;
    }

    /**
     * Default context always matches
     * 
     * @param array $arDependencies
     * @return boolean
     */
    public function match(array $arDependencies = array())
    {
        return true;
    }
}
