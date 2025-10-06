<?php
if (!defined('ABSPATH')) {
    exit;
}

class EmmrizTech_Contact_Admin {
    /**
     * @var EmmrizTech_Contact_DB
     */
    private $db;

    public function __construct() {
        $this->db = EmmrizTech_Contact_DB::instance();

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin assets only on our pages
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'emmriztech-contact') === false && strpos($hook, 'emmriztech_contact') === false) {
            return;
        }

        wp_enqueue_script(
            'emmriztech-admin-js',
            EMMRIZTECH_CF_URL . 'assets/js/admin.js',
            ['jquery'],
            EMMRIZTECH_CF_VERSION,
            true
        );
    }

    /**
     * Add plugin menu and submenu
     */
    public function add_admin_menu() {
        add_menu_page(
            'EmmrizTech Contact',
            'EmmrizTech Contact',
            'manage_options',
            'emmriztech-contact',
            [$this, 'settings_page'],
            'dashicons-email-alt',
            26
        );

        add_submenu_page(
            'emmriztech-contact',
            'Messages',
            'Messages',
            'manage_options',
            'emmriztech-contact-messages',
            [$this, 'messages_page']
        );
    }

    /**
     * Register settings group
     */
    public function register_settings() {
        register_setting('emmriztech_cf_settings_group', 'emmriztech_cf_settings');
    }

    /**
     * Settings page markup
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('emmriztech_cf_settings', []);

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

        // Handle actions first (delete, export, view, bulk delete)
        $this->maybe_handle_actions();

        // Fetch list
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $s = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $per_page = 20;

        $result = $this->db->get_messages([
            'paged' => $paged,
            'per_page' => $per_page,
            's' => $s,
        ]);

        $total = $result['total'];
        $rows = $result['rows'];

        $base_page = menu_page_url('emmriztech-contact-messages', false);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Messages</h1>
            <a href="<?php echo esc_url(add_query_arg('action', 'export', $base_page)); ?>" class="page-title-action">Export CSV</a>
            <a href="<?php echo esc_url(menu_page_url('emmriztech-contact', false)); ?>" class="page-title-action" style="margin-left:10px;">Settings</a>

            <hr class="wp-header-end">

            <form method="get" class="search-form" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="emmriztech-contact-messages" />
                <?php
                $label = __('Search Messages', 'emmriztech-contact-form');
                ?>
                <p class="search-box">
                    <label class="screen-reader-text" for="emmriztech-search-input"><?php echo esc_html($label); ?>:</label>
                    <input type="search" id="emmriztech-search-input" name="s" value="<?php echo esc_attr($s); ?>" />
                    <input type="submit" id="search-submit" class="button" value="Search">
                </p>
            </form>

            <form method="post" action="">
                <?php wp_nonce_field('emmriztech_messages_action', 'emmriztech_messages_nonce'); ?>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column" scope="col"><input id="emmriztech-select-all" type="checkbox" /></td>
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
                                    <td><?php echo esc_html(wp_trim_words($row['message'], 15, '...')); ?></td>
                                    <td><?php echo esc_html($row['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg(['action' => 'view', 'id' => $row['id']])); ?>">View</a> |
                                        <?php
                                        $delete_url = wp_nonce_url(add_query_arg(['action' => 'delete', 'id' => $row['id']]), 'emmriztech_delete_message_' . $row['id']);
                                        ?>
                                        <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this message?');">Delete</a>
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
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '?paged=%#%',
                    'current' => $paged,
                    'total' => $num_pages,
                    'add_args' => ['page' => 'emmriztech-contact-messages'],
                ]);
                echo '<div class="tablenav">' . $page_links . '</div>';
            }
            ?>

        </div>
        <?php
    }

    /**
     * Handle delete/view/export/bulk actions.
     */
    private function maybe_handle_actions() {
        // require manage_options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Single delete via GET (nonce protected)
        if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
            $id = intval($_GET['id']);
            $nonce = $_REQUEST['_wpnonce'] ?? '';
            if (wp_verify_nonce($nonce, 'emmriztech_delete_message_' . $id)) {
                $this->db->delete_message($id);
                // redirect to clean URL
                wp_safe_redirect(remove_query_arg(['action', 'id', '_wpnonce']));
                exit;
            } else {
                wp_die(__('Invalid nonce for delete action', 'emmriztech-contact-form'));
            }
        }

        // View single message - render and exit (simple approach)
        if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'view') {
            $id = intval($_GET['id']);
            $msg = $this->db->get_message($id);
            if ($msg) {
                echo '<div class="wrap">';
                echo '<h1>Message #' . esc_html($msg['id']) . '</h1>';
                echo '<p><strong>Name:</strong> ' . esc_html($msg['name']) . '</p>';
                echo '<p><strong>Email:</strong> <a href="mailto:' . esc_attr($msg['email']) . '">' . esc_html($msg['email']) . '</a></p>';
                echo '<p><strong>Phone:</strong> ' . esc_html($msg['phone']) . '</p>';
                echo '<p><strong>Option:</strong> ' . esc_html($msg['option_selected']) . '</p>';
                echo '<p><strong>IP:</strong> ' . esc_html($msg['ip']) . '</p>';
                echo '<p><strong>User Agent:</strong> ' . esc_html($msg['user_agent']) . '</p>';
                echo '<h2>Message</h2>';
                echo '<div style="padding:12px;border:1px solid #ddd;background:#fff;white-space:pre-wrap;">' . esc_html($msg['message']) . '</div>';
                echo '<p style="margin-top:16px;"><a class="button" href="' . esc_url(menu_page_url('emmriztech-contact-messages', false)) . '">Back to messages</a></p>';
                echo '</div>';
                exit;
            } else {
                wp_safe_redirect(menu_page_url('emmriztech-contact-messages', false));
                exit;
            }
        }

        // Bulk delete via POST
        if (isset($_POST['bulk_delete']) && !empty($_POST['ids'])) {
            if (empty($_POST['emmriztech_messages_nonce']) || !wp_verify_nonce($_POST['emmriztech_messages_nonce'], 'emmriztech_messages_action')) {
                wp_die(__('Invalid nonce for bulk delete', 'emmriztech-contact-form'));
            }

            $ids = array_map('intval', (array) $_POST['ids']);
            foreach ($ids as $id) {
                $this->db->delete_message($id);
            }
            wp_safe_redirect(menu_page_url('emmriztech-contact-messages', false));
            exit;
        }

        // Export CSV (action=export) - must be GET and managed by admins
        if (isset($_GET['action']) && $_GET['action'] === 'export') {
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions', 'emmriztech-contact-form'));
            }

            $csv = $this->db->export_csv();
            if ($csv === '') {
                wp_safe_redirect(menu_page_url('emmriztech-contact-messages', false));
                exit;
            }

            // Output CSV and exit
            $filename = 'emmriztech_messages_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            echo $csv;
            exit;
        }
    }
}
