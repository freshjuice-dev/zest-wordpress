=== Zest CMP ===
Contributors: freshjuice, reatlat
Tags: cookies, consent, gdpr, ccpa, privacy, cookie-consent, cookie-banner, cmp
Requires at least: 5.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
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
2. The settings modal with category toggles
3. The plugin settings page in WordPress admin

== Changelog ==

= 1.0.0 =
* Initial scaffolding. Settings page, enqueue, i18n, and build pipeline.
* Bundles Zest v2.7.0.
* Requires PHP 7.4+.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Please test on a staging site first.