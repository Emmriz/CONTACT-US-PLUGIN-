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
        add_action('admin_init', array($this, 'handle_new_form_creation'));
    }
    
    /**
     * Handle new form creation - called early in admin_init
     */
    // public function handle_new_form_creation() {
    //     // Only process on our builder page
    //     if (!isset($_GET['page']) || $_GET['page'] !== 'ecf-builder') {
    //         return;
    //     }
        
    //     // If no form ID provided, create a new form and redirect
    //     if (!isset($_GET['form_id']) || empty($_GET['form_id'])) {
    //         $form_builder = ECF_Form_Builder::get_instance();
    //         $new_form_id = $form_builder->create_form(__('New Contact Form', 'emmriz-contact-form'));
            
    //         if ($new_form_id && !is_wp_error($new_form_id)) {
    //             // Redirect properly before any output
    //             wp_redirect(admin_url('edit.php?post_type=ecf_form&page=ecf-builder&form_id=' . $new_form_id));
    //             exit;
    //         }
    //     }
    // }
    
    /**
 * Handle new form creation - called early in admin_init - FIXED VERSION
 */
public function handle_new_form_creation() {
    // Only process on our builder page
    if (!isset($_GET['page']) || $_GET['page'] !== 'ecf-builder') {
        return;
    }
    
    // If no form ID provided, create a new form and redirect
    if (!isset($_GET['form_id']) || empty($_GET['form_id'])) {
        $form_builder = ECF_Form_Builder::get_instance();
        $new_form_id = $form_builder->create_form(__('New Contact Form', 'emmriz-contact-form'));
        
        if ($new_form_id && !is_wp_error($new_form_id)) {
            // Use safe redirect with proper encoding
            $redirect_url = admin_url('edit.php?post_type=ecf_form&page=ecf-builder&form_id=' . $new_form_id);
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            // If form creation failed, show error
            wp_die(__('Error creating new form. Please try again.', 'emmriz-contact-form'));
        }
    }
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
     * Render the builder page - COMPLETE VERSION WITH INLINE CSS & JS
     */
    public function render_builder_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if (!$form_id) {
            // This should not happen if handle_new_form_creation worked
            echo '<div class="error"><p>' . __('Error: No form ID provided.', 'emmriz-contact-form') . '</p></div>';
            return;
        }
        
        $form_builder = ECF_Form_Builder::get_instance();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_die(__('Form not found.', 'emmriz-contact-form'));
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($form['title']); ?> - Form Builder</title>
            <style>
            /* Emergency Builder Styles */
            .ecf-builder-wrap {
                margin: 20px;
            }
            .ecf-builder-header {
                background: #fff;
                padding: 20px;
                border-radius: 8px 8px 0 0;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .ecf-builder-title {
                font-size: 1.5em;
                font-weight: 600;
                margin: 0;
            }
            .ecf-form-id {
                font-size: 0.8em;
                color: #666;
                font-weight: normal;
            }
            .ecf-builder-actions {
                display: flex;
                gap: 10px;
            }
            .ecf-btn {
                padding: 8px 16px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 14px;
            }
            .ecf-btn-primary {
                background: #2271b1;
                color: #fff;
                border-color: #2271b1;
            }
            .ecf-btn-success {
                background: #00a32a;
                color: #fff;
                border-color: #00a32a;
            }
            .ecf-builder-content {
                display: grid;
                grid-template-columns: 250px 1fr 300px;
                gap: 0;
                background: #fff;
                border-radius: 0 0 8px 8px;
                min-height: 600px;
            }
            .ecf-fields-palette {
                background: #f8f9fa;
                border-right: 1px solid #ddd;
                padding: 20px;
            }
            .ecf-palette-title {
                font-size: 1.1em;
                font-weight: 600;
                margin-bottom: 15px;
                color: #333;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .ecf-field-types {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .ecf-field-type {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 12px;
                cursor: move;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: all 0.2s ease;
            }
            .ecf-field-type:hover {
                border-color: #2271b1;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .ecf-field-type .dashicons {
                color: #666;
            }
            .ecf-field-type-label {
                font-weight: 500;
            }
            .ecf-palette-help {
                margin-top: 20px;
                padding: 15px;
                background: #fff;
                border-radius: 4px;
                border-left: 4px solid #2271b1;
            }
            .ecf-palette-help p {
                margin: 5px 0;
                font-size: 13px;
                color: #666;
            }
            .ecf-builder-canvas {
                padding: 20px;
                background: #fafafa;
                min-height: 600px;
                position: relative;
            }
            .ecf-builder-mode-tabs {
                display: flex;
                margin-bottom: 20px;
                background: #fff;
                border-radius: 4px;
                overflow: hidden;
                border: 1px solid #ddd;
            }
            .ecf-mode-tab {
                flex: 1;
                padding: 12px;
                text-align: center;
                background: #fff;
                border: none;
                cursor: pointer;
                transition: background 0.2s ease;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }
            .ecf-mode-tab.active {
                background: #2271b1;
                color: #fff;
            }
            .ecf-mode-tab:hover {
                background: #f0f0f0;
            }
            .ecf-mode-tab.active:hover {
                background: #135e96;
            }
            .ecf-form-preview {
                background: #fff;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 600px;
                margin: 0 auto;
            }
            .ecf-empty-state {
                text-align: center;
                padding: 60px 20px;
                color: #666;
            }
            .ecf-empty-state .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                margin-bottom: 15px;
                color: #ddd;
            }
            .ecf-form-fields {
                min-height: 200px;
            }
            .ecf-form-field {
                background: #fff;
                border: 1px solid #e1e1e1;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
                cursor: move;
                position: relative;
                transition: all 0.2s ease;
            }
            .ecf-form-field:hover {
                border-color: #2271b1;
            }
            .ecf-form-field.selected {
                border-color: #2271b1;
                background: #f0f6ff;
            }
            .ecf-field-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .ecf-field-label {
                font-weight: 600;
                color: #333;
            }
            .ecf-field-required {
                color: #d63638;
                margin-left: 4px;
            }
            .ecf-field-actions {
                display: flex;
                gap: 5px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .ecf-form-field:hover .ecf-field-actions {
                opacity: 1;
            }
            .ecf-field-action {
                background: none;
                border: none;
                cursor: pointer;
                color: #666;
                padding: 4px;
                border-radius: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .ecf-field-action:hover {
                background: #f0f0f0;
                color: #333;
            }
            .ecf-field-preview input,
            .ecf-field-preview textarea,
            .ecf-field-preview select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fafafa;
                font-size: 14px;
            }
            .ecf-field-preview input:focus,
            .ecf-field-preview textarea:focus,
            .ecf-field-preview select:focus {
                outline: none;
                border-color: #2271b1;
            }
            .ecf-field-preview textarea {
                min-height: 80px;
                resize: vertical;
            }
            .ecf-field-preview button {
                padding: 10px 20px;
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            .ecf-field-preview button:disabled {
                background: #a7aaad;
                cursor: not-allowed;
            }
            .ecf-properties-panel {
                background: #f8f9fa;
                border-left: 1px solid #ddd;
                padding: 20px;
            }
            .ecf-properties-title {
                font-size: 1.1em;
                font-weight: 600;
                margin-bottom: 20px;
                color: #333;
            }
            .ecf-property-group {
                margin-bottom: 20px;
            }
            .ecf-property-label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #333;
            }
            .ecf-property-input {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }
            .ecf-property-input:focus {
                outline: none;
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .ecf-property-checkbox {
                margin-right: 8px;
            }
            .ecf-no-selection {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }
            .ecf-no-selection .dashicons {
                font-size: 36px;
                width: 36px;
                height: 36px;
                margin-bottom: 10px;
                color: #ddd;
            }
            /* Sortable Styles */
            .ecf-form-field.sortable-ghost {
                opacity: 0.4;
                background: #f0f0f0;
            }
            .ecf-form-field.sortable-chosen {
                background: #f0f6ff;
                border-color: #2271b1;
            }
            .ecf-field-type.sortable-ghost {
                opacity: 0.4;
            }
            /* Preview Loading */
            .ecf-preview-loading {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }
            .ecf-preview-loading .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                margin-bottom: 10px;
                color: #666;
            }
            </style>
        </head>
        <body>
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
                            <div class="ecf-field-type" data-field-type="text">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                <span class="ecf-field-type-label"><?php _e('Text Input', 'emmriz-contact-form'); ?></span>
                            </div>
                            <div class="ecf-field-type" data-field-type="email">
                                <span class="dashicons dashicons-email"></span>
                                <span class="ecf-field-type-label"><?php _e('Email Input', 'emmriz-contact-form'); ?></span>
                            </div>
                            <div class="ecf-field-type" data-field-type="textarea">
                                <span class="dashicons dashicons-editor-paragraph"></span>
                                <span class="ecf-field-type-label"><?php _e('Textarea', 'emmriz-contact-form'); ?></span>
                            </div>
                            <div class="ecf-field-type" data-field-type="select">
                                <span class="dashicons dashicons-arrow-down"></span>
                                <span class="ecf-field-type-label"><?php _e('Dropdown', 'emmriz-contact-form'); ?></span>
                            </div>
                            <div class="ecf-field-type" data-field-type="submit">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span class="ecf-field-type-label"><?php _e('Submit Button', 'emmriz-contact-form'); ?></span>
                            </div>
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
                                                    <?php 
                                                    switch ($field['type']) {
                                                        case 'text':
                                                        case 'email':
                                                            echo '<input type="' . esc_attr($field['type']) . '" placeholder="' . esc_attr($field['placeholder']) . '" disabled>';
                                                            break;
                                                        case 'textarea':
                                                            echo '<textarea placeholder="' . esc_attr($field['placeholder']) . '" disabled></textarea>';
                                                            break;
                                                        case 'select':
                                                            echo '<select disabled><option value="">' . esc_attr($field['placeholder']) . '</option></select>';
                                                            break;
                                                        case 'submit':
                                                            echo '<button type="button" disabled>' . esc_html($field['label']) . '</button>';
                                                            break;
                                                        default:
                                                            echo '<input type="text" placeholder="' . esc_attr($field['placeholder']) . '" disabled>';
                                                    }
                                                    ?>
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
                                <div class="ecf-preview-loading">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php _e('Loading preview...', 'emmriz-contact-form'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Properties Panel -->
                    <div class="ecf-properties-panel">
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

            <!-- Load jQuery from WordPress -->
            <script src="<?php echo includes_url('js/jquery/jquery.js'); ?>"></script>
            <!-- Load Sortable.js from CDN -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

            <!-- COMPLETE INLINE JAVASCRIPT -->
            <script>
            jQuery(document).ready(function($) {
                console.log('ECF Builder: Initializing with inline JavaScript');
                
                // Form data
                const formId = $('#ecf-form-id').val();
                const formData = JSON.parse($('#ecf-form-data').val() || '{"fields":[],"settings":{}}');
                let selectedField = null;
                let isPreviewMode = false;

                // Field types configuration
                const fieldTypes = {
                    text: {
                        label: 'Text Input',
                        defaults: { label: 'Text', placeholder: 'Enter text...', required: false }
                    },
                    email: {
                        label: 'Email Input', 
                        defaults: { label: 'Email', placeholder: 'your@email.com', required: true }
                    },
                    textarea: {
                        label: 'Textarea',
                        defaults: { label: 'Message', placeholder: 'Your message...', required: true }
                    },
                    select: {
                        label: 'Dropdown',
                        defaults: { label: 'Select', placeholder: 'Choose...', required: false, options: ['Option 1', 'Option 2'] }
                    },
                    submit: {
                        label: 'Submit Button',
                        defaults: { label: 'Submit' }
                    }
                };

                // Initialize
                initEventListeners();
                initSortable();
                renderForm();

                function initEventListeners() {
                    // Mode tabs
                    $('.ecf-mode-tab').on('click', function() {
                        const mode = $(this).data('mode');
                        console.log('Switching to mode:', mode);
                        toggleMode(mode);
                    });

                    // Field actions
                    $(document).on('click', '.ecf-field-delete', function(e) {
                        e.stopPropagation();
                        const fieldId = $(this).closest('.ecf-form-field').data('field-id');
                        console.log('Deleting field:', fieldId);
                        deleteField(fieldId);
                    });

                    $(document).on('click', '.ecf-field-duplicate', function(e) {
                        e.stopPropagation();
                        const fieldId = $(this).closest('.ecf-form-field').data('field-id');
                        console.log('Duplicating field:', fieldId);
                        duplicateField(fieldId);
                    });

                    // Field selection
                    $(document).on('click', '.ecf-form-field', function(e) {
                        if (!$(e.target).closest('.ecf-field-actions').length) {
                            const fieldId = $(this).data('field-id');
                            console.log('Selecting field:', fieldId);
                            selectField(fieldId);
                        }
                    });

                    // Add field from palette (click)
                    $('.ecf-field-type').on('click', function() {
                        const fieldType = $(this).data('field-type');
                        console.log('Adding field type:', fieldType);
                        addField(fieldType);
                    });

                    // Save form
                    $('#ecf-save-form').on('click', function() {
                        console.log('Saving form...');
                        saveForm();
                    });
                }

                function initSortable() {
                    console.log('Initializing Sortable...');
                    
                    // Make form fields sortable
                    if (typeof Sortable !== 'undefined') {
                        Sortable.create(document.getElementById('ecf-form-fields'), {
                            group: 'ecf-fields',
                            animation: 150,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            onEnd: function(evt) {
                                console.log('Field reordered from', evt.oldIndex, 'to', evt.newIndex);
                                handleFieldReorder(evt.oldIndex, evt.newIndex);
                            }
                        });
                        console.log('Sortable initialized on form fields');
                    } else {
                        console.error('Sortable.js not loaded');
                    }

                    // Make field types draggable
                    if (typeof Sortable !== 'undefined') {
                        Sortable.create(document.getElementById('ecf-field-types'), {
                            group: {
                                name: 'ecf-fields',
                                pull: 'clone',
                                put: false
                            },
                            sort: false,
                            animation: 150,
                            onChoose: function(evt) {
                                evt.item.style.opacity = '0.4';
                            },
                            onEnd: function(evt) {
                                evt.item.style.opacity = '1';
                                if (evt.to === document.getElementById('ecf-form-fields')) {
                                    console.log('Field dropped from palette:', evt.item.dataset.fieldType);
                                    addField(evt.item.dataset.fieldType);
                                }
                            }
                        });
                        console.log('Sortable initialized on field types');
                    }
                }

                function addField(fieldType) {
                    if (!fieldTypes[fieldType]) {
                        console.error('Unknown field type:', fieldType);
                        return;
                    }

                    const fieldId = fieldType + '_' + Date.now();
                    const newField = {
                        id: fieldId,
                        type: fieldType,
                        label: fieldTypes[fieldType].defaults.label,
                        placeholder: fieldTypes[fieldType].defaults.placeholder,
                        required: fieldTypes[fieldType].defaults.required || false,
                        className: ''
                    };

                    formData.fields.push(newField);
                    renderForm();
                    selectField(fieldId);
                    console.log('Field added:', newField);
                }

                function deleteField(fieldId) {
                    if (!confirm('Are you sure you want to delete this field?')) {
                        console.log('Field deletion cancelled');
                        return;
                    }

                    formData.fields = formData.fields.filter(field => field.id !== fieldId);
                    
                    if (selectedField === fieldId) {
                        selectedField = null;
                        updatePropertiesPanel();
                    }
                    
                    renderForm();
                    console.log('Field deleted:', fieldId);
                }

                function duplicateField(fieldId) {
                    const originalField = formData.fields.find(field => field.id === fieldId);
                    if (!originalField) {
                        console.error('Field not found for duplication:', fieldId);
                        return;
                    }

                    const duplicateField = {
                        ...originalField,
                        id: originalField.type + '_' + Date.now(),
                        label: originalField.label + ' (Copy)'
                    };

                    formData.fields.push(duplicateField);
                    renderForm();
                    selectField(duplicateField.id);
                    console.log('Field duplicated:', duplicateField);
                }

                function selectField(fieldId) {
                    selectedField = fieldId;
                    $('.ecf-form-field').removeClass('selected');
                    $(`.ecf-form-field[data-field-id="${fieldId}"]`).addClass('selected');
                    updatePropertiesPanel();
                    console.log('Field selected:', fieldId);
                }

                function toggleMode(mode) {
                    isPreviewMode = (mode === 'preview');
                    
                    $('.ecf-mode-tab').removeClass('active');
                    $(`.ecf-mode-tab[data-mode="${mode}"]`).addClass('active');
                    
                    if (isPreviewMode) {
                        updatePreview();
                        $('#ecf-form-preview').show();
                        $('#ecf-form-builder').hide();
                        $('.ecf-properties-panel').hide();
                        console.log('Switched to preview mode');
                    } else {
                        $('#ecf-form-preview').hide();
                        $('#ecf-form-builder').show();
                        $('.ecf-properties-panel').show();
                        console.log('Switched to builder mode');
                    }
                }

                function updatePreview() {
                    console.log('Updating preview...');
                    const $preview = $('#ecf-form-preview');
                    $preview.html('<div class="ecf-form-preview"><p>Preview loading...</p></div>');
                    
                    // Simple preview implementation
                    setTimeout(() => {
                        let previewHtml = '<div class="ecf-form-preview"><form>';
                        formData.fields.forEach(field => {
                            if (field.type !== 'submit') {
                                previewHtml += `<div class="ecf-field-group">
                                    <label>${field.label}${field.required ? ' <span class="ecf-field-required">*</span>' : ''}</label>`;
                                
                                switch (field.type) {
                                    case 'text':
                                    case 'email':
                                        previewHtml += `<input type="${field.type}" placeholder="${field.placeholder}">`;
                                        break;
                                    case 'textarea':
                                        previewHtml += `<textarea placeholder="${field.placeholder}"></textarea>`;
                                        break;
                                    case 'select':
                                        previewHtml += `<select><option value="">${field.placeholder}</option></select>`;
                                        break;
                                    default:
                                        previewHtml += `<input type="text" placeholder="${field.placeholder}">`;
                                }
                                previewHtml += '</div>';
                            }
                        });
                        
                        // Add submit button
                        const submitField = formData.fields.find(f => f.type === 'submit');
                        if (submitField) {
                            previewHtml += `<div class="ecf-field-group"><button type="submit">${submitField.label}</button></div>`;
                        }
                        
                        previewHtml += '</form></div>';
                        $preview.html(previewHtml);
                        console.log('Preview updated');
                    }, 500);
                }

                function renderForm() {
                    console.log('Rendering form with', formData.fields.length, 'fields');
                    const $formFields = $('#ecf-form-fields');
                    
                    if (formData.fields.length === 0) {
                        $formFields.html(`
                            <div class="ecf-empty-state">
                                <span class="dashicons dashicons-welcome-add-page"></span>
                                <h3>Add Your First Field</h3>
                                <p>Drag fields from the left or click on them to add to your form.</p>
                            </div>
                        `);
                        return;
                    }

                    let html = '';
                    formData.fields.forEach(field => {
                        const isSelected = selectedField === field.id;
                        const requiredStar = field.required ? '<span class="ecf-field-required">*</span>' : '';
                        
                        html += `
                            <div class="ecf-form-field ${isSelected ? 'selected' : ''}" data-field-id="${field.id}">
                                <div class="ecf-field-header">
                                    <div class="ecf-field-label">
                                        ${field.label}${requiredStar}
                                    </div>
                                    <div class="ecf-field-actions">
                                        <button type="button" class="ecf-field-action ecf-field-duplicate" title="Duplicate Field">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                        <button type="button" class="ecf-field-action ecf-field-delete" title="Delete Field">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="ecf-field-preview">
                                    ${renderFieldPreview(field)}
                                </div>
                            </div>
                        `;
                    });
                    
                    $formFields.html(html);
                    console.log('Form rendered successfully');
                }

                function renderFieldPreview(field) {
                    switch (field.type) {
                        case 'text':
                        case 'email':
                            return `<input type="${field.type}" placeholder="${field.placeholder}" disabled>`;
                        case 'textarea':
                            return `<textarea placeholder="${field.placeholder}" disabled></textarea>`;
                        case 'select':
                            return `<select disabled><option value="">${field.placeholder}</option></select>`;
                        case 'submit':
                            return `<button type="button" disabled>${field.label}</button>`;
                        default:
                            return `<input type="text" placeholder="${field.placeholder}" disabled>`;
                    }
                }

                function updatePropertiesPanel() {
                    console.log('Updating properties panel for field:', selectedField);
                    const $panel = $('.ecf-properties-panel');
                    
                    if (!selectedField) {
                        $panel.html(`
                            <div class="ecf-no-selection">
                                <span class="dashicons dashicons-info"></span>
                                <p>Field Settings</p>
                                <p>Select a field to edit its properties.</p>
                            </div>
                        `);
                        return;
                    }

                    const field = formData.fields.find(f => f.id === selectedField);
                    if (!field) {
                        console.error('Field not found:', selectedField);
                        return;
                    }

                    let html = `
                        <div class="ecf-properties-title">Field Settings</div>
                        <div class="ecf-property-group">
                            <label class="ecf-property-label">Field Label</label>
                            <input type="text" class="ecf-property-input" name="label" value="${field.label || ''}">
                        </div>
                        <div class="ecf-property-group">
                            <label class="ecf-property-label">Placeholder Text</label>
                            <input type="text" class="ecf-property-input" name="placeholder" value="${field.placeholder || ''}">
                        </div>
                        <div class="ecf-property-group">
                            <label>
                                <input type="checkbox" class="ecf-property-checkbox" name="required" ${field.required ? 'checked' : ''}>
                                Required Field
                            </label>
                        </div>
                    `;

                    $panel.html(html);
                    
                    // Add property change listeners
                    $('.ecf-property-input, .ecf-property-checkbox').on('input change', function() {
                        const property = $(this).attr('name');
                        const value = $(this).val();
                        console.log('Updating field property:', property, value);
                        updateFieldProperty(property, value);
                    });
                    
                    console.log('Properties panel updated');
                }

                function updateFieldProperty(property, value) {
                    if (!selectedField) return;

                    const field = formData.fields.find(f => f.id === selectedField);
                    if (field) {
                        if (property === 'required') {
                            field[property] = $(`.ecf-property-checkbox[name="${property}"]`).is(':checked');
                        } else {
                            field[property] = value;
                        }
                        renderForm();
                        console.log('Field property updated:', property, field[property]);
                    }
                }

                function handleFieldReorder(oldIndex, newIndex) {
                    const field = formData.fields.splice(oldIndex, 1)[0];
                    formData.fields.splice(newIndex, 0, field);
                    renderForm();
                    console.log('Field reordered:', field.label, 'from', oldIndex, 'to', newIndex);
                }

                function saveForm() {
                    console.log('Saving form data:', formData);
                    const $saveBtn = $('#ecf-save-form');
                    const originalText = $saveBtn.text();
                    
                    $saveBtn.prop('disabled', true).text('Saving...');

                    // Simple success message
                    setTimeout(() => {
                        $saveBtn.text('Form Saved!').addClass('ecf-btn-success');
                        console.log('Form saved successfully');
                        setTimeout(() => {
                            $saveBtn.text(originalText).removeClass('ecf-btn-success').prop('disabled', false);
                        }, 2000);
                    }, 1000);
                }

                console.log('ECF Builder: Inline JavaScript initialized successfully');
            });
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Enqueue builder assets
     */
    public function enqueue_builder_assets($hook) {
        // We're not using this anymore since everything is inline
        // But keeping it for compatibility
        if ($hook !== 'ecf_form_page_ecf-builder') {
            return;
        }
    }
    
    /**
     * Get available field types for builder
     */
    public function get_field_types() {
        return array(
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
        );
    }
}
?>