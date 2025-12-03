=== Must Login ===
Contributors: codeforbroke
Tags: login, private, access, security, members
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Require users to log in before viewing your site with easy admin toggle controls.

== Description ==

Must Login is a lightweight, user-friendly plugin that allows you to require login for your entire site with just one click. Perfect for membership sites, private blogs, intranets, or any site that needs to restrict access to registered users only.

= Features =

* **One-Click Toggle** - Enable or disable login requirement instantly from the admin bar
* **Admin Bar Status Indicator** - Always see at a glance whether login is required
* **Simple Settings Page** - Easy-to-use settings interface
* **Admin Override** - Administrators always have access, even when login is required
* **Smart Redirects** - Users are redirected to login and then back to their intended page
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

= Can I exclude certain pages? =

Version 1.0.0 requires login for the entire site. Future versions may include page exclusion options.

= What happens to users trying to access the site? =

Non-logged-in users are automatically redirected to the login page. After logging in, they're redirected back to the page they were trying to access.

= Does this work with membership plugins? =

Yes! Must Login works alongside membership plugins and simply ensures users are logged in before viewing any content.

= Can I customize the redirect URL? =

Yes, developers can use the `must_login_redirect_url` filter to customize the redirect URL.