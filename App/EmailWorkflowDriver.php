<?php

namespace BookneticAddon\EmailWorkflow;

use Booknetic_PHPMailer\PHPMailer\PHPMailer;
use BookneticAddon\EmailWorkflow\Helpers\GmailMessageHelper;
use BookneticAddon\EmailWorkflow\Integrations\GoogleGmailService;
use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Common\WorkflowDriver;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Curl;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\WorkflowHelper;
use BookneticVendor\Google\Service\Gmail;
use function BookneticAddon\EmailWorkflow\bkntc__;

class EmailWorkflowDriver extends WorkflowDriver
{

	protected $driver = 'email';

	public static $cacheFiles = [];

	public function __construct()
	{
		$this->setName( bkntc__('Send Email') );
		$this->setEditAction( 'email_workflow', 'workflow_action_edit_view' );
	}

	public function handle( $eventData, $actionSettings, $shortCodeService )
	{
        $actionData = json_decode($actionSettings['data'],true);
        if ( empty( $actionData ) )
        {
            return;
        }
        
        // Debug logging for email workflow
        error_log( "[EmailWorkflow] Triggered with event: " . ($actionSettings['when'] ?? 'unknown') );
        error_log( "[EmailWorkflow] Event data: " . json_encode( $eventData ) );
    
		$sendTo         = $shortCodeService->replace( $actionData['to'], $eventData );
		$subject        = $shortCodeService->replace( $actionData['subject'], $eventData );
		$body           = $shortCodeService->replace( $actionData['body'], $eventData );
		$attachments    = $shortCodeService->replace( $actionData['attachments'], $eventData );
		
		$eventKey = $actionSettings['when'] ?? '';

		if (
			in_array($eventData['workflow'] ?? '', ['cis', 'deposit']) &&
			!in_array($eventKey, ['invoice_ready', 'invoice_ready_cis', 'invoice_ready_deposit', 'email_customer_quote_cis', 'quote_status_changed'])
		) {
			return;
		}
		

		$attachmentsArr = [];

		$allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'gif', 'png', 'bmp', 'xls', 'xlsx', 'csv', 'zip', 'rar'];

		if( !empty( $attachments ) )
		{
			$attachments = explode(',', $attachments);
			error_log("[EmailWorkflow] Processing " . count($attachments) . " attachments: " . json_encode($attachments));
			
			foreach ( $attachments AS $attachment )
			{
				$attachment = trim( $attachment );
				error_log("[EmailWorkflow] Processing attachment: $attachment");

				if( file_exists( $attachment ) && is_readable( $attachment ) )
				{
					$extension = strtolower( pathinfo( $attachment, PATHINFO_EXTENSION ) );
					if( in_array( $extension, $allowedExtensions ) )
					{
						$attachmentsArr[] = $attachment;
						error_log("[EmailWorkflow] Added local file attachment: $attachment (size: " . filesize($attachment) . " bytes)");
					}
					else
					{
						error_log("[EmailWorkflow] Skipped attachment due to invalid extension: $attachment");
					}
				}
				else if( filter_var( $attachment, FILTER_VALIDATE_URL ) )
				{
					error_log("[EmailWorkflow] Processing URL attachment: $attachment");
					
					$fileName = preg_replace( '[^a-zA-Z0-9\-\_\(\)]','', basename( $attachment ) );
					if ( empty( $fileName ) )
					{
						$fileName = uniqid();
					}

					$extension = strtolower( pathinfo( $attachment, PATHINFO_EXTENSION ) );
					if( ! in_array( $extension, $allowedExtensions ) )
					{
						$extension = 'tmp';
					}

					$fileName .= '.' . $extension;

					$cacheFilePath = Helper::uploadFolder('tmp') . $fileName;

					error_log("[EmailWorkflow] Downloading URL to cache: $cacheFilePath");
					file_put_contents( $cacheFilePath, Curl::getURL( $attachment ) );

					$attachmentsArr[] = $cacheFilePath;

					static::$cacheFiles[] = $cacheFilePath;
					error_log("[EmailWorkflow] Added URL attachment: $cacheFilePath");
				}
				else
				{
					error_log("[EmailWorkflow] Attachment not found or not accessible: $attachment");
				}
			}
		}

