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
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ContextException;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

/**
 * USER functions to render the defaults fields
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class DefaultSettingsFormElement extends AbstractFormElement
{
    /**
     * Render a checkbox for the default settings of records in this table
     *
     * @return array<string, mixed>
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function render(): array
    {
        $table = $this->data['parameterArray']['fieldConf']['config']['table'];
        $content = '';

        $namePre = str_replace(
            '[default_settings_',
            '[default_settings][',
            $this->data['parameterArray']['itemFormElName'],
        );

        // This fails
        // $namePre  = 'data' . $this->data['elementBaseName'];

        $uid = (int) $this->data['databaseRow']['uid'];

        $context = $uid > 0
            ? Container::get()->initAll()->find($uid)
            : null;

        // Generate base ID from itemFormElName (itemFormElID removed in TYPO3 v12)
        $baseId = str_replace(['[', ']'], '_', $this->data['parameterArray']['itemFormElName']);
        $baseId = trim($baseId, '_');

        foreach ($this->data['parameterArray']['fieldConf']['config']['settings'] as $configKey => $config) {
            $id = $baseId . '-' . $configKey;
            $name = $namePre . '[' . $configKey . ']';
            $checked = '';
            $setting = null;
            $hasSetting = false;

            if ($context !== null) {
                $setting = $context->getSetting($table, $configKey, 0);
                $hasSetting = (bool) $setting;
            }

            if (
                ($context === null)
                || !$hasSetting
                || (($setting !== null) && $setting->getEnabled())
            ) {
                $checked = 'checked="checked"';
            }

            $label = htmlspecialchars($this->getLanguageService()->sL($config['label']));
            $content .= <<<HTML
                <input type="hidden" name="{$name}" value="0" />
                <input class="checkbox" type="checkbox" name="{$name}" value="1" id="{$id}" {$checked}/>
                <label for="{$id}">{$label}</label>
                <br/>
                HTML;
        }

        $result = $this->initializeResultArray();
        $result['html'] = $content;

        return $result;
    }
}
