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

        // ✅ Add AJAX handlers for saving and creating forms
        add_action('wp_ajax_ecf_save_form', array($this, 'ajax_save_form'));
        add_action('wp_ajax_ecf_create_form', array($this, 'ajax_create_form'));
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
            'post_title' => sanitize_text_field($title),
            'post_type' => 'ecf_form',
            'post_status' => 'publish'
        ));

        if ($form_id && !is_wp_error($form_id)) {
            $default_data = array(
                'fields' => array(),
                'settings' => array(
                    'notification_email' => get_option('admin_email'),
                    'success_message' => __('Thank you for your message. We will get back to you soon.', 'emmriz-contact-form'),
                    'error_message' => __('There was an error sending your message. Please try again.', 'emmriz-contact-form')
                )
            );

            update_post_meta($form_id, '_ecf_form_data', $default_data);
            $this->load_forms();

            return $form_id;
        }

        return false;
    }

    /**
     * Update form data
     */
    public function update_form($form_id, $form_data, $form_title = '') {
        if (empty($form_id) || !get_post($form_id)) {
            return new WP_Error('invalid_id', 'Invalid form ID.');
        }

        // ✅ Nonce verification is handled in AJAX
        $form_data = $this->sanitize_form_data($form_data);
        update_post_meta($form_id, '_ecf_form_data', $form_data);

        if (!empty($form_title)) {
            wp_update_post(array(
                'ID' => $form_id,
                'post_title' => sanitize_text_field($form_title)
            ));
        }

        $this->forms[$form_id]['data'] = $form_data;

        return true;
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

    private function sanitize_field($field) {
        $sanitized = array();

        $sanitized['type'] = sanitize_text_field($field['type']);
        $sanitized['id'] = sanitize_text_field($field['id']);
        $sanitized['label'] = sanitize_text_field($field['label']);
        $sanitized['placeholder'] = sanitize_text_field($field['placeholder']);
        $sanitized['required'] = !empty($field['required']);
        $sanitized['className'] = sanitize_html_class($field['className']);

        return $sanitized;
    }

    private function sanitize_settings($settings) {
        $sanitized = array();

        $sanitized['notification_email'] = sanitize_email($settings['notification_email']);
        $sanitized['success_message'] = wp_kses_post($settings['success_message']);
        $sanitized['error_message'] = wp_kses_post($settings['error_message']);

        return $sanitized;
    }

    public function get_form($form_id) {
        return isset($this->forms[$form_id]) ? $this->forms[$form_id] : false;
    }

    public function get_forms() {
        return $this->forms;
    }

    public function delete_form($form_id) {
        $result = wp_delete_post($form_id, true);

        if ($result) {
            unset($this->forms[$form_id]);
        }

        return $result;
    }

    public function render_form($form_id) {
        $form = $this->get_form($form_id);

        if (!$form) {
            return '<p>' . __('Form not found.', 'emmriz-contact-form') . '</p>';
        }

        ob_start();
        include ECF_PLUGIN_PATH . 'templates/form-default.php';
        return ob_get_clean();
    }

    /* ---------------------------------------------------------------------
     * ✅ NEW: AJAX - Save Form + Create Form (with Title)
     * -------------------------------------------------------------------*/

    public function ajax_save_form() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');

        $form_id = intval($_POST['form_id']);
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        $form_title = isset($_POST['form_title']) ? sanitize_text_field($_POST['form_title']) : '';

        if (empty($form_id)) {
            wp_send_json_error('Invalid form ID.');
        }

        $result = $this->update_form($form_id, $form_data, $form_title);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array('message' => 'Form saved successfully!'));
    }

    public function ajax_create_form() {
        check_ajax_referer('ecf_builder_nonce', 'nonce');

        $title = isset($_POST['form_title']) ? sanitize_text_field($_POST['form_title']) : 'New Form';
        $form_id = $this->create_form($title);

        if ($form_id) {
            wp_send_json_success(array('form_id' => $form_id, 'title' => $title));
        } else {
            wp_send_json_error('Error creating form.');
        }
    }
}
?>
