<?php

add_action('admin_menu', 'ls_add_login_settings_submenu');

function ls_add_login_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        'Login Settings',
        'Login Settings',
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
        'Login Page Customizations',
        'ls_login_settings_section_callback',
        'login-settings'
    );

    add_settings_section(
        'ls_login_redirect_section',
        'Redirect Setting',
        'ls_login_redirect_section_callback',
        'login-settings'
    );

    add_settings_field(
        'ls_login_background_image_url',
        'Background Image URL',
        'ls_login_background_image_url_callback',
        'login-settings',
        'ls_login_settings_section'
    );

    add_settings_field(
        'ls_login_custom_text',
        'Custom Text under Login Form',
        'ls_login_custom_text_callback',
        'login-settings',
        'ls_login_settings_section'
    );

    add_settings_field(
        'ls_login_redirect_url',
        'Redirect URL after Login',
        'ls_login_redirect_url_callback',
        'login-settings',
        'ls_login_redirect_section'
    );
}

function ls_login_settings_section_callback() {
    echo '<p>Customize the login page with your own background image and custom text.</p>';
}

function ls_login_redirect_section_callback() {
 
}

function ls_login_background_image_url_callback() {
    $image_url = get_option('ls_login_background_image_url', '');
    ?>
    <input type="text" id="ls_login_background_image_url" name="ls_login_background_image_url" value="<?php echo esc_attr($image_url); ?>" style="width: 100%;" placeholder="https://example.com/path-to-your-image.jpg" />
    <p class="description">Enter the full URL of the background image you want to use. Leave blank to disable the background image.</p>
    <?php
}

function ls_login_custom_text_callback() {
    $custom_text = get_option('ls_login_custom_text', '');
    ?>
    <textarea id="ls_login_custom_text" name="ls_login_custom_text" rows="5" style="width:100%;" placeholder="Enter your custom text here. HTML tags are allowed."><?php echo esc_textarea($custom_text); ?></textarea>
    <p class="description">You can use HTML tags in this text.</p>
    <?php
}

function ls_login_redirect_url_callback() {
    $redirect_url = get_option('ls_login_redirect_url', '');
    ?>
    <input type="text" id="ls_login_redirect_url" name="ls_login_redirect_url" value="<?php echo esc_attr($redirect_url); ?>" style="width: 100%;" placeholder="https://example.com/redirect-path" />
    <p class="description">Enter the full URL where users should be redirected after logging in. Leave blank to disable custom redirect.</p>
    <?php
}

function ls_render_login_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('ls_messages', 'ls_message', 'Settings Saved', 'updated');
    }

    settings_errors('ls_messages');
    ?>
    <div class="wrap">
        <h1>Login Settings</h1>
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
