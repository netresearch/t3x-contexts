<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md — Documentation/

RST documentation for docs.typo3.org publication.

## Overview

```
Documentation/
├── Index.rst              # Main entry point with card-grid navigation
├── guides.xml             # Modern PHP-based rendering config
├── Settings.cfg           # Legacy Sphinx config (fallback)
├── Introduction/          # Overview, features, screenshots
├── Installation/          # Setup instructions
├── Configuration/         # Settings and options
├── ContextTypes/          # Context type reference
├── Developer/             # API and extension guide
├── Migration/             # Upgrade guides
└── Changelog/             # Version history
```

## Setup & Environment

```bash
# Render locally with DDEV
ddev docs

# Or with Docker directly
docker run --rm \
    -v ./Documentation:/project/docs \
    -v ./Documentation-GENERATED-temp:/project/Documentation-GENERATED-temp \
    ghcr.io/typo3-documentation/render-guides:latest

# View rendered output
open Documentation-GENERATED-temp/Index.html
```

## Build & Tests

```bash
# Validate RST syntax
ddev docs  # Warnings shown during render

# Check output directory (TYPO3 convention)
ls Documentation-GENERATED-temp/
```

## Code Style & Conventions

### RST Formatting

- **Headings**: Use sentence case (not Title Case)
- **Lists**: End items with periods
- **Code blocks**: Always specify language
- **Cross-references**: Use `:ref:` with labels

### Heading Hierarchy

```rst
==========
Page Title
==========

Section
=======

Subsection
----------

Sub-subsection
~~~~~~~~~~~~~~
```

### Permalink Anchors

Always add anchors before sections:

```rst
.. _context-types-query-parameter:

Query Parameter Context
=======================
```

### Card-Grid Navigation (Index.rst)

```rst
.. card-grid::
   :columns: 1
   :columns-md: 2
   :gap: 4
   :card-height: 100

   .. card:: Introduction

      Overview of the extension features.

      .. card-footer:: :ref:`introduction`
         :button-style: btn btn-primary stretched-link
```

### Configuration Values (confval)

```rst
.. confval:: useSession
   :type: boolean
   :Default: false
   :Path: Extension settings

   Enable session-based context persistence.
```

### Version Annotations

```rst
.. versionadded:: 4.0.0
   Added support for TYPO3 v13.

.. versionchanged:: 4.0.0
   Deprecated `legacyMethod()` in favor of `newMethod()`.

.. deprecated:: 4.0.0
   Will be removed in v5.0.
```

## Security & Safety

- Never include real credentials in examples
- Use placeholder values: `admin`, `Password:joh316`, `example.com`
- Sanitize any user-contributed content

## PR/Commit Checklist

- [ ] RST renders without warnings: `ddev docs`
- [ ] Cross-references resolve (no broken `:ref:` links)
- [ ] Code examples have language specified
- [ ] Sentence case headings
- [ ] Permalink anchors on all sections
- [ ] README.md synchronized (if applicable)

## Good vs Bad Examples

### Headings

```rst
# Good: Sentence case
Query parameter context
=======================

# Bad: Title Case
Query Parameter Context
=======================
```

### Code Blocks

```rst
# Good: Language specified
.. code-block:: php

   $context->match($request);

# Bad: No language
::

   $context->match($request);
```

### Version Notes

```rst
# Good: TYPO3 directive
.. versionadded:: 4.0.0
   New feature description.

# Bad: Plain text
Since version 4.0.0, this feature...
```

### Output Directory

```rst
# Good: TYPO3 standard
Documentation-GENERATED-temp/

# Bad: Non-standard paths
docs/
Documentation-rendered/
```

## When Stuck

- TYPO3 Documentation Guide: https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/
- RST Primer: https://www.sphinx-doc.org/en/master/usage/restructuredtext/basics.html
- TYPO3 Slack: #typo3-documentation

## House Rules

- Output directory must be `Documentation-GENERATED-temp/` (TYPO3 convention)
- Apache needs `DirectoryIndex Index.html` (capital I)
- Keep README.md and Documentation/ synchronized
- Webhook to docs.typo3.org triggers on push to main
