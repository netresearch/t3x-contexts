<?php
Tx_Contexts_Api_Configuration::enableContextsForTable($_EXTKEY, 'pages', array(
    'tx_contexts_nav' => array(
        'label' => 'LLL:' . Tx_Contexts_Api_Configuration::LANG_FILE . ':tx_contexts_menu_visibility',
        'flatten' => true
    )
));
Tx_Contexts_Api_Configuration::enableContextsForTable($_EXTKEY, 'tt_content');

Tx_Contexts_Api_Configuration::registerContextType(
    'domain',
    'Domain',
    'Tx_Contexts_Context_Type_Domain',
    'FILE:EXT:contexts/Configuration/flexform/ContextType/Domain.xml'
);
Tx_Contexts_Api_Configuration::registerContextType(
    'getparam',
    'GET parameter',
    'Tx_Contexts_Context_Type_GetParam',
    'FILE:EXT:contexts/Configuration/flexform/ContextType/GetParam.xml'
);
Tx_Contexts_Api_Configuration::registerContextType(
    'ip',
    'IP',
    'Tx_Contexts_Context_Type_Ip',
    'FILE:EXT:contexts/Configuration/flexform/ContextType/Ip.xml'
);
Tx_Contexts_Api_Configuration::registerContextType(
    'httpheader',
    'HTTP header',
    'Tx_Contexts_Context_Type_HttpHeader',
    'FILE:EXT:contexts/Configuration/flexform/ContextType/HttpHeader.xml'
);
Tx_Contexts_Api_Configuration::registerContextType(
    'combination',
    'Logical context combination',
    'Tx_Contexts_Context_Type_Combination',
    'FILE:EXT:contexts/Configuration/flexform/ContextType/Combination.xml'
);

Tx_Contexts_Api_Configuration::registerContextType(
    'session',
    'Session variable',
    'Tx_Contexts_Context_Type_Session',
    'FILE:EXT:contexts/Configuration/flexform/ContextType/Session.xml'
);

?>
