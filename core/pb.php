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
/**
 * Core initialization class of IG Pb Plugin.
 *
 * @package  IG_Pb_Plugin
 * @since	1.0.0
 */
class IG_Pb {

	/**
	 * IG Pb Plugin's custom post type slug.
	 *
	 * @var  string
	 */
	private $ig_elements;

	/**
	 * Constructor.
	 *
	 * @return  void
	 */
	function __construct() {
		$this->ig_elements = array();
		$this->register_element();
		$this->register_widget();
		$this->custom_hook();
		$this->load_assets();
	}

	/**
	 * Get array of shortcode elements
	 * @return type
	 */
	function get_elements() {
		return $this->ig_elements;
	}

	/**
	 * Add shortcode element
	 * @param type $type: type of element ( element/layout )
	 * @param type $class: name of class
	 * @param type $element: instance of class
	 */
	function set_element( $type, $class, $element = null ) {
		if ( empty( $element ) )
			$this->ig_elements[$type][strtolower( $class )] = new $class();
		else
			$this->ig_elements[$type][strtolower( $class )] = $element;
	}

	/**
	 * Pagebuilder custom hook
	 */
	function custom_hook() {
		// translation
		add_action( 'init', array( &$this, 'translation' ) );
		// register modal page
		add_action( 'admin_init', array( &$this, 'modal_register' ) );
		add_action( 'admin_init', array( &$this, 'widget_register_assets' ) );
		add_action( 'admin_init', array( &$this, 'remove_admin_assets' ), 900000 );
		add_action( 'admin_footer', array( &$this, 'remove_admin_assets_footer' ), 10 );
		add_action( 'admin_menu', array( &$this, 'add_ig_modal_page' ) );
		// enable shortcode in content & filter content with IGPB shortcodes
		add_filter( 'the_content', 'do_shortcode' );
		add_filter( 'the_content', array( &$this, 'pagebuilder_to_frontend' ) );
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
		// enqueue js for front-end
		add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_scripts' ) );
		// hook saving post
		add_action( 'edit_post', array( &$this, 'save_pagebuilder_content' ) );
		add_action( 'save_post', array( &$this, 'save_pagebuilder_content' ) );
		add_action( 'publish_post', array( &$this, 'save_pagebuilder_content' ) );
		add_action( 'edit_page_form', array( &$this, 'save_pagebuilder_content' ) );
		add_action( 'pre_post_update', array( &$this, 'save_pagebuilder_content' ) );
		// ajax action
		add_action( 'wp_ajax_save_layout', array( &$this, 'save_layout' ) );
		add_action( 'wp_ajax_update_whole_sc_content', array( &$this, 'update_whole_sc_content' ) );
		add_action( 'wp_ajax_shortcode_extract_param', array( &$this, 'shortcode_extract_param' ) );
		add_action( 'wp_ajax_get_json_custom', array( &$this, 'ajax_json_custom' ) );
		add_action( 'wp_ajax_get_shortcode_tpl', array( &$this, 'get_shortcode_tpl' ) );
		add_action( 'wp_ajax_text_to_pagebuilder', array( &$this, 'text_to_pagebuilder' ) );
		add_action( 'wp_ajax_get_html_content', array( &$this, 'get_html_content' ) );
		// preview shortcode
		add_action( 'wp_after_admin_bar_render', array( &$this, 'shortcode_iframe_preview' ) );
		// add IGPB metabox
		add_action( 'add_meta_boxes', array( &$this, 'custom_meta_boxes' ) );
		// print html template of shortcodes
		add_action( 'admin_footer', array( &$this, 'element_tpl' ) );
		add_filter( 'tiny_mce_before_init', array( &$this, 'tinymce_before_init' ) );
		add_filter( 'wp_handle_upload_prefilter', array( &$this, 'media_file_name' ), 100 );
		// add IGPB button to Wordpress TinyMCE
		add_filter( 'mce_buttons', array( &$this, 'filter_mce_button' ) );
		add_filter( 'mce_external_plugins', array( &$this, 'filter_mce_plugin' ) );

		// Remove Gravatar from Ig Modal Pages
		if ( is_admin() ) {
			add_filter( 'bp_core_fetch_avatar', array( &$this, 'remove_gravatar' ), 1, 9 );
			add_filter( 'get_avatar', array( &$this, 'get_gravatar' ), 1, 5 );
		}
		// add body class in backend
		add_filter( 'admin_body_class', array( &$this, 'admin_bodyclass' ) );
		// get image size
		add_filter( 'ig_pb_get_json_image_size', array( &$this, 'get_image_size' ) );
		// Editor hook before & after
		add_action( 'edit_form_after_title', array( &$this, 'hook_after_title' ) );
		add_action( 'edit_form_after_editor', array( &$this, 'hook_after_editor' ) );
		// Frontend hook
		add_filter( 'body_class', array( &$this, 'wp_bodyclass' ) );
		add_action( 'wp_head', array( &$this, 'post_view' ) );


		do_action( 'ig_pb_custom_hook' );
	}

