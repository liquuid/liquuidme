=== Recent Comments with Avatars ===
Contributors: peterwsterling
Donate link: http://www.sterling-adventures.co.uk/blog/2009/01/01/recent-comments-with-avatars-plugin/
Author URI: http://www.sterling-adventures.co.uk/
Plugin URI: http://www.sterling-adventures.co.uk/blog/2009/01/01/recent-comments-with-avatars-plugin/
Tags: avatars, comments
Requires at least: 2.5
Tested up to: 2.8
Stable tag: trunk

This plug-in provides a configurable widget to display recent comments with comment author avatars.

== Description ==
Once the widget is added to your sidebar you may easily configure it to provide a display of recent comments with comment author avatars.  The options are:<ul>
	<li>The title of the widget.</li>
	<li>Limit the number of comments to shown (1 to 15).</li>
	<li>Option to display the avatar of the comment author, or not.</li>
	<li>Set the size of the avatar.</li>
	<li>Chose to have the comment author's URL (if given) open in a new window,</li>
	<li>Show the date of the comment, or not.</li>
	<li>Set the format of the date.</li>
	<li>Set the size (as a fraction of the normal text size) of the date string.</li>
	<li>Show an excerpt from the comment.</li>
	<li>Convert smilies in the excerpt.</li>
	<li>Show a country flag indicating the source of the comment, requires MySQL database modifications, see below.</li>
	<li>Show a country flag in main comment loops, non-widget, requires theme and MySQL database modifications, see below.</li>
</ul>

The style of the output may be controlled with simple CSS rules that need to be included in your <code>styles.css</code> template file.
	The container (i.e. <code>&lt;ul&gt;</code> or <code>&lt;table&gt;</code>) has the id recentcomments (use CSS <code>.recentcomments</code>).
	List items (i.e. <code>&lt;li&gt;</code>) have the class recentcomments (use CSS <code>#recentcomments</code>).

== Installation ==
* Put the complete plug-in folder into your WordPress plug-in directory (if this doesn't make sense it probably isn't something you should be trying) and activate it.
* Go to your Appearance &raquo; Widgets page and include <i>Recent Comments</i> in your sidebar.
* Use the configuration options to set up your widget to display recent comments as you like...  ;-)
* Optionally, to use the comment flag options, the following notes must be completed...

<strong>Flag installation notes</strong>
Modify your theme to show country flags in main comment loops, you must make this change to your <code>comments.php</code> theme template file.  Change <code>wp_list_comments();</code> to <code>sa_comment_list_with_flag();</code>.
And you must import the <code>wp_iptocountry.sql</code> file into your MySQL WordPress database.
