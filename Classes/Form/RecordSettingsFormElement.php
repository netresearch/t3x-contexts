<?php
namespace Bmack\Contexts\Form;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Bmack\Contexts\Api\Configuration;
use Bmack\Contexts\Context\AbstractContext;
use Bmack\Contexts\Context\Container;
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * USER function to render the record settings fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class RecordSettingsFormElement
{
    /**
     * Render the context settings field for a certain table
     *
     * @param array          $params Array of record information
     *                               - table - table name
     *                               - row   - array with database row data
     * @param FormEngine $formEngineObject
     * @return string
     */
    public function render($params, $formEngineObject)
    {
        $table = $params['table'];

        $formEngineObject->addStyleSheet(
            'tx_contexts_bestyles',
            ExtensionManagementUtility::extRelPath('contexts') . 'Resources/Public/StyleSheet/be.css'
        );

        $contexts = new Container();
        $contexts->initAll();

        $namePre = str_replace('[' . $params['field'] . '_', '[' . $params['field'] . '][', $params['itemFormElName']);

        $settings = $params['fieldConf']['config']['settings'];

        $content = '<table class="tx_contexts_table_settings typo3-dblist" style="width: auto; min-width:50%">'
            . '<tbody>'
            . '<tr class="t3-row-header">'
            . '<td></td>'
            . '<td class="tx_contexts_context">' .
            $formEngineObject->sL('LLL:' . Configuration::LANG_FILE . ':tx_contexts_contexts') .
            '</td>';
        foreach ($settings as $settingName => $config) {
            $content .= '<td class="tx_contexts_setting">' . $formEngineObject->sL($config['label']) . '</td>';
        }
        $content .= '</tr>';

        $uid = (int) $params['row']['uid'];

        $visibleContexts = 0;
        foreach ($contexts as $context) {
            /* @var $context AbstractContext */
            if ($context->getDisabled() || $context->getHideInBackend()) {
                continue;
            }

            ++$visibleContexts;
            $contSettings = '';
            $bHasSetting = false;
            foreach ($settings as $settingName => $config) {
                $setting = $uid ? $context->getSetting($table, $settingName, $uid, $params['row']) : null;
                $bHasSetting = $bHasSetting || (bool) $setting;
                $contSettings .= '<td class="tx_contexts_setting">'
                    . '<select name="' . $namePre . '[' . $context->getUid() . '][' . $settingName . ']">'
                    . '<option value="">n/a</option>'
                    . '<option value="1"' . ($setting && $setting->getEnabled() ? ' selected="selected"' : '') . '>Yes</option>'
                    . '<option value="0"' . ($setting && !$setting->getEnabled() ? ' selected="selected"' : '') . '>No</option>'
                    . '</select></td>';
            }

            list($icon, $title) = $this->getRecordPreview($context);
            $content .= '<tr class="db_list_normal">'
                . '<td class="tx_contexts_context col-icon"">'
                . $icon . '</td>'
                . '<td class="tx_contexts_context">'
                . '<span class="context-' . ($bHasSetting ? 'active' : 'inactive') . '">'
                . $title
                . '</span>'
                . '</td>'
                . $contSettings
                . '</tr>';
        }
        if ($visibleContexts == 0) {
            $content .= '<tr>'
                . '<td colspan="4" style="text-align: center">'
                . $formEngineObject->sL('LLL:' . Configuration::LANG_FILE . ':no_contexts')
                . '</td>'
                . '</tr>';
        }

        $content .= '</tbody></table>';

        return $content;
    }

    /**
     * Get the standard record view for context records
     *
     * @param AbstractContext $context
     *
     * @return array First value is click icon, second is title
     */
    protected function getRecordPreview($context)
    {
        $row = array(
            'uid'   => $context->getUid(),
            'pid'   => 0,
            'type'  => $context->getType(),
            'alias' => $context->getAlias()
        );

        return array(
            $this->getClickMenu(
                IconUtility::getSpriteIconForRecord(
                    'tx_contexts_contexts',
                    $row,
                    array(
                        'style' => 'vertical-align:top',
                        'title' => htmlspecialchars(
                            $context->getTitle() .
                            ' [UID: ' . $row['uid'] . ']')
                    )
                ),
                'tx_contexts_contexts',
                $row['uid']
            ),
            htmlspecialchars($context->getTitle()) .
            ' <span class="typo3-dimmed"><em>[' . $row['uid'] . ']</em></span>'
        );
    }

    /**
     * Wraps the icon of a relation item (database record or file) in a link
     * opening the context menu for the item.
     *
     * Copied from class.t3lib_befunc.php
     *
     * @param string  $str   The icon HTML to wrap
     * @param string  $table Table name (eg. "pages" or "tt_content") OR the
     *                       absolute path to the file
     * @param mixed   $uid   The uid of the record OR if file, just blank value.
     * @return string HTML
     */
    protected function getClickMenu($str, $table, $uid = '')
    {
        $onClick = htmlspecialchars($GLOBALS['SOBE']->doc->wrapClickMenuOnIcon(
            $str, $table, $uid, 1, '', '+info,edit,view,new', TRUE
        ));
        return
            '<a href="#" onclick="' . $onClick . '" onrightclick="' . $onClick . '">' . $str . '</a>';
    }

}