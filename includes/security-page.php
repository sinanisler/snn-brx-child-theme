<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include dependent security files
require_once get_stylesheet_directory() . '/includes/login-math-captcha.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/disable-file-editing.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
// Include other security-related files as needed

/**
 * Register the "Security" submenu under "SNN Settings".
 */
function snn_add_security_submenu() {
    add_submenu_page(
        'snn-settings',                // Parent slug (SNN Settings menu)
        'Security Settings',          // Page title
        'Security Settings',                   // Menu title
        'manage_options',             // Capability required to access the page
        'snn-security',               // Menu slug
        'snn_security_page_callback'  // Callback function to render the page
    );
}
add_action('admin_menu', 'snn_add_security_submenu');

/**
 * Callback function to display the "Security" settings page.
 */
function snn_security_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Security Settings', 'snn' ); ?></h1>
        <form method="post" action="options.php">
            <?php
                // Output security fields for the registered setting
                settings_fields( 'snn_security_settings_group' );
                
                // Output setting sections and their fields
                do_settings_sections( 'snn-security' );
                
                // Output save settings button
                submit_button();
            ?>
        </form>
    </div>
    <style>
    [type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
        float: left;
        margin-right: 10px !important;
    }
    </style>
    <?php
}

/**
 * Register settings, sections, and fields for the "Security" page.
 */
function snn_security_settings_init() {
    // Register a new setting for "snn_security_settings_group"
    register_setting(
        'snn_security_settings_group', // Option group
        'snn_security_options'         // Option name
    );

    // Add a new section in the "snn-security" page
    add_settings_section(
        'snn_security_main_section',      // ID
        __( 'Main Settings', 'snn' ),     // Title
        'snn_security_section_callback',  // Callback
        'snn-security'                     // Page
    );

    // Add the math captcha settings field
    add_settings_field(
        'enable_math_captcha',
        __( 'Enable Math Captcha for Login', 'snn' ),
        'snn_math_captcha_callback',
        'snn-security',                // Page
        'snn_security_main_section'    // Section
    );
}
add_action( 'admin_init', 'snn_security_settings_init' );

/**
 * Section callback for the "Main Settings" section.
 */
function snn_security_section_callback() {
    echo '<p>' . esc_html__( 'Configure your security settings below:', 'snn' ) . '</p>';
}

/**
 * Callback function for the math captcha settings field.
 */
function snn_math_captcha_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[enable_math_captcha]" value="1" <?php checked(isset($options['enable_math_captcha']), 1); ?>>
    <p><?php esc_html_e( 'Enable this setting to add a math captcha challenge on the login page to improve security.', 'snn' ); ?></p>
    <?php
}
?>
