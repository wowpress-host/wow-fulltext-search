<?php

namespace WowFullTextSearch;

class SyncronizeHook {
	static public function delete_post( $post_id ) {
		if ( !Util::is_search_active() ) {
			return;
		}

		$post = get_post( $post_id );
		if ( !self::is_post_processed( $post ) ) {
			return;
		}

		$e = Util::engine( Util::config() );
		try {
			$e->delete( $post_id );
		} catch (\Exception $e) {
			error_log( $e->getMessage() );
		}
	}



	static public function on_post_change( $post_id ) {
		if ( !Util::is_search_active() ) {
			return;
		}

		$post = get_post( $post_id );
		if ( !self::is_post_processed( $post ) ) {
			return;
		}

		$e = Util::engine( Util::config() );

		try {
			if ( $post->post_status == 'publish' ) {
				$e->add_post( $post );
			} else {
				$e->delete( $post_id );
			}
		} catch (\Exception $e) {
			error_log( $e->getMessage() );
		}
	}



	static private function is_post_processed( $post ) {
		$post_type = $post->post_type;

		$config = Util::config();
		if ( isset( $config['post_types'] ) &&
			isset( $config['post_types'][$post_type] ) &&
			isset( $config['post_types'][$post_type]['enable'] ) &&
			$config['post_types'][$post_type]['enable'] ) {
			return true;
		}

		return false;
	}
}
