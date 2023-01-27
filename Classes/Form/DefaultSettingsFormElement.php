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
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ContextException;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

/**
 * USER functions to render the defaults fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class DefaultSettingsFormElement extends AbstractFormElement
{
    /**
     * Render a checkbox for the default settings of records in this table
     *
     * @return array
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function render(): array
    {
        $table = $this->data['parameterArray']['fieldConf']['config']['table'];

        $content = '';

        $namePre = str_replace('[default_settings_', '[default_settings][', $this->data['parameterArray']['itemFormElName']);

        // This fails
//        $namePre  = 'data' . $this->data['elementBaseName'];

        $uid = (int) $this->data['databaseRow']['uid'];

        /* @var null|AbstractContext $context */
        $context = $uid
            ? Container::get()->initAll()->find($uid)
            : null;

        foreach ($this->data['parameterArray']['fieldConf']['config']['settings'] as $configKey => $config) {
            $id         = $this->data['parameterArray']['itemFormElID'] . '-' . $configKey;
            $name       = $namePre . '[' . $configKey . ']';
            $checked    = '';
            $setting    = null;
            $hasSetting = false;

            if ($context !== null) {
                $setting    = $context->getSetting($table, $configKey, 0);
                $hasSetting = (bool) $setting;
            }

            if (
                ($context === null)
                || !$hasSetting
                || (($setting !== null) && $setting->getEnabled())
            ) {
                $checked = 'checked="checked"';
            }

            $content .= <<<HTML
<input type="hidden" name="$name" value="0" />
<input class="checkbox" type="checkbox" name="$name" value="1" id="$id" $checked/>
<label for="$id">{$GLOBALS['LANG']->sL($config['label'])}</label>
<br/>
HTML;
        }

        $result = $this->initializeResultArray();
        $result['html'] = $content;

        return $result;
    }
}
