<?php

namespace WowFullTextSearch;

class BuildProcessor {
	public $last_processed_description = '';
	public $errors_count = 0;

	private $config;
	private $engine;



	public function __construct( $config, $log, $engine ) {
		$this->config = $config;
		$this->log = $log;
		$this->engine = $engine;
	}



	public function posts_count() {
		$post_type_formats = array();
		$post_type_values = array();

		foreach ( $this->config['post_types'] as $post_type => $c ) {
			if ( isset( $c['enable'] ) && $c['enable'] ) {
				$post_type_formats[] = '%s';
				$post_type_values[] = $post_type;
			}
		}

		$post_type_formats_joined = implode( ', ', $post_type_formats);

		global $wpdb;
		$sql = $wpdb->prepare( "SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type IN ($post_type_formats_joined) AND
				post_status = 'publish'",
			$post_type_values
		);
		return $wpdb->get_var( $sql );
	}



	public function get_post_after( $post_id ) {
		$post_type_formats = array();
		$values = array();
		foreach ( $this->config['post_types'] as $post_type => $c ) {
			if ( isset( $c['enable'] ) && $c['enable'] ) {
				$post_type_formats[] = '%s';
				$values[] = $post_type;
			}
		}

		$post_type_formats_joined = implode( ', ', $post_type_formats);
		$values[] = $post_id;

		global $wpdb;

		$sql = $wpdb->prepare( "SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type IN ($post_type_formats_joined) AND ID > %d AND
				post_status = 'publish'
			ORDER BY ID
			LIMIT 1", $values );

		return $wpdb->get_var( $sql );
	}



	public function process_post( $post_id ) {
		$this->last_processed_description = '';

		$post = get_post( $post_id );
		$this->engine->add_post( $post );

		$url = get_permalink( $post );
		$this->last_processed_description = $post_id . ' ' .
			str_replace( home_url(), '', $url );
		return;
	}



	private function get_attached_filename( $post ) {
		$filename = get_attached_file( $post->ID );

		if ( !empty( $filename ) && file_exists( $filename ) ) {
			return $filename;
		}

		// if not present - try to find by guid
		if ( empty( $post->guid ) ) {
			$this->errors_count++;
			$this->log->log( $post->ID, 'Attachment has empty GUID' );

			return null;
		}
		$baseurl = $this->wp_upload_dir['baseurl'];

		if ( Util::starts_with( $post->guid, $baseurl ) ) {
			$guid_filename_postfix = substr( $post->guid,
				strlen( $baseurl ) + 1 );
		} else {
			// try default uploads keyword
			$pos = strpos( $post->guid, '/uploads/' );

			if ( $pos === FALSE ) {
				$this->errors_count++;
				$this->log->log( $post->ID, "Attachment GUID doesnt allow to restore filename " . $post->guid );
				return null;
			}

			$guid_filename_postfix = substr( $post->guid, $pos + 9 );
		}

		$guid_filename =  $this->wp_upload_dir['basedir'] .
			DIRECTORY_SEPARATOR . $guid_filename_postfix;

		if ( !file_exists( $guid_filename ) ) {
			$log_postfix = ( $guid_filename == $filename ? '' :
				" and '$guid_filename'" );

			$this->errors_count++;
			$this->log->log( $post->ID,
				"Image file referenced by attachment doesn't exists. Tried '$filename'$log_postfix" );
			$this->last_processed_description = $post->guid;
			return null;
		}

		$this->errors_count++;
		$this->log->log( $post->ID,
			"Restored image file from guid: '$guid_filename'" );
		update_post_meta( $post->ID, '_wp_attached_file',
			$guid_filename_postfix );
		return $guid_filename;
	}



	private function maybe_delete_post( $post, $filename ) {
		if ( is_null( $filename ) || !file_exists( $filename ) ) {
			if ( $this->c_posts_delete_with_missing_images ) {
				wp_delete_post( $post->ID, true );
				$this->errors_count++;
				$this->log->log( $post->ID,
					'Attachment deleted because of missing image file.' .
					( is_null( $filename ) ? '' : "'$filename'" ) );

				return true;
			}
		}

		if ( !empty( $this->c_posts_delete_duplicate_url ) ) {
			$p = new ProcessPostDuplicateUrl( $post, $filename,
				$this->log, $this->c_posts_delete_duplicate_url );
			if ( $p->maybe_delete_post() ) {
				$this->errors_count++;
				return true;
			}
		}

		return false;
	}



	private function maybe_update_guid( $post, $filename ) {
		if ( empty( $this->c_guid ) ) {
			return;
		}

		$required_guid = wp_get_attachment_url( $post->ID );
		if ( $required_guid == $post->guid ) {
			return;
		}

		if ( $this->c_guid == 'log' ) {
			$this->errors_count++;
			$this->log->log( $post->ID,
				"Post GUID mismatch: Actual value '{$post->guid}', but normalized is '$required_guid'" );
			return;
		}

		if ( $this->c_guid != 'fix' ) {
			return;
		}


		// find unique guid
		global $wpdb;


		$new_guid = '';
		for ( $n = 0; $n < 100; $n++ ) {
			$new_guid = $required_guid . ( $n <= 0 ? '' : '-' . $n );

			$sql = $wpdb->prepare( "SELECT ID
				FROM {$wpdb->posts}
				WHERE guid = %s AND ID != %d
				LIMIT 1", $new_guid, $post->ID );

			$present = $wpdb->get_var( $sql );
			if ( !empty( $wpdb->last_error ) ) {
				throw new \Exception( $wpdb->last_error );
			}
			if ( is_null( $present ) ) {
				break;
			}

			$required_guid_postfix++;
		}

		if ( $n >= 100 ) {
			$this->log->log( $post->ID,
				"Tried to update post GUID but failed to generate unique value based on '$required_guid' string" );
			return;
		}

		$old_guid = $post->guid;
		if ( $old_guid == $new_guid ) {
			return;
		}

		// wp_update_post won't change guid (and thats correct)
		global $wpdb;
		$wpdb->update( $wpdb->posts,
			array( 'guid' => $new_guid ),
			array( 'id' => $post->ID ) );
		if ( !empty( $wpdb->last_error ) ) {
			throw new \Exception( $wpdb->last_error );
		}

		$this->errors_count++;
		$this->log->log( $post->ID,
			"Post GUID changed from '$old_guid' to '$new_guid'" );
	}



	private function maybe_regenerate_metadata( $post_id, $filename ) {
		if ( !$this->c_regenerate_metadata ) {
			return wp_get_attachment_metadata( $post_id );
		}

		// often left with history data
		delete_post_meta( $post_id, '_wp_attachment_backup_sizes' );

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$meta = wp_generate_attachment_metadata( $post_id, $filename );
		wp_update_attachment_metadata( $post_id, $meta );

		if ( $this->log->verbose ) {
			$count = 0;
			if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
				$count = count( $meta['sizes'] );
			}

			$filename_for_log = str_replace( ABSPATH, '', $filename );

			$this->log->log( $post_id,
				'Regenerated attachment metadata. ' .
				$count . ' thumbnails generated for ' . $filename_for_log );
		}

		return $meta;
	}
}
