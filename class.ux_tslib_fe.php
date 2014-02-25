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

    /**
     * Calculates the cache-hash
     * This hash is unique to the template, the variables ->id, ->type, ->gr_list (list of groups), ->MP (Mount Points) and cHash array
     * Used to get and later store the cached data.
     *
     * Backported from TYPO3 4.7
     *
     * @return  string      MD5 hash of $this->hash_base which is a serialized version of there variables.
     * @access private
     * @see getFromCache(), getLockHash()
     */
    function getHash()  {
        $this->hash_base = $this->createHashBase(FALSE);
        return md5($this->hash_base);
    }

    /**
     * Calculates the lock-hash
     * This hash is unique to the above hash, except that it doesn't contain the template information in $this->all.
     *
     * Backported from TYPO3 4.7
     *
     * @return  string      MD5 hash
     * @access private
     * @see getFromCache(), getHash()
     */
    function getLockHash()  {
        $lockHash = $this->createHashBase(TRUE);
        return md5($lockHash);
    }

    /**
     * Calculates the cache-hash (or the lock-hash)
     * This hash is unique to the template,
     * the variables ->id, ->type, ->gr_list (list of groups),
     * ->MP (Mount Points) and cHash array
     * Used to get and later store the cached data.
     *
     * Backported from TYPO3 4.7
     *
     * @param boolean $createLockHashBase whether to create the lock hash, which doesn't contain the "this->all" (the template information)
     * @return string the serialized hash base
     */
    protected function createHashBase($createLockHashBase = FALSE) {
        $hashParameters = array(
            'id'      => intval($this->id),
            'type'    => intval($this->type),
            'gr_list' => (string) $this->gr_list,
            'MP'      => (string) $this->MP,
            'cHash'   => $this->cHash_array,
            'domainStartPage' => $this->domainStartPage
        );

            // include the template information if we shouldn't create a lock hash
        if (!$createLockHashBase) {
            $hashParameters['all'] = $this->all;
        }

            // Call hook to influence the hash calculation
        if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'])) {
            $_params = array(
                'hashParameters' => &$hashParameters,
                'createLockHashBase' => $createLockHashBase
            );
            foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'] as $_funcRef) {
                t3lib_div::callUserFunction($_funcRef, $_params, $this);
            }
        }

        return serialize($hashParameters);
    }
}

$xFile = 'typo3conf/ext/contexts/class.ux_tslib_fe.php';

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS'][$xFile]) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS'][$xFile]);
}
?>
