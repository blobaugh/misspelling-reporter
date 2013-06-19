<?php 
/*
* This code used when plugin is set for deletion via admin panel.
*/

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

global $wpdb;

$wpdb->query("DELETE FROM wp_posts WHERE post_type = 'missr_report' ");
