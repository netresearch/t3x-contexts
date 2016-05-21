<?php

\Bmack\Contexts\Api\Configuration::enableContextsForTable('contexts', 'pages', array(
    'tx_contexts_nav' => array(
        'label' => 'LLL:' . \Bmack\Contexts\Api\Configuration::LANG_FILE . ':tx_contexts_menu_visibility',
        'flatten' => true
    )
));
