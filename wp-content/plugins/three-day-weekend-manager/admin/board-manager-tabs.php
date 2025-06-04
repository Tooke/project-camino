<?php
if (!defined('ABSPATH')) exit;



// Main callback function for tabbed view
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

        $roles = $wpdb->get_results("SELECT id, role_name, sort_order FROM $roles_table ORDER BY sort_order ASC, role_name ASC");
        echo '<h2>Add New Board Function</h2>
        <form method="post">
            <input type="text" name="new_role" required>
            <input type="submit" class="button button-primary" value="Add Function">
        </form>';

        echo '<h2>Active Board Functions</h2>';
        if (!empty($roles)) {
            echo '<form method="post"><table class="widefat"><thead><tr><th>Role</th><th>Order</th><th>Actions</th></tr></thead><tbody>';
            foreach ($roles as $role) {
                echo '<tr><td>' . esc_html($role->role_name) . '</td>';
                echo '<td><input type="number" name="sort_order[' . esc_attr($role->id) . ']" value="' . esc_attr($role->sort_order) . '" style="width: 60px;"></td>';
                echo '<td><button type="submit" name="delete_role" value="' . esc_attr($role->role_name) . '" class="button-link-delete" onclick="return confirm(&quot;Are you sure you want to delete this role?&quot;);">Remove Function</button></td></tr>';
            }
            echo '</tbody></table><p><input type="submit" name="update_order" class="button button-secondary" value="Update Order"></p></form>';
        } else {
            echo '<p>No roles found. Add your first role above.</p>';
        }

    } else {
        // Board Members tab
        tdwm_board_members_tab();
    }

    echo '</div>';
}