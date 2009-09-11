=== WP-Activity ===
Contributors: Dric
Donate link: http://www.driczone.net/blog
Tags: stream, activity, community, multi-users, log, events
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: 0.2

Display users events in frontend

== Description ==

This plugin logs registered users events in your blog and display it in frontend.

- connections
- new comments
- profile update
- new/edit post

Users can see what other members do in the blog. Great for multi-users blogs or community blogs.

[Plugin page](http://www.driczone.net/blog/wp-activity/)

== Installation ==

1. Download the plugin and unzip,
2. Upload the wp-activity folder to your wp-content/plugins folder,
3. Activate the plugin through the Wordpress admin,
4. Go to Settings > Wp-Activity and set options that fit your needs.
5. Put `<?php act_stream() ?>` where you want the stream to appear.

== Frequently Asked Questions ==

= How do I set the events number or the title when using act_stream()? =

this function accepts two parameters :
act_stream(number,title)

defaults are :

- number = 30
- title = Recent Activity (translated by .mo)

== Screenshots ==

1. frontend display

== ChangeLog ==

= 0.2 =
* Plugin internationalization
* widget enabled

= 0.1 =
* First release
