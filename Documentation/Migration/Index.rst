.. include:: /Includes.rst.txt

.. _migration:

================
Migration Guide
================

This guide covers migrating from previous versions of the Contexts extension.

.. _migration-v4-to-v5:

Migrating from v4.x to v5.0
===========================

Version 5.0 targets TYPO3 v13.4 LTS and v14.3 LTS and completes the removal of
the frontend APIs that TYPO3 dropped in v13 and v14.

Breaking Changes
----------------

TYPO3 Version
~~~~~~~~~~~~~

**Before:** TYPO3 v12.4 LTS and v13.4 LTS

**After:** TYPO3 v13.4 LTS and v14.3 LTS

TYPO3 v12.4 is no longer supported. Use version 4.x for TYPO3 v12.4
installations.

Removed API
~~~~~~~~~~~

The following methods were bound to the ``TypoScriptFrontendController`` hooks
removed in TYPO3 v13.0 (:issue:`102932`) and are gone:

.. list-table::
   :header-rows: 1
   :widths: 60 40

   * - Removed
     - Replacement
   * - ``FrontendControllerService::registerQueryParameter()``
     - :php:`Netresearch\Contexts\Service\QueryParameterService`
   * - ``FrontendControllerService::createHashBase()``
     - ``PageCacheIdentifierEventListener``
   * - ``FrontendControllerService::configArrayPostProc()``
     - ``TypoScriptConfigEventListener``
   * - ``PageService::createHashBase()``
     - ``PageService::getHashString()``
   * - ``CacheHashEventListener`` (empty stub)
     - ``PageCacheIdentifierEventListener``

Custom context types that extend
:php:`Netresearch\Contexts\Context\AbstractContext` are affected by the removal
of the ``TypoScriptFrontendController``
(:issue:`107831`) and of :php:`GeneralUtility::getIndpEnv()`
(deprecated in TYPO3 v14.3):

.. list-table::
   :header-rows: 1
   :widths: 60 40

   * - Removed protected method
     - Replacement
   * - ``getTypoScriptFrontendController()``
     - ``getRequest()`` / ``getFrontendUser()``
   * - ``getIndpEnv()``
     - ``getNormalizedParams()``

Fixed Behaviour
---------------

Four defects are fixed by this release:

*   The **session context** and the ``use_session`` persistence of the GET
    parameter context read the frontend user from the removed
    ``TypoScriptFrontendController``. They now read the ``frontend.user``
    request attribute and match again.
*   **Context-aware page caching and** ``config.linkVars`` were registered on
    hooks removed in TYPO3 v13.0, so every context variant of a page shared one
    page cache entry and the switching GET parameter was dropped from generated
    links. They now use
    :php:`\TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent`
    and :php:`\TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent`.
*   The **FlexForm of the context type configuration** was selected via
    ``ds_pointerField``, removed in TYPO3 v14.0 (:issue:`107047`). On v14.3
    every read of ``tx_contexts_contexts.type_conf`` threw an
    ``InvalidTcaException``, so context records could neither be rendered nor
    saved. See :ref:`developer-flexform-data-structure`.
*   **Frontend caches are invalidated when a context record changes.** The
    result of :php:`ModifyTypoScriptConfigEvent` is cached under an identifier
    derived from the TypoScript sources, so a new or renamed GET parameter
    context previously did not reach ``config.linkVars`` until the caches were
    flushed by hand. See :ref:`configuration-caching`.

Removed Site Set Settings
-------------------------

``contexts.matchMode`` and ``contexts.cacheLifetimeModifier`` have been removed
from ``Configuration/Sets/Contexts/``. Neither setting was ever read by the
extension. Remove them from your site configuration; there is no replacement.
``contexts.debug`` is unchanged.

Removed Files
-------------

``ext_tables.php`` has been removed - it only held a TYPO3 v12 code path and the
file itself is deprecated since TYPO3 v14.3 (:issue:`109438`). The v13+
equivalent lives in ``Configuration/TCA/tx_contexts_contexts.php`` via
``ctrl.security.ignorePageTypeRestriction``.

``$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']`` is no longer written
in ``ext_localconf.php``; the setting was removed in TYPO3 v13.2
(:issue:`103752`) and rootline relations on ``pages`` are always resolved since.

Step-by-Step Migration
----------------------

1. **Update TYPO3** to v13.4 or v14.3

2. **Update extension** via Composer:

   .. code-block:: bash

      composer require netresearch/contexts:^5.0

3. **Clear all caches**:

   .. code-block:: bash

      vendor/bin/typo3 cache:flush

4. **Review custom context types** for the removed protected methods listed
   above

.. _migration-v3-to-v4:

Migrating from v3.x to v4.0
===========================

Version 4.0 introduces significant changes for TYPO3 v12/v13 compatibility.

