<?php
/*
Plugin Name: Three-Day Weekend Manager
Description: Manage board roles, assignments, and weekend settings for three-day communities.
Version: 1.4.4
Author: Allen Heishman
*/

if (!defined('ABSPATH')) exit;

// ─────────────────────────────────────────
// ▶ PLUGIN ACTIVATION: Create Required Tables
// ─────────────────────────────────────────
register_activation_hook(__FILE__, 'tdwm_install');
function tdwm_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$wpdb->prefix}tdwm_board_assignments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        role_name VARCHAR(255) NOT NULL,
        gender VARCHAR(10) DEFAULT NULL,
        start_date DATE NOT NULL,
        end_date DATE DEFAULT NULL
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$wpdb->prefix}tdwm_board_roles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(255) NOT NULL,
        gender_requirement ENUM('Male', 'Female', 'N/A') DEFAULT 'N/A',
        sort_order INT DEFAULT 0
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$wpdb->prefix}tdwm_user_fields (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        meta_key VARCHAR(64) NOT NULL UNIQUE,
        label VARCHAR(128) NOT NULL,
        type VARCHAR(20) NOT NULL DEFAULT 'text',
        is_required TINYINT(1) NOT NULL DEFAULT 0,
        admin_only TINYINT(1) NOT NULL DEFAULT 0,
        options TEXT DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0
    ) $charset_collate;");
}

// ─────────────────────────────────────────
// ▶ OPTIONAL RESET: Drop All Tables (Debug Only)
// ─────────────────────────────────────────
add_action('admin_init', 'tdwm_check_manual_delete_trigger');
function tdwm_check_manual_delete_trigger() {
    if (isset($_GET['tdwm_delete_data']) && is_super_admin()) {
        tdwm_delete_plugin_tables();
        wp_redirect(admin_url('admin.php?page=tdwm_main_settings&tdwm_deleted=true'));
        exit;
    }
}

function tdwm_delete_plugin_tables() {
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'tdwm_board_roles',
        $wpdb->prefix . 'tdwm_board_assignments',
        $wpdb->prefix . 'tdwm_user_fields'
    ];
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// ─────────────────────────────────────────
// ▶ ADMIN MENU STRUCTURE
// ─────────────────────────────────────────
add_action('admin_menu', 'tdwm_register_admin_menu');
function tdwm_register_admin_menu() {
    add_menu_page(
        '3-Day Manager',
        '3-Day Manager',
        'manage_options',
        'tdwm_main_settings',
        '',
        'dashicons-calendar-alt',
        2
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

// ─────────────────────────────────────────
// ▶ ADMIN SETTINGS PAGE (with Tabs)
// ─────────────────────────────────────────
function tdwm_main_page() {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'options';

    echo '<div class="wrap"><h1>3-Day Weekend Manager</h1>';

    if (isset($_GET['tdwm_deleted']) && $_GET['tdwm_deleted'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>All plugin data deleted successfully.</p></div>';
    }

   
    // ─ Tabs
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="?page=tdwm_main_settings&tab=options" class="nav-tab ' . ($tab === 'options' ? 'nav-tab-active' : '') . '">Options</a>';
    echo '<a href="?page=tdwm_main_settings&tab=user_fields" class="nav-tab ' . ($tab === 'user_fields' ? 'nav-tab-active' : '') . '">User Fields</a>';
    echo '</h2>';

    // ─ Load appropriate tab
    if ($tab === 'user_fields') {
        require_once plugin_dir_path(__FILE__) . 'admin/user-fields-tab.php';
        tdwm_render_user_fields_tab();
    } elseif ($tab === 'options') {
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
    }

    echo '</div>';
}

// ─────────────────────────────────────────
// ▶ BOARD ADMIN PAGE
// ─────────────────────────────────────────
function tdwm_board_admin_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/board-manager-functions.php';
    tdwm_board_admin_tabs();
}

// ─────────────────────────────────────────
// ▶ ENQUEUE ADMIN SCRIPTS (JS)
// ─────────────────────────────────────────
add_action('admin_enqueue_scripts', 'tdwm_enqueue_admin_scripts');
function tdwm_enqueue_admin_scripts($hook) {
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

// ─────────────────────────────────────────
// ▶ AJAX HANDLER: Role Sort Order
// ─────────────────────────────────────────
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