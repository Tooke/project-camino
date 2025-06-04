<?php
if (!defined('ABSPATH')) exit;

function tdwm_handle_board_function_post() {
    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';

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
    }
}
