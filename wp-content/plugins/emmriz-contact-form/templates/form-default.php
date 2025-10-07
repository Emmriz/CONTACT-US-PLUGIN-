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
                    
                    <?php echo $this->render_form_field($field, $form['id']); ?>
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

<?php
/**
 * Render form field for frontend
 */
function render_form_field($field, $form_id) {
    $field_id = esc_attr($field['id']);
    $field_name = esc_attr($field['id']);
    $placeholder = esc_attr($field['placeholder']);
    $required = $field['required'] ? 'required' : '';
    $class = 'ecf-field-' . $field['type'];
    
    switch ($field['type']) {
        case 'text':
        case 'email':
            return sprintf(
                '<input type="%s" id="%s" name="%s" class="%s" placeholder="%s" %s>',
                esc_attr($field['type']),
                $field_id,
                $field_name,
                $class,
                $placeholder,
                $required
            );
        
        case 'textarea':
            return sprintf(
                '<textarea id="%s" name="%s" class="%s" placeholder="%s" %s rows="4"></textarea>',
                $field_id,
                $field_name,
                $class,
                $placeholder,
                $required
            );
        
        case 'select':
            $options = isset($field['options']) ? $field['options'] : array();
            $options_html = '<option value="">' . $placeholder . '</option>';
            
            foreach ($options as $option) {
                $options_html .= sprintf(
                    '<option value="%s">%s</option>',
                    esc_attr($option),
                    esc_html($option)
                );
            }
            
            return sprintf(
                '<select id="%s" name="%s" class="%s" %s>%s</select>',
                $field_id,
                $field_name,
                $class,
                $required,
                $options_html
            );
        
        case 'checkbox':
        case 'radio':
            $options = isset($field['options']) ? $field['options'] : array();
            $wrapper_class = 'ecf-' . $field['type'] . '-group';
            $options_html = '';
            
            foreach ($options as $index => $option) {
                $option_id = $field_id . '_' . $index;
                $options_html .= sprintf(
                    '<div class="ecf-%s-item">
                        <input type="%s" id="%s" name="%s[]" value="%s">
                        <label for="%s">%s</label>
                    </div>',
                    $field['type'],
                    $field['type'],
                    $option_id,
                    $field_name,
                    esc_attr($option),
                    $option_id,
                    esc_html($option)
                );
            }
            
            return sprintf('<div class="%s">%s</div>', $wrapper_class, $options_html);
        
        case 'number':
            return sprintf(
                '<input type="number" id="%s" name="%s" class="%s" placeholder="%s" %s>',
                $field_id,
                $field_name,
                $class,
                $placeholder,
                $required
            );
        
        default:
            return sprintf(
                '<input type="text" id="%s" name="%s" class="%s" placeholder="%s" %s>',
                $field_id,
                $field_name,
                $class,
                $placeholder,
                $required
            );
    }
}
?>