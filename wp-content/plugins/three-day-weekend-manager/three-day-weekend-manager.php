<?php
/**
 * Plugin Name: Three-Day Weekend Manager
 * Description: Flexible management plugin for Tres Dias, Camino de Cristo, Vida Nueva, and similar 3-day weekend communities.
 * Version: 1.0
 * Author: Allen Heishman
 * Text Domain: three-day-weekend-manager
 */

defined('ABSPATH') or die('No script kiddies please!');

// Add admin menu and initialize settings
add_action('admin_menu', 'tdwm_add_admin_menu');
add_action('admin_init', 'tdwm_settings_init');

/**
 * Add plugin settings page
 */
function tdwm_add_admin_menu() {
    add_menu_page(
        'Three-Day Weekend Settings',   // Page title
        '3-Day Weekend',                // Menu title
        'manage_options',               // Capability
        'tdwm_settings',                // Menu slug
        'tdwm_settings_page_html',      // Callback
        'dashicons-calendar-alt',       // Icon
        80                              // Position
    );
}

/**
 * Register and initialize plugin settings
 */
function tdwm_settings_init() {
    // Community Name setting
    register_setting('tdwm_settings_group', 'tdwm_community_name');

    // Board Roles (stored as a newline-delimited string)
    register_setting('tdwm_settings_group', 'tdwm_board_roles', [
        'sanitize_callback' => function($input) {
            return sanitize_textarea_field($input);
        }
    ]);

    add_settings_section(
        'tdwm_settings_section_main',
        'General Settings',
        null,
        'tdwm_settings'
    );

    add_settings_field(
        'tdwm_community_name',
        'Community Name',
        'tdwm_community_name_render',
        'tdwm_settings',
        'tdwm_settings_section_main'
    );

    add_settings_field(
        'tdwm_board_roles',
        'Board Roles',
        'tdwm_board_roles_render',
        'tdwm_settings',
        'tdwm_settings_section_main'
    );
}

/**
 * Community Name field
 */
function tdwm_community_name_render() {
    $value = get_option('tdwm_community_name', '');
    echo "<input type='text' name='tdwm_community_name' value='" . esc_attr($value) . "' class='regular-text'>";
}

/**
 * Board Roles field (textarea)
 */
function tdwm_board_roles_render() {
    $raw = get_option('tdwm_board_roles', "President\nVice President\nTreasurer\nSecretary\nSpiritual Director");
    echo "<textarea name='tdwm_board_roles' rows='6' cols='50'>" . esc_textarea($raw) . "</textarea>";
    echo "<p class='description'>Enter one board role per line. Used for admin filters and reports.</p>";
}

/**
 * Admin page layout
 */
function tdwm_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Three-Day Weekend Manager Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('tdwm_settings_group');
            do_settings_sections('tdwm_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}