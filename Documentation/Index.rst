.. include:: /Includes.rst.txt

.. _start:

===================
Contexts Extension
===================

:Extension key:
   contexts

:Package name:
   netresearch/contexts

:Version:
   |release|

:Language:
   en

:Author:
   Netresearch DTT GmbH

:License:
   This document is published under the
   `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
   license.

:Rendered:
   |today|

----

Multi-channel content visibility for TYPO3. Define contexts based on various
conditions (IP address, domain, query parameters, HTTP headers, sessions, etc.)
and control content visibility across your entire TYPO3 installation.

.. versionadded:: 4.0.0
   TYPO3 12.4/13.4 LTS support, PHP 8.2+ requirement, PSR-14 events.

----

.. card-grid::
   :columns: 1
   :columns-md: 2
   :gap: 4
   :class: pb-4
   :card-height: 100

   .. card:: :ref:`Introduction <introduction>`

      Learn what the Contexts extension does and how it enables multi-channel
      content delivery in TYPO3.

   .. card:: :ref:`Installation <installation>`

      Install and configure the extension for TYPO3 12.4 or 13.4 LTS.

   .. card:: :ref:`Configuration <configuration>`

      Configure contexts and assign them to pages and content elements.

   .. card:: :ref:`Context Types <context-types>`

      Explore the built-in context types: IP, Domain, Query Parameter,
      HTTP Header, Session, and Combination contexts.

   .. card:: :ref:`Developer Guide <developer>`

      Integrate contexts in Fluid templates, TypoScript conditions,
      and PHP code using the public API.

   .. card:: :ref:`Migration <migration>`

      Upgrade guide from previous versions to 4.x.

.. toctree::
   :maxdepth: 2
   :titlesonly:
   :hidden:

   Introduction/Index
   Installation/Index
   Configuration/Index
   ContextTypes/Index
   Developer/Index
   Migration/Index
   Changelog/Index

----

**Credits**

Developed and maintained by `Netresearch DTT GmbH <https://www.netresearch.de/>`__.

.. Meta Menu

.. toctree::
   :hidden:

   Sitemap
