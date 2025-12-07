.. include:: /Includes.rst.txt

.. _context-types:

=============
Context Types
=============

The extension provides several built-in context types. Each type evaluates
different conditions to determine if a context is active.

.. contents:: On this page
   :local:
   :depth: 2

.. _context-type-ip:

IP Address Context
==================

Match visitors by their IP address or IP range. Supports both IPv4 and IPv6.

Configuration
-------------

.. confval:: IP/Range

   :type: string
   :required: true

   Single IP address, CIDR notation, range, or comma-separated list.

Examples:

- Single IP: ``192.168.1.100``
- CIDR notation: ``10.0.0.0/8``, ``FE80::/16``
- Range: ``192.168.1.1-192.168.1.255``
- Wildcards: ``80.76.201.*``, ``80.76.*.37``
- Multiple: ``192.168.1.0/24,10.0.0.0/8``

Use Cases
---------

- Internal network detection (show admin tools for office IPs)
- Geographic content (by IP geolocation with ``contexts_geolocation``)
- Office vs. external visitor differentiation
- Development/staging environment detection

.. _context-type-domain:

Domain Context
==============

Match based on the accessed domain name (HTTP_HOST).

Configuration
-------------

.. confval:: Domain

   :type: string
   :required: true

   Domain name(s) to match. One domain per line.

**Matching Rules:**

- Without leading dot: Exact match only

  - ``www.example.org`` will **not** match ``example.org``

- With leading dot: Matches all subdomains

  - ``.example.org`` matches ``www.example.org``, ``shop.example.org``, etc.

Examples:

- Single: ``www.example.com``
- Multiple (one per line)::

     example.com
     www.example.com

- Subdomain wildcard: ``.example.com``

Use Cases
---------

- Multi-domain setups (different content per domain)
- Staging vs. production detection
- Brand-specific content on shared installations
- Language-specific domains

.. _context-type-getparam:

Query Parameter Context
=======================

Match based on URL query parameters (GET parameters).

Configuration
-------------

.. confval:: Parameter Name

   :type: string
   :required: true

   The GET parameter name to check.

.. confval:: Expected Value

   :type: string
   :required: false

   Value to match. Supports regular expressions. If empty, any non-empty
   value activates the context.

.. confval:: Store in Session

   :type: boolean
   :default: false

   When enabled, the context state is stored in the user session, persisting
   across page navigations even after the parameter is removed from the URL.

Examples:

- ``?debug=1`` with parameter ``debug`` and value ``1``
- ``?variant=a`` for A/B testing
- ``?affID=partner`` for affiliate tracking

Use Cases
---------

- A/B testing variants
- Debug mode activation
- Campaign and affiliate tracking
- Feature flags via URL

.. _context-type-httpheader:

HTTP Header Context
===================

.. versionadded:: 3.0.0
   HTTP Header context for matching request headers.

Match based on HTTP request headers sent by the browser or proxy.

Configuration
-------------

.. confval:: Header Name

   :type: string
   :required: true

   The HTTP header name to check (case-insensitive).

.. confval:: Expected Value

   :type: string
   :required: false

   Value to match. Supports regular expressions. If empty, any non-empty
   value activates the context.

.. confval:: Store in Session

   :type: boolean
   :default: false

   When enabled, the context state persists in the user session.

Examples:

- ``X-Forwarded-For`` for proxy detection
- ``Accept-Language`` for language preferences
- ``User-Agent`` for browser/device detection
- Custom headers from CDN or load balancer

Use Cases
---------

- CDN or proxy detection (``X-Forwarded-For``, ``CF-Connecting-IP``)
- Mobile device detection via ``User-Agent``
- Accept-Language based content
- Custom application headers
- Bot detection

.. _context-type-session:

Session Context
===============

Match based on frontend user session data.

Configuration
-------------

.. confval:: Session Key

   :type: string
   :required: true

   Key in the session data to check.

.. confval:: Expected Value

   :type: string
   :required: false

   Value to match. If empty, checks if the key exists and is not NULL.

Use Cases
---------

- User state tracking
- Shopping cart state (cart has items)
- Login/authentication state
- Multi-step form progress
- User preference flags

.. _context-type-combination:

Combination Context
===================

Combine multiple contexts with logical operators for complex rules.

Configuration
-------------

.. confval:: Expression

   :type: string
   :required: true

   Logical expression combining context aliases.

**Operators:**

- ``&&`` - logical AND (all must match)
- ``||`` - logical OR (any must match)
- ``!`` - negation (must NOT match)
- ``(...)`` - parentheses for grouping

Examples:

- ``mobile && !tablet`` - Mobile but not tablet
- ``internal || admin`` - Internal network or admin user
- ``(german || austrian) && !guest`` - German-speaking non-guest
- ``campaign_a && !already_converted`` - Campaign A visitors who haven't converted

Use Cases
---------

- Complex multi-condition business rules
- Nested context hierarchies
- Exclusion rules (show to A but not if B)
- Compound targeting (A AND B OR C)

.. tip::

   Use descriptive context aliases to make combination expressions readable.
   For example: ``premium_user && active_campaign && !opted_out``

Related Extensions
==================

Additional context types are available through companion extensions:

contexts_geolocation
   Location-based contexts using IP geolocation:

   - Continent detection
   - Country detection
   - Area/region detection

contexts_wurfl
   Device-based contexts using WURFL database:

   - Device type (phone, tablet, TV, desktop)
   - Screen size detection
   - Browser capabilities
