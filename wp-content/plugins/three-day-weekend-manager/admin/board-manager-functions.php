<?php
if (!defined('ABSPATH')) exit;

function tdwm_board_admin_tabs() {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'functions';

    echo '<div class="wrap">';
    echo '<h1>Board Leadership Management</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="?page=tdwm_board_admin&tab=functions" class="nav-tab ' . ($tab === 'functions' ? 'nav-tab-active' : '') . '">Board Functions</a>';
    echo '<a href="?page=tdwm_board_admin&tab=members" class="nav-tab ' . ($tab === 'members' ? 'nav-tab-active' : '') . '">Active Board Members</a>';
    echo '<a href="?page=tdwm_board_admin&tab=history" class="nav-tab ' . ($tab === 'history' ? 'nav-tab-active' : '') . '">Role History</a>';
    echo '</h2>';

    switch ($tab) {
        case 'functions':
            require_once __DIR__ . '/board-functions-tab.php';
            tdwm_handle_board_function_post();
            tdwm_render_board_functions_ui();
            break;
        case 'members':
            require_once __DIR__ . '/board-members-tab.php';
            tdwm_board_members_tab();
            break;
        case 'history':
            require_once __DIR__ . '/board-history-tab.php';
            break;
        default:
            echo '<p>Invalid tab selection.</p>';
            break;
    }

    echo '</div>';
}