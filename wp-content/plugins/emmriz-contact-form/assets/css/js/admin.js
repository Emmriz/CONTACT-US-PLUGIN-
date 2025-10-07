/* Emmriz Contact Form - Admin Script */
(function($) {
    'use strict';

    class ECFAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.initEventListeners();
            this.initFormList();
        }

        initEventListeners() {
            // Form list actions
            $(document).on('click', '.ecf-delete-form', this.handleDeleteForm.bind(this));
            $(document).on('click', '.ecf-duplicate-form', this.handleDuplicateForm.bind(this));
            
            // Submission actions
            $(document).on('click', '.ecf-view-submission', this.handleViewSubmission.bind(this));
            $(document).on('click', '.ecf-delete-submission', this.handleDeleteSubmission.bind(this));
            $(document).on('click', '.ecf-mark-read', this.handleMarkRead.bind(this));
        }

        initFormList() {
            // Initialize any form list functionality
            $('.ecf-form-list').on('click', '.ecf-form-item', function(e) {
                if (!$(e.target).closest('.ecf-form-actions').length) {
                    window.location.href = $(this).find('.ecf-form-title a').attr('href');
                }
            });
        }

        handleDeleteForm(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const formId = $button.data('form-id');
            const formTitle = $button.data('form-title');

            if (!confirm(`Are you sure you want to delete "${formTitle}"? This action cannot be undone.`)) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ecf_delete_form',
                    form_id: formId,
                    nonce: ecf_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $button.closest('.ecf-form-item').fadeOut(300, function() {
                            $(this).remove();
                            // Show empty state if no forms left
                            if ($('.ecf-form-item').length === 0) {
                                $('.ecf-form-list').html(`
                                    <div class="ecf-no-forms">
                                        <span class="dashicons dashicons-welcome-add-page"></span>
                                        <h3>No Forms Yet</h3>
                                        <p>Create your first contact form to get started.</p>
                                        <a href="${ecf_admin.builder_url}" class="button button-primary">Create Form</a>
                                    </div>
                                `);
                            }
                        });
                    } else {
                        alert('Error deleting form: ' + response.data.message);
                    }
                },
                error: () => {
                    alert('Error deleting form. Please try again.');
                }
            });
        }

        handleDuplicateForm(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(e.currentTarget);
            const formId = $button.data('form-id');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ecf_duplicate_form',
                    form_id: formId,
                    nonce: ecf_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert('Error duplicating form: ' + response.data.message);
                    }
                },
                error: () => {
                    alert('Error duplicating form. Please try again.');
                }
            });
        }

        handleViewSubmission(e) {
            e.preventDefault();
            const $row = $(e.currentTarget).closest('tr');
            const submissionId = $row.data('submission-id');
            
            // Open submission details modal or page
            this.openSubmissionModal(submissionId);
        }

        handleDeleteSubmission(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const submissionId = $button.data('submission-id');

            if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ecf_delete_submission',
                    submission_id: submissionId,
                    nonce: ecf_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error deleting submission: ' + response.data.message);
                    }
                },
                error: () => {
                    alert('Error deleting submission. Please try again.');
                }
            });
        }

        handleMarkRead(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const submissionId = $button.data('submission-id');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ecf_mark_submission_read',
                    submission_id: submissionId,
                    nonce: ecf_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $button.closest('tr').removeClass('ecf-submission-unread');
                        $button.remove();
                        
                        // Update unread count
                        const $unreadCount = $('.ecf-unread-count');
                        if ($unreadCount.length) {
                            const newCount = parseInt($unreadCount.text()) - 1;
                            if (newCount > 0) {
                                $unreadCount.text(newCount);
                            } else {
                                $unreadCount.remove();
                            }
                        }
                    }
                }
            });
        }

        openSubmissionModal(submissionId) {
            // This would open a modal with submission details
            // For now, redirect to a dedicated submission view page
            window.location.href = `admin.php?page=ecf-submissions&view=submission&id=${submissionId}`;
        }
    }

    // Initialize admin when DOM is ready
    $(document).ready(() => {
        new ECFAdmin();
    });

})(jQuery);