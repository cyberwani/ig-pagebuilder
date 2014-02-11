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
 *
 * Content of Modal popup
 */
if ( ! isset( $_POST ) )
	die;

extract( $_POST );

$submodal = ! empty( $submodal ) ? 'submodal_frame' : '';
if ( ! isset( $params ) )
	exit;

if ( ! empty( $shortcode ) ) {
	?>
	<div id="ig-element-<?php echo esc_attr( IG_Pb_Helper_Shortcode::shortcode_name( $shortcode ) ); ?>">
		<div class="jsn-bootstrap" id="form-container">
			<div id="modalOptions" class="form-horizontal <?php echo esc_attr( $submodal ); ?>">
	<?php
	if ( ! empty( $params ) ) {
		$params = stripslashes( $params );
		$params = urldecode( $params );
	}
	// elements
	if ( $el_type == 'element' ) {
		// get shortcode class
		$class = IG_Pb_Helper_Shortcode::get_shortcode_class( $shortcode );
		if ( class_exists( $class ) ) {
			global $Ig_Pb;
			$elements = $Ig_Pb->get_elements();
			$instance = isset( $elements['element'][strtolower( $class )] ) ? $elements['element'][strtolower( $class )] : null;
			if ( ! is_object( $instance ) )
				$instance = new $class();
			if ( ! empty( $params ) ) {
				$extract_params = IG_Pb_Helper_Shortcode::extract_params( $params, $shortcode );
				// if have sub-shortcode, get content
				if ( ! empty( $instance->config['has_subshortcode'] ) ) {
					$sub_sc_data = IG_Pb_Helper_Shortcode::extract_sub_shortcode( $params, true );
					$extract_params['sub_sc_content'] = $sub_sc_data;
				}
				// MODIFY $instance->items
				IG_Pb_Helper_Shortcode::generate_shortcode_params( $instance->items, NULL, $extract_params, TRUE );
				// recall this to re-extract params
				$instance->shortcode_data();
			}

			// get Modal setting box
			$settings = $instance->items;
			$data['content'] = IG_Pb_Modal::get_shortcode_modal_settings( $settings, $shortcode, $extract_params );
			echo balanceTags( $data['content'] );
		}
	}
	// widgets
	else if ( $el_type == 'widget' ) {
		$instance = IG_Pb_Helper_Shortcode::extract_widget_params( $params );
		$instance['title'] = isset( $instance['title'] ) ? $instance['title'] : $el_title;
		// generate setting form of widget
		$widget = new $shortcode();
		ob_start();
		$widget->form( $instance );
		$form = ob_get_clean();
		// simplify widget field name
		$exp  = preg_quote( $widget->get_field_name( '____' ) );
		$exp  = str_replace( '____', '(.*? )', $exp );
		$form = preg_replace( '/' . $exp . '/', '$1', $form );
		// simplify widget field id
		$exp  = preg_quote( $widget->get_field_id( '____' ) );
		$exp  = str_replace( '____', '(.*? )', $exp );
		$form = preg_replace( '/' . $exp . '/', 'param-$1', $form );

		// tab and content generate
		$tabs = array();
		foreach ( array( 'content', 'styling' ) as $i => $tab ) {
			$active = ( $i++ == 0 ) ? 'active' : '';
			$data_['href'] = "#$tab";
			$data_['data-toggle'] = 'tab';
			$content_ = ucfirst( $tab );
			$tabs[] = "<li class='$active'>" . IG_Pb_Modal::tab_settings( 'a', $data_, $content_ ) . '</li>';
		}
		// content
		$contents   = array();
		$contents[] = "<div class='tab-pane active' id='content'><form id='ig-widget-form'>$form</form></div>";
		$contents[] = "<div class='tab-pane' id='styling'>" . IG_Pb_Helper_Shortcode::render_parameter( 'preview' ) . '</div>';

		$output = IG_Pb_Modal::setting_tab_html( $shortcode, $tabs, $contents, array(), '', array() );

		echo balanceTags( $output );
	}
	?>
	<div id="modalAction"></div>
			</div>
			<textarea class="hidden" id="shortcode_content"><?php echo esc_attr( $params ); ?></textarea>
			<textarea class="hidden" id="ig_share_data"  ></textarea>
			<textarea class="hidden" id="ig_merge_data"  ></textarea>
			<textarea class="hidden" id="ig_extract_data"  ></textarea>
			<input type="hidden" id="ig_previewing" value="0" />
            <input id="shortcode_type" type="hidden" value="<?php echo esc_attr( $el_type ); ?>" />
			<input id="shortcode_name" type="hidden" value="<?php echo esc_attr( mysql_real_escape_string( $_GET['ig_modal_type'] ) ); ?>" />
			<div class="jsn-modal-overlay"></div>
			<div class="jsn-modal-indicator"></div>
		</div>
	</div>
	<?php
}