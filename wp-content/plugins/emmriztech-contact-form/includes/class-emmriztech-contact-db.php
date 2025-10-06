<?php
if (!defined('ABSPATH')) exit;


class EmmrizTech_Contact_DB {
private static $instance = null;
public static $table = null;


public static function instance() {
if (null === self::$instance) {
self::$instance = new self();
global $wpdb;
self::$table = $wpdb->prefix . 'emmriztech_messages';
}
return self::$instance;
}

public static function install() {
global $wpdb;
self::instance();


$charset_collate = $wpdb->get_charset_collate();


$sql = "CREATE TABLE " . self::$table . " (
id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
name VARCHAR(191) NOT NULL,
email VARCHAR(191) NOT NULL,
phone VARCHAR(60) DEFAULT '',
option_selected VARCHAR(100) DEFAULT '',
message TEXT NOT NULL,
ip VARCHAR(45) DEFAULT '',
user_agent TEXT DEFAULT '',
created_at DATETIME NOT NULL,
PRIMARY KEY (id)
) $charset_collate;";


require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);


// store db version
add_option('emmriztech_cf_db_version', EMMRIZTECH_CF_VERSION);
}

public static function uninstall_table() {
global $wpdb;
self::instance();
$wpdb->query("DROP TABLE IF EXISTS " . self::$table);
delete_option('emmriztech_cf_db_version');
}


public static function deactivate() {
// no destructive actions on deactivate
}


public function insert_message($data = []) {
global $wpdb;
$defaults = [
'name' => '',
'email' => '',
'phone' => '',
'option_selected' => '',
'message' => '',
'ip' => '',
'user_agent' => '',
'created_at' => current_time('mysql')
];
$row = wp_parse_args($data, $defaults);


$inserted = $wpdb->insert(self::$table, $row, [
'%s','%s','%s','%s','%s','%s','%s','%s'
]);


if ($inserted) return $wpdb->insert_id;
return false;
}

public function get_messages($args = []) {
global $wpdb;
$per_page = $args['per_page'] ?? 20;
$paged = max(1, intval($args['paged'] ?? 1));
$offset = ($paged - 1) * $per_page;


$where = "";
if (!empty($args['s'])) {
$search = esc_sql('%' . $wpdb->esc_like($args['s']) . '%');
$where = $wpdb->prepare(" WHERE name LIKE %s OR email LIKE %s OR message LIKE %s", $search, $search, $search);
}


$total = $wpdb->get_var("SELECT COUNT(*) FROM " . self::$table . $where);
$rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . self::$table . " %s ORDER BY created_at DESC LIMIT %d OFFSET %d", $where, $per_page, $offset), ARRAY_A);


return [
'total' => (int) $total,
'rows' => $rows,
'per_page' => $per_page,
'paged' => $paged,
];
}

public function get_message($id) {
global $wpdb;
return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$table . " WHERE id = %d", $id), ARRAY_A);
}


public function delete_message($id) {
global $wpdb;
return $wpdb->delete(self::$table, ['id' => $id], ['%d']);
}


public function export_csv($ids = []) {
global $wpdb;
if (empty($ids)) {
$rows = $wpdb->get_results("SELECT * FROM " . self::$table . " ORDER BY created_at DESC", ARRAY_A);
} else {
$ids = array_map('intval', $ids);
$in = join(',', $ids);
$rows = $wpdb->get_results("SELECT * FROM " . self::$table . " WHERE id IN ($in) ORDER BY created_at DESC", ARRAY_A);
}


if (empty($rows)) return '';


$output = fopen('php://memory', 'rw');
fputcsv($output, array_keys($rows[0]));
foreach ($rows as $r) fputcsv($output, $r);
rewind($output);
$csv = stream_get_contents($output);
fclose($output);
return $csv;
}
}