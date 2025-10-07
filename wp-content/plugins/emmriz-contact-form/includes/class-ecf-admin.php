<?php
/**
 * Handles admin interface and menus
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('manage_ecf_form_posts_custom_column', array($this, 'manage_custom_columns'), 10, 2);
        add_filter('manage_ecf_form_posts_columns', array($this, 'add_custom_columns'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('Emmriz Contact Form', 'emmriz-contact-form'),
            __('Contact Forms', 'emmriz-contact-form'),
            'manage_options',
            'edit.php?post_type=ecf_form',
            '',
            'dashicons-email',
            30
        );
        
        // All Forms submenu
        add_submenu_page(
            'edit.php?post_type=ecf_form',
            __('All Forms', 'emmriz-contact-form'),
            __('All Forms', 'emmriz-contact-form'),
            'manage_options',
            'edit.php?post_type=ecf_form'
        );
        
        // Add New submenu
        add_submenu_page(
            'edit.php?post_type=ecf_form',
            __('Add New Form', 'emmriz-contact-form'),
            __('Add New', 'emmriz-contact-form'),
            'manage_options',
            'ecf-builder',
            array(ECF_Drag_Drop_Builder::get_instance(), 'render_builder_page')
        );
        
        // Submissions submenu
        add_submenu_page(
            'edit.php?post_type=ecf_form',
            __('Form Submissions', 'emmriz-contact-form'),
            __('Submissions', 'emmriz-contact-form'),
            'manage_options',
            'ecf-submissions',
            array($this, 'render_submissions_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'edit.php?post_type=ecf_form',
            __('Settings', 'emmriz-contact-form'),
            __('Settings', 'emmriz-contact-form'),
            'manage_options',
            'ecf-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Remove Add New from post type since we have custom builder
        global $submenu;
        if (isset($submenu['edit.php?post_type=ecf_form'])) {
            foreach ($submenu['edit.php?post_type=ecf_form'] as $key => $item) {
                if ($item[2] === 'post-new.php?post_type=ecf_form') {
                    unset($submenu['edit.php?post_type=ecf_form'][$key]);
                    break;
                }
            }
        }
    }
    
    /**
     * Add custom columns to forms list
     */
    public function add_custom_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'shortcode' => __('Shortcode', 'emmriz-contact-form'),
            'submissions' => __('Submissions', 'emmriz-contact-form'),
            'date' => $columns['date']
        );
        
        return $new_columns;
    }
    
    /**
     * Manage custom columns content
     */
    public function manage_custom_columns($column, $post_id) {
        switch ($column) {
            case 'shortcode':
                echo '<code>[emmriz_contact_form id="' . $post_id . '"]</code>';
                break;
                
            case 'submissions':
                $count = ECF_Submissions::get_instance()->get_submission_count($post_id);
                $unread = ECF_Submissions::get_instance()->get_unread_count($post_id);
                
                if ($count > 0) {
                    $url = admin_url('edit.php?post_type=ecf_form&page=ecf-submissions&form_id=' . $post_id);
                    echo '<a href="' . esc_url($url) . '">' . $count . '</a>';
                    if ($unread > 0) {
                        echo ' <span class="ecf-unread-count">(' . $unread . ' ' . __('unread', 'emmriz-contact-form') . ')</span>';
                    }
                } else {
                    echo '0';
                }
                break;
        }
    }
    
    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if ($form_id) {
            $this->render_form_submissions($form_id);
        } else {
            $this->render_all_submissions();
        }
    }
    
    /**
     * Render submissions for a specific form
     */
    private function render_form_submissions($form_id) {
        $form_builder = ECF_Form_Builder::get_instance();
        $form = $form_builder->get_form($form_id);
        $submissions = ECF_Submissions::get_instance()->get_submissions($form_id, 50);
        
        if (!$form) {
            echo '<div class="wrap"><h1>' . __('Form not found', 'emmriz-contact-form') . '</h1></div>';
            return;
        }
        
        include ECF_PLUGIN_PATH . 'templates/admin-submissions.php';
    }
    
    /**
     * Render all submissions across forms
     */
    private function render_all_submissions() {
        echo '<div class="wrap">';
        echo '<h1>' . __('All Form Submissions', 'emmriz-contact-form') . '</h1>';
        echo '<p>' . __('Select a form from the list above to view its submissions.', 'emmriz-contact-form') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Emmriz Contact Form Settings', 'emmriz-contact-form') . '</h1>';
        echo '<p>' . __('Settings page coming soon.', 'emmriz-contact-form') . '</p>';
        echo '</div>';
    }
}
?>