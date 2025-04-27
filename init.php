<?php
/*
 * Plugin Name: Email action for Bonzengine workflows
 * Description: Send comprehensive E-mail notifications to the relevant people.
 * Version: 1.2.6
 * Author: Bonzengine
 * Author URI: https://www.bonzengine.com
 * License: Commercial
 * Text Domain: bonzengine-workflow-email
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\EmailWorkflow\EmailWorkflowAddon::getAddonSlug() ] = new \BookneticAddon\EmailWorkflow\EmailWorkflowAddon();
    return $addons;
});
