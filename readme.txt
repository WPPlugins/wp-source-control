=== WP Source Control ===
Contributors: MMDeveloper
Tags: source, control, source control, backup, templates, posts, pages, diff
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 3.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

WP Source Control is a WordPress plugin that allows you to source control your theme directory and your posts/pages. You can even see how your theme has changed since your last commit using the diff function.

Please note that this plugin only works on UNIX/Linux OS. Unfortunately it doesn't work with WAMP.

Built by The Marketing Mix Perth: http://www.marketingmix.com.au


== Installation ==

1) Install WordPress 4.0 or higher

2) Download the latest from:

http://wordpress.org/extend/plugins/wp-source-control

3) Login to WordPress admin, click on Plugins / Add New / Upload, then upload the zip file you just downloaded.

4) Activate the plugin.


Built by The Marketing Mix Perth: http://www.marketingmix.com.au


== Upgrade Notice ==

After upgrading make sure that you reactivate the plugin.

Do not hit the upgrade button until you have fully understood these instructions:

Make sure you backup the entire file directory of your site as well as the database. Version 2 is drastically different.

Inside the wp-source-control plugin directory, you will find directories starting with version_... etc

These directories need to be copied into this directory /root/wp-content/source_control

/root is the root directory of your website.

Once you've done this, it should be safe to upgrade.


== Changelog ==

= 3.1.1 =

* Another attempt to fix security vulnerability.

= 3.1.0 =

* Fixed a security vulnerability.

= 3.0.0 =

* Removed Tom M8te dependency.

= 2.4.0 =

* Updated the dependency checker and made it better to use.

= 2.3.3 =

* Sometimes the server is a little slow saving the snapshot of the template and that broke the code that decided which files have recently been changed. I've also changed it so on the diff page, the code scrolls to the first change on the file.

= 2.3.2 =

* Small bug fix with dependency check.

= 2.3 =

* Able to see log of each individual file and posts. Improved the way clean old commits works.

= 2.2 =

* Able to delete old commits. Fixed look of commited posts in search results.

= 2.1.1 =

* Fixed bug - with deleting posts. Because of the way wordpress is, I don't think I can check in a deleted file. Perhaps we don't need to worry about checking in deleted files.

= 2.1 =

* Fixed bug - stop code complaining when trying to create a directory that already exists.

= 2.0 =

* Doesn't remove template history code. However if your at version 1.* it will delete your history. Please read Upgrade Notice before upgrading.

* Allows you to backup your entire website and database.

* Can download zipped up version of your website and database.

= 1.1 =

* Split commit area and search commit area.

= 1.0 =

* Initial Checkin

== Upgrade notice ==

= 3.1.1 =

* Another attempt to fix security vulnerability.

= 3.1.0 =

* Fixed a security vulnerability.

= 3.0.0 =

* Removed Tom M8te dependency.

= 2.4.0 =

* Updated the dependency checker and made it better to use.

= 2.3.3 =

* Sometimes the server is a little slow saving the snapshot of the template and that broke the code that decided which files have recently been changed. I've also changed it so on the diff page, the code scrolls to the first change on the file.

= 2.3.2 =

* Small bug fix with dependency check.

= 2.3 =

* Able to see log of each individual file and posts. Improved the way clean old commits works.

= 2.2 =

* Able to delete old commits. Fixed look of commited posts in search results.

= 2.1.1 =

* Fixed bug - with deleting posts. Because of the way wordpress is, I don't think I can check in a deleted file. Perhaps we don't need to worry about checking in deleted files.

= 2.1 =

* Fixed bug - stop code complaining when trying to create a directory that already exists.

= 2.0 =

* Doesn't remove template history code. However if your at version 1.* it will delete your history. Please read Upgrade Notice before upgrading.

* Allows you to backup your entire website and database.

* Can download zipped up version of your website and database.

= 1.1 =

* Split commit area and search commit area.

= 1.0 =

* Initial Checkin