=== CFB Must Login ===
Contributors: codeforbroke
Tags: private site, login, members only, intranet, rest-api
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Put your whole site behind a login with one click. Includes REST API protection and automatic cache clearing.

== Description ==

Not every site is meant for the public. A staging site waiting on client sign-off. A company intranet. A members-only community. A family blog.

CFB Must Login puts your entire site behind the WordPress login. Switch it on from the admin bar, and anyone who isn't signed in is sent to the login page, then straight back to the page they wanted once they are.

= What you get =

* **One-click toggle.** Turn the login requirement on or off from the admin bar, on any page.
* **Status at a glance.** A lock icon in the admin bar always shows whether your site is private.
* **Smart redirects.** Visitors land back on the page they asked for after logging in.
* **REST API protection.** Logged-out requests to `/wp-json/` are refused, so your content can't be read through the API. Login, contact form and oEmbed endpoints keep working.
* **Automatic cache clearing.** Ten popular caching plugins are cleared the moment you toggle, so the change takes effect right away instead of whenever the cache expires.
* **A simple settings page** under Settings → CFB Must Login.
* **Lightweight**, translation ready, and extendable with filters and actions.

= Good to know =

* **It starts switched off.** Nothing changes for your visitors until you turn it on.
* **It's all or nothing, for now.** Version 1.0 protects the entire site. Per-page exclusions may come in a future version.
* **Search engines can't crawl your site while it's on.** That's usually the point, but remember to switch it off before launch.

= Built by Code For Broke =

CFB Must Login is made and maintained by [Code For Broke](https://codeforbroke.com/), an independent web developer working with marketing teams and small businesses on WordPress and fast, secure websites. Questions and suggestions come straight to me through the [support forum](https://wordpress.org/support/plugin/cfb-must-login/).

= Developer documentation =

**Filter: `cfb_must_login_redirect_url`**

Send logged-out visitors somewhere other than the default login page.

`
add_filter( 'cfb_must_login_redirect_url', function ( $redirect_url, $redirect_to ) {
    return 'https://example.com/custom-login';
}, 10, 2 );
`

**Filter: `cfb_must_login_allowed_rest_routes`**

Keep additional REST API endpoints open while protection is on.

`
add_filter( 'cfb_must_login_allowed_rest_routes', function ( $routes ) {
    $routes[] = '/my-plugin/v1/public';
    return $routes;
} );
`

**Action: `cfb_must_login_clear_cache`**

Runs whenever the plugin clears caches. Hook in to clear a cache it doesn't know about.

`
add_action( 'cfb_must_login_clear_cache', function () {
    // Your custom cache clearing.
} );
`

**Capability: `cfb_must_login_manage`**

Controls who can change the plugin's settings. It maps to `manage_options` by default; change it with the `map_meta_cap` filter.

== Installation ==

1. In your dashboard, go to **Plugins → Add New** and search for "CFB Must Login."
2. Click **Install Now**, then **Activate**.
3. Click the lock icon in the admin bar to require login. That's it.

To adjust REST API protection, go to **Settings → CFB Must Login**.

To install manually, download the zip, go to **Plugins → Add New → Upload Plugin**, choose the file and click **Install Now**, then **Activate**.

== Frequently Asked Questions ==

= What happens when a logged-out visitor arrives? =

They're sent to the login page. Once they log in, they're returned to the page they were trying to reach.

= What stays public while login is required? =

The login, registration and password reset pages, so people can actually sign in. A few other things stay reachable too:

* **oEmbed**, which can reveal a post's title to anyone who knows its URL.
* **Media Library files**, for anyone with a direct link, because your web server serves them without loading WordPress.

= Will this affect search engines? =

Yes. While login is required, search engines can't crawl your site. That's what you want for a private site, but switch it off before you launch publicly.

= What does REST API protection do? =

WordPress exposes posts, pages and other data at `/wp-json/`. With protection on, logged-out requests to those endpoints are refused. It's on by default and can be turned off separately under Settings → CFB Must Login.

= Which REST API endpoints stay open? =

Even with protection on, these keep working:

* Login and authentication (JWT Authentication, Simple JWT Login, current-user lookup and registration)
* Contact forms (Contact Form 7, WPForms, Gravity Forms)
* oEmbed

Developers can open more with the `cfb_must_login_allowed_rest_routes` filter.

= Does it work with caching plugins? =

Yes. When you toggle the login requirement, CFB Must Login clears the cache for WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache, WP Fastest Cache, Autoptimize, Cache Enabler, Comet Cache, SG Optimizer and WP-Optimize. If you use server-level or CDN caching, purge that too.

= Can I keep some pages public? =

Not yet. Version 1.0 requires login for the entire site.

= Does it work with membership plugins? =

Yes. It simply makes sure people are logged in. Your membership plugin keeps handling who can see what.

= Can I send visitors to a custom login page? =

Yes, with the `cfb_must_login_redirect_url` filter. See the developer documentation above.

== Changelog ==

= 1.0.1 =

* Fix: RSS feeds now require login while the site is private. Previously they showed full post content to logged-out visitors.
* Tested with WordPress 7.1.

= 1.0.0 =

* Initial release.
* One-click toggle in the admin bar.
* Configurable REST API protection with selective endpoint access.
* Automatic cache clearing for popular caching plugins, with admin notices.
* Translation ready.
