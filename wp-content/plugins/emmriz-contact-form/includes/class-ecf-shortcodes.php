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
     * Contact form shortcode handler
     */
    public function contact_form_shortcode($atts) {
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
        wp_enqueue_style('ecf-frontend');
        wp_enqueue_script('ecf-frontend');
        
        // Display form
        ob_start();
        $this->display_form($form);
        return ob_get_clean();
    }
    
    /**
     * Display form with optional success/error messages
     */
    private function display_form($form) {
        // Check for submission result using the new method
        $submission_result = ECF_Form_Handler::get_submission_result();
        
        // Include form template
        include ECF_PLUGIN_PATH . 'templates/form-default.php';
    }
    
    /**
     * Enqueue assets if shortcode is used on page
     */
    public function maybe_enqueue_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'emmriz_contact_form')) {
            wp_enqueue_style('ecf-frontend');
            wp_enqueue_script('ecf-frontend');
        }
    }
}
?>