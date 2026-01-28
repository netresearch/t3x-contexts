.. include:: /Includes.rst.txt

.. _developer:

=====================
Developer Information
=====================

This chapter covers the technical aspects of extending and integrating with
the Contexts extension.

.. _developer-custom-context:

Creating Custom Context Types
=============================

You can create custom context types by implementing the context interface
and registering your type.

Step 1: Create the Context Class
--------------------------------

Extend ``AbstractContext`` and implement the ``match()`` method:

.. code-block:: php

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

           // Use invert() and storeInSession() for proper behavior
           return $this->invert($this->storeInSession(
               $configuredValue === $actualValue
           ));
       }

       private function getActualValue(): string
       {
           // Access the PSR-7 request via Container
           $request = Container::get()->getRequest();
           if ($request === null) {
               return '';
           }

           // Example: get a query parameter
           $params = $request->getQueryParams();
           return $params['myParam'] ?? '';
       }
   }

.. tip::

   The ``AbstractContext`` provides helper methods:

   - ``getConfValue($fieldName)`` - Get FlexForm configuration value
   - ``invert($match)`` - Apply inversion setting if enabled
   - ``storeInSession($match)`` - Cache result in user session
   - ``getMatchFromSession()`` - Retrieve cached match result

Step 2: Register the Context Type
---------------------------------

In your :file:`ext_localconf.php`:

.. code-block:: php

   <?php

   use Netresearch\Contexts\Context\Container;
   use Vendor\MyExtension\Context\Type\MyCustomContext;

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['types']['my_custom'] = [
       'class' => MyCustomContext::class,
       'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:context.my_custom',
   ];

Step 3: Add TCA Configuration
-----------------------------

In :file:`Configuration/TCA/Overrides/tx_contexts_contexts.php`:

.. code-block:: php

   <?php

   $GLOBALS['TCA']['tx_contexts_contexts']['types']['my_custom'] = [
       'showitem' => 'type, title, --palette--;;visibility, my_field',
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

PSR-14 Events
=============

.. versionadded:: 4.0.0
   PSR-14 events replace legacy SC_OPTIONS hooks.

The extension dispatches several PSR-14 events that you can listen to.

ContextMatchEvent
-----------------

Dispatched when a context is being matched.

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Vendor\MyExtension\EventListener;

   use Netresearch\Contexts\Event\ContextMatchEvent;
   use TYPO3\CMS\Core\Attribute\AsEventListener;

   #[AsEventListener(
       identifier: 'my-extension/context-match',
       event: ContextMatchEvent::class
   )]
   final class ContextMatchListener
   {
       public function __invoke(ContextMatchEvent $event): void
       {
           $context = $event->getContext();
           $matches = $event->getMatches();

           // Modify matching behavior
           if ($this->shouldOverride($context)) {
               $event->setMatches(true);
           }
       }
   }

.. _developer-api:

Context API
===========

Checking Contexts Programmatically
----------------------------------

The ``Container`` class provides access to all matched contexts. It uses a
singleton pattern and extends ``ArrayObject`` for easy iteration.

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

For simple matching checks, use the ``ContextMatcher`` API which caches results:

.. code-block:: php

   <?php

   declare(strict_types=1);

   use Netresearch\Contexts\Api\ContextMatcher;

   // Check if a context matches by alias
   if (ContextMatcher::getInstance()->matches('mobile')) {
       // Mobile context is active
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

The ``contextMatch()`` function is provided via TYPO3's ExpressionLanguage
and works in all condition contexts (TypoScript, TSconfig, etc.)

.. _developer-fluid:

Fluid ViewHelpers
=================

The extension provides a ViewHelper for context matching in Fluid templates.

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

The ViewHelper returns ``1`` when the context matches and ``0`` otherwise,
making it compatible with Fluid's condition evaluation.

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

The extension uses PHPUnit with the TYPO3 Testing Framework.

.. code-block:: bash

   # Run all tests
   ./Build/Scripts/runTests.sh all

   # Run unit tests only
   ./Build/Scripts/runTests.sh unit

   # Run with specific PHP version
   ./Build/Scripts/runTests.sh -p 8.3 unit

   # Run with coverage
   ./Build/Scripts/runTests.sh -c unit

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

Enable debug mode to see context matching details:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['debug'] = true;

This outputs context matching information to the TYPO3 debug console.
