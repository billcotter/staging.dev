=== WordPress Database Reset ===

Contributors: mousesports
Tags: wordpress, database, database-reset, development, default-settings, default, wp-reset, security, secure
License: GPL2
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 2.1

A secure and easy way to reinitialize the WordPress database to its default settings.

== Description ==

WordPress Database Reset is a secure and easy way to reinitialize your WordPress database back to its default settings without actually having to reinstall WordPress yourself.

This plugin will come in handy for both theme and plugin developers. There are different use case scenarios - one of which is being able to easily erase excess junk in the wp_options table that accumulates over time. Another would be to simply obtain a fresh install of the WordPress database after experimenting with various back-end options.

== Installation ==

Copy the wp-reset folder and its contents to your wp-content/plugins directory,
then activate the plugin. You could also use the built-in Add New Plugin
feature within WordPress. After activating, you will automatically be redirected
to the plugin page.

== Frequently Asked Questions ==

= Why reset the database? =

I wanted a simple and painless way to obtain a fresh clean database without actually having to reinstall WordPress. Plus, 9 times out of 10 I get tons of excess junk in the wp_options table after installing plugins and themes that do not clean up after themselves.

== Screenshots ==
1. The plugin page - a more secure way of resetting your database.

== Changelog ==
= 2.1 =
* Replaced 3.3 deprecated get_userdatabylogin() with get_user_by()
* Updated deprecated add_contextual_help() with add_help_tab()
* Small change in condition check for backup tables
* Removed custom _rand_string() with core wp_generate_password()
* Added Portuguese translation - thanks to Fernando Lopes

= 2.0 =
* Added functionality to be able to select which tables you want to reset, rather than having to reset the entire database.
* Added bsmSelect for the multiple select.
* Modified screenshot-1.png.
* Fixed redirect bug
* 'Reactivate current plugins after reset' only shows if the options table is selected from the dropdown.

= 1.4 =
* Made quite a few changes to the translation files
* Renamed french translation file for plugin format, not theme format
* Optimized (until potential version 2.0)

= 1.3 =
* Replaced reactivation option for all currently active plugins (not just this plugin)
* Updated language files

= 1.2 =
* Added capability to manually select whether or not plugin should be reactivated upon reset
* Modified class name to avoid potential conflicts with WordPress core
* Modified wp_mail override
* Removed deprecated user level for WordPress 3.0+
* Fixed small bug where if admin user did not have admin capabilities, it would tell the user they did

= 1.0 =
* First version