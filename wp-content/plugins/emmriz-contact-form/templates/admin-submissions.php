<?php
/**
 * Admin Submissions Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$submissions_handler = ECF_Submissions::get_instance();
$total_submissions = $submissions_handler->get_submission_count($form_id);
$unread_count = $submissions_handler->get_unread_count($form_id);
?>
<div class="wrap ecf-admin-wrap">
    <div class="ecf-admin-header">
        <h1>
            <?php printf(__('Submissions for: %s', 'emmriz-contact-form'), esc_html($form['title'])); ?>
            <span class="ecf-form-id">(ID: <?php echo esc_html($form_id); ?>)</span>
        </h1>
        
        <div class="ecf-stats-grid">
            <div class="ecf-stat-card">
                <span class="ecf-stat-number"><?php echo esc_html($total_submissions); ?></span>
                <span class="ecf-stat-label"><?php _e('Total Submissions', 'emmriz-contact-form'); ?></span>
            </div>
            <div class="ecf-stat-card">
                <span class="ecf-stat-number"><?php echo esc_html($unread_count); ?></span>
                <span class="ecf-stat-label"><?php _e('Unread', 'emmriz-contact-form'); ?></span>
            </div>
        </div>

        <div class="ecf-admin-actions">
            <a href="<?php echo admin_url('edit.php?post_type=ecf_form'); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to Forms', 'emmriz-contact-form'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ecf-builder&form_id=' . $form_id); ?>" class="button button-primary">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Edit Form', 'emmriz-contact-form'); ?>
            </a>
        </div>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="ecf-no-forms">
            <span class="dashicons dashicons-email-alt"></span>
            <h3><?php _e('No Submissions Yet', 'emmriz-contact-form'); ?></h3>
            <p><?php _e('Form submissions will appear here once people start filling out your form.', 'emmriz-contact-form'); ?></p>
        </div>
    <?php else: ?>
        <div class="ecf-submissions-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" width="50"><?php _e('ID', 'emmriz-contact-form'); ?></th>
                        <th scope="col"><?php _e('Submission Data', 'emmriz-contact-form'); ?></th>
                        <th scope="col" width="120"><?php _e('IP Address', 'emmriz-contact-form'); ?></th>
                        <th scope="col" width="150"><?php _e('Date', 'emmriz-contact-form'); ?></th>
                        <th scope="col" width="100"><?php _e('Status', 'emmriz-contact-form'); ?></th>
                        <th scope="col" width="120"><?php _e('Actions', 'emmriz-contact-form'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <?php
                        $submission_data = json_decode($submission->form_data, true);
                        $is_unread = !$submission->read_status;
                        ?>
                        <tr class="<?php echo $is_unread ? 'ecf-submission-unread' : ''; ?>" data-submission-id="<?php echo esc_attr($submission->id); ?>">
                            <td>#<?php echo esc_html($submission->id); ?></td>
                            <td>
                                <div class="ecf-field-preview">
                                    <?php 
                                    $preview_data = array_slice($submission_data, 0, 3);
                                    foreach ($preview_data as $key => $value):
                                        if (!empty($value) && !in_array($key, ['ecf_nonce', 'ecf_honeypot'])):
                                            $label = ECF_Template_Helper::get_field_label($key, $form['data']['fields']);
                                            ?>
                                            <div class="ecf-preview-item">
                                                <strong><?php echo esc_html($label); ?>:</strong>
                                                <?php 
                                                if (is_array($value)) {
                                                    echo esc_html(implode(', ', $value));
                                                } else {
                                                    echo esc_html(wp_trim_words($value, 10));
                                                }
                                                ?>
                                            </div>
                                        <?php endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($submission->ip_address); ?></td>
                            <td>
                                <?php 
                                echo date_i18n(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($submission->submitted_at)
                                ); 
                                ?>
                            </td>
                            <td>
                                <?php if ($is_unread): ?>
                                    <span class="ecf-status-unread"><?php _e('Unread', 'emmriz-contact-form'); ?></span>
                                <?php else: ?>
                                    <span class="ecf-status-read"><?php _e('Read', 'emmriz-contact-form'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="ecf-actions">
                                <button type="button" class="button ecf-view-submission" data-submission-id="<?php echo esc_attr($submission->id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php _e('View', 'emmriz-contact-form'); ?>
                                </button>
                                
                                <?php if ($is_unread): ?>
                                    <button type="button" class="button ecf-mark-read" data-submission-id="<?php echo esc_attr($submission->id); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="button button-link-delete ecf-delete-submission" data-submission-id="<?php echo esc_attr($submission->id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination would go here -->
        <div class="ecf-pagination">
            <p class="description">
                <?php 
                printf(
                    __('Showing %d of %d submissions', 'emmriz-contact-form'),
                    count($submissions),
                    $total_submissions
                ); 
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>