<?php

namespace WowFullTextSearch;

class AdminPage {
	static public function admin_print_scripts() {
		wp_enqueue_script( 'wow_search',
			plugin_dir_url( __FILE__ ) . 'AdminPage_View.js',
			array( 'jquery' ), '1.0' );

		wp_localize_script( 'wow_search', 'wow_search_nonce',
			wp_create_nonce( 'wow_search' ) );

		$status = Util::status();
		$value = 'start';

		if ( isset( $status['status'] ) && $status['status'] == 'working' ) {
				$value = 'paused';
		}
		wp_localize_script( 'wow_media_library_fix', 'wow_mlf_state', $value );
	}



	static public function render() {
		$config = Util::config();
		$message_saved = AdminPage::message_saved();
		$message_errors = '';
		$post_types = Data::post_types();



		$status = Util::status();
		$hide = 'style="display: none"';

		$build_start_button_text = 'Rebuild Search Index';

		$status_color = 'red';
		$status_continue_url = '';

		if ( substr( $status['status'], 0, 5 ) == 'done.' ) {
			$search_request = array(
				'query' => array(
					'query_string' => array(
						'query' => 'test',
					)
				),
				'from' => 0,
				'size' => 1
			);

			$e = Util::engine( Util::config() );

			$response = null;
			try {
				$response = $e->search( $search_request );
			} catch ( \Exception $e ) {
				$status_color = "red";
				$status_text = 'Search Engine is not reachable: ' . $e->getMessage();
			}

			if ( isset( $response['error_update_status'] ) ) {
				$status['status'] = $response['error_update_status'];
				Util::status_set( $status );
			}

			if ( is_null( $response ) ) {
				//
			} else if ( isset( $response['error_message'] ) ) {
				$status_text = $response['error_message'];
				$status_continue_url = $response['error_continue_url'];
			} else {
				$status_color = "green";
				$status_text = 'Index in sync, searching via Search Engine';
			}



		} else {
			if ( $config['search_engine'] == 'wowsearch' && empty( $config['wowsearch']['api_key'] ) ) {
				$status_text = 'You need to specify API Key. Use "Obtain API Key" button if you don\'t have one yet.';
			} else {
				$status_text = 'You need to build search index';
			}

			$build_start_button_text = 'Build Search Index';
		}

		$build_style = $hide;
		$build_working_now_style = $hide;
		$build_total = 'starting...';
		$build_processed = '0';
		$build_errors = '0';
		$build_errors_style = $hide;

		include __DIR__ . DIRECTORY_SEPARATOR . 'AdminPage_View.php';
	}



	static public function tr_textbox_api_key( $d ) {
		if ( !isset( $d['id'] ) ) {
			$d['id'] = Util::config_key_to_name( $d['key'] );
		}
		if ( !isset( $d['value'] ) ) {
			$d['value'] = Util::config_by_key( $d['key'] );
		}

		echo '<tr id="tr_' . $d['id'] . '">';
		AdminUi::th( array( 'id' => $d['id'], 'label' => $d['name'] ) );
		echo '<td>';

		echo '<input name="' . esc_attr( $d['id'] ) . '" id="' .
			esc_attr( $d['id'] ) . '" class="regular-text" value="' .
			esc_attr( $d['value'] ) . '">';
		?>
		<button id="wowfts__obtain_api_key" class="button">Obtain API Key</button>
		<div id="wowfts__obtain_api_key_form"
			style="display: none; padding: 10px 30px; border: 1px solid #ccc; margin-top: 10px">
			<div>Please specify an email to get requests rate limit notifications</div>
			<div style="margin-top: 10px">
				<strong>Email:</strong>
				<input type="text" id="wowfts__obtain_api_key_email"
					value="<?php echo esc_attr(get_option('admin_email')) ?>" />
				<button class="button" id="wowfts__obtain_api_key2">Obtain</button>
			</div>
		</div>
		<?php

		if ( isset( $d['description'] ) ) {
			AdminUi::description( $d['description'] );
		}
		echo '</td>';
		echo '</tr>';
	}



	static public function settings_action() {
		if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'wow-search' ) ) {
			wp_nonce_ays( 'wow-search' );
			return;
		}
		if ( !current_user_can( 'manage_options') ) {
			wp_nonce_ays( 'wow-search' );
			return;
		}

		$action = $_REQUEST['wow_search_settings_action'];

		if ( $action == 'settings_save' ) {
			AdminPage::settings_save();
		} else if ( $action == 'sitemap_generate' ) {
			$g = new SitemapGenerator();
			$g->start();
			$g->do_step( 3 );
		}
	}



	static private function settings_save() {
		$c = Util::config();

		$engine_original = $c['search_engine'];

		foreach ($_REQUEST as $id => $value ) {
			if ( substr( $id, 0, 8 ) == 'wowfts__' ) {
				if ( $value == '__checkbox_0' ) {
					$value = false;
				}
				if ( $value == '__checkbox_1' ) {
					$value = true;
				}

				$key = explode( '__', substr( $id, 8 ) );
				$key_last = array_pop($key);

				$config_element = &$c;
				foreach ( $key as $key_part ) {
					if ( !isset( $config_element[$key_part] ) ) {
						$config_element[$key_part] = array();
					}

					$config_element = &$config_element[$key_part];
				}

				$config_element[$key_last] = $value;
			}
		}

		update_option( 'wowfts_config', json_encode( $c ) );

		if ( $engine_original != $c['search_engine'] ) {
			$status = Util::status();
			$status['status'] = '';
			Util::status_set( $status );
		}

		wp_redirect( $_SERVER['REQUEST_URI'] . '&message=saved' );
		exit;
	}



	static private function tr_post_type( $d ) {
		echo '<tr>';
		AdminUi::th( array( 'label' => $d['name'] ) );
		echo '<td>';
		AdminUi::fieldset_start( $d['name'] );

		AdminUi::checkbox( array(
			'key' => $d['key'] . '.enable',
			'name' => 'Index for searching' ) );

		AdminUi::fieldset_end();
		echo '</td>';
		echo '</tr>';
	}



	static private function message_saved() {
		if ( !isset( $_REQUEST['message'] ) ) {
			return '';
		}

		return '<div class="updated settings-error notice is-dismissible">' .
			'<p><strong>' .
			'Settings saved.' .
			'</strong></p>' .
			'<button type="button" class="notice-dismiss">' .
			'<span class="screen-reader-text">' .
			'Dismiss this notice.' .
			'</span></button></div>';
	}



	static public function wp_ajax_wow_search_build() {
		if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'wow_search' ) ) {
			wp_nonce_ays( 'wow_search' );
			exit;
		}
		if ( !current_user_can( 'manage_options') ) {
			wp_nonce_ays( 'wow_search' );
			exit;
		}

		$secs_to_execute = 2;
		$time_end = time() + $secs_to_execute;
		$status = Util::status();

		if ( isset( $_REQUEST['wow_search_action'] ) ) {
			$action = $_REQUEST['wow_search_action'];
			if ( $action == 'start' ) {
				$status = array(
					'version' => '1.0',

					'errors_count' => 0,
					'last_processed_description' => '',
					'status' => 'working.',

					// posts status
					'posts' => array(
						'all' => 0,
						'processed' => 0,
						'last_processed_id' => 0
					)
				);

				Util::status_set($status);
			}
		}

		AdminPageAjaxWorker::execute( $time_end, $status );
		exit;
	}
}
