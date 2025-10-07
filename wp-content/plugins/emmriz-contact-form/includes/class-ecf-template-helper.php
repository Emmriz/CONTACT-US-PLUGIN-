<?php
/**
 * Template Helper Class for Emmriz Contact Form
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECF_Template_Helper {
    
    /**
     * Render form field for frontend
     */
    public static function render_form_field($field, $form_id) {
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
    
    /**
     * Render field preview for builder
     */
    public static function render_field_preview($field) {
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
    
    /**
     * Get field label from field ID
     */
    public static function get_field_label($field_id, $form_fields) {
        foreach ($form_fields as $field) {
            if ($field['id'] === $field_id) {
                return $field['label'];
            }
        }
        return ucfirst(str_replace('_', ' ', $field_id));
    }
}