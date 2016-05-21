<?php

\Bmack\Contexts\Api\Configuration::registerContextType(
    'domain',
    'Domain',
    \Bmack\Contexts\Context\Type\DomainContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Domain.xml'
);
\Bmack\Contexts\Api\Configuration::registerContextType(
    'getparam',
    'GET parameter',
    \Bmack\Contexts\Context\Type\QueryParameterContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/GetParam.xml'
);
\Bmack\Contexts\Api\Configuration::registerContextType(
    'ip',
    'IP',
    \Bmack\Contexts\Context\Type\IpContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Ip.xml'
);
\Bmack\Contexts\Api\Configuration::registerContextType(
    'httpheader',
    'HTTP header',
    \Bmack\Contexts\Context\Type\HttpHeaderContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/HttpHeader.xml'
);
\Bmack\Contexts\Api\Configuration::registerContextType(
    'combination',
    'Logical context combination',
    \Bmack\Contexts\Context\Type\CombinationContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Combination.xml'
);

\Bmack\Contexts\Api\Configuration::registerContextType(
    'session',
    'Session variable',
    \Bmack\Contexts\Context\Type\SessionContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Session.xml'
);