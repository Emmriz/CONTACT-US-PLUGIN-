<?php
/**
 * Drag & Drop Form Builder Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_builder = ECF_Form_Builder::get_instance();
$drag_drop_builder = ECF_Drag_Drop_Builder::get_instance();
$field_types = $drag_drop_builder->get_field_types();
?>
<div class="wrap ecf-builder-wrap">
    <div class="ecf-builder-header">
        <h1 class="ecf-builder-title">
            <?php echo esc_html($form['title']); ?>
            <span class="ecf-form-id">(ID: <?php echo esc_html($form_id); ?>)</span>
        </h1>
        <div class="ecf-builder-actions">
            <button type="button" id="ecf-save-form" class="button button-primary ecf-btn ecf-btn-primary">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Save Form', 'emmriz-contact-form'); ?>
            </button>
            <a href="<?php echo admin_url('edit.php?post_type=ecf_form'); ?>" class="button ecf-btn">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to Forms', 'emmriz-contact-form'); ?>
            </a>
        </div>
    </div>

    <div class="ecf-builder-content">
        <!-- Fields Palette -->
        <div class="ecf-fields-palette">
            <h3 class="ecf-palette-title">
                <span class="dashicons dashicons-menu"></span>
                <?php _e('Form Fields', 'emmriz-contact-form'); ?>
            </h3>
            <div id="ecf-field-types" class="ecf-field-types">
                <?php foreach ($field_types as $type => $field_type): ?>
                    <div class="ecf-field-type" data-field-type="<?php echo esc_attr($type); ?>">
                        <span class="dashicons <?php echo esc_attr($field_type['icon']); ?>"></span>
                        <span class="ecf-field-type-label"><?php echo esc_html($field_type['label']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ecf-palette-help">
                <p><strong><?php _e('How to use:', 'emmriz-contact-form'); ?></strong></p>
                <p><?php _e('Drag fields to the canvas or click to add them.', 'emmriz-contact-form'); ?></p>
            </div>
        </div>

        <!-- Builder Canvas -->
        <div class="ecf-builder-canvas">
            <div class="ecf-builder-mode-tabs">
                <button type="button" class="ecf-mode-tab active" data-mode="builder">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Builder', 'emmriz-contact-form'); ?>
                </button>
                <button type="button" class="ecf-mode-tab" data-mode="preview">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Preview', 'emmriz-contact-form'); ?>
                </button>
            </div>

            <!-- Builder View -->
            <div id="ecf-form-builder">
                <div class="ecf-form-preview">
                    <div id="ecf-form-fields" class="ecf-form-fields">
                        <!-- Fields will be rendered here by JavaScript -->
                        <?php if (empty($form['data']['fields'])): ?>
                            <div class="ecf-empty-state">
                                <span class="dashicons dashicons-welcome-add-page"></span>
                                <h3><?php _e('Add Your First Field', 'emmriz-contact-form'); ?></h3>
                                <p><?php _e('Drag fields from the left or click on them to add to your form.', 'emmriz-contact-form'); ?></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($form['data']['fields'] as $field): ?>
                                <div class="ecf-form-field" data-field-id="<?php echo esc_attr($field['id']); ?>">
                                    <div class="ecf-field-header">
                                        <div class="ecf-field-label">
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if ($field['required']): ?>
                                                <span class="ecf-field-required">*</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ecf-field-actions">
                                            <button type="button" class="ecf-field-action ecf-field-duplicate" title="<?php _e('Duplicate Field', 'emmriz-contact-form'); ?>">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </button>
                                            <button type="button" class="ecf-field-action ecf-field-delete" title="<?php _e('Delete Field', 'emmriz-contact-form'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="ecf-field-preview">
                                        <?php echo $this->render_field_preview($field); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Preview View -->
            <div id="ecf-form-preview" style="display: none;">
                <div class="ecf-form-preview">
                    <!-- Preview will be loaded via AJAX -->
                    <div class="ecf-preview-loading">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Loading preview...', 'emmriz-contact-form'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Panel -->
        <div class="ecf-properties-panel">
            <!-- Field properties will be loaded here by JavaScript -->
            <div class="ecf-no-selection">
                <span class="dashicons dashicons-info"></span>
                <p><?php _e('Field Settings', 'emmriz-contact-form'); ?></p>
                <p><?php _e('Select a field to edit its properties.', 'emmriz-contact-form'); ?></p>
            </div>
        </div>
    </div>

    <!-- Hidden form data for JavaScript -->
    <input type="hidden" id="ecf-form-id" value="<?php echo esc_attr($form_id); ?>">
    <input type="hidden" id="ecf-form-data" value="<?php echo esc_attr(wp_json_encode($form['data'])); ?>">
</div>

<?php
/**
 * Render field preview for builder
 */
function render_field_preview($field) {
    switch ($field['type']) {
        case 'text':
        case 'email':
            return sprintf(
                '<input type="%s" placeholder="%s" disabled>',
                esc_attr($field['type']),
                esc_attr($field['placeholder'])
            );
        
        case 'textarea':
            return sprintf(
                '<textarea placeholder="%s" disabled></textarea>',
                esc_attr($field['placeholder'])
            );
        
        case 'select':
            $options = isset($field['options']) ? $field['options'] : array('Option 1', 'Option 2');
            $options_html = '';
            foreach ($options as $option) {
                $options_html .= sprintf('<option>%s</option>', esc_html($option));
            }
            return sprintf(
                '<select disabled><option value="">%s</option>%s</select>',
                esc_attr($field['placeholder']),
                $options_html
            );
        
        case 'submit':
            return sprintf(
                '<button type="button" disabled>%s</button>',
                esc_html($field['label'])
            );
        
        default:
            return sprintf(
                '<input type="text" placeholder="%s" disabled>',
                esc_attr($field['placeholder'])
            );
    }
}
?>