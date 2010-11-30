=== WP-Activity ===
Contributors: Dric
Donate link: http://www.driczone.net/blog
Tags: stream, activity, community, multi-users, log, events, monitor
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 0.8.1.1

Display and monitor users activity in backend and frontend of WordPress. For WP single (not tested with WPMU).

== Description ==

This plugin logs registered users events in your blog and displays it in frontend and backend.
Admin can use this plugin to monitor a multi-users blog activity without displaying it in frontend.

- connections
- new comments
- profile update
- new post
- post edition
- new link

Users can see what other members do in the blog. Great for multi-users blogs or community blogs.

User who don't want to appear can hide its activity from profile. In that case, this user activity is not stored in database.

Users activity can be followed by RSS feed.

Translations :

- French
- Italian (Partial translation - Thx to Luca)

[Plugin page](http://www.driczone.net/blog/plugins/wp-activity/)

== Installation ==

1. Download the plugin and unzip,
2. Upload the wp-activity folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,
4. Go to `Settings > Wp-Activity` and set options that fit your needs.
5. Put `<?php act_stream() ?>` where you want the stream to appear, or use included widget.
6. Use `[ACT_STREAM]` to display activity in a page or post.

== Frequently Asked Questions ==

= How do I enable the user last logon on author or index page ? =

Just put `<?php act_last_connect($author) ?>` in author page, or `<?php act_last_connect() ?>` in index page.

= How do I set the events number or the title when not using the widget ? =

this function accepts two parameters :
`<?php act_stream(number,title) ?>`

defaults are :

- number = 30
- title = Recent Activity (translated by .mo)

= How do I Change the events generic icons ? =

Just change the icons in the /img directory, but keep the event name (example : to change the login/connect event icon, change the icon named CONNECT.png - names must be in capitals)

= Do you really test your plugin before updating it at the Wordpress Plugin Repository ? =
Hum. I'm testing it on a single Wordpress installation, so it can't really be called "test". That's why there is often updates that just fix the previous ones... Sorry for that.

== Screenshots ==

1. frontend display
2. admin screen - display activity
3. admin screen - manage settings

== ChangeLog ==

= 0.8.2 =
* Use of a cookie instead of a session var.
* Fixed a CSRF vulnerability (Thx to Julio - www.boiteaweb.fr)

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
