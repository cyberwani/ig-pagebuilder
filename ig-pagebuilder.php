<?php
/**
 * Plugin Name: IG Pagebuilder
 * Plugin URI:  http://www.innogears.com
 * Description: Awesome content builder for Wordpress websites
 * Version:     2.0.1
 * Author:      InnoGears Team <support@www.innogears.com>
 * Author URI:  http://www.innogears.com
 * License:     GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */
error_reporting( E_ALL ^ E_NOTICE );
define( 'IG_PB_FILE', __FILE__ );
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( ! class_exists( 'IG_Pb_Init' ) ) {

	class IG_Pb_Init {

		public function __construct() {

			$this->includes();
			$this->autoload();
			$this->cls_alias();

			// Init the plugin
			IG_Pb_Assets_Register::init();
			// Assets load management
			IG_Pb_Assets_Load::hook();

			add_action( 'widgets_init', array( &$this, 'init' ), 100 );
		}

		function init(){
			global $Ig_Pb, $Ig_Pb_Widgets;
			$Ig_Pb = new IG_Pb_Core();
			$IG_Pb_Utils_Plugin = new IG_Pb_Utils_Plugin();
			
			IG_Product_Plugin::init();
			$Ig_Pb_Widgets = ! empty( $Ig_Pb_Widgets ) ? $Ig_Pb_Widgets : IG_Pb_Helper_Functions::widgets();
		}

		// include some core files
		function includes() {
			// include core files
			include_once 'core/loader.php';
			include_once 'core/defines.php';
		}

		// autoload register
		function autoload(){
			IG_PB_Loader::register( IG_PB_PATH . 'core', 'IG_Pb_' );
			IG_PB_Loader::register( IG_PB_PATH . 'core/product', 'IG_Product_' );

			do_action( 'ig_pb_autoload' );
		}

		// For revoking class from library without changes
		function cls_alias() {
			class_alias( 'IG_Pb_Loader', 'IG_Loader' );
			class_alias( 'IG_Pb_Utils_Admin_Menu', 'IG_Init_Admin_Menu' );
			class_alias( 'IG_Pb_Assets_Load', 'IG_Init_Assets' );
		}
	}

	$GLOBALS['ig_pagebuilder'] = new IG_Pb_Init();
}
