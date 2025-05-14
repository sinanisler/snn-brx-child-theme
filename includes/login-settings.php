<?php

add_action('admin_menu', 'ls_add_login_settings_submenu');

function ls_add_login_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __('Login Settings', 'snn'),
        __('Login Settings', 'snn'),
        'manage_options',
        'login-settings',
        'ls_render_login_settings'
    );
}

add_action('admin_init', 'ls_register_login_settings');

function ls_register_login_settings() {
    register_setting('ls_login_settings_group', 'ls_login_background_image_url', [
        'sanitize_callback' => 'esc_url_raw',
    ]);

    register_setting('ls_login_settings_group', 'ls_login_custom_text', [
        'sanitize_callback' => 'wp_kses_post',
    ]);

    register_setting('ls_login_settings_group', 'ls_login_redirect_url', [
        'sanitize_callback' => 'esc_url_raw',
    ]);

    add_settings_section(
        'ls_login_settings_section',
        __('Login Page Customizations', 'snn'),
        'ls_login_settings_section_callback',
        'login-settings'
    );

    add_settings_section(
        'ls_login_redirect_section',
        __('Redirect Setting', 'snn'),
        'ls_login_redirect_section_callback',
        'login-settings'
    );

    add_settings_field(
        'ls_login_background_image_url',
        __('Background Image', 'snn'),
        'ls_login_background_image_url_callback',
        'login-settings',
        'ls_login_settings_section'
    );

    add_settings_field(
        'ls_login_custom_text',
        __('Custom Text under Login Form', 'snn'),
        'ls_login_custom_text_callback',
        'login-settings',
        'ls_login_settings_section'
    );

    add_settings_field(
        'ls_login_redirect_url',
        __('Redirect URL after Login', 'snn'),
        'ls_login_redirect_url_callback',
        'login-settings',
        'ls_login_redirect_section'
    );
}

function ls_login_settings_section_callback() {
    echo '<p>' . __('Customize the login page with your own background image and custom text.', 'snn') . '</p>';
}

function ls_login_redirect_section_callback() {
    // No text needed here
}

