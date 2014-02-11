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
 * Root wrapper div #form-container, contains all content
 * Columns are seperated by a 12px seperators
 * HTML structure: #form-container [.jsn-row-container [.jsn-column-container]+]+
 *
 */

(function ($) {
    function JSNLayoutCustomizer() {

    }

    JSNLayoutCustomizer.prototype = {
        init:function (_this) {
            // Get necessary elements
            this.wrapper = $("#form-container");
            this.wrapper_width = 0;
            this.columns = $(_this).find('.jsn-column-container');
            this.addcolumns = '.add-container';
            this.addelements = '.ig-more-element';
            this.resizecolumns = '.ui-resizable-e';
            this.deletebtn = '.item-delete';
            this.moveItemEl = "[class^='jsn-move-']";
            this.resize = 1;
            this.effect = 'easeOutCubic';

            // Initialize variables
            this.maxWidth = $('#form-container').width();
            // do this to prevent columns drop
            $('#form-container').css('width', this.maxWidth + 'px');
            this.spacing = 12;
            var self	= this;

            // Sortable for columns in row
            $(_this).find(".ig-row-content").livequery(function(){
                $(this).sortable({
                    axis:'x',
                    //   placeholder:'ui-state-highlight',
                    start:$.proxy(function (event, ui) {
                        ui.placeholder.append(ui.item.children().clone());
                        $(ui.item).parents(".ig-row-content").find(".ui-resizable-handle").hide();
                    }, this),
                    handle:".jsn-handle-drag",
                    stop:$.proxy(function (event, ui) {
                        $(ui.item).parents(".ig-row-content").find(".ui-resizable-handle").show();
                        self.wrapper.trigger('ig-pagebuilder-layout-changed', [ui.item]);
                    }, this)
                });
            });
            $(_this).find(".ig-row-content").disableSelection();

            // has not inited before, so call all functions
            this.addRow(this, this.wrapper);
            this.updateSpanWidthPBDL(this, this.wrapper, this.maxWidth);
            this.initResizable(-1);
            this.addColumn($("#tmpl-ig_column").html());
            this.removeItem();
            this.moveItem();
            this.moveItemDisable(this.wrapper);
            this.resizeHandle(this);
            this.sortableElement();
            this.addElement();
            this.showAddLayoutBox();
            this.showSaveLayoutBox();
            this.searchElement();

        },

        // Update column width when window resize
        resizeHandle:function (self) {
            $(window).resize(function() {
                if($('body').children('.ui-dialog').length)
                    $('html, body').animate({scrollTop: $('body').children('.ui-dialog').first().offset().top - 60}, 'fast');
                self.fnReset(self);
                var _rows	= $('.jsn-row-container', self.wrapper);
                self.wrapper.trigger('ig-pagebuilder-column-size-changed', [_rows]);
            });
            $("#ig_page_builder").resize(function() {
                self.fnReset(self);
            });
        },

        // reset when resize window/pagebuilder
        fnReset:function(self, trigger){
            if((self.resize || trigger) && $("#form-design-content").width()){
                // do this to prevent columns drop
                $("#form-container").width($("#form-design-content").width() + 'px');
                self.maxWidth = $("#form-container").width();
                // re-calculate step width
                self.calStepWidth(0, 'reset');
                self.initResizable(-1, false);
                self.updateSpanWidthPBDL(self, self.wrapper, self.maxWidth);
            }
        },

        // Calculate step width when resize column
        calStepWidth:function(countColumn, reset){
            var this_column = this.columns;
            if(reset != null){
                this_column = $("#form-container").find(".jsn-row-container").first().find('.jsn-column-container');
            }

            var formRowLength = (countColumn > 0) ? countColumn : this_column.length;
            this.step = parseInt((this.maxWidth - (this.spacing * (formRowLength -1))) / 12);

        },

        // Resize columns
        initResizable:function (countColumn, getStep) {
            var self = this;
            if(getStep == null || getStep)
                self.calStepWidth(countColumn);

            var step = self.step;
            var handleResize = $.proxy(function (event, ui) {
                var thisWidth = ui.element.width(),
                bothWidth = ui.element[0].__next[0].originalWidth + ui.originalSize.width,
                nextWidth = bothWidth - thisWidth;

                if (thisWidth < step) {
                    thisWidth = step;
                    nextWidth = bothWidth - thisWidth;

                    // Set min width to prevent column from collapse more
                    ui.element.resizable('option', 'minWidth', step);
                } else if (nextWidth < step) {
                    nextWidth = step;
                    thisWidth = bothWidth - nextWidth;

                    // Set max width to prevent column from expand more
                    ui.element.resizable('option', 'maxWidth', thisWidth);
                }
                var this_span = parseInt(thisWidth / step);
                var next_span = parseInt(nextWidth / step);
                thisWidth = parseInt(parseInt(this_span)*bothWidth/(this_span + next_span));
                nextWidth = parseInt(parseInt(next_span)*bothWidth/(this_span + next_span));
                // Snap column to grid
                ui.element.css('width', thisWidth + 'px');
                // Resize next sibling element as well
                ui.element[0].__next.css('width', nextWidth + 'px');
                // show % width

                self.percentColumn($(ui.element),"add",step);
                var _row	= $(ui.element).parents('.jsn-row-container');
                self.wrapper.trigger('ig-pagebuilder-column-size-changed', [_row]);
            }, this);
            // Reset resizable column

            $(".jsn-column").each($.proxy(function (i, e) {
                $(e).resizable({
                    handles:'e',
                    minWidth:step,
                    grid:[step, 0],
                    start:$.proxy(function (event, ui) {
                        ui.element[0].__next = ui.element[0].__next || ui.element.parent().next().children();
                        ui.element[0].__next[0].originalWidth = ui.element[0].__next.width();
                        ui.element.resizable('option', 'maxWidth', '');
                        // disable resize handle
                        self.resize = 0;
                    }, this),
                    resize:handleResize,
                    stop:$.proxy(function (event, ui) {
                        var oldValue = parseInt(ui.element.find(".jsn-column-content").attr("data-column-class").replace('span', '')),
                        // Round up, not parsetInt
                        newValue = Math.round(ui.element.width() / step),
                        nextOldValue = parseInt(ui.element[0].__next.find(".jsn-column-content").attr("data-column-class").replace('span', ''));
                        // Update field values
                        if (nextOldValue > 0 && newValue > 0) {
                            ui.element.find(".jsn-column-content").attr("data-column-class", 'span' + newValue);
                            ui.element[0].__next.find(".jsn-column-content").attr('data-column-class', 'span' + (nextOldValue - (newValue - oldValue)));
                            // Update visual classes
                            ui.element.attr('class', ui.element.attr('class').replace(/\bspan\d+\b/, 'span' + newValue));
                            ui.element[0].__next.attr('class', ui.element[0].__next.attr('class').replace(/\bspan\d+\b/, 'span' + (nextOldValue - (newValue - oldValue))));
                            ui.element.find("[name^='shortcode_content']").first().text(ui.element.find("[name^='shortcode_content']").first().text().replace(/span\d+/, 'span' + newValue));
                            ui.element[0].__next.find("[name^='shortcode_content']").first().text(ui.element[0].__next.find("[name^='shortcode_content']").first().text().replace(/span\d+/, 'span' + (nextOldValue - (newValue - oldValue))));
                            $(e).css({
                                "height":"auto"
                            });
                        }
                        // enable resize handle
                        self.resize = 1;
                        /// self.updateSpanWidthPBDL(self, self.wrapper, $("#form-container").width());

                        self.percentColumn($(ui.element),"remove",step);
                    }, this)
                });
            }, this));

            // remove duplicated resizable-handle div
            if(countColumn > 0){
                $(".jsn-column").each(function(){
                    if($(this).find('.ui-resizable-handle').length > 1)
                        $(this).find('.ui-resizable-handle').last().remove();
                })
            }
        },
        toFixed:function(value, precision){
            var power = Math.pow(10, precision || 0);
            return String(Math.round(value * power) / power);
        },
        getSpan:function(this_){
            return $(this_).find('.jsn-column-content').first().attr('data-column-class').replace('span', '');
        },
        percentColumn:function (element, action,step) {
            var self = this;
            if (action == "add") {

                var this_parent = $(element).parents(".jsn-column-container");
                // get current columnm & next column
                var cols = [this_parent.find('.jsn-column'), this_parent.next('.jsn-column-container').find('.jsn-column')];
                // count total span of this column & next column
                var spans = 0;
                $.each(cols, function () {
                    spans += parseInt(self.getSpan(this));
                })

                // show percent tooltip of this column & the next column
                var updated_spans = [];
                $.each(cols, function (i) {
                    var thisCol = this;
                    var round = (i == cols.length - 1) ? 1 : 0;
                    var thisSpan = parseInt($(this).width() / step) + round;
                    if(i > 0){
                        thisSpan = ((spans - updated_spans[i - 1]) < thisSpan) ? (spans - updated_spans[i - 1]) : thisSpan;
                    }
                    updated_spans[i] = thisSpan;
                    self.showPercentColumn(thisCol, thisSpan);
                });

                // show percent tooltip of other columns
                $(element).parents(".jsn-row-container").find(".jsn-column").each(function(){
                    if(!$(this).find(".ig-pb-layout-percent-column").length){
                        var thisCol = this;
                        var thisSpan = self.getSpan(this);
                        self.showPercentColumn(thisCol, thisSpan);
                    }
                })

            }
            if (action == "remove") {
                var container = $(element).parents(".jsn-row-container");
                $(container).find(".ig-pb-layout-percent-column").remove();
            }
        },
        // Show percent tooltip when know span of this column
        showPercentColumn:function(thisCol, thisSpan){
            var maxCol = 12;
            var percent = this.toFixed(thisSpan / maxCol * 100, 2).replace(".00", "") + "%";
            var thumbnail = $(thisCol).find(".thumbnail");
            $(thumbnail).css('position', 'relative');
            // $(thumbnail).find("percent-column").remove();
            if ($(thumbnail).find(".ig-pb-layout-percent-column").length) {
                $(thumbnail).find(".ig-pb-layout-percent-column .jsn-percent-inner").html(percent);
            } else {
                $(thumbnail).append(
                    $("<div/>", {"class":"jsn-percent-column ig-pb-layout-percent-column"}).append(
                        $("<div/>", {"class":"jsn-percent-arrow"})
                    ).append(
                        $("<div/>", {"class":"jsn-percent-inner"}).append(percent)
                    )
                )
            }
            var widthThumbnail = $(thumbnail).width();
            var widthPercent = $(thumbnail).find(".ig-pb-layout-percent-column").width();
            $(thumbnail).find(".ig-pb-layout-percent-column").css({"left":parseInt((widthThumbnail + 10) / 2) - parseInt(widthPercent / 2) + "px"});
            $(thumbnail).find(".ig-pb-layout-percent-column .jsn-percent-arrow").css({"left":parseInt(widthPercent / 2) - 4 + "px"});
        },

        // Add Row
        addRow:function(self, this_wrapper){
            this.wrapper.delegate('#jsn-add-container',"click",function(e) {
                    e.preventDefault();
                    if($("#form-container").find('.jsn-row-container').last().is(':animated')) return;
                    // animation
                    $(this).before(ig_pb_remove_placeholder($("#tmpl-ig_row").html(), 'custom_style', 'style="display:none"'));
                    var new_el = $("#form-container").find('.jsn-row-container').last();
                    var height_ = new_el.height();
                    new_el.css({'opacity' : 0, 'height' : 0});
                    new_el.addClass('overflow_hidden');
                    new_el.show();
                    new_el.animate({height: height_},300,self.effect, function(){
                        new_el.removeClass('overflow_hidden');
                        $(this).animate({opacity:1},300,self.effect,function(){
                            new_el.css('height','auto');
                        });
                    });
                    //last_row.fadeIn(1000);

                    // update width for colum of this new row
                    var parentForm = self.wrapper.find(".jsn-row-container").last();
                    self.updateSpanWidth(1, self.maxWidth, parentForm);
                    // enable/disable move icons
                    self.moveItemDisable(this_wrapper);

                    self.updateSpanWidthPBDL(self, self.wrapper, $("#form-container").width());
                });
        },

        // Wrap content of row
        wrapContentRow:function(a,b,direction){
            var self = this;
            if(a.is(':animated') || b.is(':animated')) return;
            var this_wrapper = self.wrapper;
            var stylea = self.getBoxStyle(a);
            var styleb = self.getBoxStyle(b);
            var time = 500, extra1 = 16, extra2 = 16, effect = self.effect;
            if(direction > 0){
                a.animate({top: '-'+(styleb.height + extra1)+'px'}, time, effect, function(){});
                b.animate({top: ''+(stylea.height + extra2)+'px'}, time, effect, function(){
                    a.css('top', '0px');
                    b.css('top', '0px');
                    a.insertBefore(b);
                    self.moveItemDisable(this_wrapper);
                });
            }
            else{
                a.animate({top: ''+(styleb.height + extra2)+'px'}, time, effect, function(){});
                b.animate({top: '-'+(stylea.height + extra1)+'px'}, time, effect, function(){
                    a.css('top', '0px');
                    b.css('top', '0px');
                    a.insertAfter(b);
                    self.moveItemDisable(this_wrapper);
                });
            }
        },

        // Handle when click Up/Down Row Icons
        moveItem:function(){
            var self = this;
            this.wrapper.delegate(this.moveItemEl, "click",function(){
                if(!$(this).hasClass("disabled")){
                    var otherRow, direction;
                    var class_ = $(this).attr("class");
                    var parent = $(this).parents(".jsn-row-container");
                    var parent_idx = parent.index(".jsn-row-container");
                    if(class_.indexOf("jsn-move-up") >= 0){
                        otherRow = self.wrapper.find(".jsn-row-container").eq(parent_idx-1);
                        direction = 1;
                    }else if(class_.indexOf("jsn-move-down") >= 0){
                        otherRow = self.wrapper.find(".jsn-row-container").eq(parent_idx+1);
                        direction = -1;
                    }
                    if(otherRow.length == 0) return;
                    self.wrapContentRow(parent, otherRow, direction);
                    // Set trigger timeout to be sure it happens after animation
                    setTimeout(function (){
                    	self.wrapper.trigger('ig-pagebuilder-layout-changed', [parent]);
                    }, 1001);

                }
            });
        },

        // Disable Move Row Up, Down Icons
        moveItemDisable:function(this_wrapper){
        	var self	= this;
            this_wrapper.find(this.moveItemEl).each(function(){
                var class_ = $(this).attr("class");
                var parent = $(this).parents(".jsn-row-container");
                var parent_idx = parent.index(".jsn-row-container");
                // add "disabled" class
                if(class_.indexOf("jsn-move-up") >= 0){
                    if(parent_idx == 0)
                        $(this).addClass("disabled");
                    else
                        $(this).removeClass("disabled");
                }
                else if(class_.indexOf("jsn-move-down") >= 0){
                    if(parent_idx == this_wrapper.find(".jsn-row-container").length -1)
                        $(this).addClass("disabled");
                    else
                        $(this).removeClass("disabled");
                }
            });
        },

        // Update span width of columns in each row of PageBuilder at Page Load
        updateSpanWidthPBDL:function(self, this_wrapper, totalWidth){
            this_wrapper.find(".jsn-row-container").each(function(){
                var countColumn = $(this).find(".jsn-column-container").length;
                self.updateSpanWidth(countColumn, totalWidth, $(this));
            })
        },

        // Update span width of columns in each row
        updateSpanWidth:function(countColumn, totalWidth, parentForm){
            //12px is width of the resizeable div
            var seperateWidth = (countColumn - 1) * 12;
            var remainWidth = totalWidth - seperateWidth;

            parentForm.find(".jsn-column-container").each(function (i) {
                var selfSpan = $(this).find(".jsn-column-content").attr("data-column-class").replace('span','');
                if(i == parentForm.find(".jsn-column-container").length - 1)
                    $(this).find('.jsn-column').css('width', Math.ceil(parseInt(selfSpan)*remainWidth/12) + 'px');
                else
                    $(this).find('.jsn-column').css('width', Math.floor(parseInt(selfSpan)*remainWidth/12) + 'px');
            })
        },

        // Add Column
        addColumn:function(column_html){
            var self = this;
            this.wrapper.delegate(this.addcolumns,"click",function(){
                var parentForm = $(this).parents(".jsn-row-container");
                var countColumn = parentForm.find(".jsn-column-container").length;
                if (countColumn < 12) {
                    countColumn += 1;
                    var span = parseInt(12 / countColumn);
                    var exclude_span = (12 % countColumn != 0)? span + (12 % countColumn) : span;
                    // update span old columns
                    parentForm.find(".jsn-column-container").each(function () {
                        $(this).attr('class', $(this).attr('class').replace(/span[0-9]{1,2}/g, 'span'+span));
                        $(this).html($(this).html().replace(/span[0-9]{1,2}/g, 'span'+span));
                    });

                    // update span new column
                    column_html = column_html.replace(/span[0-9]{1,2}/g, 'span'+exclude_span);

                    // add new column
                    parentForm.find(".ig-row-content").append(column_html);

                    // update width for all columns
                    self.updateSpanWidth(countColumn, self.maxWidth, parentForm);
                }
                // actiave resizable for columns
                self.initResizable(countColumn);
                self.wrapper.trigger('ig-pagebuilder-layout-changed', [parentForm]);
            });
        },

        // Confirm message when delete item
        removeConfirmMsg:function(item, type, column_to_row, callback){
            var self = this;
            var msg = "";
            var show_confirm = 1;
            switch(type){
                case 'row':
                    if(item.find('.jsn-column-content').find('.shortcode-container').length == 0)
                        show_confirm = 0;
                    msg = Ig_Translate.delete_row;
                    break;
                case 'column':
                    var check_item = (column_to_row != null) ? column_to_row : item;
                    if(check_item.find('.shortcode-container').length == 0)
                        show_confirm = 0;
                    msg = Ig_Translate.delete_column;
                    break;
                default:
                    msg = Ig_Translate.delete_element;
            }

            var confirm_ = show_confirm ? confirm(msg) : true;
            if(confirm_){
                if(type == 'row'){
                    item.animate({opacity:0},300,self.effect,function(){
                        item.animate({height:0},300,self.effect,function(){
                            item.remove();
                            self.moveItemDisable(self.wrapper);
                        });
                    });
                }
                else if(type == 'column'){
                    item.animate({height:0},500,self.effect,function(){
                        item.remove();
                        if(callback != null) callback();
                    });
                }
                else
                    item.remove();
                return true;
            }
            else
                return false;
        },

        // Remove Row/Column/Element Handle
        removeItem:function(){
            var self = this;
            var this_wrapper = this.wrapper;
            this.wrapper.delegate(this.deletebtn,"click",function(){
                if($(this).hasClass('row')){
                    self.removeConfirmMsg($(this).parents(".jsn-row-container"), 'row');
                }
                else if($(this).hasClass('column')){
                    var totalWidth = this_wrapper.width();
                    var parentForm = $(this).parents(".jsn-row-container");
                    var countColumn = parentForm.find(".jsn-column-container").length;
                    countColumn -= 1;
                    if(countColumn == 0){
                        // remove this row
                        self.removeConfirmMsg(parentForm, 'column', $(this).parents(".jsn-column-container"));
                        return true;
                    }
                    var span = parseInt(12 / countColumn);
                    var exclude_span = (12 % countColumn != 0)? span + (12 % countColumn) : span;

                    // remove current column
                    if(!self.removeConfirmMsg($(this).parents(".jsn-column-container"), 'column', null, function(){
                        // update span remain columns
                        parentForm.find(".jsn-column-container").each(function () {
                            $(this).attr('class', $(this).attr('class').replace(/span[0-9]{1,2}/g, 'span'+span));
                            $(this).html($(this).html().replace(/span[0-9]{1,2}/g, 'span'+span));
                        });

                        // update span last column
                        parentForm.find(".jsn-column-container").last().html(parentForm.find(".jsn-column-container").last().html().replace(/span[0-9]{1,2}/g, 'span'+exclude_span));

                        // update width for all columns
                        self.updateSpanWidth(countColumn, totalWidth, parentForm);
                        // actiave resizable for columns
                        self.initResizable(countColumn);
                        self.wrapper.trigger('ig-pagebuilder-layout-changed', [parentForm]);
                    }))
                        return false;
                }

                self.updateSpanWidthPBDL(self, self.wrapper, $("#form-container").width());

            });
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
        },

        // Sortable Element
        sortableElement:function(){
        	var self	= this;
            $(".jsn-element-container").livequery(function(){
                $(this).sortable({
                    connectWith: ".jsn-element-container",
                    placeholder: "ui-state-highlight",
                    stop: function (e, ui){
                    	self.wrapper.trigger('ig-pagebuilder-layout-changed', [ui.item]);
                    }
                })
            })
            $(".jsn-element-container").disableSelection();
        },

        // show popover box
        showPopover:function(box, e, self, this_, callback1, callback2){
            $(document).trigger('click');
            if(box.is(':animated')) return;
            e.stopPropagation();
            box.hide();
            box.fadeIn(500);

            if(callback1)
                callback1();

            // show popover
            var elmStylePopover = self.getBoxStyle(box.find(".popover")),
            parentStyle = self.getBoxStyle(this_),
            offset_ = {};
            offset_.left = parentStyle.offset.left - elmStylePopover.outerWidth / 2 + parentStyle.outerWidth / 2;
            offset_.top = parentStyle.offset.top - elmStylePopover.height;
            // check if is first row or not
            var row_idx= $("#form-container .jsn-row-container").index(this_.parents('.jsn-row-container'));
            var element_in_col= this_.parent('.jsn-column-content').find('.jsn-element').length;
            offset_.top = (row_idx == 0 && element_in_col < 3) ? (offset_.top + 40) : offset_.top;
            box.offset(offset_).click(function (e) {
                e.stopPropagation();
            });
            if($(window).height() > elmStylePopover.height){
                $('html, body').animate({scrollTop: offset_.top - 60}, 'fast');
            }
            $(document).click(function(){
                box.hide();
            });

            if(callback2)
                callback2();
        },

        // Show Add Elements Box
        addElement:function(){
            var self = this;
            var box = $("#ig-add-element");
            this.wrapper.delegate(this.addelements,"click",function(e){
                self.showPopover(box, e, self, $(this), function(){
                    // filter
                    var filter_select = box.find("select.jsn-filter-button");
                    filter_select.select2({
                        minimumResultsForSearch:99
                    });

                    if($("#jsn-quicksearch-field").val() != ''){
                        $("#reset-search-btn").trigger("click");
                    }
                    else
                        self.isotopeFilter(filter_select.val(), 'data-type');

                    $("#jsn-quicksearch-field").focus();

                }, function(){
                    // trigger this (call 2 times)
                    self.updateSpanWidthPBDL(self, self.wrapper, $("#form-container").width());
                    self.updateSpanWidthPBDL(self, self.wrapper, $("#form-container").width());
                });
            })
        },

        // Show Add Layout Box
        showAddLayoutBox:function(){
            var self = this;
            var box = $("#ig-add-layout");
            $('#premade-layout').click(function(e){
                self.showPopover(box, e, self, $(this));
            });
            $('#ig-add-layout #save-layout').click(function(e){
                $(this).toggleClass('hidden');
                $('#ig-add-layout #save-layout-form').toggleClass('hidden');
            });
        },

        // Save layout
        showSaveLayoutBox:function(){
            $('#ig-pb-save-layout').click(function(e){
                $(this).toggle();
                $('#ig-pb-layout-form').toggleClass('hidden');
            });
        },

        // Search elements in "Add Element" Box
        searchElement:function(){
            var self = this;
            $.fn.delayKeyup = function (callback, ms) {
                var timer = 0;
                var el = $(this);
                $(this).keyup(function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        callback(el)
                    }, ms);
                });
                return $(this);
            };
            $('#jsn-quicksearch-field').keydown(function (e) {
                if(e.which == 13)
                    return false;
            });
            $('#jsn-quicksearch-field').delayKeyup(function(el) {
                if($(el).val() != '')
                    $("#reset-search-btn").show();
                else
                    $("#reset-search-btn").hide();

                ///self.filterElement($(el).val(), 'value');
                self.isotopeFilter($(el).val().toLowerCase());
            }, 500);
            $('#ig-add-element .jsn-filter-button').change(function() {
                ///self.filterElement($(this).val(), 'type');
                self.isotopeFilter($(this).val(), 'data-type');
            })
            $("#reset-search-btn").click(function(){
                ///self.filterElement("all");
                self.isotopeFilter('');
                $(this).hide();
                $("#jsn-quicksearch-field").val("");
            })
        },
        // animation filter
        isotopeFilter:function(value, data){
            var $container = $('#ig-add-element .jsn-items-list');
            var selector = '#ig-add-element .jsn-items-list .jsn-item';
            if(data == null) data = 'data-value';
            if(value != '' && value != 'all')
                selector += '['+data+'*="'+value+'"]';
            if(data == 'data-value'){
                selector += '[data-type="'+$('#ig-add-element select.jsn-filter-button').val()+'"]';
            }
            $container.isotope({
                filter: selector
            });
        },

        // Filter elements in "Add Element" Box
        filterElement:function(value, filter_data){
            var resultsFilter = $('#ig-add-element .jsn-items-list');
            if (value != "all") {
                $(resultsFilter).find("li").hide();
                $(resultsFilter).find("li").each(function () {
                    var textField = (filter_data == 'value') ? $(this).attr("data-value").toLowerCase() : $(this).attr("data-type").toLowerCase();
                    if (textField.search(value.toLowerCase()) === -1) {
                        $(this).hide();
                    } else {
                        $(this).fadeIn(500);
                    }
                });
            }
            else $(resultsFilter).find("li").show();
        }
    };


    $(document).ready(function() {
        // Pagebuilder: get textarea content
        var ig_pg_html = '';
        // Pagebuilder: check if content has changed
        var ig_pg_change = 1;
        // Classic Editor: check if content has changed
        var default_text_change = 1;
        // Classic Editor: get content of Text tab
        var text_content = $('#ig_editor_tab1 #content').val();

        var layout;

        // Classic Editor: get content of Text tab
        $('#ig_editor_tab1 #content').bind('change paste', function(){
            default_text_change = 1;
            text_content = $(this).val();
        });

        // switch tab: Classic editor , Pagebuilder
        $('#ig_editor_tabs a').click(function (e, init_layout) {
            init_layout = (init_layout != null) ? init_layout : false;
            e.preventDefault();
            $(this).tab('show');
            var hash = this.hash;

            // init Pagebuilder
            if(this.hash == '#ig_editor_tab2' && layout == null){
                layout = new JSNLayoutCustomizer();
                layout.init($("#form-container .jsn-row-container"));
            }
            if(init_layout)
                return;

            if($('#ig_deactivate_pb').val() == "1")
                return;

            // synchronous content from Classic editor to Pagebuilder
            var tab_content = '';
            var html_active = $("#wp-content-wrap").hasClass('html-active');
            switch (hash) {

                // Switch from Pagebuilder -> Classic Editor
                case '#ig_editor_tab1':
                    // get content of Pagebuilder and check if it is changed
                    var old_ig_pg_html = ig_pg_html;
                    if(ig_pg_html == ''){
                        ig_pg_html = $('#form-container textarea').serialize();
                    }
                    else if(ig_pg_html != $('#form-container textarea').serialize()){
                        ig_pg_html = $('#form-container textarea').serialize();
                    }
                    if(old_ig_pg_html != $('#form-container textarea').serialize()){
                        ig_pg_change = 1;
                    }

                    if(ig_pg_change){
                        $("#form-container textarea[name^='shortcode_content']").each(function(){
                            tab_content += $(this).val();
                        });

                        if($("#ig_editor_tab1 #content").val() != tab_content){
                            // disable WP Update button
                            $('#publishing-action #publish').attr('disabled', true);
                            // remove placeholder text which was inserted to &lt; and &gt;
                            tab_content = ig_pb_remove_placeholder(tab_content, 'wrapper_append', '');

                            $.HandleElement.updateClassicEditor(tab_content, html_active, function(){
                                // reset global variable
                                tab_content = '';
                                ig_pg_change = 0;

                                // update variable for Classic Editor -> Pagebuilder
                                default_text_change = 0;
                                text_content = tab_content;
                                $('#ig-tinymce-change').val('0');
                            });
                        }
                    }
                    break;

                // Switch from Classic Editor -> Pagebuilder
                case '#ig_editor_tab2':
                    // if content is empty, try to get again
                    if ( text_content == '' ) {
                        if(tinymce.get('content'))
                            text_content = tinymce.get('content').setContent(tab_content);
                        else
                            text_content = $('#ig_editor_tab1 #content').val();
                    }

                    // get content of Classic Editor
                    if(html_active && text_content != null){
                        tab_content = text_content;
                        text_content = null;
                    }
                    else{
                        tab_content = $('#ig_editor_tab1 #content').val();
                        tab_content = tab_content.replace(/^content=/, '');
                    }

                    if(default_text_change || $('#ig-tinymce-change').val() == "1"){
                        $.HandleElement.updatePageBuilder(tab_content, function(){
                            // reset Pagebuilder Layout manager
                            layout.fnReset(layout,true);
                            layout.moveItemDisable(layout.wrapper);
                        });
                        // reset global variable
                        tab_content = '';
                        default_text_change = 0;
                        $('#ig-tinymce-change').val('0');
                    }
                    break;
            }
        })
    })


})(jQuery);