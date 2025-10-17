<?php
/**
 * Handles form shortcodes for frontend display
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_shortcode('emmriz_contact_form', array($this, 'contact_form_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
    }
    
    /**
     * Contact form shortcode handler - FIXED VERSION
     */
    public function contact_form_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'title' => ''
        ), $atts, 'emmriz_contact_form');
        
        $form_id = absint($atts['id']);
        
        if (!$form_id) {
            return '<p>' . __('Please provide a valid form ID.', 'emmriz-contact-form') . '</p>';
        }
        
        $form_builder = ECF_Form_Builder::get_instance();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            return '<p>' . __('Form not found.', 'emmriz-contact-form') . '</p>';
        }
        
        // Enqueue assets
        $this->enqueue_frontend_assets();
        
        // Display form
        ob_start();
        $this->display_form($form);
        return ob_get_clean();
    }
    
    /**
     * Display form with optional success/error messages
     */
    private function display_form($form) {
        // Check for submission result
        $form_handler = ECF_Form_Handler::get_instance();
        $submission_result = $form_handler->get_submission_result();
        
        // Include form template
        $template_path = ECF_PLUGIN_PATH . 'templates/form-default.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>' . __('Form template not found.', 'emmriz-contact-form') . '</p>';
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    private function enqueue_frontend_assets() {
        wp_enqueue_style('ecf-frontend');
        wp_enqueue_script('ecf-frontend');
        
        // Localize script for AJAX
        wp_localize_script('ecf-frontend', 'ecf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_ajax_nonce'),
            'enable_ajax' => true
        ));
    }
    
    /**
     * Enqueue assets if shortcode is used on page
     */
    public function maybe_enqueue_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'emmriz_contact_form')) {
            $this->enqueue_frontend_assets();
        }
    }
}
?>