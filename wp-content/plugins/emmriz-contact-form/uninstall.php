<?php
/**
 * Uninstall Emmriz Contact Form
 *
 * @package Emmriz_Contact_Form
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we need to run uninstall (no multisite)
if (!is_multisite()) {
    emmriz_contact_form_uninstall();
} else {
    // Multisite uninstall
    global $wpdb;
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    // Skip main site if not included
    $original_blog_id = get_current_blog_id();
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        emmriz_contact_form_uninstall();
    }
    
    switch_to_blog($original_blog_id);
}

/**
 * Main uninstall function
 */
function emmriz_contact_form_uninstall() {
    global $wpdb;
    
    // Get plugin options
    $options = get_option('ecf_settings', array());
    $delete_data = isset($options['delete_data_on_uninstall']) ? $options['delete_data_on_uninstall'] : false;
    
    // If user opted to keep data, don't delete anything
    if (!$delete_data) {
        return;
    }
    
    // Delete custom post types (forms)
    $forms = get_posts(array(
        'post_type' => 'ecf_form',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    
    foreach ($forms as $form_id) {
        wp_delete_post($form_id, true);
    }
    
    // Delete submissions table
    $table_name = $wpdb->prefix . 'ecf_submissions';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    // Delete plugin options
    delete_option('ecf_settings');
    delete_option('ecf_version');
    delete_option('ecf_db_version');
    
    // Delete any transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%ecf_%'");
    
    // Clear scheduled hooks
    wp_clear_scheduled_hook('ecf_daily_cleanup');
    
    // Remove any custom capabilities
    emmriz_contact_form_remove_capabilities();
}

/**
 * Remove custom capabilities
 */
function emmriz_contact_form_remove_capabilities() {
    global $wp_roles;
    
    if (!class_exists('WP_Roles')) {
        return;
    }
    
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    
    $capabilities = array(
        'manage_emmriz_forms',
        'view_emmriz_submissions',
        'export_emmriz_submissions'
    );
    
    foreach ($wp_roles->roles as $role_name => $role_info) {
        $role = get_role($role_name);
        
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}
?>