<?php
/**
 * Handles AJAX requests for the form builder and frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Ajax {
    
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
        // Builder AJAX actions
        add_action('wp_ajax_ecf_save_form', array($this, 'save_form'));
        add_action('wp_ajax_ecf_add_field', array($this, 'add_field'));
        add_action('wp_ajax_ecf_delete_field', array($this, 'delete_field'));
        add_action('wp_ajax_ecf_duplicate_field', array($this, 'duplicate_field'));
        
        // Frontend AJAX actions
        add_action('wp_ajax_ecf_get_form_preview', array($this, 'get_form_preview'));
        add_action('wp_ajax_nopriv_ecf_get_form_preview', array($this, 'get_form_preview'));
    }
    
    /**
     * Save form data from builder
     */
    public function save_form() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'emmriz-contact-form')));
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        
        if (!$form_id) {
            wp_send_json_error(array('message' => __('Invalid form ID.', 'emmriz-contact-form')));
        }
        
        $form_builder = ECF_Form_Builder::get_instance();
        $result = $form_builder->update_form($form_id, $form_data);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Form saved successfully.', 'emmriz-contact-form')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save form.', 'emmriz-contact-form')));
        }
    }
    
    /**
     * Add new field to form
     */
    public function add_field() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'emmriz-contact-form')));
        }
        
        $field_type = isset($_POST['field_type']) ? sanitize_text_field($_POST['field_type']) : '';
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$field_type || !$form_id) {
            wp_send_json_error(array('message' => __('Missing required data.', 'emmriz-contact-form')));
        }
        
        $drag_drop_builder = ECF_Drag_Drop_Builder::get_instance();
        $field_types = $drag_drop_builder->get_field_types();
        
        if (!isset($field_types[$field_type])) {
            wp_send_json_error(array('message' => __('Invalid field type.', 'emmriz-contact-form')));
        }
        
        $field_data = $field_types[$field_type]['defaults'];
        $field_data['type'] = $field_type;
        $field_data['id'] = $this->generate_field_id($field_type);
        $field_data['className'] = '';
        
        wp_send_json_success(array('field' => $field_data));
    }
    
    /**
     * Generate unique field ID
     */
    private function generate_field_id($field_type) {
        return $field_type . '_' . uniqid();
    }
    
    /**
     * Delete field from form
     */
    public function delete_field() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'emmriz-contact-form')));
        }
        
        wp_send_json_success(array('message' => __('Field deleted.', 'emmriz-contact-form')));
    }
    
    /**
     * Duplicate field in form
     */
    public function duplicate_field() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'emmriz-contact-form')));
        }
        
        $field_data = isset($_POST['field_data']) ? $_POST['field_data'] : array();
        
        if (empty($field_data)) {
            wp_send_json_error(array('message' => __('No field data provided.', 'emmriz-contact-form')));
        }
        
        // Create duplicate with new ID
        $duplicate_field = $field_data;
        $duplicate_field['id'] = $this->generate_field_id($field_data['type']);
        $duplicate_field['label'] = $field_data['label'] . ' (Copy)';
        
        wp_send_json_success(array('field' => $duplicate_field));
    }
    
    /**
     * Get form preview for real-time preview in builder
     */
    public function get_form_preview() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');
        
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        
        if (empty($form_data)) {
            wp_send_json_error(array('message' => __('No form data provided.', 'emmriz-contact-form')));
        }
        
        // Simulate form object for preview
        $form = array(
            'id' => 0,
            'title' => 'Preview',
            'data' => $form_data
        );
        
        ob_start();
        include ECF_PLUGIN_PATH . 'templates/form-default.php';
        $preview_html = ob_get_clean();
        
        wp_send_json_success(array('preview' => $preview_html));
    }
}
?>