/* Emmriz Contact Form - Drag & Drop Builder */
(function($) {
    'use strict';

    class ECFBuilder {
        constructor() {
            this.currentFormId = ecf_builder.form_id || 0;
            this.formData = ecf_builder.form_data || { fields: [], settings: {} };
            this.selectedField = null;
            this.isPreviewMode = false;
            
            this.init();
        }

        init() {
            this.initSortable();
            this.initEventListeners();
            this.renderForm();
            this.updatePropertiesPanel();
        }

        initSortable() {
            // Make form fields sortable
            this.sortable = Sortable.create(document.getElementById('ecf-form-fields'), {
                group: 'ecf-fields',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: (evt) => {
                    this.handleFieldReorder(evt.oldIndex, evt.newIndex);
                }
            });

            // Make field types draggable
            Sortable.create(document.getElementById('ecf-field-types'), {
                group: {
                    name: 'ecf-fields',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150,
                onChoose: (evt) => {
                    evt.item.style.opacity = '0.4';
                },
                onEnd: (evt) => {
                    evt.item.style.opacity = '1';
                    if (evt.to === document.getElementById('ecf-form-fields')) {
                        this.addField(evt.item.dataset.fieldType);
                    }
                }
            });
        }

        initEventListeners() {
            // Mode tabs
            $('.ecf-mode-tab').on('click', (e) => {
                this.toggleMode(e.target.dataset.mode);
            });

            // Field actions
            $(document).on('click', '.ecf-field-delete', (e) => {
                e.stopPropagation();
                this.deleteField($(e.target).closest('.ecf-form-field').data('field-id'));
            });

            $(document).on('click', '.ecf-field-duplicate', (e) => {
                e.stopPropagation();
                this.duplicateField($(e.target).closest('.ecf-form-field').data('field-id'));
            });

            // Field selection
            $(document).on('click', '.ecf-form-field', (e) => {
                if (!$(e.target).closest('.ecf-field-actions').length) {
                    this.selectField($(e.currentTarget).data('field-id'));
                }
            });

            // Property changes
            $(document).on('input change', '.ecf-property-input, .ecf-property-checkbox', (e) => {
                this.updateFieldProperty(e.target.name, e.target.value);
            });

            // Save form
            $('#ecf-save-form').on('click', () => {
                this.saveForm();
            });

            // Add field from palette
            $('.ecf-field-type').on('click', (e) => {
                this.addField(e.currentTarget.dataset.fieldType);
            });
        }

        addField(fieldType) {
            $.ajax({
                url: ecf_builder.ajax_url,
                type: 'POST',
                data: {
                    action: 'ecf_add_field',
                    field_type: fieldType,
                    form_id: this.currentFormId,
                    nonce: ecf_builder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.formData.fields.push(response.data.field);
                        this.renderForm();
                        this.selectField(response.data.field.id);
                    }
                }
            });
        }

        deleteField(fieldId) {
            if (!confirm(ecf_builder.i18n.confirmDelete)) {
                return;
            }

            this.formData.fields = this.formData.fields.filter(field => field.id !== fieldId);
            
            if (this.selectedField === fieldId) {
                this.selectedField = null;
                this.updatePropertiesPanel();
            }
            
            this.renderForm();
        }

        duplicateField(fieldId) {
            const originalField = this.formData.fields.find(field => field.id === fieldId);
            if (!originalField) return;

            $.ajax({
                url: ecf_builder.ajax_url,
                type: 'POST',
                data: {
                    action: 'ecf_duplicate_field',
                    field_data: originalField,
                    nonce: ecf_builder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.formData.fields.push(response.data.field);
                        this.renderForm();
                        this.selectField(response.data.field.id);
                    }
                }
            });
        }

        selectField(fieldId) {
            this.selectedField = fieldId;
            $('.ecf-form-field').removeClass('selected');
            $(`.ecf-form-field[data-field-id="${fieldId}"]`).addClass('selected');
            this.updatePropertiesPanel();
        }

        updateFieldProperty(property, value) {
            if (!this.selectedField) return;

            const field = this.formData.fields.find(f => f.id === this.selectedField);
            if (field) {
                if (property === 'required') {
                    field[property] = !!value;
                } else {
                    field[property] = value;
                }
                this.renderForm();
            }
        }

        handleFieldReorder(oldIndex, newIndex) {
            const field = this.formData.fields.splice(oldIndex, 1)[0];
            this.formData.fields.splice(newIndex, 0, field);
            this.renderForm();
        }

        toggleMode(mode) {
            this.isPreviewMode = (mode === 'preview');
            
            $('.ecf-mode-tab').removeClass('active');
            $(`.ecf-mode-tab[data-mode="${mode}"]`).addClass('active');
            
            if (this.isPreviewMode) {
                this.updatePreview();
                $('#ecf-form-preview').show();
                $('#ecf-form-builder').hide();
                $('.ecf-properties-panel').hide();
            } else {
                $('#ecf-form-preview').hide();
                $('#ecf-form-builder').show();
                $('.ecf-properties-panel').show();
            }
        }

        updatePreview() {
            $.ajax({
                url: ecf_builder.ajax_url,
                type: 'POST',
                data: {
                    action: 'ecf_get_form_preview',
                    form_data: this.formData,
                    nonce: ecf_builder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#ecf-form-preview').html(response.data.preview);
                    }
                }
            });
        }

        renderForm() {
            const $formFields = $('#ecf-form-fields');
            
            if (this.formData.fields.length === 0) {
                $formFields.html(`
                    <div class="ecf-empty-state">
                        <span class="dashicons dashicons-welcome-add-page"></span>
                        <h3>${ecf_builder.i18n.addField}</h3>
                        <p>Drag fields from the left or click on them to add to your form.</p>
                    </div>
                `);
                return;
            }

            let html = '';
            this.formData.fields.forEach(field => {
                html += this.renderField(field);
            });
            
            $formFields.html(html);
        }

        renderField(field) {
            const isSelected = this.selectedField === field.id;
            const requiredStar = field.required ? '<span class="ecf-field-required">*</span>' : '';
            
            return `
                <div class="ecf-form-field ${isSelected ? 'selected' : ''}" data-field-id="${field.id}">
                    <div class="ecf-field-header">
                        <div class="ecf-field-label">
                            ${field.label}${requiredStar}
                        </div>
                        <div class="ecf-field-actions">
                            <button type="button" class="ecf-field-action ecf-field-duplicate" title="${ecf_builder.i18n.duplicateField}">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                            <button type="button" class="ecf-field-action ecf-field-delete" title="${ecf_builder.i18n.deleteField}">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="ecf-field-preview">
                        ${this.renderFieldPreview(field)}
                    </div>
                </div>
            `;
        }

        renderFieldPreview(field) {
            switch (field.type) {
                case 'text':
                case 'email':
                    return `<input type="${field.type}" placeholder="${field.placeholder}" disabled>`;
                
                case 'textarea':
                    return `<textarea placeholder="${field.placeholder}" disabled></textarea>`;
                
                case 'select':
                    const options = field.options ? field.options.map(opt => `<option>${opt}</option>`).join('') : '';
                    return `<select disabled><option value="">${field.placeholder}</option>${options}</select>`;
                
                case 'submit':
                    return `<button type="button" disabled>${field.label}</button>`;
                
                default:
                    return `<input type="text" placeholder="${field.placeholder}" disabled>`;
            }
        }

        updatePropertiesPanel() {
            const $panel = $('.ecf-properties-panel');
            
            if (!this.selectedField) {
                $panel.html(`
                    <div class="ecf-no-selection">
                        <span class="dashicons dashicons-info"></span>
                        <p>${ecf_builder.i18n.fieldSettings}</p>
                        <p>Select a field to edit its properties.</p>
                    </div>
                `);
                return;
            }

            const field = this.formData.fields.find(f => f.id === this.selectedField);
            if (!field) return;

            let html = `
                <div class="ecf-properties-title">${ecf_builder.i18n.fieldSettings}</div>
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

            // Field-specific properties
            if (field.type === 'select') {
                html += `
                    <div class="ecf-property-group">
                        <label class="ecf-property-label">Options (one per line)</label>
                        <textarea class="ecf-property-input" name="options" rows="5">${field.options ? field.options.join('\n') : ''}</textarea>
                    </div>
                `;
            }

            $panel.html(html);
        }

        saveForm() {
            const $saveBtn = $('#ecf-save-form');
            const originalText = $saveBtn.text();
            
            $saveBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: ecf_builder.ajax_url,
                type: 'POST',
                data: {
                    action: 'ecf_save_form',
                    form_id: this.currentFormId,
                    form_data: this.formData,
                    nonce: ecf_builder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $saveBtn.text(ecf_builder.i18n.formSaved).addClass('ecf-btn-success');
                        setTimeout(() => {
                            $saveBtn.text(originalText).removeClass('ecf-btn-success');
                        }, 2000);
                    } else {
                        alert('Error saving form: ' + response.data.message);
                        $saveBtn.text(originalText);
                    }
                },
                error: () => {
                    alert('Error saving form. Please try again.');
                    $saveBtn.text(originalText);
                },
                complete: () => {
                    $saveBtn.prop('disabled', false);
                }
            });
        }
    }

    // Initialize builder when DOM is ready
    $(document).ready(() => {
        new ECFBuilder();
    });

})(jQuery);


