.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

Extension Configuration
=======================

The extension can be configured via the TYPO3 Extension Configuration
(:guilabel:`Admin Tools > Settings > Extension Configuration > contexts`).

.. confval:: enableContexts

   :type: boolean
   :Default: true

   Enable or disable context processing globally.

.. confval:: contextMatchMode

   :type: string
   :Default: all

   How multiple contexts are evaluated:

   - ``all``: All assigned contexts must match (AND logic)
   - ``any``: At least one context must match (OR logic)

Site Set Settings (TYPO3 v13+)
==============================

When using Site Sets, configure contexts in your site's settings:

.. code-block:: yaml

   settings:
     contexts:
       debug: false
       matchMode: 'all'

.. confval:: contexts.debug

   :type: boolean
   :Default: false

   Enable debug output for context matching (development only).

.. confval:: contexts.matchMode

   :type: string
   :Default: all

   Context match logic: ``all`` (AND) or ``any`` (OR).

TypoScript Configuration
========================

Basic TypoScript settings for the contexts extension:

.. code-block:: typoscript

   plugin.tx_contexts {
       settings {
           # Enable debug mode
           debug = 0

           # Default match mode
           matchMode = all
       }
   }

Page TSconfig
=============

Control context field visibility in the backend:

.. code-block:: typoscript

   # Hide context fields from certain user groups
   TCEFORM.pages.tx_contexts_visibility.disabled = 1
   TCEFORM.tt_content.tx_contexts_visibility.disabled = 1

User TSconfig
=============

Per-user context field configuration:

.. code-block:: typoscript

   # Allow specific contexts only
   options.contexts.allowedTypes = ip,domain,getparam

Caching Considerations
======================

Context-dependent content affects caching. The extension automatically:

1. Adds context identifiers to cache tags
2. Varies cache entries by active contexts
3. Clears affected caches when context definitions change

For optimal performance:

- Use a reverse proxy (Varnish) with context-aware VCL
- Consider context values in your caching strategy
- Use appropriate cache lifetimes for dynamic contexts
