/**
 * @version    $Id$
 * @package    IGPGBLDR
 * @author     InnoGears Team <support@www.innogears.com>
 * @copyright  Copyright (C) 2012 InnoGears.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.www.innogears.com
 * Technical Support: Feedback - http://www.www.innogears.com/contact-us/get-support.html
 */

(function ($)
{
    "use strict";

    $.HandleSetting 		= $.HandleSetting || {};
    $.IGModal				= $.IGModal || {};
    $.IG_ImageElement		= $.IG_ImageElement || {};
    $.IG_ImageElement.setImageSize	= $.IG_ImageElement.setImageSize || {};

    $( document ).ready( function ()
    {
        $.HandleSetting.togglePreview();

        $.HandleSetting.updateState();

        $.HandleSetting.tab();

        // Trigger action of element which has dependency elements
        $.HandleSetting.changeDependency( '.ig_has_depend' );

        // Update preview when change param in Modal Box
        $( '#modalOptions' ).delegate( '[id^="param"]', 'change', function () {
            if ($(this).attr('data-role') == 'no_preview'){
                return false;
            }
            $.HandleSetting.shortcodePreview();
        });

        // Send ajax for loading shortcode html at first time
        $.HandleSetting.renderModal();

        $.HandleSetting.select2();

        $.HandleSetting.icons();

        $.HandleSetting.actionHandle();

        $.HandleSetting.selectImage();

        $.HandleSetting.gradientPicker();

        $.HandleSetting.buttonGroup();

        $.HandleSetting.inputValidator();

        $.HandleSetting.setTinyMCE('.ig_pb_tiny_mce');

        if ( $('#shortcode_name').val() != 'ig_image' ) {
            $('select option[value="large_image"]').hide();
        }

    } );

    $.HandleSetting.gradientPicker = function() {

        var gradientPicker = function(){
            var val = $('#param-background').val();
            if(val == 'gradient'){
                $("input.jsn-grad-ex").each(function(i, e) {
                    $(e).next('.classy-gradient-box').first().ClassyGradient({
                        gradient: $(e).val(),
                        width : 218,
                        orientation : $('#param-gradient_direction').val(),
                        onChange: function(stringGradient, cssGradient, arrayGradient) {
                            $(e).val() == stringGradient || $(e).val(stringGradient);
                            $('#param-gradient_color_css').val(cssGradient);
                            $.HandleSetting.shortcodePreview();
                        }
                    });
                });
            }
        }
        $(document).ready(function(){
            setTimeout(function(){
                gradientPicker();
            }, 500);
        });

        $('#param-background').change(function(){
            gradientPicker();
        });


        // control orientation
        $('#param-gradient_direction').on('change', function() {
            var orientation = $(this).val();
            $('.classy-gradient-box').data('ClassyGradient').setOrientation(orientation);
            // update background gradient
            if(orientation == 'horizontal'){
                $('#param-gradient_color_css').val($('#param-gradient_color_css').val().replace('left top, left bottom', 'left top, right top').replace(/\(top/g,'(left'));
            }else{
                $('#param-gradient_color_css').val($('#param-gradient_color_css').val().replace('left top, right top', 'left top, left bottom').replace(/\(left/g,'(top'));
            }
        });

    }

    // check radio button when click button in btn-group
    $.HandleSetting.buttonGroup = function() {
        var data_value;
        $('.ig-btn-group .btn').click(function(i){
            data_value = $(this).attr('data-value');
            $(this).parent().next('.ig-btn-radio').find('input:radio[value="'+data_value+'"]').prop('checked', true);
            $.HandleSetting.shortcodePreview();
        });
    }

    // validate input of text field with regular expression
    $.HandleSetting.regexTestInput = function(event, regex_str) {
        var regex = new RegExp(regex_str);
        var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
        if (!regex.test(key) && key != ' ') {
            event.preventDefault();
            return false;
        }
        return true;
    }

    // Validator input field
    $.HandleSetting.inputValidator = function() {
        var input_action = 'keypress change paste';

        // allow currency symbold in Price field
//        $('#modalOptions input:text.ig_pb_price').bind(input_action, function (event) {
//            $.HandleSetting.regexTestInput(event, "^[^!@#%\^&_;|\b\s]+$");
//        });

        // Disable Special Characters, Limit length of title
        $("#param-el_title, input:text[name$='[title]'], [data-role='title'], .ig-pb-limit-length", '#modalOptions').each(function(){
            $(this).prop('maxlength', 50);
//            $(this).bind(input_action, function (event) {
//                $.HandleSetting.regexTestInput(event, "^[a-zA-Z0-9-_;|\b\s]+$");
//            });
        });

        // Number field: only allow to type number
        $("#modalOptions input[type='number']").bind(input_action, function (event) {
            $.HandleSetting.regexTestInput(event, "^[0-9\b\-]+$");
        });
        // Doesn't allow 0 in items_per_ input field
        $("#modalOptions input[id*='items_per']").bind(input_action, function (event) {
            var regex = /^[1-9\b]+$/g;
            var val = regex.test($(this).val());
            if(!val){
                $(this).val('1');
            }
        });

        // positive value
        $('.positive-val').bind(input_action, function (event) {
            var this_val = $(this).val();
            if(parseInt(this_val) <= 0){
                $(this).val(1);
            }
        });
    }

    // Custom WYSIWYG
    $.HandleSetting.setTinyMCE = function (selector) {
        $(selector).each(function() {
            var current_id = $(this).attr('id');
            if ( current_id ) {
                $('#' + current_id).wysiwyg({
                    controls:{
                        bold:{
                            visible:true
                        },
                        italic:{
                            visible:true
                        },
                        underline:{
                            visible:true
                        },
                        strikeThrough:{
                            visible:true
                        },

                        justifyLeft:{
                            visible:true
                        },
                        justifyCenter:{
                            visible:true
                        },
                        justifyRight:{
                            visible:true
                        },
                        justifyFull:{
                            visible:true
                        },

                        indent:{
                            visible:true
                        },
                        outdent:{
                            visible:true
                        },

                        subscript:{
                            visible:true
                        },
                        superscript:{
                            visible:true
                        },

                        undo:{
                            visible:true
                        },
                        redo:{
                            visible:true
                        },

                        insertOrderedList:{
                            visible:true
                        },
                        insertUnorderedList:{
                            visible:true
                        },
                        insertHorizontalRule:{
                            visible:true
                        },

                        h4:{
                            visible:true,
                            className:'h4',
                            command:($.browser.msie || $.browser.safari) ? 'formatBlock' : 'heading',
                            arguments:($.browser.msie || $.browser.safari) ? '<h4>' : 'h4',
                            tags:['h4'],
                            tooltip:'Header 4'
                        },
                        h5:{
                            visible:true,
                            className:'h5',
                            command:($.browser.msie || $.browser.safari) ? 'formatBlock' : 'heading',
                            arguments:($.browser.msie || $.browser.safari) ? '<h5>' : 'h5',
                            tags:['h5'],
                            tooltip:'Header 5'
                        },
                        h6:{
                            visible:true,
                            className:'h6',
                            command:($.browser.msie || $.browser.safari) ? 'formatBlock' : 'heading',
                            arguments:($.browser.msie || $.browser.safari) ? '<h6>' : 'h6',
                            tags:['h6'],
                            tooltip:'Header 6'
                        },

                        cut:{
                            visible:true
                        },
                        copy:{
                            visible:true
                        },
                        paste:{
                            visible:true
                        },
                        html:{
                            visible:true
                        },
                        increaseFontSize:{
                            visible:true
                        },
                        decreaseFontSize:{
                            visible:true
                        }
                    },
                    initialContent: '&nbsp;'
                });
            }
        });
    }

    $.HandleSetting.selectImage = function() {
        var _custom_media 			= true,
        _orig_send_attachment 	= wp.media.editor.send.attachment;
        $('#modalOptions .select-media-remove').on('click', function() {
            var _input	= $(this).closest('div').find('input[type="text"]');
            _input.attr('value', '');
            _input.trigger('change');
        });

        $('#modalOptions .select-media').click(function(e) {
            var button 			= $(this);
            var id 				= button.attr('id').replace('_button', '');
            var jqueryParent 	= window.parent.jQuery.noConflict();
            var filter_type		= $(this).attr('filter_type');
            var object 			= {};
            if (typeof(filter_type) != undefined){
                object.type	=	filter_type;
            }else {
                object.type	= '';
            }

            jqueryParent('#ig-select-media').val(JSON.stringify(object));
            jqueryParent('#ig-select-media').trigger('change');
            var timer = setInterval(function() {
                var currentValue = jqueryParent('#ig-select-media').val();
                if ( currentValue ) {
                    var jsonObject = JSON.parse( currentValue );
                    switch ( jsonObject.type ) {
                        case 'media_selected':
                            if (typeof($.IG_ImageElement.setImageSize) == 'function') {
                                $.IG_ImageElement.setImageSize(jsonObject.select_prop);
                            }
                            $("#"+id).val(jsonObject.select_url);
                            $("#"+id).trigger('change');
                            clearInterval(timer);
                            break;
                    }
                }
            }, 500);
        });

        $('#modalOptions .add_media').on('click', function(){
            _custom_media = false;
        });

    }

    $.HandleSetting.updateState = function(state){
        if(state != null){
            $.HandleSetting.doing = state;
        }
        else{
            if($.HandleSetting.doing == null || $.HandleSetting.doing)
                $.HandleSetting.doing = 0;
            else
                $.HandleSetting.doing = 1;
        }
    }

    $.HandleSetting.renderModal = function ()
    {
        if($( "#modalOptions" ).length == 0) return false;

        $( "#modalOptions" ).modal( 'toggle' );

        // Sortable sub-elements
        $("#group_elements").sortable({
            stop: function( event, ui ) {
                $.HandleSetting.shortcodePreview();
                $('body').trigger('on_after_reorder_element');
            }
        });
        $( "#group_elements" ).disableSelection();
        $('body').trigger('on_remove_handle_reorder');

        $.HandleSetting.setColorPicker( '#modalOptions .color-selector' );
        $.HandleSetting.setTipsyElement( '#modalOptions .ig-label-des-tipsy' );

        // toggle dependency params
        var ig_HasDepend = $( '#modalOptions .ig_has_depend' );
        ig_HasDepend.each( function ()
        {
            if(($(this).is(":radio") || $(this).is(":checkbox")) && !$(this).is(":checked")) return;
            var this_id  = $(this).attr( 'id' );
            var this_val  = $(this).val();
            $.HandleSetting.toggleDependency( this_id, this_val );
        });
    }

    $.HandleSetting.setTipsyElement = function ( selector ) {
        if ( ! selector )
            return false;
        // Setting tipsy
        $(selector).tipsy({
            title: function() {
                return this.getAttribute('data-title');
            },
            gravity: 'w',
            fade: true
        });
        return true;
    }

    $.HandleSetting.setColorPicker = function ( selector ) {
        if ( ! selector )
            return false;

        $( selector ).each(function () {
            var self	= $(this);
            var colorInput = self.siblings('input').last();
            var inputId 	= colorInput.attr('id');
            var inputValue 	= inputId.replace(/_color/i, '') + '_value';
            if ($('#' + inputValue).length){
                $('#' + inputValue).val($(colorInput).val());
            }

            self.ColorPicker({
                color: $(colorInput).val(),
                onShow: function (colpkr) {
                    $(colpkr).fadeIn(500);
                    return false;
                },
                onHide: function (colpkr) {
                    $(colpkr).fadeOut(500);
                    $.HandleSetting.shortcodePreview();
                    return false;
                },
                onChange: function (hsb, hex, rgb) {
                    $(colorInput).val('#' + hex);

                    if ($('#' + inputValue).length){
                        $('#' + inputValue).val('#' + hex);
                    }
                    self.children().css('background-color', '#' + hex);
                }
            });
        });
    }

    $.HandleSetting.selector = function (curr_iframe, element)
    {
        var $selector = (curr_iframe != null &&  curr_iframe.contents() != null) ? curr_iframe.contents().find(element) : $(element);
        return $selector;
    }


    $.HandleSetting.shortcodePreview = function (params, shortcode, curr_iframe, callback)
    {
        if(($.HandleSetting.selector(curr_iframe,"#modalOptions").length == 0 || $.HandleSetting.selector(curr_iframe,"#modalOptions").hasClass('submodal_frame')) && curr_iframe == null)
            return true;

        // change state to ACTIVE
        $.HandleSetting.updateState(1);

        // Set default params before get shortcode preview
        //$.HandleSetting.removeParams();

        var tmp_content = [];
        var params_arr = {};
        var shortcode_name, shortcode_type;
        if(params == null){
            shortcode_name = $.HandleSetting.selector(curr_iframe, '#modalOptions #shortcode_name' ).val();
            shortcode_type = $.HandleSetting.selector(curr_iframe, '#form-container #shortcode_type' ).val();
            // widget
            if(shortcode_type == 'widget'){
                var form_serialize = $.HandleSetting.selector(curr_iframe, '#modalOptions #ig-widget-form' ).serialize();
                $('input[type=checkbox]').each(function() {
                    if (!this.checked) {
                        form_serialize += '&'+this.name+'=0';
                    }
                });
                tmp_content = '[ig_widget widget_id="'+shortcode_name+'"]' + form_serialize + '[/ig_widget]';
            }
            // shortcode element
            else{
                tmp_content.push('['+ shortcode_name);
                var sc_content = '';
                $.HandleSetting.selector(curr_iframe, '#modalOptions .control-group' ).each( function ()
                {
                    if ( ! $(this).hasClass( 'ig_hidden_depend' ) )
                    {
                        $(this).find( "[id^='param-']" ).each(function(){
                            if(
                                $(this).parents(".tmce-active").length == 0 && !$(this).hasClass('tmce-active')
                                && $(this).parents(".html-active").length == 0 && !$(this).hasClass('html-active')
                                && !$(this).parents("[id^='parent-param']").hasClass( 'ig_hidden_depend' )
                                && $(this).attr('id').indexOf('parent-') == -1
                                )
                                {
                                var id = $(this).attr('id');
                                if($(this).attr('data-role') == 'content'){
                                    sc_content =  $(this).val();
                                }else{
                                    if(($(this).is(":radio") || $(this).is(":checkbox")) && !$(this).is(":checked"));
                                    else{
                                        if(!params_arr[id.replace('param-','')] || id.replace('param-', '') == 'title_font_face_type' || id.replace('param-', '') == 'title_font_face_value' || id.replace('param-','') == 'font_face_type' || id.replace('param-','') == 'font_face_value' || id.replace('param-', '') == 'image_type_post' || id.replace('param-', '') == 'image_type_page' || id.replace('param-', '') == 'image_type_category' ) {
                                            params_arr[id.replace('param-','')] = $(this).val();
                                        } else {
                                            params_arr[id.replace('param-','')] += '__#__' + $(this).val();
                                        }

                                    }
                                }

                                // data-share
                                if($(this).attr('data-share')){
                                    var share_element = $('#' + $(this).attr('data-share'));
                                    var share_data = share_element.text();
                                    if(share_data == "" || share_data == null)
                                        share_element.text($(this).val());
                                    else{
                                        share_element.text(share_data + ',' + $(this).val());
                                        var arr = share_element.text().split(',');
                                        $.unique( arr );
                                        share_element.text(arr.join(','));
                                    }

                                }

                                // data-merge
                                if($(this).parent().hasClass('merge-data')){
                                    var ig_merge_data = window.parent.jQuery.noConflict()( '#jsn_view_modal').contents().find('#ig_merge_data');
                                    ig_merge_data.text(ig_merge_data.text() + $(this).val());
                                }

                                // table
                                if($(this).attr("data-role") == "extract"){
                                    var extract_holder = window.parent.jQuery.noConflict()( '#jsn_view_modal').contents().find('#ig_extract_data');
                                    extract_holder.text(extract_holder.text()+$(this).attr("id")+':'+$(this).val()+'#');
                                }
                            }

                        });
                    }
                });


                // for shortcode which contain TinyMCE param
//                var tinymce_content = '';
//                if(tinymce && tinymce.activeEditor != null && $('#modalOptions .wp-editor-wrap').hasClass('tmce-active')){
//                    tinymce_content = tinymce.activeEditor.getContent();
//                }else{
//                    tinymce_content = $.HandleSetting.selector(curr_iframe, '#ig_tiny_mce' ).serialize().replace(/rich_content_param-[a-z_]+=/,'');
//                }
//                tinymce_content = decodeURIComponent(tinymce_content.replace(/\+/g, ' '));
//                sc_content += tinymce_content;

                // update tinymce content to #ig_share_data
                window.parent.jQuery.noConflict()( '#jsn_view_modal').contents().find('#ig_share_data').text(sc_content);

                // for shortcode which has sub-shortcode
                if($.HandleSetting.selector(curr_iframe,"#modalOptions").find('.has_submodal').length > 0)
                {
                    var sub_sc_content = [];
                    $.HandleSetting.selector(curr_iframe, "#modalOptions [name^='shortcode_content']" ).each(function(){
                        if ( ! $(this).hasClass('exclude_gen_shortcode') ) {
                            sub_sc_content.push($(this).text());
                        }
                    })
                    sc_content += sub_sc_content.join('');
                }

                // wrap key, value of params to this format: key = "value"
                $.each(params_arr, function(key, value){
                    if ( value ) {
                        if ( value instanceof Array ) {
                            value = value.toString();
                        }
                        tmp_content.push(key + '="' + value.replace(/\"/g,"&quot;") + '"');
                    }
                });

                tmp_content.push(']' + sc_content + '[/' + shortcode_name + ']');
                tmp_content	= tmp_content.join( ' ' );
            }
        }
        else{
            shortcode_name = shortcode;
            tmp_content = params;
        }

        // update shortcode content
        $.HandleSetting.selector(curr_iframe, '#shortcode_content' ).text( tmp_content );

        if(callback)
            callback();
        // change state to inactive
        $.HandleSetting.updateState(0);

        var url		= Ig_Ajax.adminroot;
        url			+= 'index.php?page=ig_modal_page&ig_shortcode_preview=1';
        url			+= '&ig_shortcode_name=' + shortcode_name;
        url			+= '&ig_nonce_check=' + Ig_Ajax._nonce;

        if ($('#shortcode_preview_iframe').length > 0){
            // asign value to a variable (for show/hide preview)
            $.HandleSetting.previewData = {
                curr_iframe: curr_iframe,
                url : url,
                tmp_content: tmp_content
            };
            // load preview iframe
            $.HandleSetting.loadIframe(curr_iframe, url, tmp_content);
        }
        return false;
    }

    // load preview iframe
    $.HandleSetting.loadIframe = function(curr_iframe, url, tmp_content){
        $('#ig_preview_data').remove();
        var tmp_form	= $('<form action="' + url + '" id="ig_preview_data" name="ig_preview_data" method="post" target="shortcode_preview_iframe"><input type="hidden" id="ig_preview_params" name="params" value="' + encodeURIComponent(tmp_content) + '"></form>');
        tmp_form.appendTo($('body'));

        $.HandleSetting.selector(curr_iframe, '#modalOptions #ig_overlay_loading').fadeIn('fast');
        $('#ig_preview_data').submit();
        $('#modalOptions #shortcode_preview_iframe').bind('load', function (){
            $('#modalOptions #shortcode_preview_iframe').fadeIn('fast');
            $.HandleSetting.selector(curr_iframe, '#modalOptions #ig_overlay_loading').fadeOut('fast');
            $('#ig_previewing').val('0');
        });
        // in case above 'load' action is not triggered
        setTimeout( function(){
            if($.HandleSetting.selector(curr_iframe, '#modalOptions #ig_overlay_loading').is(":visible"))
                $.HandleSetting.selector(curr_iframe, '#modalOptions #ig_overlay_loading').fadeOut('fast');
        }, 2000 );
        tmp_form.remove();
    }

    // hide/show preview
    $.HandleSetting.togglePreview = function(){
        $('#previewToggle *').click(function(){
            if($(this).attr('id') == 'hide_preview'){
                $(this).addClass('hidden');
                $('#show_preview').removeClass('hidden');
                // remove iframe
                $('#preview_container iframe').remove();
            }
            else{
                $(this).addClass('hidden');
                $('#hide_preview').removeClass('hidden');
                $('#preview_container').append("<iframe scrolling='no' id='shortcode_preview_iframe' name='shortcode_preview_iframe' class='shortcode_preview_iframe' ></iframe>");
                if($.HandleSetting.previewData != null){
                    var data = $.HandleSetting.previewData;
                    $.HandleSetting.loadIframe(data.curr_iframe, data.url, data.tmp_content);
                }
            }
        });
    }

    // Show or hide dependency params
    $.HandleSetting.toggleDependency = function ( this_id, this_val )
    {
        if(!this_id || !this_val) return;
        $( '#modalOptions .ig_depend_other[data-depend-element="'+this_id+'"]' ).each( function ()
        {
            var operator 		= $(this).attr( 'data-depend-operator' );
            var compare_value 	= $(this).attr( 'data-depend-value' );
            switch( operator )
            {
                case "=":
                {
                    var check_ = 0;
                    if(compare_value.indexOf('__#__') > 0){
                        var values_ = compare_value.split('__#__');
                        check_ = ($.inArray(this_val, values_) >= 0);
                    }
                    else
                        check_  = (this_val == compare_value);
                    if( check_ )
                        $(this).removeClass( 'ig_hidden_depend' );
                    else
                        $(this).addClass( 'ig_hidden_depend' );

                }
                break;
                case ">":
                {
                    if( this_val > compare_value )
                        $(this).removeClass( 'ig_hidden_depend' );
                    else
                        $(this).addClass( 'ig_hidden_depend' );

                }
                break;
                case "<":
                {
                    if( this_val < compare_value )
                        $(this).removeClass( 'ig_hidden_depend' );
                    else
                        $(this).addClass( 'ig_hidden_depend' );

                }
                case "!=":
                {
                    if( this_val != compare_value )
                        $(this).removeClass( 'ig_hidden_depend' );
                    else
                        $(this).addClass( 'ig_hidden_depend' );

                }
                break;

            }
            $.HandleSetting.secondDependency($(this).attr('id'), $(this).hasClass('ig_hidden_depend'), $(this).find('select').hasClass('no_plus_depend'));
        });
    }

    // show/hide 2rd level dependency elements
    $.HandleSetting.secondDependency = function(this_id, hidden, allow){
        if(!this_id) return;
        this_id = this_id.replace('parent-','');

        $( '#modalOptions .ig_depend_other[data-depend-element="'+this_id+'"]' ).each( function ()
        {
            if(hidden)
                $(this).addClass( 'ig_hidden_depend2' );
            else
                $(this).removeClass( 'ig_hidden_depend2' );
        });
        if ( ! allow ) {
            $( '#modalOptions .ig_depend_other[data-depend-element="'+this_id+'"]' ).each( function ()
            {
                $(this).removeClass( 'ig_hidden_depend2' );
            });
        }

        // hide label if all options in .controls div have 'ig_hidden_depend' class
        $( '#modalOptions .controls').each(function(){
            var hidden_div = 0;
            $(this).children().each(function(){
                if($(this).hasClass('ig_hidden_depend'))
                    hidden_div++;
            });
            if(hidden_div > 0 && hidden_div == $(this).children().length){
                $(this).parent('.control-group').addClass('margin0');
                $(this).prev('.control-label').hide();
            }
            else{
                $(this).parent('.control-group').removeClass('margin0');
                $(this).prev('.control-label').show();
            }
        });
    }
    // Set change event of dependency elements
    $.HandleSetting.changeDependency = function ( dp_selector )
    {
        if ( ! dp_selector )
            return false;
        $( '#modalOptions' ).delegate( dp_selector, 'change', function ()
        {
            var this_id		= $(this).attr( 'id' );
            var this_val	= $(this).val();
            $.HandleSetting.toggleDependency( this_id, this_val );
        });
    }

    // Show tab in Modal Options
    $.HandleSetting.tab = function ()
    {
        $('#ig_option_tab a[href="#styling"]').on('click', function () {
            if($('#ig_previewing').val() == '1')
                return;
            $('#ig_previewing').val('1');
            $.HandleSetting.shortcodePreview();
        });
        if(!$('.jsn-tabs').find("#Notab").length)
            $('.jsn-tabs').tabs();
        return true;
    }

    $.HandleSetting.select2 = function ()
    {
        $(".select2").each(function(){
            var share_element = window.parent.jQuery.noConflict()( '#jsn_view_modal').contents().find('#' + $(this).attr('data-share'));
            var  share_data = [];
            if(share_element && share_element.text() != ""){
                share_data = share_element.text().split(',');
                share_data = $.unique(share_data);
            }
            $(this).css('width','300px');
            $(this).select2({
                tags:share_data,
                maximumInputLength: 10
            });
        })

        $('.select2-select').each(function () {
            var id = $(this).attr('id');
            if ($('#' + id + '_select_multi').val()) {
                var arr_select_multi = $('#' + id + '_select_multi').val().split('__#__');
                $(this).val(arr_select_multi).select2();
            } else {
                $(this).select2();
            }
        });

        $.HandleSetting.select2_color();
    }

    $.HandleSetting.select2_color = function (){
        function format(state) {
            if (!state.id) return state.text; // optgroup
            var type = state.id.toLowerCase();
            type = type.split('-');
            type = type[type.length - 1];
            return "<img class='color_select2_item' src='"+Ig_Translate.asset_url+"images/icons-16/btn-color/" + type + ".png'/>" + state.text;
        }
        $('.color_select2').not('.hidden').each(function () {
            $(this).find('select').each(function(){
                $(this).select2({
                    formatResult: format,
                    formatSelection: format,
                    escapeMarkup: function(m) {
                        return m;
                    }
                });
            });
        });
    }

    /**
     * Handle icon change action in Modal box
     */
    $.HandleSetting.icons = function ()
    {
        // Icon type: handle click on an icon
        $("#form-container").delegate("[data-type='ig-icon-item']","click",function(){
            $(".controls .icon-selected").each(function(){
                $(this).removeClass('icon-selected');
            });
            // update selected icon
            var $selected_icon = $(this).attr('class').replace('icon-selected', '').replace(' ','');
            var icon = $(this).parent('li').parent('ul').next("input[id^='param']");
            icon.val($selected_icon);
            icon.trigger('change');
            $(this).addClass('icon-selected');
        });

    }

    // Handle click action on Button in Modal: Convert action/ Add Row, Column / ...
    $.HandleSetting.actionHandle = function ()
    {
        // Handle Convert To ... button
        $(".ig_action_btn").delegate("a", "click", function(e){
            e.preventDefault();

            var action_type = $(this).attr('data-action-type');
            var action = $(this).attr('data-action');
            if(action_type && action){
                var action_data = {};
                action_data[action_type] = action;
                $.HandleElement.updateBeforeClose(action_data);
            }
        });
    }

})(jQuery);