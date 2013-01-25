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


/*
add_action('genesis_before_post_content', 'child_get_book_review_field'); //display meta-box content before the content

//Function to show the content
function child_get_book_review_field() {
   if( 'book_review' != get_post_type() )
    return; 
 echo '<hr><strong>Title: '. genesis_get_custom_field( '_cmb_wjc_book_title' ) .'</strong>';
		echo '<p><strong>Author: </strong><em>'. genesis_get_custom_field( '_cmb_wjc_book_author' ) .'</em></p>';
				echo '<p><strong>Amazon Link:</strong><em> '. genesis_get_custom_field( '_cmb_wjc_book_website' ) .'</em></p><hr>';
						//echo '<p>'. genesis_get_custom_field( '_cmb_ds_artist_bio' ) .'</p><hr>';
}
*/
genesis();
