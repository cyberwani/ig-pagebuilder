(function($) {
    "use strict";
    $.IGModal = $.IGModal || {};
    $.HandleElement = $.HandleElement || {};
    $.PbDoing = $.PbDoing || {};
    $.HandleSetting = $.HandleSetting || {};

    $.options = {
        min_column_span : 2,
        layout_span : 12,
        new_sub_element : false,
        curr_iframe_ : null,
        clicked_column : null,
        if_childmodal : 0,
        modal_settings : {
            modalId: 'jsn_view_modal',
            sub_modalId: 'jsn_view_modal_sub',
            sub_modalChildId: 'jsn_view_modal_sub_child'
        },
        effect: 'easeOutCubic'
    }

    var clk_title_el , append_title_el;
    var el_type; // save type of editing shortcode: element/widget

    /**
	 * 1. Common
     * 2. Resizable
     * 3. PageBuilder
     * 4. Modal
	 */

    /***************************************************************************
	 * 1. Common
	 **************************************************************************/
    $.HandleElement.selector = function(curr_iframe, element) {
        var $selector = (curr_iframe != null && curr_iframe.contents() != null) ? curr_iframe.contents().find(element) : window.parent.jQuery.noConflict()(element);
        return $selector;
    },
    $.HandleElement.capitalize = function(text) {
        return text.charAt(0).toUpperCase()
        + text.slice(1).toLowerCase();
    },
    $.HandleElement.ucwords = function(text) {
        return (text + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
            return $1.toUpperCase();
        });
    },
    $.HandleElement.remove_underscore_ucwords = function(text) {
        var arr = text.split('_');
        return $.HandleElement.ucwords( arr.join(' ') ).replace(/^(Wp)\s+/g, '');
    },
    $.HandleElement.sliceContent = function(text) {
        text = unescape(text);
        text = text.replace(/\+/g, ' ');

        var arr = text.split(' ');
        arr = arr.slice(0, 10);
        return arr.join(' ');
    },
    $.HandleElement.getCookie = function ( c_name ) {
        if ( ! c_name )
            return null;
        c_name = c_name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(c_name) == 0) return c.substring(c_name.length,c.length);
        }
        return null;
    },
    $.HandleElement.setCookie = function ( c_name, c_value ) {
        c_value = c_value + ";max-age=" + 60 * 3 + ";path=/";
        document.cookie	= c_name + "=" + c_value;
    },
    $.HandleElement.removeCookie = function ( c_name ) {
        if ( ! c_name )
            return null;
        document.cookie = c_name + "=;max-age=0; path=/";
    }

    /*******************************************************************
	 * 3. PageBuilder
	 ******************************************************************/

    /**
	 * add Element to Pagebuilder when click on an element in Add Elements Popover
	 */
    $.HandleElement.addElement = function() {
        $("body").delegate(".ig-add-element .shortcode-item","click",function(e) {
            self_(e, this);
        });
        $("#ig-add-element").delegate(".shortcode-item","click",function(e) {
            self_(e, this);
        });

        function self_(e, this_){
            e.preventDefault();

            if($.PbDoing.addElement)
                return;
            $.PbDoing.addElement = 1;
            // check if adding shortcode from Inno button in Classic Editor
            if($(this_).parents('#ig-shortcodes').length)
                top.addInClassic = 1;

            $("#ig-add-element").hide();
            $.HandleElement.showLoading();

            clk_title_el = $.trim($(this_).html().replace(/<i\sclass.*><\/i>/, ''));
            var $shortcode = $(this_).attr('data-shortcode');
            var $type = $(this_).parent().attr('data-type');
            $.HandleElement.appendToHolder($shortcode, null, $type);
        }
    },

    /**
	 * Add sub Item on Modal setting of an element (Accordion, Tab, Carousel...)
	 */
    $.HandleElement.addItem = function() {
        $("#form-container").delegate(".ig-more-element","click",function(e) {
            e.preventDefault();

            $.options.clicked_column = $(this).parent('.item-container').find('.item-container-content');
            // add item in Accordion/ List ...
            if ($(this).attr('data-shortcode-item') != null) {
                $.HandleElement.showLoading();

                $.options.new_sub_element = true;
                var $count = $.options.clicked_column.find(".jsn-item").length;
                var $replaces = {};
                $replaces['index'] = parseInt($count) + 1;
                $.HandleElement.appendToHolder($(this).attr('data-shortcode-item'), $replaces);
            }
        });
    },

    /**
	 * delete an element (a row OR a column OR an shortcode item)
	 */
    $.HandleElement.deleteElement = function() {
        $("#form-container").delegate(".element-delete","click",function(){
            var msg,is_column;
            if($(this).hasClass('row') || $(this).attr("data-target") == "row_table"){
                msg = Ig_Translate.delete_row;
            }else if($(this).hasClass('column') || $(this).attr("data-target") == "column_table"){
                msg = Ig_Translate.delete_column;
                is_column = 1;
            }else{
                msg = Ig_Translate.delete_element;
            }

            var confirm_ = confirm(msg);
            if(confirm_){
                var $column = $(this).parent('.jsn-iconbar').parent('.shortcode-container');
                if(is_column == 1)
                {
                    // Delete a Column in Table element
                    if($(this).attr("data-target") == "column_table")
                    {
                        var table = new $.IGTable();
                        table.deleteColRow($(this), 'column', Ig_Translate);
                        $.HandleSetting.shortcodePreview();
                    }
                    else{
                        var $row = $column.parent('.row-content').parent('.row-region');
                        // if is last column of row, remove parent row
                        if($column.parent('.row-content').find('.column-region').length == 1){
                            $.HandleElement.removeElement($row);
                        }else{
                            $.HandleElement.removeElement($column);
                        }
                    }
                }
                else{
                    // Delete a Row in Table element
                    if($(this).attr("data-target") == "row_table"){
                        table = new $.IGTable();
                        table.deleteColRow($(this), 'row', Ig_Translate);
                        $.HandleSetting.shortcodePreview();
                    }else{
                        $.HandleElement.removeElement($column);
                    }
                }
            }
        });
    },
    // request to get html template of shortcode
    $.HandleElement.getShortcodeTpl = function($shortcode, $type, callback){
        $.post(
            Ig_Ajax.ajaxurl,
            {
                action 		: 'get_shortcode_tpl',
                shortcode   : $shortcode,
                type   : $type,
                ig_nonce_check : Ig_Ajax._nonce
            },
            function( data ) {
                callback(data);
            })
    },

    /**
	 * Add an element to Parent Holder (a column [in PageBuilder], a
	 * group list[in Modal of Accordion, Tab...])
	 */
    $.HandleElement.appendToHolder = function($shortcode, $replaces, $type, sc_html, elem_title) {
        var append_to_div = $("#form-design-content #form-container");
        if(!$(this).hasClass('layout-element') && $.options.clicked_column != null){
            append_to_div = $.options.clicked_column;
        }

        // get HTML template of shortcode
        var html;
        if ( sc_html ) {
            $.HandleElement.appendToHolderFinish($shortcode, sc_html, $replaces, append_to_div, null, elem_title);
        } else {
            if($("#tmpl-"+$shortcode).length == 0){
                // request to get html template of shortcode
                $type = ($type != null) ? $type : 'element';
                $.HandleElement.getShortcodeTpl($shortcode, $type, function(data){
                    $('body').append(data);
                    html = $("#tmpl-"+$shortcode).html();
                    $.HandleElement.appendToHolderFinish($shortcode, html, $replaces, append_to_div, $type, elem_title);
                });
            }
            else{
                html = $("#tmpl-"+$shortcode).html();
                $.HandleElement.appendToHolderFinish($shortcode, html, $replaces, append_to_div, null, elem_title);
            }
        }
    },
    $.HandleElement.elTitle = function($shortcode, clk_title_el, exclude_this){
        if(clk_title_el == '')
            return '';
        var count_element = $("#form-container").find("a.element-edit[data-shortcode='"+$shortcode+"']").length;
        exclude_this = (exclude_this != null) ? exclude_this : 0;
        return clk_title_el + ' ' + parseInt(count_element + 1 - exclude_this);
    },

    $.HandleElement.appendToHolderFinish = function($shortcode, html, $replaces, append_to_div, $type, elem_title) {
        // hide popover
        $("#ig-add-element").hide();
        // count existing elements which has same type
        append_title_el = $.HandleElement.elTitle($shortcode, clk_title_el);
        if ( append_title_el.indexOf('undefined') >= 0 ) {
            append_title_el = ''
        }
        if ( elem_title ) {
            append_title_el = elem_title;
        }
        if($type != null && $type == 'widget'){
            html = ig_pb_remove_placeholder(html, 'widget_title', 'title='+append_title_el);
        }else{
            html = html.replace(/el_title=\"\"/, 'el_title="'+append_title_el+'"');
        }

        html = ig_pb_remove_placeholder(html, 'extra_class', 'ig-selected-element');
        if($replaces != null){
            html = ig_pb_remove_placeholder(html, 'index', $replaces['index']);
        }
        else{
            var idx = 0;
            html = ig_pb_remove_placeholder(html, 'index', function(match, number){
                return ++idx;
            });
        }
        // animation
        append_to_div.append(ig_pb_remove_placeholder(html, 'custom_style', 'style="display:none"'));
        var new_el = append_to_div.find('.jsn-element').last();
        var height_ = new_el.height();
        $.HandleElement.appendElementAnimate(new_el, height_);

        // Show loading image
        if ( $(append_to_div).find('.jsn-item').length ) {
            $(append_to_div).find('.jsn-item').last().append('<i class="jsn-icon16 jsn-icon-loading"></i>');
        }

        // open Setting Modal box right after add new element
        $(".ig-selected-element .element-edit").trigger('click');
        $(".ig-selected-element").removeClass('ig-selected-element');


    }

    // animation when add new element to container
    $.HandleElement.appendElementAnimate = function(new_el, height_, callback, finished){
        var obj_return = {
            obj_element:new_el
        };
        $('body').trigger('on_clone_element_item', [obj_return]);
        new_el = obj_return.obj_element;
        new_el.css({
            'min-height' : 0,
            'height' : 0,
            'opacity' : 0
        });
        new_el.addClass('padTB0');
        if(callback)callback();
        new_el.show();
        new_el.animate({
            height: height_
        },500,$.options.effect, function(){
            $(this).animate({
                opacity:1
            },300,$.options.effect,function(){
                new_el.removeClass('padTB0');
                new_el.css('height', 'auto');
                $('body').trigger('on_update_attr_label_common');
                $('#form-container').trigger('ig-pagebuilder-layout-changed');
                if(finished)finished();
            });
        });
    }

    /**
	 * Remove an element in Pagebuilder / In Modal
	 */
    $.HandleElement.removeElement = function(element) {
        element.css({
            'min-height' : 0,
            'overflow' : 'hidden'
        });
        element.animate({
            opacity:0
        },300,$.options.effect,function(){
            element.animate({
                height:0,
                'padding-top' : 0,
                'padding-bottom' : 0
            },300,$.options.effect,function(){
                element.remove();
                $('body').trigger('on_after_delete_element');
                // for shortcode which has sub-shortcode
                if ($("#modalOptions").find('.has_submodal').length > 0){
                    $.HandleElement.rescanShortcode();
                }
                $('#form-container').trigger('ig-pagebuilder-layout-changed');
            });
        });
    },

    // Add Premade Layout
    $.HandleElement.addLayout = function() {
        var select_layout = 0, go = true;
        var selected_layout = 'selected-layout';

        // Show modal of layouts
        var modal_width = ($(window).width() > 750) ? 750 : $(window).width()*0.9;
        var modal_height = $(window.parent).height()*0.9;
        var frameId = 'ig-layout-lib-modal';
        $('#apply-layout').click(function(){
            $("#ig-add-layout").hide();

            var modal = new $.IGModal({
            frameId: frameId,
            jParent : window.parent.jQuery.noConflict(),
            title: 'Select layouts',
            url: Ig_Ajax.ig_modal_url + '&ig_layout=1',
            buttons: [{
                'text'	: Ig_Translate.button.select,
                'id'	: 'selected',
                'class' : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only btn-disabled',
                'click'	: function () {
                    if($(this).hasClass('btn-disabled'))
                        return false;
                    var curr_iframe = window.parent.jQuery.noConflict()( '#' + frameId );
                    var selected_layout_el = curr_iframe.contents().find('.' + selected_layout);
                    if (!selected_layout_el.length) {
                        return false;
                    }
                    var layout_content = selected_layout_el.find('textarea').val();
                    if ( !select_layout ) {
                        go = confirm( Ig_Translate.select_layout );
                    }
                    if ( select_layout || go ) {
                        console.log(layout_content);
                        // close modal
                        $.HandleElement.hideLoading();
                        $.HandleElement.removeModal();
                        // update content
                        $.HandleElement.updatePageBuilder( layout_content );
                    }
                    select_layout++;
                }
            }, {
                'text'	: Ig_Ajax.cancel,
                'id'	: 'close',
                'class' : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only',
                'click'	: function () {
                    $.HandleElement.hideLoading();
                    $.HandleElement.removeModal();
                }
            }],
            loaded: function (obj, iframe) {
            },
            fadeIn:200,
            scrollable: true,
            width: modal_width,
            height: modal_height
        });
        modal.show();
        });

        // filter layout
        $('#ig-layout-lib .jsn-filter-button').on('change', function(){
            var layout_type = $(this).val();
            $('#ig-layout-lib .jsn-items-list').find('li[data-type!="'+layout_type+'"]').addClass('hidden');
            $('#ig-layout-lib .jsn-items-list').find('li[data-type="'+layout_type+'"]').removeClass('hidden').hide();
            $('#ig-layout-lib .jsn-items-list').find('li[data-type="'+layout_type+'"]').fadeIn( 1000 );
        });

        // click on a layout
        $('#ig-layout-lib .premade-layout-item').on('click', function(){
            $('#ig-layout-lib .premade-layout-item.'+selected_layout).removeClass(selected_layout);
            $(this).toggleClass(selected_layout);
            window.parent.jQuery.noConflict()('.ui-dialog-buttonset .btn-disabled').removeClass('btn-disabled');
        });


//        $("#ig-add-layout").delegate(".premade-layout-item","click",function(){
//            var layout_id = $(this).attr('data-id');
//            var layout_content = $('#tmpl-layout-' + layout_id).html();
//            $.HandleElement.updatePageBuilder( layout_content);
//        });
    },

    // Save Premade Layout
    $.HandleElement.saveLayout = function() {
        $('#btn-layout-cancel').click(function(e){
           $('#ig-add-layout #save-layout').toggleClass('hidden');
           $('#ig-add-layout #save-layout-form').toggleClass('hidden');
        });
        $('#btn-layout-add').click(function(e){
            var this_   = $(this);
            var loading = $('#ig-add-layout #save-layout-loading');
            e.preventDefault();
            var layout_name = $('#ig-add-layout #layout-name').val();
            if (layout_name.trim() != '') {
                loading.toggleClass('hidden');
                $('#ig-add-layout #save-layout-form').toggleClass('hidden');
                // ajax post to save
                var layout_content = '';
                $("#form-container textarea[name^='shortcode_content']").each(function(){
                    layout_content += $(this).val();
                });
                layout_content = ig_pb_remove_placeholder(layout_content, 'wrapper_append', '');

                $.post(
                    Ig_Ajax.ajaxurl,
                    {
                        action          : 'save_layout',
                        layout_name     : layout_name,
                        layout_content	: layout_content,
                        ig_nonce_check  : Ig_Ajax._nonce
                    },
                    function( data ) {
                        loading.toggleClass('hidden');
                        $('#save-layout-messsage').toggleClass('hidden');

                        // hide save layout box
                        setTimeout(function(){
                            $('#save-layout-messsage').toggleClass('hidden');
                            $('#ig-add-layout #save-layout').toggleClass('hidden');
                        }, 2000 );
                    }
                );
            }
        });
    },

    // Clone an Element
    $.HandleElement.cloneElement = function() {
        $("#form-container").delegate(".element-clone","click",function(){
            if($.PbDoing.cloneElement)
                return;
            $.PbDoing.cloneElement = 1;

            var parent_item = $(this).parent('.jsn-iconbar').parent('.jsn-item');
            var height_ = parent_item.height();
            var clone_item = parent_item.clone(true);

            var item_class = $('#modalOptions').length ? '.jsn-item-content' : '.ig-pb-element';
            // update title for clone element
            var html = clone_item.html();
            if(item_class == '.jsn-item-content')
                append_title_el = parent_item.find(item_class).html();
            else
                append_title_el = parent_item.find(item_class).find('span').html();
            if (append_title_el) {
                var regexp = new RegExp(append_title_el, "g");
                html = html.replace(regexp, append_title_el + ' ' + Ig_Translate.copy);
            }
            clone_item.html(html);

            // add animation before insert
            $.HandleElement.appendElementAnimate(clone_item, height_, function(){
                clone_item.insertAfter(parent_item);
                if($('#form-container').hasClass('fullmode')){
                    // active iframe preview for cloned element
                    $(clone_item[0]).find('form.shortcode-preview-form').remove();
                    $(clone_item[0]).find('iframe').remove();
                    $.HandleElement.turnOnShortcodePreview(clone_item[0]);
                }

                $.HandleElement.rescanShortcode();
            }, function(){
                $.PbDoing.cloneElement = 0;
            });
        });
    },

    // dDeactivate an Element
    $.HandleElement.deactivateElement = function() {
        $("#form-container").delegate(".element-deactivate","click",function(){
            var parent_item = $(this).parents('.jsn-item');
            var textarea    = parent_item.find("[data-sc-info^='shortcode_content']").first();
            var textarea_text = textarea.text();

            var child_i = $(this).find('i');
            if(child_i.hasClass('icon-checkmark')){
                textarea_text = textarea_text.replace('disabled="yes"', 'disabled="no"');
                // update icon
                child_i.removeClass('icon-checkmark').addClass('icon-cancel');
                // update title
                $(this).attr('title', Ig_Translate.disabled.deactivate);
            } else {
                if ( textarea_text.indexOf('disabled="no"') > 0 ) {
                    textarea_text = textarea_text.replace('disabled="no"', 'disabled="yes"');
                } else {
                    textarea_text = textarea_text.replace(']', ' disabled="yes" ]');
                }
                // update icon
                child_i.removeClass('icon-cancel').addClass('icon-checkmark');
                // update title
                $(this).attr('title', Ig_Translate.disabled.reactivate);
            }
            parent_item.toggleClass('disabled');
            // replace shortcode content
            textarea.text(textarea_text);
            $('#form-container').trigger('ig-pagebuilder-layout-changed');
        });
    },

    // Edit an Element in Pagebuilder / in Modal
    $.HandleElement.editElement = function() {
        $("#form-container").delegate(".element-edit","click",function(e, restart_edit){
            e.preventDefault();
            $.HandleElement.showLoading();

            if($.PbDoing.editElement && restart_edit == null)
                return;
            $.PbDoing.editElement = 1;

            $("#form-container .active-shortcode").removeClass('active-shortcode');
            var parent_item, shortcode = $(this).attr("data-shortcode"), el_title = '';
            if($(this).hasClass('row')){
                parent_item = $(this).parent('.jsn-iconbar').parent('.jsn-row-container');
                el_type		= 'element';
            }
            else{
                parent_item = $(this).parent('.jsn-iconbar').parent('.jsn-item');
                el_type		= parent_item.attr('data-el-type');
            }
            parent_item.addClass('active-shortcode');

            $.HandleElement.removeModal();

            if(el_type == 'widget'){
                el_title = $.HandleElement.elTitle(shortcode, clk_title_el, 1);
            }
            var params		= parent_item.find("[data-sc-info^='shortcode_content']").first().text();


            var title = $.HandleElement.getModalTitle(shortcode, parent_item.attr('data-modal-title'));
            var frameId = $.options.modal_settings.modalId;
            var has_submodal = 0;
            if( $(this).parents('.has_submodal').length > 0 ){
                has_submodal = 1;
                frameId = $.options.modal_settings.sub_modalId;
            }

            if( $(this).parents('.has_childsubmodal').length > 0 ){
                has_submodal = 1;
                frameId = $.options.modal_settings.sub_modalChildId;
            }

            var frame_url = Ig_Ajax.ig_modal_url + '&ig_modal_type=' + shortcode;

            var form = $("<form/>").attr({
                method: "post",
                style: "display:none",
                action: frame_url,
                target: frameId
            });
            form.append($("<input/>").attr( { name : "shortcode", value : shortcode } ) );
            form.append($("<textarea/>").attr( { name : "params", value : params } ) );
            form.append($("<input/>").attr( { name : "el_type", value : el_type } ) );
            form.append($("<input/>").attr( { name : "el_title", value : el_title } ) );
            form.append($("<input/>").attr( { name : "submodal", value : has_submodal } ) );
            $("body").append(form);

            // save data of shortcode to preview in new Modal window
            var modal_width, modal_height;
            if( has_submodal == 0 ){
                modal_width = ($(window).width() > 750) ? 750 : $(window).width()*0.9;
                modal_height = $(window.parent).height()*0.9;
            }
            else{
                modal_width = (parent.document.body.clientWidth > 800) ? 800 : parent.document.body.clientWidth*0.9;
                modal_height = parent.document.body.clientHeight*0.95;
            }
            var modal = new $.IGModal({
                frameId: frameId,
                jParent : window.parent.jQuery.noConflict(),
                title: $.HandleElement.remove_underscore_ucwords(title),
                ///url: Ig_Ajax.ig_modal_url + '&ig_modal_type=' + shortcode,
                buttons: [{
                    'text'	: Ig_Ajax.save,
                    'id'	: 'selected',
                    'class' : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only',
                    'click'	: function () {
                        $('body').trigger('add_exclude_jsn_item_class');
                        if($.HandleSetting.doing != null && $.HandleSetting.doing){
                            alert(Ig_Translate.saving);
                            return false;
                        }
                        var curr_iframe = window.parent.jQuery.noConflict()( '#' + frameId );
                        $.HandleElement.closeModal(curr_iframe);
                    }
                }, {
                    'text'	: Ig_Ajax.cancel,
                    'id'	: 'close',
                    'class' : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only',
                    'click'	: function () {
                        $('body').trigger('add_exclude_jsn_item_class');
                        var curr_iframe = window.parent.jQuery.noConflict()( '#' + frameId );
                        var is_submodal = curr_iframe.contents().find('.submodal_frame').length;
                        $.HandleElement.finalize(is_submodal);

                        // update Element Title to Active element (only for not child element)
                        if(!$.options.new_sub_element && append_title_el){
                            var active_title = $("#form-container .active-shortcode").find('.ig-pb-element').first();
                            active_title.html(active_title.html().split(':')[0] + ": " + '<span>'+append_title_el+'</span>');
                            append_title_el = '';
                        }

                        // remove loading image from active child element
                        $("#form-container .active-shortcode").find('.jsn-icon-loading').remove();

                        $("#form-container .active-shortcode").removeClass('active-shortcode');

                        $('body').trigger('on_update_shortcode_widget', 'is_cancel');
                    }
                }],
                loaded: function (obj, iframe) {
                    $('body').trigger('ig_submodal_load',[iframe]);

                    // remove title of un-titled element
                    var title = $(iframe).contents().find('[data-role="title"]').val();
                    var index = ig_pb_get_placeholder( 'index' );
                    if ( title != null && title.indexOf(index) >= 0 ) {
                        $(iframe).contents().find('[data-role="title"]').val('');
                    }
                },
                fadeIn:200,
                scrollable: true,
                width: modal_width,
                height: modal_height
            });
            modal.show( function(){
                window.parent.document.getElementById(frameId).src = frame_url;
				window.parent.document.getElementById(frameId).name = frameId;
                //$('#'+frameId).attr('src', frame_url);
                //$('#'+frameId).attr('name', frameId);

                form.submit();
                setTimeout(function(){form.remove();}, 2000);
            } );
        });
    },

    // fix error of TinyMCE on Modal setting iframe
    $.HandleElement.fixTinyMceError = function(){
        $('#content-html').trigger('click');
    },

    /*******************************************************************
	 * 4. Modal
	 ******************************************************************/

    /**
	 * Generate Title for Modal
	 */
    $.HandleElement.getModalTitle = function(shortcode, modal_title) {
        var title = Ig_Translate.page_modal;
        if (shortcode != '') {
            if(modal_title)
                title = modal_title;
            else{
                shortcode = shortcode.replace('ig_','').replace('_',' ');
                title = $.HandleElement.capitalize(shortcode);
            }
        }
        return title + ' ' + Ig_Translate.settings;
    },

    /**
	 * Remove Modal, Show Loading, Hide Loading
	 */
    $.HandleElement.removeModal = function() {
        $('.jsn-modal').remove();
    },

    // Show Overlay & Loading of Modal
    $.HandleElement.showLoading = function() {
        var $selector = $;//window.parent.jQuery.noConflict();

        var $overlay = $selector('.jsn-modal-overlay');
        if ($overlay.size() == 0) {
            $overlay = $('<div/>', {
                'class': 'jsn-modal-overlay'
            });
        }

        var $indicator = $selector('.jsn-modal-indicator');
        if ($indicator.size() == 0) {
            $indicator = $('<div/>', {
                'class': 'jsn-modal-indicator'
            });
        }

        $selector('body')
        .append($overlay)
        .append($indicator);
        $overlay.css({
            'z-index': 100
        }).show();
        $indicator.show();
    },

    // Hide Overlay of Modal
    $.HandleElement.hideLoading = function() {
        var $selector = $;//window.parent.jQuery.noConflict()
        $selector('.jsn-modal-overlay').hide();
        $selector('.jsn-modal-indicator').hide();
    },

    /**
	 * Extract shortcode params of sub-shortcodes, then update merged
	 * data to a #div
	 */
    $.HandleElement.extractParam = function(shortcode_, param_,
        updateTo_) {
        var sub_data = [];
        $("#modalOptions #group_elements .jsn-item").each(function() {
            sub_data.push($(this).find('textarea').text());
        });
        $.post(Ig_Ajax.ajaxurl, {
            action : 'shortcode_extract_param',
            param : param_,
            shortcode : shortcode_,
            data : sub_data.join(""),
            ig_nonce_check : Ig_Ajax._nonce
        }, function(data) {
            $(updateTo_).text(data);
        });
    },

    /**
	 * For Parent Shortcode: Rescan sub-shortcodes content, call preview
	 * function to regenerate preview
	 */
    $.HandleElement.rescanShortcode = function(curr_iframe, callback) {
        try {
            $.HandleSetting.shortcodePreview(null, null, curr_iframe, callback);
        } catch (err) {
            console.log(err);
        }
    },

    /**
	 * save shortcode data before close Modal
	 */
    $.HandleElement.closeModal = function(curr_iframe) {
        $.options.curr_iframe_ = curr_iframe;
        var submodal = curr_iframe.contents().find('.has_submodal');
        var childsubmodal = curr_iframe.contents().find('.has_childsubmodal');
        if(submodal.length > 0 && ! childsubmodal )
        {
            // Advance shortcodes like Tabs, Accordion
            $.HandleElement.updateBeforeClose();
        }else{
            if(curr_iframe.contents().find('.submodal_frame').length)
            {
                $.options.if_childmodal = 1;
                // call Preview to get content of params + tinymce. Finally, update #shortcode_content, Close Modal, call Preview of parents shortcode
                // for sub modal child
                $.HandleElement.rescanShortcode(curr_iframe, function(){
                    $.HandleElement.finishCloseModal(curr_iframe, window.parent.jQuery.noConflict()('#'+$.options.modal_settings.modalId));
                });
            }
            else{
                $.HandleElement.finishCloseModal(curr_iframe);
            }

        }
    },

    /**
	 * Parent shortcodes like Tabs, Accordion: Collection sub-shortcode
	 * content and update to #shortecode_content before close
	 */
    $.HandleElement.updateBeforeClose = function(action_data) {
        if(action_data != null){
            $.options.curr_iframe_ = window.parent.jQuery.noConflict()( '#' + $.options.modal_settings.modalId);
        }
        // get sub-shorcodes content
        var sub_sc_content = [];
        $.options.curr_iframe_.contents().find( "#modalOptions [name^='shortcode_content']" ).each(function() {
            sub_sc_content.push($(this).text());
        })
        sub_sc_content = sub_sc_content.join('');

        $.post(
            Ig_Ajax.ajaxurl,
            {
                action 		: 'update_whole_sc_content',
                shortcode_content 	: $.options.curr_iframe_.contents().find( '#shortcode_content' ).text(),
                sub_shortcode_content    : sub_sc_content,
                ig_nonce_check : Ig_Ajax._nonce
            },
            function( data ) {
                $.options.curr_iframe_.contents().find( '#shortcode_content' ).text(data);
                $.HandleElement.finishCloseModal($.options.curr_iframe_, null, action_data);
            })
    },

    /**
	 * update shortcode-content & close Modal & call preview (shortcode
	 * has sub-shortcode) action_data: null (Save button) OR { 'convert' :
	 * 'tab_to_accordion'}
	 */
    $.HandleElement.finishCloseModal = function(curr_iframe, update_iframe, action_data) {
        var shortcode_content	= curr_iframe.contents().find( '#shortcode_content' ).text();

        // trigger update shortcode for widget pagebuilder element
        $('body').trigger('on_update_shortcode_widget', [shortcode_content]);

        var item_title = "", title_prepend, title_prepend_val = "";
        if(curr_iframe.contents().find('[data-role="title"]').length)
        {
            title_prepend = curr_iframe.contents().find('[data-role="title_prepend"]');
            title_prepend_val = '';
            if(title_prepend.length){
                title_prepend = title_prepend.first();
                var title_prepend_type = title_prepend.attr("data-title-prepend");
                title_prepend_val = title_prepend.val();
                if ( typeof( title_prepend_val ) != "undefined" && Ig_Js_Html[title_prepend_type]) {
                    title_prepend_val = ig_pb_remove_placeholder(Ig_Js_Html[title_prepend_type], 'standard_value', title_prepend.val());
                }
            }

            item_title = title_prepend_val + curr_iframe.contents().find('[data-role="title"]').first().val();
        }
        if(curr_iframe.contents().find('#ig-widget-form').length){
            title_prepend = curr_iframe.contents().find('#ig-widget-form').find("input:text[name$='[title]']");
            item_title = title_prepend.val();
        }
        if(!top.addInClassic)
            $.HandleElement.updateActiveElement(update_iframe, shortcode_content, item_title, action_data);
        else{
            // update to textarea of Classdic Editor

            // inserts the shortcode into the active editor
            tinymce.activeEditor.execCommand('mceInsertContent', 0, shortcode_content);
            // closes Thickbox
            tb_remove();
        }

        if($.options.if_childmodal){
            // Update Tags of sub-element in Accordion
            if($("#modalOptions #shortcode_name").val() == "ig_accordion"){
                $.HandleElement.extractParam("ig_accordion", "tag", "#ig_share_data");
            }
            // Rescan sub-element shortcode of Parent element (Accordion, Tab...)
            $.HandleElement.rescanShortcode();
        }
        $.HandleElement.finalize($.options.if_childmodal);
    },

    /**
	 * Update to active element
	 */
    $.HandleElement.updateActiveElement = function(update_iframe, shortcode_content, item_title, action_data) {
        var active_shortcode = $.HandleElement.selector(update_iframe,"#form-container .active-shortcode");
        if(active_shortcode.hasClass('jsn-row-container'))
            shortcode_content = shortcode_content.replace('[/ig_row]','');
        active_shortcode.find("[data-sc-info^='shortcode_content']").first().text(shortcode_content);

        // update content to current active sub-element in group elements (Accordions, Tabs...)
        var item_class = ($.options.if_childmodal) ? ".jsn-item-content" : ".ig-pb-element";
        // if sub modal, use item_title as title. If in pagebuilder, show like this (Element Type : item_title)
        if(!$.options.if_childmodal && active_shortcode.find(item_class).first().length){
            if(item_title != '')
                item_title = active_shortcode.find(item_class).first().html().split(':')[0] + ": " + '<span>'+item_title+'</span>';
            else
                item_title = active_shortcode.find(item_class).first().html().split(':')[0];
        }

        if ( ! item_title || item_title == "<i class=''></i>" )
            item_title = Ig_Translate.no_title;
        active_shortcode.find(item_class).first().html(item_title);
        // update content to current active Cell in Table
        if(window.parent.jQuery.noConflict()( '#jsn_view_modal_sub').contents().find('#shortcode_name').val() == "ig_item_table"){
            var table = new $.IGTable();
            table.init(active_shortcode);
        }

        var element_html = active_shortcode.html();
        var action_;
        if(action_data != null){
            $.each(action_data, function(action, data){
                action_ = action;
                if(action == "convert")
                {
                    var arr = data.split('_');
                    if(arr.length == 3)
                    {
                        var regexp = new RegExp("ig_"+arr[0], "g");
                        element_html = element_html.replace(regexp, "ig_"+arr[2]);

                        regexp = new RegExp("ig_item_"+arr[0], "g");
                        element_html = element_html.replace(regexp, "ig_item_"+arr[2]);
                        //Shortcode name in PageBuilder
                        regexp = new RegExp($.HandleElement.capitalize(arr[0]), "g");
                        element_html = element_html.replace(regexp, $.HandleElement.capitalize(arr[2]));
                        //"Convert to" button
                        regexp = new RegExp(Ig_Translate.convertText + arr[2], "g");
                        element_html = element_html.replace(regexp, Ig_Translate.convertText + arr[0]);
                    }

                }
            })

        }
        active_shortcode.html(element_html);
        // reopen Modal with Converted Shortcode
        if(action_ == "convert")
            active_shortcode.find(".element-edit").trigger('click', [true]);
        else
            active_shortcode.removeClass('active-shortcode');

        // Hide Loading in Group elements
        if ( $(active_shortcode).parents('#group_elements').length ) {
            $(active_shortcode).parents('#group_elements').find('.jsn-item').last().find('.jsn-icon-loading').remove();
        }

        // Check if in Fullmode, then turn live preview on

        if ($(active_shortcode).parents('#form-container.fullmode').length > 0) {
            $.HandleElement.turnOnShortcodePreview(active_shortcode);
        }


        /* Update package attribute label common json */
        $('body').trigger('on_update_attr_label_common');
        $('body').trigger('on_update_attr_label_setting');

    }

    // finalize when click Save/Cancel modal
    $.HandleElement.finalize = function(is_submodal, remove_modal){
        // remove modal
        if(remove_modal || remove_modal == null)
            window.parent.jQuery.noConflict()('.jsn-modal').last().remove();

        $("#form-container").find('.jsn-icon-loading').remove();

        // reactive TinyMCE tab
        if(top.addInClassic){
            top.addInClassic = 0;
            switchEditors.switchto(document.getElementById('content-tmce'));
        }
        // reset/update status
        $.options.if_childmodal = 0;
        $.PbDoing.addElement = 0;
        $.PbDoing.editElement = 0;
        // remove overlay & loading
        $.HandleElement.hideLoading();
        $.HandleElement.removeModal();

        $('#form-container').trigger('ig-pagebuilder-layout-changed');
    }


    $.HandleElement.checkSelectMedia = function() {
        $('body').delegate('#ig-select-media', 'change', function () {
            var currentValue = $(this).val();
            if ( currentValue ) {
                var jsonObject = JSON.parse( currentValue );
                $('#ig-select-media').val('');
                var send_attachment_bkp = wp.media.editor.send.attachment;
                var button 				= $(this);

                if (typeof(jsonObject.type) != undefined) {
                    var _custom_media = true;
                    wp.media.editor.send.attachment = function(props, attachment){
                        if ( _custom_media ) {
                            var select_url 	= attachment.url;

                            if ( props.size && attachment.type == jsonObject.type) {
                                var select_prop 	= props.size;
                                var object 			= {};
                                object.type			= 'media_selected';
                                object.select_prop	= select_prop;
                                object.select_url	= select_url;
                                $('#ig-select-media').val(JSON.stringify(object));
                            }
                        } else {
                            return _orig_send_attachment.apply( this, [props, attachment] );
                        };

                    }
                    wp.media.editor.open(button);
                }else{
                    wp.media.editor.open(button);
                }
            }
        });
    }

    /**
     * Init events for Mode Switcher to turn view to full or compact
     */
    $.HandleElement.initModeSwitcher	= function (){
        var switcher_group	= $('#mode-switcher');
        var container		= $('#form-container');
        var cur_url			= window.location.search.substring(1);
        
        $('.switchmode-button', switcher_group).on('click', function (){
            if($(this).hasClass('disabled')) return false;
            if($(this).attr('id')	== 'switchmode-full'){
                container.addClass('fullmode');
                $.HandleElement.switchToFull(container);
                container.on('ig-pagebuilder-layout-changed', function (event, ctn){
                    $.HandleElement.switchToFull(ctn);
                });

                container.on('ig-pagebuilder-column-size-changed', function (event, ctn_row){
                    $(ctn_row).find('.shortcode-preview-iframe').each(function (){
                        var _iframe			= $(this);
                        var _iframe_width	= _iframe.width();
                        if (_iframe.contents().find('#shortcode_inner_wrapper').length > 0){
                            _iframe.contents().find('#shortcode_inner_wrapper').width(_iframe_width - 25);
                            var _contentHeight	= _iframe.contents().find('#shortcode_inner_wrapper')[0].scrollHeight;
                            _iframe.height(_contentHeight);
                        }

                    });
                });
                $.HandleElement.setCookie('ig-pb-mode-' + cur_url, 2);

            }else if ($(this).attr('id') == 'switchmode-compact'){
                container.removeClass('fullmode');
                $.HandleElement.switchToCompact(container);
                container.unbind('ig-pagebuilder-layout-changed');
                $.HandleElement.setCookie('ig-pb-mode-' + cur_url, 1)
            }
        });
        // Auto switch to full mode if it was
        if ($.HandleElement.getCookie('ig-pb-mode-' + cur_url) == 2) {
        	$('#switchmode-full', switcher_group).click();
        }
        
    }

    /**
     * Turn view to Full mode
     */
    $.HandleElement.switchToFull = function (container){
        // Load preview frames for each shortcode item
        if ($(container).hasClass('jsn-item') || $(container).parents('jsn-item').length > 0) {
            $.HandleElement.turnOnShortcodePreview(container);
        }else{
            $('.jsn-item', container).each(function (){
                var _shortcode_title	= $('.ig-pb-element', $(this)).text();
                $(this).find('.ig-pb-fullmode-shortcode-title').remove();
                $(this).append(
                    $("<div/>", {
                        "class":"jsn-percent-column ig-pb-fullmode-shortcode-title"
                    }).append(
                        $("<div/>", {
                            "class":"jsn-percent-arrow"
                        })
                        ).append(
                        $("<div/>", {
                            "class":"jsn-percent-inner"
                        }).append(_shortcode_title)
                        )
                    );
                $(this).find(".jsn-percent-column .jsn-percent-arrow").css({
                    "left": "10px"
                });
                $.HandleElement.turnOnShortcodePreview(this);
            });
        }


    }

    /**
     * Turn live preview of a shortcode on
     */
    $.HandleElement.turnOnShortcodePreview	= function (shortcode_wrapper){
        // Create form and iframe used for submitting data
        // to preview.
        var _rnd_id				= randomString(5);
        var _shortcode_params	= $(shortcode_wrapper).find('textarea.shortcode-content').clone();
        _shortcode_params.attr('name', 'params').removeAttr('data-sc-info').removeClass('shortcode-content');

        var _shorcode_name		= $(shortcode_wrapper).find('textarea.shortcode-content').attr('shortcode-name');
        if ( typeof(_shorcode_name) == 'undefined' || _shorcode_name == null ) {
            return;
        }
        $(shortcode_wrapper).find('.jsn-overlay').show();

        if ($(shortcode_wrapper).find('form.shortcode-preview-form').length == 0){
            var _form				= $('<form/>', {
                'class': 'shortcode-preview-form',
                'method': 'post',
                'target': 'iframe-' + _rnd_id,
                'action': Ig_Ajax.adminroot + 'index.php?page=ig_modal_page&ig_nonce_check=' + Ig_Ajax._nonce + '&ig_shortcode_preview=1&ig_shortcode_name=' + _shorcode_name
            });
            var _iframe				= $('<iframe/>', {
                'scrolling': 'no',
                'id': 'iframe-' + _rnd_id,
                'name': 'iframe-' + _rnd_id,
                'width': '100%',
                'height': '50',
                'class': 'shortcode-preview-iframe'
            });
            var _preview_container	= $(shortcode_wrapper).find('.shortcode-preview-container');

            // Append cloned shortcode content to temporary form

            _shortcode_params.appendTo(_form);

            // Append form and iframe to shorcode preview div
            _form.appendTo(_preview_container);
            _iframe.appendTo(_preview_container);
            _form.submit();
        }else{
            var _form	= $(shortcode_wrapper).find('form.shortcode-preview-form').first();
            _form.find('textarea').remove();
            _shortcode_params.appendTo(_form);
            _form.submit();
            _iframe	= $('#' + _form.attr('target'));
        //_iframe.css('height', '50');
        }

        $('.shortcode-preview-container', shortcode_wrapper).show();
        // Show preview content after preview iframe loaded successfully
        _iframe.on('load', function (){
            var self	= this;
            var	_frame_id	= $(this).attr('id');
            setTimeout(function (){
                $(self).contents().find('#shortcode_inner_wrapper').css({
                    'height': 'auto',
                    'width': $(self).width()
                });
                if (document.getElementById(_frame_id).contentWindow.document.getElementById('shortcode_inner_wrapper')){
                    var _contentHeight	= document.getElementById(_frame_id).contentWindow.document.getElementById('shortcode_inner_wrapper').scrollHeight - 10;
                    $(self).height(_contentHeight) ;
                    $(self).contents().find('#shortcode_inner_wrapper').height(_contentHeight);
                }

            }, 100);
            $(this).parents('.jsn-item').find('.jsn-overlay').hide('slow');
            // Hide shorcode title when iframe loaded
            $(this).parents('.jsn-item').find('.ig-pb-element').hide('slow');
            // update content for Classic editor - to make php "Save post hook" works well
            var tab_content = '';
            $("#form-container textarea[name^='shortcode_content']").each(function(){
                tab_content += $(this).val();
            });
            $.HandleElement.updateClassicEditor(tab_content);
        });
    }

    /**
     * Turn view to Compact mode
     */
    $.HandleElement.switchToCompact	= function (container){
        $('.shortcode-preview-container', container).hide();
        $('.jsn-overlay', container).show();
        $('.ig-pb-element', container).show();
        $('.ig-pb-fullmode-shortcode-title', container).remove();
    }

    /**
     * Init events for Status Switcher to turn on/off pagebuilder
     */
    $.HandleElement.initStatusSwitcher	= function (){
        var switcher_group	= $('#status-switcher');
        var container		= $('#form-container');
        var class_btn = new Array();
        class_btn['status-on'] = 'btn-success';
        class_btn['status-off'] = 'btn-danger';
        $('.switchmode-button', switcher_group).on('click', function (e, doit){
            if($(this).attr('id')	== 'status-off'){
                // UPDATE CLASSIC EDIGOR
                var cf;
                if(doit != null)
                    cf = doit;
                else
                    cf = confirm(Ig_Translate.deactivatePb);
                if(cf){
                    // disable Mode switcher buttons
                    $('#mode-switcher button').addClass('disabled');
                    // Hide Pagebuilder UI
                    container.addClass('hidden');
                    // Show message
                    $('#deactivate-msg').removeClass('hidden');

                    if(doit == null){
                        // Update tracking field
                        $('#ig_deactivate_pb').val("1");

                        // Set the HTML alternative content to default editor and clear pagebuilder content
                        var html_active = $("#wp-content-wrap").hasClass('html-active');
                        var tab_content = '';
                        $("#form-container textarea[name^='shortcode_content']").each(function(){
                            tab_content += $(this).val();
                        });
                        // disable WP Update button
                        $('#publishing-action #publish').attr('disabled', true);
                        // remove placeholder text which was inserted to &lt; and &gt;
                        tab_content = ig_pb_remove_placeholder(tab_content, 'wrapper_append', '');

                        $.post(
                            Ig_Ajax.ajaxurl,
                            {
                                action : 'get_html_content',
                                content : tab_content,
                                ig_nonce_check : Ig_Ajax._nonce
                            },
                            function( tab_content ) {
                                $.HandleElement.updateClassicEditor(tab_content, html_active, function(){
                                    $('#status-on').removeClass(class_btn['status-on']);
                                    $('#status-off').addClass(class_btn['status-off']);
                                });
                            });

                    }
                    else{
                        $('#status-on').removeClass('btn-success');
                        $(this).addClass('btn-danger');
                    }

                    return true;
                }
                return false;
            }else if ($(this).attr('id') == 'status-on'){
                // UPDATE PAGE BUILDER
                // enable Mode switcher buttons
                $('#mode-switcher button').removeClass('disabled');
                // Show Pagebuilder UI
                container.removeClass('hidden');
                // Hide message
                $('#deactivate-msg').addClass('hidden');
                // Update tracking field
                $('#ig_deactivate_pb').val("0");

                // Get content of default editor, parse to a Text shortcode and add to Pagebuilder
                var classic_content = $('#ig_editor_tab1 #content').val();
                classic_content = classic_content.replace(/^content=/, '');
                $.HandleElement.updatePageBuilder(classic_content, function(){
                    $('#status-off').removeClass(class_btn['status-off']);
                    $('#status-on').addClass(class_btn['status-on']);
                });
            }
        });
    }

    /**
     * Update UI of Pagebuilder
     */
    $.HandleElement.updatePageBuilder = function (tab_content, callback){
        // disable WP Update button
        $('#publishing-action #publish').attr('disabled', true);
        // show loading indicator
        $("#form-container").css('opacity',0);
        $("#ig-pbd-loading").css('display','block');
        if($.trim(tab_content) != ''){
            $.post(
                Ig_Ajax.ajaxurl,
                {
                    action 		: 'text_to_pagebuilder',
                    content   : tab_content,
                    ig_nonce_check : Ig_Ajax._nonce
                },
                function( data ) {
                    self_(data);
                });
        }
        else
            self_('');

        function self_(data){
            // remove current content of Pagebuilder
            $("#jsn-add-container").prevAll().remove();

            // insert placeholder text to &lt; and &gt; before prepend, then replace it
            data = ig_pb_add_placeholder( data, '&lt;', 'wrapper_append', '&{0}lt;');
            data = ig_pb_add_placeholder( data, '&gt;', 'wrapper_append', '&{0}gt;');
            $("#form-container").prepend(data);
            $("#form-container").html(ig_pb_remove_placeholder($("#form-container").html(), 'wrapper_append', ''));

            if(callback != null)
                callback();

            // show Pagebuilder
            $("#ig-pbd-loading").hide();
            $("#form-container").animate({
                'opacity':1
            },200,'easeOutCubic');

            // active WP Update button
            $('#publishing-action #publish').removeAttr('disabled');
        }
    }
    /**
     * Update Content of Classic Editor
     */
    $.HandleElement.updateClassicEditor	= function (tab_content, html_active, callback){
        if(tinymce.get('content'))
            tinymce.get('content').setContent(tab_content);
        $("#ig_editor_tab1 #content").val(tab_content);

        if(callback != null)
                callback();
        // active WP Update button
        $('#publishing-action #publish').removeAttr('disabled');
    }

    // Disable click on a tag inside preview iframe
    $.HandleElement.disableHref = function() {
        $('#modalOptions a, #shortcode_inner_wrapper a').click(function(e){
            e.preventDefault();
        });
        // disable form submit
        $('#shortcode_inner_wrapper form').submit(function(e){
            e.preventDefault();
            return false;
        });
    }

    // Disable click on a tag inside preview iframe
    $.HandleElement.disableHref = function() {
        $('#modalOptions a, #shortcode_inner_wrapper a').click(function(e){
            e.preventDefault();
        });
        // disable form submit
        $('#shortcode_inner_wrapper form').submit(function(e){
            e.preventDefault();
            return false;
        });
    }

    // Disable click on a tag inside preview iframe
    $.HandleElement.disableHref = function() {
        $('#modalOptions a, #shortcode_inner_wrapper a').click(function(e){
            e.preventDefault();
        });
        // disable form submit
        $('#shortcode_inner_wrapper form').submit(function(e){
            e.preventDefault();
            return false;
        });
    }

    $.HandleElement.deactivateShow = function() {
        // Disable element
        $('.shortcode-content').each(function(){
            var content = $(this).val();
            var shortcode = $(this).attr('shortcode-name');
            var regex = new RegExp("\\[" + shortcode + '\\s' + '([^\\]])*' + 'disabled="yes"' + '([^\\]])*' + '\\]', "g");
            var val = regex.test(content);
            if (val) {
                $(this).parent().addClass('disabled');
                var deactivate_btn = $(this).parent().find('.element-deactivate');
                deactivate_btn.attr('title', Ig_Translate.reactivate_el);
                deactivate_btn.find('i').attr('class', 'icon-checkmark');
            }

        });
    }

    $(document).ready(function() {
        $.HandleElement.addItem();
        $.HandleElement.addElement();
        $.HandleElement.deleteElement();
        $.HandleElement.editElement();
        $.HandleElement.cloneElement();
        $.HandleElement.deactivateElement();
        $.HandleElement.deactivateShow();
        $.HandleElement.addLayout();
        $.HandleElement.saveLayout();
        $.HandleElement.checkSelectMedia();
        $.HandleElement.initModeSwitcher();
        $.HandleElement.initStatusSwitcher();
        $.HandleElement.disableHref();
    });

    /**
     * Renerate a random string
     */
    function randomString(length) {
        var result 	= '';
        var chars	= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
        return result;
    }
})(jQuery);