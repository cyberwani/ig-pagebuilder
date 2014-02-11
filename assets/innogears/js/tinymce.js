// Add Inno button to TinyMCE
(function($){
	// creates the plugin
	tinymce.create('tinymce.plugins.ig_pb', {
		// creates control instances based on the control's id.
		// our button's id is "ig_pb_button"
		createControl : function(id, controlManager) {
			if (id == 'ig_pb_button') {
				// creates the button
				var button = controlManager.createButton('ig_pb_button', {
					title : Ig_Translate.inno_shortcode, // title of the button
					image : Ig_Translate.inno_icon,  // path to the button's image
					onclick : function() {
                        // triggers the thickbox
						tb_show( Ig_Translate.inno_shortcode, '#TB_inline?width=' + 100 + '&height=' + 100 + '&inlineId=ig_pb-form' );
                        // custom style
                        $('#TB_window').css({'overflow-y' : 'auto', 'overflow-x' : 'hidden', 'height' : parseInt(jQuery(window).height()*0.9 - 3) + 'px'});
                        $('#TB_ajaxContent').css({'width' : '95%', 'height' : '90%'});
					}
				});
				return button;
			}
			return null;
		}
	});

	// registers the plugin. DON'T MISS THIS STEP!!!
	tinymce.PluginManager.add('ig_pb', tinymce.plugins.ig_pb);

	// executes this when the DOM is ready
	jQuery(function(){
		// creates a form to be displayed everytime the button is clicked
		// you should achieve this using AJAX instead of direct html code like this
		var form = $("<div/>", {
                        "id":"ig_pb-form"
                    }).append(
                        $("<div />").append(window.parent.jQuery.noConflict()('#ig-shortcodes').clone()).html()
                    );
		form.appendTo('body').hide();
        form.find('#ig-shortcodes').fadeIn(500);
	});
})(jQuery)