<?php

namespace WowFullTextSearch;

AdminUi::tr_radiogroup( 'Search Engine', array(
	'key' => 'search_engine',
	'values' => array(
		array(
			'value' => 'wowsearch',
			'name_html' => 'Managed <a target="_blank" href="https://search.wowpress.host/">See details</a>'
		),
		array(
			'value' => 'elasticsearch',
			'name' => 'Self-hosted ElasticSearch server'
		)
	),
	'description' => 'Fulltext search engine to use. You may configure and set up own self-hosted service or use managed onw provided by Wow Search.'
) );

self::tr_textbox_api_key( array(
	'key' => 'wowsearch.api_key',
	'name' => 'WowSearch API key'
) );


AdminUi::tr_textbox( array(
	'key' => 'elasticsearch.host_port',
	'name' => 'ElasticSearch host:port',
	'description' => 'Network address of your ElasticSearch instance in a hostname:port format'
) );

AdminUi::tr_textbox( array(
	'key' => 'elasticsearch.index',
	'name' => 'ElasticSearch index'
) );

foreach ( $post_types as $post_type => $o ):
	AdminPage::tr_post_type( array(
		'key' => 'post_types.' . $post_type,
		'name' => $o->labels->name
	) );
endforeach;
