<?php 

function snn_add_logo_settings() {
    add_settings_field(
        'snn_login_logo_url',
        __('Login Logo Image URL', 'snn'),
        'snn_login_logo_url_callback',
        'login-settings',
        'ls_login_settings_section'
    );

    add_settings_field(
        'snn_custom_logo_url',
        __('Custom Logo Link Website URL', 'snn'),
        'snn_custom_logo_url_callback',
        'login-settings',
        'ls_login_settings_section'
    );

    register_setting('ls_login_settings_group', 'snn_settings', 'snn_settings_sanitize');
}
add_action('admin_init', 'snn_add_logo_settings');

function snn_settings_sanitize($input) {
    $sanitized = array();

    if (isset($input['login_logo_url'])) {
        $sanitized['login_logo_url'] = esc_url_raw($input['login_logo_url']);
    }

    if (isset($input['custom_logo_url'])) {
        $sanitized['custom_logo_url'] = esc_url_raw($input['custom_logo_url']);
    }

    return $sanitized;
}

function snn_login_logo_url_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="text" name="snn_settings[login_logo_url]" value="<?php echo esc_attr($options['login_logo_url'] ?? ''); ?>" placeholder="<?php esc_attr_e('https://website.com/image.png', 'snn'); ?>" style="width:100%">
    <p><?php _e('Enter the URL for the login page logo image. (.png, .jpg)', 'snn'); ?></p>
    <?php
}

function snn_custom_logo_url_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="text" name="snn_settings[custom_logo_url]" value="<?php echo esc_attr($options['custom_logo_url'] ?? ''); ?>" placeholder="<?php esc_attr_e('https://yourwebsite.com', 'snn'); ?>" style="width:100%">
    <p><?php _e('Enter the URL where the logo should link on the login page.', 'snn'); ?></p>
    <?php
}

function snn_login_enqueue_scripts() {
    $options = get_option('snn_settings');
    $logo_url = !empty($options['login_logo_url']) ? $options['login_logo_url'] : get_bloginfo('url') . '/wp-admin/images/w-logo-blue.png';
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('<?php echo $logo_url; ?>');
            height: 85px;
            width: 320px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center center;
            padding-bottom: 10px;
            border-radius: 10px;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'snn_login_enqueue_scripts');

function snn_custom_login_logo_url() {
    $options = get_option('snn_settings');
    return $options['custom_logo_url'] ?? home_url();
}
add_filter('login_headerurl', 'snn_custom_login_logo_url');

?>
