<?php

namespace WowFullTextSearch;

class Data {
	static private function sitemap_table() {
		global $wpdb;
		return $wpdb->prefix . 'wow_sitemap';
	}



	static public function sitemap_create() {
		$q = 'CREATE TABLE IF NOT EXISTS `' . Data::sitemap_table() . '` (
			`id` int auto_increment,
			`url` varchar(200),
			`priority` char(1),
			`frequency` char(1),
			`sitemap_postfix` varchar(5),
			`lastmod` datetime,
			`type` char(1),
			`related_id` int,
			PRIMARY KEY `wow_sitemap_id` (`id`),
			UNIQUE KEY `wow_sitemap_url` (`url`),
			KEY `wow_sitemap_type_related_id` (`type`, `related_id`),
			KEY `wow_sitemap_sitemap_postfix` (`sitemap_postfix`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8';

		global $wpdb;
		return $wpdb->query( $q );
	}



	static public function sitemap_delete() {
		global $wpdb;
		return $wpdb->query( 'DELETE FROM `' . Data::sitemap_table() . '`' );
	}



	static public function sitemap_drop() {
		global $wpdb;
		return $wpdb->query( 'DROP TABLE `' . Data::sitemap_table() . '`' );
	}



	static public function sitemap_add( $d ) {
		$sitemap = Data::sitemap_table();

		$sql = "
			INSERT INTO $sitemap
			(url, priority, frequency, sitemap_postfix, lastmod, type, related_id)
			VALUES
			(%s, %s, %s, %s, %s, %s, %d)
			ON DUPLICATE KEY UPDATE
			priority = %s, frequency = %s, sitemap_postfix = %s, lastmod = %s,
			type = %s, related_id = %s";

		global $wpdb;
		$wpdb->query( $wpdb->prepare( $sql,
			$d['url'], $d['priority'], $d['frequency'], $d['sitemap_postfix'],
			$d['lastmod'], $d['type'], $d['related_id'],
			$d['priority'], $d['frequency'], $d['sitemap_postfix'], $d['lastmod'],
			$d['type'], $d['related_id'] ) );
	}



	static public function sitemap_count() {
		global $wpdb;
		$sitemap = Data::sitemap_table();

		$q = "
			SELECT COUNT(id)
			FROM {$sitemap}";

		return $wpdb->get_var( $q );
	}



	static public function sitemap_postfixes() {
		global $wpdb;
		$sitemap = Data::sitemap_table();

		$q = "
			SELECT sitemap_postfix, MAX(lastmod) AS lastmod
			FROM {$sitemap}
			GROUP BY sitemap_postfix
			ORDER BY sitemap_postfix";

		return $wpdb->get_results( $q );
	}



	static public function sitemap_next_postfix( $postfix ) {
		global $wpdb;
		$sitemap = Data::sitemap_table();

		$q = $wpdb->prepare( "
			SELECT sitemap_postfix
			FROM {$sitemap}
			WHERE sitemap_postfix > %s
			ORDER BY sitemap_postfix
			LIMIT 1", $postfix );

		return $wpdb->get_var( $q );
	}



	static public function sitemap_all() {
		global $wpdb;
		$sitemap = Data::sitemap_table();

		$q = "
			SELECT *
			FROM {$sitemap}
			ORDER BY id";

		return $wpdb->get_results( $q );
	}



	static public function sitemap_by_postfix( $postfix ) {
		global $wpdb;
		$sitemap = Data::sitemap_table();

		$q = $wpdb->prepare( "
			SELECT *
			FROM {$sitemap}
			WHERE sitemap_postfix = %s
			ORDER BY id", $postfix );

		return $wpdb->get_results( $q );
	}



	static public function post_types() {
		$r = get_post_types( array( 'public' => true ), 'objects' );
		unset( $r['attachment'] );
		$r = apply_filters( 'wow_sitemap_post_types', $r );

		return $r;
	}



	static public function taxonomies() {
		$r = get_taxonomies(
			array( 'public' => true, 'show_ui' => true ), 'objects' );
		$r = apply_filters( 'wow_sitemap_taxonomies', $r );

		return $r;
	}


	static public function term_taxonomy_max_id() {
		global $wpdb;

		$q = "
			SELECT MAX(term_taxonomy_id)
			FROM {$wpdb->term_taxonomy}";

		return $wpdb->get_var( $q );
	}



	static public function term_taxonomies_after( $after_id, $taxonomies ) {
		if ( empty( $taxonomies ) ) {
			return array();
		}

		global $wpdb;
		$taxonomies_escaped = array();
		foreach ( $taxonomies as $t ) {
			$taxonomies_escaped[] = $wpdb->prepare( '%s', $t );
		}
		$taxonomies_escaped = implode(',', $taxonomies_escaped);

		$q = $wpdb->prepare( "
			SELECT term_taxonomy_id, term_id, taxonomy
			FROM {$wpdb->term_taxonomy}
			WHERE term_taxonomy_id > %d AND taxonomy IN ({$taxonomies_escaped})
			ORDER BY term_taxonomy_id
			LIMIT 50",
			$after_id );

		return $wpdb->get_results( $q );
	}



	static public function term_taxonomy_max_post_modified( $term_taxonomy_id ) {
		global $wpdb;

		$q = "
			SELECT MAX(post_modified)
			FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr
					ON tr.object_id = p.ID AND tr.term_taxonomy_id = %d
			WHERE p.post_status = 'publish' AND p.post_password = ''";

		return $wpdb->get_var( $wpdb->prepare( $q, $term_taxonomy_id ) );
	}




	static public function post_max_modified() {
		global $wpdb;

		$q = "
			SELECT MAX(post_modified)
			FROM {$wpdb->posts} AS p
			WHERE p.post_status = 'publish' AND p.post_password = ''";

		return $wpdb->get_var( $q );
	}



	static public function posts_range_date() {
		global $wpdb;

		$q = "
			SELECT MIN(post_date) AS min_date, MAX(post_date) as max_date
			FROM {$wpdb->posts} AS p
			WHERE p.post_status = 'publish' AND p.post_password = ''";

		return $wpdb->get_results( $q );
	}



	static public function post_archive_max_post_modified(
			$min_date, $max_date ) {
		global $wpdb;

		$q = $wpdb->prepare( "
			SELECT MAX(post_modified)
			FROM {$wpdb->posts} AS p
			WHERE p.post_status = 'publish' AND p.post_password = ''
				AND post_date BETWEEN %s AND %s",
			$min_date, $max_date );

		return $wpdb->get_var( $q );

	}

	static public function post_max_id() {
		global $wpdb;

		$q = "
			SELECT MAX(ID)
			FROM {$wpdb->posts}";

		return $wpdb->get_var( $q );
	}



	static public function posts_after( $after_id, $post_types ) {
		global $wpdb;

		if ( empty( $post_types ) ) {
			return array();
		}

		global $wpdb;
		$post_types_escaped = array();
		foreach ( $post_types as $t ) {
			$post_types_escaped[] = $wpdb->prepare( '%s', $t );
		}
		$post_types_escaped = implode(',', $post_types_escaped);

		$q = $wpdb->prepare( "
			SELECT
				ID, post_modified, post_type
				FROM {$wpdb->posts}
				WHERE post_status = 'publish' AND post_password = ''
					AND ID > %d AND post_type IN ($post_types_escaped)
				ORDER BY id
				LIMIT 100",
			$after_id );

		return $wpdb->get_results( $q );
	}



	static public function users_after( $after_id ) {
		global $wpdb;

		$q = $wpdb->prepare( "
			SELECT ID, user_nicename
			FROM {$wpdb->users}
			WHERE ID > %d
			ORDER BY id
			LIMIT 50",
			$after_id );

		return $wpdb->get_results( $q );
	}



	static public function user_max_id() {
		global $wpdb;

		$q = "
			SELECT MAX(ID)
			FROM {$wpdb->users}";

		return $wpdb->get_var( $q );
	}



	static public function user_max_post_modified( $user_id ) {
		global $wpdb;

		$q = "
			SELECT MAX(post_modified)
			FROM {$wpdb->posts} AS p
			WHERE p.post_status = 'publish' AND p.post_password = ''
				AND p.post_author = %d";

		return $wpdb->get_var( $wpdb->prepare( $q, $user_id ) );
	}
}
