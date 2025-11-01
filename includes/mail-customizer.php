<?php
/**
 * SNN Mail Customizer
 * Simple email customization for WordPress
 */

if (!defined('ABSPATH')) exit;

// Default settings
function snn_mail_get_defaults() {
    return [
        'enabled' => false,
        
        // Sender Info
        'sender_name' => get_bloginfo('name'),
        'sender_email' => get_bloginfo('admin_email'),
        
        // Colors
        'header_bg_color' => '#2c3e50',
        'body_bg_color' => '#f4f4f4',
        'content_bg_color' => '#ffffff',
        'text_color' => '#333333',
        'header_text_color' => '#ffffff',
        'link_color' => '#3498db',
        
        // Branding
        'logo_url' => '',
        'logo_width' => '200',
        'site_name' => get_bloginfo('name'),
        
        // Custom Content (with TinyMCE)
        'header_content' => '',
        'footer_content' => '',
    ];
}

// Get settings
function snn_mail_get_settings() {
    $defaults = snn_mail_get_defaults();
    $settings = get_option('snn_mail_customizer_settings', []);
    return wp_parse_args($settings, $defaults);
}

// Add submenu page
add_action('admin_menu', 'snn_mail_add_submenu', 20);
function snn_mail_add_submenu() {
    add_submenu_page(
        'snn-settings',
        __('Mail Customizer', 'snn'),
        __('Mail Customizer', 'snn'),
        'manage_options',
        'snn-mail-customizer',
        'snn_mail_render_page'
    );
}

// Register settings
add_action('admin_init', 'snn_mail_register_settings');
function snn_mail_register_settings() {
    register_setting(
        'snn_mail_customizer_group',
        'snn_mail_customizer_settings',
        'snn_mail_sanitize_settings'
    );
}

// Sanitize settings
function snn_mail_sanitize_settings($input) {
    $sanitized = [];
    
    $sanitized['enabled'] = isset($input['enabled']) ? true : false;
    
    // Text fields
    $text_fields = ['sender_name', 'sender_email', 'site_name', 
                   'logo_url', 'logo_width'];
    
    foreach ($text_fields as $field) {
        $sanitized[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
    }
    
    // Email validation
    if (isset($input['sender_email'])) {
        $sanitized['sender_email'] = sanitize_email($input['sender_email']);
    }
    
    // Color fields
    $color_fields = ['header_bg_color', 'body_bg_color', 'content_bg_color', 
                    'text_color', 'header_text_color', 'link_color'];
    
    foreach ($color_fields as $field) {
        if (isset($input[$field])) {
            $sanitized[$field] = sanitize_hex_color($input[$field]);
        }
    }
    
    // Rich text fields (TinyMCE)
    $sanitized['header_content'] = isset($input['header_content']) ? wp_kses_post($input['header_content']) : '';
    $sanitized['footer_content'] = isset($input['footer_content']) ? wp_kses_post($input['footer_content']) : '';
    
    return $sanitized;
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'snn_mail_enqueue_scripts');
function snn_mail_enqueue_scripts($hook) {
    if ($hook !== 'snn-settings_page_snn-mail-customizer') {
        return;
    }
    
    wp_enqueue_media();
    
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($){
            // Media uploader for logo
            $(".snn-upload-button").click(function(e) {
                e.preventDefault();
                var button = $(this);
                var input = button.prev("input");
                
                var mediaUploader = wp.media({
                    title: "' . esc_js(__('Choose Logo', 'snn')) . '",
                    button: {
                        text: "' . esc_js(__('Use this image', 'snn')) . '"
                    },
                    multiple: false
                });
                
                mediaUploader.on("select", function() {
                    var attachment = mediaUploader.state().get("selection").first().toJSON();
                    input.val(attachment.url);
                });
                
                mediaUploader.open();
            });
            
            // Sync color picker with text input
            $(".snn-color-input").on("input", function() {
                var val = $(this).val();
                $(this).next(".snn-color-picker").val(val);
            });
            
            $(".snn-color-picker").on("input", function() {
                var val = $(this).val();
                $(this).prev(".snn-color-input").val(val);
            });
        });
    ');
}

