<?php
/*
Plugin Name: Three-Day Weekend Manager
Description: Manage board roles, assignments, and weekend settings for three-day communities.
Version: 1.3.1
Author: Allen Heishman
*/

if (!defined('ABSPATH')) exit;

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

add_action('admin_init', function() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'tdwm_placeholder') {
        wp_redirect(admin_url('admin.php?page=tdwm_roles_admin'));
        exit;
    }
});

add_action('admin_menu', function() {
    add_menu_page('3-Day Manager', '3-Day Manager', 'manage_options', 'tdwm_placeholder', '__return_null', 'dashicons-calendar-alt', 3);
    add_submenu_page('tdwm_placeholder', 'Board Roles', 'Board Roles', 'manage_options', 'tdwm_roles_admin', 'tdwm_combined_board_roles_page');
    remove_submenu_page('tdwm_placeholder', 'tdwm_placeholder');
}, 99);

function tdwm_combined_board_roles_page() {
    if (!is_super_admin()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';
    $assignments_table = $wpdb->prefix . 'tdwm_board_assignments';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_order']) && is_array($_POST['sort_order'])) {
            foreach ($_POST['sort_order'] as $role_id => $order) {
                if (isset($order) && is_numeric($order)) {
                    $wpdb->update($roles_table, ['sort_order' => intval($order)], ['id' => intval($role_id)]);
                }
            }
            echo '<div class="updated"><p>Sort order updated.</p></div>';
        }

        if (!empty($_POST['new_role'])) {
            $new_role = sanitize_text_field($_POST['new_role']);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $roles_table WHERE role_name = %s", $new_role));
            if (!$exists) {
                $wpdb->insert($roles_table, ['role_name' => $new_role]);
                echo '<div class="updated"><p>Role added successfully.</p></div>';
            } else {
                echo '<div class="error"><p>Role already exists.</p></div>';
            }
        }

        if (!empty($_POST['delete_role'])) {
            $delete_role = sanitize_text_field($_POST['delete_role']);
            $wpdb->delete($roles_table, ['role_name' => $delete_role]);
            echo '<div class="updated"><p>Role removed.</p></div>';
        }

        if (!empty($_POST['user_id']) && !empty($_POST['role_name'])) {
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
    }

    if (!empty($_GET['remove'])) {
        $remove_id = intval($_GET['remove']);
        $wpdb->update($assignments_table, ['end_date' => current_time('mysql')], ['id' => $remove_id]);
        echo '<div class="updated"><p>Role unassigned.</p></div>';
    }

    $roles = $wpdb->get_results("SELECT id, role_name, sort_order FROM $roles_table ORDER BY sort_order ASC, role_name ASC");
    $users = get_users(['fields' => ['ID', 'display_name']]);

    echo '<div class="wrap"><h1>Board Role Management</h1>';

    echo '<h2>Add New Board Role</h2>
        <form method="post">
            <input type="text" name="new_role" required>
            <input type="submit" class="button button-primary" value="Add Role">
        </form>';

    echo '<h2>Existing Roles</h2>';
    if (!empty($roles)) {
        echo '<form method="post"><table class="widefat"><thead><tr><th>Role</th><th>Order</th><th>Actions</th></tr></thead><tbody>';
        foreach ($roles as $role) {
            echo '<tr><td>' . esc_html($role->role_name) . '</td>';
            echo '<td><input type="number" name="sort_order[' . esc_attr($role->id) . ']" value="' . esc_attr($role->sort_order) . '" style="width: 60px;"></td>';
            echo '<td><button type="submit" name="delete_role" value="' . esc_attr($role->role_name) . '" class="button-link-delete" onclick="return confirm(&quot;Are you sure you want to delete this role?&quot;);">Remove</button></td></tr>';
        }
        echo '</tbody></table><p><input type="submit" name="update_order" class="button button-secondary" value="Update Order"></p></form>';
    } else {
        echo '<p>No roles found. Add your first role above.</p>';
    }

    echo '<h2>Assign Board Role</h2>
        <form method="post">
            <select name="user_id" required><option value="">Select User</option>';
    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
    }
    echo '</select>
            <select name="role_name" required><option value="">Select Role</option>';
    foreach ($roles as $role) {
        echo '<option value="' . esc_attr($role->role_name) . '">' . esc_html($role->role_name) . '</option>';
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
            "SELECT a.id, a.user_id, a.gender, a.start_date 
             FROM $assignments_table a 
             WHERE a.role_name = %s AND a.end_date IS NULL", $role->role_name));

        if (!empty($assigned)) {
            foreach ($assigned as $entry) {
                $first = get_user_meta($entry->user_id, 'first_name', true);
                $last = get_user_meta($entry->user_id, 'last_name', true);
                $full_name = ($first || $last) ? trim("$first $last") : get_userdata($entry->user_id)->display_name;
                echo '<tr><td>' . esc_html($role->role_name) . '</td><td>' . esc_html($full_name) . '</td><td>' . esc_html($entry->gender) . '</td><td>' . esc_html($entry->start_date) . '</td><td><a href="?page=tdwm_roles_admin&remove=' . esc_attr($entry->id) . '" onclick="return confirm(&quot;Remove this assignment?&quot;);">Remove</a></td></tr>';
            }
        } else {
            echo '<tr><td>' . esc_html($role->role_name) . '</td><td colspan="4"><strong>Open Position</strong></td></tr>';
        }
    }

    echo '</tbody></table></div>';
}

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
?>