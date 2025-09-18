=== Fiber Admin ===
Contributors: daomapsieucap
Tags: white label, duplicate post, content protection
Requires at least: 4.7
Tested up to: 6.8.1
Requires PHP: 7.4
Stable tag: 3.2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bring multiple customization features to make your own WordPress admin.

== Description ==

* Use your Site Settings to white label WordPress automatically.
* Customize admin login page by multiple options.
* Set Image Metadata automatically after uploading.
* Convert plain email text into link automatically.
* Enable SVG support.
* Drag and drop post types / taxonomies order.
* Duplicate post types with single item or bulk action.
* Protect your site image by disable right click / drag image into html page *(only for non-admin users)*.
* Protect your site content by disable these following keys: Ctrl / Cmd + S, Ctrl / Cmd + A, Ctrl / Cmd + C, Ctrl / Cmd + X, Ctrl / Cmd + Shift + I *(only for non-admin users)*.
* Disable WordPress comments & WordPress Automatic Updates completely as default.
* Customize 503 Database Error page.

[Fork the plugin on GitHub.](https://github.com/daomapsieucap/fiber-admin)

== Frequently Asked Questions ==

= Why can't I save the settings for Customized WordPress Database Error page? =

At the first time using this setting, Fiber Admin will ask you to save it to create the db-error.php file. When your site has some security plugins like Sucuri or Wordfence Security..., these plugins will have an option to prevent file editor. All you need is whitelist wp-content/db-error.php from plugin setting.

== Installation ==

1. Install either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Go to CMS admin plugins list and activate the plugin.
3. Go to Fiber Admin setting page and update the options.

== Screenshots ==

1. White Label WordPress settings.
2. Enable drag and drop post types / taxonomies order.
3. Enable duplicate post types with single item or bulk action.
4. 503 Database Error page settings.
5. Miscellaneous settings.

== Changelog ==

= 3.2.7 =
*Release Date - 15 September 2025*

* Changed: Improve Admin Login UI