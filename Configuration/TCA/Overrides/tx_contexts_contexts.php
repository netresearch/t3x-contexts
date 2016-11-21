<?php
//TODO check, prevent xml error for new records
\Netresearch\Contexts\Api\Configuration::registerContextType(
    '',
    'Select a type',
    '',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Empty.xml'
);

\Netresearch\Contexts\Api\Configuration::registerContextType(
    'domain',
    'Domain',
    \Netresearch\Contexts\Context\Type\DomainContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Domain.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'getparam',
    'GET parameter',
    \Netresearch\Contexts\Context\Type\QueryParameterContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/GetParam.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'ip',
    'IP',
    \Netresearch\Contexts\Context\Type\IpContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Ip.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'httpheader',
    'HTTP header',
    \Netresearch\Contexts\Context\Type\HttpHeaderContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/HttpHeader.xml'
);
\Netresearch\Contexts\Api\Configuration::registerContextType(
    'combination',
    'Logical context combination',
    \Netresearch\Contexts\Context\Type\CombinationContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Combination.xml'
);

\Netresearch\Contexts\Api\Configuration::registerContextType(
    'session',
    'Session variable',
    \Netresearch\Contexts\Context\Type\SessionContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Session.xml'
);