<?php

/**
* Set up the Grid Loop
* @author Bill Erickson
* @link http://www.billerickson.net/genesis-grid-loop-content/
*
*/
function child_grid_loop_helper() {
     genesis_grid_loop( array(
               'post_type' => array( 'post', 'artist', 'book_review', ),
               'features' => 1,
               'feature_image_size' => 0,
               'feature_image_class' => 'alignleft post-image',
               'feature_content_limit' => 0,
               'grid_image_size'          => 0,
               'grid_content_limit' => 250,
               'more' => __( '[Continue reading]', 'genesis' ),
               'posts_per_page' => 5,
          ) );
}
remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'child_grid_loop_helper' );

/**
* Attach Grid Loop Functions
* @author Bill Erickson
* @link http://www.billerickson.net/genesis-grid-loop-content/
*
*/
add_action('genesis_before_post_content', 'child_grid_loop_image');

/**
* Grid Loop Image
* @author Bill Erickson
* @link http://www.billerickson.net/genesis-grid-loop-content/
*
*/
function child_grid_loop_image() {
     if ( in_array( 'genesis-grid', get_post_class() ) ) {
          global $post;
          echo '<p class="thumbnail"><a href="'.get_permalink().'">'.get_the_post_thumbnail($post->ID, 'grid-thumbnail').'</a></p>';
     }
}

/** Add the home featured section 
add_action( 'genesis_before_loop', 'wiredpm_home_featured' );
function wiredpm_home_featured() {

    /** Do nothing on page 2 or greater 
    if ( get_query_var( 'paged' ) >= 2 )
        return;

    genesis_widget_area( 'home-featured', array(
    'before' => '<div class="home-featured widget-area">',
    ) );

}




remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'wiredpm_grid_loop_helper' );

/** Add support for Genesis Grid Loop 
function wiredpm_grid_loop_helper() {

	if ( function_exists( 'genesis_grid_loop' ) ) {
		genesis_grid_loop( array(
			'features' => 1,
			'feature_image_size' => 0,
			'feature_image_class' => 'alignleft post-image',
			'feature_content_limit' => 0,
			'grid_image_size'		=> 'grid-thumbnail',
			'grid_image_class'		=> 'alignnone',
			'grid_content_limit' => 250,
			'more' => __( '[Continue reading]', 'genesis' ),
			'posts_per_page' => 5,
		) );
	} else {
		genesis_standard_loop();
	}

}

*/

genesis();