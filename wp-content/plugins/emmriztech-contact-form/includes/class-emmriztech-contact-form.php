<?php
if (!defined('ABSPATH')) exit;

class EmmrizTech_Contact_Form {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('emmriztech_contact', [$this, 'render_contact_form']);
        add_action('init', [$this, 'handle_form_submission']);
    }

    /**
     * Enqueue styles
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'tailwind',
            'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            [],
            '2.2.19'
        );
        wp_enqueue_style(
            'emmriztech-form-style',
            EMMRIZTECH_CF_URL . 'assets/css/form.css',
            ['tailwind'],
            filemtime(EMMRIZTECH_CF_PATH . 'assets/css/form.css')
        );
    }

    /**
     * Render form
     */
    public function render_contact_form($atts) {
        $atts = shortcode_atts(['template' => '1'], $atts);
        $template = intval($atts['template']);

        $success = isset($_GET['form_status']) && $_GET['form_status'] === 'success';
        $error   = isset($_GET['form_status']) && $_GET['form_status'] === 'error';

        $settings = get_option('emmriztech_cf_settings');
        $success_message = !empty($settings['success_message']) ? esc_html($settings['success_message']) : 'Message sent successfully!';
        $error_message   = !empty($settings['error_message']) ? esc_html($settings['error_message']) : 'Something went wrong. Please try again.';

        ob_start(); ?>

        <div class="emmriztech-contact-container flex justify-center items-center py-10 px-4">
            <div class="bg-white w-full max-w-md rounded-2xl shadow-lg p-6 md:p-8">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Contact Us</h2>

                <?php if ($success): ?>
                    <div class="bg-green-100 text-green-700 p-3 rounded-md mb-4">✅ <?php echo $success_message; ?></div>
                <?php elseif ($error): ?>
                    <div class="bg-red-100 text-red-700 p-3 rounded-md mb-4">❌ <?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="post" class="space-y-4">
                    <?php wp_nonce_field('emmriztech_contact_nonce', 'emmriztech_nonce'); ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full border border-gray-300 rounded-md p-2 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full border border-gray-300 rounded-md p-2 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" required class="w-full border border-gray-300 rounded-md p-2 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <?php if ($template === 2): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Option</label>
                            <select name="option" class="w-full border border-gray-300 rounded-md p-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select an option</option>
                                <option value="sales">Sales Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="billing">Billing</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="4" required class="w-full border border-gray-300 rounded-md p-2 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div>
                        <button type="submit" name="emmriztech_submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-md transition duration-200">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Handle submissions
     */
    public function handle_form_submission() {
        if (!isset($_POST['emmriztech_submit'])) return;
        if (!isset($_POST['emmriztech_nonce']) || !wp_verify_nonce($_POST['emmriztech_nonce'], 'emmriztech_contact_nonce')) {
            wp_safe_redirect(add_query_arg('form_status', 'error', wp_get_referer()));
            exit;
        }

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $option = isset($_POST['option']) ? sanitize_text_field($_POST['option']) : '';
        $message = sanitize_textarea_field($_POST['message']);

        $settings = get_option('emmriztech_cf_settings');
        $recipient = !empty($settings['recipient_email']) ? sanitize_email($settings['recipient_email']) : get_option('admin_email');

        $subject = "New Contact Message from $name";
        $body = "Name: $name\nEmail: $email\nPhone: $phone\nOption: $option\n\nMessage:\n$message";
        $headers = ['Content-Type: text/plain; charset=UTF-8', "Reply-To: $name <$email>"];

        $mail_sent = wp_mail($recipient, $subject, $body, $headers);
        $status = $mail_sent ? 'success' : 'error';
        wp_safe_redirect(add_query_arg('form_status', $status, wp_get_referer()));
        exit;
    }
}
