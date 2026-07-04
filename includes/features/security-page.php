<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once SNN_PATH . 'includes/features/login-math-captcha.php'; 
require_once SNN_PATH . 'includes/features/turnstile-captcha.php'; 
require_once SNN_PATH . 'includes/features/disable-xmlrpc.php'; 
require_once SNN_PATH . 'includes/features/disable-wp-json-if-not-logged-in.php'; 
require_once SNN_PATH . 'includes/features/disable-file-editing.php'; 
require_once SNN_PATH . 'includes/features/remove-rss.php'; 
require_once SNN_PATH . 'includes/features/remove-wp-version.php'; 
require_once SNN_PATH . 'includes/features/disable-bundled-theme-install.php';
require_once SNN_PATH . 'includes/features/limit-login-attempts.php';
require_once SNN_PATH . 'includes/features/login-url-security.php';

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

    <script type="text/javascript">
        (function() {
            const typeSelect = document.getElementById('snn_captcha_type');
            if (!typeSelect) return;

            const turnstileRows = document.querySelectorAll('.snn-turnstile-row');
            // Turnstile key fields are inside .snn-turnstile-row divs; find their parent <tr> elements
            const turnstileTRs = new Set();
            turnstileRows.forEach(row => {
                const tr = row.closest('tr');
                if (tr) turnstileTRs.add(tr);
            });

            function toggleTurnstileFields() {
                const show = typeSelect.value === 'turnstile';
                turnstileTRs.forEach(tr => {
                    tr.style.display = show ? '' : 'none';
                });
            }

            typeSelect.addEventListener('change', toggleTurnstileFields);
            toggleTurnstileFields();
        })();
    </script>
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
        'captcha_type',
        __( 'Captcha Protection', 'snn' ),
        'snn_captcha_type_callback',
        'snn-security',
        'snn_security_main_section'
    );

    add_settings_field(
        'turnstile_site_key',
        __( 'Turnstile Site Key', 'snn' ),
        'snn_turnstile_site_key_callback',
        'snn-security',
        'snn_security_main_section'
    );

    add_settings_field(
        'turnstile_secret_key',
        __( 'Turnstile Secret Key', 'snn' ),
        'snn_turnstile_secret_key_callback',
        'snn-security',
        'snn_security_main_section'
    );

    add_settings_field(
        'turnstile_theme',
        __( 'Turnstile Theme', 'snn' ),
        'snn_turnstile_theme_callback',
        'snn-security',
        'snn_security_main_section'
    );

    add_settings_field(
        'turnstile_size',
        __( 'Turnstile Widget Size', 'snn' ),
        'snn_turnstile_size_callback',
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

function snn_captcha_type_callback() {
    $options = get_option('snn_security_options');
    $captcha_type = $options['captcha_type'] ?? 'none';
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
    .snn-turnstile-row {  }
    .snn-turnstile-row label { display: inline-block; min-width: 140px; font-weight: 600; }
    .snn-turnstile-row input[type="text"], .snn-turnstile-row input[type="password"] { width: 320px; }
    </style>
    <select name="snn_security_options[captcha_type]" id="snn_captcha_type">
        <option value="none" <?php selected($captcha_type, 'none'); ?>><?php esc_html_e( 'None (Disabled)', 'snn' ); ?></option>
        <option value="math" <?php selected($captcha_type, 'math'); ?>><?php esc_html_e( 'Simple Math Captcha', 'snn' ); ?></option>
        <option value="turnstile" <?php selected($captcha_type, 'turnstile'); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'snn' ); ?></option>
    </select>
    <p class="description"><?php esc_html_e( 'Choose the type of captcha protection for login, registration, lost password, and comment forms. Default: None.', 'snn' ); ?></p>
    <?php
}

function snn_turnstile_site_key_callback() {
    $options = get_option('snn_security_options');
    $site_key = $options['turnstile_site_key'] ?? '';
    ?>
    <div class="snn-turnstile-row">
        <input type="text" name="snn_security_options[turnstile_site_key]" value="<?php echo esc_attr($site_key); ?>" placeholder="1x00000000000000000000">
        <p class="description"><?php esc_html_e( 'Your Turnstile site key from the Cloudflare dashboard. Used for the front-end widget.', 'snn' ); ?></p>
    </div>
    <?php
}

