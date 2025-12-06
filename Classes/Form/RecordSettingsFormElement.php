<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Form;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ContextException;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * USER function to render the record settings fields
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class RecordSettingsFormElement extends AbstractFormElement
{
    /**
     * Render the context settings field for a certain table
     *
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function render(): array
    {
        $contexts = new Container();
        $contexts->initAll();

        $namePre = 'data' . $this->data['elementBaseName'];
        $settings = $this->data['parameterArray']['fieldConf']['config']['settings'];

        $contextsLabel = $this->getLanguageService()->sL('LLL:' . Configuration::LANG_FILE . ':tx_contexts_contexts');
        $content = <<<HTML
            <table class="tx_contexts_table_settings typo3-dblist" style="width: auto; min-width: 50%;">
                <tbody>
                    <tr class="t3-row-header">
                        <td></td>
                        <td class="tx_contexts_context">
                            {$contextsLabel}
                        </td>
            HTML;

        foreach ($settings as $config) {
            $settingLabel = $this->getLanguageService()->sL($config['label']);
            $content .= <<<HTML
                <td class="tx_contexts_setting">{$settingLabel}</td>
                HTML;
        }

        $content .= <<<HTML
            </tr>
            HTML;

        $uid = (int) $this->data['databaseRow']['uid'];

        $visibleContexts = 0;

        /* @var AbstractContext $context */
        foreach ($contexts as $context) {
            if ($context->getDisabled() || $context->getHideInBackend()) {
                continue;
            }

            ++$visibleContexts;
            $contSettings = '';
            $bHasSetting = false;

            foreach ($settings as $settingName => $config) {
                $setting = $uid > 0
                    ? $context->getSetting(
                        $this->data['tableName'],
                        $settingName,
                        $uid,
                        $this->data['databaseRow'],
                    )
                    : null;

                $bHasSetting = $bHasSetting || ($setting !== null);
                $contSettings .= '<td class="tx_contexts_setting">'
                    . '<select name="' . $namePre . '[' . $context->getUid() . '][' . $settingName . ']">'
                    . '<option value="">n/a</option>'
                    . '<option value="1"'
                    . (($setting !== null) && $setting->getEnabled() ? ' selected="selected"' : '')
                    . '>Yes</option>'
                    . '<option value="0"'
                    . (($setting !== null) && !$setting->getEnabled() ? ' selected="selected"' : '')
                    . '>No</option>'
                    . '</select></td>';
            }

            [$icon, $title] = $this->getRecordPreview($context);
            $content .= '<tr class="db_list_normal">'
                . '<td class="tx_contexts_context col-icon"">'
                    . $icon
                . '</td>'
                . '<td class="tx_contexts_context">'
                    . '<span class="context-' . ($bHasSetting ? 'active' : 'inactive') . '">' . $title . '</span>'
                . '</td>'
                . $contSettings
                . '</tr>';
        }
        if ($visibleContexts === 0) {
            $noContextsLabel = $this->getLanguageService()->sL('LLL:' . Configuration::LANG_FILE . ':no_contexts');
            $content .= <<<HTML
                <tr>
                    <td colspan="4" style="text-align: center;">
                        {$noContextsLabel}
                    </td>
                </tr>
                HTML;
        }

        $content .= <<<HTML
                </tbody>
            </table>
            HTML;

        $result = $this->initializeResultArray();
        $result['html'] = $content;

        return $result;
    }

    /**
     * Get the standard record view for context records
     *
     *
     * @return array First value is click icon, second is title
     */
    protected function getRecordPreview(AbstractContext $context): array
    {
        $row = [
            'uid' => $context->getUid(),
            'pid' => 0,
            'type' => $context->getType(),
            'alias' => $context->getAlias(),
        ];

        return [
            $this->getClickMenu(
                $this->getIcon($row),
                'tx_contexts_contexts',
                $row['uid'],
            ),
            htmlspecialchars($context->getTitle())
            . ' <span class="typo3-dimmed"><em>[' . $row['uid'] . ']</em></span>',
        ];
    }

    /**
     * Wraps the icon of a relation item (database record or file) in a link
     * opening the context menu for the item.
     *
     * Copied from class.t3lib_befunc.php
     *
     * @param string     $str   The icon HTML to wrap
     * @param string     $table Table name (e.g. "pages" or "tt_content") OR the
     *                          absolute path to the file
     * @param int|string $uid   The uid of the record OR if a file, just blank value.
     *
     */
    protected function getClickMenu(string $str, string $table, $uid = 0): string
    {
        return BackendUtility::wrapClickMenuOnIcon(
            $str,
            $table,
            $uid,
        );
    }

    /**
     * Get the icon HTML.
     *
   *
     */
    protected function getIcon(array $row): string
    {
        if (class_exists(IconFactory::class)) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

            return (string) $iconFactory->getIconForRecord(
                'tx_contexts_contexts',
                $row,
                'small',
            );
        }

        return '';
    }
}
