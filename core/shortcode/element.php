<?php
/**
 * @version	$Id$
 * @package	IG Pagebuilder
 * @author	 InnoGears Team <support@www.innogears.com>
 * @copyright  Copyright (C) 2012 www.innogears.com. All Rights Reserved.
 * @license	GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.www.innogears.com
 * Technical Support:  Feedback - http://www.www.innogears.com
 */
/*
 * Parent class for normal elements
 */

class IG_Pb_Shortcode_Element extends IG_Pb_Shortcode_Common {

	public function __construct() {
		$this->type = 'element';
		$this->config['el_type'] = 'element';

		$this->element_config();
		$this->element_items();
		$this->element_items_extra();
		$this->shortcode_data();

		// add shortcode
		add_shortcode( $this->config['shortcode'], array( &$this, 'element_shortcode' ) );

		// enqueue script for current element in backend (modal setting iframe)
		if ( IG_Pb_Helper_Functions::is_modal_of_element( $this->config['shortcode'] ) ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts_modal' ) );
		}
		// enqueue script for current element in backend (preview iframe)
		if ( IG_Pb_Helper_Functions::is_preview() ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts_frontend' ) );
		}

		do_action( 'ig_pb_element_init' );

		$prefix = IG_Pb_Helper_Functions::is_preview() ? 'admin' : 'wp';
		add_action( "{$prefix}_footer", array( &$this, 'custom_scripts_frontend' ) );
		add_filter( 'custom_scripts_frontend_filter', array( &$this, 'custom_scripts_frontend_filter' ) );
	}

	// custom scripts for frontend
	public function custom_scripts_frontend() {

	}

	// custom filter for scripts for frontend
	public function custom_scripts_frontend_filter( $scripts ) {
		return $scripts;
	}

	// enqueue scripts for frontend
	public function enqueue_scripts_frontend() {
		IG_Pb_Helper_Functions::shortcode_enqueue_js( $this, 'require_frontend_js', '_frontend' );
	}

	// enqueue scripts for modal setting iframe
	public function enqueue_scripts_modal( $hook ) {
		IG_Pb_Helper_Functions::shortcode_enqueue_js( $this, 'require_js', '' );
	}

	// define configuration information of shortcode
	public function element_config() {

	}

	// define setting options of shortcode
	public function element_items() {

	}

	// add more options to all elements
	public function element_items_extra() {

		$disable_el = array(
			'name' => __( 'Disable', IGPBL ),
			'id' => 'disabled_el',
			'type' => 'radio',
			'std' => 'no',
			'options' => array( 'yes' => __( 'Yes', IGPBL ), 'no' => __( 'No', IGPBL ) ),
			'wrap_class' => 'control-group hidden',
		);
		$css_suffix = array(
			'name'    => __( 'Css Class suffix', IGPBL ),
			'id'      => 'css_suffix',
			'type'    => 'text_field',
			'std'     => __( '', IGPBL ),
			'tooltip' => __( 'Add custom css class for the wrapper div of this element', IGPBL )
		);
		if ( isset ( $this->items['styling'] ) ) {
			$this->items['styling'] = array_merge(
				$this->items['styling'], array(
					$css_suffix,
					$disable_el,
					// always at the end of array
					array(
						'name'			=> __( 'Margin', IGPBL ),
						'container_class' 	=> 'combo-group',
						'id'			=> 'div_margin',
						'type'			=> 'margin',
						'extended_ids'	=> array( 'div_margin_top', 'div_margin_bottom' ),
						'div_margin_top'	=> array( 'std' => '' ),
						'div_margin_bottom'	=> array( 'std' => '' ),
						'margin_elements'	=> 't, b',
						'tooltip' 			=> __( 'Set margin size', 	IGPBL )
					),
				)
			);
		} else {
			if ( isset ( $this->items['Notab'] ) ) {
				$this->items['Notab'] = array_merge(
					$this->items['Notab'], array(
						$css_suffix,
						$disable_el,

					)
				);
			}
		}
	}