	// Translation
	function translation() {
		load_plugin_textdomain( IGPBL, false, dirname( plugin_basename( IG_PB_FILE ) ) . '/languages/' );
	}

	/**
	 * Register custom asset files
	 * @param type $assets
	 * @return string
	 */
	function apply_assets( $assets ) {
		$assets['ig-pb-frontend-css'] = array(
			'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/css/front_end.css',
			'ver' => '1.0.0',
		);
		if ( ! is_admin() || IG_Pb_Helper_Functions::is_preview() ) {
			$assets['ig-pb-bootstrap-css'] = array(
				'src' => IG_Pb_Helper_Functions::path( 'assets/3rd-party/bootstrap3' ) . '/css/bootstrap.min.css',
				'ver' => '3.0.2',
			);
			$assets['ig-pb-bootstrap-js'] = array(
				'src' => IG_Pb_Helper_Functions::path( 'assets/3rd-party/bootstrap3' ) . '/js/bootstrap.min.js',
				'ver' => '3.0.2',
			);
		}
		$assets['ig-pb-frontend-responsive-css'] = array(
			'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/css/front_end_responsive.css',
			'ver' => '1.0.0',
		);
		$assets['ig-pb-addpanel-js'] = array(
			'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/js/add_page_builder.js',
			'ver' => '1.0.0',
		);
		$assets['ig-pb-layout-js'] = array(
			'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/js/layout.js',
			'ver' => '1.0.0',
		);
		$assets['ig-pb-widget-js'] = array(
			'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/js/widget.js',
			'ver' => '1.0.0',
		);
		$assets['ig-pb-placeholder'] = array(
			'src' => IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/js/placeholder.js',
			'ver' => '1.0.0',
		);
		return $assets;
	}

	/**
	 * Enqueue scripts & style for FRONT END
	 */
	function frontend_scripts() {
		// load css
		$ig_pb_frontend_css = array( 'ig-pb-font-icomoon-css', 'ig-pb-joomlashine-css', 'ig-pb-frontend-css', 'ig-pb-frontend-responsive-css', 'ig-pb-jquery-tipsy-css', 'ig-pb-jquery-fancybox-css' );
		IG_Pb_Assets::load( $ig_pb_frontend_css );
		// load js
		$ig_pb_frontend_js = array( 'jquery', 'jquery-ui', 'ig-pb-bootstrap-js', 'ig-pb-jquery-tipsy-js', 'ig-pb-jquery-mousewheel-js', 'ig-pb-jquery-fancybox-js', 'ig-pb-jquery-lazyload-js' );
		IG_Pb_Assets::load( apply_filters( 'ig_pb_assets_enqueue_frontend',  $ig_pb_frontend_js ) );
	}

	/**
	 * Add Pagebuilder Metaboxes
	 */
	function custom_meta_boxes() {
		add_meta_box(
				'ig_page_builder'
				, __( 'Page Builder', IGPBL )
				, array( &$this, 'page_builder_html' )
		);
	}

