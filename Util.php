<?php

namespace WowFullTextSearch;

class Util {
	static public function config() {
		$v = get_option( 'wowfts_config' );

		if ( !empty( $v ) ) {
			try {
				return json_decode( $v, true );
			} catch ( \Exception $error ) {
			}
		}

		return array(
			'version' => '1.0',
			'search_engine' => 'wowsearch',
			'elasticsearch' => array(
				'host_port' => 'localhost:9200',
				'index' => 'wordpress'
			),
			'wowsearch' => array(
				'api_key' => ''
			),
			'post_types' => array(
				'post' => array(
					'enable' => true
				),
				'page' => array(
					'enable' => true
				),
				'product' => array(
					'enable' => true
				)
			)
		);
	}



	static public function config_key_to_name( $key ) {
		return 'wowfts__' . str_replace( '.', '__', $key );
	}



	static public function config_by_key( $key ) {
		$c = Util::config();

		$key_parts = explode( '.', $key );
		$key_last = array_pop($key_parts);

		$config_element = &$c;
		foreach ( $key_parts as $key_part ) {
			if ( !isset( $config_element[$key_part] ) ) {
				return null;
			}

			$config_element = &$config_element[$key_part];
		}

		if ( !isset( $config_element[$key_last] ) ) {
			return null;
		}

		return $config_element[$key_last];
	}



	static public function engine( $c ) {
		if ( $c['search_engine'] == 'elasticsearch' ) {
			return new EngineElasticSearch( $c );
		}

		return new EngineWowSearch( $c );
	}



	static public function status() {
		$v = get_option( 'wowfts_status' );

		if ( !empty( $v ) ) {
			try {
				return json_decode( $v, true );
			} catch ( \Exception $error ) {
			}
		}

		return array(
			'version' => '1.0',
			'processed' => array(
				'post_id' => 0
			),
			'status' => ''
		);
	}



	static public function status_set( $s ) {
		update_option( 'wowfts_status', json_encode( $s ) );
	}



	static public function is_search_active() {
		$status = self::status();
		return ( !empty( $status['status'] ) &&
			substr( $status['status'], 0, 5 ) == 'done.' &&
			$status['status'] != 'done.upgrade_required'
		);
	}
}