	/**
	 * DEFINE html structure of shortcode in Page Builder area
	 *
	 * @param type $content
	 * @param type $shortcode_data: string stores params (which is modified default value) of shortcode
	 * @param type $el_title: Element Title used to identifying elements in Pagebuilder
	 * Ex:  param-tag=h6&param-text=Your+heading&param-font=custom&param-font-family=arial
	 * @return type
	 */
	public function element_in_pgbldr( $content = '', $shortcode_data = '', $el_title = '' ) {
		$shortcode		  = $this->config['shortcode'];
		$is_sub_element   = ( isset( $this->config['sub_element'] ) ) ? true : false;
		$parent_shortcode = ( $is_sub_element ) ? str_replace( 'ig_item_', '', $shortcode ) : $shortcode;
		$type			  = ! empty( $this->config['el_type'] ) ? $this->config['el_type'] : 'widget';

		// Empty content if this is not sub element
		if ( ! $is_sub_element )
			$content = '';

		$exception   = isset( $this->config['exception'] ) ? $this->config['exception'] : array();
		$content     = ( isset( $exception['default_content'] ) ) ? $exception['default_content'] : $content;
		$modal_title = '';
		// if is widget
		if ( $type == 'widget' ) {
			global $Ig_Pb_Widgets;
			if ( isset( $Ig_Pb_Widgets[$shortcode] ) && is_array( $Ig_Pb_Widgets[$shortcode] ) && isset( $Ig_Pb_Widgets[$shortcode]['identity_name'] ) ) {
				$modal_title = $Ig_Pb_Widgets[$shortcode]['identity_name'];
				$content     = $this->config['exception']['data-modal-title'] = $modal_title;
			}
		}

		// if content is still empty, Generate it
		if ( empty( $content ) ) {
			if ( ! $is_sub_element )
				$content = ucfirst( str_replace( 'ig_', '', $shortcode ) );
			else {
				if ( isset( $exception['item_text'] ) ) {
					if ( ! empty( $exception['item_text'] ) )
						$content = IG_Pb_Utils_Placeholder::add_placeholder( $exception['item_text'] . ' %s', 'index' );
				} else
					$content = IG_Pb_Utils_Placeholder::add_placeholder( ( __( ucfirst( $parent_shortcode ), IGPBL ) . ' ' . __( 'Item', IGPBL ) ) . ' %s', 'index' );
			}
		}
		$content = ! empty( $el_title ) ? ( $content . ': ' . "<span>$el_title</span>" ) : $content;

		// element name
		if ( $type == 'element' ) {
			if ( ! $is_sub_element )
				$name = ucfirst( str_replace( 'ig_', '', $shortcode ) );
			else
				$name = __( ucfirst( $parent_shortcode ), IGPBL ) . ' ' . __( 'Item', IGPBL );
		}
		else {
			$name = $content;
		}
		if ( empty($shortcode_data) )
			$shortcode_data = $this->config['shortcode_structure'];

		$shortcode_data  = stripslashes( $shortcode_data );
		$element_wrapper = ! empty( $exception['item_wrapper'] ) ? $exception['item_wrapper'] : ( $is_sub_element ? 'li' : 'div' );
		$content_class   = ( $is_sub_element ) ? 'jsn-item-content' : 'ig-pb-element';
		$modal_title     = empty ( $modal_title ) ? ( ! empty( $exception['data-modal-title'] ) ? "data-modal-title='{$exception['data-modal-title']}'" : '' ) : $modal_title;
		$element_type    = "data-el-type='$type'";

		$data = array(
			'element_wrapper' => $element_wrapper,
			'modal_title' => $modal_title,
			'element_type' => $element_type,
			'name' => $name,
			'shortcode' => $shortcode,
			'shortcode_data' => $shortcode_data,
			'content_class' => $content_class,
			'content' => $content,
			'action_btn' => empty( $exception['action_btn'] ) ? '' : $exception['action_btn'],
		);
		$extra = array();
		if ( isset( $this->config['exception']['disable_preview_container'] ) ) {
			$extra = array(
				'has_preview' => FALSE,
			);
		}
		$data = array_merge( $data, $extra );
		$html_preview = IG_Pb_Helper_Functions::get_element_item_html( $data );
		return array(
			$html_preview
		);
	}

