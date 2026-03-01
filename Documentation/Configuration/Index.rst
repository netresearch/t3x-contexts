.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

Site Set Settings (TYPO3 v13+)
==============================

When using Site Sets, the extension provides configurable settings
in your site configuration. Add the contexts Site Set to your site:

.. code-block:: yaml
   :caption: config/sites/<identifier>/config.yaml

   imports:
     - { resource: "EXT:contexts/Configuration/Sets/Contexts/config.yaml" }

   settings:
     contexts:
       debug: false

The following settings are available:

.. confval:: contexts.debug
   :name: siteset-contexts-debug

   :type: boolean
   :Default: false

   Enable debug output in the frontend. When enabled, an HTML comment
   ``<!-- Contexts Extension Debug Mode Active -->`` is added to the
   page header. Use in development only.

.. confval:: contexts.matchMode

   :type: string
   :Default: all

   How multiple contexts are evaluated when checking visibility:

   - ``all``: All assigned contexts must match (AND logic)
   - ``any``: At least one context must match (OR logic)

.. confval:: contexts.cacheLifetimeModifier

   :type: integer
   :Default: 0

   Modify cache lifetime when contexts are active (in seconds).
   ``0`` means no modification.

Page and Content Element Settings
=================================

Context visibility is configured directly on page and content element
records via the **Contexts** tab in the TYPO3 backend.

Each context record appears with two options:

- **Visible: yes** — record is only shown when the context is active
- **Visible: no** — record is hidden when the context is active

The extension adds two database columns to controlled tables:

``tx_contexts_enable``
   Comma-separated list of context UIDs that must be active for the
   record to be visible.

``tx_contexts_disable``
   Comma-separated list of context UIDs that hide the record when
   active.

.. _configuration-caching:

Caching Considerations
======================

Context-dependent content affects page caching. The extension handles
this through several mechanisms:

1. **Query restriction**: The ``ContextRestriction`` class
   automatically adds WHERE clauses to database queries, filtering
   records based on active contexts.

2. **Cache hash modification**: When query parameter contexts are
   active, the extension adds context identifiers to the page cache
   hash, ensuring separate cache entries per context combination.

3. **Menu filtering**: Menu items are filtered based on context
   visibility settings, so navigation reflects the current context.

.. tip::

   For pages that depend heavily on context state, consider using
   ``config.no_cache = 1`` in TypoScript or use context-aware
   cache tags to ensure correct content delivery.
