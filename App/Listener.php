<?php

namespace BookneticAddon\EmailWorkflow;

use BookneticAddon\EmailWorkflow\Integrations\GoogleGmailService;
use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Helpers\Helper;

class Listener
{
    public static function checkGmailSMTPCallback()
    {
        $gmail_smtp_redirect_uri = Helper::_get('gmail_smtp' ,'','string');
        $authCode = Helper::_get('code' ,'','string');
        if( empty($gmail_smtp_redirect_uri) || empty($authCode))
            return;

        $gmailService = new GoogleGmailService();
        $client = $gmailService->getClient();
        $client->fetchAccessTokenWithAuthCode($authCode);

        Helper::setOption('gmail_smtp_access_token', json_encode($client->getAccessToken() ) );
        Helper::redirect( admin_url( 'admin.php?page=' . Backend::getSlugName() . '&module=settings' ) );
    }
}