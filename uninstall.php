<?php 
/*
* This code used when plugin is set for deletion via admin panel.
*/

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

/**
* Cleans up all posts of missr_report type
*/
function missr_uninstall() {
	
	global $post;

	$posts_query = new WP_Query( 'post_type=missr_report' );
	
	while ( $posts_query->have_posts() ) {
		$posts_query->the_post();
		wp_delete_post( $post->ID, true );
	}
	
	wp_reset_postdata();
} 

missr_uninstall();