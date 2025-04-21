<?php

namespace BookneticAddon\EmailWorkflow\Backend;

use BookneticAddon\EmailWorkflow\EmailWorkflowDriver;
use BookneticAddon\EmailWorkflow\Integrations\GoogleGmailService;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\EmailWorkflow\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

    /**
     * @var WorkflowEventsManager
     */
    private $workflowEventsManager;

    public function __construct($workflowEventsManager)
    {
        $this->workflowEventsManager = $workflowEventsManager;
    }

	public function settings_view ()
	{
		Capabilities::must( 'email_settings' );

        $accessToken  = Helper::getOption('gmail_smtp_access_token','');
        $authorized = ! empty( $accessToken );
        $errors     = [];

        if( $authorized )
        {
            $gmailService = new GoogleGmailService();
            $client = $gmailService->getClient();
            $client->setAccessToken( $accessToken );

            $Oauth2 = new \BookneticVendor\Google\Service\Oauth2($client);
            $gmailApi = new \BookneticVendor\Google\Service\Gmail( $client );

            try
            {
                if ( strpos( $client->getAccessToken()['scope'], 'https://www.googleapis.com/auth/gmail.settings.basic' ) === false )
                {
                    $errors[][ 'error_description' ] = bkntc__( 'New scope added. To refresh the session, please log out and log in again.' );
                }

                $aliases = $gmailApi->users_settings_sendAs->listUsersSettingsSendAs( 'me' )->getSendAs();

                $userInfo = $Oauth2->userinfo->get();
            }
            catch ( \Exception $e )
            {
                $errors[] = json_decode( $e->getMessage(), true );
            }
        }

        $email = ( $authorized && ! empty($userInfo) && empty ($errors) ) ? $userInfo->email : '----';
        $aliases = ! empty( $aliases ) ? $aliases : [];

        return $this->modalView( __DIR__ . '/view/email_settings.php',compact('email','authorized', 'aliases', 'errors'));
	}

    public function logout_gmail()
    {
        if( ! Permission::isAdministrator() )
            return $this->response(false);

        Helper::deleteOption('gmail_smtp_access_token',false);
        return $this->response(true);
    }

	public function save_settings()
	{
		Capabilities::must('email_settings');

		$mail_gateway		= Helper::_post('mail_gateway', '', 'string');
		$smtp_hostname		= Helper::_post('smtp_hostname', '', 'string');
		$smtp_port			= Helper::_post('smtp_port', '', 'string');
		$smtp_secure		= Helper::_post('smtp_secure', '', 'string');
		$smtp_username		= Helper::_post('smtp_username', '', 'string');
		$smtp_password		= Helper::_post('smtp_password', '', 'string');
		$gmail_smtp_client_id		= Helper::_post('gmail_smtp_client_id', '', 'string');
		$gmail_smtp_client_secret	= Helper::_post('gmail_smtp_client_secret', '', 'string');
		$sender_email		= Helper::_post('sender_email', '', 'string');
		$sender_name		= Helper::_post('sender_name', '', 'string');

		if( $mail_gateway != 'smtp' || Helper::isSaaSVersion() )
		{
			$smtp_hostname		= '';
			$smtp_port			= '';
			$smtp_secure		= '';
			$smtp_username		= '';
			$smtp_password		= '';
		}
		else if( $mail_gateway == 'smtp' && ( empty( $smtp_hostname ) || empty( $smtp_port ) || !is_numeric( $smtp_port ) || empty( $smtp_secure ) || !in_array( $smtp_secure, ['tls', 'ssl', 'no'] ) || empty( $smtp_username ) ) )
		{
			return $this->response(false, bkntc__('Please fill the SMTP credentials!'));
		}else if( $mail_gateway == 'gmail_smtp' && ( empty( $gmail_smtp_client_id ) || empty( $gmail_smtp_client_secret ) ) )
        {
            return $this->response(false, bkntc__('Please fill the Gmail SMTP credentials!'));
        }


		if( empty( $sender_name ) )
		{
			return $this->response(false, bkntc__('Please type the sender name field!'));
		}

		if( ! Helper::isSaaSVersion() )
		{
			if( empty( $sender_email ) || !filter_var( $sender_email, FILTER_VALIDATE_EMAIL ) )
			{
				return $this->response(false, bkntc__('Please type the sender email field!'));
			}

			Helper::setOption('mail_gateway', $mail_gateway);
			Helper::setOption('smtp_hostname', $smtp_hostname);
			Helper::setOption('smtp_port', $smtp_port);
			Helper::setOption('smtp_secure', $smtp_secure);
			Helper::setOption('smtp_username', $smtp_username);
			Helper::setOption('smtp_password', $smtp_password);
			Helper::setOption('gmail_smtp_client_id', $gmail_smtp_client_id);
			Helper::setOption('gmail_smtp_client_secret', $gmail_smtp_client_secret);
			Helper::setOption('sender_email', $sender_email);
		}

		Helper::setOption('sender_name', $sender_name);

        $translations = Helper::_post( 'translations', '', 'string' );
        Helper::setTranslatedOption( $translations, [ 'sender_name' ] );
		return $this->response(true);
	}

    public function gmail_smtp_login()
    {
        if( Helper::isSaaSVersion() )
            return $this->response(false );

        $mail_gateway		        = Helper::_post('mail_gateway', '', 'string');
        $gmail_smtp_client_id		= Helper::_post('gmail_smtp_client_id', '', 'string');
        $gmail_smtp_client_secret	= Helper::_post('gmail_smtp_client_secret', '', 'string');
        $sender_email		        = Helper::_post('sender_email', '', 'string');
        $sender_name		        = Helper::_post('sender_name', '', 'string');

        Helper::setOption('mail_gateway', $mail_gateway);
        Helper::setOption('gmail_smtp_client_id', $gmail_smtp_client_id);
        Helper::setOption('gmail_smtp_client_secret', $gmail_smtp_client_secret);
        Helper::setOption('sender_email', $sender_email);
        Helper::setOption('sender_name', $sender_name);

        $service = new GoogleGmailService();
        $client = $service->getClient();
        $authUrl = $client->createAuthUrl();

        return $this->response(true, ['redirect_url'=>$authUrl]);
    }

	public function workflow_action_edit_view()
	{
		$id = Helper::_post('id', 0, 'int');

		$workflowActionInfo = WorkflowAction::get( $id );
		if( ! $workflowActionInfo )
		{
			return $this->response( false );
		}

        $data = !empty($workflowActionInfo->data) ? json_decode($workflowActionInfo->data, true) : [];

        $availableParams = $this->workflowEventsManager->get(Workflow::get($workflowActionInfo->workflow_id)['when'])
            ->getAvailableParams();

        $toShortcodes               = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, ['email']);
        $subjectAndBodyShortcodes   = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams);
        $attachmentShortcodes       = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams,['file','url']);

        $data['attachments_value'] = isset($data['attachments']) ? explode(',',   $data['attachments']) : [];
        $data['to_value'] = isset($data['to']) ? explode(',',   $data['to']) : [];

        $toAllShortcodeList = $this->shortcodeListGenerate($toShortcodes , $data['to_value']);
        $attachmentAllShortcodeList = $this->shortcodeListGenerate($attachmentShortcodes , $data['attachments_value']);

        return $this->modalView( __DIR__ . '/view/workflow_action_edit.php', [
			'action_info'   =>  $workflowActionInfo,
			'data'          =>  $data,
            'to_shortcodes' =>  $toAllShortcodeList,
            'all_shortcodes'=>  $subjectAndBodyShortcodes,
            'attachment_shortcodes'=>  $attachmentAllShortcodeList,
		], [ 'workflow_action_id' => $id ] );
	}

    private function shortcodeListGenerate($shortcodeList,$shortcodeDbValue)
    {
        $list = [];

        foreach ( $shortcodeList as $value )
        {
            $list['{'.$value['code'].'}']['value'] = $value['name'];
        }

        foreach ( $shortcodeDbValue as $value )
        {
            if( empty($value) ) continue;

            if( ! array_key_exists($value , $list) )
            {
                $list[$value]['value'] = $value;
            }

            $list[$value]['selected'] = true;
        }

        return $list;
	}

	public function workflow_action_save_data()
	{
		$id             = Helper::_post( 'id', 0, 'int' );
		$to             = Helper::_post( 'to', '', 'string' );
		$subject        = Helper::_post( 'subject', '', 'string' );
		$body           = Helper::_post( 'body', '', 'string' );
		$attachments    = Helper::_post( 'attachments', '', 'string' );
        $is_active      = Helper::_post( 'is_active', 1, 'num' );

		if( ! WorkflowAction::get( $id ) )
		{
			return $this->response( false );
		}

		$newData = [
			'to'            =>  $to,
			'subject'       =>  $subject,
			'body'          =>  $body,
			'attachments'   =>  $attachments
		];

		WorkflowAction::where('id', $id)->update([ 'data' => json_encode( $newData ), 'is_active' => $is_active ]);

		return $this->response( true );
	}

    public function workflow_action_send_test_data ()
    {
        $to = Helper::_post('to', '', 'string');
        $actionId = Helper::_post('id', 0, 'int');

        if( !empty( $to ) && $actionId > 0 )
        {
            $actionInf = WorkflowAction::get( $actionId );
            $settings = json_decode( $actionInf->data, true );
            $settings['to'] = $to;
            $actionInf->data = json_encode($settings);
            $actionInf->when = 'send_test';
            $driver = new EmailWorkflowDriver();
            $driver->handle(new Collection(), $actionInf, new ShortCodeService());
        }

        return $this->response( true );
    }

}
