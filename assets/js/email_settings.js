(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		booknetic.initMultilangInput( $( '#input_sender_name' ), 'options', 'sender_name' );

		$('#booknetic_settings_area').on('change', '#input_mail_gateway', function()
		{
			$(".smtp_details").slideUp(300);
			$(".gmail_smtp_details").slideUp(300);

			if( $(this).val() == 'smtp' ||  $(this).val() == 'gmail_smtp' )
			{
				$( "." + $(this).val() + "_details").slideDown(300);
			}

			if ( $(this).val() == 'gmail_smtp' )
			{
				$('input.input_sender_email').slideUp(300)
				$('select.input_sender_email').show().next('.select2-container').slideDown(300)
			}
			else
			{
				$('select.input_sender_email').hide().next('.select2-container').slideUp('fast')
				$('input.input_sender_email:hidden').slideDown(300)
			}


		}).on('click', '.settings-save-btn', function ()
		{
			var mail_gateway	= $("#input_mail_gateway").val(),
				smtp_hostname	= $("#input_smtp_hostname").val(),
				smtp_port		= $("#input_smtp_port").val(),
				smtp_secure		= $("#input_smtp_secure").val(),
				smtp_username	= $("#input_smtp_username").val(),
				smtp_password	= $("#input_smtp_password").val(),
				gmail_smtp_client_id 		= $("#input_gmail_smtp_client_id").val(),
				gmail_smtp_client_secret 	= $("#input_gmail_smtp_client_secret").val(),
				sender_email	= $(".input_sender_email:visible").val(),
				sender_name		= $("#input_sender_name").val();

			var data = new FormData();

			data.append('mail_gateway', mail_gateway);
			data.append('smtp_hostname', smtp_hostname);
			data.append('smtp_port', smtp_port);
			data.append('smtp_secure', smtp_secure);
			data.append('smtp_username', smtp_username);
			data.append('smtp_password', smtp_password);
			data.append('gmail_smtp_client_id', gmail_smtp_client_id);
			data.append('gmail_smtp_client_secret', gmail_smtp_client_secret);
			data.append('sender_email', sender_email);
			data.append('sender_name', sender_name);
			data.append('translations', booknetic.getTranslationData( $( '#booknetic_settings_area' ) ));

			booknetic.ajax('email_settings.save_settings', data, function()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		}).on('click', '#gmail_login_btn', function ()
		{

			let gmail_smtp_client_id 		= $("#input_gmail_smtp_client_id").val(),
				gmail_smtp_client_secret 	= $("#input_gmail_smtp_client_secret").val(),
				sender_email				= $(".input_sender_email:visible").val(),
				sender_name					= $("#input_sender_name").val(),
				mail_gateway				= $("#input_mail_gateway").val();

			var data = new FormData();
			data.append('gmail_smtp_client_id', gmail_smtp_client_id);
			data.append('gmail_smtp_client_secret', gmail_smtp_client_secret);
			data.append('sender_email', sender_email);
			data.append('sender_name', sender_name);
			data.append('mail_gateway', mail_gateway);

			booknetic.ajax('email_settings.gmail_smtp_login', data, function( response )
			{
				if( response.redirect_url )
				{
					console.log(response.redirect_url)
					location.href = response.redirect_url
				}else{
					alert('no')
				}
			});
		});

		$('#gmail_logout_btn').on('click',()=>{
			booknetic.ajax('email_settings.logout_gmail', {}, function()
			{
				location.reload();
			});
		});

		if( $("#input_mail_gateway").val() != 'smtp' )
		{
			$(".smtp_details").hide();
		}

		if( $("#input_mail_gateway").val() != 'gmail_smtp' )
		{
			$(".gmail_smtp_details").hide();
		}

		$("#input_mail_gateway, #input_smtp_secure, select.input_sender_email").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select'),
			allowClear: true,
			minimumResultsForSearch: -1
		});

		$('#input_mail_gateway').trigger('change')

	});

})(jQuery);