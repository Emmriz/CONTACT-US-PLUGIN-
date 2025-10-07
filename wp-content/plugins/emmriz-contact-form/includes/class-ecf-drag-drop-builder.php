<?php
/**
 * Handles the drag & drop form builder interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Drag_Drop_Builder {
    
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
        add_action('admin_menu', array($this, 'add_builder_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_builder_assets'));
    }
    
    /**
     * Add builder page to admin menu
     */
    public function add_builder_page() {
        add_submenu_page(
            'edit.php?post_type=ecf_form',
            __('Form Builder', 'emmriz-contact-form'),
            __('Form Builder', 'emmriz-contact-form'),
            'manage_options',
            'ecf-builder',
            array($this, 'render_builder_page')
        );
    }
    
    /**
     * Render the builder page
     */
    public function render_builder_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $form_builder = ECF_Form_Builder::get_instance();
        
        // Create new form if no form ID provided
        if (!$form_id) {
            $form_id = $form_builder->create_form(__('New Contact Form', 'emmriz-contact-form'));
            if ($form_id) {
                wp_redirect(admin_url('edit.php?post_type=ecf_form&page=ecf-builder&form_id=' . $form_id));
                exit;
            }
        }
        
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_die(__('Form not found.', 'emmriz-contact-form'));
        }
        
        include ECF_PLUGIN_PATH . 'templates/form-builder.php';
    }
    
    /**
     * Enqueue builder assets
     */
    public function enqueue_builder_assets($hook) {
        if ($hook !== 'ecf_form_page_ecf-builder') {
            return;
        }
        
        wp_enqueue_style('ecf-builder');
        wp_enqueue_script('ecf-builder');
        wp_enqueue_script('ecf-admin');
    }
    
    /**
     * Get available field types for builder
     */
    public function get_field_types() {
        return apply_filters('ecf_available_field_types', array(
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
            'select' => array(
                'label' => __('Dropdown', 'emmriz-contact-form'),
                'icon' => 'dashicons-arrow-down',
                'defaults' => array(
                    'label' => __('Select', 'emmriz-contact-form'),
                    'options' => array('Option 1', 'Option 2'),
                    'required' => false
                )
            ),
            'submit' => array(
                'label' => __('Submit Button', 'emmriz-contact-form'),
                'icon' => 'dashicons-yes-alt',
                'defaults' => array(
                    'label' => __('Submit', 'emmriz-contact-form')
                )
            )
        ));
    }
}
?>