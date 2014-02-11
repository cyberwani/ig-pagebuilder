<?php
/**
 * IG Pagebuilder Activate & Deactivate
 *
 * Show confirmation page before doing deactivation or go back
 *
 * @author		InnoGears Team <support@www.innogears.com>
 * @package		IGPGBLDR
 * @version		$Id$
 */

add_action( 'admin_init', 'ig_pb_activate_plugin' );
// active extracted plugin
function ig_pb_activate_plugin() {
	global $pagenow;
	$providers = ig_default_providers();

	if ( is_plugin_active( 'ig-pagebuilder/ig-pagebuilder.php' ) ) {
		ig_pb_extract_plugins();

		// activate dependency plugins
		foreach ( $providers as $provider ) {
			if ( isset ( $provider['folder'] ) ) {
				$folder = $provider['folder'];
				if ( ! is_plugin_active( $folder . '/main.php' ) ) {
					activate_plugin( $folder . '/main.php' );
					// remove action
					remove_action( 'admin_init', __FUNCTION__ );
				}
			}
		}
	}
}

/**
 * Extract packages of third-party plugins
 */
function ig_pb_extract_plugins() {
	$providers = ig_default_providers();
	WP_Filesystem();
	// extract dependency plugins
	foreach ( $providers as $provider ) {
		if ( isset ( $provider['folder'] ) ) {
			$folder     = $provider['folder'];
			$source_zip = plugin_dir_path( IG_PB_FILE ) . $folder . '.zip';
			if ( ! ( file_exists( $source_zip ) ) ){
				$error = 404;
				return;
			}

			$source_folder = WP_PLUGIN_DIR . "/$folder";
			if ( file_exists( $source_folder ) ) {
				// delete folder
				rrmdir( $source_folder );
				// rename older folder
				//rename( $source_folder, $source_folder . '-old' );
			}
			// extract to plugin folder
			$unzipfile = unzip_file( $source_zip, $source_folder );
			if ( $unzipfile ) {
				$error = 0;
			} else {
				$error = 1;
			}
			// remove zip file
			unlink( $source_zip );
		}
	}
}

/**
 * Get default providers directory
 *
 * @global type $Ig_Sc_Providers
 * @return type
 */
function ig_default_providers() {
	global $Ig_Sc_Providers;
	if ( count( $Ig_Sc_Providers ) == 1 ) {
		return array(
				array(
					'folder' => 'ig-shortcodes-free',
				),
		);
	}
	return $Ig_Sc_Providers;
}

/**
 * Remove directory
 * @param type $dir
 */
function rrmdir( $dir ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( $object != '.' && $object != '..' ) {
				if ( filetype( $dir.'/'.$object ) == 'dir' )
					rrmdir( $dir.'/'.$object );
				else unlink( $dir.'/'.$object );
			}
		}
		reset( $objects );
		rmdir( $dir );
	}
}
/*------------------------------------------------------
	Deactivate
------------------------------------------------------*/
register_deactivation_hook( IG_PB_FILE, 'ig_pb_deactivate' );
// in case: select some/all plugins then Deactivate
add_action( 'admin_init', 'ig_pb_deactivate' );

