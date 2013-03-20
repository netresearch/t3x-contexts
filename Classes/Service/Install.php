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
 * Hooks for tx_em_Install: Append the necessary field definitions for
 * the enableFields-columns (tx_contexts_enable, tx_contexts_disable)
 * to the tables they were registered for
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Tx_Contexts_Service_Install implements tx_em_Index_CheckDatabaseUpdatesHook
{
    /**
     * Hook that allows to dynamically extend the table definitions for e.g. custom
     * caches. The hook implementation may return table create strings that will be
     * respected by the extension manager during installation of an extension.
     *
     * @param string $extKey Extension key
     * @param array $extInfo Extension information array
     * @param string $fileContent Content of the current extension sql file
     * @param t3lib_install $instObj Instance of the installer
     * @param t3lib_install_Sql $instSqlObj Instance of the installer sql object
     * @param tx_em_Install $parent The calling parent object
     * @return string Either empty string or table create strings
     */
    public function appendTableDefinitions($extKey, array $extInfo, $fileContent, t3lib_install $instObj, t3lib_install_Sql $instSqlObj, tx_em_Install $parent)
    {
        global $TCA;

        $flatTableFields = Tx_Contexts_Api_Configuration::getExtensionFlatFields();
        if (!array_key_exists($extKey, $flatTableFields)) {
            return '';
        }

        $sql = '';
        foreach ($flatTableFields[$extKey] as $table => $fields) {
            $sql .= "\nCREATE TABLE $table (\n";
            foreach ($fields as $field) {
                $flatFields = Tx_Contexts_Api_Configuration::getFlatFields($table, $field);
                $sql .= $flatFields[0] . " tinytext NOT NULL,\n";
                $sql .= $flatFields[1] . " tinytext NOT NULL\n";
            }
            $sql .= ');';
        }

        return $sql;
    }

    /**
     * Hook that allows pre-processing of database structure modifications.
     * The hook implementation may return a user form that will temporarily
     * replace the standard database update form. This allows additional
     * operations to be performed before the database structure gets updated.
     *
     * @param string $extKey Extension key
     * @param array $extInfo Extension information array
     * @param array $diff Database differences
     * @param t3lib_install $instObj Instance of the installer
     * @param tx_em_Install $parent The calling parent object
     * @return string Either empty string or a pre-processing user form
     */
    public function preProcessDatabaseUpdates($extKey, array $extInfo, array $diff, t3lib_install $instObj, tx_em_Install $parent)
    {
        return '';
    }
}
?>