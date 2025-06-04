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

// Render the Board Functions Tab
function tdwm_render_board_functions_ui() {
    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';
    $roles = $wpdb->get_results("SELECT * FROM $roles_table ORDER BY sort_order ASC, role_name ASC");
    ?>

    <style>
    .tdwm-role-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .tdwm-role-row .drag-icon {
        cursor: move;
    }
    </style>

    <h2>Add New Board Role</h2>
    <form method="post">
        <table class="form-table">
            <tr>
                <th><label for="new_role">Role Name</label></th>
                <td><input type="text" name="new_role" id="new_role" required /></td>
            </tr>
            <tr>
                <th><label for="gender_requirement">Gender Requirement</label></th>
                <td>
                    <select name="gender_requirement" id="gender_requirement" required>
                        <option value="N/A">N/A</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </td>
            </tr>
        </table>
        <p><input type="submit" name="add_role" class="button button-primary" value="Add Role" /></p>
    </form>

    <hr>

    <h2>Existing Board Roles</h2>
    <table class="widefat fixed striped" id="tdwm-sortable-roles">
        <thead>
            <tr>
                <th>Role Name</th>
                <th>Gender Requirement</th>
                <th>Update Gender</th>
                <th>Delete Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr data-role-id="<?php echo esc_attr($role->id); ?>">
                    <td>
                        <div class="tdwm-role-row">
                            <span class="drag-icon">⬍</span>
                            <span><?php echo esc_html($role->role_name); ?></span>
                        </div>
                    </td>
                    <td><?php echo esc_html($role->gender_requirement); ?></td>
                    <td>
                        <form method="post" style="display: inline-block;">
                            <select name="gender_requirement">
                                <option value="N/A" <?php selected($role->gender_requirement, 'N/A'); ?>>N/A</option>
                                <option value="Male" <?php selected($role->gender_requirement, 'Male'); ?>>Male</option>
                                <option value="Female" <?php selected($role->gender_requirement, 'Female'); ?>>Female</option>
                            </select>
                            <input type="hidden" name="role_id" value="<?php echo esc_attr($role->id); ?>" />
                            <input type="submit" name="update_gender" class="button" value="Update" />
                        </form>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this role?');" style="display: inline-block;">
                            <input type="hidden" name="delete_role" value="<?php echo esc_attr($role->role_name); ?>" />
                            <input type="submit" class="button button-secondary" value="Delete" />
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            document.querySelectorAll('.notice.is-dismissible').forEach(function (el) {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);
    });
    </script>
    <?php
}