function ig_pb_deactivate() {
	global $pagenow;
	if ( $pagenow == 'plugins.php' ) {
		$deactivate_action = false;
		$ig_pb_plugin = 'ig-pagebuilder/ig-pagebuilder.php';
		if ( ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'deactivate-selected', 'deactivate' ) ) ) {
			$action = $_REQUEST['action'];
			if ( ( $action == 'deactivate' && $_REQUEST['plugin'] == $ig_pb_plugin ) || ( $action == 'deactivate-selected' && in_array( $ig_pb_plugin, $_REQUEST['checked'] ) ) ) {
				$deactivate_action = true;
			}
		}

		if ( $deactivate_action ) {
			$ig_action  = 'ig_deactivate';
			$plugin_url = admin_url( 'plugins.php' );
			// check whether delete only Ig Pagebuilder OR Bulk deactivate plugins
			$deactivate_one = isset( $_POST['action'] ) ? false : true;

			// original WP request
			if ( ! isset( $_REQUEST['ig_wpnonce'] ) && ! isset( $_REQUEST['ig_back'] ) ) {
				// create ig_nonce
				$ig_nonce = wp_create_nonce( $ig_action );
				$method   = $deactivate_one ? 'GET' : 'POST';

				$back_text = __( 'No, take me back', IGPBL );
				if ( $deactivate_one )
					$back_btn = "<a href='$plugin_url' class='button button-large'>" . $back_text . '</a>';
				else {
					$back_btn = "<input type='submit' name='ig_back' class='button button-large' value='" . $back_text . "'>";
				}
				$form   = " action='{$plugin_url}' method='$method' ";
				$fields = '';

				foreach ( $_REQUEST as $key => $value ) {
					if ( ! is_array( $value ) ) {
						$fields .= "<input type='hidden' name='$key' value='$value' />";
					} else {
						foreach ( $value as $p ) {
							$fields .= "<input type='hidden' name='{$key}[]' value='$p' />";
						}
					}
				}
				$fields .= "<input type='hidden' name='ig_wpnonce' value='$ig_nonce' />";
				// show message
				ob_start();
				?>
				<p>
					<?php _e( 'After deactivating, all content built with PageBuilder will be parsed to plain HTML code. Are you sure you want to deactivate PageBuilder plugin?', IGPBL ); ?>
				</p>
				<center>
					<form <?php echo balanceTags( $form ); ?>>
						<?php echo balanceTags( $fields ); ?>
						<input type="submit" name="ig_deactivate" class="button button-large" value="<?php _e( 'Yes, deactivate plugin', IGPBL ); ?>" style="background: #d9534f;color: #fff;text-shadow:none;border:none;">
						<?php echo balanceTags( $back_btn ); ?>
					</form>
				</center>
                <p style="font-style: italic; font-size: 12px;margin-top: 20px;">
                        <?php _e( "Or if you want to deactivate without parsing 'content built with PageBuilder' to HTML code, click on the button below", IGPBL ); ?>
				</p>
                <center>
					<form <?php echo balanceTags( $form ); ?>>
						<?php echo balanceTags( $fields ); ?>
						<input type="submit" name="ig_deactivate_light" class="button button-large" value="<?php _e( 'Deactivate without parsing data', IGPBL ); ?>" style="background: #f0ad4e;color: #fff;text-shadow:none;border:none;">
					</form>
				</center>
				<?php
				$message = ob_get_clean();
				_default_wp_die_handler( $message );

				exit;
			} else {
				$ig_nonce = esc_sql( $_REQUEST['ig_wpnonce'] );
				$nonce    = wp_verify_nonce( $ig_nonce, $ig_action );

				if ( ! in_array( $nonce, array( 1, 2 ) ) ) {
					_default_wp_die_handler( __( 'Nonce is invalid!', IGPBL ) );
					exit;
				}

				// No, take me back
				if ( isset($_REQUEST['ig_back']) ) {
					// remove Ig Pagebuilder from the checked list
					if ( ($key = array_search( $ig_pb_plugin, $_REQUEST['checked'] ) ) !== false ) {
						unset( $_REQUEST['checked'][$key] );
					}

					// Overwrite list of checked plugins to deactivating
					$_POST['checked'] = $_REQUEST['checked'];
				}
				// deactivate Ig Pagebuilder & parsing content
				else if ( isset($_REQUEST['ig_deactivate']) ) {
					global $wpdb;
					// update post content = value of '_ig_html_content', deactivate pagebuilder
					$meta_key1 = 1;
					$meta_key2 = '_ig_html_content';
					$meta_key3 = '_ig_deactivate_pb';
					$wpdb->query(
						$wpdb->prepare(
							"
							UPDATE		$wpdb->posts p
							LEFT JOIN	$wpdb->postmeta p1
										ON p1.post_id = p.ID
							LEFT JOIN	$wpdb->postmeta p2
										ON p2.post_id = p.ID
							SET			post_content = p1.meta_value, p2.meta_value = %d
							WHERE		p1.meta_key = %s
										AND p2.meta_key = %s
							",
							$meta_key1,
							$meta_key2,
							$meta_key3
						)
					);
					// delete pagebuilder content
					ig_pb_delete_meta_key( array( '_ig_page_builder_content', '_ig_page_active_tab' ) );

					do_action( 'ig_pb_deactivate' );
				}
			}
		}
	}
}