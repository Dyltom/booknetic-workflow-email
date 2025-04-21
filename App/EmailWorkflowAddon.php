<?php

namespace BookneticAddon\EmailWorkflow;

use BookneticApp\Config;
use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\WorkflowHelper;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Providers\Core\Route as SaasRoute;
use BookneticAddon\EmailWorkflow\Backend\Ajax;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, EmailWorkflowAddon::getAddonTextDomain() );
}

class EmailWorkflowAddon extends AddonLoader
{

	public function init ()
	{
        Capabilities::registerTenantCapability( 'email_workflow', bkntc__('Email workflow integration'), 'workflow'  );
        Capabilities::registerTenantCapability( 'email_settings', bkntc__('Email settings'), 'settings'  );

		if( ! Capabilities::tenantCan( 'email_workflow' ) )
			return;

		Capabilities::register('email_settings' , bkntc__('Email settings') , 'settings');
		Capabilities::registerLimit( 'email_allowed_max_number', bkntc__('Allowed maximum Email') );

        Config::getWorkflowDriversManager()->register( new EmailWorkflowDriver() );

        Listener::checkGmailSMTPCallback();
	}

	public function initBackend ()
	{
		if( ! Capabilities::tenantCan( 'email_workflow' ) )
			return;

        $ajaxController = new Ajax(Config::getWorkflowEventsManager());

		Route::post( 'email_workflow', $ajaxController, [ 'workflow_action_edit_view', 'workflow_action_save_data', 'workflow_action_send_test_data' ] );

		if ( Capabilities::userCan('email_settings') && Capabilities::tenantCan('email_settings') )
		{
			Route::post( 'email_settings', $ajaxController, [ 'settings_view', 'save_settings' ,'gmail_smtp_login','logout_gmail' ] );

			SettingsMenuUI::get( 'settings_view', 'email_settings' )
			              ->setTitle( bkntc__( 'Email settings' ) )
			              ->setDescription( bkntc__( 'You must set this settings for email workflow action ( wp_mail or SMTP settings )' ) )
			              ->setIcon( Helper::icon( 'email-settings.svg', 'Settings' ) )
			              ->setPriority( 7 );
		}

        add_filter('bkntc_tenant_limits' , function ($limitsArr ,$currentPlanInf){
            $limits = json_decode($currentPlanInf->permissions,true)['limits'];

            if( ! array_key_exists('email_allowed_max_number' , $limits )) return $limitsArr;

            $count = WorkflowHelper::getUsage( (new EmailWorkflowDriver())->getDriver() );
            $limitsArr['email'] = ['title'=>bkntc__('Email') , 'current_usage'=>$count , 'max_usage'=>$limits['email_allowed_max_number'] ];

            return $limitsArr;
        },10,2);
	}

}