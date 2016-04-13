<?php get_header(); ?>

<div id="main">

<div id="content">

<?php printf( __( 'Category Archives: %s', 'newdark' ), single_cat_title( '', false ) ); ?>

<div class="breaker"></div>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<div class="post-title"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></div>
<div class="post-date"></div>

<?php if ( has_post_thumbnail()) : ?>
   <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" >
   <?php the_post_thumbnail('category-thumb', array('class' => 'alignleft')); ?>
   </a>
 <?php endif; ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

<?php the_content('Read More...'); ?>

</div>

<div class="commentstext">

<?php _e('Posted on ', 'newdark'); ?><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_time(get_option('date_format').', '.get_option('time_format')) ?></a><?php _e(' By ', 'newdark'); ?><?php the_author_posts_link(); ?>

<div class="delimiter"></div>

<?php
  comments_popup_link( 'No comments yet', '1 comment', '% comments', 'comments-link', '');
?> <?php _e('Categories: ', 'newdark'); ?><?php the_category(', '); ?> <?php the_tags(); ?>

</div>

<div class="breaker"></div>

<?php endwhile; else: ?>

<p><?php _e('Sorry, no posts matched your criteria.', 'newdark'); ?></p><?php endif; ?>

<?php wp_link_pages(array('next_or_number'=>'next', 'previouspagelink' => ' &laquo; ', 'nextpagelink'=>' &raquo;')); ?>

<?php comments_template(); ?>

<h4 class="pagi">
<?php posts_nav_link(' &#183 ', 'Previous Page', 'Next Page'); ?>
</h4>

</div>

<?php get_sidebar(); ?>

</div>

<div class="breaker"></div>

</div>

<?php get_footer(); ?>