	/**
	 * Content file for Pagebuilder Metabox
	 */
	function page_builder_html() {
		include IG_PB_TPL_PATH . '/page-builder.php';
	}

	/**
	 * Register all Parent & No-child element, for Add Element popover
	 */
	function register_element() {
		global $Ig_Pb_Shortcodes;
		$current_shortcode = IG_Pb_Helper_Functions::current_shortcode();
		$Ig_Pb_Shortcodes  = ! empty ( $Ig_Pb_Shortcodes ) ? $Ig_Pb_Shortcodes : IG_Pb_Helper_Shortcode::ig_pb_shortcode_tags();
		foreach ( $Ig_Pb_Shortcodes as $name => $sc_info ) {
			$arr  = explode( '_', $name );
			$type = $sc_info['type'];
			if ( ! $current_shortcode || ! is_admin() || in_array( $current_shortcode, $arr ) || ( ! $current_shortcode && $type == 'layout' ) ) {
				$class   = IG_Pb_Helper_Shortcode::get_shortcode_class( $name );
				$element = new $class();
				$this->set_element( $type, $class, $element );
				$this->register_sub_el( $class, 1 );
			}
		}
	}

	// Register IGPB Widget
	function register_widget(){
		register_widget( 'IG_Pb_Widget' );
	}
	/**
	 * Regiter sub element
	 * @param type $class
	 * @param type $level
	 */
	private function register_sub_el( $class, $level = 1 ) {
		$item  = str_repeat( 'Item_', intval( $level ) - 1 );
		$class = str_replace( "IG_$item", "IG_Item_$item", $class );
		if ( class_exists( $class ) ) {
			// 1st level sub item
			$element = new $class();
			$this->set_element( 'element', $class, $element );
			// 2rd level sub item
			$this->register_sub_el( $class, 2 );
		}
	}

	/**
	 * print HTML template of shortcodes
	 */
	function element_tpl() {
		ob_start();

		// print template html of IG element
		$elements = $this->get_elements();
		foreach ( $elements as $type_list ) {
			foreach ( $type_list as $element ) {
				$element_type = $element->element_in_pgbldr();
				foreach ( $element_type as $element_structure ) {
					echo balanceTags( "<script type='text/html' id='tmpl-{$element->config['shortcode']}'>\n{$element_structure}\n</script>\n" );
				}
			}
		}
		// print template html of Widget
		global $Ig_Pb_Widgets;
		foreach ( $Ig_Pb_Widgets as $shortcode => $shortcode_obj ) {
			if ( ! class_exists( 'IG_Widget' ) ) {
				continue;
			}
			$element = new IG_Widget();
			$modal_title = $shortcode_obj['identity_name'];
			$element->config['shortcode'] = $shortcode;
			$content = $element->config['exception']['data-modal-title'] = $modal_title;
			$element->config['shortcode_structure'] = ig_pb_add_placeholder( "[ig_widget widget_id=\"$shortcode\"]%s[/ig_widget]", 'widget_title' );
			$element->config['el_type'] = $type;
			$element_type = $element->element_in_pgbldr( $content );
			foreach ( $element_type as $element_structure ) {
				echo balanceTags( "<script type='text/html' id='tmpl-{$shortcode}'>\n{$element_structure}\n</script>\n" );
			}
		}


		do_action( 'ig_pb_footer' );
		ob_end_flush();
	}

	/**
	 * Show Modal page
	 */
	function modal_register() {
		if ( IG_Pb_Helper_Functions::is_modal() ) {
			$cls_modal = IG_Pb_Modal::get_instance();
			if ( ! empty( $_GET['ig_modal_type'] ) )
				$cls_modal->preview_modal();
			if ( ! empty( $_GET['ig_layout'] ) )
				$cls_modal->preview_modal( '_layout' );
		}
	}

	/**
	 * Add modal page hook
	 * @global array $_registered_pages
	 */
	function add_ig_modal_page() {
		global $_registered_pages;
		$hookname = get_plugin_page_hookname( 'ig_modal_page', 'index.php' );
		if ( ! empty( $hookname ) ) {
			add_action( $hookname, array( &$this, 'modal_page_content' ) );
		}
		$_registered_pages[$hookname] = true;
	}

