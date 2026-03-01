.. include:: /Includes.rst.txt

.. _developer:

=====================
Developer Information
=====================

This chapter covers the technical aspects of extending and integrating
with the Contexts extension.

.. contents:: On this page
   :local:
   :depth: 2

.. _developer-custom-context:

Creating Custom Context Types
=============================

You can create custom context types by implementing the context
interface and registering your type.

Step 1: Create the Context Class
--------------------------------

Extend ``AbstractContext`` and implement the ``match()`` method:

.. code-block:: php
   :caption: Classes/Context/Type/MyCustomContext.php

   <?php

   declare(strict_types=1);

   namespace Vendor\MyExtension\Context\Type;

   use Netresearch\Contexts\Context\AbstractContext;
   use Netresearch\Contexts\Context\Container;

   final class MyCustomContext extends AbstractContext
   {
       public function match(array $arDependencies = []): bool
       {
           $configuredValue = $this->getConfValue('myField');
           $actualValue = $this->getActualValue();

           return $this->invert($this->storeInSession(
               $configuredValue === $actualValue
           ));
       }

       private function getActualValue(): string
       {
           $request = Container::get()->getRequest();
           if ($request === null) {
               return '';
           }

           $params = $request->getQueryParams();
           return $params['myParam'] ?? '';
       }
   }

.. tip::

   The ``AbstractContext`` provides helper methods:

   - ``getConfValue($fieldName)`` — Get FlexForm configuration
   - ``invert($match)`` — Apply inversion setting if enabled
   - ``storeInSession($match)`` — Cache result in user session
   - ``getMatchFromSession()`` — Retrieve cached match result

Step 2: Register the Context Type
---------------------------------

In your :file:`ext_localconf.php`:

.. code-block:: php
   :caption: ext_localconf.php

   <?php

   use Vendor\MyExtension\Context\Type\MyCustomContext;

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['types']
       ['my_custom'] = [
       'class' => MyCustomContext::class,
       'label' => 'LLL:EXT:my_extension/Resources/Private/'
           . 'Language/locallang.xlf:context.my_custom',
   ];

Step 3: Add TCA Configuration
-----------------------------

In :file:`Configuration/TCA/Overrides/tx_contexts_contexts.php`:

.. code-block:: php
   :caption: Configuration/TCA/Overrides/tx_contexts_contexts.php

   <?php

   $GLOBALS['TCA']['tx_contexts_contexts']['types']
       ['my_custom'] = [
       'showitem' => 'type, title, '
           . '--palette--;;visibility, my_field',
   ];

   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
       'tx_contexts_contexts',
       [
           'my_field' => [
               'label' => 'My Field',
               'config' => [
                   'type' => 'input',
               ],
           ],
       ]
   );

.. _developer-events:

PSR-14 Event Listeners
======================

.. versionadded:: 4.0.0
   PSR-14 event listeners replace legacy SC_OPTIONS hooks.

The extension registers four PSR-14 event listeners that handle
context-based behavior in the frontend and backend. These listeners
are registered via PHP attributes and cannot be removed, but you
can register your own listeners on the same events.

Page Access Control
-------------------

``PageAccessEventListener`` listens to
``AfterPageAndLanguageIsResolvedEvent`` and checks whether the
current page is accessible based on its context restrictions
(``tx_contexts_enable`` / ``tx_contexts_disable``). If access
is denied, it throws an ``ImmediateResponseException`` with a
403 response.

The current page is always checked for its own context
restrictions. Parent pages only propagate restrictions when
``extendToSubpages`` is enabled.

Menu Item Filtering
-------------------

``MenuItemFilterEventListener`` listens to
``FilterMenuItemsEvent`` and removes menu items that should
not be visible in the current context. This ensures navigation
menus reflect context-based visibility.

Icon Overlay Modification
-------------------------

