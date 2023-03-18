<?php

/**
 * 
 * Plugin Name: Contact Plugin
 * Description: This is a test plugin
 * Version: 1.0.0
 * 
 */

if (!defined("ABSPATH")) {
    die("you can't be here");
}

if (!class_exists('ContactPlugin')) {
    class ContactPlugin
    {

        public function __construct()
        {
            define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
            require_once(MY_PLUGIN_PATH . '/vendor/autoload.php');
        }

        public function initialize()
        {
            include_once MY_PLUGIN_PATH . 'includes/options_page.php';
        }
    }

    $contactPlugin = new ContactPlugin;
    $contactPlugin->initialize();
}
