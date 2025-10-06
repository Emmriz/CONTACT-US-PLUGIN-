<?php
/**
 * Plugin Name: EmmrizTech Contact Form
 * Plugin URI: https://emmriztech.com/
 * Description: A secure and responsive contact form plugin with multiple templates using TailwindCSS.
 * Version: 1.1.0
 * Author: EmmrizTech
 * Author URI: https://emmriztech.com/
 * License: GPLv2 or later
 * Text Domain: emmriztech-contact-form
 */

if (!defined('ABSPATH')) exit;

// Define constants
define('EMMRIZTECH_CF_PATH', plugin_dir_path(__FILE__));
define('EMMRIZTECH_CF_URL', plugin_dir_url(__FILE__));
define('EMMRIZTECH_CF_VERSION', '1.1.0');

// Includes
require_once EMMRIZTECH_CF_PATH . 'includes/class-emmriztech-contact-form.php';
require_once EMMRIZTECH_CF_PATH . 'includes/class-emmriztech-contact-admin.php';

// Initialize plugin
add_action('plugins_loaded', function() {
    new EmmrizTech_Contact_Form();
    new EmmrizTech_Contact_Admin();
});