``IconOverlayEventListener`` listens to
``ModifyRecordOverlayIconIdentifierEvent`` and modifies icon
overlays for records that have context-based visibility
settings, providing visual feedback in the backend.

Cache Lifetime Modification
---------------------------

``CacheHashEventListener`` listens to
``ModifyCacheLifetimeForPageEvent`` and can adjust cache
lifetime based on active contexts.

.. _developer-architecture:

Architecture
============

Request Lifecycle
-----------------

The ``ContainerInitialization`` PSR-15 middleware initializes
context matching on every frontend request. It:

1. Sets the PSR-7 request on the ``Container`` singleton
2. Triggers ``Container::initMatching()`` which loads all context
   records from the database and runs ``match()`` on each
3. Only matched contexts are retained in the container

This middleware runs before the page resolver, ensuring contexts
are available throughout the rendering pipeline.

Query Restrictions
------------------

The ``ContextRestriction`` class implements
``EnforceableQueryRestrictionInterface`` and automatically adds
WHERE clauses to all database queries on context-controlled
tables. It uses ``FIND_IN_SET()`` to check whether active
context UIDs appear in the ``tx_contexts_enable`` or
``tx_contexts_disable`` columns.

This means records with context restrictions are automatically
filtered in all frontend queries without additional code.

.. _developer-api:

Context API
===========

Checking Contexts Programmatically
----------------------------------

The ``Container`` class provides access to all matched contexts.
It uses a singleton pattern and extends ``ArrayObject``.

.. code-block:: php

   <?php

   declare(strict_types=1);

   use Netresearch\Contexts\Context\Container;

   // Get the container instance (singleton)
   $container = Container::get();

   // Find a specific context by alias or UID
   $context = $container->find('my-context-alias');
   if ($context !== null) {
       // Context exists and is active (matched)
   }

   // Iterate over all active (matched) contexts
   foreach (Container::get() as $uid => $context) {
       echo $context->getAlias() . ' is active';
   }

Using the ContextMatcher API
----------------------------

For simple matching checks, use the ``ContextMatcher`` API:

.. code-block:: php

   <?php

   declare(strict_types=1);

   use Netresearch\Contexts\Api\ContextMatcher;

   if (ContextMatcher::getInstance()->matches('mobile')) {
       // Mobile context is active
   }

Configuration API
-----------------

Enable context settings on custom tables using the
``Configuration`` API:

.. code-block:: php
   :caption: Configuration/TCA/Overrides/my_table.php

   <?php

   use Netresearch\Contexts\Api\Configuration;

   // Add default visibility settings (enable/disable)
   Configuration::enableContextsForTable(
       'contexts',
       'my_table'
   );

   // With additional custom settings
   Configuration::enableContextsForTable(
       'contexts',
       'my_table',
       [
           'my_setting' => [
               'label' => 'LLL:EXT:my_ext/Resources/'
                   . 'Private/Language/locallang.xlf:setting',
               'flatten' => true,
               'enables' => true,
           ],
       ]
   );

This registers the ``tx_contexts_settings`` column, adds flat
columns (``tx_contexts_enable``, ``tx_contexts_disable``), and
integrates context visibility into the record editing form.

Record API
----------

Check if a record is enabled for the current contexts:

.. code-block:: php

   <?php

   declare(strict_types=1);

   use Netresearch\Contexts\Api\Record;

   // Check if a record is visible in current context
   $row = ['uid' => 42, 'tx_contexts_enable' => '3,5'];
   if (Record::isEnabled('tt_content', $row)) {
       // Record is visible
   }

   // Check a specific setting
   if (Record::isSettingEnabled('my_table', 'my_setting', $row)) {
       // Setting is enabled for this record
   }

TypoScript Conditions
---------------------

Use the ``contextMatch()`` function in TypoScript conditions:

.. code-block:: typoscript

   # Show content only when mobile context is active
   [contextMatch('mobile')]
       page.10.wrap = <div class="mobile-wrapper">|</div>
   [END]

   # Combine with other conditions
   [contextMatch('internal') && tree.level > 2]
       lib.breadcrumb.show = 1
   [END]

