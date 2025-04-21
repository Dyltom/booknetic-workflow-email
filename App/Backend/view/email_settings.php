<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\EmailWorkflow\EmailWorkflowAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\EmailWorkflow\bkntc__;

/**
 * @var mixed $parameters
 */

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo EmailWorkflowAddon::loadAsset('assets/css/email_settings.css')?>">
	<script type="application/javascript" src="<?php echo EmailWorkflowAddon::loadAsset('assets/js/email_settings.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Email settings')?>
		</div>
		<div class="ms-content">

			<?php if( !Helper::isSaaSVersion() ):?>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_mail_gateway"><?php echo bkntc__('Mail Gateway')?>:</label>
					<select class="form-control" id="input_mail_gateway">
						<option value="wp_mail"<?php echo ( Helper::getOption('mail_gateway', 'wp_mail') == 'wp_mail' ? ' selected' : '' )?>><?php echo bkntc__('WordPress Mail')?></option>
						<option value="smtp"<?php echo ( Helper::getOption('mail_gateway', 'wp_mail') == 'smtp' ? ' selected' : '' )?>><?php echo bkntc__('SMTP')?></option>
						<option value="gmail_smtp"<?php echo ( Helper::getOption('mail_gateway', 'wp_mail') == 'gmail_smtp' ? ' selected' : '' )?>><?php echo bkntc__('Gmail SMTP')?></option>
					</select>
				</div>
			</div>

			<div class="smtp_details dashed-border">
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_hostname"><?php echo bkntc__('SMTP Hostname')?>:</label>
						<input class="form-control" id="input_smtp_hostname" value="<?php echo htmlspecialchars( Helper::getOption('smtp_hostname', '') )?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_port"><?php echo bkntc__('SMTP Port')?>:</label>
						<input class="form-control" id="input_smtp_port" value="<?php echo htmlspecialchars( Helper::getOption('smtp_port', '') )?>">
					</div>
					<div class="form-group col-md-3">
						<label for="input_smtp_secure"><?php echo bkntc__('SMTP Secure')?>:</label>
						<select class="form-control" id="input_smtp_secure">
							<option value="tls"<?php echo ( Helper::getOption('smtp_secure', 'tls') == 'tls' ? ' selected' : '' )?>><?php echo bkntc__('TLS')?></option>
							<option value="ssl"<?php echo ( Helper::getOption('smtp_secure', 'tls') == 'ssl' ? ' selected' : '' )?>><?php echo bkntc__('SSL')?></option>
							<option value="no"<?php echo ( Helper::getOption('smtp_secure', 'tls') == 'no' ? ' selected' : '' )?>><?php echo bkntc__('Disabled ( not recommend )')?></option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_smtp_username"><?php echo bkntc__('Username')?>:</label>
						<input class="form-control" id="input_smtp_username" value="<?php echo htmlspecialchars( Helper::getOption('smtp_username', '') )?>">
					</div>
					<div class="form-group col-md-6">
						<label for="input_smtp_password"><?php echo bkntc__('Password')?>:</label>
						<input class="form-control" id="input_smtp_password" value="<?php echo htmlspecialchars( Helper::getOption('smtp_password', '') )?>">
					</div>
				</div>
			</div>
			<div class="gmail_smtp_details dashed-border">
				<div class="form-row">

                    <?php foreach ( $parameters['errors'] as $error  ): ?>
					<?php
						if( isset( $error[ 'error_description' ] ) )
						{
							$msg = htmlspecialchars($error[ 'error_description' ]);
						}
						else if ( isset( $error['error'][ 'message' ] ) )
						{
							$msg = htmlspecialchars( $error[ 'error' ][ 'message' ] );
						}
						else
						{
							$msg = bkntc__( 'Looks like there was an unknown error caused by Google, please contact support https://support.fs-code.com/' );
						}
					?>
                        <div class="form-group col-md-12">
                            <div style="text-align: center;" class="alert alert-danger" role="alert"><?php echo $msg ?></div>
                        </div>
                    <?php endforeach; ?>

					<div class="form-group col-md-6">
						<label for="input_smtp_hostname"><?php echo bkntc__('Client ID')?>:</label>
						<input class="form-control" id="input_gmail_smtp_client_id" value="<?php echo htmlspecialchars( Helper::getOption('gmail_smtp_client_id', '') )?>">
					</div>
                    <div class="form-group col-md-6">
                        <label for="input_smtp_port"><?php echo bkntc__('Client secret')?>:</label>
                        <input class="form-control" id="input_gmail_smtp_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('gmail_smtp_client_secret', '') )?>">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="input_smtp_port"><?php echo bkntc__('Redirect URI')?>:</label>
                        <input class="form-control" id="input_redirect_uri" readonly value="<?php echo \BookneticAddon\EmailWorkflow\Integrations\GoogleGmailService::redirectURI(); ?>">
                    </div>
				</div>
                <div class="form-row justify-content-start">
                    <div class="form-group col-md-6 text-left">
                        <button id="gmail_login_btn" class="btn btn-primary px-4 <?php echo $parameters['authorized'] ? 'hidden' : ''?>"><?php echo bkntc__('Authorize') ?></button>
                        <p class="<?php echo $parameters['authorized'] ? '' : 'hidden'?>">
                            <span><?php echo bkntc__('Logged as %s',$parameters['email'] ) ?></span>
                            <a id="gmail_logout_btn" href="javascript:void(0)">( <?php echo bkntc__('Log out') ?> )</a>
                        </p>
                    </div>
                </div>

			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_email"><?php echo bkntc__('Sender E-mail')?>:</label>
					<?php if ( Helper::getOption('mail_gateway', 'wp_mail') == 'gmail_smtp' ): ?>
						<input style="display: none" class="form-control input_sender_email" value="<?php echo htmlspecialchars( Helper::getOption('sender_email', '') )?>">
						<select <?php echo ! empty( $parameters[ 'errors' ] ) ? 'disabled' : ''  ?> class="form-control input_sender_email">
							<option></option>
							<?php foreach( $parameters[ 'aliases' ] AS $alias ): ?>
								<option value="<?php echo htmlspecialchars( $alias[ 'sendAsEmail' ] ) ?>" <?php echo htmlspecialchars( Helper::getOption('sender_email', '') )  === htmlspecialchars( $alias[ 'sendAsEmail' ] ) ? 'selected' : '' ?>><?php echo $alias[ 'sendAsEmail' ] ?></option>
							<?php endforeach; ?>
						</select>
					<?php else: ?>
						<input class="form-control input_sender_email" value="<?php echo htmlspecialchars( Helper::getOption('sender_email', '') )?>">
						<select style="display:none;" <?php echo ! empty( $parameters[ 'errors' ] ) ? 'disabled' : ''  ?> class="form-control input_sender_email">
							<option></option>
							<?php foreach( $parameters[ 'aliases' ] AS $alias ): ?>
								<option value="<?php echo htmlspecialchars( $alias[ 'sendAsEmail' ] ) ?>" <?php echo htmlspecialchars( Helper::getOption('sender_email', '') )  === htmlspecialchars( $alias[ 'sendAsEmail' ] ) ? 'selected' : '' ?>><?php echo $alias[ 'sendAsEmail' ] ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
				<div class="form-group col-md-6">
					<label for="input_sender_name"><?php echo bkntc__('Sender Name')?>:</label>
					<input class="form-control" data-multilang="true" id="input_sender_name" value="<?php echo htmlspecialchars( Helper::getOption('sender_name', '') )?>">
				</div>
			</div>
			<?php else:?>
				<div class="form-row">
					<div class="form-group col-md-6">
						<label for="input_sender_name"><?php echo bkntc__('Sender Name')?>:</label>
						<input class="form-control" id="input_sender_name" value="<?php echo htmlspecialchars( Helper::getOption('sender_name', '') )?>">
					</div>
				</div>
			<?php endif;?>

		</div>
	</div>
</div>