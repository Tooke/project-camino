<?php
if (!defined('ABSPATH')) exit;

function tdwm_render_user_fields_tab() {
    global $wpdb;
    $table = $wpdb->prefix . 'tdwm_user_fields';

    // ─ Handle Add Field
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_field'])) {
        $meta_key = sanitize_key($_POST['meta_key']);
        $label = sanitize_text_field($_POST['label']);
        $type = sanitize_text_field($_POST['type']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $admin_only = isset($_POST['admin_only']) ? 1 : 0;
        $options = !empty($_POST['options']) ? sanitize_text_field($_POST['options']) : null;

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE meta_key = %s", $meta_key));
        if ($exists) {
            echo '<div class="notice notice-error"><p>Meta key already exists.</p></div>';
        } else {
            $wpdb->insert($table, [
                'meta_key' => $meta_key,
                'label' => $label,
                'type' => $type,
                'is_required' => $is_required,
                'admin_only' => $admin_only,
                'options' => $options,
                'sort_order' => 0
            ]);
            echo '<div class="notice notice-success is-dismissible"><p>Field added successfully.</p></div>';
        }
    }

    // ─ Handle Delete
    if (isset($_GET['delete_field'])) {
        $id = intval($_GET['delete_field']);
        $wpdb->delete($table, ['id' => $id]);
        echo '<div class="notice notice-success is-dismissible"><p>Field deleted.</p></div>';
    }

    // ─ Handle Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_field'])) {
        $id = intval($_POST['id']);
        $label = sanitize_text_field($_POST['label']);
        $type = sanitize_text_field($_POST['type']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $admin_only = isset($_POST['admin_only']) ? 1 : 0;
        $options = !empty($_POST['options']) ? sanitize_text_field($_POST['options']) : null;

        $wpdb->update($table, [
            'label' => $label,
            'type' => $type,
            'is_required' => $is_required,
            'admin_only' => $admin_only,
            'options' => $options
        ], ['id' => $id]);

        echo '<div class="notice notice-success is-dismissible"><p>Field updated.</p></div>';
    }

    $editing_id = isset($_GET['edit_field']) ? intval($_GET['edit_field']) : null;

    // ─ Load all fields
    $fields = $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order ASC, label ASC");
	
    echo '<div class="wrap"><h2>Manage Custom User Fields</h2>';
	echo '<h4>Default WordPress User Profile Fields</h4>';
	echo '<ul>
    	<li><code>user_login</code> – Username (cannot be changed)</li>
    	<li><code>user_email</code> – Email Address</li>
    	<li><code>first_name</code> – First Name</li>
    	<li><code>last_name</code> – Last Name</li>
    	<li><code>nickname</code> – Nickname</li>
    	<li><code>display_name</code> – Display Name</li>
    	<li><code>description</code> – Bio</li>
    	<li><code>user_url</code> – Website</li>
	</ul>';
	echo '<p><em>You do not need to manually add these fields. They are part of the standard WordPress user profile.</em></p>';
    echo '<h3>Add New Field</h3>
    <form method="post">
        <table class="form-table">
            <tr>
                <th><label for="label">Field Label</label></th>
                <td><input type="text" name="label" id="label" required></td>
            </tr>
            <tr>
                <th><label for="meta_key">Meta Key</label></th>
                <td><input type="text" name="meta_key" id="meta_key" required></td>
            </tr>
            <tr>
                <th><label for="type">Field Type</label></th>
                <td>
                    <select name="type" id="type">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="date">Date</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="options">Options (for select)</label></th>
                <td><input type="text" name="options" id="options" placeholder="Comma-separated"></td>
            </tr>
            <tr>
                <th>Flags</th>
                <td>
                    <label><input type="checkbox" name="is_required"> Required</label><br>
                    <label><input type="checkbox" name="admin_only"> Admin Only</label>
                </td>
            </tr>
        </table>
        <p><input type="submit" name="add_user_field" class="button button-primary" value="Add Field"></p>
    </form>';

    echo '<hr><h3>Existing Fields</h3>';
    if ($fields) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Label</th><th>Key</th><th>Type</th><th>Required</th><th>Admin Only</th><th>Options</th><th>Actions</th></tr></thead><tbody>';
        foreach ($fields as $field) {
            if ($editing_id === intval($field->id)) {
                echo '<tr><form method="post">';
                echo '<td><input type="text" name="label" value="' . esc_attr($field->label) . '" required></td>';
                echo '<td><code>' . esc_html($field->meta_key) . '</code><input type="hidden" name="id" value="' . esc_attr($field->id) . '"></td>';
                echo '<td><select name="type">
                        <option value="text" ' . selected($field->type, 'text', false) . '>Text</option>
                        <option value="textarea" ' . selected($field->type, 'textarea', false) . '>Textarea</option>
                        <option value="select" ' . selected($field->type, 'select', false) . '>Select</option>
                        <option value="checkbox" ' . selected($field->type, 'checkbox', false) . '>Checkbox</option>
                      </select></td>';
                echo '<td><input type="checkbox" name="is_required" ' . checked($field->is_required, 1, false) . '></td>';
                echo '<td><input type="checkbox" name="admin_only" ' . checked($field->admin_only, 1, false) . '></td>';
                echo '<td><input type="text" name="options" value="' . esc_attr($field->options) . '"></td>';
                echo '<td>
                        <input type="submit" name="update_user_field" class="button button-primary" value="Save">
                        <a href="?page=tdwm_main_settings&tab=user_fields" class="button">Cancel</a>
                      </td>';
                echo '</form></tr>';
            } else {
                echo '<tr>';
                echo '<td>' . esc_html($field->label) . '</td>';
                echo '<td><code>' . esc_html($field->meta_key) . '</code></td>';
                echo '<td>' . esc_html($field->type) . '</td>';
                echo '<td>' . ($field->is_required ? 'Yes' : 'No') . '</td>';
                echo '<td>' . ($field->admin_only ? 'Yes' : 'No') . '</td>';
                echo '<td>' . esc_html($field->options) . '</td>';
                echo '<td>
    				<a href="?page=tdwm_main_settings&tab=user_fields&edit_field=' . intval($field->id) . '" class="button button-small">Edit</a>
    				<a href="?page=tdwm_main_settings&tab=user_fields&delete_field=' . intval($field->id) . '" class="button button-small" onclick="return confirm(\'Delete this field?\');">Delete</a>
				</td>';
            }
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No user fields defined yet.</p>';
    }

    echo '</div>';
}
?>