	/**
	 * Doaction on modal page hook
	 */
	function modal_page_content() {
		do_action( 'ig_pb_modal_page_content' );
	}

	/**
	 * Hook before tinymce init
	 * @param array $initArray
	 * @return type
	 */
	function tinymce_before_init( $initArray ) {
		$initArray['setup'] = <<<JS
[function(ed) {
	ed.onChange.add( function(ed, l ) {
		jQuery( '.mceEditor' ).each(function (){
			var tiny_iframe	= jQuery( this ).find( 'iframe' );
			var _param_id	= tiny_iframe.attr( 'id' ).replace( '_ifr', '' );
			var _param		= jQuery( '#' + _param_id );
			_param.text( l.content ).val( l.content );
			jQuery( '#ig-tinymce-change' ).val( '1' );
			if( window.top.document.getElementById( 'jsn_view_modal' ) )
				window.top.document.getElementById( 'jsn_view_modal' ).contentWindow['jQuery'].HandleSetting.shortcodePreview();
		});

	});

}][0]
JS;
		return $initArray;
	}

	/**
	 * Save Pagebuilder shortcode content of a post/page
	 * @param type $post_id
	 * @return type
	 */
	function save_pagebuilder_content( $post_id ) {
		if ( ! current_user_can( 'edit_page', $post_id ) )
			return;

		if ( ! isset($_POST[IGNONCE . '_builder'] ) || ! wp_verify_nonce( $_POST[IGNONCE . '_builder'], 'ig_builder' ) )
			return;

		$ig_deactivate_pb = intval( mysql_real_escape_string( $_POST['ig_deactivate_pb'] ) );
		if ( $ig_deactivate_pb ) {
			ig_pb_delete_meta_key( array( '_ig_page_builder_content', '_ig_html_content', '_ig_page_active_tab', '_ig_post_view_count' ), $post_id );
		} else {
			$ig_active_tab = intval( mysql_real_escape_string( $_POST['ig_active_tab'] ) );
			$post_content  = '';
			if ( $ig_active_tab ) {
				$data = array();
				if ( isset( $_POST['shortcode_content'] ) && is_array( $_POST['shortcode_content'] ) ) {
					foreach ( $_POST['shortcode_content'] as $shortcode ) {
						$data[] = trim( stripslashes( $shortcode ) );
					}
				} else
					$data[] = '';

				$post_content = ( implode( '', $data ) );
				$post_content = ig_pb_remove_placeholder( $post_content, 'wrapper_append', '' );
			}
			else {
				$content = stripslashes( $_POST['content'] );
				/// remove this line? $content = apply_filters( 'the_content', $content );
				$post_content = $content;
			}
			update_post_meta( $post_id, '_ig_page_builder_content', $post_content );
			update_post_meta( $post_id, '_ig_html_content', IG_Pb_Helper_Shortcode::doshortcode_content( $post_content ) );
			update_post_meta( $post_id, '_ig_page_active_tab', $ig_active_tab );
		}
		update_post_meta( $post_id, '_ig_deactivate_pb', $ig_deactivate_pb );
	}

