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
/*
 * Define a Row shortcode
 */
if ( ! class_exists( 'IG_Row' ) ) {

	class IG_Row extends IG_Pb_Layout {

		public function __construct() {
			parent::__construct();
		}

		/**
		 * DEFINE configuration information of shortcode
		 */
		function element_config() {
			$this->config['shortcode'] = strtolower( __CLASS__ );
		}

		/**
		 * contain setting items of this element ( use for modal box )
		 *
		 */
		function element_items() {
			$this->items = array(
				'Notab' => array(
					array(
						'name'    => __( 'Width', IGPBL ),
						'id'      => 'width',
						'type'    => 'radio',
						'std'     => 'boxed',
						'options' => array( 'boxed' => __( 'Boxed', IGPBL ), 'full' => __( 'Full', IGPBL ) ),
					),
					array(
						'name'       => __( 'Background', IGPBL ),
						'id'         => 'background',
						'type'       => 'select',
						'std'        => 'none',
						'options'    => array(
							'none'     => __( 'None', IGPBL ),
							'solid'    => __( 'Solid Color', IGPBL ),
							'gradient' => __( 'Gradient Color', IGPBL ),
							'pattern'  => __( 'Pattern', IGPBL ),
							'image'    => __( 'Image', IGPBL )
						),
						'has_depend' => '1',
					),
					array(
						'name' => __( 'Solid Color', IGPBL ),
						'type' => array(
							array(
								'id'           => 'solid_color_value',
								'type'         => 'text_field',
								'class'        => 'input-small',
								'std'          => '#FFFFFF',
								'parent_class' => 'combo-item',
							),
							array(
								'id'           => 'solid_color_color',
								'type'         => 'color_picker',
								'std'          => '#ffffff',
								'parent_class' => 'combo-item',
							),
						),
						'container_class' => 'combo-group',
						'dependency'      => array( 'background', '=', 'solid' ),
					),
					array(
						'name'       => __( 'Gradient Color', IGPBL ),
						'id'         => 'gradient_color',
						'type'       => 'gradient_picker',
						'std'        => '0% #FFFFFF,100% #000000',
						'dependency' => array( 'background', '=', 'gradient' ),
					),
					array(
						'id'              => 'gradient_color_css',
						'type'            => 'text_field',
						'std'             => '',
						'input-type'      => 'hidden',
						'container_class' => 'hidden',
						'dependency'      => array( 'background', '=', 'gradient' ),
					),
					array(
						'name'       => __( 'Gradient Direction', IGPBL ),
						'id'         => 'gradient_direction',
						'type'       => 'select',
						'std'        => 'vertical',
						'options'    => array( 'vertical' => __( 'Vertical', IGPBL ), 'horizontal' => __( 'Horizontal', IGPBL ) ),
						'dependency' => array( 'background', '=', 'gradient' ),
					),
					array(
						'name'       => __( 'Pattern', IGPBL ),
						'id'         => 'pattern',
						'type'       => 'select_media',
						'std'        => '',
						'class'      => 'jsn-input-large-fluid',
						'dependency' => array( 'background', '=', 'pattern' ),
					),
					array(
						'name'    => __( 'Repeat', IGPBL ),
						'id'      => 'repeat',
						'type'    => 'radio_button_group',
						'std'     => 'full',
						'options' => array(
							'full'       => __( 'Full', IGPBL ),
							'vertical'   => __( 'Vertical', IGPBL ),
							'horizontal' => __( 'Horizontal', IGPBL ),
						),
						'dependency' => array( 'background', '=', 'pattern' ),
					),
					array(
						'name'       => __( 'Image', IGPBL ),
						'id'         => 'image',
						'type'       => 'select_media',
						'std'        => '',
						'class'      => 'jsn-input-large-fluid',
						'dependency' => array( 'background', '=', 'image' ),
					),
					array(
						'name'    => __( 'Stretch', IGPBL ),
						'id'      => 'stretch',
						'type'    => 'radio_button_group',
						'std'     => 'none',
						'options' => array(
							'none'    => __( 'None', IGPBL ),
							'full'    => __( 'Full', IGPBL ),
							'cover'   => __( 'Cover', IGPBL ),
							'contain' => __( 'Contain', IGPBL ),
						),
						'dependency' => array( 'background', '=', 'pattern' ),
					),
					array(
						'name'       => __( 'Position', IGPBL ),
						'id'         => 'position',
						'type'       => 'radio',
						'label_type' => 'image',
						'dimension'  => array( 23, 23 ),
						'std'        => 'center center',
						'options'    => array(
							'left top'      => array( 'left top' ),
							'center top'    => array( 'center top' ),
							'right top'     => array( 'right top', 'linebreak' => true ),
							'left center'   => array( 'left center' ),
							'center center' => array( 'center center' ),
							'right center'  => array( 'right center', 'linebreak' => true ),
							'left bottom'   => array( 'left bottom' ),
							'center bottom' => array( 'center bottom' ),
							'right bottom'  => array( 'right bottom' ),
						),
						'dependency' => array( 'background', '=', 'image' ),
					),
					array(
						'name'       => __( 'Enable Paralax', IGPBL ),
						'id'         => 'paralax',
						'type'       => 'radio',
						'std'        => 'no',
						'options'    => array( 'yes' => __( 'Yes', IGPBL ), 'no' => __( 'No', IGPBL ) ),
						'dependency' => array( 'background', '=', 'pattern__#__image' ),
					),
					array(
						'name' => __( 'Border', IGPBL ),
						'type' => array(
							array(
								'id'           => 'border_width_value_',
								'type'         => 'text_append',
								'type_input'   => 'number',
								'class'        => 'input-mini',
								'std'          => '0',
								'append'       => 'px',
								'validate'     => 'number',
								'parent_class' => 'combo-item',
							),
							array(
								'id'           => 'border_style',
								'type'         => 'select',
								'class'        => 'input-medium',
								'std'          => 'solid',
								'options'      => IG_Pb_Helper_Type::get_border_styles(),
								'parent_class' => 'combo-item',
							),
							array(
								'id'           => 'border_color',
								'type'         => 'color_picker',
								'std'          => '#000',
								'parent_class' => 'combo-item',
							),
						),
						'container_class' => 'combo-group',
					),
					array(
						'name'               => __( 'Padding', IGPBL ),
						'container_class'    => 'combo-group',
						'id'                 => 'div_padding',
						'type'               => 'margin',
						'extended_ids'       => array( 'div_padding_top', 'div_padding_bottom', 'div_padding_right', 'div_padding_left' ),
						'div_padding_top'    => array( 'std' => '10' ),
						'div_padding_bottom' => array( 'std' => '10' ),
						'div_padding_right'  => array( 'std' => '10' ),
						'div_padding_left'   => array( 'std' => '10' ),
					),
				)
			);
		}

		/**
		 *
		 * @param type $content		: inner shortcode elements of this row
		 * @param type $shortcode_data : not used
		 * @return string
		 */
		public function element_in_pgbldr( $content = '', $shortcode_data = '' ) {
			if ( empty($content) ) {
				$column = new IG_Column();
				$column_html = $column->element_in_pgbldr();
				$column_html = $column_html[0];
			} else {
				$column_html = IG_Pb_Helper_Shortcode::do_shortcode_admin( $content );
			}
			if ( empty($shortcode_data) )
				$shortcode_data = $this->config['shortcode_structure'];
			// remove [/ig_row][ig_column...] from $shortcode_data
			$shortcode_data = explode( '][', $shortcode_data );
			$shortcode_data = $shortcode_data[0] . ']';
			$custom_style   = ig_pb_get_placeholder( 'custom_style' );
			$row[] = '<div class="jsn-row-container ui-sortable row-fluid shortcode-container" ' . $custom_style . '>
							<textarea class="hidden" data-sc-info="shortcode_content" name="shortcode_content[]" >' . $shortcode_data . '</textarea>
							<div class="jsn-iconbar left">
								<a href="javascript:void(0);" title="' . __( 'Move Up', IGPBL ) . '" class="jsn-move-up disabled"><i class="icon-chevron-up"></i></a>
								<a href="javascript:void(0);" title="' . __( 'Move Down', IGPBL ) . '" class="jsn-move-down disabled"><i class=" icon-chevron-down"></i></a>
							</div>
							<div class="ig-row-content">
							' . $column_html . '
							</div>
							<div class="jsn-iconbar jsn-vertical">
								<a href="javascript:void(0);" class="add-container" title="' . __( 'Add column', IGPBL ) . '"><i class="icon-plus"></i></a>
								<a href="javascript:void(0);" title="Edit row" data-shortcode="' . $this->config['shortcode'] . '" class="element-edit row"><i class="icon-pencil"></i></a>
								<a href="javascript:void(0);" class="item-delete row" title="' . __( 'Delete row', IGPBL ) . '"><i class="icon-trash"></i></a>
							</div>
							<textarea class="hidden" name="shortcode_content[]" >[/' . $this->config['shortcode'] . ']</textarea>
						</div>';
			return $row;
		}

		/**
		 * get params & structure of shortcode
		 */
		public function shortcode_data() {
			$this->config['params'] = IG_Pb_Helper_Shortcode::generate_shortcode_params( $this->items, null, null, false, true );
			$this->config['shortcode_structure'] = IG_Pb_Helper_Shortcode::generate_shortcode_structure( $this->config['shortcode'], $this->config['params'] );
		}

		/**
		 * define shortcode structure of element
		 */
		function element_shortcode( $atts = null, $content = null ) {
			$extra_class = $style = $common_style = '';
			if ( isset( $atts ) && is_array( $atts ) ) {
				$arr_styles = array();

				switch ( $atts['width'] ) {
					case 'full':
						$extra_class = 'ig_fullwidth';
						// some overwrite css to enable row full width
						$common_style = '<style>
						*{-webkit-box-sizing: inherit;-moz-box-sizing: inherit;-ms-box-sizing: inherit;-o-box-sizing: inherit;box-sizing: inherit;}
						#page, #content, .ig_fullwidth{-webkit-box-sizing: inherit!important;-moz-box-sizing: inherit!important;-ms-box-sizing: inherit!important;-o-box-sizing: inherit!important;box-sizing: inherit!important;}
						#page{max-width: 100%!important;overflow: hidden;border: none;margin: 0 auto;padding: 0;}
						</style>';

						$arr_styles[] = 'width: 100%;padding-left: 1000px!important;padding-right: 1000px!important;margin-left: -1000px;';
						break;
					case 'boxed':
						///$arr_styles[] = "width: 100%;";
						break;
				}
				$background = '';
				switch ( $atts['background'] ) {
					case 'none':
						if ( $atts['width'] == 'full' )
							$background = 'background: none;';
						break;
					case 'solid':
						$solid_color = $atts['solid_color_value'];
						$background  = "background-color: $solid_color;";
						break;
					case 'gradient':
						$background = $atts['gradient_color_css'];
						break;
					case 'pattern':
						$pattern_img     = $atts['pattern'];
						$pattern_repeat  = $atts['repeat'];
						$pattern_stretch = $atts['stretch'];
						$background = "background-image:url(\"$pattern_img\");";
						switch ( $pattern_repeat ) {
							case 'full':
								$background_repeat = 'repeat';
								break;
							case 'vertical':
								$background_repeat = 'repeat-y';
								break;
							case 'horizontal':
								$background_repeat = 'repeat-x';
								break;
						}
						$background .= "background-repeat:$background_repeat;";

						switch ( $pattern_stretch ) {
							case 'none':
								$background_size = '';
								break;
							case 'full':
								$background_size = '100% 100%';
								break;
							case 'cover':
								$background_size = 'cover';
								break;
							case 'contain':
								$background_size = 'contain';
								break;
						}
						$background .= ! empty( $background_size ) ? "background-size:$background_size;" : '';

						break;
					case 'image':
						$image = $atts['image'];
						$image_position = $atts['position'];

						$background = "background-image:url(\"$image\");background-position:$image_position;";
						break;
				}
				$arr_styles[] = $background;

				if ( isset( $atts['paralax']) && $atts['paralax'] == 'yes' )
					$arr_styles[] = 'background-attachment:fixed;';

				if ( isset( $atts['border_width_value_'] ) && intval( $atts['border_width_value_'] ) ) {
					$border       = array();
					$border[]     = $atts['border_width_value_'] . 'px';
					$border[]     = $atts['border_style'];
					$border[]     = $atts['border_color'];
					$border       = implode( ' ', $border );
					$arr_styles[] = "border-top:$border; border-bottom:$border;";
				}

				$arr_styles[] = "padding-top:{$atts['div_padding_top']}px;";
				$arr_styles[] = "padding-bottom:{$atts['div_padding_bottom']}px;";
				$arr_styles[] = "padding-left:{$atts['div_padding_left']}px;";
				$arr_styles[] = "padding-right:{$atts['div_padding_right']}px;";

				$arr_styles = implode( '', $arr_styles );
				$style = ! empty( $arr_styles ) ? "style='$arr_styles'" : '';
			}

			return $common_style . "<div class='jsn-bootstrap'>" . "<div class='row $extra_class' $style>" . IG_Pb_Helper_Shortcode::remove_autop( $content ) . '</div>' . '</div>';
		}

	}

}