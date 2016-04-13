<?php get_header(); ?>

<div id="main">

<div id="content">

<h1><?php _e('404 Error', 'newdark'); ?></h1>

	<p><?php _e('We cannot seem to find what you were looking for.', 'newdark'); ?></p>
	<p><?php _e('Maybe we can still help you.', 'newdark'); ?></p>

	<ul>
		<li><?php _e('You can search our site using the form provided below.', 'newdark'); ?>

<p><?php get_search_form(); ?></p>

</li>
	</ul>

<?php _e('Click', 'newdark'); ?> <a href="<?php echo esc_url(home_url('/')); ?>"><?php _e('here', 'newdark'); ?></a> <?php _e('to return to the main page.', 'newdark'); ?>

</div>

<?php get_sidebar(); ?>

</div>

<div class="breaker"></div>

</div>

<?php get_footer(); ?>