function snn_turnstile_secret_key_callback() {
    $options = get_option('snn_security_options');
    $secret_key = $options['turnstile_secret_key'] ?? '';
    ?>
    <div class="snn-turnstile-row">
        <input type="password" name="snn_security_options[turnstile_secret_key]" value="<?php echo esc_attr($secret_key); ?>" placeholder="1x0000000000000000000000000000000">
        <p class="description"><?php esc_html_e( 'Your Turnstile secret key. Keep this private — it is used for server-side validation.', 'snn' ); ?></p>
    </div>
    <?php
}

function snn_turnstile_theme_callback() {
    $options = get_option('snn_security_options');
    $theme = $options['turnstile_theme'] ?? 'auto';
    ?>
    <div class="snn-turnstile-row">
        <fieldset>
            <label style="margin-right: 20px;">
                <input type="radio" name="snn_security_options[turnstile_theme]" value="auto" <?php checked($theme, 'auto'); ?>>
                <?php esc_html_e( 'Auto', 'snn' ); ?>
            </label>
            <label style="margin-right: 20px;">
                <input type="radio" name="snn_security_options[turnstile_theme]" value="light" <?php checked($theme, 'light'); ?>>
                <?php esc_html_e( 'Light', 'snn' ); ?>
            </label>
            <label>
                <input type="radio" name="snn_security_options[turnstile_theme]" value="dark" <?php checked($theme, 'dark'); ?>>
                <?php esc_html_e( 'Dark', 'snn' ); ?>
            </label>
        </fieldset>
        <p class="description"><?php esc_html_e( 'Visual color theme for the Turnstile widget. Auto follows the user\'s system preference.', 'snn' ); ?></p>
    </div>
    <?php
}

function snn_turnstile_size_callback() {
    $options = get_option('snn_security_options');
    $size = $options['turnstile_size'] ?? 'normal';
    ?>
    <div class="snn-turnstile-row">
        <fieldset>
            <label style="margin-right: 20px;">
                <input type="radio" name="snn_security_options[turnstile_size]" value="normal" <?php checked($size, 'normal'); ?>>
                <?php esc_html_e( 'Normal (300px)', 'snn' ); ?>
            </label>
            <label>
                <input type="radio" name="snn_security_options[turnstile_size]" value="compact" <?php checked($size, 'compact'); ?>>
                <?php esc_html_e( 'Compact (130px)', 'snn' ); ?>
            </label>
        </fieldset>
        <p class="description"><?php esc_html_e( 'Widget size. Use Compact if the widget overflows narrow form containers.', 'snn' ); ?></p>
    </div>
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
    <div style="padding-left: 40px;">
        <input type="number" name="snn_security_options[max_login_attempts]" value="<?php echo esc_attr($max_attempts); ?>" min="1" max="100" step="1">
        <p><?php esc_html_e( 'Specify the number of failed login attempts before an IP address is blocked. Default: 5', 'snn' ); ?></p>
    </div>
    <?php
}

function snn_reset_time_callback() {
    $options = get_option('snn_security_options');
    $reset_time = isset($options['login_reset_time']) && $options['login_reset_time'] > 0 ? intval($options['login_reset_time']) : 24;
    ?>
    <div style="padding-left: 40px;">
        <input type="number" name="snn_security_options[login_reset_time]" value="<?php echo esc_attr($reset_time); ?>" min="1" max="720" step="1">
        <p><?php esc_html_e( 'Time in hours after which failed login attempts count is reset to 0 and blocked IPs are unblocked. Default: 24 hours (1 day)', 'snn' ); ?></p>
    </div>
    <?php
}

/**
 * Migrate old enable_math_captcha setting to new captcha_type setting.
 * Runs once when the options are loaded and captcha_type is not yet set.
 */
function snn_migrate_captcha_setting() {
    $options = get_option( 'snn_security_options', array() );

    // Only migrate if captcha_type hasn't been set yet
    if ( ! isset( $options['captcha_type'] ) ) {
        if ( ! empty( $options['enable_math_captcha'] ) ) {
            $options['captcha_type'] = 'math';
        } else {
            $options['captcha_type'] = 'none';
        }
        update_option( 'snn_security_options', $options );
    }
}
add_action( 'admin_init', 'snn_migrate_captcha_setting', 5 );
?>
