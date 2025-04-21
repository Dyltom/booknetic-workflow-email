<?php
/*
 * Plugin Name: Email action for Booknetic workflows
 * Description: Send comprehensive E-mail notifications to the relevant people.
 * Version: 1.2.6
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-workflow-email
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\EmailWorkflow\EmailWorkflowAddon::getAddonSlug() ] = new \BookneticAddon\EmailWorkflow\EmailWorkflowAddon();
    return $addons;
});
