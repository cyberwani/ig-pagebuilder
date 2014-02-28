<?php
/**
 *
 * Uninstalling IG Pagebuilder: deletes post metas & options
 *
 * @author		InnoGears Team <support@www.innogears.com>
 * @package		IGPGBLDR
 * @version		$Id$
 */

// delete all other providers
$providers = get_transient( '_ig_pb_providers' );
if ( $providers ) {
	$providers    = unserialize( $providers );
	$list_plugins = array();
	foreach ( $providers as $provider ) {
		if ( isset ( $provider['file'] ) ) {
			$list_plugins[] = $provider['file'];
		}
	}
	delete_plugins( $list_plugins );
}

//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

include_once 'core/utils/common.php';

// remove cache folder
IG_Pb_Utils_Common::remove_cache_folder();

// delele meta key
IG_Pb_Utils_Common::delete_meta_key( array( '_ig_page_builder_content', '_ig_html_content', '_ig_page_active_tab', '_ig_post_view_count', '_ig_deactivate_pb' ) );