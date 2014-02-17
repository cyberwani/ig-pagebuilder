<?php
/**
 * Plugin Name: IG Pagebuilder
 * Plugin URI:  http://www.innogears.com
 * Description: Awesome content builder for Wordpress websites
 * Version:     1.0.8
 * Author:      InnoGears Team <support@www.innogears.com>
 * Author URI:  http://www.innogears.com
 * License:     GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */
error_reporting( E_ALL ^ E_NOTICE );
define( 'IG_PB_FILE', __FILE__ );

if ( ! class_exists( 'IG_Pb_Init' ) ) {

	class IG_Pb_Init {

		public function __construct() {

			$this->includes();
			$this->autoload();

			global $pagenow;
			$pages = array( 'post.php', 'post-new.php', 'widgets.php', 'admin-ajax.php' );
			if ( in_array( $pagenow, $pages )  || IG_Pb_Helper_Functions::is_modal() || ! is_admin() ) {
				// Init the plugin
				IG_Pb_Plugin::init();
				// Assets load management
				IG_Pb_Assets::hook();
			}

			add_action( 'widgets_init', array( &$this, 'init' ), 100 );
		}

		function init(){
			global $Ig_Pb, $Ig_Pb_Widgets;
			$Ig_Pb = new IG_Pb();
			$Ig_Pb_Widgets = ! empty( $Ig_Pb_Widgets ) ? $Ig_Pb_Widgets : IG_Pb_Helper_Functions::widgets();
		}

		// include some core files
		function includes() {
			// include core files
			include_once 'core/loader.php';
			include_once 'core/pb.php';
			include_once 'core/third-party.php';
			// include utility files
			include_once 'core/utils/common.php';
			include_once 'core/utils/placeholder.php';
			include_once 'core/utils/plugin.php';
		}

		// autoload register
		function autoload(){
			IG_Pb_Loader::register( IG_PB_PATH . 'core/assets', 'IG_Pb_' );
			IG_Pb_Loader::register( IG_PB_PATH . 'core/helper', 'IG_Pb_Helper' );
			IG_PB_Loader::register( IG_PB_PATH . 'core/helper/html', 'IG_Pb_Helper_Html_' );
			IG_Pb_Loader::register( IG_PB_PATH . 'core/objects', 'IG_Pb_' );
			IG_Pb_Loader::register( IG_PB_PATH . 'core/shortcode', 'IG_Pb_' );

			do_action( 'ig_pb_autoload' );
		}
	}

	$GLOBALS['ig_pagebuilder'] = new IG_Pb_Init();
}
