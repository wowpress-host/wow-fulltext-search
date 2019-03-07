<?php
/*
Plugin Name: Wow FullText Search
Plugin URI: https://wowpress.host/plugins/wow-search
Description: Fast fulltext search replacing default search.
Version: 1.0
Author: WowPress.host
Author URI: https://wowpress.host
License: GPL2
*/
if ( !defined( 'ABSPATH' ) ) {
	die();
}



/*
 * PSR-4 class autoloader
 */
function wowfts_spl_autoload( $class ) {
	$class = rtrim( $class, '\\' );
	if ( substr( $class, 0, 18 ) == 'WowFullTextSearch\\' ) {
		$filename = __DIR__ . DIRECTORY_SEPARATOR .
			substr( $class, 18 ) . '.php';

		if ( file_exists( $filename ) ) {
			require $filename;
		}
	}
}

spl_autoload_register( 'wowfts_spl_autoload' );



register_activation_hook( __FILE__,
	array( 'WowFullTextSearch\Activation', 'activate' ) );
register_deactivation_hook( __FILE__,
	array( 'WowFullTextSearch\Activation', 'deactivate' ) );

add_action( 'init', array( 'WowFullTextSearch\WpQueryHook', 'init' ) );

add_action( 'delete_post', array( 'WowFullTextSearch\SyncronizeHook', 'delete_post' ) );
add_action( 'publish_post', array( 'WowFullTextSearch\SyncronizeHook', 'on_post_change' ) );
add_action( 'save_post', array( 'WowFullTextSearch\SyncronizeHook', 'on_post_change' ) );

add_action( 'admin_init', array( 'WowFullTextSearch\AdminInit', 'admin_init' ) );
add_action( 'admin_menu', array( 'WowFullTextSearch\AdminInit', 'admin_menu' ) );
