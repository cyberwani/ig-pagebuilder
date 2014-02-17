<?php
/**
 * @version    $Id$
 * @package    IG Pagebuilder
 * @author     InnoGears Team <support@www.innogears.com>
 * @copyright  Copyright (C) 2012 www.innogears.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.www.innogears.com
 * Technical Support:  Feedback - http://www.www.innogears.com
 */
if ( ! class_exists( 'IG_Pb_Helper_Shortcode' ) ) {

	class IG_Pb_Helper_Shortcode {

		static $pattern = '';
		static $group_shortcodes   = array( 'group', 'table', 'pricingtable_item' );
		static $item_html_template = array(
			'icon' => "<i class='_IG_STD_'></i>",
		);

		/**
		 * Get list of (element + layout) shortcodes from shortcode folders
		 * @return array
		 */
		public static function ig_pb_shortcode_tags() {
			do_action( 'ig_pb_third_party' );

			global $Ig_Sc_Providers;
			$Ig_Sc_Providers = apply_filters(
					'ig_pb_provider',
					self::this_provider()
			);
			set_transient( '_ig_pb_providers', serialize( $Ig_Sc_Providers ) );

			$sc_path = self::shortcode_dirs();

			foreach ( $sc_path as $path ) {
				self::autoload_shortcodes( $path );
				while ( $d = glob( $path . '/*', GLOB_ONLYDIR ) ) {
					$path .= '/*';
					foreach ( $d as $adir ) {
						self::autoload_shortcodes( $adir );
					}
				}
			}

			$shortcodes = self::shortcodes_list( $sc_path );
			return $shortcodes;
		}

		// autoload shortcodes & sub shortcodes
		public static function autoload_shortcodes( $path ){
			$items   = substr_count( $path, '/item' );
			$postfix = str_repeat( 'Item_', $items );
			// autoload shortcodes
			IG_Pb_Loader::register( $path, 'IG_' . $postfix );
		}

		// set information for InnoGears provider
		public static function this_provider(){
			return array(
						plugin_dir_path( IG_PB_FILE ) =>
							array(
								'path' => IG_PB_PATH,
								'uri' => IG_PB_URI,
								'name' => 'InnoGears',
								'shortcode_dir' => array( IG_PB_LAYOUT_PATH ), //array( IG_PB_LAYOUT_PATH, IG_PB_ELEMENT_PATH ),
								'js_shortcode_dir' => array(
										'path' => IG_PB_PATH . 'assets/innogears/js/shortcodes',
										'uri' => IG_PB_URI . 'assets/innogears/js/shortcodes',
									),
							)
						);
		}

		/**
		 * Get provider name & path of a shortcode directory
		 *
		 * @param type $shortcode_dir
		 * @return type
		 */
		private static function get_provider( $shortcode_dir ){
			global $Ig_Sc_Providers;
			$providers = $Ig_Sc_Providers;
			foreach ( $providers as $dir => $provider ) {
				foreach ( $provider['shortcode_dir'] as $dir ) {
					if ( strpos( $shortcode_dir, $dir ) !== false ){
						return array(
							'name' => $provider['name'],
							'dir' => $dir,
						);
					}
				}
			}
		}

		/**
		 * Get info of provider of the shortcode
		 *
		 * @global type $Ig_Sc_Providers, $Ig_Sc_By_Providers
		 * @param type $shortcode_name
		 * @param type $shortcode_by_providers
		 * @return type
		 */
		public static function get_provider_info( $shortcode_name, $info ){
			global $Ig_Sc_Providers;
			global $Ig_Sc_By_Providers;
			$providers = $Ig_Sc_Providers;
			$shortcode_by_providers = $Ig_Sc_By_Providers;
			foreach ( $shortcode_by_providers as $provider_dir => $shortcodes ) {
				// find shortcode in what directory
				if ( in_array( $shortcode_name, $shortcodes ) ){
					// find provider of that directory
					foreach ( $providers as $dir => $provider ) {
						foreach ( $provider['shortcode_dir'] as $dir ) {
							if ( $provider_dir == $dir ){
								return $Ig_Sc_Providers[$provider['path']][$info];
							}
						}
					}
				}
			}
		}

		/**
		 * Get shortcode directories of providers
		 * @return type
		 */
		public static function shortcode_dirs( ){
			global $Ig_Sc_Providers;
			$providers = $Ig_Sc_Providers;
			$shortcode_dirs = array();
			foreach ( $providers as $provider ) {
				$shortcode_dirs = array_merge( $shortcode_dirs, $provider['shortcode_dir'] );
			}
			return $shortcode_dirs;
		}

		/**
		 * Get shortcodes in shortcode directories
		 * @param array $sc_path
		 * @return array
		 */
		public static function shortcodes_list( $sc_path ) {
			if ( empty( $sc_path ) )
				return NULL;
			if ( ! is_array( $sc_path ) ) {
				$sc_path = array( $sc_path );
			}
			global $Ig_Sc_By_Providers;
			// get list of directory by directory level
			$level_dirs = array();
			foreach ( $sc_path as $path ) {
				$level_dirs[substr_count( $path, '/*' )][] = $path;
				while ( $d = glob( $path . '/*', GLOB_ONLYDIR ) ) {
					$path .= '/*';
					foreach ( $d as $adir ) {
						$level_dirs[substr_count( $path, '/*' )][] = $adir;
					}
				}
			}
			// traverse over array of path to get shortcode information
			foreach ( $level_dirs as $level => $dirs ) {
				foreach ( $dirs as $dir ) {
					// provider info
					$parent_path = str_replace( '/item', '', $dir );
					$provider    = self::get_provider( $parent_path );
					// shortcode info
					$type   = ( $dir == IG_PB_LAYOUT_PATH ) ? 'layout' : 'element';
					$this_level = ( intval( $level ) > 0 ) ? ( intval( $level ) - 1 ) : intval( $level );
					$append     = str_repeat( 'item_', $this_level );
					foreach ( glob( $dir . '/*.php' ) as $file ) {
						$p = pathinfo( $file );
						$element = str_replace( '-', '_', $p['filename'] );
						$shortcode_name = 'ig_' . $append . $element;
						$shortcodes[$shortcode_name] = array( 'type' => $type, 'provider' => $provider );
						$Ig_Sc_By_Providers[$provider['dir']][] = $shortcode_name;
					}
				}
			}
			return $shortcodes;
		}

		/**
		 * Extract shortcode params from string
		 * Ex: [param-tag=h3&param-text=Your+heading+text&param-font=custom]
		 *
		 * @param type $param_str
		 * @return array
		 */
		static function extract_params( $param_str, $str_shortcode = '' ) {
			$param_str = stripslashes( $param_str );
			$params    = array();
			// get params of shortcode
			preg_match_all( '/[A-Za-z0-9_-]+=\"[^"\']*\"/u', $param_str, $tmp_params, PREG_PATTERN_ORDER );
			foreach ( $tmp_params[0] as $param_value ) {
				$output = array();
				preg_match_all( '/([A-Za-z0-9_-]+)=\"([^"\']*)\"/u', $param_value, $output, PREG_SET_ORDER );
				foreach ( $output as $item ) {
					if ( ! in_array( $item[1], array( 'disabled' ) ) || ! isset ( $params[$item[1]] ) ) {
						$params[$item[1]] = urldecode( $item[2] );
					}
				}
			}
			$pattern = get_shortcode_regex();
			preg_match_all( '/' . $pattern . '/s', $param_str, $tmp_params, PREG_PATTERN_ORDER );
			$content = isset( $tmp_params[5][0] ) ? trim( $tmp_params[5][0] ) : '';
			$content = preg_replace( '/rich_content_param-[a-z_]+=/', '', $content );
			$params['sc_inner_content'] = $content;
			return $params;
		}

		/**
		 * Generate params list of shortcode (from $this->items array) OR get value of a param
		 *
		 * @param type $arr	($this->items)
		 * @param type $paramID (get std of a param by its ID)
		 * @param type $filter_arr (re-assign value for some params ( "pram id" => "new std value" ))
		 * @param type $assign_content (assign content of $filter_arr['sc_inner_content'] to the param which has role = 'content')
		 * @return type
		 */
		static function generate_shortcode_params( &$arr, $paramID = NULL, $filter_arr = NULL, $assign_content = FALSE, $extract_content = FALSE, $assign_title = '' ) {
			$params = array();
			if ( $arr ) {
				foreach ( $arr as $tab => &$options ) {
					foreach ( $options as &$option ) {
						$type = isset( $option['type'] ) ? $option['type'] : '';
						$option['std'] = ! isset( $option['std'] ) ? '' : $option['std'];

						if ( isset($option['role'] ) && $option['role'] == 'content' ) {
							if ( $assign_content ) {
								if ( ! empty($filter_arr ) && isset( $filter_arr['sc_inner_content']) )
									$option['std'] = $filter_arr['sc_inner_content'];
							}
							if ( $extract_content ) {
								$params['sub_sc_extract_content'][$option['id']] = $option['std'];
							} else {
								// remove option which role = content from Shortcode structure ( except option which has another role: title )
								if ( ! ( ( isset( $option['role'] ) && $option['role'] == 'title' ) || ( isset($option['role_2'] ) && $option['role_2'] == 'title') || ( isset($option['role'] ) && $option['role'] == 'title_prepend' ) ) ) {
									unset( $option );
									continue;
								}
							}
						}
						if ( $type != 'preview' ) {
							if ( ! is_array( $type ) ) {
								if ( ! in_array( $type, self::$group_shortcodes ) ) {
									if ( empty( $filter_arr ) ) {
										if ( ! empty( $paramID ) ) {
											if ( $option['id'] == $paramID )
												return $option['std'];
										}
										else if ( isset( $option['id'] ) )
											$params[$option['id']] = $option['std'];
									}else {
										if ( isset($option['id'] ) && array_key_exists( $option['id'], $filter_arr ) )
											$option['std'] = $filter_arr[$option['id']];
									}
									if ( ! empty( $assign_title ) ) {
										// default std
										if ( strpos( $option['std'], ig_pb_get_placeholder( 'index' ) ) !== false ) {
											$option['std'] = '';
											$params['assign_title'] = __( '(Untitled)', IGPBL );
										} else if ( (isset($option['role'] ) && $option['role'] == 'title') || ( isset($option['role_2'] ) && $option['role_2'] == 'title' ) ) {
											if ( $option['role'] == 'title' )
												$params['assign_title'] = $option['std'];
											else
												$params['assign_title'] = ig_pb_slice_content( $option['std'] );
										} else if ( (isset($option['role'] ) && $option['role'] == 'title_prepend') && ! empty( $option['title_prepend_type'] ) && ! empty( $option['std']) ) {
											$params['assign_title'] = ig_pb_remove_placeholder( self::$item_html_template[$option['title_prepend_type']], 'standard_value', $option['std'] ) . $params['assign_title'];
										}
									}
								} else {
									// shortcode in shortcode
									if ( empty( $filter_arr ) ) {
										foreach ( $option['sub_items'] as &$sub_items ) {
											$sub_items['std'] = ! isset( $sub_items['std'] ) ? '' : $sub_items['std'];
											if ( ! empty( $paramID ) ) {
												if ( $sub_items['id'] == $paramID )
													return $sub_items['std'];
											}
											else
												$params['sub_sc_content'][] = $sub_items;
										}
									}
									else {
										// Assign Content For Sub-Shortcode
										$count_default = count( $option['sub_items'] );
										$count_real    = isset( $filter_arr['sub_sc_content'] ) ? count( $filter_arr['sub_sc_content'] ) : 0;
										if ( $count_real > 0 ) {
											if ( $count_default < $count_real ) {
												for ( $index = $count_default; $index < $count_real; $index++ ) {
													$option['sub_items'][$index] = array( 'std' => '' );
												}
											} elseif ( $count_default > $count_real ) {
												for ( $index = $count_real; $index < $count_default; $index++ ) {
													unset( $option['sub_items'][$index] );
												}
											}
											array_walk( $option['sub_items'], array( 'IG_Pb_Helper_Functions', 'ig_arr_walk_subsc' ), $filter_arr['sub_sc_content'] );
										}
									}
								}
							} else {
								if ( empty( $filter_arr ) ) {
									foreach ( $option['type'] as &$sub_options ) {
										$sub_options['std'] = ! isset( $sub_options['std'] ) ? '' : $sub_options['std'];
										if ( ! empty( $paramID ) ) {
											if ( $sub_options['id'] == $paramID )
												return $sub_options['std'];
										}
										else
											$params[$sub_options['id']] = $sub_options['std'];
									}
								}
								else {
									array_walk( $option['type'], array( 'IG_Pb_Helper_Functions', 'ig_arr_walk' ), $filter_arr );
								}
							}

							if ( isset( $option['extended_ids'] ) ) {
								foreach ( $option['extended_ids'] as $_id ) {
									$params[$_id] = isset( $option[$_id]['std'] ) ? $option[$_id]['std'] : '';
								}
							}
						}
					}
				}
			}
			return $params;
		}

		/**
		 * Generate shortcode structure from array of params and name of shortcode
		 *
		 * @param type $shortcode_name
		 * @param type $params
		 * @return type
		 */
		static function generate_shortcode_structure( $shortcode_name, $params, $content = '' ) {
			$shortcode_structure = "[$shortcode_name ";

			$arr = array();
			$exclude_params = array( 'sub_sc_content', 'sub_sc_extract_content' );
			foreach ( $params as $key => $value ) {
				if ( ! in_array( $key, $exclude_params ) && $key != '' )
					$arr[$key] = $value;
			}

			// get content of param which has: role = content
			if ( ! empty( $params['sub_sc_extract_content'] ) ) {
				foreach ( $params['sub_sc_extract_content'] as $paramId => $std ) {
					unset( $arr[$paramId] );
					$content = $std;
				}
			}

			foreach ( $arr as $key => $value ) {
				$shortcode_structure .= "$key=\"$value\" ";
			}
			$shortcode_structure .= ']';
			$shortcode_structure .= $content;
			$shortcode_structure .= "[/$shortcode_name]";
			return $shortcode_structure;
		}

		/**
		 * Get Shortcode class from shortcode name
		 * @param type $shortcode_name
		 * @return type
		 */
		static function get_shortcode_class( $shortcode_name ) {
			$shortcode_name = str_replace( 'ig_', 'IG_', $shortcode_name );
			$shortcode = str_replace( '_', ' ', $shortcode_name );
			$class = ucwords( $shortcode );
			$class = str_replace( ' ', '_', $class );
			return $class;
		}

		/**
		 * Return shortcode name without 'ig_' prefix
		 *
		 * @param type $ig_shortcode_name
		 * @return type
		 */
		static function shortcode_name( $ig_shortcode_name ){
			return str_replace( 'ig_', '', $ig_shortcode_name );
		}

		/**
		 * Removes wordpress autop and invalid nesting of p tags, as well as br tags
		 *
		 * @param string $content html content by the wordpress editor
		 * @return string $content
		 */
		static function remove_autop( $content ) {
			$shortcode_tags = array();
			$tagregexp = join( '|', array_map( 'preg_quote', $shortcode_tags ) );

			// opening tag
			$content = preg_replace( "/(<p>)?\[($tagregexp)(\s[^\]]+)?\](<\/p>|<br\s\/>)?/", '[$2$3]', $content );

			// closing tag
			$content = preg_replace( "/(<p>)?\[\/($tagregexp)](<\/p>|<br\s\/>)?/", '[/$2]', $content );

			$content = do_shortcode( shortcode_unautop( $content ) );
			$content = preg_replace( '#^<\/p>|^<br\s?\/?>|<p>$|<p>\s*(&nbsp;)?\s*<\/p>#', '', $content );

			return ( $content );
		}

		/**
		 * Generate shortcode pattern ( for Ig shortcodes only )
		 * @global type $shortcode_tags
		 * @return pattern which contains only shortcodes of Ig Pagebuilder
		 */
		public static function shortcodes_pattern( $tags = '' ) {
			global $Ig_Pb_Shortcodes;
			global $shortcode_tags;
			$shortcode_tags_clone = $shortcode_tags;
			$shortcode_tags       = empty( $tags ) ? ( ! empty ( $Ig_Pb_Shortcodes ) ? $Ig_Pb_Shortcodes : IG_Pb_Helper_Shortcode::ig_pb_shortcode_tags() ) : $tags;
			$pattern			  = get_shortcode_regex();
			$shortcode_tags       = $shortcode_tags_clone;
			return "/$pattern/s";
		}

		/**
		 * Remove all Ig shortcodes from content
		 * @param string $content
		 * @return string Content without shortcode tags
		 */
		public static function remove_ig_shortcodes( $content ) {
			global $Ig_Pb_Shortcodes;
			$ig_shortcode_tags = ! empty ( $Ig_Pb_Shortcodes ) ? $Ig_Pb_Shortcodes : IG_Pb_Helper_Shortcode::ig_pb_shortcode_tags();
			$tagnames		  = array_keys( $ig_shortcode_tags );
			$tagregexp		 = join( '|', array_map( 'preg_quote', $tagnames ) );

			// replace opening tag
			$regex   = '\\['						   // Opening bracket
					. '(\\[?)'					   // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
					. "($tagregexp)"				 // 2: Shortcode name
					. '(?![\\w-])'				   // Not followed by word character or hyphen
					. '('							// 3: Unroll the loop: Inside the opening shortcode tag
					. '[^\\]\\/]*'				   // Not a closing bracket or forward slash
					. '(?:'
					. '\\/(?!\\])'				   // A forward slash not followed by a closing bracket
					. '[^\\]\\/]*'				   // Not a closing bracket or forward slash
					. ')*?'
					. ')'
					. '(?:'
					. '(\\/)'						// 4: Self closing tag ...
					. '\\]'						  // ... and closing bracket
					. '|'
					. '\\]'						  // Closing bracket
					. ')'
					. '(\\]?)';					  // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
			$content = preg_replace( "/$regex/s", '<p>', $content );

			// replace closing tag
			$regex   = "\\[\\/($tagregexp)\\]";
			$content = preg_replace( "/$regex/s", '</p>', $content );

			// remove redundant p tag
			$content = preg_replace( '/(<p>)+/', '<p>', $content );
			$content = preg_replace( '/(<\/p>)+/', '</p>', $content );
			$content = preg_replace( '/(<p>\s*<\/p>)+/', '', $content );
			return $content;
		}

		/**
		 * Split string by regular expression, then replace nodes by string ( [wrapper string]node content[/wrapper string] )
		 *
		 * @param type $pattern
		 * @param type $content
		 * @param type $content_flag
		 * @param type $append_
		 * @return type string
		 */
		private static function wrap_content( $pattern, $content, $content_flag, $append_ ) {
			$nodes      = preg_split( $pattern, $content, -1, PREG_SPLIT_OFFSET_CAPTURE );
			$idx_change = 0;
			foreach ( $nodes as $node ) {
				$replace   = $node[0];
				$empty_str = self::check_empty_( $content );
				if ( strlen( trim( $replace ) ) != 0 && strlen( trim( $empty_str ) ) != 0 ) {
					$offset       = intval( $node[1] ) + $idx_change;
					$replace_html = $replace;

					$content     = substr_replace( $content, str_replace( $content_flag, $replace_html, $append_ ), $offset, strlen( $replace ) );
					$idx_change += strlen( $append_ ) - strlen( $content_flag ) - ( strlen( $replace ) - strlen( $replace_html ) );
				}
			}
			return $content;
		}

		public static function check_empty_( $content ){
			$empty_str = preg_replace( '/(<p>)+/', '', $content );
			$empty_str = preg_replace( '/(<\/p>)+/', '', $content );
			$empty_str = str_replace( '&nbsp;', '', $content );
			return $empty_str;
		}
		/**
		 * Rebuild pagebuilder from Shortcode content
		 *
		 * @param type $content
		 * @param type $column: whether this content is wrapped by a column or not
		 * @param type $refine: true only first time call
		 * @return type Pagebuilder content for Admin
		 */
		public static function do_shortcode_admin( $content = '', $column = false, $refine = false ) {
			if ( empty( $content ) )
				return '';
			// check if Free Shortcode Plugin is not installed
			global $shortcode_tags;
			if ( ! array_key_exists( 'ig_text', $shortcode_tags ) ) {
				return __( 'You have not activated <b>"IG Free Shortcodes"</b> plugin. Please activate it before using PageBuilder.', IGPBL );
			}

			$content = trim( $content );

			$content_flag = 'X';
			if ( $refine ) {
				// remove duplicator wrapper
				$row_start = '\[ig_row';
				$col_start = '\[ig_column';
				$row_end   = '\[\/ig_row\]';
				$col_end   = '\[\/ig_column\]';
				$content   = preg_replace( "/$row_start([^($row_start)|($col_start)]*)$col_start/", '[ig_row][ig_column', $content );
				$content   = preg_replace( "/$col_end([^($row_end)|($col_end)]*)$row_end/", '[/ig_column][/ig_row]', $content );

				// wrap alone shortcode ( added in Classic Editor )
				$pattern = self::shortcodes_pattern( array( 'ig_row' => '', 'ig_column' => '' ) );
				$append_ = "[ig_row][ig_column]{$content_flag}[/ig_column][/ig_row]";
				$content = self::wrap_content( $pattern, $content, $content_flag, $append_ );
			}

			// wrap alone text
			self::$pattern = self::shortcodes_pattern();

			$pattern = self::$pattern;
			$append_ = $column ? "[ig_text]{$content_flag}[/ig_text]" : "[ig_row][ig_column][ig_text]{$content_flag}[/ig_text][/ig_column][/ig_row]";
			$content = self::wrap_content( $pattern, $content, $content_flag, $append_ );

			return preg_replace_callback( self::$pattern, array( 'self', 'do_shortcode_tag' ), $content );
		}

		public static function do_shortcode_tag( $m ) {

			// allow [[foo]] syntax for escaping a tag
			if ( $m[1] == '[' && $m[6] == ']' ) {
				return substr( $m[0], 1, -1 );
			}

			$tag     = $m[2];
			$content = isset( $m[5] ) ? trim( $m[5] ) : '';
			return call_user_func( array( 'self', 'shortcode_to_pagebuilder' ), $tag, $content, $m[0], $m[3] );
		}

		/**
		 * Return html structure of shortcode in Page Builder area
		 * @param type $shortcode_name
		 * @param type $attr
		 * @param type $content
		 */
		public static function shortcode_to_pagebuilder( $shortcode_name, $content = '', $shortcode_data = '', $shortcode_params = '' ) {
			$class = IG_Pb_Helper_Shortcode::get_shortcode_class( $shortcode_name );

			if ( class_exists( $class ) ) {
				global $Ig_Pb;
				$elements = $Ig_Pb->get_elements();
				$instance = isset( $elements['element'][strtolower( $class )] ) ? $elements['element'][strtolower( $class )] : null;
				if ( ! is_object( $instance ) ) {
					$instance = new $class();
				}
				$el_title = '';
				if ( $class != 'IG_Widget' ) {
					// extract param of shortcode ( now for column )
					if ( isset( $instance->config['extract_param'] ) ) {
						parse_str( trim( $shortcode_params ), $output );
						foreach ( $instance->config['extract_param'] as $param ) {
							if ( isset( $output[$param] ) )
								$instance->params[$param] = ig_pb_remove_quotes( $output[$param] );
						}
					}

					// get content in pagebuilder of shortcode: Element Title must always first option of Content tab

					if ( isset($instance->items['content'] ) && isset( $instance->items['content'][0]) ) {
						$title = $instance->items['content'][0];
						if ( $title['role'] == 'title' ) {
							$params   = shortcode_parse_atts( $shortcode_params );
							$el_title = ! empty( $params[$title['id']] ) ? $params[$title['id']] : '';
						}
					}
				} else {
					$widget_info = IG_Pb_Helper_Shortcode::extract_widget_params( $shortcode_data );
					$el_title    = ! empty( $widget_info['title'] ) ? $widget_info['title'] : '';
					$params      = IG_Pb_Helper_Shortcode::extract_params( $shortcode_data );
					$instance->config['shortcode'] = $params['widget_id'];
					$instance->config['el_type']   = 'widget';
				}

				$shortcode_view = $instance->element_in_pgbldr( $content, $shortcode_data, $el_title );
				return $shortcode_view[0];
			}
		}

		/**
		 * Extract sub-shortcode content of a shortcode
		 *
		 * @param type $content
		 * @param type $recursion
		 * @return type
		 */
		public static function extract_sub_shortcode( $content = '', $recursion = false ) {
			if ( empty( self::$pattern ) )
				self::$pattern = self::shortcodes_pattern();
			preg_match_all( self::$pattern, $content, $out );
			if ( $recursion )
				return self::extract_sub_shortcode( $out[5][0] );
			return $out[0];
		}

		/**
		 * Merge Shortcode Content & Sub Shortcode Content
		 *
		 * @param type $shortcode_content
		 * @param type $sub_shortcode_content
		 * @return type
		 */
		public static function merge_shortcode_content( $shortcode_content, $sub_shortcode_content ) {
			if ( empty( self::$pattern ) )
				self::$pattern = self::shortcodes_pattern();
			preg_match_all( self::$pattern, $shortcode_content, $out );

			$merge_shortcode					  = array();
			$merge_shortcode['shortcode_tag']     = "[{$out[2][0]}";
			$merge_shortcode['shortcode_params']  = "{$out[3][0]}]";
			$merge_shortcode['shortcode_content'] = $sub_shortcode_content;
			$merge_shortcode['shortcode_tag_end'] = "[/{$out[2][0]}]";
			$merge_shortcode					  = implode( '', $merge_shortcode );
			return stripslashes( $merge_shortcode );
		}

		/**
		 * Extract setting params of Widget Form
		 *
		 * @param type $params
		 * @return type
		 */
		public static function extract_widget_params( $params ) {
			$params = urldecode( $params );
			$params = preg_replace( '/\[ig_widget\s([A-Za-z0-9_-]+=\"[^"\']*\")*\]/', '', $params );
			// replace: widget-pages[][title]=Pages 1 => title=Pages 1
			$params = preg_replace( '/([a-z-_])+\[\]\[([^\[\]]+)\]/', '$2', $params );
			$params = str_replace( '[/ig_widget]', '', $params );
			parse_str( $params, $instance );
			return $instance;
		}

		/**
		 * Do shortcode & Return final html output for frontend
		 *
		 * @param type $content
		 */
		public static function doshortcode_content( $ig_pagebuilder_content ) {
			// remove placeholder text which was inserted to &lt; and &gt;
			$ig_pagebuilder_content = ig_pb_remove_placeholder( $ig_pagebuilder_content, 'wrapper_append', '' );
			$ig_pagebuilder_content = preg_replace_callback( '/\[ig_widget\s([A-Za-z0-9_-]+=\"[^"\']*\")*\](.*)\[\/ig_widget\]/Us', array( 'self', 'widget_content' ), $ig_pagebuilder_content );

			$output = do_shortcode( $ig_pagebuilder_content );
			return $output;
		}

		/**
		 * Replace widget shortcode by Widget output
		 *
		 * @param type $widget_shortcode
		 * @return type
		 */
		public static function widget_content( $widget_shortcode ) {
			$widget_contents = urldecode( $widget_shortcode[0] );
			// get widget class
			$element = IG_Pb_Helper_Shortcode::extract_params( $widget_contents );
			if ( empty( $element['widget_id'] ) )
				return '';
			$widget = $element['widget_id'];
			// get widget settings parameters
			$instance = IG_Pb_Helper_Shortcode::extract_widget_params( $widget_contents );
			$args     = array( 'widget_id' => strtolower( $widget ) );
			// fix problem of woocommerce
			global $woocommerce;
			if ( isset ( $woocommerce ) && empty ( $woocommerce->query ) ) {
				$woocommerce->query = new WC_Query();
			}
			// ouput
			ob_start();
			the_widget( $widget, $instance, $args );
			$widget_content = ob_get_clean();

			return $widget_content;
		}

		/**
		 * Render HTML code for shortcode's parameter type
		 * (used in shortcode setting modal)
		 * @param string $type Type name
		 * @param string $element
		 * @return string HTML
		 */
		public static function render_parameter( $type, $element = '', $extra_params = null ) {
			$type_string = self::ucname( $type );
			$class       = 'IG_Pb_Helper_Html_' . $type_string;
			if ( class_exists( $class ) ) {
				return call_user_func( array( $class, 'render' ), $element, $extra_params );
			}
			return false;
		}

		// TODO: move this function to a common file
		public static function ucname( $string ) {
			$string = ucwords( strtolower( $string ) );

			foreach ( array( '-', '\'' ) as $delimiter ) {
				if ( strpos( $string, $delimiter ) !== false ) {
					$string = implode( $delimiter, array_map( 'ucfirst', explode( $delimiter, $string ) ) );
				}
			}
			return $string;
		}
	}

}