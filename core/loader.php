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

if ( ! class_exists( 'IG_Pb_Loader' ) ) {
	/**
	 * Class autoloader.
	 *
	 * @package  IG_Plugin_Framework
	 * @since    1.0.0
	 */
	class IG_Pb_Loader {
		/**
		 * Path to search for class declaration.
		 *
		 * @var  array
		 */
		protected static $paths = array();

		/**
		 * Register to base path to search for class declaration files.
		 *
		 * @param   string  $path    Base path.
		 * @param   string  $prefix  Class prefix.
		 *
		 * @return  void
		 */
		public static function register( $path, $prefix = 'IG_' ) {
			self::$paths[$path] = $prefix;
		}

		/**
		 * Auload InnoGears's classes.
		 *
		 * @param   string  $className  Name of class.
		 *
		 * @return  void
		 */
		public static function load( $className ) {
			// Only autoload class name prefixed with IG_
			if ( 'IG_' == substr( $className, 0, 3 ) ) {
				// Filter paths to search for class file
				self::$paths = apply_filters( 'ig_pb_loader_get_path', self::$paths );

				foreach ( array_reverse( self::$paths ) AS $base => $prefix ) {
					if ( 0 === strpos( $className, $prefix ) ) {
						// Split the class name into parts separated by underscore character
						$path = explode( '_', trim( str_replace( $prefix, '', $className ), '_' ) );

						// Convert class name to file path
						$path = implode( '/', array_map( 'strtolower', $path ) );

						// Check if class declaration file exists
						$file = $base . '/' . $path . '.php';

						while ( ! ( $exists = is_file( $file ) ) && false !== strpos( $file, '/' ) ) {
							$file = preg_replace( '#/([^/]+)$#', '-\\1', $file );
						}

						if ( ! $exists && false === strpos( $path, '/' ) ) {
							// If class name has single word, e.g. IG_Version, duplicate it for alternative file path, e.g. version/version.php
							$exists = is_file( $file = $base . '/' . $path . '/' . $path . '.php' );
						}

						if ( $exists ) {
							return include_once $file;
						}
					}
				}

				return false;
			}
		}

		/**
		 * Search a file in registered paths.
		 *
		 * @param   string  $file  Relative file path to search for.
		 *
		 * @return  string
		 */
		public static function get_path( $file ) {
			// Get alternative file name
			$alternative = str_replace( '_', '-', $file );

			// Filter paths to search for file
			self::$paths = apply_filters( 'ig_pb_loader_get_path', self::$paths );

			foreach ( array_reverse( self::$paths ) AS $base => $prefix ) {
				if ( is_file( $base . '/' . $alternative ) ) {
					return $base . '/' . $alternative;
				} elseif ( is_file( $base . '/' . $file ) ) {
					return $base . '/' . $file;
				}
			}

			return null;
		}
	}

	// Register class autoloader with PHP
	spl_autoload_register( array( 'IG_Pb_Loader', 'load' ) );
}