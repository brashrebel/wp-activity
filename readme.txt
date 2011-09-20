=== WP-Activity ===
Contributors: Dric1107
Donate link: http://www.driczone.net/blog
Tags: stream, activity, community, multi-users, log, event, monitor
Requires at least: 2.8
Tested up to: 3.2.1
Stable tag: 1.4

Monitor and display users activity (logins, logon failures, new posts, new comments, etc.) in backend and frontend of WordPress.

== Description ==

This plugin logs registered users events in your blog and displays it in frontend and backend.

- connections
- new comments
- profile update
- new post
- post edition
- new link
- login failures (displayed only in admin panel)

Admin can use this plugin to monitor a multi-users blog activity without displaying it in frontend.
Users can see what other members do in the blog. Great for multi-users blogs or community blogs.

If enabled, user who don't want to be listed in blog activity can hide its own activity by checking a privacy option in the profile page. In that case, this user activity is not stored in database.

Users activity can be followed by RSS feed.


Translations :

- French
- Italian (Partial translation - Thx to Luca)
- Turkish (Thx to Can KAYA - translated up to v1.2)
- Spanish (Thx to Cscean - translated up to v1.3)

(If you translated my plugin, please send the translated .po file at cedric@driczone.net )

[Plugin page](http://www.driczone.net/blog/plugins/wp-activity/) (French blog but feel free to comment in english)

== Installation ==

1. Download the plugin and unzip,
2. Upload the wp-activity folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,
4. Go to `Settings > Wp-Activity` and set options.
5. Put `<?php act_stream() ?>` where you want the stream to be displayed, or use included widget.
6. Use `[ACT_STREAM]` to display activity in a page or post. See FAQ section for parameters.
7. If you renamed your wp-content directory and you want to use RSS feed, change `$wpcontentdir` var in wp-activity-feed.php

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

= I would like to display more or less than 50 lines per page in admin panel of wp_activity =

You have to modify the `$act_list_limit` var line 31 of wp-activity.php.

= I don't need the last login column in user list or I don't need the last login failures in admin panel =

You have to modify the `$no_admin_mess` var line 33 of wp-activity.php and set it to **true**.

= The RSS feed is not working =

If you renamed your wp-content directory, you have to change `$wpcontentdir` var in ../plugins/wp-activity/wp-activity-feed.php

= I edited a post but wp-activity logged POST_ADD instead of POST_EDIT =
That's because the post_add event for this post id was removed from the wp-activity database table. Wordpress doesn't have separate actions for adding or editing posts, so the plugin checks in it's table if there was a post creation with the same ID. If found, the plugin logs a post edition. But as the plugin clears old logs (see "Rows limit in database" setting), the post creation event can be previously deleted when the post edition occurs.

= I have a poor hosting, is your plugin a big fat resources consumer ? =
I also have a poor hosting, so I try to keep my plugin as light as I can. But for more performance, do not display the users gravatars in activity list.

= Do you really test your plugin before publishing new versions at the Wordpress Plugin Repository ? =

Hum. I'm testing it on a single Wordpress installation, so it can't really be called "test". That's why there is often updates that just fix the previous ones... Sorry for that.


== Screenshots ==

1. frontend display
2. admin screen - activity display
3. admin screen - one of the settings tabs
4. admin screen - reset/uninstall tab

== ChangeLog ==

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
