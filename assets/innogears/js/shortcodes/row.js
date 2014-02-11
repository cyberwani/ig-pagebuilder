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
 * Custom script for Row
 */
( function ($) {
	"use strict";
    function hidePosition(value){
        if(value == 'full'){
            $('#parent-param-position').addClass('ig_hidden_depend2');
        }else{
            $('#parent-param-position').removeClass('ig_hidden_depend2');
        }
    }
	$(document).ready(function () {
        $('#param-background').change(function(){
            var value = $(this).val();
            if(value == 'image'){
                value = $('#parent-param-stretch button.active').attr('data-value');
                hidePosition(value);
            }
        });

        $('#parent-param-stretch button').click(function(){
            var value = $(this).attr('data-value');
            hidePosition(value);
        });
	});

})(jQuery)