<?php
/*
 *Template Name: Books Reviews Archive
 */
remove_action( 'genesis_before_post_content', 'genesis_post_info' );
remove_action( 'genesis_after_post_content', 'genesis_post_meta' );
remove_action( 'genesis_after_post', 'genesis_do_author_box_single' );

remove_action('genesis_loop', 'genesis_do_loop');
add_action('genesis_loop', 'custom_do_loop');
function custom_do_loop() {
     global $paged;
    $args = array('post_type' => 'book_review');
        //$args = array('post_type' => 'books');
 
    genesis_custom_loop( $args );
 }
genesis();
