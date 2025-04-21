(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(".fs-modal").on('click', '#saveWorkflowActionBtn', function ()
		{
			saveEmail();
		}).on('click', '#saveAndTestWorkflowActionBtn', function ()
		{
			saveEmail(function ()
			{
				booknetic.modal('<div class="p-3 pt-5 pb-5">' +
					'<div class="mb-2">' +
					'<input class="form-control" id="send_test_email_to" placeholder="'+booknetic.__('To')+'">' +
					'</div>' +
					'<div class="d-flex justify-content-center">' +
					'<button type="button" class="btn btn-lg btn-default mr-1" data-dismiss="modal">'+booknetic.__('CLOSE')+'</button>' +
					'<button type="button" class="btn btn-lg btn-success" id="send_test_btn">'+booknetic.__('SEND')+'</button>' +
					'</div>' +
					'</div>', {type: 'center'});

				$('#send_test_btn').click(function ()
				{
					let modal = $(this).closest( '.modal' );

					booknetic.ajax( 'email_workflow.workflow_action_send_test_data', { id: workflow_action_id, to: $('#send_test_email_to').val()}, function ()
					{
						booknetic.modalHide( modal );
					} );
				});
			});
		});

		function saveEmail( callback )
		{
			var to	            = $("#input_to").val(),
				attachments	    = $("#input_attachments").val(),
				subject	        = $("#input_subject").val(),
				body            = booknetic.summernoteReplace($("#input_body"),true),
				is_active = $("#input_is_active").is(':checked') ? 1 : 0;

			var data = new FormData();
			data.append('id', workflow_action_id);
			data.append('attachments', attachments);
			data.append('to', to);
			data.append('subject', subject);
			data.append('body', body);
			data.append('is_active', is_active);

			booknetic.ajax('email_workflow.workflow_action_save_data', data, function()
			{
				if( typeof callback !== 'undefined' )
				{
					callback();
				}
				else
				{
					booknetic.modalHide($(".fs-modal"));
					booknetic.reloadActionList();
				}
			});
		}

		$( '#input_attachments' ).select2( {
			tokenSeparators: [ ',' ],
			theme: 'bootstrap',
			tags: true
		} );

		$( '#input_attachments' ).on( 'select2:select', function ()
		{
			$( this ).trigger( 'change' );
		} );

		$( '#input_to' ).select2( {
			tokenSeparators: [ ',' ],
			theme: 'bootstrap',
			tags: true,
		} );

		booknetic.initKeywordsInput( $( '#input_subject' ), workflow_email_action_all_shortcodes_obj );

		booknetic.summernote(
			$('#input_body'),
			[
				['style', ['style']],
				['style', ['bold', 'italic', 'underline', 'clear']],
				['fontsize', ['fontsize']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['table', ['table']],
				['insert', ['link', 'picture']],
				['view', ['codeview']],
				['height', ['height']],
			],
			workflow_email_action_all_shortcodes_obj
		);

	});

})(jQuery);