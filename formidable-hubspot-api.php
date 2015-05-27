<?php
/*
Plugin Name: Formidable Hubspot API
Description: Create hubspot customers on the Forms API via Formidable Forms
Version: 0.1
Plugin URI: http://ottawacondonetwork.com
Author: Harry Brundage
Author URI: http://harry.me
*/

function frm_forms_hub_api_autoloader($class_name) {
    // Only load Frm classes here
    if ( ! preg_match('/^FrmHubAPI.+$/', $class_name) ) {
        return;
    }

    $filepath = dirname(__FILE__);
    if ( preg_match('/^.+Helper$/', $class_name) ) {
        $filepath .= '/helpers/';
    } else if ( preg_match('/^.+Controller$/', $class_name) ) {
        $filepath .= '/controllers/';
    } else {
        $filepath .= '/models/';
    }

    $filepath .= $class_name .'.php';

    if ( file_exists($filepath) ) {
        include($filepath);
    }
}

// Add the autoloader
spl_autoload_register('frm_forms_hub_api_autoloader');

FrmHubAPIAppController::load_hooks();
FrmHubAPISettingsController::load_hooks();
