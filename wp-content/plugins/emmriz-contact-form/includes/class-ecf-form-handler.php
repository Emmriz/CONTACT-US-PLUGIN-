<?php
/**
 * Handles form submission, validation, and processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Form_Handler {
    
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
        add_action('wp_ajax_ecf_submit_form', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_ecf_submit_form', array($this, 'handle_ajax_submission'));
        add_action('wp_loaded', array($this, 'handle_regular_submission'));
    }
    
    /**
     * Handle AJAX form submission
     */
    public function handle_ajax_submission() {
        check_ajax_referer('ecf_ajax_nonce', 'nonce');
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        
        $result = $this->process_submission($form_id, $form_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle regular form submission
     */
    public function handle_regular_submission() {
        if (!isset($_POST['ecf_submit']) || !isset($_POST['form_id'])) {
            return;
        }
        
        $form_id = intval($_POST['form_id']);
        $form_data = $_POST;
        
        $result = $this->process_submission($form_id, $form_data);
        
        // Store result in session for display
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['ecf_submission_result'] = $result;
        
        // Redirect back to form page
        $referer = wp_get_referer();
        if ($referer) {
            wp_redirect($referer);
            exit;
        }
    }
    
    /**
     * Process form submission
     */
    private function process_submission($form_id, $submitted_data) {
        $form_builder = ECF_Form_Builder::get_instance();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            return array(
                'success' => false,
                'message' => __('Form not found.', 'emmriz-contact-form')
            );
        }
        
        // Validate submission
        $validation_result = $this->validate_submission($form, $submitted_data);
        
        if (!$validation_result['valid']) {
            return array(
                'success' => false,
                'message' => $validation_result['message'],
                'errors' => $validation_result['errors']
            );
        }
        
        // Process the submission
        $submission_id = ECF_Submissions::get_instance()->store_submission($form_id, $submitted_data);
        
        // Send email notification
        $email_sent = ECF_Email_Handler::get_instance()->send_notification($form_id, $submitted_data);
        
        return array(
            'success' => true,
            'message' => $form['data']['settings']['success_message'],
            'submission_id' => $submission_id,
            'email_sent' => $email_sent
        );
    }
    
    /**
     * Validate form submission
     */
    private function validate_submission($form, $data) {
        $errors = array();
        
        foreach ($form['data']['fields'] as $field) {
            $field_id = $field['id'];
            $field_value = isset($data[$field_id]) ? $data[$field_id] : '';
            
            // Check required fields
            if ($field['required'] && empty($field_value)) {
                $errors[$field_id] = sprintf(__('%s is required.', 'emmriz-contact-form'), $field['label']);
                continue;
            }
            
            // Field type validation
            switch ($field['type']) {
                case 'email':
                    if (!empty($field_value) && !is_email($field_value)) {
                        $errors[$field_id] = __('Please enter a valid email address.', 'emmriz-contact-form');
                    }
                    break;
                    
                case 'number':
                    if (!empty($field_value) && !is_numeric($field_value)) {
                        $errors[$field_id] = __('Please enter a valid number.', 'emmriz-contact-form');
                    }
                    break;
            }
        }
        
        // Honeypot spam protection
        if (isset($data['ecf_honeypot']) && !empty($data['ecf_honeypot'])) {
            $errors['spam'] = __('Spam detected.', 'emmriz-contact-form');
        }
        
        if (!empty($errors)) {
            return array(
                'valid' => false,
                'message' => __('Please correct the errors below.', 'emmriz-contact-form'),
                'errors' => $errors
            );
        }
        
        return array('valid' => true);
    }
}
?>