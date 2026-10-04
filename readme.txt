=== Zest CMP ===
Contributors: freshjuice4dev, reatlat
Tags: cookies, consent, gdpr, privacy, cookie-banner
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Lightweight GDPR/CCPA cookie consent toolkit with script blocking, cookie interception, and a Shadow DOM UI. Powered by @freshjuice/zest.

== Description ==

Zest CMP integrates the [Zest](https://cookiezest.com/) consent toolkit into WordPress. It provides a beautiful, accessible consent banner with Shadow DOM isolation, intelligent script blocking, cookie/storage interception, and support for 12 languages out of the box.

**Key features:**

* **Shadow DOM UI** — banner, settings modal, and floating widget are fully encapsulated. Your site's styles never leak in; Zest's styles never leak out.
* **Script blocking** — four modes (safe, manual, strict, doomsday) with automatic detection of known trackers. Zest installs interceptors before any tracking script can fire.
* **Cookie & storage interception** — intercepts `document.cookie`, `localStorage`, and `sessionStorage` writes, queuing them until consent is granted.
* **12 languages** — built-in translations for English, German, Spanish, French, Italian, Portuguese, Dutch, Polish, Ukrainian, Russian, Japanese, and Chinese. Auto-detects from the browser.
* **Do Not Track / GPC** — respects the browser privacy signal and auto-rejects non-essential cookies when DNT/GPC is enabled.
* **Geo / jurisdiction gating** — optional opt-in feature that shows a GDPR banner in the EU, a "Do Not Sell" link in the US, and nothing elsewhere. Uses the hosted zest-geo gateway or your own resolver.
* **Button layouts** — choose between row (default), split, or split-modern layouts.
* **Hard consent wall** — optional full-viewport overlay that blocks page interaction until the visitor decides.
* **Backdrop blur** — optional frosted-glass effect on the modal and wall overlay.
* **Hidden categories** — remove unused consent categories from the settings modal. Hidden categories are forced to rejected.
* **Zero dependencies** — vanilla JavaScript, no jQuery, no React, nothing.

== External services ==

By default this plugin is fully self-contained and makes no network requests of its own.

If you enable **Geo / jurisdiction gating** in the settings, the visitor's browser requests `geo.cookiezest.com` once to look up which privacy regulations apply in the visitor's country, so the right consent banner is shown (full banner in the EU/EEA/UK, notice in regulated US states, nothing elsewhere). The lookup sends the visitor's IP address to the gateway at the time of the request, and the gateway returns only regulation flags — it is not a tracking service and the IP is not logged or stored by it. See the gateway's [privacy notes](https://cookiezest.com/privacy/) and [terms](https://cookiezest.com/terms/).

This service is provided by CookieZest (FreshJuice). It is optional and off by default; the plugin works fully without it.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/zest-cmp/`, or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Settings > Cookie Consent** to configure the banner.

== Frequently Asked Questions ==

= Does this work with caching plugins? =

Yes. Zest runs entirely client-side in the browser. The consent banner and script blocking happen after the cached HTML is served, so caching plugins like WP Super Cache, W3 Total Cache, or LiteSpeed Cache work without any special configuration.

= Does this work with Google Analytics / GTM? =

Yes. Zest automatically blocks Google Analytics, Google Tag Manager, Facebook Pixel, and other known trackers until the visitor grants consent. Once consent is given, blocked scripts are replayed automatically.

= Do I need a subscription? =

No. Zest is 100% free and open source. This plugin is GPL-3.0-or-later; the bundled Zest JS library is MIT-licensed. No subscription, no SaaS dependency, no external API calls (unless you opt-in to geo gating).

== Screenshots ==

1. The consent banner with default settings
2. The plugin settings page in WordPress admin

== Changelog ==

= 1.1.0 =
* Removed the blog and changelog dashboard widgets and their feed requests to cookiezest.com — the plugin now makes no network requests from wp-admin (Guidelines 7 & 9).
* Documented the optional geo gating service in the readme (Guideline 6).
* Admin styles and scripts are now enqueued via wp_enqueue_style/wp_enqueue_script instead of inline tags (Guideline 13).
* Config output uses wp_add_inline_script instead of a raw script tag in wp_head.
* Overview widget styles use wp_add_inline_style.

= 1.0.0 =
* Initial scaffolding. Settings page, enqueue, i18n, and build pipeline.
* Bundles Zest v2.7.0.
* Requires PHP 7.4+.

== Upgrade Notice ==

= 1.1.0 =
No network requests from wp-admin anymore. Optional geo gating is now documented; the plugin works fully without it.

= 1.0.0 =
Initial release. Please test on a staging site first.
