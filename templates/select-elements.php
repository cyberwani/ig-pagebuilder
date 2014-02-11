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
global $Ig_Pb;
global $Ig_Pb_Shortcodes;
$elements = $Ig_Pb->get_elements();

if ( empty ($elements) || empty ( $elements['element'] ) ) {
	_e( 'You have not install Free or Pro Shortcode package.' );
} else {

	//HTML structure of elements
	$elements_html = array();
	foreach ( $elements['element'] as $idx => $element ) {
		// don't show sub-shortcode
		if ( ! isset( $element->config['name'] ) )
			continue;

		$elements_html[] = $element->element_button( $idx + 1 );
	}
	?>
	<div id="ig-add-element" class="ig-add-element add-field-dialog" style="display: none;">
		<div class="popover top" style="display: block;">
			<div class="arrow"></div>
			<h3 class="popover-title"><?php _e( 'Select Elements', IGPBL ); ?></h3>
			<div class="popover-content">
				<div class="jsn-elementselector">
					<div class="jsn-fieldset-filter">
						<fieldset>
							<div class="pull-left">
								<select class="jsn-filter-button input-large">
									<option value="element" selected><?php _e( 'Page Elements', IGPBL ) ?></option>
									<option value="widget"><?php _e( 'Widgets', IGPBL ) ?></option>
								</select>
							</div>
							<div class="pull-right jsn-quick-search">
								<input type="text" class="input search-query" id="jsn-quicksearch-field" placeholder="<?php _e( 'Search', IGPBL ); ?>...">
								<a href="javascript:void(0);" title="<?php _e( 'Clear Search', IGPBL ); ?>" class="jsn-reset-search" id="reset-search-btn"><i class="icon-remove"></i></a>
							</div>
						</fieldset>
					</div>
					<!-- Elements -->
					<ul class="jsn-items-list">
	<?php
	// shortcode elements
	foreach ( $elements_html as $idx => $element ) {
		echo balanceTags( $element );
	}

	// widgets
	global $Ig_Pb_Widgets;
	foreach ( $Ig_Pb_Widgets as $wg_class => $config ) {
		$extra_ = $config['extra_'];
		echo balanceTags( IG_Pb_Element::el_button( $extra_, $config ) );
	}
	?>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<!-- Use for Popup modal in Classic Editor, when click on Inno icon -->
	<div id="ig-shortcodes" class="ig-add-element add-field-dialog jsn-bootstrap" style="display: none;">
		<div class="jsn-elementselector">
			<ul class="jsn-items-list">
	<?php
	// shortcode elements
	foreach ( $elements_html as $idx => $element ) {
		echo balanceTags( $element );
	}
	?>
			</ul>
		</div>
	</div>

	<?php
}