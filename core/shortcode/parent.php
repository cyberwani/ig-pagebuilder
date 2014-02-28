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
 * Parent class for parent elements
 */

class IG_Pb_Shortcode_Parent extends IG_Pb_Shortcode_Element {

	/**
	 * get params & structure of shortcode
	 * OVERWRIGE parent function
	 */
	public function shortcode_data() {
		$params = IG_Pb_Helper_Shortcode::generate_shortcode_params( $this->items );
		$this->config['params'] = array_merge( array( 'div_margin_top' => '', 'div_margin_bottom' => '', 'disabled_el' => 'no', 'css_suffix' => '' ), $params );

		// get content of sub-shortcode
		$sub_sc_content = array();
		$item_type      = $this->config['has_subshortcode'];
		foreach ( $this->config['params']['sub_sc_content'] as &$sub_shortcode ) {
			$sub_sc = new $item_type();
			if ( empty($sub_shortcode['std']) ) {
				// if empty, get default shortcode structure of sub-shortcode
				$sub_sc->config['params'] = IG_Pb_Helper_Shortcode::generate_shortcode_params( $sub_sc->items, null, null, false, true );
				$sub_shortcode['std']     = $sub_sc->config['shortcode_structure'] = IG_Pb_Helper_Shortcode::generate_shortcode_structure( $sub_sc->config['shortcode'], $sub_sc->config['params'] );
			} else {
				// if std of sub-shortcode is predefined ( such as GoogleMap )
				$params         = stripslashes( $sub_shortcode['std'] );
				$extract_params = IG_Pb_Helper_Shortcode::extract_params( urldecode( $params ) );
				IG_Pb_Helper_Shortcode::generate_shortcode_params( $sub_sc->items, NULL, $extract_params, TRUE );
				$sub_sc->shortcode_data();
			}

			$sub_sc_content[] = $sub_shortcode['std'];
		}
		$sub_sc_content = implode( '', $sub_sc_content );
		// END get content of sub-shortcode

		$this->config['shortcode_structure'] = IG_Pb_Helper_Shortcode::generate_shortcode_structure( $this->config['shortcode'], $this->config['params'], $sub_sc_content );
	}

}

