.. include:: /Includes.rst.txt

.. _migration:

================
Migration Guide
================

This guide covers migrating from previous versions of the Contexts extension.

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

**Before:** TYPO3 v10 - v11

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

+------------------------------------------+----------------------------------------+
| Old Hook (SC_OPTIONS)                    | New PSR-14 Event                       |
+==========================================+========================================+
| processDatamapClass                      | AfterDatabaseOperationsEvent           |
+------------------------------------------+----------------------------------------+
| filterMenuPages                          | FilterMenuItemsEvent                   |
+------------------------------------------+----------------------------------------+
| hook_checkEnableFields                   | ModifyRecordOverlayEvent               |
+------------------------------------------+----------------------------------------+

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

2. Move TypoScript constants to site settings:

   **Before (TypoScript Constants):**

   .. code-block:: typoscript

      plugin.tx_contexts.settings.debug = 1

   **After (Site Settings):**

   .. code-block:: yaml

      settings:
        contexts:
          debug: true

3. Remove the static TypoScript include (no longer needed with Site Sets)

.. _migration-testing:

Updating Tests
==============

Test code must be updated for PHPUnit 10/11 compatibility.

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
