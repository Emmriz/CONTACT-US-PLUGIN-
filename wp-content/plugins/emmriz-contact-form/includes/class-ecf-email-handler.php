<?php
/**
 * Handles email notifications for form submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Email_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // No hooks needed for now
    }
    
    /**
     * Send email notification for form submission
     */
    public function send_notification($form_id, $form_data) {
        $form_builder = ECF_Form_Builder::get_instance();
        $form = $form_builder->get_form($form_id);
        
        if (!$form || !isset($form['data']['settings']['notification_email'])) {
            return false;
        }
        
        $to = $form['data']['settings']['notification_email'];
        $subject = $this->get_email_subject($form, $form_data);
        $message = $this->get_email_message($form, $form_data);
        $headers = $this->get_email_headers($form_data);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Generate email subject
     */
    private function get_email_subject($form, $form_data) {
        $subject = sprintf(__('New form submission from %s', 'emmriz-contact-form'), get_bloginfo('name'));
        
        // Try to get subject from form data if available
        if (isset($form_data['subject'])) {
            $subject = sanitize_text_field($form_data['subject']);
        } elseif (isset($form_data['email'])) {
            $subject = sprintf(__('New message from %s', 'emmriz-contact-form'), sanitize_email($form_data['email']));
        }
        
        return apply_filters('ecf_email_subject', $subject, $form, $form_data);
    }
    
    /**
     * Generate email message
     */
    private function get_email_message($form, $form_data) {
        $message = "You have received a new form submission:\n\n";
        $message .= "Form: " . $form['title'] . "\n";
        $message .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Add form fields data
        foreach ($form['data']['fields'] as $field) {
            if ($field['type'] === 'submit') continue;
            
            $field_value = isset($form_data[$field['id']]) ? $form_data[$field['id']] : '';
            $message .= $field['label'] . ": " . $this->format_field_value($field_value) . "\n";
        }
        
        // Add technical details
        $message .= "\n---\n";
        $message .= "IP Address: " . $this->get_client_ip() . "\n";
        $message .= "Website: " . get_bloginfo('url') . "\n";
        
        return apply_filters('ecf_email_message', $message, $form, $form_data);
    }
    
    /**
     * Format field value for email
     */
    private function format_field_value($value) {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        return $value;
    }
    
    /**
     * Get email headers
     */
    private function get_email_headers($form_data) {
        $headers = array();
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        
        // Set From header if email field exists
        if (isset($form_data['email']) && is_email($form_data['email'])) {
            $headers[] = 'Reply-To: ' . sanitize_email($form_data['email']);
        }
        
        return apply_filters('ecf_email_headers', $headers, $form_data);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'Unknown';
    }
}
?>