	/**
	 * Render shortcode preview in a blank page
	 * @return Ambigous <string, mixed>|WP_Error
	 */
	function shortcode_iframe_preview() {

		if ( isset( $_GET['ig_shortcode_preview'] ) ) {
			if ( ! isset($_GET['ig_shortcode_name'] ) || ! isset( $_POST['params'] ) )
				return __( 'empty shortcode name / parameters', IGPBL );

			if ( ! isset($_GET[IGNONCE] ) || ! wp_verify_nonce( $_GET[IGNONCE], IGNONCE ) )
				return;

			$shortcode = mysql_real_escape_string( $_GET['ig_shortcode_name'] );
			$params    = urldecode( $_POST['params'] );
			$pattern   = '/^\[ig_widget/i';
			if ( ! preg_match( $pattern, trim( $params ) ) ) {
				// get shortcode class
				$class = IG_Pb_Helper_Shortcode::get_shortcode_class( $shortcode );
				// get option settings of shortcode
				$elements = $this->get_elements();
				$elements = $this->get_elements();
				$element  = isset( $elements['element'][strtolower( $class )] ) ? $elements['element'][strtolower( $class )] : null;
				if ( ! is_object( $element ) )
					$element = new $class();

				if ( $_POST['params'] ) {
					$extract_params = IG_Pb_Helper_Shortcode::extract_params( $params, $shortcode );
				} else {
					$extract_params = $element->config;
				}

				$element->shortcode_data();

				$sc_inner_content = $extract_params['sc_inner_content'];
				$content = $element->element_shortcode( $extract_params, $sc_inner_content );
			} else {
				$content = IG_Pb_Helper_Shortcode::widget_content( array( $params ) );
			}
			$html  = '<div id="shortcode_inner_wrapper" class="jsn-bootstrap">';
			$html .= $content;
			$html .= '</div>';
			echo balanceTags( $html );
		}
	}

	/**
	 * Save premade layout to file
	 * @return type
	 */
	function save_layout() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		$layout_name    = $_POST['layout_name'];
		$layout_content = stripslashes( $_POST['layout_content'] );

		IG_Pb_Helper_Functions::save_premade_layouts( $layout_name, $layout_content );

