=== Podcast Channels ===
Contributors: alanft
Tags: admin, podcast, podcasting, media, categories
Requires at least: 3.6
Tested up to: 3.8.1
Stable tag: 0.28
License: GPLv2 or later

Podcast Channels lets you setup 'Category-Casting' -- a different podcast per category.

== Description ==
What do you need to podcast with Wordpress? Nothing -- Wordpress puts enclosures in the feed for you. That's the bare bones and it works well enough.

But iTunes metadata would be nice. And, how about different info in different categories? Podcast Channels lets you specify iTunes metadata for the home feed, specific category feeds, and even 'user defined' ('Conditional') feeds (see the FAQ).

= Setting up Category Channels =
Go to the 'Media > Podcast Channels' page to set up the Site Defaults (it's 'Manage > Podcast Channels' for pre-2.7 WP). If you are not happy with the defaults shown in grey, click in the fields to set your own. If you want the main blog page feed to use this data, tick the 'add to Home Feed' option.

To add a podcast channel, choose a blog category from the drop-down menu and click 'Add Category Channel'. When the new channel pops up, there is an 'Add field...' drop-down menu in it that lets you specify defaults for this channel that over-ride the site defaults.

Leave a field undefined/blank or the same as the default and it will be removed from the channel definition - which means the value reverts to the site default.

= Fields that don't inherit =
Two fields are channel specific and do not inherit from the site defaults:

'Feed Moving To' is used to tell your podcast users (and the iTunes directory) that the podcast channel is moving to a new URL.

'Feedburner URL' redirects everyone (except the Feedburner site!) to a Feedurner URL so that they can collect stats for you.

= Audio Files in the Media Library =
When you upload an audio file, Podcast Channels adds 'Artist', 'Duration' and 'Explicit' fields to the Media Library. It initially fills in the artist and duration with the information found in the ID3 tags, but you are free to edit the fields.

Remember to set the explicit flag to 'Yes' if the content is not suitable for children. Apple are likely to de-list your podcast from the iTunes directory if you do not.


= Copyright and Image =
Podcast Channels adds image and copyright info to all your feeds if you specify it in the extra fields in 'Settings > General' (see screenshots).

== Changelog ==

0.28 - Switched to using the copy of getID3 included in WP since v 3.6

0.26 - Add episode author and episode category options - including taking episode author from the ID3 tags. (Plus a number of other bug fixes and improvements.)

0.25 - Added initial Feedburner support

0.22 - Making it work with WP 2.7

0.21 - important bug fix (i am an idiot)

0.20 - Added duration and explicit metadata for audio items in the Media Library.
      And got the Title and Link data working too.

0.1 - Just starting out, something to get it working

== Upgrade Notice ==

= 0.28 =
This version fixes a cross-site-scripting related issue.  Upgrade immediately.


== Installation ==
1. Upload the `podcast-channels` folder to the plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get going under Media > Podcast Channels (WP 2.7+) or Manage > Podcast Channels (pre-2.7)
4. There are two extra fields in Settings > General for you to use too.


== Frequently Asked Questions ==

= How do I add an audio file? =
All you need do is have the full URL of an audio file in a post, and it will pop up as an enclosure, and consequently in the RSS feed to be downloaded by a podcast client. So just use WP's usual uploader and Media Library -- a link to the file will do. Or if you use the [wpaudioplayer](http://wpaudioplayer.com/) plugin, the [audio: xxxx] text in the post will work so long as xxxx is a full URL to an audio file.

= Add Conditional Channel? =
It seemed like a good idea at the time -- a way to setup more generic channels than just by category. You can put in PHP and WP's [Conditional Tags](http://codex.wordpress.org/Conditional_Tags) here to make any kind of channel

eg `is_tag('film')` will make a channel in /tags/film/feed/

and `is_author('alan')` will make a channel in /author/alan/feed/

Kind of cool, but not well tested. Let me know if you find this useful. You can specify full PHP code, and when it 'returns TRUE', the channel data appears in the feed.

If you don't use 'return' in the code, there is an implicit return added on the front and a terminating ';' on the end. If you DO specify return, remember to put the final semi-colon in.

== Screenshots ==

1. Setting up the channels.
2. Two extra fields added to all RSS feeds.
3. Artist, Duration and Explicit metadata appear for audio items in the Media Library.