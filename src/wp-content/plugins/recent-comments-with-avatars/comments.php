<?php
/*
	Plugin Name:	Recent Comment Avatars
	Plugin URI:		http://www.sterling-adventures.co.uk/blog/2009/01/01/comments-with-avatars/
	Description:	Add avatars to your recent comments sidebar widget.
	Author:			Peter Sterling
	Version:		3.5
	Changes:		1.0 - Initial version.
					2.0 - Added option for showing a comment excerpt, thanks to Angelo Milanetti for the idea.
					2.1 - Convert smilies.
					3.0 - Indicate country of posting (via IP address) with small flag icon.
					3.1 - Comment list flags; start or end option.
					3.2 - Don't allow flag options if no database table.
					3.3 - Fix printf of comment time.
					3.4 - Tidy up IP lookup.
					3.5 - Add option to exclude comments from scheduled posts.
	Author URI:		http://www.sterling-adventures.co.uk/
*/


function sa_get_comment_flag_from_ip($ip)
{
	global $wpdb;
	$sql = "select country_code2 code, country_name name from wp_iptocountry where ip_from <= inet_aton('" . $ip . "') and ip_to >= inet_aton('" . $ip . "')";
	$country = $wpdb->get_row($sql);
	if(!empty($country->code)) {
		$country->ip	= $ip;
		$country->code	= strtolower($country->code);
		$country->name	= ucwords(strtolower($country->name));
		$country->img	= "<img src='" . get_bloginfo('home') . "/wp-content/plugins/recent-comments-with-avatars/mini-flags/" . $country->code . ".gif' alt='" . $country->name . "' />";
	}
	return $country;
}


function sa_comment_list_with_flag()
{
	$options = get_option('sa_comments');
	switch($options['flags']) {
		case 'S': wp_list_comments('callback=sa_comment_flag_start'); break;
		case 'E': wp_list_comments('end-callback=sa_comment_flag_end'); break;
		default: wp_list_comments(); break;
	}
}


