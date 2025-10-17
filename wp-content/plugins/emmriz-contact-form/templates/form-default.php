<?php
/**
 * Default Form Template for Frontend Display
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get form data
$form_data = $form['data'];
$form_id = $form['id'];

// Get submission result
$form_handler = ECF_Form_Handler::get_instance();
$submission_result = $form_handler->get_submission_result();
?>
<div class="emmriz-contact-form" id="ecf-form-<?php echo esc_attr($form_id); ?>" data-form-id="<?php echo esc_attr($form_id); ?>">
    
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
                    <?php if (isset($submission_result['errors']) && is_array($submission_result['errors'])): ?>
                        <ul>
                            <?php foreach ($submission_result['errors'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <form class="ecf-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('ecf_form_submission', 'ecf_nonce'); ?>
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        
        <!-- Honeypot Spam Protection -->
        <div class="ecf-honeypot">
            <label for="ecf_honeypot_<?php echo esc_attr($form_id); ?>">
                <?php _e('Leave this field empty', 'emmriz-contact-form'); ?>
            </label>
            <input type="text" id="ecf_honeypot_<?php echo esc_attr($form_id); ?>" name="ecf_honeypot" value="" autocomplete="off">
        </div>

        <!-- Form Fields -->
        <?php if (!empty($form_data['fields'])): ?>
            <?php foreach ($form_data['fields'] as $field): ?>
                <?php if ($field['type'] !== 'submit'): ?>
                    <div class="ecf-field-group">
                        <label for="<?php echo esc_attr($field['id']); ?>" class="ecf-field-label">
                            <?php echo esc_html($field['label']); ?>
                            <?php if ($field['required']): ?>
                                <span class="ecf-field-required">*</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php echo ECF_Template_Helper::render_form_field($field, $form_id); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p><?php _e('No form fields configured.', 'emmriz-contact-form'); ?></p>
        <?php endif; ?>

        <!-- Submit Button -->
        <?php 
        $submit_field = null;
        if (!empty($form_data['fields'])) {
            foreach ($form_data['fields'] as $field) {
                if ($field['type'] === 'submit') {
                    $submit_field = $field;
                    break;
                }
            }
        }
        ?>
        <?php if ($submit_field): ?>
            <div class="ecf-field-group ecf-submit-group">
                <button type="submit" name="ecf_submit" class="ecf-submit-btn">
                    <?php echo esc_html($submit_field['label']); ?>
                </button>
            </div>
        <?php else: ?>
            <div class="ecf-field-group ecf-submit-group">
                <button type="submit" name="ecf_submit" class="ecf-submit-btn">
                    <?php _e('Submit', 'emmriz-contact-form'); ?>
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>