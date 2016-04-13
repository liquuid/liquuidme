<?php

if ( ! isset( $content_width ) ) $content_width = 600;

function newdark_wp_title( $title ) {
	global $page, $paged;

	if ( is_feed() )
		return $title;

	$site_description = get_bloginfo( 'description' );

	$filtered_title = $title . get_bloginfo( 'name' );
	$filtered_title .= ( ! empty( $site_description ) && ( is_home() || is_front_page() ) ) ? ' | ' . $site_description: '';
	$filtered_title .= ( 2 <= $paged || 2 <= $page ) ? ' | ' . sprintf( __( 'Page %s', 'newdark' ), max( $paged, $page ) ) : '';

	return $filtered_title;
}
add_filter( 'wp_title', 'newdark_wp_title' );

function newdark_widgets_init() {

	register_sidebar( array(
		'name' => 'Home right sidebar',
		'id' => 'home_right_1',
		'before_widget' => '',
		'after_widget' => '<br />',
		'before_title' => '<h5 class="sidebarhd">',
		'after_title' => '</h5>',
	) );

}
add_action( 'widgets_init', 'newdark_widgets_init' );

function newdark_font_url() {
	$font_url = '';
	/*
	 * Translators: If there are characters in your language that are not supported
	 * by this font, translate this to 'off'. Do not translate into your own language.
	 */
	if ( 'off' !== _x( 'on', 'Lato font: on or off', 'newdark' ) ) {
		$font_url = add_query_arg( 'family', urlencode( 'Lato' ), "//fonts.googleapis.com/css" );
	}

	return $font_url;
}

function newdark_scripts() {

// Add font, used in the main stylesheet.
wp_enqueue_style( 'newdark-font', newdark_font_url(), array(), null );

wp_enqueue_style( 'newdark-style', get_stylesheet_uri() );

if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'newdark_scripts' );

// Register Theme Features
function newdark_setup()  {

// Add theme support for Semantic Markup
$markup = array( 'search-form', 'comment-form', 'comment-list', );
add_theme_support( 'html5', $markup );	

add_theme_support( 'automatic-feed-links' );

add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 250, 150, true );
add_image_size( 'category-thumb',  250, 150, true );
add_image_size( 'category-thumb-full',  640, 350, true );

register_nav_menu( 'header-menu',__( 'Header Menu', 'newdark' ) );

add_editor_style( array( 'editor-style.css', newdark_font_url() ) );

}

// Hook into the 'after_setup_theme' action
add_action( 'after_setup_theme', 'newdark_setup' );

function newdark_custom_header_setup() {
	$args = array(

		// Set height and width, with a maximum value for the width.
		'height'                 => 200,
		'width'                  => 960,
		'max-width'              => 960,
                'header-text'            => false,

		// Support flexible height and width.
		'flex-height'            => false,
		'flex-width'             => false,

		// Random image rotation off by default.
		'random-default'         => false,

	        'uploads'                => true,
	        'wp-head-callback'       => '',
	        'admin-head-callback'    => '',
	        'admin-preview-callback' => '',
	);

	add_theme_support( 'custom-header', $args );
}
add_action( 'after_setup_theme', 'newdark_custom_header_setup' );

function newdark_excerpt_more($more) {
       global $post;
    return ' <a class="more-link" href="'. get_permalink($post->ID) . '">Read More...</a>';
}
add_filter('excerpt_more', 'newdark_excerpt_more');

function newdark_excerpt_length( $length ) {
	return 50;
}
add_filter( 'excerpt_length', 'newdark_excerpt_length', 999 );

?>