		exit;
	}

	/**
	 * Update Shortcode content by merge its content & sub-shortcode content
	 */
	function update_whole_sc_content() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		$shortcode_content     = $_POST['shortcode_content'];
		$sub_shortcode_content = $_POST['sub_shortcode_content'];
		echo balanceTags( IG_Pb_Helper_Shortcode::merge_shortcode_content( $shortcode_content, $sub_shortcode_content ) );

		exit;
	}

	/**
	 * extract a param from shortcode data
	 */
	function shortcode_extract_param() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		$data		  = $_POST['data'];
		$extract_param = $_POST['param'];
		$extract       = array();
		$shortcodes    = IG_Pb_Helper_Shortcode::extract_sub_shortcode( $data );
		foreach ( $shortcodes as $shortcode ) {
			$shortcode    = stripslashes( $shortcode );
			$parse_params = shortcode_parse_atts( $shortcode );
			$extract[]    = isset( $parse_params[$extract_param] ) ? trim( $parse_params[$extract_param] ) : '';
		}
		$extract = array_filter( $extract );
		$extract = array_unique( $extract );

		echo balanceTags( implode( ',', $extract ) );
		exit;
	}

	function ajax_json_custom() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		if ( ! $_POST['custom_type'] )
			return 'false';

		$response = apply_filters( 'ig_pb_get_json_' . $_POST['custom_type'], $_POST );
		echo balanceTags( $response );

		exit;
	}

	/**
	 * GET <script type='text/html'... template for shortcode element
	 * @global type $Ig_Pb_Widgets
	 * @return type
	 */
	function get_shortcode_tpl() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		if ( ! $_POST['shortcode'] )
			return;
		$shortcode = $_POST['shortcode'];
		$type      = $_POST['type'];
		$elements  = $this->get_elements();
		if ( $type == 'element' ) {
			if ( isset( $elements['element'][strtolower( $shortcode )] ) && is_object( $elements['element'][strtolower( $shortcode )] ) ) {
				$element = $elements['element'][strtolower( $shortcode )];
			} else {
				$class   = IG_Pb_Helper_Shortcode::get_shortcode_class( $shortcode );
				$element = new $class();
			}
			$element->shortcode_data();
			$element_type = $element->element_in_pgbldr();
			foreach ( $element_type as $element_structure ) {
				echo balanceTags( "<script type='text/html' id='tmpl-{$shortcode}'>\n{$element_structure}\n</script>\n" );
			}
		} else {
			$shortcode = mysql_real_escape_string( $shortcode );
			$element   = new IG_Widget();
			global $Ig_Pb_Widgets;
			$modal_title = $Ig_Pb_Widgets[$shortcode]['identity_name'];
			$element->config['shortcode'] = $shortcode;
			$content = $element->config['exception']['data-modal-title'] = $modal_title;
			$element->config['shortcode_structure'] = ig_pb_add_placeholder( "[ig_widget widget_id=\"$shortcode\"]%s[/ig_widget]", 'widget_title' );
			$element->config['el_type'] = $type;
			$element_type = $element->element_in_pgbldr( $content );
			foreach ( $element_type as $element_structure ) {
				echo balanceTags( "<script type='text/html' id='tmpl-{$shortcode}'>\n{$element_structure}\n</script>\n" );
			}
		}

		exit;
	}

	/**
	 * Classic Editor to Pagebuilder
	 * @return type
	 */
	function text_to_pagebuilder() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		if ( ! isset( $_POST['content'] ) )
			return;
		// $content = urldecode( $_POST['content'] );
		$content = ( $_POST['content'] );
		$content = stripslashes( $content );

		$empty_str = IG_Pb_Helper_Shortcode::check_empty_( $content );
		if ( strlen( trim( $content ) ) != 0 && strlen( trim( $empty_str ) ) != 0 ) {
			$builder = new IG_Pb_Helper_Shortcode();
			echo balanceTags( $builder->do_shortcode_admin( $content, false, true ) );
		}
		exit;
	}

	/**
	 * Show Pagebuilder content for Frontend post
	 *
	 * @param type $content
	 * @return type
	 */
	function pagebuilder_to_frontend( $content ) {
		global $post;

		$ig_deactivate_pb = get_post_meta( $post->ID, '_ig_deactivate_pb', true );
		// if not deactivate pagebuilder on this post
		if ( empty( $ig_deactivate_pb ) ) {
			$ig_pagebuilder_content = get_post_meta( $post->ID, '_ig_page_builder_content', true );
			if ( ! empty( $ig_pagebuilder_content ) ) {
				// remove placeholder text which was inserted to &lt; and &gt;
				$ig_pagebuilder_content = ig_pb_remove_placeholder( $ig_pagebuilder_content, 'wrapper_append', '' );

				$ig_pagebuilder_content = preg_replace_callback(
						'/\[ig_widget\s([A-Za-z0-9_-]+=\"[^"\']*\")*\](.*)\[\/ig_widget\]/Us', array( 'IG_Pb_Helper_Shortcode', 'widget_content' ), $ig_pagebuilder_content
						);

				$content = do_shortcode( $ig_pagebuilder_content );
			}
		}

		return $content;
	}

	/**
	 * Get output html of pagebuilder content
	 */
	function get_html_content() {
		if ( ! isset($_POST[IGNONCE] ) || ! wp_verify_nonce( $_POST[IGNONCE], IGNONCE ) )
			return;

		if ( ! isset( $_POST['content'] ) )
			return;

		$content = $_POST['content'];
		$content = stripslashes( $content );
		$content = IG_Pb_Helper_Shortcode::doshortcode_content( $content );
		echo "<div class='jsn-bootstrap'>" . $content . '</div>';

		exit;
	}

	// get media file name
	function media_file_name( $file ) {
		$file_name = iconv( 'utf-8', 'ascii//TRANSLIG//IGNORE', $file['name'] );
		if ( $file_name ) {
			$file['name'] = $file_name;
		}
		return $file;
	}

	/**
	 * Load necessary assets.
	 *
	 * @return  void
	 */
	function load_assets() {
		add_filter( 'ig_pb_assets_register', array( &$this, 'apply_assets' ) );

		global $pagenow;
		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' || $pagenow == 'widgets.php' ) {

			// styles
			IG_Pb_Helper_Functions::enqueue_styles();

			IG_Pb_Assets::load( array( 'ig-pb-jquery-fancybox-css', 'ig-pb-jquery-isotope-css' ) );

			// scripts
			IG_Pb_Helper_Functions::enqueue_scripts();

			$scripts = array( 'ig-pb-jquery-select2-js', 'ig-pb-jquery-livequery-js', 'ig-pb-addpanel-js', 'ig-pb-jquery-resize-js', 'ig-pb-joomlashine-modalresize-js', 'ig-pb-jquery-isotope-js', 'ig-pb-layout-js', 'ig-pb-jquery-mousewheel-js', 'ig-pb-jquery-fancybox-js', 'ig-pb-placeholder' );
			IG_Pb_Assets::load( apply_filters( 'ig_pb_assets_enqueue_admin', $scripts ) );

			IG_Pb_Helper_Functions::enqueue_scripts_end();
		}
	}

	/**
	 * function for register pagebuilder widget assets
	 *
	 * @return void
	 */
	function widget_register_assets() {
		global $pagenow;

		if ( $pagenow == 'widgets.php' ) {
			// enqueue admin script
			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			} else {
				wp_enqueue_style( 'thickbox' );
				wp_enqueue_script( 'media-upload' );
				wp_enqueue_script( 'thickbox' );
			}
			$this->load_assets();
			IG_Pb_Assets::load( 'ig-pb-handlesetting-js' );
			IG_Pb_Assets::load( 'ig-pb-jquery-fancybox-js' );
			IG_Pb_Assets::load( 'ig-pb-widget-js' );
		}
	}

	/**
	 * Unload Wp admin css in modal iframe
	 */
	function remove_admin_assets() {
		if ( IG_Pb_Helper_Functions::is_modal() ) {
			wp_deregister_style( 'wp-admin' );

			wp_deregister_script( 'colors' );
			wp_deregister_script( 'ie' );
			wp_dequeue_script( 'utils' );

			global $wp_scripts;
			unset ( $wp_scripts );

			remove_all_actions( 'admin_print_styles' );
			//			remove_all_actions( 'admin_print_scripts' );

			if ( is_network_admin() ) {
				/**
				 * Print network admin screen notices.
				 *
				 * @since 3.1.0
				 */
				remove_all_actions( 'network_admin_notices' );
			} elseif ( is_user_admin() ) {
				/**
				 * Print user admin screen notices.
				 *
				 * @since 3.1.0
				 */
				remove_all_actions( 'user_admin_notices' );
			} else {
				/**
				 * Print admin screen notices.
				 *
				 * @since 3.1.0
				 */
				remove_all_actions( 'admin_notices' );
			}
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * Remove assets of any third parties at admin footer
	 */
	function remove_admin_assets_footer() {
		if ( IG_Pb_Helper_Functions::is_modal() ) {
			global $ig_handle_assets;
			$ig_handle_assets[] = 'common';
			//			$ig_handle_assets[] = 'admin-bar';
			$ig_handle_assets[] = 'media-editor';
			//			$ig_handle_assets[] = 'utils';
			$ig_handle_assets[] = 'wp-auth-check';
			$ig_handle_assets[] = 'jquery';
			$ig_handle_assets[] = 'jquery-ui-core';
			$ig_handle_assets[] = 'jquery-ui-tabs';
			$ig_handle_assets[] = 'jquery-ui-widget';
			$ig_handle_assets[] = 'jquery-ui-resizable';
			$ig_handle_assets[] = 'jquery-ui-sortable';
			$ig_handle_assets[] = 'jquery-ui-dialog';
			$ig_handle_assets[] = 'jquery-ui-button';
			$ig_handle_assets[] = 'jquery-ui-slider';

			global $wp_scripts;
			foreach ( $wp_scripts->queue as $script ) {
				if ( ! in_array( $script, $ig_handle_assets ) ) {
					wp_dequeue_script( $script );
					wp_dequeue_style( $script );
				}
			}
		}
	}

	/**
	 * Add Inno Button
	 *
	 * @param type $buttons
	 * @return type
	 */
	function filter_mce_button( $buttons ) {
		// add a separation before our button, here our button's id is "ig_pb_button"
		array_push( $buttons, '|', 'ig_pb_button' );
		return $buttons;
	}

	/**
	 * Add js file to handling event
	 * @param array $plugins
	 * @return string
	 */
	function filter_mce_plugin( $plugins ) {
		$plugins['ig_pb'] = IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/js/tinymce.js';
		return $plugins;
	}

	// Gravatar : use default avatar, don't request from gravatar server
	function remove_gravatar( $image, $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir ) {

		$default = IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/images/default_avatar.png';

		if ( $image && strpos( $image, 'gravatar.com' ) ) {

			return '<img src="' . $default . '" alt="avatar" class="avatar" ' . $html_width . $html_height . ' />';
		} else {
			return $image;
		}
	}

	// Gravatar : use default avatar
	function get_gravatar( $avatar, $id_or_email, $size, $default ) {
		$default = IG_Pb_Helper_Functions::path( 'assets/innogears' ) . '/images/default_avatar.png';
		return '<img src="' . $default . '" alt="avatar" class="avatar" width="60" height="60" />';
	}

	// filter admin body class
	function admin_bodyclass( $classes ) {
		$classes .= ' jsn-master';
		if ( isset($_GET['ig_load_modal'] ) AND isset( $_GET['ig_modal_type']) ) {
			$classes .= ' contentpane';
		}
		return $classes;
	}

	// get image size
	function get_image_size( $post_request ) {
		$response  = '';
		$image_url = $post_request['image_url'];

		if ( $image_url ) {
			$image_id   = IG_Pb_Helper_Functions::get_image_id( $image_url );
			$attachment = wp_prepare_attachment_for_js( $image_id );
			if ( $attachment['sizes'] ) {
				$sizes		       = $attachment['sizes'];
				$attachment['sizes'] = null;
				foreach ( $sizes as $key => $item ) {
					$item['total_size']	= $item['height'] + $item['width'];
					$attachment['sizes'][ucfirst( $key )] = $item;
				}
			}
			$response = json_encode( $attachment );
		}

		return $response;
	}

	// filter frontend body class
	function wp_bodyclass( $classes ) {
		$classes[] = 'jsn-master';
		return $classes;
	}

	// Update post view in frontend
	function post_view() {
		global $post;
		if ( ! isset($post ) || ! is_object( $post ) )
			return;
		if ( is_single( $post->ID ) ) {
			IG_Pb_Helper_Functions::set_postview( $post->ID );
		}
	}

	// after title hook
	function hook_after_title() {
		global $post;
		$ig_pagebuilder_content = get_post_meta( $post->ID, '_ig_page_builder_content', true );
		// active tab
		$ig_page_active_tab = get_post_meta( $post->ID, '_ig_page_active_tab', true );
		$tab_active = isset( $ig_page_active_tab ) ? intval( $ig_page_active_tab ) : ( ! empty($ig_pagebuilder_content ) ? 1 : 0);
		// deactivate pagebuilder
		$ig_deactivate_pb = get_post_meta( $post->ID, '_ig_deactivate_pb', true );
		$ig_deactivate_pb = isset( $ig_deactivate_pb ) ? intval( $ig_deactivate_pb ) : 0;

		$wrapper_style = $tab_active ? 'style="display:none"' : '';
		echo '
			<input id="ig_active_tab" name="ig_active_tab" value="' . $tab_active . '" type="hidden">
			<input id="ig_deactivate_pb" name="ig_deactivate_pb" value="' . $ig_deactivate_pb . '" type="hidden">
			<div class="jsn-bootstrap ig-editor-wrapper" ' . $wrapper_style . '>
				<ul class="nav nav-tabs" id="ig_editor_tabs">
					<li class="active"><a href="#ig_editor_tab1">' . __( 'Classic Editor', IGPBL ) . '</a></li>
					<li><a href="#ig_editor_tab2">' . __( 'Page Builder', IGPBL ) . '</a></li>
				</ul>
				<div class="tab-content ig-editor-tab-content">
					<div class="tab-pane active" id="ig_editor_tab1">';
	}

	// after editor hook
	function hook_after_editor() {
		echo '</div><div class="tab-pane" id="ig_editor_tab2"><div id="ig_before_pagebuilder"></div></div></div></div>';
	}
}