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

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Vendor\MyExtension\Context\Type;

   use Netresearch\Contexts\Context\AbstractContext;

   final class MyCustomContext extends AbstractContext
   {
       public function match(array $arDependencies = []): bool
       {
           $configuredValue = $this->getConfValue('myField');
           $actualValue = $this->getActualValue();

           return $configuredValue === $actualValue;
       }

       private function getActualValue(): string
       {
           // Your matching logic here
           return '';
       }
   }

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

.. code-block:: php

   <?php

   use Netresearch\Contexts\Context\Container;

   // Get all active contexts
   $activeContexts = Container::getActiveContexts();

   // Check if specific context is active
   if (Container::isContextActive('my-context-alias')) {
       // Context-specific logic
   }

   // Get context by alias
   $context = Container::getContextByAlias('my-context-alias');
   if ($context !== null && $context->match()) {
       // Handle matched context
   }

Getting Context Settings
------------------------

.. code-block:: php

   <?php

   use Netresearch\Contexts\Context\Setting;

   // Get setting for a record
   $setting = Setting::getForRecord('tt_content', $uid);

   // Check visibility
   if ($setting->isEnabled()) {
       // Record is visible in current context
   }

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
