<?php

/** Load Google stylesheet to header */
add_action( 'wp_enqueue_scripts', 'load_google_stylesheet' );
function load_google_stylesheet() {
	if ( is_single( '2942' ) ) {
		wp_enqueue_style( 'google-stylesheet', CHILD_URL . '/css/google.css', array(), PARENT_THEME_VERSION );
	}
}

/** Load Color Buttons stylesheet to header */
add_action( 'wp_enqueue_scripts', 'load_color_buttons_stylesheet' );
function load_color_buttons_stylesheet() {
	if ( is_single( '4720' ) ) {
		wp_enqueue_style( 'color-buttons', CHILD_URL . '/css/color-buttons.css', array(), PARENT_THEME_VERSION );
	}
}

/** Load Content Boxes stylesheet to header */
add_action( 'wp_enqueue_scripts', 'load_content_boxes_stylesheet' );
function load_content_boxes_stylesheet() {
	if ( is_single( '4700' ) ) {
		wp_enqueue_style( 'content-boxes', CHILD_URL . '/css/content-boxes.css', array(), PARENT_THEME_VERSION );
	}
}

/** Load Gradient Buttons stylesheet to header */
add_action( 'wp_enqueue_scripts', 'load_gradient_buttons_stylesheet' );
function load_gradient_buttons_stylesheet() {
	if ( is_single( '5619' ) ) {
		wp_enqueue_style( 'color-buttons', CHILD_URL . '/css/gradient-buttons.css', array(), PARENT_THEME_VERSION );
	}
}

/** Add newsletter section on single posts */
add_action( 'genesis_after_post_content', 'include_newsletter' );
function include_newsletter() {
	if ( is_singular( 'post' ) )
	require( CHILD_DIR.'/newsletter.php' );
}

/** Add the edit link to bottom of post */
add_action('genesis_after_post', 'bg_edit_link');
	function bg_edit_link() { ?>
	<?php edit_post_link('(Edit)', '<p>', '</p>'); ?>
	<?php
}

genesis();