<?php
/*
Plugin Name: Multi-Step Form Plugin
Description: A plugin to create a multi-step form and save data to a custom post type.
Version: 1.0
Author: MB
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/*$css_path = plugin_dir_url(__FILE__) . 'admin/css/form-style.css';
echo $css_path.'<br>';
$js_path= plugin_dir_url(__FILE__) . 'admin/js/form-script.js';
echo $js_path;*/

// Include the main class file
require plugin_dir_path(__FILE__) . 'class-multi-step-form.php';


// Initialize the plugin
function msfp_initialize_plugin() {
    $form = new Multi_Step_Form();
    $form->register_hooks();
}
add_action('plugins_loaded', 'msfp_initialize_plugin');


// Function to create the custom table
function plugin_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'my_custom_table'; // Add table prefix
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if ($table_exists != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            value text NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Include the upgrade.php file for dbDelta function
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Use dbDelta to create or update the table
        dbDelta($sql);
    }
}

// Hook to run the function on plugin activation
register_activation_hook(__FILE__, 'plugin_create_table');
