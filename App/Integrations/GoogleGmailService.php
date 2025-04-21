<?php

namespace BookneticAddon\EmailWorkflow\Integrations;

use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Helpers\Helper;
use BookneticVendor\Google\Client;
use BookneticVendor\Google\Service\Gmail;

class GoogleGmailService
{

    const APPLICATION_NAME = 'Booknetic';
    const ACCESS_TYPE = 'offline';

    private $client_id;
    private $client_secret;
    private $access_token;
    private $client;
    private $service;

    public static function redirectURI()
    {
        return admin_url( 'admin.php?page=' . Backend::getSlugName() . '&module=settings&gmail_smtp=true' );
    }

    public function __construct()
    {
        $this->client_id = Helper::getOption('gmail_smtp_client_id', '', false);
        $this->client_secret = Helper::getOption('gmail_smtp_client_secret', '', false);
    }

    public function setAccessToken( )
    {
        $access_token = Helper::getOption('gmail_smtp_access_token','');
        $this->access_token = !is_array( $access_token ) ? json_decode( $access_token, true ) : $access_token;

        $this->getClient()->setAccessToken( $this->access_token );

        if ( $this->getClient()->isAccessTokenExpired() )
        {
            $refresh_token = $this->getClient()->getRefreshToken();
            $this->getClient()->fetchAccessTokenWithRefreshToken( $refresh_token );

            $this->access_token = $this->getClient()->getAccessToken();
            Helper::setOption( 'gmail_smtp_access_token', json_encode($this->getClient()->getAccessToken()));
        }

        return $this;
    }

    public function createAuthURL( $redirect = true )
    {
        $authUrl = $this->getClient()->createAuthUrl();

        if( $redirect )
        {
            Helper::redirect( $authUrl );
        }

        return $authUrl;
    }

    public function fetchAccessToken()
    {
        $code = Helper::_get('code', '', 'string');

        if( empty( $code ) )
            return false;

        $this->getClient()->fetchAccessTokenWithAuthCode( $code );
        $access_token = $this->getClient()->getAccessToken();

        return json_encode($access_token);
    }

    public function revokeToken()
    {
        $this->getClient()->revokeToken();

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if( is_null( $this->client ) )
        {
            $this->client = new Client();
            $this->client->setApplicationName(static::APPLICATION_NAME);
            $this->client->setClientId($this->client_id);
            $this->client->setClientSecret($this->client_secret);
            $this->client->setRedirectUri(static::redirectURI());
            $this->client->setAccessType(static::ACCESS_TYPE);
            $this->client->setPrompt('consent');
            $this->client->addScope([
                'email',
                'profile',
                'https://mail.google.com/',
                'https://www.googleapis.com/auth/gmail.settings.basic'
            ]);
        }

        return $this->client;
    }

    public function getService()
    {
        if( is_null( $this->service ) )
        {
            $this->service = new Gmail( $this->getClient() );
        }

        return $this->service;
    }


}