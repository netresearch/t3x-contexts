.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

.. _installation-requirements:

Requirements
============

.. csv-table:: Version compatibility
   :header: "Extension Version", "TYPO3", "PHP"
   :widths: 20, 30, 30

   "4.x", "12.4 LTS, 13.4 LTS", "8.2 - 8.4"
   "3.x", "11.5 LTS", "7.4 - 8.1"

The recommended way to install this extension is via Composer.

.. _installation-composer:

Installation via Composer
=========================

.. code-block:: bash

   composer require netresearch/contexts

After installation, activate the extension in the TYPO3 Extension Manager or
via CLI:

.. code-block:: bash

   vendor/bin/typo3 extension:activate contexts

.. _installation-site-set:

Site Set Configuration (TYPO3 v13+)
===================================

.. versionadded:: 4.0.0
   Site Sets support for TYPO3 v13.

For TYPO3 v13 and later, you can include the Contexts site set in your site
configuration:

1. Go to :guilabel:`Site Management > Sites`
2. Edit your site configuration
3. In the :guilabel:`Sets` tab, add "Contexts - Multi-channel content visibility"

Alternatively, add it to your site's :file:`config/sites/<identifier>/config.yaml`:

.. code-block:: yaml

   imports:
     - { resource: "EXT:contexts/Configuration/Sets/Contexts/config.yaml" }

.. _installation-classic:

Classic TypoScript Include (TYPO3 v12+)
=======================================

For traditional TypoScript setup, include the static template:

1. Go to :guilabel:`Web > Template`
2. Select your root page
3. Edit the template record
4. In :guilabel:`Includes`, add "Contexts" to the selected items

.. _installation-database:

Database Updates
================

After installation, run the database analyzer to create required tables:

.. code-block:: bash

   vendor/bin/typo3 database:updateschema

Or use the :guilabel:`Admin Tools > Maintenance > Analyze Database Structure`
module in the TYPO3 backend.

.. _installation-verification:

Verification
============

After installation, you should see:

1. A new "Contexts" module in the backend (under Admin Tools)
2. "Context visibility" options in page and content element properties
3. Database tables: `tx_contexts_contexts`, `tx_contexts_settings`
