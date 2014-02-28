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
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class IG_Pb_Utils_Plugin {

	function __construct() {
		add_action( 'admin_init', array( &$this, 'check_activate_plugin' ), 100 );
		add_action( 'admin_init', array( &$this, 'activate_plugin' ), 100 );
		register_activation_hook( IG_PB_FILE, array( &$this, 'do_activate' ) );
		register_deactivation_hook( IG_PB_FILE, array( &$this, 'do_deactivate' ) );
		// in case: select some/all plugins then Deactivate
		add_action( 'admin_init', array( &$this, 'do_deactivate' ) );
	}

	// Manual do activation_hook for Update action (when register_activation_hook is not fired)
	function check_activate_plugin() {
		global $pagenow;
		if ( in_array( $pagenow, array( 'plugins.php', 'update.php' ) ) ) {
			$plugin_data = get_plugin_data( IG_PB_FILE );
			$version     = get_transient( 'ig_pb_check_activate' );

			if ( $plugin_data['Version'] != $version ) {
				ob_start();
				$this->do_activate();
			}
		}
	}

	// Active extracted plugin
	function activate_plugin() {
		if ( get_transient( 'ig_pb_check_activate' ) ) {
			ob_start();
			global $pagenow;
			$providers = $this->default_providers();

			if ( is_plugin_active( 'ig-pagebuilder/ig-pagebuilder.php' ) ) {
				$this->extract_plugins();

				// activate dependency plugins
				foreach ( $providers as $provider ) {
					if ( isset ( $provider['folder'] ) ) {
						$folder = $provider['folder'];
						if ( ! is_plugin_active( $folder . '/main.php' ) ) {
							activate_plugin( $folder . '/main.php' );
						}
					}
				}
			}
			ob_clean();

			setcookie( 'ig_pb_check_activate', '', time() - 1000 );
		}
	}

	// Activate handle
	function do_activate() {
		$plugin_data = get_plugin_data( IG_PB_FILE );
		set_transient( 'ig_pb_check_activate', $plugin_data['Version'] );
		IG_Pb_Utils_Common::remove_cache_folder();
        $this->deactivate_providers();
	}

    // deactivate dependency plugins
    function deactivate_providers() {
        $providers = $this->default_providers();
        $plugins = array();
        foreach ( $providers as $provider ) {
            if ( isset ( $provider['folder'] ) ) {
                $folder = $provider['folder'];
                if ( is_plugin_active( $folder . '/main.php' ) ) {
                    $plugins[] = $folder . '/main.php';
                }
            }
        }
        deactivate_plugins( $plugins );
    }

	// Extract packages of third-party plugins
	function extract_plugins() {
		$providers = $this->default_providers();
        global $wp_filesystem;

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! $wp_filesystem ) {
            WP_Filesystem();
        }
		// extract dependency plugins
		foreach ( $providers as $provider ) {
			if ( isset ( $provider['folder'] ) ) {
				$folder     = $provider['folder'];
				$source_zip = plugin_dir_path( IG_PB_FILE ) . $folder . '.zip';
				if ( ! ( file_exists( $source_zip ) ) ){
					$error = 404;
				} else {
					$source_folder = WP_PLUGIN_DIR . "/$folder";
					if ( file_exists( $source_folder ) ) {
						// delete folder
						IG_Pb_Utils_Common::recursive_rmdir( $source_folder );
                        if ( file_exists( $source_folder ) ) {
                            // rename older folder
                            rename( $source_folder, $source_folder . '-old' );
                        }

					}
					// extract to plugin folder
					$unzipfile = unzip_file( $source_zip, $source_folder );
					if ( $unzipfile ) {
						$error = 0;
						chmod( WP_PLUGIN_DIR, 0777 );
						unlink( $source_zip );
					} else {
						$error = 1;
					}
				}
			}
		}
	}

	// Get default providers directory
	function default_providers() {
		global $Ig_Sc_Providers;
        $Ig_Sc_Providers = empty ( $Ig_Sc_Providers ) ? array() : $Ig_Sc_Providers;
		return array_merge(
			$Ig_Sc_Providers,
			array(
				array(
					'folder' => 'ig-shortcodes-free',
				),
			)
		);
	}

	// Deactivate handle
	function do_deactivate() {
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
						IG_Pb_Utils_Common::delete_meta_key( array( '_ig_page_builder_content', '_ig_page_active_tab' ) );

						do_action( 'ig_pb_deactivate' );
					}
				}
			}
		}
	}
}