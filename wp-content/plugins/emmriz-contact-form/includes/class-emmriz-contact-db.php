<?php
if (!defined('ABSPATH')) exit;

class Emmriz_Contact_DB {
    private static $instance = null;
    private $table;

    private function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'emmriz_messages';
    }

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_messages($args = array()) {
        global $wpdb;

        $paged = isset($args['paged']) ? max(1, intval($args['paged'])) : 1;
        $per_page = isset($args['per_page']) ? intval($args['per_page']) : 20;
        $offset = ($paged - 1) * $per_page;
        $search = isset($args['s']) ? trim($args['s']) : '';

        $where = '';
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where = $wpdb->prepare(
                "WHERE name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s",
                $like, $like, $like, $like
            );
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table} $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset),
            ARRAY_A
        );

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} $where");

        return array('rows' => $rows, 'total' => $total);
    }

    public function get_message($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
    }

    public function delete_message($id) {
        global $wpdb;
        return $wpdb->delete($this->table, array('id' => $id), array('%d'));
    }

    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            phone varchar(50) DEFAULT '' NOT NULL,
            option_selected varchar(191) DEFAULT '' NOT NULL,
            message text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
