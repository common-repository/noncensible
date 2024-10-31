=== Noncensible ===
Contributors: lev0
Tags: nonce, optimize, cache, performance, stability, security
Requires at least: 2.5.0
Tested up to: 6.5.2
Stable tag: 1.1.0
Requires PHP: 5.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides sensible replacements of default nonce generation functions to more accurately reflect their expected behaviour.

== Description ==

The core functions' documentation states that nonces, small validity tokens used throughout WordPress core and other plugins, have a lifespan of 1 day, however they can last as little as half that time. For common usage that may not be a problem, but if that lifespan is relied upon in any meaningful way, inexplicable failures can occur. For example, filters that shorten the lifespan may randomly make it difficult to complete some tasks before their nonce expires. Very long lifespans, such as those set by many caching and SEO optimization plugins, can result in forms and other actions suddenly breaking before the cached content expires and new nonces get generated. Imagine a contact form that's cached for a week but stops working after 4 days, then starts working again if that cache is cleared. A cursory search for terms like `caching nonce expired wordpress` yields many results for relevant problems.

This plugin guarantees a nonce will last _at least_ as long as it's intended to, but up to ⅛ of a lifepsan more. By default, this means a nonce will last from 24 up to 27 hours, rather than anywhere from 12 to 24 hours. It was created because it was inappropriate to change the behaviour of such old code (in [ticket #53236](https://core.trac.wordpress.org/ticket/53236)) because the functions are pluggable.

Hourglass icon by [mavadee](https://www.flaticon.com/authors/mavadee).

== Installation ==

1. Install the plugin in the usual way, through the admin interface by uploading manually or searching on the **Plugins** page.
2. Clear *all* caching plugins' content.
3. Test that forms, etc. on your site are still working. You may need to force-reload affected pages, or clear your browser's cache.

== Changelog ==

= 1.1.0 =
Compatibility with WP v6.1: passing `wp_verify_nonce()`'s `$action` parameter to `wp_nonce_tick()`.
