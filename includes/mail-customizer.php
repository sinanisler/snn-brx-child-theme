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
        'body_bg_color' => '#f4f4f4',
        'content_bg_color' => '#ffffff',
        'text_color' => '#000000',
        'link_color' => '#0073aa',
        
        // Email Body
        'body_content' => '<p>Hello,</p><p>{content}</p><p>Best regards,<br>{site_name}</p>',
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
    $text_fields = ['sender_name', 'sender_email'];
    
    foreach ($text_fields as $field) {
        $sanitized[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
    }
    
    // Email validation
    if (isset($input['sender_email'])) {
        $sanitized['sender_email'] = sanitize_email($input['sender_email']);
    }
    
    // Color fields
    $color_fields = ['body_bg_color', 'content_bg_color', 'text_color', 'link_color'];
    
    foreach ($color_fields as $field) {
        if (isset($input[$field])) {
            $sanitized[$field] = sanitize_hex_color($input[$field]);
        }
    }
    
    // Rich text field
    $sanitized['body_content'] = isset($input['body_content']) ? wp_kses_post($input['body_content']) : '';
    
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
            // Sync color picker with text input - better UX
            $(".snn-color-picker").on("input change", function() {
                var val = $(this).val();
                $(this).siblings(".snn-color-input").val(val);
            });
            
            $(".snn-color-input").on("input change", function() {
                var val = $(this).val();
                // Validate hex color
                if (/^#[0-9A-F]{6}$/i.test(val)) {
                    $(this).siblings(".snn-color-picker").val(val);
                }
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
                .snn-form-table input[type="number"] { width: 100%; max-width: 500px; padding: 8px; }
                .snn-color-input { max-width: 120px; padding: 8px; }
                .snn-color-picker { width: 50px; height: 38px; margin-left: 8px; border: 1px solid #8c8f94; border-radius: 3px; cursor: pointer; vertical-align: middle; }
                .snn-color-field { display: flex; align-items: center; }
                .snn-description { color: #646970; font-size: 13px; margin-top: 5px; }
                .snn-toggle { background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; margin: 20px 0; }
                .snn-available-tags { background: #f0f6fc; padding: 15px; border: 1px solid #c3e6ff; border-radius: 4px; margin-top: 15px; }
                .snn-available-tags strong { display: block; margin-bottom: 10px; color: #1d2327; }
                .snn-available-tags code { background: #fff; padding: 4px 8px; margin: 3px; display: inline-block; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }
                .snn-body-editor-wrapper { margin-top: 20px; }
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
                
                <!-- Email Body Content -->
                <div class="snn-settings-section">
                    <h2><?php _e('Email Body', 'snn'); ?></h2>
                    <p class="snn-description"><?php _e('Customize the complete email template. Use dynamic tags to insert content automatically.', 'snn'); ?></p>
                    
                    <!-- Sender Information at Top -->
                    <table class="snn-form-table" style="margin-bottom: 20px;">
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
                    
                    <div class="snn-body-editor-wrapper">
                        <?php
                        wp_editor(
                            $settings['body_content'],
                            'snn_mail_body_content',
                            [
                                'textarea_name' => 'snn_mail_customizer_settings[body_content]',
                                'textarea_rows' => 15,
                                'media_buttons' => true,
                                'teeny' => false,
                                'tinymce' => [
                                    'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,image,forecolor,backcolor,alignleft,aligncenter,alignright',
                                ]
                            ]
                        );
                        ?>
                        <div class="snn-available-tags">
                            <strong><?php _e('Available Dynamic Tags:', 'snn'); ?></strong>
                            <code>{content}</code> - <?php _e('Original email message content', 'snn'); ?>
                            <br>
                            <code>{site_name}</code> - <?php _e('Your site name', 'snn'); ?>
                            <br>
                            <code>{site_url}</code> - <?php _e('Your site URL', 'snn'); ?>
                            <br>
                            <code>{current_year}</code> - <?php _e('Current year', 'snn'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Colors -->
                <div class="snn-settings-section">
                    <h2><?php _e('Colors', 'snn'); ?></h2>
                    <table class="snn-form-table">
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
                
            </div>
            
            <?php submit_button(__('Save Settings', 'snn')); ?>
        </form>
    </div>
    <?php
}

// Replace dynamic tags
function snn_mail_replace_tags($content, $original_content = '') {
    $settings = snn_mail_get_settings();
    
    $tags = [
        '{content}' => $original_content,
        '{site_name}' => get_bloginfo('name'),
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
    
    // Replace tags in body content
    $body_content = snn_mail_replace_tags($settings['body_content'], $original_message);
    
    // Build the complete email template
    $template = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: ' . esc_attr($settings['body_bg_color']) . '; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif; font-size: 16px; line-height: 1.6; color: ' . esc_attr($settings['text_color']) . ';">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: ' . esc_attr($settings['body_bg_color']) . ';">
            <tr>
                <td align="center" style="padding: 30px 15px;">
                    
                    <!-- Main Container -->
                    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: ' . esc_attr($settings['content_bg_color']) . '; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        
                        <!-- CONTENT -->
                        <tr>
                            <td style="padding: 40px; color: ' . esc_attr($settings['text_color']) . ';">
                                <div style="color: ' . esc_attr($settings['text_color']) . ';">
                                    ' . $body_content . '
                                </div>
                            </td>
                        </tr>
                        
                    </table>
                    
                </td>
            </tr>
        </table>
        
        <style>
            a { color: ' . esc_attr($settings['link_color']) . '; text-decoration: none; }
            a:hover { text-decoration: underline; }
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