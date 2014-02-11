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
if ( ! class_exists( 'IG_Pb_Modal' ) ) {

	class IG_Pb_Modal {

		private static $instance;

		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			add_filter( 'ig_pb_assets_register', array( &$this, 'apply_assets' ) );
			$this->enqueue_admin_style();
			$this->enqueue_admin_script();
			add_action( 'ig_pb_assets_localize', array( &$this, 'ig_localize' ) );
		}

		public function apply_assets( $assets ){
			$assets['ig-pb-handlesetting-js'] = array(
				'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/js/handle_setting.js',
				'ver' => '1.0.0',
			);
			if ( IG_Pb_Helper_Functions::is_preview() ) {
				$assets['ig-pb-frontend-css'] = array(
					'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/css/front_end.css',
					'ver' => '1.0.0',
				);
			}
			$assets['ig-pb-modal-css'] = array(
				'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/css/modal.css',
				'ver' => '1.0.0',
			);

			$assets = apply_filters( 'ig_pb_assets_register_modal', $assets );

			return $assets;
		}

		public function enqueue_admin_script() {
			IG_Pb_Helper_Functions::enqueue_scripts();

			wp_enqueue_media();

			IG_Pb_Helper_Functions::enqueue_scripts_end();

			IG_Pb_Assets::load( array( 'ig-pb-jquery-fancybox-js', 'ig-pb-placeholder' ) );
		}

		public function enqueue_admin_style() {
			IG_Pb_Helper_Functions::enqueue_styles();
			IG_Pb_Assets::load( array( 'ig-pb-jquery-tipsy-css', 'ig-pb-modal-css', 'ig-pb-jquery-fancybox-css' ) );
			if ( IG_Pb_Helper_Functions::is_preview() ) {
				IG_Pb_Assets::load( array( 'ig-pb-frontend-css' ) );
			}
		}

		public function ig_localize( $load ) {
			wp_localize_script( 'ig-pb-handlesetting-js', 'Ig_Ajax', IG_Pb_Helper_Functions::localize_js() );
		}

		public function preview_modal( $page = '' ) {
			add_action( 'ig_pb_modal_page_content', array( &$this, 'content' . $page ), 10 );
		}

		public function content() {
			include IG_PB_TPL_PATH . '/modal.php';

			// load last assets: HandleSettings & hooked assets
			$assets = apply_filters( 'ig_pb_assets_enqueue_modal', array( 'ig-pb-handlesetting-js', ) );
			IG_Pb_Assets::load( $assets );
		}

		public function content_layout() {
			include IG_PB_TPL_PATH . '/layout-list.php';

			// load last assets: HandleSettings & hooked assets
			$assets = apply_filters( 'ig_pb_assets_enqueue_modal', array( 'ig-pb-handlesetting-js', ) );
			IG_Pb_Assets::load( $assets );
		}

		/**
		 * Ignore settings key in array
		 * @param type $options
		 * @return type
		 */
		static function ignore_settings( $options ) {
			if ( array_key_exists( 'settings', $options ) ) {
				$options = array_slice( $options, 1 );
			}
			return $options;
		}

		/**
		 * Add setting data to a tag
		 *
		 * @param type $tag
		 * @param type $data
		 * @param type $content
		 * @return type
		 */
		static function tab_settings( $tag, $data, $content ) {
			$tag_data = array();
			if ( ! empty( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( ! empty( $value ) )
						$tag_data[] = "$key = '$value'";
				}
			}
			$tag_data = implode( ' ', $tag_data );
			return "<$tag $tag_data>$content</$tag>";
		}

		/**
		 * get HTML of Modal Settings Box of Shortcode
		 * @param type $options
		 * @return type
		 */
		static function get_shortcode_modal_settings( $settings, $shortcode = '', $input_params = null ) {
			$i    = 0;
			$tabs = $contents = $actions = $general_actions = array();
			foreach ( $settings as $tab => $options ) {
				$options = self::ignore_settings( $options );
				if ( $tab == 'action' ) {
					foreach ( $options as $option ) {
						$actions[] = IG_Pb_Helper_Shortcode::render_parameter( $option['type'], $option );
					}
				} else if ( $tab == 'generalaction' ) {
					foreach ( $options as $option ) {
						$option['id'] = isset( $option['id'] ) ? ( 'param-' . $option['id'] ) : '';
						$general_actions[] = IG_Pb_Helper_Shortcode::render_parameter( $option['type'], $option );
					}
				} else {
					$active = ( $i++ == 0 ) ? 'active' : '';
					if ( $tab != 'Notab' ) {
						$data_ = isset( $settings[$tab]['settings'] ) ? $settings[$tab]['settings'] : array();
						$data_['href'] = "#$tab";
						$data_['data-toggle'] = 'tab';
						$content_ = ucfirst( $tab );
						$tabs[] = "<li class='$active'>" . self::tab_settings( 'a', $data_, $content_ ) . '</li>';
					}

					$has_margin = 0;
					$param_html = array();
					foreach ( $options as $idx => $option ) {
						// check if this element has Margin param (1)
						if ( isset( $option['name'] ) && $option['name'] == __( 'Margin', IGPBL ) && $option['id'] != 'div_margin' )
							$has_margin = 1;
						// if (1), don't use the 'auto extended margin ( top, bottom ) option'
						if ( $has_margin && isset( $option['id'] ) && $option['id'] == 'div_margin' )
							continue;

						$type = $option['type'];
						$option['id'] = isset( $option['id'] ) ? ( 'param-' . $option['id'] ) : "$idx";
						if ( ! is_array( $type ) ) {
							$param_html[$option['id']] = IG_Pb_Helper_Shortcode::render_parameter( $type, $option, $input_params );
						} else {
							$output_inner = '';
							foreach ( $type as $sub_options ) {
								$sub_options['id'] = isset( $sub_options['id'] ) ? ( 'param-' . $sub_options['id'] ) : '';
								/* for sub option, auto assign bound = 0 {not wrapped by <div class='controls'></div> } */
								$sub_options['bound'] = '0';
								/* for sub option, auto assign 'input-small' class */
								$sub_options['class'] = isset( $sub_options['class'] ) ? ( $sub_options['class'] ) : '';
								$type = $sub_options['type'];
								$output_inner .= IG_Pb_Helper_Shortcode::render_parameter( $type, $sub_options );
							}
							$option = IG_Pb_Helper_Html::get_extra_info( $option );
							$label = IG_Pb_Helper_Html::get_label( $option );
							$param_html[$option['id']] = IG_Pb_Helper_Html::final_element( $option, $output_inner, $label );
						}
					}
					if ( ! empty ( $param_html['param-div_margin'] ) ) {
						$margin = $param_html['param-div_margin'];
						array_pop( $param_html );
						// move "auto extended margin ( top, bottom ) option" to top of output
						$preview    = array_shift( $param_html );
						$param_html = array_merge(
							array(
								$preview,
								$margin,
							),
							$param_html
						);
					}

					$param_html  = implode( '', $param_html );
					$content_tab = "<div class='tab-pane $active' id='$tab'>$param_html</div>";
					$contents[]  = $content_tab;
				}
			}

			return self::setting_tab_html( $shortcode, $tabs, $contents, $general_actions, $settings, $actions );
		}

		/**
		 * generate tab with content, use for generating Modal
		 * @return string
		 */
		static function setting_tab_html( $shortcode, $tabs, $contents, $general_actions, $settings, $actions ){
			$output = '<input type="hidden" value="' . $shortcode . '" id="shortcode_name" name="shortcode_name" />';

			/* Tab Content - Styling */

			$output .= '<div class="jsn-tabs">';
			if ( count( $tabs ) > 0 ) {
				$output .= '<ul class="" id="ig_option_tab">';
				$output .= implode( '', $tabs );
				$output .= '</ul>';
			}
			/* Tab Content */

			$output .= implode( '', $contents );

			$output .= "<div class='jsn-buttonbar ig_action_btn'>";

			/* Tab Content - General actions */
			if ( count( $general_actions ) ) {
				$data_    = $settings['generalaction']['settings'];
				$content_ = implode( '', $general_actions );
				$output  .= self::tab_settings( 'div', $data_, $content_ );
			}

			$output .= implode( '', $actions );
			$output .= '</div>';
			$output .= '</div>';

			return $output;
		}

	}

}