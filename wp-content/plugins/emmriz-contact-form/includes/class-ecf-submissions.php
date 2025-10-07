<?php
/**
 * Handles form submissions storage and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Submissions {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ecf_submissions';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'create_table'));
        register_activation_hook(ECF_PLUGIN_BASENAME, array($this, 'create_table'));
    }
    
    /**
     * Create submissions table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            form_data longtext NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            submitted_at datetime NOT NULL,
            read_status tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY submitted_at (submitted_at),
            KEY read_status (read_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Store a form submission
     */
    public function store_submission($form_id, $form_data) {
        global $wpdb;
        
        // Remove honeypot and nonce fields
        $clean_data = array();
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'ecf_') !== 0 || $key === 'ecf_honeypot') {
                if ($key !== 'nonce' && $key !== '_wp_http_referer') {
                    $clean_data[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
                }
            }
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'form_id' => $form_id,
                'form_data' => json_encode($clean_data),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
                'submitted_at' => current_time('mysql'),
                'read_status' => 0
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
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
        
        return '0.0.0.0';
    }
    
    /**
     * Get submissions for a form
     */
    public function get_submissions($form_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE form_id = %d ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $form_id, $limit, $offset
            )
        );
    }
    
    /**
     * Get submission by ID
     */
    public function get_submission($submission_id) {
        global $wpdb;
        
        $submission = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $submission_id)
        );
        
        if ($submission) {
            $submission->form_data = json_decode($submission->form_data, true);
        }
        
        return $submission;
    }
    
    /**
     * Mark submission as read
     */
    public function mark_as_read($submission_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('read_status' => 1),
            array('id' => $submission_id),
            array('%d'),
            array('%d')
        );
    }
    
    /**
     * Delete submission
     */
    public function delete_submission($submission_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $submission_id),
            array('%d')
        );
    }
    
    /**
     * Get submission count for a form
     */
    public function get_submission_count($form_id) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE form_id = %d", $form_id)
        );
    }
    
    /**
     * Get unread submission count for a form
     */
    public function get_unread_count($form_id) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE form_id = %d AND read_status = 0", $form_id)
        );
    }
}
?>