function sa_comment_flag_start($comment, $args, $depth)
{
	$GLOBALS['comment'] = $comment;

	if('div' == $args['style']) {
		$tag = 'div';
		$add_below = 'comment';
	}
	else {
		$tag = 'li';
		$add_below = 'div-comment';
	}

	echo '<', $tag, ' ', comment_class(empty($args['has_children']) ? '' : 'parent'), ' id="comment-', comment_ID(), '">';
	if('ul' == $args['style']) echo '<div id="div-comment-', comment_ID(), '">';
	echo '<div class="comment-author vcard">';

	if($args['avatar_size'] != 0) echo get_avatar($comment, $args['avatar_size']);
	printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>'), get_comment_author_link());
	echo '</div>';
	if($comment->comment_approved == '0') echo '<em>', __('Your comment is awaiting moderation.'), '</em><br />';

	echo '<div class="comment-meta commentmetadata"><a href="', htmlspecialchars(get_comment_link($comment->comment_ID)), '">', sprintf(__('%1$s at %2$s'), get_comment_date(),  get_comment_time()), '</a>';
	$country = sa_get_comment_flag_from_ip($comment->comment_author_IP);
	if(!empty($country->name)) {
		echo ' in ', $country->img, ' (', $country->name, ')';
	}
	edit_comment_link(__('Edit'), ' | ', '');
	echo '</div>';

	comment_text();

	echo '<div class="reply">';
	comment_reply_link(array_merge($args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth'])));
	echo '</div>';
	if('ul' == $args['style']) echo '</div>';
}


function sa_comment_flag_end($comment, $args, $depth)
{
	$country = sa_get_comment_flag_from_ip($comment->comment_author_IP);
	if(!empty($country->name)) {
		echo '<p class="commentmetadata">Comment made from: ', $country->img, ' (', $country->name, ')</p>';
	}

	if('div' == $args['style']) echo "</div>\n";
	else echo "</li>\n";
}


function sa_comments_widget_init()
{
	// Check widgets are activated.
	if(!function_exists('register_sidebar_widget')) return;

	// Customised recent comments widget.
	function sa_comments($args)
	{
		global $wpdb, $comments, $comment;
		extract($args, EXTR_SKIP);
		$options = get_option('sa_comments');
		$title = empty($options['title']) ? __('Recent Comments') : apply_filters('widget_title', $options['title']);
		if(!$number = (int)$options['number'])
			$number = 5;
		else if($number < 1)
			$number = 1;
		else if($number > 15)
			$number = 15;

		if(!$comments = wp_cache_get('recent_comments', 'widget')) {
			if($options['exclude'] == 'on') $sql = "select * from $wpdb->comments, $wpdb->posts where comment_approved = '1' and comment_post_ID = ID and post_status = 'publish' order by comment_date_gmt DESC limit $number";
			else $sql = "select * from $wpdb->comments where comment_approved = '1' order by comment_date_gmt DESC limit $number";
			$comments = $wpdb->get_results($sql);
			wp_cache_add('recent_comments', $comments, 'widget');
		}

		echo $before_widget;
			echo $before_title . $title . $after_title;
			echo ($options['avatar'] == 'on' ? '<table' : '<ul') . ' id="recentcomments">';
			if($comments) : foreach((array)$comments as $comment) :
				echo ($options['avatar'] == 'on' ? '<tr><td>' . get_avatar($comment, (empty($options['avatar-size']) ? 25 : $options['avatar-size'])) . '</td><td>' : '<li class="recentcomments">');

				$url = get_comment_author_url();
				$author = get_comment_author();

				if(empty( $url ) || 'http://' == $url) echo $author;
				else printf("<a href='$url' rel='external nofollow' class='url' %s>$author</a>", ((int)$comment->user_id > 0 ? '' : ($options['blank'] == 'on' ? "target='_blank'" : '')));
				printf(' on %s', '<a href="'. get_comment_link($comment->comment_ID) . '">' . get_the_title($comment->comment_post_ID) . '</a>');

				if($options['date'] == 'on') {
					echo '<br /><span style="font-size: ', (empty($options['date-size']) ? '0.5' : $options['date-size']), 'em;">', mysql2date((empty($options['date-format']) ? get_option('date_format') : $options['date-format']), $comment->comment_date), '</span>';
				}

				if($options['excerpt'] == 'on') {
					$exp = get_comment_excerpt();
					echo '<br />', ($options['smiles'] == 'on' ? convert_smilies($exp) : $exp);
				}

				if($options['flags-widget'] == 'on') {
					echo '<br />';
					$country = sa_get_comment_flag_from_ip($comment->comment_author_IP);
					if(!empty($country->name)) echo '<span style="font-size: ', (empty($options['date-size']) ? '0.5' : $options['date-size']), 'em;">From ', $country->img, ' (', $country->name, ')</span>';
				}

				echo ($options['avatar'] == 'on' ? '</td></tr>' : '</li>');
			endforeach; endif;
			echo ($options['avatar'] == 'on' ? '</table>' : '</ul>');
		echo $after_widget;
	}


	// Customised recent comments widget control form.
	function sa_comments_control()
	{
		$options = $newoptions = get_option('sa_comments');
		if(isset($_POST["recent-comments-submit"])) {
			$newoptions['title'] = strip_tags(stripslashes($_POST["recent-comments-title"]));
			$newoptions['number'] = (int)$_POST["recent-comments-number"];
			$newoptions['avatar'] = strip_tags(stripslashes($_POST["recent-comments-avatar"]));
			$newoptions['blank'] = strip_tags(stripslashes($_POST["recent-comments-blank"]));
			$newoptions['avatar-size'] = strip_tags(stripslashes($_POST["recent-comments-avatar-size"]));
			$newoptions['date'] = strip_tags(stripslashes($_POST["recent-comments-date"]));
			$newoptions['date-format'] = strip_tags(stripslashes($_POST["recent-comments-date-format"]));
			$newoptions['date-size'] = strip_tags(stripslashes($_POST["recent-comments-date-size"]));
			$newoptions['excerpt'] = strip_tags(stripslashes($_POST["recent-comments-excerpt"]));
			$newoptions['smiles'] = strip_tags(stripslashes($_POST["recent-comments-smiles"]));
			$newoptions['flags'] = strip_tags(stripslashes($_POST["recent-comments-flags"]));
			$newoptions['flags-widget'] = strip_tags(stripslashes($_POST["recent-comments-flags-widget"]));
			$newoptions['exclude'] = strip_tags(stripslashes($_POST["recent-comments-exclude"]));
		}
		if($options != $newoptions) {
			$options = $newoptions;
			update_option('sa_comments', $options);
		}
		$title = attribute_escape($options['title']);
		if(!$number = (int)$options['number'])
			$number = 5;
		?>
		<p><label for="recent-comments-title">Title: <input class="widefat" id="recent-comments-title" name="recent-comments-title" type="text" value="<?php echo $title; ?>" /></label></p>
		<p>
			<label for="recent-comments-number">Number of comments to show: <input style="width: 40px; text-align: center;" id="recent-comments-number" name="recent-comments-number" type="text" value="<?php echo $number; ?>" /></label>
			<br />
			<small>At most 15</small>
		</p>
		<p><label for="recent-comments-avatar"><input class="checkbox" type="checkbox" id="recent-comments-avatar" name="recent-comments-avatar" <?php echo ($options['avatar'] ? 'checked="checked"' : ''); ?> /> show avatar?</label></p>
		<p><label for="recent-comments-avatar-size">Avatar size: <input style="width: 40px; text-align: center;" id="recent-comments-avatar-size" name="recent-comments-avatar-size" type="text" value="<?php echo empty($options['avatar-size']) ? '25' : $options['avatar-size']; ?>" /> px</label></p>
		<p>
			<label for="recent-comments-blank"><input class="checkbox" type="checkbox" id="recent-comments-blank" name="recent-comments-blank" <?php echo ($options['blank'] ? 'checked="checked"' : ''); ?> /> new window for avatar hyperlinks?</label>
			<br />
			<small>Include <code>target='_blank'</code></small>
		</p>
		<p><label for="recent-comments-date"><input class="checkbox" type="checkbox" id="recent-comments-date" name="recent-comments-date" <?php echo ($options['date'] ? 'checked="checked"' : ''); ?> /> show comment date?</label></p>
		<p>
			<label for="recent-comments-date-format">Date format: <input style="width: 180px; text-align: center;" id="recent-comments-date-format" name="recent-comments-date-format" type="text" value="<?php echo empty($options['date-format']) ? 'jS M y' : $options['date-format']; ?>" /></label>
			<br />
			<small><a href='http://php.net/date' target="_blank">Date format help...</a></small>
		</p>
		<p>
			<label for="recent-comments-date-size">Date text size: <input style="width: 40px; text-align: center;" id="recent-comments-date-size" name="recent-comments-date-size" type="text" value="<?php echo empty($options['date-size']) ? '0.5' : $options['date-size']; ?>" /> em</label>
			<br />
			<small>Factor of normal text size, e.g. 0.5 is half.</small>
		</p>
		<p><label for="recent-comments-excerpt"><input class="checkbox" type="checkbox" id="recent-comments-excerpt" name="recent-comments-excerpt" <?php echo ($options['excerpt'] ? 'checked="checked"' : ''); ?> /> show comment excerpt?</label></p>
		<p>
			<label for="recent-comments-smiles"><input class="checkbox" type="checkbox" id="recent-comments-smiles" name="recent-comments-smiles" <?php echo ($options['smiles'] ? 'checked="checked"' : ''); ?> /> convert emoticons in comment excerpts?</label>
			<br />
			<small>Only works if <i>Convert Emoticons</i> is set on the <i>Settings &raquo; Writing</i>.</small>
		</p>
		<?php
			global $wpdb;
			$found = false;
			foreach($wpdb->get_col("show tables", 0) as $table) {
				if($table == 'wp_iptocountry') {
					$found = true;
					break;
				}
			}

			if($found) { ?>
				<p><label for="recent-comments-flags-widget"><input class="checkbox" type="checkbox" id="recent-comments-flags-widget" name="recent-comments-flags-widget" <?php echo ($options['flags-widget'] ? 'checked="checked"' : ''); ?> /> show flag for originating country?</label></p>
				<p>
					<label for="recent-comments-flags">Show flag for originating country in main comment listings at the </label>
					<input type='radio' name='recent-comments-flags' value='S' <?php echo ($options['flags'] == 'S' ? 'checked="checked"' : ''); ?> /> start
					<input type='radio' name='recent-comments-flags' value='E' <?php echo ($options['flags'] == 'E' ? 'checked="checked"' : ''); ?> /> end
					<input type='radio' name='recent-comments-flags' value='N' <?php echo ($options['flags'] == 'N' ? 'checked="checked"' : ''); ?> /> no flags.
					<br />
					<small>Requires theme comment loop modification (see readme).</small>
				</p>
			<?php }
		?>
		<p><label for="recent-comments-exclude"><input class="checkbox" type="checkbox" id="recent-comments-exclude" name="recent-comments-exclude" <?php echo ($options['exclude'] ? 'checked="checked"' : ''); ?> /> exclude comments from scheduled posts?</label></p>
		<input type="hidden" id="recent-comments-submit" name="recent-comments-submit" value="1" />
	<?php }


	// Register widgets and their controls.
	wp_register_sidebar_widget('sa-recent-comments', 'Recent Comments', sa_comments, array('classname' => 'widget_recent_comments', 'description' => 'Recent comments with configurable option to show avatars and more!'));
	wp_register_widget_control('sa-recent-comments', 'Recent Comments', 'sa_comments_control', array('width' => 300));
}

add_action('plugins_loaded', 'sa_comments_widget_init');
?>