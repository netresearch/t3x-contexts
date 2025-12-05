.. include:: /Includes.rst.txt

.. _changelog:

=========
Changelog
=========

All notable changes to this project are documented here.

Version 4.0.0
=============

Release date: 2025

This is a major release with TYPO3 v12/v13 support and significant modernization.

Breaking Changes
----------------

- **Dropped TYPO3 v11 support** - Use version 3.x for TYPO3 v11
- **Dropped PHP 7.4/8.0/8.1 support** - Minimum PHP 8.2 required
- **Removed SC_OPTIONS hooks** - Migrated to PSR-14 events
- **PHPUnit 10/11 required** - Test code must use new patterns

New Features
------------

- **TYPO3 v12.4 LTS support** - Full compatibility with TYPO3 v12
- **TYPO3 v13.4 LTS support** - Full compatibility with TYPO3 v13
- **Site Sets support** - Modern site configuration for TYPO3 v13
- **PHP 8.2/8.3/8.4 support** - Latest PHP versions supported
- **PSR-14 events** - Modern event dispatching system
- **PHP 8 Attributes** - Native attribute-based configuration

Improvements
------------

- Modernized codebase with strict types
- Enhanced IDE support through better typing
- Improved test coverage (target: 80%+)
- PHPStan level 8 compliance
- Updated coding standards (PSR-12)

Migration
---------

See :ref:`migration` for detailed upgrade instructions.

Version 3.x
===========

Legacy version supporting TYPO3 v10 and v11. No longer actively developed.
Use version 4.0+ for new installations.

Version 2.x
===========

Legacy version supporting TYPO3 v8 and v9. End of life.

Version 1.x
===========

Initial release for TYPO3 v6 and v7. End of life.
