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
    $image = esc_attr($options['login_logo_url'] ?? '');
    ?>
    <div>
        <input type="text" id="snn_login_logo_url" name="snn_settings[login_logo_url]" value="<?php echo $image; ?>" style="width:80%">
        <input type="button" class="button snn-media-upload" data-target="snn_login_logo_url" value="<?php esc_attr_e('Select Image', 'snn'); ?>">
    </div>
    <p><?php _e('Select the logo image for the login page. (.png, .jpg)', 'snn'); ?></p>
    <script>
    jQuery(document).ready(function($){
        $('.snn-media-upload').off('click').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var custom_uploader = wp.media({
                title: '<?php echo esc_js(__('Select or Upload Logo', 'snn')); ?>',
                button: {
                    text: '<?php echo esc_js(__('Use this image', 'snn')); ?>'
                },
                multiple: false
            })
            .on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#' + button.data('target')).val(attachment.url);
            })
            .open();
        });
    });
    </script>
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

// Enqueue WordPress media scripts on settings page only
function snn_admin_enqueue_media($hook) {
    if (isset($_GET['page']) && $_GET['page'] === 'login-settings') {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'snn_admin_enqueue_media');

?>
