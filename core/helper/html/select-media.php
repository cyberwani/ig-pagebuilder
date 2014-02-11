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
class IG_Pb_Helper_Html_Select_Media extends IG_Pb_Helper_Html {
	/**
	 * Input field to select Media
	 * @param type $element
	 * @return type
	 */
	static function render( $element ) {
		$element = parent::get_extra_info( $element );
		$label = parent::get_label( $element );
		$_filter_type = isset( $element['filter_type'] ) ? $element['filter_type'] : 'image';
		$output = '<div class="input-append row-fluid">
							<input type="text" class="' . $element['class'] . '" value="' . $element['std'] . '" id="' . $element['id'] . '">
							<button class="btn select-media" filter_type="' . $_filter_type . '" id="' . $element['id'] . '_button" type="button">...</button>
							<button class="btn btn-icon select-media-remove" type="button"><i class="icon-remove"></i></button>
						</div>';
		return parent::final_element( $element, $output, $label );
	}
}