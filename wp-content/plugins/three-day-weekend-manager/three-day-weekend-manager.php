<?php
/*
Plugin Name: Three-Day Weekend Manager
Description: Manage board roles, assignments, and weekend settings for three-day communities.
Version: 1.1
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
        role_name VARCHAR(255) NOT NULL UNIQUE
    ) $charset_collate;");
}

// Redirect main menu to first submenu
add_action('admin_init', function() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'tdwm_placeholder') {
        wp_redirect(admin_url('admin.php?page=tdwm_admin'));
        exit;
    }
});

// Admin menu setup
add_action('admin_menu', function() {
    add_menu_page('3-Day Manager', '3-Day Manager', 'manage_options', 'tdwm_placeholder', '__return_null', 'dashicons-calendar-alt', 3);
    add_submenu_page('tdwm_placeholder', 'Administration', 'Administration', 'manage_options', 'tdwm_admin', 'tdwm_admin_page');
    add_submenu_page('tdwm_placeholder', 'Board Admin', 'Board Admin', 'manage_options', 'tdwm_board_admin', 'tdwm_board_admin_page');

    // Remove the duplicate submenu created by WP
    remove_submenu_page('tdwm_placeholder', 'tdwm_placeholder');
}, 99);



// Admin: Add/Delete board roles
function tdwm_admin_page() {
    if (!is_super_admin()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'tdwm_board_roles';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['new_role'])) {
            $new_role = sanitize_text_field($_POST['new_role']);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE role_name = %s", $new_role));
            if (!$exists) {
                $wpdb->insert($table, ['role_name' => $new_role]);
                echo '<div class="updated"><p>Role added successfully.</p></div>';
            } else {
                echo '<div class="error"><p>Role already exists.</p></div>';
            }
        } elseif (!empty($_POST['delete_role'])) {
            $delete_role = sanitize_text_field($_POST['delete_role']);
            $wpdb->delete($table, ['role_name' => $delete_role]);
            echo '<div class="updated"><p>Role removed.</p></div>';
        }
    }

    $roles = $wpdb->get_col("SELECT role_name FROM $table ORDER BY role_name ASC");

    echo '<div class="wrap"><h1>Board Role Administration</h1>';
    echo '<h2>Add New Board Role</h2>
        <form method="post">
            <input type="text" name="new_role" required>
            <input type="submit" class="button button-primary" value="Add Role">
        </form>';

    echo '<h2>Existing Roles</h2>';
    if (!empty($roles)) {
        echo '<ul>';
        foreach ($roles as $role) {
            echo '<li>' . esc_html($role) . '
                <form method="post" style="display:inline">
                    <input type="hidden" name="delete_role" value="' . esc_attr($role) . '">
                    <input type="submit" class="button-link-delete" value="Remove" onclick="return confirm(\'Are you sure?\');">
                </form></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No roles found. Add your first role above.</p>';
    }
    echo '</div>';
}

// Admin: Assign board roles
function tdwm_board_admin_page() {
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'tdwm_board_assignments';
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
        $user_id = intval($_POST['user_id']);
        $role_name = sanitize_text_field($_POST['role_name']);
        $gender = sanitize_text_field($_POST['gender']);
        $start_date = sanitize_text_field($_POST['start_date']);

        $conflict = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $assignments_table WHERE user_id = %d AND end_date IS NULL", $user_id));
        if ($conflict) {
            echo '<div class="error"><p>This user is already assigned to an active board role.</p></div>';
        } else {
            if (strtolower($role_name) === 'spiritual director') {
                $role_conflict = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $assignments_table WHERE role_name = %s AND gender = %s AND end_date IS NULL", $role_name, $gender));
            } else {
                $role_conflict = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $assignments_table WHERE role_name = %s AND end_date IS NULL", $role_name));
            }

            if ($role_conflict) {
                echo '<div class="error"><p>This role is already filled.</p></div>';
            } else {
                $wpdb->insert($assignments_table, [
                    'user_id' => $user_id,
                    'role_name' => $role_name,
                    'gender' => $gender,
                    'start_date' => $start_date,
                    'end_date' => null
                ]);
                echo '<div class="updated"><p>Assignment saved.</p></div>';
            }
        }
    }

    if (!empty($_GET['remove']) && current_user_can('manage_options')) {
        $remove_id = intval($_GET['remove']);
        $wpdb->update($assignments_table, ['end_date' => current_time('mysql')], ['id' => $remove_id]);
        echo '<div class="updated"><p>Role unassigned.</p></div>';
    }

    $roles = $wpdb->get_col("SELECT role_name FROM $roles_table ORDER BY role_name ASC");
    $users = get_users(['fields' => ['ID', 'display_name']]);

    echo '<div class="wrap"><h1>Assign Board Role</h1>
        <form method="post">
            <select name="user_id" required><option value="">Select User</option>';
    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
    }
    echo '</select>
            <select name="role_name" required><option value="">Select Role</option>';
    foreach ($roles as $role) {
        echo '<option value="' . esc_attr($role) . '">' . esc_html($role) . '</option>';
    }
    echo '</select>
            <select name="gender">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
            <input type="date" name="start_date" value="' . esc_attr(date('Y-m-d')) . '" required>
            <input type="submit" class="button button-primary" value="Assign">
        </form>';

    echo '<h2>Current Board Members</h2>';
    echo '<table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Role</th><th>Name</th><th>Gender</th><th>Start Date</th><th>Action</th></tr></thead><tbody>';

    foreach ($roles as $role) {
        $assigned = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, u.display_name, a.gender, a.start_date 
             FROM $assignments_table a 
             JOIN {$wpdb->users} u ON a.user_id = u.ID 
             WHERE a.role_name = %s AND a.end_date IS NULL", $role));

        if ($assigned) {
            foreach ($assigned as $entry) {
                echo '<tr><td>' . esc_html($role) . '</td><td>' . esc_html($entry->display_name) . '</td><td>' . esc_html($entry->gender) . '</td><td>' . esc_html($entry->start_date) . '</td><td><a href="?page=tdwm_board_admin&remove=' . esc_attr($entry->id) . '" onclick="return confirm(\'Remove this assignment?\');">Remove</a></td></tr>';
            }
        } else {
            echo '<tr><td>' . esc_html($role) . '</td><td colspan="4"><strong>Open Position</strong></td></tr>';
        }
    }

    echo '</tbody></table></div>';
}