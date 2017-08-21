<?php

\Netresearch\Contexts\Api\Configuration::enableContextsForTable('contexts', 'pages', [
    'tx_contexts_nav' => [
        'label'   => 'LLL:'.\Netresearch\Contexts\Api\Configuration::LANG_FILE.':tx_contexts_menu_visibility',
        'flatten' => true,
    ],
]);