// Media uploader for background image
function ls_login_background_image_url_callback() {
    $image_url = get_option('ls_login_background_image_url', '');
    ?>
    <div style="max-width: 100%;">
        <div style="display:flex">
            <input type="text" id="ls_login_background_image_url" name="ls_login_background_image_url" value="<?php echo esc_attr($image_url); ?>" style="width: 80%;" placeholder="<?php esc_attr_e('Select an image or paste a URL', 'snn'); ?>" />
            <button type="button" class="button" id="ls_upload_bg_img_btn"><?php _e('Select Image', 'snn'); ?></button>
        </div>
        <p class="description"><?php _e('Select a background image for the login page. Leave blank to disable the background image.', 'snn'); ?></p>
    </div>
    <script>
    (function($){
        $(document).ready(function(){
            var custom_uploader;
            $('#ls_upload_bg_img_btn').on('click', function(e) {
                e.preventDefault();
                if (custom_uploader) {
                    custom_uploader.open();
                    return;
                }
                custom_uploader = wp.media({
                    title: '<?php echo esc_js(__('Select or Upload Image', 'snn')); ?>',
                    button: {
                        text: '<?php echo esc_js(__('Use this image', 'snn')); ?>'
                    },
                    multiple: false
                });
                custom_uploader.on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#ls_login_background_image_url').val(attachment.url);
                    $('#ls_login_bg_img_preview').html('<img src="' + attachment.url + '" style="max-width:100%;height:auto;" />').show();
                });
                custom_uploader.open();
            });
            $('#ls_login_background_image_url').on('input', function() {
                var val = $(this).val();
                if (val) {
                    $('#ls_login_bg_img_preview').html('<img src="' + val + '" style="max-width:100%;height:auto;" />').show();
                } else {
                    $('#ls_login_bg_img_preview').hide();
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}

function ls_login_custom_text_callback() {
    $custom_text = get_option('ls_login_custom_text', '');
    ?>
    <textarea id="ls_login_custom_text" name="ls_login_custom_text" rows="5" style="width:100%;" placeholder="<?php esc_attr_e('Enter your custom text here. HTML tags are allowed.', 'snn'); ?>"><?php echo esc_textarea($custom_text); ?></textarea>
    <p class="description"><?php _e('You can use HTML tags in this text.', 'snn'); ?></p>
    <?php
}

function ls_login_redirect_url_callback() {
    $redirect_url = get_option('ls_login_redirect_url', '');
    ?>
    <input type="text" id="ls_login_redirect_url" name="ls_login_redirect_url" value="<?php echo esc_attr($redirect_url); ?>" style="width: 100%;" placeholder="<?php esc_attr_e('https://example.com/redirect-path', 'snn'); ?>" />
    <p class="description"><?php _e('Enter the full URL where users should be redirected after logging in. Leave blank to disable custom redirect.', 'snn'); ?></p>
    <?php
}

function ls_render_login_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('ls_messages', 'ls_message', __('Settings Saved', 'snn'), 'updated');
    }

    settings_errors('ls_messages');
    ?>
    <div class="wrap">
        <h1><?php _e('Login Settings', 'snn'); ?></h1>
        <form method="post" action="options.php" style="max-width:800px">
            <?php
                settings_fields('ls_login_settings_group');
                do_settings_sections('login-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'snn-settings_page_login-settings' || $hook === 'toplevel_page_login-settings') {
        wp_enqueue_media();
    }
});

add_action('login_footer', 'ls_add_custom_login_footer');

function ls_add_custom_login_footer() {
    $background_image_url = get_option('ls_login_background_image_url', '');
    $custom_text = get_option('ls_login_custom_text', '');
    $redirect_url = get_option('ls_login_redirect_url', '');

    $custom_text = wp_kses_post($custom_text);

    echo '
    <div class="ls-terms">
        ' . $custom_text . '
    </div>
 
    <div class="ls-image-right"></div>

    <style>
    .ls-image-right{
        width:50%;
        height:100%;
        position:absolute;
        top:0;
        right:0;';
        if (!empty($background_image_url)) {
            echo 'background: url(' . esc_url($background_image_url) . ');';
        } else {
            echo 'display: none;';
        }
        echo '
        background-size:cover;
        background-position:center;
        box-shadow: inset 0px 0px 10px;
    }

    .wpml-login-ls{
        text-align:left !important;
    }
    #wpml-login-ls-form{
        margin-left:16% !important;
    }

    @media (max-width: 980px) {
        .wpml-login-ls{
            text-align:center !important;
        }
        #wpml-login-ls-form{
            margin-left:auto !important;
        }
    }

    ';
    if (!empty($background_image_url)) {
        echo '
        #login{
            width:330px;
            margin:0;
            margin-left:14%;
            padding-top:150px
        } 
        ';
    } else{
        echo '
        .ls-terms{
            margin:auto !important
        }
        ';
    }
 

    echo '
    #nav {display:flex; align-items:center}
    #nav a{ width:100%; text-align:center}

    #backtoblog , .language-switcher{ display:none }

    .ls-terms{
        max-width:330px;
        font-size:12px;
        text-align: center; 
        padding-left:5px;
        padding-right:5px;
        margin-top:30px;
        margin-left:14%; 
    }

    .ls-snn{
        width:330px;
        padding: 20px; 
        font-weight:300;
        padding-left:5px;
        padding-right:5px;
        text-align:center;
        margin-left:14%;
    }

    #loginform{
        border-radius:10px;
    }
    body{ 
        border-radius:10px
    }

    @media (max-width: 980px) {
        .ls-image-right{display:none}
        #login{margin:auto}
        .ls-terms{margin:auto}
        .ls-snn{margin:auto}
    }
    </style>
    ';
}

// Add redirect functionality
add_filter('login_redirect', 'ls_login_redirect', 10, 3);
function ls_login_redirect($redirect_to, $request_redirect_to, $user) {
    $redirect_url = get_option('ls_login_redirect_url');
 
    if (!empty($redirect_url)) {
        return esc_url_raw($redirect_url);
    }
 
    return $redirect_to;
} 
