<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('ecf_settings', array());
?>
<div class="wrap ecf-admin-wrap">
    <div class="ecf-admin-header">
        <h1><?php _e('Emmriz Contact Form Settings', 'emmriz-contact-form'); ?></h1>
    </div>

    <div class="ecf-settings-content">
        <form method="post" action="options.php">
            <?php settings_fields('ecf_settings_group'); ?>
            <?php do_settings_sections('ecf_settings_group'); ?>
            
            <div class="ecf-settings-sections">
                <!-- General Settings -->
                <div class="ecf-settings-section">
                    <h2><?php _e('General Settings', 'emmriz-contact-form'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ecf_default_email"><?php _e('Default Notification Email', 'emmriz-contact-form'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="ecf_default_email" name="ecf_settings[default_email]" 
                                       value="<?php echo esc_attr($settings['default_email'] ?? get_option('admin_email')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Default email address to receive form notifications.', 'emmriz-contact-form'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ecf_enable_ajax"><?php _e('Enable AJAX Submission', 'emmriz-contact-form'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="ecf_enable_ajax" name="ecf_settings[enable_ajax]" value="1" 
                                       <?php checked(($settings['enable_ajax'] ?? true), true); ?>>
                                <label for="ecf_enable_ajax"><?php _e('Use AJAX for form submissions (recommended)', 'emmriz-contact-form'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Spam Protection -->
                <div class="ecf-settings-section">
                    <h2><?php _e('Spam Protection', 'emmriz-contact-form'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Spam Protection Method', 'emmriz-contact-form'); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="ecf_settings[spam_protection]" value="honeypot" 
                                               <?php checked(($settings['spam_protection'] ?? 'honeypot'), 'honeypot'); ?>>
                                        <?php _e('Honeypot (Recommended)', 'emmriz-contact-form'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="radio" name="ecf_settings[spam_protection]" value="none" 
                                               <?php checked(($settings['spam_protection'] ?? 'honeypot'), 'none'); ?>>
                                        <?php _e('None', 'emmriz-contact-form'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="radio" name="ecf_settings[spam_protection]" value="recaptcha" 
                                               <?php checked(($settings['spam_protection'] ?? 'honeypot'), 'recaptcha'); ?> disabled>
                                        <?php _e('Google reCAPTCHA (Coming Soon)', 'emmriz-contact-form'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Data Management -->
                <div class="ecf-settings-section">
                    <h2><?php _e('Data Management', 'emmriz-contact-form'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ecf_delete_data"><?php _e('Delete Data on Uninstall', 'emmriz-contact-form'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="ecf_delete_data" name="ecf_settings[delete_data_on_uninstall]" value="1" 
                                       <?php checked(($settings['delete_data_on_uninstall'] ?? false), true); ?>>
                                <label for="ecf_delete_data">
                                    <?php _e('Delete all form data when plugin is uninstalled', 'emmriz-contact-form'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Warning: This will permanently delete all forms and submissions when you uninstall the plugin.', 'emmriz-contact-form'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Submission Retention', 'emmriz-contact-form'); ?>
                            </th>
                            <td>
                                <select name="ecf_settings[submission_retention]">
                                    <option value="forever" <?php selected(($settings['submission_retention'] ?? 'forever'), 'forever'); ?>>
                                        <?php _e('Keep forever', 'emmriz-contact-form'); ?>
                                    </option>
                                    <option value="30" <?php selected(($settings['submission_retention'] ?? 'forever'), '30'); ?>>
                                        <?php _e('30 days', 'emmriz-contact-form'); ?>
                                    </option>
                                    <option value="90" <?php selected(($settings['submission_retention'] ?? 'forever'), '90'); ?>>
                                        <?php _e('90 days', 'emmriz-contact-form'); ?>
                                    </option>
                                    <option value="365" <?php selected(($settings['submission_retention'] ?? 'forever'), '365'); ?>>
                                        <?php _e('1 year', 'emmriz-contact-form'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('How long to keep form submissions before automatic deletion.', 'emmriz-contact-form'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Email Settings -->
                <div class="ecf-settings-section">
                    <h2><?php _e('Email Settings', 'emmriz-contact-form'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ecf_email_from_name"><?php _e('From Name', 'emmriz-contact-form'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="ecf_email_from_name" name="ecf_settings[email_from_name]" 
                                       value="<?php echo esc_attr($settings['email_from_name'] ?? get_bloginfo('name')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ecf_email_from_address"><?php _e('From Email', 'emmriz-contact-form'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="ecf_email_from_address" name="ecf_settings[email_from_address]" 
                                       value="<?php echo esc_attr($settings['email_from_address'] ?? get_option('admin_email')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ecf_email_content_type"><?php _e('Email Content Type', 'emmriz-contact-form'); ?></label>
                            </th>
                            <td>
                                <select id="ecf_email_content_type" name="ecf_settings[email_content_type]">
                                    <option value="html" <?php selected(($settings['email_content_type'] ?? 'plain'), 'html'); ?>>
                                        <?php _e('HTML', 'emmriz-contact-form'); ?>
                                    </option>
                                    <option value="plain" <?php selected(($settings['email_content_type'] ?? 'plain'), 'plain'); ?>>
                                        <?php _e('Plain Text', 'emmriz-contact-form'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php submit_button(__('Save Settings', 'emmriz-contact-form')); ?>
        </form>
    </div>

    <!-- System Information -->
    <div class="ecf-settings-section">
        <h2><?php _e('System Information', 'emmriz-contact-form'); ?></h2>
        
        <table class="widefat striped">
            <tr>
                <td width="200"><strong><?php _e('Plugin Version', 'emmriz-contact-form'); ?></strong></td>
                <td><?php echo esc_html(ECF_VERSION); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('WordPress Version', 'emmriz-contact-form'); ?></strong></td>
                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('PHP Version', 'emmriz-contact-form'); ?></strong></td>
                <td><?php echo esc_html(phpversion()); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Total Forms', 'emmriz-contact-form'); ?></strong></td>
                <td>
                    <?php
                    $forms_count = wp_count_posts('ecf_form');
                    echo esc_html($forms_count->publish ?? 0);
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php _e('Total Submissions', 'emmriz-contact-form'); ?></strong></td>
                <td>
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'ecf_submissions';
                    $submissions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                    echo esc_html($submissions_count);
                    ?>
                </td>
            </tr>
        </table>
    </div>
</div>