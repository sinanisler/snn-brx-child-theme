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
        
        // Typography
        'font_family' => 'Arial, Helvetica, sans-serif',
        'font_size' => '14px',
        
        // Branding
        'logo_url' => '',
        'logo_width' => '200',
        'company_name' => get_bloginfo('name'),
        'company_address' => '',
        
        // Custom Content (with TinyMCE)
        'header_content' => '',
        'footer_content' => '',
        
        // Social Links
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
        
        // Layout
        'email_width' => '600',
        'content_padding' => '40',
        'border_radius' => '8',
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
    $text_fields = ['sender_name', 'sender_email', 'company_name', 'company_address', 
                   'logo_url', 'logo_width', 'font_family', 'font_size',
                   'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
                   'email_width', 'content_padding', 'border_radius'];
    
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
    
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_media();
    
    wp_add_inline_script('wp-color-picker', '
        jQuery(document).ready(function($){
            $(".snn-color-picker").wpColorPicker();
            
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
                .snn-color-picker { max-width: 100px; }
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
                            <th><label><?php _e('Company Name', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[company_name]" value="<?php echo esc_attr($settings['company_name']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Company Address', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[company_address]" value="<?php echo esc_attr($settings['company_address']); ?>">
                                <p class="snn-description"><?php _e('Will be displayed in the footer.', 'snn'); ?></p>
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
                                <input type="text" name="snn_mail_customizer_settings[header_bg_color]" value="<?php echo esc_attr($settings['header_bg_color']); ?>" class="snn-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Header Text Color', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[header_text_color]" value="<?php echo esc_attr($settings['header_text_color']); ?>" class="snn-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Body Background', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[body_bg_color]" value="<?php echo esc_attr($settings['body_bg_color']); ?>" class="snn-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Content Background', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[content_bg_color]" value="<?php echo esc_attr($settings['content_bg_color']); ?>" class="snn-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Text Color', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[text_color]" value="<?php echo esc_attr($settings['text_color']); ?>" class="snn-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Link Color', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[link_color]" value="<?php echo esc_attr($settings['link_color']); ?>" class="snn-color-picker">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Typography -->
                <div class="snn-settings-section">
                    <h2><?php _e('Typography', 'snn'); ?></h2>
                    <table class="snn-form-table">
                        <tr>
                            <th><label><?php _e('Font Family', 'snn'); ?></label></th>
                            <td>
                                <select name="snn_mail_customizer_settings[font_family]" style="width: 300px;">
                                    <option value="Arial, Helvetica, sans-serif" <?php selected($settings['font_family'], 'Arial, Helvetica, sans-serif'); ?>>Arial</option>
                                    <option value="'Georgia', serif" <?php selected($settings['font_family'], "'Georgia', serif"); ?>>Georgia</option>
                                    <option value="'Times New Roman', Times, serif" <?php selected($settings['font_family'], "'Times New Roman', Times, serif"); ?>>Times New Roman</option>
                                    <option value="'Courier New', Courier, monospace" <?php selected($settings['font_family'], "'Courier New', Courier, monospace"); ?>>Courier New</option>
                                    <option value="Verdana, Geneva, sans-serif" <?php selected($settings['font_family'], 'Verdana, Geneva, sans-serif'); ?>>Verdana</option>
                                    <option value="'Trebuchet MS', sans-serif" <?php selected($settings['font_family'], "'Trebuchet MS', sans-serif"); ?>>Trebuchet MS</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Font Size', 'snn'); ?></label></th>
                            <td>
                                <select name="snn_mail_customizer_settings[font_size]" style="width: 150px;">
                                    <option value="12px" <?php selected($settings['font_size'], '12px'); ?>>12px</option>
                                    <option value="13px" <?php selected($settings['font_size'], '13px'); ?>>13px</option>
                                    <option value="14px" <?php selected($settings['font_size'], '14px'); ?>>14px</option>
                                    <option value="15px" <?php selected($settings['font_size'], '15px'); ?>>15px</option>
                                    <option value="16px" <?php selected($settings['font_size'], '16px'); ?>>16px</option>
                                    <option value="18px" <?php selected($settings['font_size'], '18px'); ?>>18px</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Layout -->
                <div class="snn-settings-section">
                    <h2><?php _e('Layout', 'snn'); ?></h2>
                    <table class="snn-form-table">
                        <tr>
                            <th><label><?php _e('Email Width (px)', 'snn'); ?></label></th>
                            <td>
                                <input type="number" name="snn_mail_customizer_settings[email_width]" value="<?php echo esc_attr($settings['email_width']); ?>" min="400" max="800">
                                <p class="snn-description"><?php _e('Maximum width of the email container (recommended: 600px).', 'snn'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Content Padding (px)', 'snn'); ?></label></th>
                            <td>
                                <input type="number" name="snn_mail_customizer_settings[content_padding]" value="<?php echo esc_attr($settings['content_padding']); ?>" min="10" max="80">
                                <p class="snn-description"><?php _e('Inner padding for email content area.', 'snn'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Border Radius (px)', 'snn'); ?></label></th>
                            <td>
                                <input type="number" name="snn_mail_customizer_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="20">
                                <p class="snn-description"><?php _e('Rounded corners for the email container.', 'snn'); ?></p>
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
                        <code>{company_name}</code>
                        <code>{company_address}</code>
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
                        <code>{company_name}</code>
                        <code>{company_address}</code>
                        <code>{site_url}</code>
                        <code>{current_year}</code>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="snn-settings-section">
                    <h2><?php _e('Social Media Links', 'snn'); ?></h2>
                    <table class="snn-form-table">
                        <tr>
                            <th><label><?php _e('Facebook URL', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[facebook_url]" value="<?php echo esc_attr($settings['facebook_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Twitter URL', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[twitter_url]" value="<?php echo esc_attr($settings['twitter_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Instagram URL', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[instagram_url]" value="<?php echo esc_attr($settings['instagram_url']); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('LinkedIn URL', 'snn'); ?></label></th>
                            <td>
                                <input type="text" name="snn_mail_customizer_settings[linkedin_url]" value="<?php echo esc_attr($settings['linkedin_url']); ?>">
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
function snn_mail_replace_tags($content, $settings) {
    $tags = [
        '{company_name}' => $settings['company_name'],
        '{company_address}' => $settings['company_address'],
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
    
    // Build social links HTML
    $social_html = '';
    if (!empty($settings['facebook_url']) || !empty($settings['twitter_url']) || 
        !empty($settings['instagram_url']) || !empty($settings['linkedin_url'])) {
        
        $social_html = '<div style="margin: 20px 0; text-align: center;">';
        
        if (!empty($settings['facebook_url'])) {
            $social_html .= '<a href="' . esc_url($settings['facebook_url']) . '" style="display: inline-block; margin: 0 8px;">
                <img src="https://img.icons8.com/color/48/facebook.png" alt="Facebook" style="width: 32px; height: 32px;">
            </a>';
        }
        
        if (!empty($settings['twitter_url'])) {
            $social_html .= '<a href="' . esc_url($settings['twitter_url']) . '" style="display: inline-block; margin: 0 8px;">
                <img src="https://img.icons8.com/color/48/twitter.png" alt="Twitter" style="width: 32px; height: 32px;">
            </a>';
        }
        
        if (!empty($settings['instagram_url'])) {
            $social_html .= '<a href="' . esc_url($settings['instagram_url']) . '" style="display: inline-block; margin: 0 8px;">
                <img src="https://img.icons8.com/color/48/instagram-new.png" alt="Instagram" style="width: 32px; height: 32px;">
            </a>';
        }
        
        if (!empty($settings['linkedin_url'])) {
            $social_html .= '<a href="' . esc_url($settings['linkedin_url']) . '" style="display: inline-block; margin: 0 8px;">
                <img src="https://img.icons8.com/color/48/linkedin.png" alt="LinkedIn" style="width: 32px; height: 32px;">
            </a>';
        }
        
        $social_html .= '</div>';
    }
    
    // Logo HTML
    $logo_html = '';
    if (!empty($settings['logo_url'])) {
        $logo_html = '<img src="' . esc_url($settings['logo_url']) . '" alt="' . esc_attr($settings['company_name']) . '" style="max-width: ' . esc_attr($settings['logo_width']) . 'px; height: auto; display: block; margin: 0 auto 15px;">';
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
    <body style="margin: 0; padding: 0; background-color: ' . esc_attr($settings['body_bg_color']) . '; font-family: ' . esc_attr($settings['font_family']) . '; font-size: ' . esc_attr($settings['font_size']) . ';">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: ' . esc_attr($settings['body_bg_color']) . ';">
            <tr>
                <td align="center" style="padding: 30px 15px;">
                    
                    <!-- Main Container -->
                    <table width="' . esc_attr($settings['email_width']) . '" cellpadding="0" cellspacing="0" border="0" style="max-width: ' . esc_attr($settings['email_width']) . 'px; background-color: ' . esc_attr($settings['content_bg_color']) . '; border-radius: ' . esc_attr($settings['border_radius']) . 'px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        
                        <!-- HEADER -->
                        <tr>
                            <td style="background-color: ' . esc_attr($settings['header_bg_color']) . '; padding: 30px ' . esc_attr($settings['content_padding']) . 'px; text-align: center; color: ' . esc_attr($settings['header_text_color']) . ';">
                                ' . $logo_html . '
                                ' . $header_content . '
                            </td>
                        </tr>
                        
                        <!-- CONTENT -->
                        <tr>
                            <td style="padding: ' . esc_attr($settings['content_padding']) . 'px; color: ' . esc_attr($settings['text_color']) . '; line-height: 1.6;">
                                <div style="color: ' . esc_attr($settings['text_color']) . ';">
                                    ' . $original_message . '
                                </div>
                            </td>
                        </tr>
                        
                        <!-- FOOTER -->
                        <tr>
                            <td style="background-color: #f9f9f9; padding: 30px ' . esc_attr($settings['content_padding']) . 'px; text-align: center; border-top: 1px solid #eeeeee;">
                                ' . $social_html . '
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