Breaking Changes
----------------

PHP Version
~~~~~~~~~~~

**Before:** PHP 7.4 - 8.1

**After:** PHP 8.2+

Update your deployment and CI pipelines accordingly.

TYPO3 Version
~~~~~~~~~~~~~

**Before:** TYPO3 v11

**After:** TYPO3 v12.4 LTS and v13.4 LTS only

TYPO3 v11 and earlier are no longer supported. Use version 3.x for older
TYPO3 installations.

Hook Migration
~~~~~~~~~~~~~~

All SC_OPTIONS hooks have been replaced with PSR-14 events.

**Before (v3.x):**

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']
       ['processDatamapClass'][] = MyHook::class;

**After (v4.0):**

.. code-block:: php

   #[AsEventListener(
       identifier: 'my-extension/datahandler',
       event: AfterDatabaseOperationsEvent::class
   )]
   final class MyEventListener { ... }

Dependency Injection
~~~~~~~~~~~~~~~~~~~~

Services must now be properly configured for dependency injection.

**Before:** Direct instantiation or makeInstance()

**After:** Constructor injection via Services.yaml

.. code-block:: yaml

   services:
     Vendor\MyExtension\Service\MyService:
       public: true

Step-by-Step Migration
----------------------

1. **Update PHP version** to 8.2 or higher

2. **Update TYPO3** to v12.4 or v13.4

3. **Update extension** via Composer:

   .. code-block:: bash

      composer require netresearch/contexts:^4.0

4. **Run database migrations**:

   .. code-block:: bash

      vendor/bin/typo3 database:updateschema

5. **Clear all caches**:

   .. code-block:: bash

      vendor/bin/typo3 cache:flush

6. **Review custom context types** - update to new interface if needed

7. **Migrate hooks to events** - see Event Migration below

Event Migration Reference
-------------------------

.. list-table::
   :header-rows: 1
   :widths: 50 50

   * - Old Hook (SC_OPTIONS)
     - New PSR-14 Event
   * - hook_checkEnableFields
     - AfterPageAndLanguageIsResolvedEvent
   * - filterMenuPages
     - FilterMenuItemsEvent
   * - overrideIconOverlay (IconFactory)
     - ModifyRecordOverlayIconIdentifierEvent
   * - createHashBase
     - BeforePageCacheIdentifierIsHashedEvent (since v5.0)
   * - configArrayPostProc
     - ModifyTypoScriptConfigEvent (since v5.0)

.. _migration-site-sets:

Adopting Site Sets (Optional)
=============================

TYPO3 v13 introduces Site Sets as the modern way to manage site configuration.
This is optional and backward compatible.

Benefits of Site Sets
---------------------

- Cleaner site configuration
- Composable settings
- Better multi-site management
- IDE autocompletion for settings

Migration Steps
---------------

1. In your site configuration, add the contexts set:

   .. code-block:: yaml

      imports:
        - { resource: "EXT:contexts/Configuration/Sets/Contexts/config.yaml" }

2. Configure settings in your site configuration:

   .. code-block:: yaml

      settings:
        contexts:
          debug: false

3. Remove any static TypoScript include (no longer needed with
   Site Sets)

.. _migration-testing:

Updating Tests
==============

Test code must be updated for PHPUnit 10/11/12/13 compatibility.

Annotation to Attribute Migration
---------------------------------

**Before:**

.. code-block:: php

   /**
    * @test
    * @dataProvider myProvider
    */
   public function myTest(): void { }

**After:**

.. code-block:: php

   use PHPUnit\Framework\Attributes\Test;
   use PHPUnit\Framework\Attributes\DataProvider;

   #[Test]
   #[DataProvider('myProvider')]
   public function myTest(): void { }

Deprecated Methods
------------------

The ``$this->at()`` method is removed. Use explicit mock configuration:

**Before:**

.. code-block:: php

   $mock->expects($this->at(0))->method('foo')->willReturn('a');
   $mock->expects($this->at(1))->method('foo')->willReturn('b');

**After:**

.. code-block:: php

   $mock->expects($this->exactly(2))
       ->method('foo')
       ->willReturnOnConsecutiveCalls('a', 'b');

.. _migration-troubleshooting:

Troubleshooting
===============

Common Issues
-------------

**"Class not found" errors:**

Clear all caches and ensure Composer autoload is regenerated:

.. code-block:: bash

   composer dump-autoload
   vendor/bin/typo3 cache:flush

**Context not matching:**

Enable debug mode to see matching details:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['debug'] = true;

**Backend module missing:**

Ensure the extension is properly activated:

.. code-block:: bash

   vendor/bin/typo3 extension:activate contexts

Getting Help
------------

- GitHub Issues: https://github.com/netresearch/t3x-contexts/issues
- TYPO3 Slack: #ext-contexts channel
