.. include:: /Includes.rst.txt

.. _context-types:

=============
Context Types
=============

The extension provides several built-in context types. Each type evaluates
different conditions to determine if a context is active.

.. _context-type-ip:

IP Address Context
==================

Match visitors by their IP address or IP range.

Configuration
-------------

:IP/Range:
   Single IP address, CIDR notation, or range.

Examples:

- Single IP: ``192.168.1.100``
- CIDR: ``10.0.0.0/8``
- Range: ``192.168.1.1-192.168.1.255``
- Multiple: ``192.168.1.0/24,10.0.0.0/8``

Use Cases
---------

- Internal network detection
- Geographic content (by IP geolocation)
- Office vs. external visitors

.. _context-type-domain:

Domain Context
==============

Match based on the accessed domain name.

Configuration
-------------

:Domain:
   Domain name(s) to match against HTTP_HOST.

Examples:

- Single: ``www.example.com``
- Multiple: ``example.com,www.example.com``
- Wildcard: ``*.example.com``

Use Cases
---------

- Multi-domain setups
- Staging vs. production detection
- Brand-specific content

.. _context-type-getparam:

GET Parameter Context
=====================

Match based on URL query parameters.

Configuration
-------------

:Parameter Name:
   The GET parameter name to check.

:Expected Value:
   Value to match (supports regex).

Examples:

- ``?debug=1`` with parameter ``debug`` and value ``1``
- ``?variant=a`` for A/B testing

Use Cases
---------

- A/B testing variants
- Debug mode activation
- Campaign tracking

.. _context-type-postparam:

POST Parameter Context
======================

Match based on POST request data.

Configuration
-------------

:Parameter Name:
   The POST parameter name.

:Expected Value:
   Value to match.

Use Cases
---------

- Form submission contexts
- AJAX request handling

.. _context-type-cookie:

Cookie Context
==============

Match based on cookie values.

Configuration
-------------

:Cookie Name:
   Name of the cookie to check.

:Expected Value:
   Cookie value to match.

Use Cases
---------

- User preferences
- Returning visitor detection
- Consent-based content

.. _context-type-session:

Session Context
===============

Match based on session data.

Configuration
-------------

:Session Key:
   Key in the session data.

:Expected Value:
   Value to match.

Use Cases
---------

- User state tracking
- Shopping cart state
- Authentication state

.. _context-type-combination:

Combination Context
===================

Combine multiple contexts with logical operators.

Configuration
-------------

:Contexts:
   Select multiple contexts to combine.

:Logic:
   AND (all must match) or OR (any must match).

Use Cases
---------

- Complex multi-condition rules
- Nested context hierarchies
