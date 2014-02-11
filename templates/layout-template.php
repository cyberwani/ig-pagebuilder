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
?>
<div id="ig-add-layout" style="display: none;">
    <div class="popover top" style="display: block;">
        <div class="arrow"></div>
        <div class="popover-content">
            <div id="save-layout"><a href="javascript:void(0)"><?php _e( 'Save current content as template', IGPBL ); ?> <i class="icon-star"></i></a></div>
            <div id="save-layout-loading" class="hidden"><i class="jsn-icon16 jsn-icon-loading"></i></div>
            <div id="save-layout-messsage" class="hidden"><?php _e( 'Saved successfully', IGPBL ); ?></div>
            <div id="save-layout-form" class="input-append hidden"><input type="text" name="layout_name" id="layout-name" placeholder="<?php _e( 'Layout name', IGPBL ); ?>"><button class="btn" type="button" id="btn-layout-add"><i class="icon-checkmark"></i></button><button class="btn" type="button" id="btn-layout-cancel"><i class="icon-remove"></i></button></div>
            <div id="apply-layout"><a href="javascript:void(0)"><?php _e( 'Apply template from library', IGPBL ); ?></a></div>
        </div>
    </div>
</div>