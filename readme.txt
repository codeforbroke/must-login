=== Must Login ===
Contributors: codeforbroke
Tags: login, private, access, security, members, rest-api
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Require users to log in before viewing your site with easy admin toggle controls. Includes REST API protection and automatic cache clearing.

== Description ==

Must Login is a lightweight, user-friendly plugin that allows you to require login for your entire site with just one click. Perfect for membership sites, private blogs, intranets, or any site that needs to restrict access to registered users only.

= Features =

* **One-Click Toggle** - Enable or disable login requirement instantly from the admin bar
* **REST API Protection** - Configurable authentication requirement for REST API endpoints
* **Automatic Cache Clearing** - Automatically clears popular caching plugins when toggling protection
* **Admin Bar Status Indicator** - Always see at a glance whether login is required
* **Simple Settings Page** - Easy-to-use settings interface
* **Admin Override** - Administrators always have access, even when login is required
* **Smart Redirects** - Users are redirected to login and then back to their intended page
* **Selective Endpoint Access** - Allow specific REST API endpoints for forms and authentication
* **Cache Compatibility** - Works with WP Super Cache, W3 Total Cache, WP Rocket, and more
* **No Configuration Needed** - Works perfectly out of the box
* **Lightweight** - Minimal impact on site performance
* **Translation Ready** - Fully internationalized and ready for translation

= How It Works =

1. Install and activate the plugin
2. Click the lock icon in the admin bar to toggle login requirement
3. That's it! Your site is now protected

When enabled, all visitors must log in to view any page on your site. Administrators can quickly toggle this on or off from anywhere on the site using the admin bar.

== Installation ==

= Automatic Installation =

1. Log in to your dashboard
2. Navigate to Plugins → Add New
3. Search for "Must Login"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to your dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the zip file and click "Install Now"
5. Activate the plugin

== Frequently Asked Questions ==

= Will this affect search engines? =

Yes, when login is required, search engines cannot crawl your site. This is useful for private sites but should be disabled if you want search engine visibility.

= What is REST API protection? =

REST API protection requires authentication to access WordPress REST API endpoints when login is required. This prevents unauthenticated access to your site's data through the API. You can enable or disable this feature separately in the settings.

= Which REST API endpoints are always accessible? =

Even with REST API protection enabled, the following endpoints remain accessible:
* Authentication endpoints (JWT, Simple JWT Login)
* Contact form endpoints (Contact Form 7, WPForms, Gravity Forms)
* oEmbed endpoints

Developers can allow additional endpoints using the `must_login_allowed_rest_routes` filter.

= Does this work with caching plugins? =

Yes! The plugin automatically detects and clears cache from popular caching plugins including:
* WP Super Cache
* W3 Total Cache
* WP Rocket
* LiteSpeed Cache
* WP Fastest Cache
* Autoptimize
* Cache Enabler
* Comet Cache
* SG Optimizer
* WP Optimize

When you toggle the login requirement, the cache is automatically cleared to ensure the changes take effect immediately.

= Can I exclude certain pages? =

Version 1.0.0 requires login for the entire site. Future versions may include page exclusion options.

= What happens to users trying to access the site? =

Non-logged-in users are automatically redirected to the login page. After logging in, they're redirected back to the page they were trying to access.

= Does this work with membership plugins? =

Yes! Must Login works alongside membership plugins and simply ensures users are logged in before viewing any content.

= Can I customize the redirect URL? =

Yes, developers can use the `must_login_redirect_url` filter to customize the redirect URL.

= How do I allow additional REST API endpoints? =

Developers can use the `must_login_allowed_rest_routes` filter to add custom endpoints to the allowlist:

`
add_filter('must_login_allowed_rest_routes', function($routes) {
    $routes[] = '/my-plugin/v1/public-endpoint';
    return $routes;
});
`

== Screenshots ==

1. Admin bar toggle with status indicator
2. Simple settings page with REST API protection option
3. Cache notice when caching plugins are detected

== Changelog ==

= 1.0.0 =
* Initial release
* One-click toggle via admin bar
* Configurable REST API protection
* Automatic cache clearing for popular caching plugins
* Support for WP Super Cache, W3 Total Cache, WP Rocket, and more
* Selective REST API endpoint access
* Admin notices for caching plugins
* Translation ready

== Upgrade Notice ==

= 1.0.0 =
Initial release with full login protection, REST API security, and automatic cache clearing.

== Developer Documentation ==

= Filters =

**must_login_redirect_url** - Customize the login redirect URL
`
add_filter('must_login_redirect_url', function($redirect_url, $redirect_to) {
    return 'https://example.com/custom-login';
}, 10, 2);
`

**must_login_allowed_rest_routes** - Allow additional REST API endpoints
`
add_filter('must_login_allowed_rest_routes', function($routes) {
    $routes[] = '/my-plugin/v1/public';
    return $routes;
});
`

= Actions =

**must_login_clear_cache** - Triggered when cache is cleared
`
add_action('must_login_clear_cache', function() {
    // Custom cache clearing logic
});
`

= Capabilities =

The plugin uses the `must_login_manage` capability, which is mapped to `manage_options` by default. You can customize this using the `map_meta_cap` filter.