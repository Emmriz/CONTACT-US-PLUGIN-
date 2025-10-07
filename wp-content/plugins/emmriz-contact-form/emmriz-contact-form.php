<?php
/**
 * Plugin Name: Emmriz Contact Form
 * Plugin URI: https://yourwebsite.com/emmriz-contact-form
 * Description: Drag & drop contact form builder with multiple forms support and real-time preview
 * Version: 1.0.0
 * Author: Emmriz
 * Author URI: https://emmriztech.com.ng
 * License: GPL v2 or later
 * Text Domain: emmriz-contact-form
 * Domain Path: /languages
 */

// Prevent direct access
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
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Include required files
        $this->includes();
        
        // Initialize classes
        $this->init_classes();
        
        // Register hooks
        $this->register_hooks();
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'emmriz-contact-form',
            false,
            dirname(ECF_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    private function includes() {
        $includes_path = ECF_PLUGIN_PATH . 'includes/';
        
        // Core functionality
        require_once $includes_path . 'class-ecf-form-builder.php';
        require_once $includes_path . 'class-ecf-drag-drop-builder.php';
        require_once $includes_path . 'class-ecf-form-handler.php';
        require_once $includes_path . 'class-ecf-submissions.php';
        require_once $includes_path . 'class-ecf-email-handler.php';
        require_once $includes_path . 'class-ecf-shortcodes.php';
        require_once $includes_path . 'class-ecf-ajax.php';
        require_once $includes_path . 'class-ecf-template-helper.php'; // Add this line
        
        // Admin interface
        if (is_admin()) {
            require_once $includes_path . 'class-ecf-admin.php';
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
        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
        
        // Initialize plugin
        add_action('init', array($this, 'init_plugin'));
    }
    
    public function register_frontend_assets() {
        // Frontend CSS
        wp_register_style(
            'ecf-frontend',
            ECF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ECF_VERSION
        );
        
        // Frontend JS
        wp_register_script(
            'ecf-frontend',
            ECF_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ECF_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ecf-frontend', 'ecf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_ajax_nonce')
        ));
    }
    
    public function register_admin_assets() {
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'emmriz-contact-form') === false) {
            return;
        }
        
        // Admin CSS
        wp_register_style(
            'ecf-admin',
            ECF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ECF_VERSION
        );
        
        // Builder CSS
        wp_register_style(
            'ecf-builder',
            ECF_PLUGIN_URL . 'assets/css/builder.css',
            array('ecf-admin'),
            ECF_VERSION
        );
        
        // Sortable.js for drag & drop
        wp_register_script(
            'ecf-sortable',
            ECF_PLUGIN_URL . 'assets/js/vendors/sortable.js',
            array(),
            '1.14.0',
            true
        );
        
        // Builder JS
        wp_register_script(
            'ecf-builder',
            ECF_PLUGIN_URL . 'assets/js/builder.js',
            array('jquery', 'ecf-sortable', 'jquery-ui-sortable'),
            ECF_VERSION,
            true
        );
        
        // Admin JS
        wp_register_script(
            'ecf-admin',
            ECF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'ecf-builder'),
            ECF_VERSION,
            true
        );
        
        // Localize builder data
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
        // Initialize custom post type for forms
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
            'capabilities' => array(
                'create_posts' => 'manage_options',
            ),
            'map_meta_cap' => true
        ));
    }
    
    public function activate() {
        // Create necessary database tables
        ECF_Submissions::get_instance()->create_table();
        
        // Set default options
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

// Initialize the plugin
function emmriz_contact_form() {
    return Emmriz_Contact_Form::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'emmriz_contact_form');
?>