<?php
// command-center.php
// This file sets up a command center with HTMX, simplifying dynamic loading of settings content.

function enqueue_command_center_assets() {
    // Enqueue command center CSS, JavaScript for toggling, and HTMX library
    wp_enqueue_style('command-center-style', get_stylesheet_directory_uri() . '/css/command-center.css');
    wp_enqueue_script('command-center-toggle-js', get_stylesheet_directory_uri() . '/js/command-center-toggle.js', array('jquery'), null, true);
    wp_enqueue_script('htmx', 'https://unpkg.com/htmx.org@1.7.0', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_command_center_assets');
add_action('admin_enqueue_scripts', 'enqueue_command_center_assets');

function command_center_html() {
    // HTML structure for the command center with HTMX-based content loading
    ?>
    <div id="command-center" class="command-center">
        <div class="command-center-header">
            <h3>Command Center</h3>
            <button id="close-command-center" class="command-center-close">X</button>
        </div>
        <div class="command-center-content">
            <button 
                class="command-center-button" 
                hx-get="<?php echo admin_url('admin-ajax.php?action=load_documentation_settings'); ?>" 
                hx-target="#command-center-dynamic-content">
                Documentation
            </button>
            <button 
                class="command-center-button" 
                hx-get="<?php echo admin_url('admin-ajax.php?action=load_custom_fields_settings'); ?>" 
                hx-target="#command-center-dynamic-content">
                Custom Fields
            </button>
            <div id="command-center-dynamic-content" class="command-center-dynamic-content">
                <!-- Dynamic content loaded via HTMX will appear here -->
            </div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'command_center_html');
add_action('admin_footer', 'command_center_html'); // Add to admin footer as well

// AJAX function to load Documentation settings page
function load_documentation_settings() {
    require_once get_stylesheet_directory() . '/includes/documentation-settings-page.php';
    if (function_exists('snn_documentation_page_callback')) {
        snn_documentation_page_callback(); // Display documentation content
    }
    wp_die();
}
add_action('wp_ajax_load_documentation_settings', 'load_documentation_settings');

// AJAX function to load Custom Fields settings page
function load_custom_fields_settings() {
    require_once get_stylesheet_directory() . '/includes/custom-field-settings.php';
    if (function_exists('snn_custom_fields_page_callback')) {
        snn_custom_fields_page_callback(); // Display custom fields content
    }
    wp_die();
}
add_action('wp_ajax_load_custom_fields_settings', 'load_custom_fields_settings');
