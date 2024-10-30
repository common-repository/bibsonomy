=== BibSonomy ===
Contributors: chschenk
Donate link: http://www.christianschenk.org/donation/
Tags: bibsonomy, web2.0, folksonomy, bookmarks, tag cloud, tags
Requires at least: 2.5
Tested up to: 3.4
Stable tag: 1.10

This plugin integrates BibSonomy into your WordPress blog.

== Description ==

This plugin integrates [BibSonomy](http://www.bibsonomy.org/) into your blog:

* post your blog entries to BibSonomy with a few clicks
* show your tags from BibSonomy as a cloud or chart
* include a list of posts inside your content (powerful)

== Installation ==

1. Unzip the plugin into your wp-content/plugins directory.
2. Activate the plugin at the plugin administration page.
3. Go to Settings - BibSonomy and fill out the form.
4. Follow the instructions [here](http://www.christianschenk.org/projects/wordpress-bibsonomy-plugin/#howto).

== Frequently Asked Questions ==

= The macros are eating my content?! =

This happens if you're using one of the [macros](http://www.christianschenk.org/projects/wordpress-bibsonomy-plugin/#macros)
more than once inside a post/page. Say, your page looks like this:

> [bibsonomy-tags]

> ... some content ...

> [bibsonomy-tags]

> [bibsonomy-chart]

> [\bibsonomy-tags]

The output of the first macro will show up but the content until the
closing part of the same macro will disappear.

Solution: just add the closing part of a macro to every macro on this
post/page.

== Screenshots ==

1. Display your tags in a cloud.
2. Statistics chart showing the overall occurrence of your tags.

== Changelog ==

= 1.10 =

* Corrected issue that BibSonomy returns JSON instead of XML
* Adapted rights for settings page
* Small visual enhancements

= 1.9.3 =

* Added russian translation, kudos to [FatCow](http://www.fatcow.com/)

= 1.9.2 =

* Added german translation
* All in one SEO descriptions will be shown

= 1.9.1 =

* The tag cloud is a little bit more sophisticated, i.e. calculates the size for the tags adapted to the tags at hand.

= 1.9 =

* The tag occurrence graph looks nicer now.

= 1.8.1 =

* Bugfixes.

= 1.8 =

* Added Harvard and JUCS style to _style_ attribute of _bibsonomy-posts_ macro.
* Added _sort_ attribute to _bibsonomy-posts_ macro.
* Nicer word-wrapping of a post's description (_excerpt_) on admin page.
* Updated BibSonomy PHP API, so we can leverage the caching mechanism.

= 1.7 =

* Publications can be styled with BibSonomy's own [format](http://www.bibsonomy.org/publ/user/cschenk).
* User can supply own CSS via _config.php_.

= 1.6 =

* Now we're using the [Google Chart API](http://code.google.com/apis/chart/) to generate charts with statistical information about tags.
* Custom configuration parameters now reside in a _config.php_.
* Removed _Minimum tag frequency_ from admin page and introduced a _minusercount_ attribute to the tags macro.

= 1.5 =

* There's no support for WordPress prior to 2.5 anymore.
* The plugin works with PHP 5.x; using PHP 4 won't cut it.
* We're using the [Shortcode API](http://codex.wordpress.org/Shortcode_API).
* [cURL](http://php.net/curl) must be installed on the server now.

= 1.2, 1.3 and 1.4 =

* not released

= 1.1 =

* First release

== Upgrade Notice ==

No special instructions.

== Licence ==

This plugin is released under the GPL.