<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EmmrizTech_Contact_Admin
 *
 * Upgraded admin class: messages listing, modal view, delete, export.
 */
class EmmrizTech_Contact_Admin {
    /**
     * @var EmmrizTech_Contact_DB
     */
    private $db;

    public function __construct() {
        $this->db = EmmrizTech_Contact_DB::instance();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers for modal view and delete
        add_action('wp_ajax_emmriztech_get_message', array($this, 'ajax_get_message'));
        add_action('wp_ajax_emmriztech_delete_message', array($this, 'ajax_delete_message'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'EmmrizTech Contact',
            'EmmrizTech Contact',
            'manage_options',
            'emmriztech-contact',
            array($this, 'settings_page'),
            'dashicons-email-alt',
            26
        );

        add_submenu_page(
            'emmriztech-contact',
            'Messages',
            'Messages',
            'manage_options',
            'emmriztech-contact-messages',
            array($this, 'messages_page')
        );
    }

    public function register_settings() {
        register_setting('emmriztech_cf_settings_group', 'emmriztech_cf_settings');
    }

    public function enqueue_admin_assets($hook) {
        // Only load our assets on plugin admin pages
        if (strpos($hook, 'emmriztech-contact') === false && strpos($hook, 'emmriztech_contact') === false) {
            return;
        }

        // CSS for admin modal/table
        wp_enqueue_style('emmriztech-admin-css', EMMRIZTECH_CF_URL . 'assets/css/admin.css', array(), EMMRIZTECH_CF_VERSION);

        // Small helper JS
        wp_enqueue_script('emmriztech-admin-js', EMMRIZTECH_CF_URL . 'assets/js/admin.js', array('jquery'), EMMRIZTECH_CF_VERSION, true);

        // Localize script with nonce and ajax URL
        wp_localize_script('emmriztech-admin-js', 'EmmrizTechAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('emmriztech_admin_nonce'),
            'confirm_delete' => __('Delete this message?', 'emmriztech-contact-form'),
        ));
    }

