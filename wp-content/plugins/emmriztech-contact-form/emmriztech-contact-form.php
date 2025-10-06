<?php
/**
* Plugin Name: EmmrizTech Contact Form
* Plugin URI: https://emmriztech.com/
* Description: Production-ready contact form with Tailwind, admin settings, and message logging.
* Version: 1.2.0
* Author: EmmrizTech
* Text Domain: emmriztech-contact-form
* License: GPLv2 or later
*/


if (!defined('ABSPATH')) exit;


define('EMMRIZTECH_CF_PATH', plugin_dir_path(__FILE__));
define('EMMRIZTECH_CF_URL', plugin_dir_url(__FILE__));
define('EMMRIZTECH_CF_VERSION', '1.2.0');


// Includes
require_once EMMRIZTECH_CF_PATH . 'includes/class-emmriztech-contact-db.php';
require_once EMMRIZTECH_CF_PATH . 'includes/class-emmriztech-contact-form.php';
require_once EMMRIZTECH_CF_PATH . 'includes/class-emmriztech-contact-admin.php';


// Initialize
add_action('plugins_loaded', function() {
// DB handler must be ready early
EmmrizTech_Contact_DB::instance();
new EmmrizTech_Contact_Form();
new EmmrizTech_Contact_Admin();
});


// Activation / Deactivation hooks
register_activation_hook(__FILE__, ['EmmrizTech_Contact_DB', 'install']);
register_deactivation_hook(__FILE__, ['EmmrizTech_Contact_DB', 'deactivate']);