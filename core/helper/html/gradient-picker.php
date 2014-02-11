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
class IG_Pb_Helper_Html_Gradient_Picker extends IG_Pb_Helper_Html {
	/**
	 * gradient picker
	 * @param type $element
	 */
	static function render( $element ){
		$element = parent::get_extra_info( $element );
		$label   = parent::get_label( $element );
		$output  = "<input type='hidden' class='jsn-grad-ex' id='{$element['id']}' name='{$element['id']}' value='{$element['std']}'  DATA_INFO />";
		$output .= "<div class='classy-gradient-box'></div>";

		add_filter( 'ig_pb_assets_enqueue_modal', array( __CLASS__, 'this_assets_enqueue_modal' ) );

		return parent::final_element( $element, $output, $label );
	}

	// enqueue custom assets
	static function this_assets_enqueue_modal( $scripts ){
		$scripts = array_merge( $scripts, array( 'ig-pb-classygradient-js', 'ig-pb-classygradient-css', ) );

		return $scripts;
	}
}