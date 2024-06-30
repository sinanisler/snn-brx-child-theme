<?php

function snn_add_menu_page() {
    add_menu_page(
        'SNN Settings', // Page title
        'SNN Settings', // Menu title
        'manage_options', // Capability
        'snn-settings', // Menu slug
        'snn_settings_page_callback', // Function
        '', // Icon URL (optional)
        99 // Position (optional, set to a high number to make it the last item)
    );
}
add_action('admin_menu', 'snn_add_menu_page');

function snn_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>SNN Bricks Builder Child Theme Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_settings_group');
            do_settings_sections('snn-settings');
            submit_button();
            ?>
        </form>
    </div>

    <style>
        .tt1  {
            width: 880px;
            height: 40px;
        }
        .style_css {
            width: 880px;
            height: 140px;
        }
        [type="checkbox"] {
            width: 20px !important;
            height: 20px !important;
            float: left;
            margin-right: 10px !important;
        }
    </style>
    <?php
}

function snn_register_settings() {
    register_setting('snn_settings_group', 'snn_settings');

    add_settings_section(
        'snn_general_section',
        'Enable or Disable Settings Depending on Your Project Needs and Requirements',
        'snn_general_section_callback',
        'snn-settings'
    );
}
add_action('admin_init', 'snn_register_settings');

function snn_general_section_callback() {
    echo '<br><br>';
}
?>
