<?php
add_filter( 'wp_title', 'bg_cpt_archive_doctitle', 15 );
/**
 * Provide an alternate doctitle for the Code custom post type archive.
 *
 */
function bg_cpt_archive_doctitle( $title ) {
	return 'Genesis Framework Code Snippets';
}

remove_action( 'genesis_loop', 'genesis_do_loop' );

add_action( 'genesis_loop', 'bg_code' );
function bg_code() { ?>

	<h1 class="entry-title">Code Snippets</h1> 
	
	<div class="entry-content">
	
		<p>From time to time I find myself looking for code that I can use when building themes. I'm guessing you have the same problem, so feel free to use what you see.</p>

		<p class="bold">Below is a list of resources for the Genesis Framework:</p>

		<ul>
			<li><a href="http://www.briangardner.com/code/css-best-practices/">CSS Best Practices</a></li>
			<li><a href="http://www.briangardner.com/code/functions-best-practices/">Functions Best Practices</a></li>
			<li><a href="http://www.briangardner.com/code/genesis-hook-reference/">Genesis Hook Reference</a></li>
		</ul>
		
		<p class="bold">Below is a list of code snippets for the Genesis Framework:</p>
			
		<div class="split-column-1">
		
			<ul>
				<li><a href="http://www.briangardner.com/code/add-body-class/">Add Body Class</a></li>
				<li><a href="http://www.briangardner.com/code/add-featured-images/">Add Featured Images</a></li>
				<li><a href="http://www.briangardner.com/code/add-footer-widgets/">Add Footer Widgets</a></li>
				<li><a href="http://www.briangardner.com/code/add-mobile-meta-tag/">Add Meta Tag for Mobile Browsers</a></li>
				<li><a href="http://www.briangardner.com/code/add-post-formats/">Add Post Formats</a></li>
				<li><a href="http://www.briangardner.com/code/add-structural-wraps/">Add Structural Wraps</a></li>
				<li><a href="http://www.briangardner.com/code/admin-management/">Admin Management</a></li>
				<li><a href="http://www.briangardner.com/code/create-color-style-options/">Create Color Style Options</a></li>
				<li><a href="http://www.briangardner.com/code/customize-author-box/">Customize Author Box</a></li>
				<li><a href="http://www.briangardner.com/code/customize-footer/">Customize Footer</a></li>
				<li><a href="http://www.briangardner.com/code/customize-header-url/">Customize Header URL</a></li>
				<li><a href="http://www.briangardner.com/code/customize-post-info/">Customize Post Info</a></li>
				<li><a href="http://www.briangardner.com/code/customize-post-meta/">Customize Post Meta</a></li>
				<li><a href="http://www.briangardner.com/code/customize-post-navigation/">Customize Post Navigation</a></li>
				<li><a href="http://www.briangardner.com/code/customize-search-form/">Customize Search Form</a></li>
				<li><a href="http://www.briangardner.com/code/force-layout-setting/">Force Layout Setting</a></li>
			</ul>
			
		</div><!-- end .split-column-1-->
		
		<div class="split-column-2">
		
			<ul>
				<li><a href="http://www.briangardner.com/code/load-custom-favicon/">Load Custom Favicon</a></li>
				<li><a href="http://www.briangardner.com/code/load-custom-style-sheet/">Load Custom Style Sheet</a></li>
				<li><a href="http://www.briangardner.com/code/load-google-fonts/">Load Google Fonts</a></li>
				<li><a href="http://www.briangardner.com/code/modify-gravatar-size/">Modify Gravatar Size</a></li>
				<li><a href="http://www.briangardner.com/code/register-default-layout-setting/">Register Default Layout Setting</a></li>
				<li><a href="http://www.briangardner.com/code/register-widget-areas/">Register Widget Areas</a></li>
				<li><a href="http://www.briangardner.com/code/remove-edit-link/">Remove Edit Link</a></li>
				<li><a href="http://www.briangardner.com/code/remove-header-elements/">Remove Header Elements</a></li>
				<li><a href="http://www.briangardner.com/code/reposition-breadcrumbs/">Reposition Breadcrumbs</a></li>
				<li><a href="http://www.briangardner.com/code/reposition-footer/">Reposition Footer</a></li>
				<li><a href="http://www.briangardner.com/code/reposition-navigation/">Reposition Navigation</a></li>
				<li><a href="http://www.briangardner.com/code/unregister-genesis-widgets/">Unregister Genesis Widgets</a></li>
				<li><a href="http://www.briangardner.com/code/unregister-layout-settings/">Unregister Layout Settings</a></li>
				<li><a href="http://www.briangardner.com/code/unregister-navigation-menus/">Unregister Navigation Menus</a></li>
				<li><a href="http://www.briangardner.com/code/unregister-sidebars/">Unregister Sidebars</a></li>
				<li><a href="http://www.briangardner.com/code/unregister-superfish-scripts/">Unregister Superfish Scripts</a></li>
			</ul>
			
		</div><!-- end .split-column-2-->
		
		<div class="clear"></div>
		
		<p class="bold">Below is a list of code snippets for WordPress:</p>
		
			<ul>
				<li><a href="http://www.briangardner.com/code/unregister-wordpress-widgets/">Unregister Default WordPress Widgets</a></li>
			</ul>

																											
	</div><!-- end .entry-content -->

<?php

}

genesis();