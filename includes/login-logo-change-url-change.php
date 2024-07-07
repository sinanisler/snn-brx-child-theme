<?php 

function snn_add_logo_settings() {
    add_settings_field(
        'snn_login_logo_url',
        'Login Logo URL',
        'snn_login_logo_url_callback',
        'snn-settings',
        'snn_general_section'  // Assuming you want to add this to an existing section named 'snn_general_section'
    );

    add_settings_field(
        'snn_custom_logo_url',
        'Custom Logo Link URL',
        'snn_custom_logo_url_callback',
        'snn-settings',
        'snn_general_section'
    );

    register_setting('snn-settings', 'snn_settings', 'snn_settings_sanitize');
}

function snn_login_logo_url_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="text" name="snn_settings[login_logo_url]" value="<?php echo esc_attr($options['login_logo_url'] ?? ''); ?>">
    <p>Enter the URL for the login page logo image.</p>
    <?php
}

function snn_custom_logo_url_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="text" name="snn_settings[custom_logo_url]" value="<?php echo esc_attr($options['custom_logo_url'] ?? ''); ?>">
    <p>Enter the URL where the logo should link on the login page.</p>
    <?php
}

add_action('admin_init', 'snn_add_logo_settings');

function snn_login_enqueue_scripts() {
    $options = get_option('snn_settings');
    $logo_url = $options['login_logo_url'] ?? 'https://paste-your-image-url-here/default.jpg'; 
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('<?php echo esc_url($logo_url); ?>');
            height: 85px;
            width: 320px;
            background-size: 100%;
            background-repeat: no-repeat;
            background-position:center center;
            padding-bottom: 10px;
            border-radius:10px;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'snn_login_enqueue_scripts');

function snn_custom_login_logo_url() {
    $options = get_option('snn_settings');
    return $options['custom_logo_url'] ?? home_url(); // Fallback to home URL
}
add_filter('login_headerurl', 'snn_custom_login_logo_url');
