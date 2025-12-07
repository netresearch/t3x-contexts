.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

What does it do?
================

The Contexts extension provides a flexible system for defining contextual
conditions that control content visibility throughout your TYPO3 installation.

Instead of duplicating pages for different scenarios (mobile vs. desktop,
different countries, logged-in vs. anonymous users), you can define contexts
that automatically show or hide content based on various conditions.

Key Features
============

Multi-channel content management
   Define contexts based on IP addresses, domains, query parameters, HTTP headers,
   session data, and more. Content visibility is automatically controlled based
   on the active context.

Page and content element visibility
   Apply contexts to pages, content elements, and records. Elements are
   automatically shown or hidden based on context matching.

Extensible architecture
   Create custom context types through a clean API. The extension provides
   built-in types and allows easy extension for project-specific needs.

Performance optimized
   Context matching is cached and optimized for production environments.
   Minimal overhead on page rendering.

Use Cases
=========

- **Geographic content**: Show different content based on visitor location (IP-based)
- **Device-specific content**: Different layouts for mobile/desktop (via domain or parameter)
- **A/B testing**: Show different content variants based on session or cookie
- **Environment-specific**: Different behavior in staging vs. production
- **User-based**: Content visibility based on frontend user properties

Requirements
============

- TYPO3 v12.4 LTS or v13.4 LTS
- PHP 8.2 or higher

.. tip::

   For TYPO3 v11 support, use version 3.x of this extension.

Related Extensions
==================

Additional context types are available through companion extensions by Netresearch:

`contexts_geolocation <https://github.com/netresearch/t3x-contexts_geolocation>`__
   Location-based contexts: continent, country, and area detection.

`contexts_wurfl <https://github.com/netresearch/t3x-contexts_wurfl>`__
   Device-based contexts: phone, tablet, TV detection, screen sizes, browser capabilities.
