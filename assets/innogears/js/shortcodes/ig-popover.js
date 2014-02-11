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
( function ($)
{
    $.IGPopoverOptions	= $.IGPopoverOptions || {};

    $.IGPopoverOptions = function () {};

    $.IGPopoverOptions.prototype = {
        init:function(){
            this.container = $(".jsn-items-list");
            this.addIconbar();
            this.actionIconbar(this);
            if(this.container.parents('.unsortable').length == 0){
                this.container.sortable({
                    placeholder: "ui-state-highlight",
                    stop: function( event, ui ) {
                        $.HandleSetting.shortcodePreview();
                    }
                });
                this.container.disableSelection();
            }
        },
        addIconbar:function(){
            this.container.find(".jsn-item").find(":input[data-popover-item='yes']").each(function(){
                $(this).after('<div class="jsn-iconbar"><a class="element-action-edit" href="javascript:void(0)"><i class="icon-cog"></i></a></div>');
            })
        },
        actionIconbar:function(this_){
            this_.container.find(".element-action-edit").click(function (e) {
                this_.openActionSettings(this_, $(this));
                $.HandleSetting.select2_color();
                // fix Font selector error
                new $.IGSelectFonts();
                new $.IGColorPicker();
                setTimeout(function(){
                    $('#modalAction .combo-item').each(function(){
                        if($(this).find('.select2-container').length == 2){
                            $(this).find('.select2-container').first().remove();
                        }
                    });
                }, 200);

                e.stopPropagation();
            });
        },
        openActionSettings:function(this_, btnInput, specific, callback){
            this_.container.find(".jsn-item.ui-state-edit").removeClass("ui-state-edit");
            $(btnInput).parents(".jsn-item").addClass("ui-state-edit");
            $(".control-list-action").hide();
            var dialog, value, el_title;
            if(specific == null){
                value = $(btnInput).parents(".jsn-item").find(":input").val();
            }
            else{
                value = $(btnInput).parents(".jsn-item").find(":input#param-elements").val();
            }
            el_title = $(btnInput).parents(".jsn-item").find("label").text();

            if($("#control-action-"+value).length == 0){
                var dialog_html = '';
                $('body').find('[data-related-to="'+value+'"]').each(function(){
                    dialog_html += $("<div />").append($(this).clone()).html();
                    $(this).remove();
                })
                dialog = $("<div/>", {
                    'class':'control-list-action jsn-bootstrap',
                    'id':"control-action-"+value,
                    'style' : 'position: absolute;width:300px;'
                }).append(
                    $("<div/>", {
                        "class":"popover left"
                    }).css("display", "block").append($("<div/>", {
                        "class":"arrow"
                    })).append(
                        $("<h3/>", {
                            "class":"popover-title",
                            text:el_title + ' ' + Ig_Translate.settings
                        })
                    ).append(
                        $("<div/>", {
                            "class":"popover-content"
                        }).append(
                            dialog_html
                            )
                    )
                )
                $(dialog).find('.hidden').removeClass('hidden');
                $(dialog).hide();
                $(dialog).appendTo('#modalAction');
            }
            else{
                dialog = $("#control-action-"+value);
            }
            dialog.fadeIn(500);
            // update HTML DOM
            $( '.control-list-action' ).delegate( '[id^="param"]', 'change', function () {
                $(this).attr('value',$(this).val());
                if($(this).is('select')){
                    var html = $(this).html();
                    html = html.replace('selected=""','').replace('value="'+$(this).val()+'"', 'value="'+$(this).val()+'" selected=""');
                    $(this).html(html);
                }
            });

            if(callback)
                callback(dialog);

            var elmStyle = this_.getBoxStyle($(dialog).find(".popover")),
            parentStyle = this_.getBoxStyle($(btnInput)),
            position = {};
            position.left = parentStyle.offset.left - elmStyle.outerWidth - 11; // 11 is width of arrow of popover left
            position.top = parentStyle.offset.top - (elmStyle.outerHeight / 2) + (parentStyle.outerHeight / 2) - 12;

            dialog.css(position).click(function (e) {
                e.stopPropagation();
            });
            $(document).click(function () {
                dialog.hide();
                this_.container.find(".jsn-item.ui-state-edit").removeClass("ui-state-edit");
            });

            // fire hook event after insert popover html
            $('body').trigger('ig_after_popover');
        },
        // Get element's dimension
        getBoxStyle:function(element){
            var style = {
                width:element.width(),
                height:element.height(),
                outerHeight:element.outerHeight(),
                outerWidth:element.outerWidth(),
                offset:element.offset(),
                margin:{
                    left:parseInt(element.css('margin-left')),
                    right:parseInt(element.css('margin-right')),
                    top:parseInt(element.css('margin-top')),
                    bottom:parseInt(element.css('margin-bottom'))
                },
                padding:{
                    left:parseInt(element.css('padding-left')),
                    right:parseInt(element.css('padding-right')),
                    top:parseInt(element.css('padding-top')),
                    bottom:parseInt(element.css('padding-bottom'))
                }
            };

            return style;
        }
    }

    $(document).ready(function(){
        var Ig_Content = new $.IGPopoverOptions();
        Ig_Content.init();
    })

})(jQuery);