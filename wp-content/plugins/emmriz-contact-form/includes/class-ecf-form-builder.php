<?php
/**
 * Handles form creation, storage, and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Form_Builder {
    
    private static $instance = null;
    private $forms;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->forms = array();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Load existing forms
        $this->load_forms();
    }
    
    /**
     * Load all forms from database
     */
    private function load_forms() {
        $forms = get_posts(array(
            'post_type' => 'ecf_form',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        foreach ($forms as $form) {
            $form_data = get_post_meta($form->ID, '_ecf_form_data', true);
            $this->forms[$form->ID] = array(
                'id' => $form->ID,
                'title' => $form->post_title,
                'data' => $form_data ? $form_data : array(),
                'settings' => get_post_meta($form->ID, '_ecf_form_settings', true)
            );
        }
    }
    
    /**
     * Create a new form
     */
    public function create_form($title = 'New Form') {
        $form_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'ecf_form',
            'post_status' => 'publish'
        ));
        
        if ($form_id && !is_wp_error($form_id)) {
            // Set default form structure
            $default_data = array(
                'fields' => array(),
                'settings' => array(
                    'notification_email' => get_option('admin_email'),
                    'success_message' => __('Thank you for your message. We will get back to you soon.', 'emmriz-contact-form'),
                    'error_message' => __('There was an error sending your message. Please try again.', 'emmriz-contact-form')
                )
            );
            
            update_post_meta($form_id, '_ecf_form_data', $default_data);
            $this->load_forms(); // Reload forms
            
            return $form_id;
        }
        
        return false;
    }
    
    /**
     * Update form data
     */
    public function update_form($form_id, $form_data) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'ecf_save_form_' . $form_id)) {
            return false;
        }
        
        $form_data = $this->sanitize_form_data($form_data);
        $result = update_post_meta($form_id, '_ecf_form_data', $form_data);
        
        if ($result) {
            $this->forms[$form_id]['data'] = $form_data;
        }
        
        return $result;
    }
    
    /**
     * Sanitize form data before saving
     */
    private function sanitize_form_data($data) {
        $sanitized = array();
        
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $field) {
                $sanitized['fields'][] = $this->sanitize_field($field);
            }
        }
        
        if (isset($data['settings']) && is_array($data['settings'])) {
            $sanitized['settings'] = $this->sanitize_settings($data['settings']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize individual field
     */
    private function sanitize_field($field) {
        $sanitized = array();
        
        $sanitized['type'] = sanitize_text_field($field['type']);
        $sanitized['id'] = sanitize_text_field($field['id']);
        $sanitized['label'] = sanitize_text_field($field['label']);
        $sanitized['placeholder'] = sanitize_text_field($field['placeholder']);
        $sanitized['required'] = (bool) $field['required'];
        $sanitized['className'] = sanitize_html_class($field['className']);
        
        return $sanitized;
    }
    
    /**
     * Sanitize form settings
     */
    private function sanitize_settings($settings) {
        $sanitized = array();
        
        $sanitized['notification_email'] = sanitize_email($settings['notification_email']);
        $sanitized['success_message'] = wp_kses_post($settings['success_message']);
        $sanitized['error_message'] = wp_kses_post($settings['error_message']);
        
        return $sanitized;
    }
    
    /**
     * Get form by ID
     */
    public function get_form($form_id) {
        return isset($this->forms[$form_id]) ? $this->forms[$form_id] : false;
    }
    
    /**
     * Get all forms
     */
    public function get_forms() {
        return $this->forms;
    }
    
    /**
     * Delete a form
     */
    public function delete_form($form_id) {
        $result = wp_delete_post($form_id, true);
        
        if ($result) {
            unset($this->forms[$form_id]);
        }
        
        return $result;
    }
    
    /**
     * Render form HTML for frontend
     */
    public function render_form($form_id) {
        $form = $this->get_form($form_id);
        
        if (!$form) {
            return '<p>' . __('Form not found.', 'emmriz-contact-form') . '</p>';
        }
        
        ob_start();
        include ECF_PLUGIN_PATH . 'templates/form-default.php';
        return ob_get_clean();
    }
}
?>