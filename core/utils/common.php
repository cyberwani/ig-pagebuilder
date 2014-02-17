<?php
/**
 * IG Pagebuilder Common functions
 *
 * Common functions (pure data, don't relate to IGPB shortcodes)
 *
 * @author		InnoGears Team <support@www.innogears.com>
 * @package		IGPGBLDR
 * @version		$Id$
 */

// Define absolute path of plugin
define( 'IG_PB_PATH', plugin_dir_path( IG_PB_FILE ) );

// Define absolute path of shortcodes folder
define( 'IG_PB_LAYOUT_PATH', plugin_dir_path( IG_PB_FILE ) . 'core/shortcode/layout' );
define( 'IG_PB_ELEMENT_PATH', plugin_dir_path( IG_PB_FILE ) . 'shortcodes' );

// Define premade layout folder
define( 'IG_PB_PREMADE_LAYOUT', plugin_dir_path( IG_PB_FILE ) . 'templates/pre-made-layout' );
define( 'IG_PB_PREMADE_LAYOUT_URI', plugin_dir_url( IG_PB_FILE ) . 'templates/pre-made-layout' );

// Define absolute path of templates folder
define( 'IG_PB_TPL_PATH', plugin_dir_path( IG_PB_FILE ) . 'templates' );

// Define plugin uri
define( 'IG_PB_URI', plugin_dir_url( IG_PB_FILE ) );

// Define plugin domain
define( 'IGPBL', 'ig-pb' );

// Define nonce ID
define( 'IGNONCE', 'ig_nonce_check' );

// Delete meta keys
function ig_pb_delete_meta_key( $keys, $post_id = null ) {
	if ( empty ( $keys ) || ! count( $keys ) ) {
		return false;
	}
	global $wpdb;
	$keys  = implode( ', ', $keys );
	$extra = ! empty ( $post_id ) ? 'post_id = ' . mysql_real_escape_string( $post_id ) . ' AND' : '';
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE $extra meta_key IN (%s)", $keys ) );
}

// Remove cache folder
function ig_pb_remove_cache_folder() {
	$cache_dir = WP_CONTENT_DIR . '/uploads' . '/igcache/pagebuilder';
	ig_pb_rrmdir( $cache_dir );
}

/**
 * Remove directory
 * @param type $dir
 */
function ig_pb_rrmdir( $dir ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( $object != '.' && $object != '..' ) {
				if ( filetype( $dir.'/'.$object ) == 'dir' )
					ig_pb_rrmdir( $dir.'/'.$object );
				else unlink( $dir.'/'.$object );
			}
		}
		reset( $objects );
		rmdir( $dir );
	}
}

/**
 * Generate random string
 * @param type $length
 * @param type $is_lower_no_number
 * @return string
 */
function ig_pb_generate_random_string( $length = 6, $is_lower_no_number = false ) {
	if ( ! $is_lower_no_number ) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	} else {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
	}

	$randomString = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$randomString .= $characters[rand( 0, strlen( $characters ) - 1 )];
	}
	return $randomString;
}

/**
 * remove ' and " from string
 * @param type $str
 * @return type
 */
function ig_pb_remove_quotes( $str ) {
	$str    = stripslashes( $str );
	$result = preg_replace( "/[\'\"]+/", '', $str );
	return $result;
}

/**
 * Slice to get nth-child first word
 * @param type $content
 */
function ig_pb_slice_content( $content ) {
	$content = urldecode( $content );
	$arr     = explode( ' ', $content );
	$arr     = array_slice( $arr, 0, 10 );
	return implode( ' ', $arr );
}

/**
 * Trim content to : $limit_count $limit_type ( 5 words, 20 characters ... )
 * @param type $content
 * @param type $limit_count
 * @param type $limit_type
 */
function ig_pb_trim_content( $content, $limit_count, $limit_type ) {
	if ( empty( $limit_count ) )
		return $content;
	$content = trim( $content );
	if ( $limit_type == 'words' ) {
		$content = implode( ' ', array_slice( explode( ' ', $content ), 0, intval( $limit_count ) ) );
	} else if ( $limit_type == 'characters' ) {
		$content = substr( $content, 0, intval( $limit_count ) );
	}
	return balanceTags( $content );
}

/**
 * Generate random lorem ipsum text
 *
 * @param type $word_count
 * @return type
 */
function ig_pb_lorem_text( $word_count = 50, $lorem_start = false ){
	$words  = array( 'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'curabitur', 'vel', 'hendrerit', 'libero', 'eleifend', 'blandit', 'nunc', 'ornare', 'odio', 'ut', 'orci', 'gravida', 'imperdiet', 'nullam', 'purus', 'lacinia', 'a', 'pretium', 'quis', 'congue', 'praesent', 'sagittis', 'laoreet', 'auctor', 'mauris', 'non', 'velit', 'eros', 'dictum', 'proin', 'accumsan', 'sapien', 'nec', 'massa', 'volutpat', 'venenatis', 'sed', 'eu', 'molestie', 'lacus', 'quisque', 'porttitor', 'ligula', 'dui', 'mollis', 'tempus', 'at', 'magna', 'vestibulum', 'turpis', 'ac', 'diam', 'tincidunt', 'id', 'condimentum', 'enim', 'sodales', 'in', 'hac', 'habitasse', 'platea', 'dictumst', 'aenean', 'neque', 'fusce', 'augue', 'leo', 'eget', 'semper', 'mattis', 'tortor', 'scelerisque', 'nulla', 'interdum', 'tellus', 'malesuada', 'rhoncus', 'porta', 'sem', 'aliquet', 'et', 'nam', 'suspendisse', 'potenti', 'vivamus', 'luctus', 'fringilla', 'erat', 'donec', 'justo', 'vehicula', 'ultricies', 'varius', 'ante', 'primis', 'faucibus', 'ultrices', 'posuere', 'cubilia', 'curae', 'etiam', 'cursus', 'aliquam', 'quam', 'dapibus', 'nisl', 'feugiat', 'egestas', 'class', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora', 'torquent', 'per', 'conubia', 'nostra', 'inceptos', 'himenaeos', 'phasellus', 'nibh', 'pulvinar', 'vitae', 'urna', 'iaculis', 'lobortis', 'nisi', 'viverra', 'arcu', 'morbi', 'pellentesque', 'metus', 'commodo', 'ut', 'facilisis', 'felis', 'tristique', 'ullamcorper', 'placerat', 'aenean', 'convallis', 'sollicitudin', 'integer', 'rutrum', 'duis', 'est', 'etiam', 'bibendum', 'donec', 'pharetra', 'vulputate', 'maecenas', 'mi', 'fermentum', 'consequat', 'suscipit', 'aliquam', 'habitant', 'senectus', 'netus', 'fames', 'quisque', 'euismod', 'curabitur', 'lectus', 'elementum', 'tempor', 'risus', 'cras', );
	$result = array();
	if ( $lorem_start ) {
		$result[]    = 'lorem';
		$word_count -= 1;
	}
	for ( $i = 0; $i < $word_count; $i++ ) {
		$random_index = rand( 0, $word_count );
		$result[]     = $words[$random_index];
	}
	return implode( ' ', $result );
}