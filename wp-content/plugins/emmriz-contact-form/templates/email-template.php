<?php
/**
 * Email Template for Form Notifications
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('New Form Submission', 'emmriz-contact-form'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .field-group { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .field-label { font-weight: bold; color: #2271b1; }
        .field-value { margin-top: 5px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><?php _e('New Form Submission', 'emmriz-contact-form'); ?></h1>
            <p><?php echo get_bloginfo('name'); ?></p>
        </div>
        
        <div class="content">
            <h2><?php printf(__('Form: %s', 'emmriz-contact-form'), esc_html($form_title)); ?></h2>
            <p><strong><?php _e('Submitted:', 'emmriz-contact-form'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?></p>
            
            <h3><?php _e('Submission Details:', 'emmriz-contact-form'); ?></h3>
            
            <?php foreach ($form_fields as $field): ?>
                <?php if ($field['type'] !== 'submit' && isset($form_data[$field['id']]) && !empty($form_data[$field['id']])): ?>
                    <div class="field-group">
                        <div class="field-label"><?php echo esc_html($field['label']); ?></div>
                        <div class="field-value">
                            <?php
                            $value = $form_data[$field['id']];
                            if (is_array($value)) {
                                echo esc_html(implode(', ', $value));
                            } else {
                                echo nl2br(esc_html($value));
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p><strong><?php _e('Technical Details:', 'emmriz-contact-form'); ?></strong></p>
            <p><?php _e('IP Address:', 'emmriz-contact-form'); ?> <?php echo esc_html($ip_address); ?></p>
            <p><?php _e('User Agent:', 'emmriz-contact-form'); ?> <?php echo esc_html($user_agent); ?></p>
            <p><?php _e('Website:', 'emmriz-contact-form'); ?> <?php echo esc_url(home_url()); ?></p>
        </div>
    </div>
</body>
</html>