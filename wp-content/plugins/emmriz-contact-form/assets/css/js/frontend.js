/* Emmriz Contact Form - Frontend Script */
(function($) {
    'use strict';

    class ECFFrontend {
        constructor() {
            this.ajaxUrl = ecf_ajax.ajax_url;
            this.nonce = ecf_ajax.nonce;
            this.init();
        }

        init() {
            this.initEventListeners();
        }

        initEventListeners() {
            // Form submission
            $(document).on('submit', '.emmriz-contact-form', (e) => {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            });

            // Real-time validation
            $(document).on('blur', '.ecf-field-input, .ecf-field-textarea, .ecf-field-select', (e) => {
                this.validateField(e.target);
            });
        }

        handleFormSubmit(form) {
            const $form = $(form);
            const $submitBtn = $form.find('.ecf-submit-btn');
            const formId = $form.data('form-id');
            const formData = new FormData(form);

            // Disable submit button
            $submitBtn.prop('disabled', true).addClass('ecf-loading');

            // Clear previous messages
            this.clearMessages($form);

            // Validate form
            if (!this.validateForm($form)) {
                $submitBtn.prop('disabled', false).removeClass('ecf-loading');
                return;
            }

            // Prepare data for AJAX
            const submissionData = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'ecf_submit' && !key.startsWith('_')) {
                    submissionData[key] = value;
                }
            }

            // AJAX submission
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ecf_submit_form',
                    form_id: formId,
                    form_data: submissionData,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess($form, response.data.message);
                        $form[0].reset();
                    } else {
                        this.showError($form, response.data.message, response.data.errors);
                    }
                },
                error: () => {
                    this.showError($form, 'An error occurred. Please try again.');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).removeClass('ecf-loading');
                }
            });
        }

        validateForm($form) {
            let isValid = true;
            const $fields = $form.find('.ecf-field-input, .ecf-field-textarea, .ecf-field-select');

            $fields.each((index, field) => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            return isValid;
        }

        validateField(field) {
            const $field = $(field);
            const $fieldGroup = $field.closest('.ecf-field-group');
            const isRequired = $fieldGroup.find('.ecf-field-required').length > 0;
            const value = $field.val().trim();
            const type = $field.attr('type') || $field.prop('tagName').toLowerCase();

            // Clear previous error
            $fieldGroup.find('.ecf-error-message').remove();
            $field.removeClass('ecf-field-error');

            // Required field validation
            if (isRequired && !value) {
                this.showFieldError($field, 'This field is required.');
                return false;
            }

            // Email validation
            if (type === 'email' && value && !this.isValidEmail(value)) {
                this.showFieldError($field, 'Please enter a valid email address.');
                return false;
            }

            return true;
        }

        showFieldError($field, message) {
            const $fieldGroup = $field.closest('.ecf-field-group');
            $field.addClass('ecf-field-error');
            $fieldGroup.append(`<span class="ecf-error-message">${message}</span>`);
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        clearMessages($form) {
            $form.find('.ecf-messages').empty();
            $form.find('.ecf-error-message').remove();
            $form.find('.ecf-field-error').removeClass('ecf-field-error');
        }

        showSuccess($form, message) {
            const $messages = $form.find('.ecf-messages');
            $messages.html(`<div class="ecf-alert ecf-alert-success">${message}</div>`);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $messages.offset().top - 100
            }, 500);
        }

        showError($form, message, errors = {}) {
            const $messages = $form.find('.ecf-messages');
            let errorHtml = `<div class="ecf-alert ecf-alert-error">${message}</div>`;
            
            $messages.html(errorHtml);

            // Show field-specific errors
            if (errors) {
                Object.keys(errors).forEach(fieldId => {
                    const $field = $form.find(`[name="${fieldId}"]`);
                    if ($field.length) {
                        this.showFieldError($field, errors[fieldId]);
                    }
                });
            }

            // Scroll to first error
            $('html, body').animate({
                scrollTop: $form.offset().top - 100
            }, 500);
        }
    }

    // Initialize frontend when DOM is ready
    $(document).ready(() => {
        new ECFFrontend();
    });

})(jQuery);