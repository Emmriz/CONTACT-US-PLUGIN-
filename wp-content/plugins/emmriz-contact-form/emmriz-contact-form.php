<?php
/**
 * Plugin Name: Emmriz Contact Form
 * Plugin URI: https://yourwebsite.com/emmriz-contact-form
 * Description: Drag & drop contact form builder with multiple forms support and real-time preview.
 * Version: 1.0.0
 * Author: Emmriz
 * Author URI: https://emmriztech.com.ng
 * License: GPL v2 or later
 * Text Domain: emmriz-contact-form
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ECF_VERSION', '1.0.0');
define('ECF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ECF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ECF_PLUGIN_BASENAME', plugin_basename(__FILE__));

final class Emmriz_Contact_Form {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        $this->includes();
        $this->init_classes();
        $this->register_hooks();

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('emmriz-contact-form', false, dirname(ECF_PLUGIN_BASENAME) . '/languages');
    }

    private function includes() {
        $path = ECF_PLUGIN_PATH . 'includes/';

        require_once $path . 'class-ecf-form-builder.php';
        require_once $path . 'class-ecf-drag-drop-builder.php';
        require_once $path . 'class-ecf-form-handler.php';
        require_once $path . 'class-ecf-submissions.php';
        require_once $path . 'class-ecf-email-handler.php';
        require_once $path . 'class-ecf-shortcodes.php';
        require_once $path . 'class-ecf-ajax.php';
        require_once $path . 'class-ecf-template-helper.php';
        require_once $path . 'class-emmriz-contact-db.php';

        if (is_admin()) {
            require_once $path . 'class-ecf-admin.php';
        }
    }

    private function init_classes() {
        ECF_Form_Builder::get_instance();
        ECF_Drag_Drop_Builder::get_instance();
        ECF_Form_Handler::get_instance();
        ECF_Submissions::get_instance();
        ECF_Email_Handler::get_instance();
        ECF_Shortcodes::get_instance();
        ECF_Ajax::get_instance();

        if (is_admin()) {
            ECF_Admin::get_instance();
        }
    }

    private function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
        add_action('init', array($this, 'init_plugin'));
    }

    /** ------------------------
     * FRONTEND ASSETS
     * ------------------------ */
    public function register_frontend_assets() {
        wp_register_style(
            'ecf-frontend',
            ECF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ECF_VERSION
        );

        wp_register_script(
            'ecf-frontend',
            ECF_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ECF_VERSION,
            true
        );

        wp_localize_script('ecf-frontend', 'ecf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_ajax_nonce'),
            'enable_ajax' => true
        ));
    }

    /** ------------------------
     * ADMIN ASSETS
     * ------------------------ */
    public function register_admin_assets($hook) {
        // Restrict to plugin pages only
        if (strpos($hook, 'emmriz-contact-form') === false) {
            return;
        }

        wp_register_style(
            'ecf-admin',
            ECF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ECF_VERSION
        );

        wp_register_style(
            'ecf-builder',
            ECF_PLUGIN_URL . 'assets/css/builder.css',
            array('ecf-admin'),
            ECF_VERSION
        );

        wp_register_script(
            'ecf-sortable',
            ECF_PLUGIN_URL . 'assets/js/vendors/sortable.js',
            array(),
            '1.15.0',
            true
        );

        wp_register_script(
            'ecf-builder',
            ECF_PLUGIN_URL . 'assets/js/builder.js',
            array('jquery', 'ecf-sortable', 'jquery-ui-sortable'),
            ECF_VERSION,
            true
        );

        wp_register_script(
            'ecf-admin',
            ECF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'ecf-builder'),
            ECF_VERSION,
            true
        );

        wp_localize_script('ecf-builder', 'ecf_builder', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_builder_nonce'),
            'field_types' => $this->get_available_field_types(),
            'i18n' => $this->get_builder_translations()
        ));
    }

    private function get_available_field_types() {
        return array(
            'text' => array(
                'label' => __('Text Input', 'emmriz-contact-form'),
                'icon' => 'dashicons-editor-textcolor',
                'defaults' => array(
                    'label' => __('Text', 'emmriz-contact-form'),
                    'placeholder' => __('Enter text...', 'emmriz-contact-form'),
                    'required' => false
                )
            ),
            'email' => array(
                'label' => __('Email Input', 'emmriz-contact-form'),
                'icon' => 'dashicons-email',
                'defaults' => array(
                    'label' => __('Email', 'emmriz-contact-form'),
                    'placeholder' => __('your@email.com', 'emmriz-contact-form'),
                    'required' => true
                )
            ),
            'textarea' => array(
                'label' => __('Textarea', 'emmriz-contact-form'),
                'icon' => 'dashicons-editor-paragraph',
                'defaults' => array(
                    'label' => __('Message', 'emmriz-contact-form'),
                    'placeholder' => __('Your message...', 'emmriz-contact-form'),
                    'required' => true
                )
            ),
            'submit' => array(
                'label' => __('Submit Button', 'emmriz-contact-form'),
                'icon' => 'dashicons-yes-alt',
                'defaults' => array(
                    'label' => __('Submit', 'emmriz-contact-form')
                )
            )
        );
    }

    private function get_builder_translations() {
        return array(
            'addField' => __('Add Field', 'emmriz-contact-form'),
            'deleteField' => __('Delete Field', 'emmriz-contact-form'),
            'duplicateField' => __('Duplicate Field', 'emmriz-contact-form'),
            'fieldSettings' => __('Field Settings', 'emmriz-contact-form'),
            'preview' => __('Preview', 'emmriz-contact-form'),
            'builder' => __('Builder', 'emmriz-contact-form'),
            'saveForm' => __('Save Form', 'emmriz-contact-form'),
            'formSaved' => __('Form Saved!', 'emmriz-contact-form'),
            'confirmDelete' => __('Are you sure you want to delete this field?', 'emmriz-contact-form')
        );
    }

    public function init_plugin() {
        $this->register_form_post_type();
    }

    private function register_form_post_type() {
        register_post_type('ecf_form', array(
            'labels' => array(
                'name' => __('Contact Forms', 'emmriz-contact-form'),
                'singular_name' => __('Contact Form', 'emmriz-contact-form'),
                'add_new' => __('Add New Form', 'emmriz-contact-form'),
                'add_new_item' => __('Add New Contact Form', 'emmriz-contact-form'),
                'edit_item' => __('Edit Contact Form', 'emmriz-contact-form'),
                'new_item' => __('New Contact Form', 'emmriz-contact-form'),
                'view_item' => __('View Contact Form', 'emmriz-contact-form'),
                'search_items' => __('Search Forms', 'emmriz-contact-form'),
                'not_found' => __('No forms found', 'emmriz-contact-form'),
                'not_found_in_trash' => __('No forms found in Trash', 'emmriz-contact-form')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'capabilities' => array('create_posts' => 'manage_options'),
            'map_meta_cap' => true
        ));
    }

    public function activate() {
        ECF_Submissions::get_instance()->create_table();

        if (!get_option('ecf_settings')) {
            update_option('ecf_settings', array(
                'default_email' => get_option('admin_email'),
                'enable_ajax' => true,
                'spam_protection' => 'honeypot'
            ));
        }

        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

/** ------------------------
 *  DEBUG HELPER
 * ------------------------ */
function ecf_debug_assets() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'ecf-builder') {
        echo '<!-- ECF Debug: Current hook: ' . current_filter() . ' -->';

        global $wp_scripts, $wp_styles;
        echo '<!-- ECF Debug: Registered scripts: ' . implode(', ', array_keys($wp_scripts->registered)) . ' -->';
        echo '<!-- ECF Debug: Registered styles: ' . implode(', ', array_keys($wp_styles->registered)) . ' -->';
        echo '<!-- ECF Debug: ECF_PLUGIN_URL: ' . ECF_PLUGIN_URL . ' -->';
        echo '<!-- ECF Debug: ECF_PLUGIN_PATH: ' . ECF_PLUGIN_PATH . ' -->';
    }
}
add_action('admin_head', 'ecf_debug_assets');

/** ------------------------
 *  START PLUGIN
 * ------------------------ */
function emmriz_contact_form() {
    return Emmriz_Contact_Form::get_instance();
}
add_action('plugins_loaded', 'emmriz_contact_form');

?>
