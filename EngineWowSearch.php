<?php

namespace WowFullTextSearch;

class EngineWowSearch {
	private $config;



	public function __construct( $config ) {
		$this->config = $config;
	}



	public function initialize() {
		if ( empty( $this->config['wowsearch']['api_key'] ) ) {
			throw new \Exception( 'API Key not specified, please obtain one by clicking "Obtain API Key" button' );
		}

		$r = $this->post( 'documents/initialize' );

		if ( isset( $r['error_message'] ) ) {
			throw new \Exception('Initialize failed: ' . $r['error_message'] );
		}

		if ( !isset( $r['success'] ) ) {
			throw new \Exception('unexpected response to initialize request: ' .
				json_encode( $r ) );
		}
	}



	public function add_post( $post ) {
		return $this->add( $post->ID, array(
			'author' => $post->post_author,
			'content' => $post->post_content,
			'excerpt' => $post->post_excerpt,
			'title' => $post->post_title,
			'url' => get_permalink( $post )
		) );
	}



	public function add( $id, $data ) {
		$r = $this->post( 'documents/set', array(
			'id' => $id,
			'content' => $data
		) );

		if ( !isset( $r['success'] ) ) {
			throw new \Exception('unexpected response to document set request: ' .
				json_encode( $r ) );
		}
	}



	public function delete( $id ) {
		$r = $this->post( 'documents/delete', array(
			'id' => $id
		) );

		if ( !isset( $r['success'] ) ) {
			throw new \Exception('unexpected response to document delete request: ' .
				json_encode( $r ) );
		}
	}



	public function search( $data ) {
		$r = $this->post( 'documents/search', $data );

		if ( !isset( $r['total'] ) ) {
			throw new \Exception( 'unexpected response to document search request: ' .
				json_encode( $r ) );
		}

		return $r;
	}



	private function post( $uri, $data = array() ) {
		$data['api_key'] = $this->config['wowsearch']['api_key'];

		$response = wp_remote_request( 'http://search.wowpress.host/api/' . $uri, array(
			'method' => 'POST',
			'body' => json_encode( $data ),
			'timeout' => 15000,
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		) );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$v = @json_decode( $body, true );
		if ( !$v ) {
			throw new \Exception( 'non JSON: ' . $body );
		}

		if ( isset( $r['error_message'] ) ) {
			throw new \Exception('Request failed: ' . $r['error_message'] );
		}

		return $v;
	}



	private function obtain_api_key( $data ) {
		$response = wp_remote_request( 'http://search.wowpress.host/api/signup', array(
			'method' => 'POST',
			'body' => json_encode( $data ),
			'timeout' => 15000,
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		) );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$v = @json_decode( $body, true );
		if ( !$v ) {
			throw new \Exception( 'non JSON: ' . $body );
		}

		return $v;
	}
}
