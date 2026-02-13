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
require_once SNN_PATH . 'includes/limit-login-attempts.php'; 

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

        <?php
        // Clear blocked IPs section
        $blocked_count = snn_get_blocked_ips_count();
        ?>
        <div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php esc_html_e( 'Blocked IPs Management', 'snn' ); ?></h2>
            <p><?php printf( esc_html__( 'Currently blocked IPs: %d', 'snn' ), $blocked_count ); ?></p>
            <form method="post" action="" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all blocked IPs?', 'snn' ); ?>');">
                <?php wp_nonce_field('snn_clear_blocked_ips_action', 'snn_clear_blocked_ips_nonce'); ?>
                <input type="submit" name="snn_clear_blocked_ips" class="button button-secondary" value="<?php esc_attr_e( 'Clear All Blocked IPs', 'snn' ); ?>">
            </form>
        </div>
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

    // Limit Login Attempts settings
    add_settings_field(
        'enable_limit_login',
        __( 'Enable Limit Login Attempts', 'snn' ),
        'snn_limit_login_callback',
        'snn-security',
        'snn_security_main_section'
    );

    add_settings_field(
        'max_login_attempts',
        __( 'Maximum Login Attempts', 'snn' ),
        'snn_max_attempts_callback',
        'snn-security',
        'snn_security_main_section'
    );

    add_settings_field(
        'login_reset_time',
        __( 'Reset Failed Attempts After (hours)', 'snn' ),
        'snn_reset_time_callback',
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
    [type="number"]{
        width: 100px !important;
    }
    </style>
    <input type="checkbox" name="snn_security_options[enable_math_captcha]" value="1" <?php checked(isset($options['enable_math_captcha']) && $options['enable_math_captcha'], 1); ?>>
    <p><?php esc_html_e( 'Enable this setting to add a math captcha challenge on the login page to improve security.', 'snn' ); ?></p>
    <?php
}

function snn_limit_login_callback() {
    $options = get_option('snn_security_options');
    ?>
    <input type="checkbox" name="snn_security_options[enable_limit_login]" value="1" <?php checked(isset($options['enable_limit_login']) && $options['enable_limit_login'], 1); ?>>
    <p><?php esc_html_e( 'Enable this setting to limit login attempts and automatically block IPs with too many failed logins.', 'snn' ); ?></p>
    <?php
}

function snn_max_attempts_callback() {
    $options = get_option('snn_security_options');
    $max_attempts = isset($options['max_login_attempts']) && $options['max_login_attempts'] > 0 ? intval($options['max_login_attempts']) : 5;
    ?>
    <input type="number" name="snn_security_options[max_login_attempts]" value="<?php echo esc_attr($max_attempts); ?>" min="1" max="100" step="1">
    <p><?php esc_html_e( 'Specify the number of failed login attempts before an IP address is blocked. Default: 5', 'snn' ); ?></p>
    <?php
}

function snn_reset_time_callback() {
    $options = get_option('snn_security_options');
    $reset_time = isset($options['login_reset_time']) && $options['login_reset_time'] > 0 ? intval($options['login_reset_time']) : 24;
    ?>
    <input type="number" name="snn_security_options[login_reset_time]" value="<?php echo esc_attr($reset_time); ?>" min="1" max="720" step="1">
    <p><?php esc_html_e( 'Time in hours after which failed login attempts count is reset to 0 and blocked IPs are unblocked. Default: 24 hours (1 day)', 'snn' ); ?></p>
    <?php
}
?>
