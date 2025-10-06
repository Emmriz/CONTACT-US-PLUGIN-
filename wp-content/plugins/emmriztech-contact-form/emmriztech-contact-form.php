<?php
/**
 * Plugin Name: EmmrizTech Contact Form
 * Description: A secure and responsive contact form plugin with multiple templates using TailwindCSS.
 * Version: 1.0.0
 * Author: EmmrizTech
 * License: GPL2
 * Text Domain: emmriztech-contact-form
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

define('EMMRIZTECH_CF_PATH', plugin_dir_path(__FILE__));
define('EMMRIZTECH_CF_URL', plugin_dir_url(__FILE__));

// Include main class
require_once EMMRIZTECH_CF_PATH . 'includes/class-emmriztech-contact-form.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    new EmmrizTech_Contact_Form();
});