    /**
     * Settings page (keeps simple - same as before)
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $settings = get_option('emmriztech_cf_settings', array());
        ?>
        <div class="wrap">
            <h1>EmmrizTech Contact Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('emmriztech_cf_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="recipient_email">Recipient Email</label></th>
                            <td>
                                <input name="emmriztech_cf_settings[recipient_email]" type="email" id="recipient_email" value="<?php echo esc_attr($settings['recipient_email'] ?? ''); ?>" class="regular-text" />
                                <p class="description">Where contact messages are sent. Defaults to site admin email if empty.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="success_message">Success Message</label></th>
                            <td>
                                <input name="emmriztech_cf_settings[success_message]" type="text" id="success_message" value="<?php echo esc_attr($settings['success_message'] ?? 'Message sent successfully!'); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="error_message">Error Message</label></th>
                            <td>
                                <input name="emmriztech_cf_settings[error_message]" type="text" id="error_message" value="<?php echo esc_attr($settings['error_message'] ?? 'Something went wrong. Please try again.'); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Messages listing page
     */
    public function messages_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'emmriztech-contact-form'));
        }

        // Process export if requested (GET action=export)
        if (isset($_GET['action']) && $_GET['action'] === 'export') {
            $this->export_csv();
        }

        // Process bulk delete if POSTed
        if (isset($_POST['bulk_delete']) && !empty($_POST['ids'])) {
            if (empty($_POST['emmriztech_messages_nonce']) || !wp_verify_nonce($_POST['emmriztech_messages_nonce'], 'emmriztech_messages_action')) {
                wp_die(__('Invalid request', 'emmriztech-contact-form'));
            }
            $ids = array_map('intval', (array) $_POST['ids']);
            foreach ($ids as $id) {
                $this->db->delete_message($id);
            }
            wp_safe_redirect(menu_page_url('emmriztech-contact-messages', false));
            exit;
        }

        // Get pagination/search params
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $s = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $per_page = 20;

        $result = $this->db->get_messages(array(
            'paged' => $paged,
            'per_page' => $per_page,
            's' => $s,
        ));

        $total = $result['total'];
        $rows = $result['rows'];

        $base_page = menu_page_url('emmriztech-contact-messages', false);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">EmmrizTech Messages</h1>
            <a href="<?php echo esc_url(add_query_arg('action', 'export', $base_page)); ?>" class="page-title-action">Export CSV</a>
            <a href="<?php echo esc_url(menu_page_url('emmriztech-contact', false)); ?>" class="page-title-action" style="margin-left:10px;">Settings</a>

            <hr class="wp-header-end">

            <form method="get" class="search-form" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="emmriztech-contact-messages" />
                <p class="search-box">
                    <label class="screen-reader-text" for="emmriztech-search-input"><?php echo esc_html__('Search Messages', 'emmriztech-contact-form'); ?>:</label>
                    <input type="search" id="emmriztech-search-input" name="s" value="<?php echo esc_attr($s); ?>" />
                    <input type="submit" id="search-submit" class="button" value="Search">
                </p>
            </form>

            <form method="post" action="">
                <?php wp_nonce_field('emmriztech_messages_action', 'emmriztech_messages_nonce'); ?>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column"><input id="emmriztech-select-all" type="checkbox" /></td>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Option</th>
                            <th scope="col">Message</th>
                            <th scope="col">Date</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="the-list">
                        <?php if (empty($rows)): ?>
                            <tr class="no-items">
                                <td colspan="9">No messages found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo esc_attr($row['id']); ?>" /></th>
                                    <td><?php echo esc_html($row['id']); ?></td>
                                    <td><?php echo esc_html($row['name']); ?></td>
                                    <td><a href="mailto:<?php echo esc_attr($row['email']); ?>"><?php echo esc_html($row['email']); ?></a></td>
                                    <td><?php echo esc_html($row['phone']); ?></td>
                                    <td><?php echo esc_html($row['option_selected']); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($row['message'], 12, '...')); ?></td>
                                    <td><?php echo esc_html($row['created_at']); ?></td>
                                    <td>
                                        <a href="#" class="emmriztech-view-message" data-id="<?php echo esc_attr($row['id']); ?>">View</a> |
                                        <a href="#" class="emmriztech-delete-message" data-id="<?php echo esc_attr($row['id']); ?>">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="tablenav">
                    <input type="submit" name="bulk_delete" id="bulk-action-selector-bottom" class="button action" value="Delete Selected" onclick="return confirm('Delete selected messages?');" />
                    <a class="button" href="<?php echo esc_url(add_query_arg('action', 'export', $base_page)); ?>">Export All</a>
                </p>
            </form>

            <?php
            // pagination
            $num_pages = max(1, ceil($total / $per_page));
            if ($num_pages > 1) {
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '?paged=%#%',
                    'current' => $paged,
                    'total' => $num_pages,
                    'add_args' => array('page' => 'emmriztech-contact-messages'),
                ));
                echo '<div class="tablenav">' . $page_links . '</div>';
            }
            ?>

        </div>

        <!-- Modal container -->
        <div id="emmriztech-modal" class="emmriztech-modal" aria-hidden="true" style="display:none;">
            <div class="emmriztech-modal-inner">
                <button id="emmriztech-modal-close" class="emmriztech-modal-close" aria-label="<?php esc_attr_e('Close', 'emmriztech-contact-form'); ?>">&times;</button>
                <div id="emmriztech-modal-content" class="emmriztech-modal-content">
                    <!-- AJAX content injected here -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: return a single message HTML for the modal
     */
    public function ajax_get_message() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }

        check_ajax_referer('emmriztech_admin_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            wp_send_json_error('invalid_id', 400);
        }

        $msg = $this->db->get_message($id);
        if (!$msg) {
            wp_send_json_error('not_found', 404);
        }

        // prepare HTML (escaped)
        ob_start();
        ?>
        <h2>Message #<?php echo esc_html($msg['id']); ?></h2>
        <p><strong><?php esc_html_e('Name', 'emmriztech-contact-form'); ?>:</strong> <?php echo esc_html($msg['name']); ?></p>
        <p><strong><?php esc_html_e('Email', 'emmriztech-contact-form'); ?>:</strong> <a href="mailto:<?php echo esc_attr($msg['email']); ?>"><?php echo esc_html($msg['email']); ?></a></p>
        <p><strong><?php esc_html_e('Phone', 'emmriztech-contact-form'); ?>:</strong> <?php echo esc_html($msg['phone']); ?></p>
        <p><strong><?php esc_html_e('Option', 'emmriztech-contact-form'); ?>:</strong> <?php echo esc_html($msg['option_selected']); ?></p>
        <p><strong><?php esc_html_e('IP', 'emmriztech-contact-form'); ?>:</strong> <?php echo esc_html($msg['ip']); ?></p>
        <p><strong><?php esc_html_e('User Agent', 'emmriztech-contact-form'); ?>:</strong> <?php echo esc_html($msg['user_agent']); ?></p>
        <h3><?php esc_html_e('Message', 'emmriztech-contact-form'); ?></h3>
        <div style="white-space:pre-wrap;padding:10px;border:1px solid #ddd;background:#fff;"><?php echo esc_html($msg['message']); ?></div>
        <p style="margin-top:12px;">
            <button class="button emmriztech-delete-message" data-id="<?php echo esc_attr($msg['id']); ?>"><?php esc_html_e('Delete', 'emmriztech-contact-form'); ?></button>
            <button class="button emmriztech-modal-close-inline"><?php esc_html_e('Close', 'emmriztech-contact-form'); ?></button>
        </p>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: delete a single message
     */
    public function ajax_delete_message() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }

        check_ajax_referer('emmriztech_admin_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            wp_send_json_error('invalid_id', 400);
        }

        $deleted = $this->db->delete_message($id);
        if ($deleted) {
            wp_send_json_success('deleted');
        } else {
            wp_send_json_error('delete_failed', 500);
        }
    }

    /**
     * Export CSV of all messages and exit
     */
    protected function export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'emmriztech-contact-form'));
        }

        $csv = $this->db->export_csv();
        if ($csv === '') {
            wp_safe_redirect(menu_page_url('emmriztech-contact-messages', false));
            exit;
        }

        $filename = 'emmriztech_messages_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $csv;
        exit;
    }
}
