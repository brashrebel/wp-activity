=== WP-Activity ===
Contributors: Dric1107
Donate link: http://www.driczone.net/blog
Tags: stream, activity, community, multi-users, log, event, monitor, stats, blacklist, tracking, access, security, login
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.7

Monitor and display registered users activity (logins, logon failures, new posts, new comments, etc.). You can also prevent unwanted login attemps.

== Description ==

This plugin logs registered users activity in your blog and displays it in frontend and backend. It can also track and deny access by blacklisting to unwanted login attempts.

- logons
- new comments
- profile update
- new post
- post edition
- new link
- login failures (displayed only in admin panel)
- accesses denied by IP blacklisting (displayed only in admin panel)

Possible usages :

- Monitor unwanted connexions attempts on your blog and block hackers IP.
- Monitor the registered users activity on a multi-users blog.
- Enhance your community blog by displaying to all users what other members have done.

If enabled, user who don't want to be listed in blog activity can hide its own activity by checking a privacy option in the profile page. In that case, this user activity is not stored in database.
When a login failure occurs, the IP address is also logged.
Users activity can be followed by RSS feed and can be exported in csv file (semicolon separation).

Admin can follow the blog users activity within dates range with the stats module.

To avoid spammers or hackers trying to steal accounts, you can blacklist their IP addresses. Be careful, I you blacklist your own IP you won't be able to login anymore !
Blacklisted IP addresses get a 403 error when trying to logon, and the activity log displays an 'access denied' event.
Keep in mind that this plugin is not security oriented. There are lots of plugins that specifically deal with [security](http://wordpress.org/extend/plugins/search.php?q=security).

I would like to thank Tom V. for finding a lot of bugs each time I release a new version, and for helping me fix them.

Translations :

- French
- Italian (Partial translation - Thx to Luca)
- Turkish (Thx to Can KAYA - translated up to v1.2)
- Spanish (Thx to Cscean - translated up to v1.3)
- Dutch (Thx to Tom - translated up to 1.6.1)

(If you translated my plugin, please send the translated .po file at cedric@driczone.net )

[Plugin page](http://www.driczone.net/blog/plugins/wp-activity/) (French blog but feel free to comment in english)

I my plugin doesn't fit your needs, you can also try [ThreeWP Activity Monitor](http://wordpress.org/extend/plugins/threewp-activity-monitor/) by [Edward Mindeantre](http://mindreantre.se).

== Installation ==

1. Download the plugin and unzip,
2. Upload the wp-activity folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,
4. Go to `Wp-Activity > Settings` for plugin options.
5. Put `<?php act_stream() ?>` where you want the stream to be displayed, or use included widget.
6. Use `[ACT_STREAM]` to display activity in a page or post. See FAQ section for parameters.
7. If you renamed your wp-content directory and you want to use RSS feed, change `$wpcontentdir` var in wp-activity-feed.php and wp-activity-export.php

== Frequently Asked Questions ==

= How do I enable the user last logon on author or index page ? =

Use `<?php act_last_connect($author) ?>` in author.php template, or `<?php act_last_connect() ?>` in index page.

= How do I add user activity on it's author page ? =

Use `<?php act_stream_user($author) ?>` in author.php template

= How do I set the events number or the title when not using the widget ? =

this function accepts two parameters :
`<?php act_stream(number,title) ?>`

defaults are :

* number = 30
* title = Recent Activity (translated by .mo)

= Shortcode use =

`[ACT_STREAM]`

`[ACT_STREAM number="" title=""]`

defaults are :

* number = no limit
* title = Recent Activity (translated by .mo)

= I blacklisted my own IP address, or I can't login anymore since I activated the blacklisting ! =

Just rename or delete the wp-activity directory in wp-content/plugins/, and you should be able to access to your blog.

= How do I avoid erasing css tweaks when I update the plugin ? =

Just put a copy of wp-activity.css in your theme dir, it will be processed instead of the css file included with the plugin.

= How do I display all activity ? =

You must specify "-1" in number parameter. All activity stored in database will be returned.

= How do I change author page links ? =

Change the value in the plugin administration, under display options tab.

= How do I Change the events generic icons ? =

Just change the icons in the /img directory, but keep the event name (example : to change the login/connect event icon, change the icon named CONNECT.png - names must be in capitals)

= I added a post and changed the author, and the activity logs have changed too. How could I disable this ? =

You will have to edit wp-activity.php, check line 32 and set `$strict_logs` to **true**.

= I exported data to a csv file but there are ugly characters in MS Excel ! =

This is a known excel bug : when you open a .csv file in Excel, it uses the local encoding set (WINDOWS-1252 for French) and not UTF-8. To avoid this, you will have to rename the file extension from .csv to .txt, open Excel, do File/Open and open the wp-activity.txt. The csv import assistant will now launch, allowing you to set the encoding to UTF-8. 

= I would like to display more or less than 50 lines per page in admin panel of wp_activity =

You have to modify the `$act_list_limit` var line 31 of wp-activity.php.

= I don't need the last login column in user list or I don't need the last login failures in admin panel =

You have to modify the `$no_admin_mess` var line 33 of wp-activity.php and set it to **true**.

= RSS feed is not working =

If you renamed your wp-content directory, you have to change `$wpcontentdir` var in ../plugins/wp-activity/wp-activity-feed.php.

= I edited a post but wp-activity logged POST_ADD instead of POST_EDIT =
That's because the post_add event for this post id was removed from the wp-activity database table. Wordpress doesn't have separate actions for adding or editing posts, so the plugin checks in it's table if there was a post creation with the same ID. If found, the plugin logs a post edition. But as the plugin clears old logs (see "Rows limit in database" setting), the post creation event can be previously deleted when the post edition occurs.

= I have a poor hosting, is your plugin a big fat resources consumer ? =
I also have a poor hosting, so I try to keep my plugin as light as I can ; the admin scripts and css files are only loaded when needed.
Performance tips :

* Use of Gravatars generate more sql queries and is slower to display.
* If you don't use frontend login form, check the 'blacklist on wp-login.php only' option. If you want to blacklist an IP address on all your blog, use htaccess filtering instead.
* Unckeck the events you don't want to monitor.

= Do you really test your plugin before publishing new versions at the Wordpress Plugin Repository ? =

Hum. I'm testing it on two Wordpress installations (local WAMP and online test site), so it can't be called extended tests. That's why there is often updates that just fix the previous ones... Sorry for that.


== Screenshots ==

1. frontend display
2. admin screen - activity display
3. admin screen - one of the settings tabs
4. admin screen - stats

== ChangeLog ==

= 1.7.1 =
* Fixed bug with the logon log function.
* Fixed php bug where numbers were possibly displayed as scientific notation with a comma, totally messing up js code and preventing stats chart to display.

= 1.7 =
* Added blacklisting of IP addresses.
* Added dutch translation (Thx to Tom Vennekens).
* Admin and export functions are only loaded when needed (separate php files).
* Tweaked Cron task activation.
* Changed the display of settings page to look more like 'standard' admin WP.
* Replaced a few translation strings, sorry for translaters.
* Fixed Logon events who where only added when entering credentials since v1.6. Authentification with cookie ('remember me' option) will now generate a login event.
* Fixed deletion of old activity (cron task).
* Fixed csv file generation bug for IE.
* Fixed missing datepicker js script when using wordpress prior to 3.3.

= 1.6.1 =
* Fixed pages navigation links

= 1.6 =
* Added Logging of IP Address when a logon failure occurs.
* Added Activity stats.
* Added a few css rules to wp-activity.css (custom css files must be updated).
* Changed plugin menus (Wp-Activity has now it's own menu).
* Changed CONNECT events tracking, should be less disturbed by plugins that deals with Wordpress login.
* Fixed csv file generation (bug with url rewriting).
* Fixed (again) empty Last login column in user list when using User Access Manager plugin.
* Fixed login failures bad link in right now widget.

= 1.5 =
* Added current rows count in db next to the max rows value setting.
* Added export to csv file - filters and ordering are also processed to exported data.
* Added filtering by user in admin activity list.
* Tweaks and optimizations.
* Fixed missing profile field to allow user privacy.
* Fixed double login events when using a plugin dealing with WP login.
* Fixed "last connect" empty data values when using a plugin that deals with WP admin panel users list.
* Fixed a bug in multisites environment where queries to the users table where wrong (bad prefix).
* Fixed a bug where spam comments were written in activity table (but not displayed). 

= 1.4 =
* Added a 'Last Login' column in WP-Admin user list page.
* Added an option to change the author page links when your permalink structure for authors is not 'author'.
* Added a widget to display to a logged user its own activity.
* Fixed a css conflit when using jquery.tabs in another plugin (Thx to Cscean - http://cscean.es/)
* Added Spanish Translation (Thx to Cscean - http://cscean.es/)

= 1.3.2 =
* If two login events occur within a minute, only the first of them is displayed in frontend (double login events reported with facebook login).
* Corrected another bug with dates (Thx to Royzzz - http://www.roypoots.nl).

= 1.3.1 =
* Security check removed as it causes fatal error.

= 1.3 =
* Added logon fails count since last administrator login on "Right Now" admin panel widget.
* More privileges security added.
* Corrected a bug with relatives dates.

= 1.2.1 =
* Fixed bad posts links in admin and RSS logs (Thx again to Mario_7).

= 1.2 =
* Fixed stupids "\n" displayed in plugin admin.
* Added links in wordpress plugin lists to configure or uninstall WP-Activity.
* Fixed a misplaced div closing tag (Thx to Mario_7).
* Added Turkish translation (Thx to Can KAYA - http://www.kartaca.com)

= 1.1 =
* Fixed RSS feed (it has probably never worked outside of my wordpress test site).
* Admin can now prevent users to deny logging of their activity.
* Activity list in admin panel has now the same ergonomy as the standard wp admin lists (with pagination, filtering and ordering).
* Login failures can now be logged.

= 1.0 =
* Reset/uninstall tab
* User activity can now be displayed on author page
* If the author of a post has been changed, the plugin will change it in activity logs too. See FAQ for more details.

= 0.9.1 =
* Fixed a XSS vulnerability (Thx again to Julio - http://www.boiteaweb.fr)
* Admin panel improved
* Activity archive link in frontend

= 0.9 =
* improved shortcode - now with parameters.
* possible use of an alternate css file in theme directory - avoid erasing css tweaks with plugin updates.

= 0.8.2 =
* Use of a cookie instead of a session var.
* Fixed a CSRF vulnerability (Thx to Julio - http://www.boiteaweb.fr)

= 0.8.1.1 =
* Bug fix that prevented activity to be displayed in frontend.

= 0.8.1 =
* Added shortcode [ACT_STREAM] to display activity on a page or post.

= 0.8 =
* New activity can be highlighted since last user login (in fact old activity is greyed out)
* Bug fix with a possibly shared var name (thx to Stephane)

= 0.7.2 =
* Bug fix with cron settings for deleting old activity

= 0.7.1 =
* Bug fix when auto-delete old activity (activity limit)

= 0.7 =
* User last logon can now be displayed on author page

= 0.6 =
* admin panel tweaked
* Plugin now support gravatars for connect and profile edit events. Generic icons can also be used.
* Activity stream display tweaked.

= 0.5 =
* Added setting for using relatives dates
* Activity is now displayed in the admin plugin page (backend)
* Post Add/Edit events are now correctly logged

= 0.4a =
* Comments and posts adds are now correctly logged

= 0.4 =
* Post creation/edition separated
* Add link event added (only public links)
* Users can hide their activity from profile
* RSS feed added

= 0.3a =
* Big bug (introduced in 0.3) squeezed

= 0.3 =
* Less SQL queries
* Admin can choose events types to log

= 0.2 =
* Plugin internationalization
* widget enabled

= 0.1 =
* First release
