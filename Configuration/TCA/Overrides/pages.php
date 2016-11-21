<?php

\Netresearch\Contexts\Api\Configuration::enableContextsForTable('contexts', 'pages', array(
    'tx_contexts_nav' => array(
        'label' => 'LLL:' . \Netresearch\Contexts\Api\Configuration::LANG_FILE . ':tx_contexts_menu_visibility',
        'flatten' => true
    )
));
