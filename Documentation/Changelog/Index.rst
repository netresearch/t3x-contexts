.. include:: /Includes.rst.txt

.. _changelog:

=========
Changelog
=========

All notable changes to this project are documented here.

Version 5.0.0
=============

This is a major release targeting TYPO3 v13.4 LTS and v14.3 LTS. It also fixes
several defects, two of which were already silent on TYPO3 v13.

Breaking Changes
----------------

- **Dropped TYPO3 v12.4 support** - use version 4.x for TYPO3 v12.4
- **Removed** :file:`ext_tables.php` - deprecated in TYPO3 v14.3
  (:issue:`109438`)
- **Removed** ``FrontendControllerService::registerQueryParameter()``,
  ``::createHashBase()`` and ``::configArrayPostProc()``
- **Removed** ``PageService::createHashBase()`` - use the public
  ``PageService::getHashString()``
- **Removed** the empty ``CacheHashEventListener``
- **Removed** the protected ``AbstractContext::getTypoScriptFrontendController()``
  and ``AbstractContext::getIndpEnv()`` - custom context types use
  ``getRequest()``, ``getFrontendUser()`` and ``getNormalizedParams()``
- **Dropped** the unused ``typo3/cms-extbase`` and ``typo3/cms-extensionmanager``
  requirements - no class of either package is referenced
- **Removed** the site set settings ``contexts.matchMode`` and
  ``contexts.cacheLifetimeModifier`` - neither was ever read by the extension
- :php:`Configuration::registerContextType()` **no longer writes** the FlexForm
  file into ``TCA[tx_contexts_contexts][columns][type_conf][config][ds]``; the
  data structure is resolved from the context type registry instead

Bug Fixes
---------

- **Context records can be edited on TYPO3 v14.3 again** - ``ds_pointerField``
  and the multi-entry ``ds`` array were removed in TYPO3 v14.0
  (:issue:`107047`), so every read of ``tx_contexts_contexts.type_conf`` threw
  an ``InvalidTcaException``. ``FlexFormDataStructureEventListener`` resolves
  the data structure through the PSR-14 ``BeforeFlexFormDataStructure*`` events,
  which behave identically on v13.4 and v14.3
- **Frontend caches are invalidated when a context record changes** - saving,
  deleting or restoring a context flushes the ``pages`` cache group. TYPO3
  caches the :php:`ModifyTypoScriptConfigEvent` result under an identifier
  derived from the TypoScript sources, so a new or renamed GET parameter
  context previously never reached :typoscript:`config.linkVars`

- **Session context matches again** - the frontend user is read from the
  ``frontend.user`` request attribute instead of the ``TypoScriptFrontendController``
  removed in TYPO3 v14 (:issue:`107831`). The same applies to the
  ``use_session`` persistence of the GET parameter context
- **Context-aware page caching works again** - the active contexts and the
  values of all context GET parameters are added to the page cache identifier
  via :php:`\TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent`,
  replacing the ``createHashBase`` hook removed in TYPO3 v13.0
  (:issue:`102932`)
- **config.linkVars works again** - the GET parameters of all query parameter
  contexts are appended via
  :php:`\TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent`, replacing the
  removed ``configArrayPostProc`` hook
- **Page tree restrictions are no longer dropped** - the ``PageTreeRepository``
  XCLASS forwards the third ``$additionalQueryRestrictions`` constructor
  argument, so ``options.pageTree.excludeDoktypes`` is honoured again
- **Removed** the write to ``$TYPO3_CONF_VARS['FE']['addRootLineFields']``,
  removed in TYPO3 v13.2 (:issue:`103752`)

Improvements
------------

- CI matrix covers TYPO3 ``^13.4`` and ``^14.3`` on PHP 8.2 - 8.5
- :file:`composer.json` declares ``extra.typo3/cms.version`` and
  ``extra.typo3/cms.Package.providesPackages`` (:issue:`108345`)
- Rector and Fractor run the TYPO3 v14 rule sets; the one rule that may not be
  applied while TYPO3 v13.4 is supported is skipped explicitly
- PHPStan enables ``reportUnmatchedIgnoredErrors`` and drops the 27 ignore
  patterns that no longer matched anything
- DDEV development environment provides TYPO3 v13 and v14 instances

Version 4.0.0
=============

Release date: 2026-03-01

This is a major release with TYPO3 v12/v13 support and significant modernization.

Breaking Changes
----------------

- **Dropped TYPO3 v11 support** - Use version 3.x for TYPO3 v11
- **Dropped PHP 7.4/8.0/8.1 support** - Minimum PHP 8.2 required
- **Removed SC_OPTIONS hooks** - Migrated to PSR-14 events
- **PHPUnit 10/11/12/13 required** - Test code must use new patterns

New Features
------------

- **TYPO3 v12.4 LTS support** - Full compatibility with TYPO3 v12
- **TYPO3 v13.4 LTS support** - Full compatibility with TYPO3 v13
- **Site Sets support** - Modern site configuration for TYPO3 v13
- **PHP 8.2/8.3/8.4/8.5 support** - Latest PHP versions supported
- **PSR-14 events** - Modern event dispatching system
- **PHP 8 Attributes** - Native attribute-based configuration
- **Docker-based runTests.sh** - TYPO3 core testing pattern

Improvements
------------

- Modernized codebase with strict types
- Enhanced IDE support through better typing
- Improved test coverage (target: 80%+)
- **Mutation testing** - Infection with MSI > 80%
- PHPStan level 10 compliance
- Updated coding standards (PSR-12)

Migration
---------

See :ref:`migration` for detailed upgrade instructions.

Version 3.x
===========

Legacy version supporting TYPO3 v10 and v11. No longer actively developed.
Use version 4.0+ for new installations.

Version 2.x
===========

Legacy version supporting TYPO3 v8 and v9. End of life.

Version 1.x
===========

Initial release for TYPO3 v6 and v7. End of life.