// Render settings page
function snn_mail_render_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save message
    if (isset($_GET['settings-updated'])) {
        add_settings_error('snn_mail_messages', 'snn_mail_message', __('Settings Saved', 'snn'), 'updated');
    }
    
    settings_errors('snn_mail_messages');
    
    $settings = snn_mail_get_settings();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_mail_customizer_group');
            ?>
            
            <style>
                .snn-mail-settings { max-width: 1200px; }
                .snn-settings-section { background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
                .snn-settings-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
                .snn-form-table { width: 100%; }
                .snn-form-table th { width: 200px; padding: 15px 10px 15px 0; text-align: left; vertical-align: top; }
                .snn-form-table td { padding: 15px 10px; }
                .snn-form-table input[type="text"],
                .snn-form-table input[type="email"],
                .snn-form-table input[type="number"] { width: 100%; max-width: 500px; }
                .snn-form-table input[type="number"] { max-width: 100px; }
                .snn-upload-button { margin-left: 10px; }
                .snn-color-input { max-width: 120px; }
                .snn-color-picker { width: 60px; height: 38px; margin-left: 10px; border: 1px solid #ddd; cursor: pointer; }
                .snn-color-field { display: flex; align-items: center; }
                .snn-description { color: #666; font-style: italic; margin-top: 5px; }
                .snn-toggle { background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; margin: 20px 0; }
                .snn-available-tags { background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-top: 10px; }
                .snn-available-tags code { background: #fff; padding: 2px 6px; margin: 2px; display: inline-block; }
            </style>
            
            <div class="snn-mail-settings">
                
                <!-- Enable/Disable Toggle -->
                <div class="snn-toggle">
                    <label>
                        <input type="checkbox" name="snn_mail_customizer_settings[enabled]" value="1" <?php checked($settings['enabled'], true); ?>>
                        <strong><?php _e('Enable Custom Email Templates', 'snn'); ?></strong>
                    </label>
                    <p class="snn-description">
                        <?php _e('Check this to apply custom email template to all WordPress emails.', 'snn'); ?>
                    </p>
                </div>
                
                <!-- Sender Information -->
                <div class="snn-settings-section">
                    <h2><?php _e('Sender Information', 'snn'); ?></h2>
                    <table class="snn-form-table">
                        <tr>
                            <th><label><?php _e('Sender Name', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[sender_name]" value="<?php echo esc_attr($settings['sender_name']); ?>">
                                <p class="snn-description"><?php _e('The name that appears as the email sender.', 'snn'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Sender Email', 'snn'); ?></label></th>
                            <td>
                                <input type="email" name="snn_mail_customizer_settings[sender_email]" value="<?php echo esc_attr($settings['sender_email']); ?>">
                                <p class="snn-description"><?php _e('The email address that appears as the sender.', 'snn'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Branding -->
                <div class="snn-settings-section">
                    <h2><?php _e('Branding', 'snn'); ?></h2>
                    <table class="snn-form-table">
                        <tr>
                            <th><label><?php _e('Logo URL', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[logo_url]" value="<?php echo esc_attr($settings['logo_url']); ?>">
                                <button type="button" class="button snn-upload-button"><?php _e('Upload Logo', 'snn'); ?></button>
                                <p class="snn-description"><?php _e('Logo to display in email header.', 'snn'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Logo Width (px)', 'snn'); ?></label></th>
                            <td>
                                <input type="number" name="snn_mail_customizer_settings[logo_width]" value="<?php echo esc_attr($settings['logo_width']); ?>" min="50" max="600">
                                <p class="snn-description"><?php _e('Maximum width of the logo in pixels.', 'snn'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Site Name', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[site_name]" value="<?php echo esc_attr($settings['site_name']); ?>">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Colors -->
                <div class="snn-settings-section">
                    <h2><?php _e('Colors', 'snn'); ?></h2>
                    <table class="snn-form-table">
                        <tr>
                            <th><label><?php _e('Header Background', 'snn'); ?></label></th>
                            <td>
                                <div class="snn-color-field">
                                    <input type="text" name="snn_mail_customizer_settings[header_bg_color]" value="<?php echo esc_attr($settings['header_bg_color']); ?>" class="snn-color-input">
                                    <input type="color" value="<?php echo esc_attr($settings['header_bg_color']); ?>" class="snn-color-picker">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Header Text Color', 'snn'); ?></label></th>
                            <td>
                                <div class="snn-color-field">
                                    <input type="text" name="snn_mail_customizer_settings[header_text_color]" value="<?php echo esc_attr($settings['header_text_color']); ?>" class="snn-color-input">
                                    <input type="color" value="<?php echo esc_attr($settings['header_text_color']); ?>" class="snn-color-picker">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Body Background', 'snn'); ?></label></th>
                            <td>
                                <div class="snn-color-field">
                                    <input type="text" name="snn_mail_customizer_settings[body_bg_color]" value="<?php echo esc_attr($settings['body_bg_color']); ?>" class="snn-color-input">
                                    <input type="color" value="<?php echo esc_attr($settings['body_bg_color']); ?>" class="snn-color-picker">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Content Background', 'snn'); ?></label></th>
                            <td>
                                <div class="snn-color-field">
                                    <input type="text" name="snn_mail_customizer_settings[content_bg_color]" value="<?php echo esc_attr($settings['content_bg_color']); ?>" class="snn-color-input">
                                    <input type="color" value="<?php echo esc_attr($settings['content_bg_color']); ?>" class="snn-color-picker">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Text Color', 'snn'); ?></label></th>
                            <td>
                                <div class="snn-color-field">
                                    <input type="text" name="snn_mail_customizer_settings[text_color]" value="<?php echo esc_attr($settings['text_color']); ?>" class="snn-color-input">
                                    <input type="color" value="<?php echo esc_attr($settings['text_color']); ?>" class="snn-color-picker">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Link Color', 'snn'); ?></label></th>
                            <td>
                                <div class="snn-color-field">
                                    <input type="text" name="snn_mail_customizer_settings[link_color]" value="<?php echo esc_attr($settings['link_color']); ?>" class="snn-color-input">
                                    <input type="color" value="<?php echo esc_attr($settings['link_color']); ?>" class="snn-color-picker">
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Header Content -->
                <div class="snn-settings-section">
                    <h2><?php _e('Header Content (Optional)', 'snn'); ?></h2>
                    <p class="snn-description"><?php _e('Add custom content below the logo in the header section.', 'snn'); ?></p>
                    <?php
                    wp_editor(
                        $settings['header_content'],
                        'snn_mail_header_content',
                        [
                            'textarea_name' => 'snn_mail_customizer_settings[header_content]',
                            'textarea_rows' => 8,
                            'media_buttons' => true,
                            'teeny' => false,
                            'tinymce' => [
                                'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,forecolor,backcolor,alignleft,aligncenter,alignright',
                            ]
                        ]
                    );
                    ?>
                    <div class="snn-available-tags">
                        <strong><?php _e('Available Tags:', 'snn'); ?></strong>
                        <code>{site_name}</code>
                        <code>{site_url}</code>
                    </div>
                </div>
                
                <!-- Footer Content -->
                <div class="snn-settings-section">
                    <h2><?php _e('Footer Content', 'snn'); ?></h2>
                    <p class="snn-description"><?php _e('Customize the footer content of your emails.', 'snn'); ?></p>
                    <?php
                    wp_editor(
                        $settings['footer_content'],
                        'snn_mail_footer_content',
                        [
                            'textarea_name' => 'snn_mail_customizer_settings[footer_content]',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny' => false,
                            'tinymce' => [
                                'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,forecolor,backcolor,alignleft,aligncenter,alignright',
                            ]
                        ]
                    );
                    ?>
                    <div class="snn-available-tags">
                        <strong><?php _e('Available Tags:', 'snn'); ?></strong>
                        <code>{site_name}</code>
                        <code>{site_url}</code>
                        <code>{current_year}</code>
                    </div>
                </div>
                
            </div>
            
            <?php submit_button(__('Save Settings', 'snn')); ?>
        </form>
    </div>
    <?php
}

// Replace dynamic tags
function snn_mail_replace_tags($content, $settings) {
    $tags = [
        '{site_name}' => $settings['site_name'],
        '{site_url}' => home_url(),
        '{current_year}' => date('Y'),
    ];
    
    return str_replace(array_keys($tags), array_values($tags), $content);
}

// Apply email filters
add_filter('wp_mail_content_type', 'snn_mail_set_content_type');
function snn_mail_set_content_type($content_type) {
    $settings = snn_mail_get_settings();
    if ($settings['enabled']) {
        return 'text/html';
    }
    return $content_type;
}

add_filter('wp_mail_from_name', 'snn_mail_set_from_name');
function snn_mail_set_from_name($name) {
    $settings = snn_mail_get_settings();
    if ($settings['enabled'] && !empty($settings['sender_name'])) {
        return $settings['sender_name'];
    }
    return $name;
}

add_filter('wp_mail_from', 'snn_mail_set_from_email');
function snn_mail_set_from_email($email) {
    $settings = snn_mail_get_settings();
    if ($settings['enabled'] && !empty($settings['sender_email'])) {
        return $settings['sender_email'];
    }
    return $email;
}

// Main email template wrapper
add_filter('wp_mail', 'snn_mail_customize_template', 10, 1);
function snn_mail_customize_template($args) {
    $settings = snn_mail_get_settings();
    
    // Only apply if enabled
    if (!$settings['enabled']) {
        return $args;
    }
    
    $original_message = $args['message'];
    
    // Replace tags in header and footer
    $header_content = snn_mail_replace_tags($settings['header_content'], $settings);
    $footer_content = snn_mail_replace_tags($settings['footer_content'], $settings);
    
    // Logo HTML
    $logo_html = '';
    if (!empty($settings['logo_url'])) {
        $logo_html = '<img src="' . esc_url($settings['logo_url']) . '" alt="' . esc_attr($settings['site_name']) . '" style="max-width: ' . esc_attr($settings['logo_width']) . 'px; height: auto; display: block; margin: 0 auto 15px;">';
    }
    
    // Build the complete email template
    $template = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: ' . esc_attr($settings['body_bg_color']) . '; font-family: Arial, Helvetica, sans-serif; font-size: 14px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: ' . esc_attr($settings['body_bg_color']) . ';">
            <tr>
                <td align="center" style="padding: 30px 15px;">
                    
                    <!-- Main Container -->
                    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: ' . esc_attr($settings['content_bg_color']) . '; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        
                        <!-- HEADER -->
                        <tr>
                            <td style="background-color: ' . esc_attr($settings['header_bg_color']) . '; padding: 30px 40px; text-align: center; color: ' . esc_attr($settings['header_text_color']) . ';">
                                ' . $logo_html . '
                                ' . $header_content . '
                            </td>
                        </tr>
                        
                        <!-- CONTENT -->
                        <tr>
                            <td style="padding: 40px; color: ' . esc_attr($settings['text_color']) . '; line-height: 1.6;">
                                <div style="color: ' . esc_attr($settings['text_color']) . ';">
                                    ' . $original_message . '
                                </div>
                            </td>
                        </tr>
                        
                        <!-- FOOTER -->
                        <tr>
                            <td style="background-color: #f9f9f9; padding: 30px 40px; text-align: center; border-top: 1px solid #eeeeee;">
                                ' . $footer_content . '
                            </td>
                        </tr>
                        
                    </table>
                    
                </td>
            </tr>
        </table>
        
        <style>
            a { color: ' . esc_attr($settings['link_color']) . '; }
        </style>
    </body>
    </html>
    ';
    
    // Replace the message
    $args['message'] = $template;
    
    // Ensure HTML content type in headers
    if (!isset($args['headers']) || !is_array($args['headers'])) {
        $args['headers'] = [];
    }
    
    $has_content_type = false;
    foreach ($args['headers'] as $header) {
        if (stripos($header, 'Content-Type:') !== false) {
            $has_content_type = true;
            break;
        }
    }
    
    if (!$has_content_type) {
        $args['headers'][] = 'Content-Type: text/html; charset=UTF-8';
    }
    
    return $args;
}