		</section><!-- End Main Section -->

		<section id="sidebar-off" role="complementary">
			<nav id="sideMenu" role="navigation">
		    	<?php
		    		if ( has_nav_menu( 'primary_navigation' ) ):
		    	    	wp_nav_menu( array(
		    				'theme_location' => 'primary_navigation',
		    				'container' =>false,
		    				'menu_class' => '',
		    				'echo' => true,
		    				'before' => '',
		    				'after' => '',
		    				'link_before' => '',
		    				'link_after' => '',
		    				'depth' => 0,
		    				'items_wrap' => '<ul class="nav-bar">%3$s</ul>',
		    				'walker' => new reverie_walker())
		    			);
		    		endif;
		    		?>											
		   	</nav>
		</section>
		
		</div><!-- End Off-Canvas Row -->
		
		<footer id="content-info" role="contentinfo">
			<div class="row">
				<?php dynamic_sidebar("Footer"); ?>
			</div>
			<div class="row">
				<div class="four columns">
					&copy; 2008-<?php echo date('Y'); ?> All rights reserved.
					<br>
					Powered by <a href="http://themefortress.com/reverie/" rel="nofollow" title="Reverie Framework">Reverie Framework</a>.
				</div>
				<?php wp_nav_menu(array('theme_location' => 'utility_navigation', 'container' => false, 'menu_class' => 'eight columns footer-nav')); ?>
			</div>
		</footer>
			
	</div><!-- Container End -->
	
	<!-- Prompt IE 6 users to install Chrome Frame. Remove this if you want to support IE 6.
	     chromium.org/developers/how-tos/chrome-frame-getting-started -->
	<!--[if lt IE 7]>
		<script defer src="//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js"></script>
		<script defer>window.attachEvent('onload',function(){CFInstall.check({mode:'overlay'})})</script>
	<![endif]-->
	
	<?php wp_footer(); ?>
</body>
</html>