<?php
/*
Plugin Name: Three-Day Weekend Manager
Description: Manage board roles, assignments, and weekend settings for three-day communities.
Version: 1.4.4
Author: Allen Heishman
*/

if (!defined('ABSPATH')) exit;

// Plugin activation: create required tables
register_activation_hook(__FILE__, 'tdwm_install');
function tdwm_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $assignments_table = $wpdb->prefix . 'tdwm_board_assignments';
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE $assignments_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        role_name VARCHAR(255) NOT NULL,
        gender VARCHAR(10) DEFAULT NULL,
        start_date DATE NOT NULL,
        end_date DATE DEFAULT NULL
    ) $charset_collate;");

    dbDelta("CREATE TABLE $roles_table (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(255) NOT NULL,
    gender_requirement ENUM('Male', 'Female', 'N/A') DEFAULT 'N/A',
    sort_order INT DEFAULT 0
    ) $charset_collate;");
}

// Delete database and drop data manually [for debug  and reload]
add_action('admin_init', 'tdwm_check_manual_delete_trigger');
function tdwm_check_manual_delete_trigger() {
    if (isset($_GET['tdwm_delete_data']) && is_super_admin()) {
        tdwm_delete_plugin_tables();
        wp_redirect(admin_url('admin.php?page=tdwm_main_settings&tdwm_deleted=true'));
        exit;
    }
}

// Tables to delete if triggered manually
function tdwm_delete_plugin_tables() {
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'tdwm_board_roles',
        $wpdb->prefix . 'tdwm_board_assignments'
    ];
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Admin menu and pages
add_action('admin_menu', 'tdwm_register_admin_menu');
function tdwm_register_admin_menu() {
    add_menu_page(
        '3-Day Manager',
        '3-Day Manager',
        'manage_options',
        'tdwm_main_settings',
        '',
        'dashicons-calendar-alt',
        2  // moves right under the WP Dashboard
    );

    add_submenu_page(
        'tdwm_main_settings',
        'Admin Settings',
        'Admin Settings',
        'manage_options',
        'tdwm_main_settings',
        'tdwm_main_page'
    );

    add_submenu_page(
        'tdwm_main_settings',
        'Board Manager',
        'Board Manager',
        'manage_options',
        'tdwm_board_admin',
        'tdwm_board_admin_page'
    );

  
}

function tdwm_main_page() {
    echo '<div class="wrap"><h1>3-Day Weekend Manager</h1>';

    if (isset($_GET['tdwm_deleted']) && $_GET['tdwm_deleted'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>All plugin data deleted successfully.</p></div>';
    }

    if (is_super_admin()) {
        $delete_url = esc_url(admin_url('admin.php?page=tdwm_main_settings&tdwm_delete_data=true'));
        echo <<<HTML
        <div style="margin-top:2rem;padding:1rem;border:1px solid red;background:#fff0f0;">
            <h3 style="color:red;">Danger Zone</h3>
            <p>This will <strong>delete all plugin data</strong> and reset the plugin to a clean install state. This cannot be undone.</p>
            <a href="{$delete_url}"
               onclick="return confirm('Are you sure you want to delete all plugin data?');"
               style="color:white;background:red;padding:0.5rem 1rem;text-decoration:none;">
               Delete Plugin Data
            </a>
        </div>
HTML;
    }

    echo '</div>';
}

//  Call Board Admin functions to support all tabs
function tdwm_board_admin_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/board-manager-functions.php';
    tdwm_board_admin_tabs();
}

// Add JS for supporting Board Admin tables
add_action('admin_enqueue_scripts', 'tdwm_enqueue_admin_scripts');
function tdwm_enqueue_admin_scripts($hook) {
    // Only load scripts on our plugin's admin pages
    if (strpos($hook, 'tdwm') === false) return;

    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_script(
        'tdwm-notices',
        plugin_dir_url(__FILE__) . 'assets/js/tdwm-notices.js',
        [],
        '1.0',
        true
    );

    wp_enqueue_script(
        'tdwm-sortable',
        plugin_dir_url(__FILE__) . 'assets/js/tdwm-sortable.js',
        ['jquery', 'jquery-ui-sortable'],
        '1.0',
        true
    );

    wp_localize_script('tdwm-sortable', 'tdwm_ajax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}

add_action('wp_ajax_tdwm_update_sort_order', 'tdwm_update_sort_order');
function tdwm_update_sort_order() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['order']) || !is_array($_POST['order'])) {
        wp_send_json_error('Invalid data');
    }

    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';

    foreach ($_POST['order'] as $id => $sort_order) {
        $wpdb->update(
            $roles_table,
            ['sort_order' => intval($sort_order)],
            ['id' => intval($id)]
        );
    }

    wp_send_json_success('Sort order updated');
}