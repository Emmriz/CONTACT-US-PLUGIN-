<?php
/**
 * Default Form Template for Frontend Display
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_builder = ECF_Form_Builder::get_instance();
$form_data = $form['data'];
?>
<div class="emmriz-contact-form" id="ecf-form-<?php echo esc_attr($form['id']); ?>" data-form-id="<?php echo esc_attr($form['id']); ?>">
    
    <!-- Messages Container -->
    <div class="ecf-messages">
        <?php if ($submission_result): ?>
            <?php if ($submission_result['success']): ?>
                <div class="ecf-alert ecf-alert-success">
                    <?php echo wp_kses_post($submission_result['message']); ?>
                </div>
            <?php else: ?>
                <div class="ecf-alert ecf-alert-error">
                    <?php echo wp_kses_post($submission_result['message']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <form class="ecf-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('ecf_form_submission', 'ecf_nonce'); ?>
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form['id']); ?>">
        
        <!-- Honeypot Spam Protection -->
        <div class="ecf-honeypot">
            <label for="ecf_honeypot_<?php echo esc_attr($form['id']); ?>">
                <?php _e('Leave this field empty', 'emmriz-contact-form'); ?>
            </label>
            <input type="text" id="ecf_honeypot_<?php echo esc_attr($form['id']); ?>" name="ecf_honeypot" value="" autocomplete="off">
        </div>

        <!-- Form Fields -->
        <?php foreach ($form_data['fields'] as $field): ?>
            <?php if ($field['type'] !== 'submit'): ?>
                <div class="ecf-field-group">
                    <label for="<?php echo esc_attr($field['id']); ?>" class="ecf-field-label">
                        <?php echo esc_html($field['label']); ?>
                        <?php if ($field['required']): ?>
                            <span class="ecf-field-required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php echo ECF_Template_Helper::render_form_field($field, $form['id']); ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Submit Button -->
        <?php 
        $submit_field = array_filter($form_data['fields'], function($field) {
            return $field['type'] === 'submit';
        });
        $submit_field = reset($submit_field);
        ?>
        <?php if ($submit_field): ?>
            <div class="ecf-field-group ecf-submit-group">
                <button type="submit" name="ecf_submit" class="ecf-submit-btn">
                    <?php echo esc_html($submit_field['label']); ?>
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>