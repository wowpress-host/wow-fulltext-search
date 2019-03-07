<?php

namespace WowFullTextSearch;

class EngineElasticSearch {
	private $config;



	public function __construct( $config ) {
		$this->config = $config;
	}



	public function initialize() {
		if ( empty( $this->config['elasticsearch']['host_port'] ) ||
			empty( $this->config['elasticsearch']['index'] ) ) {
			throw new \Exception( 'ElasticSearch options are not configured, please specify those' );
		}
		$server_info = null;
		try {
			$server_info = $this->get_server_info();

		} catch (\Exception $e) {
			throw new \Exception( $this->config['elasticsearch']['host_port'] .
				' is not recognized as ElasticSearch server: ' . $e->getMessage() );
		}

		if ( !isset( $server_info['version'] ) ||
			!isset( $server_info['version']['number'] ) ) {
			throw new \Exception( $this->config['elasticsearch']['host_port'] .
				' did not share version number' );
		}


		//
		// configure index
		//
		$index_data = $this->get( '' );
		if ( isset($index_data[$this->config['elasticsearch']['index']]) ) {
			// index present already
			$delete_response = $this->_delete( '' );
			if ( !isset( $delete_response['acknowledged'] ) ||
				!$delete_response['acknowledged'] ) {
				throw new \Exception('unexpected response to DELETE: ' .
					json_encode( $delete_response ) );
			}

		}

		$settings_response = $this->put( '', array(
			'settings' => array(
				'analysis' => array(
					'analyzer' => array(
						'wow_standard' => array(
							 'type' => 'standard'
						),
						'wow_fulltext' => array(
							 'type' => 'snowball'
						)
					)
				)
			)
		) );

		if ( !isset( $settings_response['acknowledged'] ) ||
				!$settings_response['acknowledged'] ) {
			throw new \Exception('unexpected response to PUT settings: ' .
				json_encode( $settings_response ) );
		}


		$standard = array(
			'type' => 'text',
			'index' => true,
			'analyzer' => 'wow_standard',
			'search_analyzer' => 'wow_standard'
		);
		$fulltext = array(
			'type' => 'text',
			'index' => true,
			'analyzer' => 'wow_fulltext',
			'search_analyzer' => 'wow_fulltext'
		);

		$mapping_response = $this->put( 'post/_mapping', array(
			'post' => array(
				'properties' => array(
					'author' => $standard,
					'content' => $fulltext,
					'excerpt' => $fulltext,
					'title' => $fulltext
				)
			)
		) );

		if ( !isset( $mapping_response['acknowledged'] ) ||
				!$mapping_response['acknowledged'] ) {
			throw new \Exception('unexpected response to PUT mapping: ' .
				json_encode( $mapping_response ) );
		}
	}



	public function add_post( $post ) {
		return $this->add( $post->ID, array(
			'author' => $post->post_author,
			'content' => $post->post_content,
			'excerpt' => $post->post_excerpt,
			'title' => $post->post_title
		) );
	}



	public function add( $id, $data ) {
		$r = $this->put( 'post/' . $id, $data );

		if ( !isset( $r['result'] ) ||
			( $r['result'] != 'created' && $r['result'] != 'updated' ) ) {
			throw new \Exception('unexpected response to document PUT: ' .
				json_encode( $r ) );
		}
	}



	public function delete( $id ) {
		$r = $this->_delete( 'post/' . $id, $data );

		if ( !isset( $r['result'] ) ) {
			throw new \Exception('unexpected response to document DELETE: ' .
				json_encode( $r ) );
		}
	}



	public function search( $data ) {
		$response = wp_remote_request( $this->url( 'post/_search' ), array(
			'method' => 'POST',
			'body' => json_encode( $data ),
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		) );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );
		if ( !isset( $body['hits'] ) ) {
			throw new \Exception( 'hits not returned: ' . json_encode( $body ) );
		}

		if ( isset( $body['hits']['hits'] ) ) {
			for ( $n = 0; $n < count( $body['hits']['hits'] ); $n++ ) {
				$body['hits']['hits'][$n]['id'] = $body['hits']['hits'][$n]['_id'];
			}
		}

		return $body['hits'];
	}



	private function url( $uri ) {
		return 'http://' . $this->config['elasticsearch']['host_port'] . '/' .
			$this->config['elasticsearch']['index'] . '/' . $uri;
	}



	private function get_server_info() {
		$response = wp_remote_get( 'http://' . $this->config['elasticsearch']['host_port'] );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}



	private function get( $uri ) {
		$response = wp_remote_get( $url = $this->url( $uri ) );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}



	private function put( $uri, $data ) {
		$response = wp_remote_request( $url = $this->url( $uri ), array(
			'method' => 'PUT',
			'body' => json_encode( $data ),
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		) );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}



	private function _delete( $uri ) {
		$response = wp_remote_request( $this->url( $uri ), array(
			'method' => 'DELETE'
		) );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		$r = json_decode( $body, true );
		return $r;
		if ( isset( $r['acknowledged'] ) && $r['acknowledged'] ) {
			return;
		}
	}
}
