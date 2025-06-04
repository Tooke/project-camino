<?php
if (!defined('ABSPATH')) exit;

function tdwm_handle_board_function_post() {
    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add new role
        if (!empty($_POST['add_role']) && !empty($_POST['new_role'])) {
            $new_role = sanitize_text_field($_POST['new_role']);
            $gender_req = in_array($_POST['gender_requirement'], ['Male', 'Female', 'N/A']) ? $_POST['gender_requirement'] : 'N/A';

            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $roles_table WHERE role_name = %s", $new_role));
            if (!$exists) {
                $wpdb->insert($roles_table, [
                    'role_name' => $new_role,
                    'gender_requirement' => $gender_req
                ]);
                echo '<div class="updated notice is-dismissible"><p>Role added successfully.</p></div>';
            } else {
                echo '<div class="error notice is-dismissible"><p>Role already exists.</p></div>';
            }
        }

        // Update sort order
        if (!empty($_POST['update_order']) && is_array($_POST['sort_order'])) {
            foreach ($_POST['sort_order'] as $role_id => $order) {
                if (is_numeric($order)) {
                    $wpdb->update($roles_table, ['sort_order' => intval($order)], ['id' => intval($role_id)]);
                }
            }
            echo '<div class="updated notice is-dismissible"><p>Sort order updated.</p></div>';
        }

        // Delete role
        if (!empty($_POST['delete_role'])) {
            $delete_role = sanitize_text_field($_POST['delete_role']);
            $wpdb->delete($roles_table, ['role_name' => $delete_role]);
            echo '<div class="updated notice is-dismissible"><p>Role removed.</p></div>';
        }

        // Update gender requirement for existing role
        if (!empty($_POST['update_gender']) && isset($_POST['role_id']) && isset($_POST['gender_requirement'])) {
            $role_id = intval($_POST['role_id']);
            $new_gender = in_array($_POST['gender_requirement'], ['Male', 'Female', 'N/A']) ? $_POST['gender_requirement'] : 'N/A';

            $wpdb->update($roles_table, ['gender_requirement' => $new_gender], ['id' => $role_id]);
            echo '<div class="updated notice is-dismissible"><p>Gender requirement updated.</p></div>';
        }
    }
}