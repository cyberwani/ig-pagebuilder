/**
 * Add a new tab besides Visual, Text tab of content editor
 */
jQuery( function ( $ ) {

	// move Pagebuilder box to content of "Page Builder" tab
	$( '#ig_page_builder' )
	.insertAfter( '#ig_before_pagebuilder' )
	.addClass( 'jsn-bootstrap' )
	.removeClass('postbox')
	.find( '.handlediv' ).remove()
	.end()
	.find( '.hndle' ).remove();


	$("#ig_editor_tab2").append($("<div />").append($('#ig_page_builder').clone()).html());
	// remove pagebuilder metabox
	$('#ig_page_builder').remove();

    // Show Pagebuilder only when Click "Page Builder" tab
    $(document).ready(function() {
        $('#ig_page_builder').show();

        // switch between "Classic Editor" & "Page Builder"
        $('#ig_editor_tabs a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
            $("#ig_active_tab").val($('#ig_editor_tabs a').index($(this)));
        })

        $(".ig-editor-wrapper").show();
        // Show pagebuilder if tab "Page Builder" is active
        if($("#ig_active_tab").val() == "1"){
            $('#ig_editor_tabs a').eq('1').trigger('click',[true]);
        }

        // hide Pagebuilder UI if pagebuilder is deactivate on this page
        if($("#ig_deactivate_pb").val() == "1"){
            $(".switchmode-button[id='status-off']").trigger('click', [true]);
        }
        else{
            $(".switchmode-button[id='status-on']").addClass('btn-success');
        }

        // Preview Changes fix
        $('#preview-action').css('position', 'relative');
        // Add a overlay div of "Preview Changes" button
        $('<div />', {'id' : 'ig-preview-overlay'}).css({'position':'absolute', 'width' : '100%', 'height' : '24px'}).hide().appendTo($('#preview-action'));
        $('#form-container').bind('ig-pagebuilder-layout-changed', function() {
            // Prevent click "Preview Changes" button
            $('#ig-preview-overlay').show();
            $('#post-preview').attr('disabled', true);

            _update_content(function(){
                // Active "Preview Changes" button
                $('#ig-preview-overlay').hide();
                $('#post-preview').removeAttr('disabled');
            });

            function _update_content(callback) {
                var tab_content = '';
                $("#form-container textarea[name^='shortcode_content']").each(function(){
                    tab_content += $(this).val();
                });

                if(tinymce.activeEditor)
                    tinymce.activeEditor.setContent(tab_content);
                $("#ig_editor_tab1 #content").val(tab_content);

                if(callback)
                    callback();
            }
        });


    });
} );

// add shortcode from Classic Editor
top.addInClassic = 0;