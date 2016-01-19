**********************
Multi-channel contexts
**********************

.. image:: https://api.travis-ci.org/netresearch/t3x-contexts.png
   :target: https://travis-ci.org/netresearch/t3x-contexts
   :align: right

Show and hide pages and content elements based on configurable "contexts".
With the use of contexts, TYPO3 is able to do multi-channel output.

Examples for contexts:

- Screen size < 500px
- Browser runs on a tablet or mobile phone
- Location is 15km around a certain place
- User is from one of certain countries
- User entered website with GET-Parameter ``affID=foo``
- User IP matches a given rule
- A session variable is set
- A combination of any other rules

Apart from the context rules, this extension also provides an API to use
contexts in your own extensions.

.. contents::

=====
Setup
=====
1. Install and activate extension ``contexts``
2. Clear TYPO3 cache

Optional: Install extensions ``contexts_geolocation`` for location-based
context rules (continent, country, area) and
``contexts_wurfl`` for device-based rules
(type check: phone, tablet, TV, ...; screen sizes, device/browser type).


===========================
Creating and using contexts
===========================

Creating a context
==================
1. Log into the TYPO3 backend as administrator
2. Goto Web/List view, root page (ID 0)
3. Create a new record: TYPO3 contexts -> Context

   - Give it a title, e.g. "Affiliate ID: foo"
   - Select a type: "GET parameter"
   - GET parameter name: ``affID``
   - Parameter value: ``foo``
   - Activate "Store result in user session"
   - Save and close

Using a context
===============
1. Goto Web/Page, select a page
2. Edit a content element
3. Select the "Contexts" tab
4. For Context "Affiliate ID: foo", select "Visible: yes"
5. Save

View the page. The content element is not visible.

Now add ``?affID=foo`` to the URL and load it.
The content element will be visible now.

You can visit other pages now. When you come back, the content element
will still be visible - even though the GET parameter is not in the URL
anymore - because "Store result in user session" had been activated.


=============
Context types
=============
The ``contexts`` extension ships with a number of simple contexts.
All of them get stored in table ``tx_contexts_contexts``.


Domain
======
A domain context matches when the domain the user visits is in the
configured list.

This is helpful if the site is available on several domains, or
when it is deployed on development/stage/live systems - you may choose
to show a content element on the development system only.

Domain matching
---------------
You may use one domain per line.

When the domain does not begin with a dot, it will only match fully:
``www.example.org`` will not match the configured domain ``example.org``.

It is possible to use a dot in front of the domain name.
In this case, all subdomains will match:
``some.www.example.org`` matches the configured domain ``.example.org``.


GET parameter
=============
Checks if a GET parameter is available and has a certain value.

Activate "Store result in user session" to keep the context when navigating
between pages.

When leaving the parameter values field empty, any non-empty parameter value
will activate the context.


IP address
==========
Matches the user's IP address. IPv4 and IPv6 are supported.

Supported notations:

- Full addresses: ``80.76.201.32``
- Prefix: ``80.76.201.32/27``, ``FE80::/16``
- Wildcards: ``80.76.201.*``, ``80.76.*.37``, ``80.76.*.*``


HTTP header
===========
Checks if a HTTP header is available and has a certain value.

Activate "Store result in user session" to keep the context when navigating
between pages.

When leaving the parameter values field empty, any non-empty parameter value
will activate the context.


Logical context combination
===========================
Combines other contexts with logical operators.

Contexts are referenced via their alias and can be combined with
the following signs:

- logical and: ``&&``
- logical or: ``||``
- negation: ``!``
- parentheses to group parts of expressions: ``(...)``


Session variable
================
This context checks if a session variable with the given name is
set (is not NULL).


=============================
Fluid template implementation
=============================
The implementation of a context query in fluid templates looks like::

    <div xmlns="http://www.w3.org/1999/xhtml" xmlns:contexts="http://typo3.org/ns/Tx_Contexts_ViewHelpers">
        <f:if condition="{contexts:matches(alias:'mobile')}">
            <f:then>is Mobile</f:then>
            <f:else>is not Mobile</f:else>
        </f:if>
    </div>

=========================
TypoScript implementation
=========================
The implementation of a context query in TypoScript looks like::

    [userFunc = user_contexts_matches("mobile")]
        # do something, it's a mobile browser
    [global]
