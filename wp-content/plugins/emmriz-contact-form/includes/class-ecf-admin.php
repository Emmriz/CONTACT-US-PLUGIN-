<?php
if (!defined('ABSPATH')) exit;

class ECF_Admin {
    private static $instance = null;
    private $db;

    /** ✅ Singleton initializer */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** ✅ Constructor */
    private function __construct() {
        // If DB class exists, instantiate it (optional)
        if (class_exists('Emmriz_Contact_DB')) {
            $this->db = Emmriz_Contact_DB::instance();
        }

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_ecf_get_message', array($this, 'ajax_get_message'));
        add_action('wp_ajax_ecf_delete_message', array($this, 'ajax_delete_message'));
    }

    /** ✅ Create Admin Menu */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Emmriz Contact',
            'Emmriz Contact',
            'manage_options',
            'ecf-contact',
            array($this, 'builder_page'),
            'dashicons-email-alt',
            26
        );

        // Submenu: Form Builder
        add_submenu_page(
            'ecf-contact',
            'Form Builder',
            'Form Builder',
            'manage_options',
            'ecf-builder',
            array($this, 'builder_page')
        );

        // Submenu: Messages
        add_submenu_page(
            'ecf-contact',
            'Messages',
            'Messages',
            'manage_options',
            'ecf-contact-messages',
            array($this, 'messages_page')
        );

        // Submenu: Settings
        add_submenu_page(
            'ecf-contact',
            'Settings',
            'Settings',
            'manage_options',
            'ecf-contact-settings',
            array($this, 'settings_page')
        );
    }

    /** ✅ Enqueue admin scripts/styles */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();

        // Only load assets on our plugin pages
        if (strpos($screen->id, 'ecf-contact') === false && strpos($screen->id, 'ecf-builder') === false) {
            return;
        }

        // Core admin styles
        wp_enqueue_style(
            'ecf-admin-css',
            ECF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ECF_VERSION
        );

        // Tailwind or Builder UI styles
        if (strpos($screen->id, 'ecf-builder') !== false) {
            wp_enqueue_style(
                'ecf-builder-css',
                ECF_PLUGIN_URL . 'assets/css/builder.css',
                array('ecf-admin-css'),
                ECF_VERSION
            );

            wp_enqueue_script(
                'ecf-sortable',
                ECF_PLUGIN_URL . 'assets/js/vendors/sortable.js',
                array(),
                '1.15.0',
                true
            );

            wp_enqueue_script(
                'ecf-builder-js',
                ECF_PLUGIN_URL . 'assets/js/builder.js',
                array('jquery', 'jquery-ui-sortable', 'ecf-sortable'),
                ECF_VERSION,
                true
            );

            wp_localize_script('ecf-builder-js', 'ecf_builder', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ecf_builder_nonce')
            ));
        }

        // Admin JS
        wp_enqueue_script(
            'ecf-admin-js',
            ECF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ECF_VERSION,
            true
        );

        wp_localize_script('ecf-admin-js', 'ECFAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_admin_nonce'),
            'confirm_delete' => __('Delete this message?', 'emmriz-contact-form')
        ));
    }

    /** ✅ Builder Page */
    public function builder_page() {
        if (!class_exists('ECF_Form_Builder')) {
            echo '<div class="notice notice-error"><p><strong>Error:</strong> ECF_Form_Builder class not found.</p></div>';
            return;
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Form Builder</h1><hr class="wp-header-end">';
        ECF_Form_Builder::get_instance()->render_builder_page();
        echo '</div>';
    }

    /** ✅ Settings Page */
    public function settings_page() {
        if (!current_user_can('manage_options')) return;

        $settings = get_option('ecf_settings', array());
        ?>
        <div class="wrap">
            <h1>Emmriz Contact Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ecf_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th><label for="recipient_email">Recipient Email</label></th>
                            <td>
                                <input name="ecf_settings[recipient_email]" type="email" id="recipient_email"
                                       value="<?php echo esc_attr($settings['recipient_email'] ?? ''); ?>"
                                       class="regular-text"/>
                                <p class="description">Defaults to the admin email if left empty.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    /** ✅ Messages Page */
    public function messages_page() {
        if (!current_user_can('manage_options')) return;

        if (!$this->db || !method_exists($this->db, 'get_messages')) {
            echo '<div class="notice notice-warning"><p>No database handler found for messages.</p></div>';
            return;
        }

        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $s = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $per_page = 20;

        $result = $this->db->get_messages(array(
            'paged' => $paged,
            'per_page' => $per_page,
            's' => $s,
        ));

        $rows = $result['rows'];
        $total = $result['total'];
        $num_pages = max(1, ceil($total / $per_page));
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Emmriz Messages</h1>
            <a href="<?php echo esc_url(add_query_arg('action', 'export')); ?>" class="page-title-action">Export CSV</a>
            <hr class="wp-header-end">

            <form method="get" class="search-form">
                <input type="hidden" name="page" value="ecf-contact-messages">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr($s); ?>">
                    <input type="submit" class="button" value="Search">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Option</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8">No messages found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row['id']); ?></td>
                                <td><?php echo esc_html($row['name']); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($row['email']); ?>"><?php echo esc_html($row['email']); ?></a></td>
                                <td><?php echo esc_html($row['phone']); ?></td>
                                <td><?php echo esc_html($row['option_selected']); ?></td>
                                <td><?php echo esc_html(wp_trim_words($row['message'], 10, '...')); ?></td>
                                <td><?php echo esc_html($row['created_at']); ?></td>
                                <td>
                                    <a href="#" class="view-message" data-id="<?php echo esc_attr($row['id']); ?>">View</a> |
                                    <a href="#" class="delete-message" data-id="<?php echo esc_attr($row['id']); ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($num_pages > 1): ?>
                <div class="tablenav">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $num_pages,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="ecf-modal" class="modal" style="display:none;">
            <div class="modal-inner">
                <button class="modal-close">&times;</button>
                <div id="ecf-modal-content"></div>
            </div>
        </div>
        <?php
    }

    /** ✅ AJAX: Get Message */
    public function ajax_get_message() {
        check_ajax_referer('ecf_admin_nonce', 'nonce');

        if (!$this->db) wp_send_json_error('db_not_found');

        $id = intval($_POST['id']);
        $msg = $this->db->get_message($id);

        if (!$msg) wp_send_json_error('not_found');

        ob_start(); ?>
        <h2><?php echo esc_html($msg['name']); ?></h2>
        <p><strong>Email:</strong> <?php echo esc_html($msg['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo esc_html($msg['phone']); ?></p>
        <p><strong>Option:</strong> <?php echo esc_html($msg['option_selected']); ?></p>
        <p><strong>Message:</strong></p>
        <div style="background:#fff;border:1px solid #ccc;padding:10px;"><?php echo esc_html($msg['message']); ?></div>
        <?php
        wp_send_json_success(array('html' => ob_get_clean()));
    }

    /** ✅ AJAX: Delete Message */
    public function ajax_delete_message() {
        check_ajax_referer('ecf_admin_nonce', 'nonce');

        if (!$this->db) wp_send_json_error('db_not_found');

        $id = intval($_POST['id']);
        $deleted = $this->db->delete_message($id);
        $deleted ? wp_send_json_success('deleted') : wp_send_json_error('delete_failed');
    }
}
