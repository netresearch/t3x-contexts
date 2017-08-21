<?php

//prevent xml error for new records
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'default',
    'Select a type',
    '',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Empty.xml'
);

\Netresearch\Contexts\Api\Configuration::registerContextType(
    'domain',
    'Domain',
    'Netresearch\Contexts\Context\Type\DomainContext',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Domain.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'getparam',
    'GET parameter',
    'Netresearch\Contexts\Context\Type\QueryParameterContext',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/GetParam.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'ip',
    'IP',
    'Netresearch\Contexts\Context\Type\IpContext',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Ip.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'httpheader',
    'HTTP header',
    'Netresearch\Contexts\Context\Type\HttpHeaderContext',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/HttpHeader.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'combination',
    'Logical context combination',
    'Netresearch\Contexts\Context\Type\CombinationContext',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Combination.xml'
);

\Netresearch\Contexts\Api\Configuration::registerContextType(
    'session',
    'Session variable',
    'Netresearch\Contexts\Context\Type\SessionContext',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Session.xml'
);
