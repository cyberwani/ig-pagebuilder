<?php
/**
 * @version    $Id$
 * @package    IG_PageBuilder
 * @author     InnoGears Team <support@www.innogears.com>
 * @copyright  Copyright (C) 2012 InnoGears.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.www.innogears.com
 * Technical Support:  Feedback - http://www.www.innogears.com/contact-us/get-support.html
 */

/**
 * Assets initialization.
 *
 * @package  IG_Plugin_Framework
 * @since    1.0.0
 */
class IG_Pb_Assets {
	/**
	 * Assets to be registered.
	 *
	 * @var  array
	 */
	protected static $assets = array();

	/**
	 * Registered assets.
	 *
	 * @var  array
	 */
	protected static $registered = array();

	/**
	 * Loaded assets.
	 *
	 * @var  array
	 */
	protected static $loads = array();

	/**
	 * Registered inline scripts/styles.
	 *
	 * @var  array
	 */
	protected static $inline = array( 'css' => array(), 'js' => array() );

	/**
	 * Triggered hooks.
	 *
	 * @var  array
	 */
	protected static $triggered_hooks = array();

	/**
	 * Hook into WordPress.
	 *
	 * @return  void
	 */
	public static function hook() {
		// Register action to load assets
		static $registered;

		if ( ! isset( $registered ) ) {
			global $pagenow;

			// Admin or frontend?
			if ( 'customize.php' == $pagenow ) {
				// Register actions
				add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 100 );
				add_action( 'customize_controls_print_styles'   , array( __CLASS__, 'head'            ), 100 );
				add_action( 'customize_controls_print_scripts'  , array( __CLASS__, 'footer'          ), 100 );
			} else {
				$prefix = defined( 'WP_ADMIN' ) ? 'admin' : 'wp';
				// Register actions
				add_action( "{$prefix}_enqueue_scripts", array( __CLASS__, 'enqueue_scripts' ), 1000000 );
				add_action( "{$prefix}_head"           , array( __CLASS__, 'head'            ), 1000000 );
				add_action( "{$prefix}_footer"         , array( __CLASS__, 'footer'          ), 1000000 );
			}

			$registered = true;
		}
	}

	/**
	 * Load required asset.
	 *
	 * @param   string  $handle  Asset handle, e.g. bootstrap-css, bootstrap-js, jquery-fancybox-css, jquery-fancybox-js, etc.
	 * @param   string  $src     Relative path from IG Plugin Framework directory to asset file.
	 * @param   array   $deps    Array of dependencies.
	 * @param   string  $ver     Asset version.
	 *
	 * @return  void
	 */
	public static function load( $handle, $src = null, $deps = array(), $ver = null ) {
		// Check if we have an array of handle
		if ( is_array( $handle ) ) {
			foreach ( $handle AS $key ) {
				self::load( $key );
			}

			return;
		}

		// Store new asset details for register later
		if ( ! isset( self::$assets[ $handle ] ) && ! empty( $src ) ) {
			self::$assets[ $handle ] = array(
				'src'  => $src,
				'deps' => $deps,
				'ver'  => $ver,
			);
		}

		// Check if required hook is triggered?
		$prefix = defined( 'WP_ADMIN' ) ? 'admin' : 'wp';

		if ( in_array( 'enqueue_scripts', self::$triggered_hooks ) ) {
			self::enqueue_asset( $handle );
		} else {
			self::$loads[] = $handle;
		}
	}

	/**
	 * Register inline scripts / styles.
	 *
	 * @param   string   $type       Either 'css' or 'js'.
	 * @param   string   $text       Inline script/style, do not wrap inside <script> / <style> tags.
	 * @param   boolean  $print_out  Print out immediately instead of schedule till proper hook is triggered.
	 *
	 * @return  void
	 */
	public static function inline( $type, $text, $print_out = false ) {
		if ( isset( self::$inline[$type] ) ) {
			// Print out immediately if proper hook for printing out inline scripts / styles is already triggered
			if ( 'css' == $type && in_array( 'head', self::$triggered_hooks ) ) {
				$print_out = true;
			} elseif ( 'js' == $type && in_array( 'footer', self::$triggered_hooks ) ) {
				$print_out = true;
			}

			// Trim CR / LF character
			$text = trim( $text, "\r\n" );

			if ( $print_out ) {
				self::print_inline( $type, $text );
			} else {
				self::$inline[ $type ][] = $text;
			}
		}
	}

	/**
	 * Do 'admin_enqueue_scripts' / 'wp_enqueue_scripts' action.
	 *
	 * @return  void
	 */
	public static function enqueue_scripts() {
		// Register assets
		self::register();

		foreach ( self::$loads AS $handle ) {
			self::enqueue_asset( $handle );
		}

		// Indicate that the hook is triggered
		self::$triggered_hooks[] = 'enqueue_scripts';
	}

	/**
	 * Do 'admin_head' / 'wp_head' action.
	 *
	 * @return  void
	 */
	public static function head() {
		self::print_inline( 'css' );

		// Indicate that the hook is triggered
		self::$triggered_hooks[] = 'head';
	}

	/**
	 * Do 'admin_footer' / 'wp_footer' action.
	 *
	 * @return  void
	 */
	public static function footer() {
		self::print_inline( 'js' );

		// Indicate that the hook is triggered
		self::$triggered_hooks[] = 'footer';

		// Execute action to localize script
		do_action( 'ig_pb_assets_localize', self::$loads );
	}

	/**
	 * Enqueue asset.
	 *
	 * @param   string  $handle  Asset handle.
	 *
	 * @return  void
	 */
	protected static function enqueue_asset( $handle ) {
		global $ig_handle_assets;
		if ( isset( self::$assets[ $handle ] ) && isset( self::$assets[ $handle ]['site'] ) ) {
			if ( 'admin' == self::$assets[ $handle ]['site'] && ! defined( 'WP_ADMIN' ) ) {
				return;
			}

			if ( 'front' == self::$assets[ $handle ]['site'] && defined( 'WP_ADMIN' ) ) {
				return;
			}
		}

		// Register assets if not already registered
		if ( ! in_array( $handle, self::$registered ) ) {
			self::register();
		}

		// Detect asset type
		$type = ( substr( $handle, -4 ) == '-css' ) ? 'style' : 'script';

		// Enqueue asset
		$ig_handle_assets[] = $handle;
		if ( 'script' == $type && isset( self::$assets[ $handle ] ) && in_array( $handle, self::$registered ) ) {
			// Build arguments to load script in footer so it can be localized at any time
			$args[] = preg_replace( '/-( css|js )$/', '', $handle );
			$args[] = self::$assets[ $handle ]['src'];
			$args[] = isset( self::$assets[ $handle ]['deps'] ) ? self::$assets[$handle]['deps'] : array();
			$args[] = isset( self::$assets[ $handle ]['ver']  ) ? self::$assets[$handle]['ver']  : false;
			$args[] = true;

			call_user_func_array( 'wp_enqueue_script', $args );
		} else {
			call_user_func( "wp_enqueue_{$type}", preg_replace( '/-( css|js )$/', '', $handle ) );
		}
	}

	/**
	 * Generate and print out inline scripts / styles.
	 *
	 * @param   string   $type     Either 'css' or 'js'.
	 * @param   string   $text     Text to be printed out.
	 * @param   boolean  $no_wrap  If set to TRUE, inline script will not be wrapped inside '$( document ).ready' function.
	 *
	 * @return  void
	 */
	protected static function print_inline( $type, $text = null, $no_wrap = false ) {
		// Generate then print inline styles / scripts
		$html = array();

		if ( ! empty( $text ) || count( self::$inline[$type] ) ) {
			if ( 'js' == $type ) {
				$html[] = '<script type="text/javascript">';
				$html[] = '(function($) {';

				$no_wrap || $html[] = "\t$(document).ready(function() {";
			} else {
				$html[] = '<style type="text/css">';
			}

			$html[] = ! empty( $text ) ? $text : implode( "\n\n", self::$inline[$type] );

			if ( 'js' == $type ) {
				$no_wrap || $html[] = "\t});";

				$html[] = '})(jQuery);';
				$html[] = '</script>';
			} else {
				$html[] = '</style>';
			}
		}

		echo ' ' . implode( "\n", $html ) . "\n";
	}

	/**
	 * Register InnoGears's assets with WordPress.
	 *
	 * @return  void
	 */
	protected static function register() {
		// Filter assets to be registered
		self::$assets = apply_filters( 'ig_pb_assets_register', self::$assets );

		foreach ( self::$assets AS $key => $value ) {
			// If asset is registered, continue the loop
			if ( in_array( $key, self::$registered ) ) {
				continue;
			}

			// Store asset being registered
			self::$registered[] = $key;

			// Set default value for missing data
			isset( $value['deps'] ) || $value['deps'] = array();
			isset( $value['ver' ] ) || $value['ver' ] = null;

			// Detect asset type
			$type = substr( $key, -4 ) == '-css' ? 'style' : 'script';

			// Shorten asset and dependency keys
			$key = preg_replace( '/-( css|js )$/', '', $key );

			foreach ( $value['deps'] AS $k => $v ) {
				if ( array_key_exists( $v, self::$assets ) ) {
					$value['deps'][ $k ] = preg_replace( '/-( css|js )$/', '', $v );
				}
			}

			// Register asset
			call_user_func( "wp_register_{$type}", $key, $value['src'], $value['deps'], $value['ver'] );
		}
	}
}
