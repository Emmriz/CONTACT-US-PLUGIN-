<?php
if (!defined('ABSPATH')) exit;

class EmmrizTech_Contact_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

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
    }

    public function register_settings() {
        register_setting('emmriztech_cf_settings_group', 'emmriztech_cf_settings');
    }

    public function settings_page() {
        $settings = get_option('emmriztech_cf_settings'); ?>

        <div class="wrap">
            <h1>EmmrizTech Contact Form Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('emmriztech_cf_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Recipient Email</th>
                        <td><input type="email" name="emmriztech_cf_settings[recipient_email]" value="<?php echo esc_attr($settings['recipient_email'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Success Message</th>
                        <td><input type="text" name="emmriztech_cf_settings[success_message]" value="<?php echo esc_attr($settings['success_message'] ?? 'Message sent successfully!'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Error Message</th>
                        <td><input type="text" name="emmriztech_cf_settings[error_message]" value="<?php echo esc_attr($settings['error_message'] ?? 'Something went wrong. Please try again.'); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>

    <?php }
}
