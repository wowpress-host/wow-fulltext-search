<?php

namespace WowFullTextSearch;

class WpQueryHook {
	private $last_response = null;
	private $modify_request = false;



	static public function init() {
		if ( !Util::is_search_active() ) {
			return;
		}

		if ( is_admin() ) {
			// wp-admin has own logic for searching - dont affect it
			return;
		}

		$o = new WpQueryHook();
		add_action( 'pre_get_posts', array( $o, 'pre_get_posts' ) );
		add_filter( 'posts_search', array( $o, 'posts_search' ), 10, 2 );

		add_filter( 'found_posts', array( $o, 'found_posts' ), 10, 2 );
		add_filter( 'posts_search_orderby', array( $o, 'posts_search_orderby' ), 10, 2 );
		add_filter( 'posts_orderby', array( $o, 'posts_orderby' ), 10, 2 );
		add_filter( 'post_limits', array( $o, 'post_limits' ), 10, 2 );
		add_filter( 'posts_results', array( $o, 'posts_results' ), 10, 2 );

		// debug
		// add_filter( 'posts_request', array( $this, 'posts_request' ), 10, 2 );
	}



	public function pre_get_posts() {
		$this->last_response = null;
		$this->modify_request = false;
	}



	public function posts_search( $sql, $wp_query ) {
		global $wpdb;

		$modify_request = $wp_query->is_search();
		$modify_request = apply_filters( 'wow_search_modify_request',
			$modify_request, $wp_query, $sql );
		if ( !$modify_request ) {
			return $sql;
		}


		// do search request
		$s = $wp_query->get( 's' );
		$paged = $wp_query->get( 'paged' );
		if ( !$paged || $paged <= 0) {
			$paged = 1;
		}
		$posts_per_page = $wp_query->get( 'posts_per_page' );
		if ( !$posts_per_page || $posts_per_page <= 0) {
			$posts_per_page = 10;
		}

		$from = $posts_per_page * ( $paged - 1 );

		$search_request = array(
			'query' => array(
				'query_string' => array(
					'query' => $s,
				)
			),
			'from' => $from,
			'size' => $posts_per_page,
			'_source' => false,
		);

		$search_request = apply_filters( 'wow_search_search_request',
			$search_request, $wp_query, $sql );

		$e = Util::engine( Util::config() );
		$response = $e->search( $search_request );

		if ( isset( $response['error_update_status'] ) ) {
			$status = Util::status();
			$status['status'] = $response['error_update_status'];
			Util::status_set( $status );
		}


		// parse response
		$this->last_response = $response;
		$this->modify_request = true;

		if ( $response['total'] <= 0 ) {
			return "AND {$wpdb->posts}.ID = 0";
		}

		$formats = array();
		$values = array();
		foreach ( $response['hits'] as $i ) {
			$formats[] = '%d';
			$values[] = $i['id'];
		}

		$formats_joined = implode( ', ', $formats);

		$sql = $wpdb->prepare( "AND {$wpdb->posts}.ID IN ($formats_joined)",
			$values );
		$sql = apply_filters( 'wow_search_posts_search', $sql, $wp_query,
			$response );

		// search where clause
		return $sql;
	}



	public function posts_search_orderby( $sql, $wp_query ) {
		if ( !$this->modify_request ) {
			return $sql;
		}

		// orderby clause - title priority
		return '';
	}



	public function posts_orderby( $sql, $wp_query ) {
		if ( !$this->modify_request ) {
			return $sql;
		}

		// orderby clause - what query wanted
		return '';
	}



	public function post_limits( $sql, $wp_query ) {
		if ( !$this->modify_request ) {
			return $sql;
		}

		// limits clause
		return '';
	}



	public function posts_request( $sql, $wp_query ) {
		var_dump($sql);
		return $sql;
	}



	/**
	 * Should return total number of entries found from search engine
	 */
	public function found_posts( $count, $wp_query ) {
		if ( is_null( $this->last_response ) ) {
			return $count;
		}

		$count = $this->last_response['total'];
		$posts_per_page = $wp_query->get( 'posts_per_page' );
		if ( !$posts_per_page || $posts_per_page <= 0) {
			$posts_per_page = 10;
		}

		$wp_query->max_num_pages = ceil( $count / $posts_per_page );

		return $count;
	}



	/**
	 * Reorder results
	 */
	public function posts_results( $posts, $wp_query ) {
		if ( is_null( $this->last_response ) ) {
			return $posts;
		}

		$pos_by_id = array();
		for ( $n = 0; $n < count( $posts ); $n++ ) {
			$post = $posts[$n];
			$pos_by_id[$post->ID] = $n;
		}

		for ( $i = count( $this->last_response['hits'] ) - 1; $i >= 0; $i-- ) {
			$id = $this->last_response['hits'][$i]['id'];

			for ( $n = 0; $n < count( $posts ); $n++ ) {
				$post = $posts[$n];
				if ( $post->ID == $id ) {
					unset( $posts[$n] );
					array_unshift( $posts, $post );
					break;
				}
			}
		}

		return $posts;
	}
}