		// Validate attachments before sending.
		error_log("[EmailWorkflow] Validating " . count($attachmentsArr) . " attachments before sending");
		foreach ($attachmentsArr as $key => $attachment) {
			$exists = file_exists($attachment);
			$size = $exists ? filesize($attachment) : 0;
			$basename = basename($attachment);
			
			error_log("[EmailWorkflow] Validating: $attachment (exists: " . ($exists ? 'yes' : 'no') . ", size: $size, name: $basename)");
			
			if (!$exists || $size == 0 || $basename === 'placeholder.pdf') {
				unset($attachmentsArr[$key]);
				error_log("[EmailWorkflow] REMOVED invalid attachment: $basename (exists: " . ($exists ? 'yes' : 'no') . ", size: $size)");
			} else {
				error_log("[EmailWorkflow] KEEPING valid attachment: $basename (size: $size bytes)");
			}
		}
		$attachmentsArr = array_values($attachmentsArr);
		error_log("[EmailWorkflow] Final attachment count: " . count($attachmentsArr));

		if( ! empty( $sendTo ) )
		{
			$sendToArr = explode( ',', $sendTo );
			foreach ( $sendToArr AS $sendTo )
			{
                $this->send( trim( $sendTo ) , strip_tags( htmlspecialchars_decode(  str_replace('&nbsp;' ,' ' ,$subject ) ) ) , $body , $attachmentsArr , $actionSettings);
			}
		}
	}

	public function send( $sendTo, $subject, $body, $attachments , $actionSettings )
	{

		if( empty( $sendTo ) )
			return false;

        $logCount = WorkflowHelper::getUsage( $this->getDriver() );

		if( Capabilities::getLimit( 'email_allowed_max_number' ) <= $logCount && Capabilities::getLimit( 'email_allowed_max_number' ) > -1 )
		{
			return false;
		}

		$mailGateway	= Helper::getOption('mail_gateway', 'wp_mail', false);
		$senderEmail	= Helper::getOption('sender_email', '', false);
		$senderName		= Helper::getOption('sender_name', '', false);

		if( Capabilities::tenantCan('email_settings') )
		{
			$tenantSenderName = Helper::getOption('sender_name', '');
			if( !empty( $tenantSenderName ) )
			{
				$senderName = $tenantSenderName;
			}
		}

		$headers = 'From: ' . $senderName . ' <' . $senderEmail . '>' . "\r\n" .
		           "Content-Type: text/html; charset=UTF-8\r\n";

		if( $mailGateway == 'wp_mail' )
		{
			wp_mail( $sendTo, $subject, $body, $headers, $attachments );
		}
		else if( $mailGateway == 'smtp') // SMTP
		{
			$mail = new PHPMailer();

			$mail->isSMTP();

			$mail->Host			= Helper::getOption('smtp_hostname', '', false);
			$mail->Port			= Helper::getOption('smtp_port', '', false);
			$mail->SMTPSecure	= Helper::getOption('smtp_secure', '', false);
			$mail->SMTPAuth		= true;
			$mail->Username		= Helper::getOption('smtp_username', '', false);
			$mail->Password		= Helper::getOption('smtp_password', '', false);

			$mail->setFrom( $senderEmail, $senderName );
			$mail->addAddress( $sendTo );

			$mail->Subject		= $subject;
			$mail->Body			= $body;

			$mail->IsHTML(true);
			$mail->CharSet = 'UTF-8';

			foreach ( $attachments AS $attachment )
			{
				$mail->AddAttachment( $attachment, basename( $attachment ) );
			}

			$mail->send();
		}else if( $mailGateway == 'gmail_smtp') // Gmail SMTP
        {

            $gmailService = new GoogleGmailService();
            $client = $gmailService->getClient();

            $access_token = Helper::getOption('gmail_smtp_access_token','',false);
            $client->setAccessToken($access_token);

            if( $client->isAccessTokenExpired() )
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

            $service = new Gmail($client);
            $message = GmailMessageHelper::getInstance()
                ->setSenderName($senderName)
                ->setSenderEmail($senderEmail)
                ->setSendTo($sendTo)
                ->setSubject($subject)
                ->setBody($body)
                ->setAttachments($attachments)
                ->getMessage();

            try {
                $result = $service->users_messages->send( 'me' , $message);
            } catch (\Exception $e) {
                return false;
            }

        }

        WorkflowLog::insert([
            'workflow_id'   => $actionSettings['workflow_id'],
            'when'          => $actionSettings->when,
            'driver'    =>  $this->getDriver(),
            'date_time' =>  Date::dateTimeSQL(),
            'data'      =>  json_encode([
                'to'            =>$sendTo,
                'subject'       => $subject,
                'body'          => $body,
                'attachments'   =>$attachments
            ]),
        ]);

        return true;
	}
}