The ``contextMatch()`` function is provided via TYPO3's
ExpressionLanguage and works in all condition contexts
(TypoScript, TSconfig, etc.).

.. _developer-fluid:

Fluid ViewHelpers
=================

The extension provides a ViewHelper for context matching in Fluid
templates.

Register the Namespace
----------------------

Add the namespace to your Fluid template:

.. code-block:: html

   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
         xmlns:contexts="http://typo3.org/ns/Netresearch/Contexts/ViewHelpers"
         data-namespace-typo3-fluid="true">

Or use the inline namespace declaration:

.. code-block:: html

   {namespace contexts=Netresearch\Contexts\ViewHelpers}

Using the Matches ViewHelper
----------------------------

Check if a context is active using the ``matches`` ViewHelper:

.. code-block:: html

   <f:if condition="{contexts:matches(alias: 'mobile')}">
       <f:then>
           <p>Mobile context is active</p>
       </f:then>
       <f:else>
           <p>Mobile context is not active</p>
       </f:else>
   </f:if>

The ViewHelper returns ``1`` when the context matches and ``0``
otherwise, making it compatible with Fluid's condition evaluation.

Practical Examples
------------------

**Conditional rendering based on context:**

.. code-block:: html

   <f:if condition="{contexts:matches(alias: 'internal')}">
       <div class="admin-toolbar">
           <!-- Only shown for internal network -->
       </div>
   </f:if>

**Combining with other conditions:**

.. code-block:: html

   <f:if condition="{contexts:matches(alias: 'premium')} && {user}">
       <div class="premium-content">
           <!-- Premium user content -->
       </div>
   </f:if>

.. _developer-testing:

Testing
=======

Running Tests
-------------

Tests can be run via Composer scripts or the Docker-based
``runTests.sh`` script.

**Composer scripts** (local PHP required):

.. code-block:: bash

   # Unit tests
   composer ci:test:php:unit

   # Functional tests (requires database)
   composer ci:test:php:functional

   # Static analysis
   composer ci:test:php:phpstan

   # Code style check
   composer ci:test:php:cgl

**Docker-based** (no local PHP needed):

.. code-block:: bash

   # Unit tests (default suite)
   ./Build/Scripts/runTests.sh -s unit

   # Unit tests with coverage
   ./Build/Scripts/runTests.sh -s unitCoverage

   # Functional tests with SQLite
   ./Build/Scripts/runTests.sh -s functional -d sqlite

   # PHPStan
   ./Build/Scripts/runTests.sh -s phpstan

   # With specific PHP version
   ./Build/Scripts/runTests.sh -s unit -p 8.4

   # Show all options
   ./Build/Scripts/runTests.sh -h

The Docker-based script uses ``ghcr.io/typo3/core-testing-php*``
images and requires Docker or Podman.

Writing Tests for Custom Contexts
---------------------------------

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Vendor\MyExtension\Tests\Unit\Context\Type;

   use PHPUnit\Framework\Attributes\Test;
   use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
   use Vendor\MyExtension\Context\Type\MyCustomContext;

   final class MyCustomContextTest extends UnitTestCase
   {
       #[Test]
       public function matchReturnsTrueForValidCondition(): void
       {
           $context = new MyCustomContext();
           // Setup test conditions

           self::assertTrue($context->match());
       }
   }

.. _developer-debugging:

Debugging
=========

Enable context debugging via the Site Set setting:

.. code-block:: yaml
   :caption: config/sites/<identifier>/config.yaml

   settings:
     contexts:
       debug: true

This adds an HTML comment to the page header indicating that
debug mode is active. For detailed context matching inspection,
use the TYPO3 Admin Panel or check the ``tx_contexts_contexts``
table for context configuration and the ``tx_contexts_enable`` /
``tx_contexts_disable`` columns on your records.
