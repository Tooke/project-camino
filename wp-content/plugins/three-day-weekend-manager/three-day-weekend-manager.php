<?php
/*
Plugin Name: Three-Day Weekend Manager
Description: Manage board roles, assignments, and weekend settings for three-day communities.
Version: 1.4.3
Author: Allen Heishman
*/

if (!defined('ABSPATH')) exit;

// Activation hook
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
        role_name VARCHAR(255) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0
    ) $charset_collate;");

    // Create public-facing page if it doesn't exist
    if (!get_page_by_path('board-members')) {
        wp_insert_post([
            'post_title'    => 'Board Members',
            'post_name'     => 'board-members',
            'post_content'  => '[tdwm_board_members]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ]);
    }
}

// Load admin modules
require_once plugin_dir_path(__FILE__) . 'admin/board-functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/board-manager-tabs.php';
require_once plugin_dir_path(__FILE__) . 'admin/board-members-tab.php';

// Admin menu
add_action('admin_menu', function() {
    add_menu_page(
        '3-Day Manager',         // Page title
        '3-Day Manager',         // Menu title
        'manage_options',
        'tdwm_3day_manager',     // Slug
        '__return_null',         // Callback function (null placeholder)
        'dashicons-calendar-alt',
        3
    );

    add_submenu_page(
        'tdwm_3day_manager',     // Parent slug
        'Board Manager',         // Page title
        'Board Admin',           // Submenu title
        'manage_options',
        'tdwm_board_admin',
        'tdwm_board_admin_tabs'  // Callback
    );

    //  This removes the redundant first submenu
    remove_submenu_page('tdwm_3day_manager', 'tdwm_3day_manager');
}, 99);

// call JS for making notices disappear
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('tdwm-notices', plugin_dir_url(__FILE__) . 'assets/js/tdwm-notices.js', [], null, true);
});

// Public shortcode
add_shortcode('tdwm_board_members', 'tdwm_display_public_board_members');
function tdwm_display_public_board_members() {
    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';
    $assignments_table = $wpdb->prefix . 'tdwm_board_assignments';

    $roles = $wpdb->get_results("SELECT role_name FROM $roles_table ORDER BY sort_order ASC, role_name ASC");

    $output = '<div class="tdwm-board-members">';
    $output .= '<table class="wp-list-table widefat fixed striped"><tbody>';

    foreach ($roles as $role_obj) {
        $role = $role_obj->role_name;
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT u.ID, a.start_date
             FROM $assignments_table a
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.role_name = %s AND a.end_date IS NULL
             ORDER BY a.start_date DESC LIMIT 1", $role));

        if ($assignment) {
            $first = get_user_meta($assignment->ID, 'first_name', true);
            $last = get_user_meta($assignment->ID, 'last_name', true);
            $name = ($first || $last) ? trim("$first $last") : get_userdata($assignment->ID)->display_name;
        } else {
            $name = 'Open Position';
        }

        $output .= '<tr><td>' . esc_html($role) . '</td><td style="width: 40px;"></td><td>' . esc_html($name) . '</td></tr>';
    }

    $output .= '</tbody></table></div>';
    return $output;
}