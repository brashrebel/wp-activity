=== WP-Activity ===
Contributors: Dric
Donate link: http://www.driczone.net/blog
Tags: stream, activity, community, multi-users, log, events
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: 0.3a

Display users events in frontend

== Description ==

This plugin logs registered users events in your blog and displays it in frontend.

- connections
- new comments
- profile update
- new post
- post edition
- new link

Users can see what other members do in the blog. Great for multi-users blogs or community blogs.

French translation included.

[Plugin page](http://www.driczone.net/blog/wp-activity/)

== Installation ==

1. Download the plugin and unzip,
2. Upload the wp-activity folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,
4. Go to `Settings > Wp-Activity` and set options that fit your needs.
5. Put `<?php act_stream() ?>` where you want the stream to appear, or use included widget.

== Frequently Asked Questions ==

= How do I set the events number or the title when not using the widget ? =

this function accepts two parameters :
`<?php act_stream(number,title) ?>`

defaults are :

- number = 30
- title = Recent Activity (translated by .mo)

== Screenshots ==

1. frontend display
2. admin screen

== ChangeLog ==

= 0.4 =
* Post creation/edition separated
* Add link event added (only public links)

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
