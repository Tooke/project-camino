<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'board-functions.php';

function tdwm_board_admin_tabs() {
    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';

    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'members';

    echo '<div class="wrap"><h1>Board Leadership Management</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="?page=tdwm_board_admin&tab=functions" class="nav-tab ' . ($tab === 'functions' ? 'nav-tab-active' : '') . '">Board Functions</a>';
    echo '<a href="?page=tdwm_board_admin&tab=members" class="nav-tab ' . ($tab === 'members' ? 'nav-tab-active' : '') . '">Board Members</a>';
    echo '</h2>';

    if ($tab === 'functions') {
        tdwm_handle_board_function_post();

        $roles = $wpdb->get_results("SELECT id, role_name, sort_order, gender_requirement FROM $roles_table ORDER BY sort_order ASC, role_name ASC");

        echo '<h2>Add New Board Function</h2>
        <form method="post">
            <input type="text" name="new_role" required placeholder="Role Name">
            <select name="gender_requirement">
                <option value="N/A">N/A (Any gender allowed)</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            <input type="submit" name="add_role" class="button button-primary" value="Add Function">
        </form>';

        echo '<h2>Active Board Functions</h2>';
        if (!empty($roles)) {
            echo '<form method="post"><table class="widefat"><thead><tr><th>Role</th><th>Gender Req.</th><th>Order</th><th>Actions</th></tr></thead><tbody>';
            foreach ($roles as $role) {
                echo '<tr>';
                echo '<td>' . esc_html($role->role_name) . '</td>';

                // Inline editable gender requirement
                echo '<td>
                        <form method="post" style="display: flex; gap: 6px; align-items: center;">
                            <input type="hidden" name="role_id" value="' . esc_attr($role->id) . '">
                            <select name="gender_requirement">
                                <option value="N/A"' . selected($role->gender_requirement, 'N/A', false) . '>N/A</option>
                                <option value="Male"' . selected($role->gender_requirement, 'Male', false) . '>Male</option>
                                <option value="Female"' . selected($role->gender_requirement, 'Female', false) . '>Female</option>
                            </select>
                            <input type="submit" name="update_gender" class="button button-small" value="Save">
                        </form>
                      </td>';

                echo '<td><input type="number" name="sort_order[' . esc_attr($role->id) . ']" value="' . esc_attr($role->sort_order) . '" style="width: 60px;"></td>';

                echo '<td><button type="submit" name="delete_role" value="' . esc_attr($role->role_name) . '" class="button-link-delete" onclick="return confirm(\'Are you sure you want to delete this role?\');">Remove Function</button></td>';
                echo '</tr>';
            }
            echo '</tbody></table><p><input type="submit" name="update_order" class="button button-secondary" value="Update Order"></p></form>';
        } else {
            echo '<p>No roles found. Add your first role above.</p>';
        }

    } else {
        tdwm_board_members_tab(); // Assuming this is already implemented
    }

    echo '</div>';
}