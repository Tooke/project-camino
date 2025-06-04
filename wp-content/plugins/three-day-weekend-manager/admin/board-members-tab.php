<?php
function tdwm_board_members_tab() {
    global $wpdb;
    $roles_table = $wpdb->prefix . 'tdwm_board_roles';
    $assignments_table = $wpdb->prefix . 'tdwm_board_assignments';

    // ======================
    // Handle Assign or Edit
    // ======================

    // Assign new board member
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_role_name'])) {
        $user_id = intval($_POST['assign_user_id']);
        $role_name = sanitize_text_field($_POST['assign_role_name']);
        $gender = sanitize_text_field($_POST['assign_gender']);
        $start_date = sanitize_text_field($_POST['assign_start_date']);

        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $assignments_table WHERE user_id = %d AND end_date IS NULL", $user_id
        ));

        if ($conflict) {
            echo '<div class="error"><p>This user is already assigned to an active board role.</p></div>';
        } else {
            $role_conflict = (strtolower($role_name) === 'spiritual director')
                ? $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $assignments_table WHERE role_name = %s AND gender = %s AND end_date IS NULL", $role_name, $gender))
                : $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $assignments_table WHERE role_name = %s AND end_date IS NULL", $role_name));

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

    // Update existing active member
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $new_start = sanitize_text_field($_POST['edit_start_date']);
        $new_end = sanitize_text_field($_POST['edit_end_date']);
        $new_comment = sanitize_text_field($_POST['edit_comment'] ?? '');
        $wpdb->update($assignments_table, [
            'start_date' => $new_start,
            'end_date' => $new_end ?: null,
            'comment'    => $new_comment
        ], ['id' => $id]);
        echo '<div class="updated"><p>Assignment updated.</p></div>';
    }

    $roles = $wpdb->get_results("SELECT id, role_name FROM $roles_table ORDER BY sort_order ASC, role_name ASC");

    // ============================
    // Section: Active Board Members
    // ============================

    echo '<h2>Active Board Members</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Role</th><th>Name</th><th>Gender</th><th>Start Date</th><th>End Date</th><th>Comment</th><th>Action</th></tr></thead><tbody>';

    foreach ($roles as $role) {
        $assigned = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, a.user_id, a.gender, a.start_date, a.end_date, a.comment 
             FROM $assignments_table a 
             WHERE a.role_name = %s AND (a.end_date IS NULL OR a.end_date >= CURDATE())", $role->role_name));
        if (!empty($assigned)) {
            foreach ($assigned as $entry) {
                $first = get_user_meta($entry->user_id, 'first_name', true);
                $last = get_user_meta($entry->user_id, 'last_name', true);
                $full_name = ($first || $last) ? trim("$first $last") : get_userdata($entry->user_id)->display_name;

                echo '<tr>';
                echo '<td>' . esc_html($role->role_name) . '</td>';
                echo '<td>' . esc_html($full_name) . '</td>';
                echo '<td>' . esc_html($entry->gender) . '</td>';
                echo '<form method="post">';
                echo '<input type="hidden" name="edit_id" value="' . esc_attr($entry->id) . '">';
                echo '<td><input type="date" name="edit_start_date" value="' . esc_attr($entry->start_date) . '" required></td>';
                echo '<td><input type="date" name="edit_end_date" value="' . esc_attr($entry->end_date ?? '') . '"></td>';
                echo '<td style="padding-right: 10px;"><input type="text" name="edit_comment" value="' . esc_attr($entry->comment ?? '') . '" placeholder="Comment"></td>';
                echo '<td style="padding-left: 10px;"><input type="submit" class="button" value="Update"></td>';
                echo '</form>';
                echo '</tr>';
            }
        } else {
            echo '<tr>';
            echo '<form method="post">';
            echo '<input type="hidden" name="assign_role_name" value="' . esc_attr($role->role_name) . '">';
            echo '<td>' . esc_html($role->role_name) . '</td>';

            // Name select dropdown
            echo '<td><select name="assign_user_id" required><option value="">Select Name</option>';
// Get IDs of users with active board assignments
$active_user_ids = $wpdb->get_col("SELECT user_id FROM $assignments_table WHERE end_date IS NULL OR end_date >= CURDATE()");

// Fetch users who are NOT currently active
$users = get_users([
    'fields' => ['ID'],
    'orderby' => 'meta_value',
    'meta_key' => 'first_name',
    'order' => 'ASC',
    'exclude' => $active_user_ids
]);
            foreach ($users as $user) {
                $first = get_user_meta($user->ID, 'first_name', true);
                $last = get_user_meta($user->ID, 'last_name', true);
                $name = ($first || $last) ? trim("$first $last") : get_userdata($user->ID)->display_name;
                echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($name) . '</option>';
            }
            echo '</select></td>';

            echo '<td><select name="assign_gender" required>
                    <option value="">Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select></td>';
            echo '<td><input type="date" name="assign_start_date" value="' . esc_attr(date('Y-m-d')) . '" required></td>';
            echo '<td style="padding-right: 10px;"></td><td></td>';
            echo '<td><input type="submit" class="button" value="Assign"></td>';
            echo '</form>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    // ============================
    // Section: Past Board Members
    // ============================

    echo '<h2 style="margin-top: 40px;">Past Board Members</h2>';

    // Handle filters
    $filter_role = isset($_GET['past_role']) ? sanitize_text_field($_GET['past_role']) : '';
    $filter_name = isset($_GET['past_name']) ? sanitize_text_field($_GET['past_name']) : '';
    $filter_gender = isset($_GET['past_gender']) ? sanitize_text_field($_GET['past_gender']) : '';

    echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" style="margin-bottom: 15px;">';
    echo '<input type="hidden" name="page" value="tdwm_board_admin">';
    echo '<select name="past_role"><option value="">All Roles</option>';
    foreach ($roles as $r) {
        $selected = ($r->role_name === $filter_role) ? ' selected' : '';
        echo '<option value="' . esc_attr($r->role_name) . '"' . $selected . '>' . esc_html($r->role_name) . '</option>';
    }
    echo '</select> ';
    echo '<input type="text" name="past_name" placeholder="Search Name" value="' . esc_attr($filter_name) . '"> ';
    echo '<select name="past_gender">
            <option value="">All Genders</option>
            <option value="male"' . selected($filter_gender, 'male', false) . '>Male</option>
            <option value="female"' . selected($filter_gender, 'female', false) . '>Female</option>
          </select> ';
    echo '<input type="submit" class="button" value="Filter">';
    echo '</form>';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Role</th><th>Name</th><th>Gender</th><th>Start Date</th><th>End Date</th><th>Comment</th></tr></thead><tbody>';

    $where_clauses = ["a.end_date IS NOT NULL AND a.end_date < CURDATE()"];
    $params = [];

    if ($filter_role) {
        $where_clauses[] = "a.role_name = %s";
        $params[] = $filter_role;
    }
    if ($filter_gender) {
        $where_clauses[] = "a.gender = %s";
        $params[] = $filter_gender;
    }
    if ($filter_name) {
        $where_clauses[] = "(EXISTS (SELECT 1 FROM {$wpdb->usermeta} m1 WHERE m1.user_id = a.user_id AND m1.meta_key = 'first_name' AND m1.meta_value LIKE %s)
                            OR EXISTS (SELECT 1 FROM {$wpdb->usermeta} m2 WHERE m2.user_id = a.user_id AND m2.meta_key = 'last_name' AND m2.meta_value LIKE %s))";
        $params[] = '%' . $wpdb->esc_like($filter_name) . '%';
        $params[] = '%' . $wpdb->esc_like($filter_name) . '%';
    }

    $sql = "SELECT a.role_name, a.user_id, a.gender, a.start_date, a.end_date, a.comment 
            FROM $assignments_table a 
            WHERE " . implode(' AND ', $where_clauses) . " 
            ORDER BY a.end_date DESC";

    $past_members = $wpdb->get_results($wpdb->prepare($sql, ...$params));

    if (!empty($past_members)) {
        foreach ($past_members as $entry) {
            $first = get_user_meta($entry->user_id, 'first_name', true);
            $last = get_user_meta($entry->user_id, 'last_name', true);
            $full_name = ($first || $last) ? trim("$first $last") : get_userdata($entry->user_id)->display_name;

            echo '<tr>';
            echo '<td>' . esc_html($entry->role_name) . '</td>';
            echo '<td>' . esc_html($full_name) . '</td>';
            echo '<td>' . esc_html($entry->gender) . '</td>';
            echo '<td>' . esc_html($entry->start_date) . '</td>';
            echo '<td>' . esc_html($entry->end_date) . '</td>';
            echo '<td>' . esc_html($entry->comment ?? '') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">No past board members found.</td></tr>';
    }

    echo '</tbody></table>';
}
?>