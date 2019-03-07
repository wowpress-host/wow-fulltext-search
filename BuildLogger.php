<?php

namespace WowFullTextSearch;

class BuildLogger {
	public $notices;



	public function log( $post_id, $message ) {
		$this->notices[] = array(
			'post_id' => $post_id,
			'message' => $message
		);
	}
}
