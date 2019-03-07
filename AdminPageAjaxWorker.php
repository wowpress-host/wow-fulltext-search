<?php

namespace WowFullTextSearch;

class AdminPageAjaxWorker {
	static public function execute( $time_end, $status ) {
		//
		// init processors
		//
		$log = new BuildLogger();
		$config = Util::config();
		$engine = Util::engine( $config );
		$process_post = new BuildProcessor( $config, $log, $engine );

		if ($status['posts']['all'] == 0) {
			$status['posts']['all'] = $process_post->posts_count();
		}

		//
		// run processors
		//
		$last_processed_description = '';

		try {
			if ( $status['status'] == 'working.' ) {
				try {
					$engine->initialize();
					$status['status'] = 'working.posts';
				} catch ( \Exception $e ) {
					$status['status'] = 'error.configuration';
					$log->log( null, $e->getMessage() );
				}
			}
			if ( $status['status'] == 'working.posts' ) {
				for (;;) {
					$post_id = $process_post->get_post_after(
						$status['posts']['last_processed_id'] );
					$status['posts']['processed']++;
					if ( is_null( $post_id ) ) {
						$status['status'] = 'done.';
						$status['posts']['processed'] = $status['posts']['all'];
						break;
					}

					$process_post->process_post( $post_id );
					$status['posts']['last_processed_id'] = $post_id;

					if ( time() >= $time_end ) {
						break;
					}
				}

				$last_processed_description = $process_post->last_processed_description;
			}

			$status['errors_count'] += $process_post->errors_count;
			Util::status_set($status);
		} catch ( \Exception $e ) {
			die( $e->getMessage() );
		}

		echo json_encode(array(
			'posts_all' => $status['posts']['all'],
			'posts_processed' => $status['posts']['processed'],
			'errors_count' => $status['errors_count'],
			'last_processed_description' => $last_processed_description,
			'status' => $status['status'],
			'new_notices' => $log->notices
		));
	}
}
