<?php
/**
 * Plugin Name:       Levelup Transactional Email & SMS
 * Plugin URI:        https://drift.levelup.no
 * Description:       Get access to send SMS and email through Levelup's API
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Levelup AS
 * Author URI:        https://drift.levelup.no/om-oss
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       levelup-wp-sms-plugin
 * Domain Path:       /languages
 */

/*
   * Levelup Transactional Email & SMS
   * Copyright (C) 2020 Levelup AS
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// BASE VARIABLES
$LVLTES_PLUGIN_DIR = dirname(__FILE__);
$GLOBALS['LVLTES_PLUGIN_DIR'] = dirname(__FILE__);

// BASE FUNCTIONS
@include $LVLTES_PLUGIN_DIR . '/includes/base-functions.php';

safeRequireFile('/includes/lvltes-class.php');
$lvltes = new LVLTES();

safeRequireFile('/includes/template-functions.php');

// INCLUDES FOR ADMINS
if (is_admin()) {
    safeRequireFile('/includes/admin/admin.php');
    $lvltesAdmin = new LVLTESAdmin();

    safeRequireFile('/includes/admin/test-configuration.php');
    safeRequireFile('/includes/admin/getting-started.php');
}

