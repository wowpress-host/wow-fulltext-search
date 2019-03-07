<?php

namespace WowFullTextSearch;

class AdminInit {
	static public function admin_init() {
		if ( isset( $_REQUEST['wow_search_settings_action'] ) ) {
			AdminPage::settings_action();
		}

		add_action('admin_print_scripts-settings_page_wow-search-page',
			array( 'WowFullTextSearch\AdminPage', 'admin_print_scripts' ) );
		add_filter( 'wp_ajax_wow_search_build',
			array( 'WowFullTextSearch\AdminPage', 'wp_ajax_wow_search_build' ) );

		add_filter( 'plugin_action_links_' .
			plugin_basename( plugin_dir_path( __FILE__ ) . 'wow-fulltext-search.php' ),
			array( __CLASS__, 'plugin_action_links' ) );
	}



	static public function admin_menu() {
		add_options_page(
			'Wow Search',
			'Search',
			'manage_options',
			'wow-search-page' ,
			array( 'WowFullTextSearch\AdminPage' , 'render' ) );
	}



	static public function plugin_action_links( $links ) {
		$url = add_query_arg(
			array( 'page' => 'wow-search-page' ),
			admin_url( 'options-general.php' ) );

		$links[] = '<a href="' . esc_url( $url ) . '">'.
			esc_html__( 'Settings' , 'wow_search' ) .
			'</a>';

		return $links;
	}
}
