<?php
// Start the engine
require_once( get_template_directory() . '/lib/init.php' );

// Add Viewport meta tag for mobile browsers
add_action( 'genesis_meta', 'bg_viewport_meta_tag' );
function bg_viewport_meta_tag() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>';
}

// Unregister secondary sidebar
unregister_sidebar( 'sidebar-alt' );

// Add the topnav section
add_action( 'genesis_before', 'bg_topnav' );
function bg_topnav() {
	echo '<div id="topnav"><div class="wrap"><span class="left"><span class="from-the-blog">Like this theme?</span><a href="https://github.com/bgardner/avalanche">You can fork it on Github.</a></span><span class="right"><a class="first" href="http://www.briangardner.com/about/">The 411 on Me</a><a href="http://www.briangardner.com/code/">Code Snippets</a><a href="http://www.briangardner.com/themes/">WordPress Themes</a></span></div></div>';
}

// Customize the post info function
add_filter( 'genesis_post_info', 'post_info_filter' );
function post_info_filter($post_info) {

	if ( is_singular( 'code' ) ) {
		$post_info = '';
	} 

	else {
		$post_info = 'Posted on [post_date] [post_edit] [post_comments]';
	}

	return $post_info;
}

// Modify the WordPress read more link
add_filter( 'the_content_more_link', 'custom_read_more_link' );
function custom_read_more_link() {
	return '<a class="more-link" href="' . get_permalink() . '">Continue Reading</a>';
}

// Remove the post meta function
remove_action( 'genesis_after_post_content', 'genesis_post_meta' );

// Add sharing icons to the top of content
remove_filter( 'the_content', 'sharing_display', 19 );
remove_filter( 'the_excerpt', 'sharing_display', 19 );

add_filter( 'the_content', 'sharedaddy_sharing_at_bottom', 19 );
add_filter( 'the_excerpt', 'sharedaddy_sharing_at_bottom', 19 );

function sharedaddy_sharing_at_bottom( $content = '' ) {
	if ( function_exists( 'sharing_display' ) ) {
		return sharing_display() . $content;
	}
	else {
		return $content;
	}
}

// Modify comments header text in comments
add_filter( 'genesis_title_comments', 'custom_genesis_title_comments' );
function custom_genesis_title_comments() {
	$title = '<h3><span class="comments-title">Comments</span></h3>';
	return $title;
}

// Modify the speak your mind text
add_filter( 'genesis_comment_form_args', 'custom_comment_form_args' );
function custom_comment_form_args($args) {
	$args['title_reply'] = '<span class="comments-title">Speak Your Mind</span>';
	return $args;
}

// Create a custom Gravatar
function add_custom_gravatar ($avatar) {
$custom_avatar = get_bloginfo('template_directory') . '/images/gravatar-bg.png';
$avatar[$custom_avatar] = "Custom Gravatar";
	return $avatar;
	}
add_filter( 'avatar_defaults', 'add_custom_gravatar' );

// Customize search form input box text
add_filter( 'genesis_search_text', 'custom_search_text' );
function custom_search_text($text) {
	return esc_attr( 'Search my website ...' );
}

// Create code custom post type
add_action( 'init', 'code_post_type' );
function code_post_type() {
	register_post_type( 'code',
		array(
			'labels' => array(
				'name' => __( 'Code' ),
				'singular_name' => __( 'Code Snippets' ),
			),
			'has_archive' => true,
			'hierarchical' => true,
			'menu_icon' => get_stylesheet_directory_uri() . '/images/icons/code.png',
			'public' => true,
			'rewrite' => array( 'slug' => 'code' ),
			'supports' => array( 'title', 'editor', 'custom-fields', 'genesis-seo', 'thumbnail' ),
		)
	);
}


// Add span class to widget headline
add_filter( 'widget_title', 'child_widget_title' );
function child_widget_title( $title ){
	if( $title )
		return sprintf('<span class="sidebar-title">%s</span>', $title );
}

// Customize the footer
remove_action( 'genesis_footer', 'genesis_footer_markup_open', 5 );
remove_action( 'genesis_footer', 'genesis_do_footer' );
remove_action( 'genesis_footer', 'genesis_footer_markup_close', 15 );
add_action( 'genesis_after', 'bg_footer' );
	function bg_footer() { ?>
	<div id="footer"><div class="wrap">
		<p>&copy; Copyright 2012. Powered by <a href="http://www.starbucks.com">Starbucks lattes</a>, <a href="http://www.sarahmclachlan.com">really good music</a> and the <a href="http://www.studiopress.com/">Genesis Framework</a>. <a href="http://www.briangardner.com/contact/">Get in touch.</a></p>
	</div></div>
	<?php
}