	/**
	 * DEFINE shortcode content
	 *
	 * @param type $atts
	 * @param type $content
	 */
	public function element_shortcode_full( $atts = null, $content = null ) {

	}

	/**
	 * return shortcode content: if shortcode is disable, return empty
	 *
	 * @param type $atts
	 * @param type $content
	 */
	public function element_shortcode( $atts = null, $content = null ) {
		$arr_params = ( shortcode_atts( $this->config['params'], $atts ) );
		if ( $arr_params['disabled_el'] == 'yes' ) {
			if ( IG_Pb_Helper_Functions::is_preview() ) {
				return ''; //_e( 'This element is deactivated. It will be hidden at frontend', IGPBL );
			}
			return '';
		}

		// enqueue script for current element in frontend
		add_action( 'wp_footer', array( &$this, 'enqueue_scripts_frontend' ), 1 );
		// get full shortcode content
		return $this->element_shortcode_full( $atts, $content );
	}

	/**
	 * Wrap output html of a shortcode
	 *
	 * @param type $arr_params
	 * @param type $html_element
	 * @param type $extra_class
	 * @return type
	 */
	public function element_wrapper( $html_element, $arr_params, $extra_class = '', $custom_style = '' ) {
		$shortcode_name = IG_Pb_Helper_Shortcode::shortcode_name( $this->config['shortcode'] );
		// extract margin here then insert inline style to wrapper div
		$styles = array();
		if ( ! empty ( $arr_params['div_margin_top'] ) ) {
			$styles[] = 'margin-top:' . intval( $arr_params['div_margin_top'] ) . 'px';
		}
		if ( ! empty ($arr_params['div_margin_bottom'] ) ) {
			$styles[] = 'margin-bottom:' . intval( $arr_params['div_margin_bottom'] ) . 'px';
		}
		$style = count( $styles ) ? implode( '; ', $styles ) : '';
		if ( ! empty( $style ) || ! empty( $custom_style ) ){
			$style = "style='$style $custom_style'";
		}
		$class  = "ig-element-container ig-element-$shortcode_name";
		$extra_class .= ! empty ( $arr_params['css_suffix'] ) ? ' ' . esc_attr( $arr_params['css_suffix'] ) : '';
		$class .= ! empty ( $extra_class ) ? ' ' . ltrim( $extra_class, ' ' ) : '';
		return "<div class='$class' $style>" . $html_element . '</div>';
	}

	/**
	 * DEFINE html structure of shortcode in "Select Elements" Modal
	 *
	 * @param type $sort
	 * @return type
	 */
	public function element_button( $sort ) {
		$type   = 'element';
		$extra_ = ( $sort > 0 ) ? "data-value='" . strtolower( $this->config['name'] ) . "' data-type = '" . ( $type ) . "'" : '';
		return self::el_button( $extra_, $this->config );
	}

	public static function el_button( $extra_, $config ) {
		$icon = '';//isset($config['icon']) ? '<i class="jsn-icon16 icon-formfields jsn-' . $config['icon'] . '"></i>' : '';
		return '<li class="jsn-item" ' . $extra_ . '>
					<button data-shortcode="' . $config['shortcode'] . '" class="shortcode-item btn">
						' . $icon . $config['name'] . '
					</button>
				</li>';
	}

	// get params & structure of shortcode
	public function shortcode_data() {
		$params = IG_Pb_Helper_Shortcode::generate_shortcode_params( $this->items, null, null, false, true );
		// add Margin parameter for Not child shortcode
		if ( strpos( $this->config['shortcode'], '_item' ) === false ) {
			$this->config['params'] = array_merge( array( 'div_margin_top' => '', 'div_margin_bottom' => '', 'disabled_el' => 'no', 'css_suffix' => '' ), $params );
		}
		else {
			$this->config['params'] = $params;
		}
		$this->config['shortcode_structure'] = IG_Pb_Helper_Shortcode::generate_shortcode_structure( $this->config['shortcode'], $this->config['params'] );
	}

}
