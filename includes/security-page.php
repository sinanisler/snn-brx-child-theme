<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once SNN_PATH . 'includes/login-math-captcha.php'; 
require_once SNN_PATH . 'includes/disable-xmlrpc.php'; 
require_once SNN_PATH . 'includes/disable-wp-json-if-not-logged-in.php'; 
require_once SNN_PATH . 'includes/disable-file-editing.php'; 
require_once SNN_PATH . 'includes/remove-rss.php'; 
require_once SNN_PATH . 'includes/remove-wp-version.php'; 
require_once SNN_PATH . 'includes/disable-bundled-theme-install.php'; 

function snn_add_security_submenu() {
    add_submenu_page(
        'snn-settings',
        'Security Settings',
        'Security Settings',
        'manage_options',
        'snn-security',
        'snn_security_page_callback'
    );
}
add_action('admin_menu', 'snn_add_security_submenu');

function snn_security_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Security Settings', 'snn' ); ?></h1>
 
        <?php
            settings_errors();
        ?>
 
        <form method="post" action="options.php">
            <?php
                settings_fields( 'snn_security_settings_group' );
                do_settings_sections( 'snn-security' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_security_settings_init() {
    register_setting(
        'snn_security_settings_group',
        'snn_security_options'
    );

    add_settings_section(
        'snn_security_main_section',
        __( 'Main Settings', 'snn' ),
        'snn_security_section_callback',
        'snn-security'
    );

    add_settings_field(
        'enable_math_captcha',
        __( 'Enable Math Captcha for Login', 'snn' ),
        'snn_math_captcha_callback',
        'snn-security',
        'snn_security_main_section'
    );
}
add_action( 'admin_init', 'snn_security_settings_init' );

function snn_security_section_callback() {
    echo '<p>' . esc_html__( 'Configure your security settings below:', 'snn' ) . '</p>';
}

function snn_math_captcha_callback() {
    $options = get_option('snn_security_options');
    ?>
    <style> 
    [type="checkbox"]{
        width: 18px !important;
        height: 18px !important;
        float: left;
        margin-right: 10px !important;
    }
    </style>
    <input type="checkbox" name="snn_security_options[enable_math_captcha]" value="1" <?php checked(isset($options['enable_math_captcha']) && $options['enable_math_captcha'], 1); ?>>
    <p><?php esc_html_e( 'Enable this setting to add a math captcha challenge on the login page to improve security.', 'snn' ); ?></p>
    <?php
}
?>
