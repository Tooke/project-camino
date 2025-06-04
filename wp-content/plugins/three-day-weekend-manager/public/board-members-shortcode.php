<?php
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

add_shortcode('tdwm_board_members', 'tdwm_display_public_board_members');