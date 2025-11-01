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
        
        <!-- Test Email Section -->
        <div class="snn-settings-section" style="max-width: 1200px; margin-top: 30px;">
            <h2><?php _e('Test & Preview Emails', 'snn'); ?></h2>
            <p class="snn-description"><?php _e('Send test emails or preview how different WordPress email types will look with your template.', 'snn'); ?></p>
            
            <table class="snn-form-table">
                <!-- Custom Test Email -->
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <th><label><?php _e('Custom Test Email', 'snn'); ?></label></th>
                    <td>
                        <input type="email" id="snn-test-email-recipient" value="<?php echo esc_attr(get_bloginfo('admin_email')); ?>" placeholder="<?php _e('recipient@example.com', 'snn'); ?>" style="width: 100%; max-width: 300px; padding: 8px; margin-bottom: 10px;">
                        <textarea id="snn-test-email-message" rows="3" placeholder="<?php _e('Enter your test message...', 'snn'); ?>" style="width: 100%; max-width: 500px; padding: 8px; margin-bottom: 10px;">This is a test email to verify your custom email template is working correctly!</textarea>
                        <div>
                            <button type="button" class="button button-primary snn-send-test" data-type="custom">
                                <?php _e('Send Custom Test', 'snn'); ?>
                            </button>
                            <button type="button" class="button snn-preview-test" data-type="custom">
                                <?php _e('Preview', 'snn'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
                
                <!-- WordPress Core Emails -->
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <th><label><?php _e('Password Reset Email', 'snn'); ?></label></th>
                    <td>
                        <p class="snn-description" style="margin-top: 0;"><?php _e('WordPress password reset email notification.', 'snn'); ?></p>
                        <button type="button" class="button snn-send-test" data-type="password_reset">
                            <?php _e('Send to Current User', 'snn'); ?>
                        </button>
                        <button type="button" class="button snn-preview-test" data-type="password_reset">
                            <?php _e('Preview', 'snn'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <th><label><?php _e('New User Notification', 'snn'); ?></label></th>
                    <td>
                        <p class="snn-description" style="margin-top: 0;"><?php _e('Email sent when a new user account is created.', 'snn'); ?></p>
                        <button type="button" class="button snn-preview-test" data-type="new_user">
                            <?php _e('Preview', 'snn'); ?>
                        </button>
                        <span class="snn-description"><?php _e('(Preview only - creates actual user account)', 'snn'); ?></span>
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <th><label><?php _e('Comment Notification', 'snn'); ?></label></th>
                    <td>
                        <p class="snn-description" style="margin-top: 0;"><?php _e('Email sent to post author when someone comments.', 'snn'); ?></p>
                        <button type="button" class="button snn-preview-test" data-type="comment">
                            <?php _e('Preview', 'snn'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <th><label><?php _e('Auto Update Success', 'snn'); ?></label></th>
                    <td>
                        <p class="snn-description" style="margin-top: 0;"><?php _e('Email sent when WordPress auto-updates successfully.', 'snn'); ?></p>
                        <button type="button" class="button snn-preview-test" data-type="auto_update_success">
                            <?php _e('Preview', 'snn'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <th><label><?php _e('Auto Update Failed', 'snn'); ?></label></th>
                    <td>
                        <p class="snn-description" style="margin-top: 0;"><?php _e('Email sent when WordPress auto-update fails.', 'snn'); ?></p>
                        <button type="button" class="button snn-preview-test" data-type="auto_update_failed">
                            <?php _e('Preview', 'snn'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Email Change Request', 'snn'); ?></label></th>
                    <td>
                        <p class="snn-description" style="margin-top: 0;"><?php _e('Email sent when user requests to change their email address.', 'snn'); ?></p>
                        <button type="button" class="button snn-preview-test" data-type="email_change">
                            <?php _e('Preview', 'snn'); ?>
                        </button>
                    </td>
                </tr>
            </table>
            
            <div id="snn-test-result" style="margin-top: 20px;"></div>
            
            <!-- Preview Modal -->
            <div id="snn-preview-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; overflow: auto;">
                <div style="max-width: 800px; margin: 50px auto; background: #fff; border-radius: 8px; position: relative;">
                    <div style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;"><?php _e('Email Preview', 'snn'); ?></h3>
                        <button type="button" id="snn-close-preview" style="background: none; border: none; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
                    </div>
                    <div id="snn-preview-content" style="padding: 20px; max-height: 70vh; overflow: auto;">
                        <!-- Preview content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($){
            // Send test email
            $('.snn-send-test').on('click', function(){
                var btn = $(this);
                var type = btn.data('type');
                var resultDiv = $('#snn-test-result');
                var recipient = $('#snn-test-email-recipient').val();
                var message = $('#snn-test-email-message').val();
                
                btn.prop('disabled', true);
                var originalText = btn.text();
                btn.text('<?php _e('Sending...', 'snn'); ?>');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'snn_send_test_email',
                        nonce: '<?php echo wp_create_nonce('snn_test_email'); ?>',
                        type: type,
                        recipient: recipient,
                        message: message
                    },
                    success: function(response){
                        if(response.success){
                            resultDiv.html('<div class="notice notice-success inline" style="margin: 0; padding: 10px;"><p><strong>✓ ' + response.data.message + '</strong></p></div>');
                        } else {
                            resultDiv.html('<div class="notice notice-error inline" style="margin: 0; padding: 10px;"><p><strong>✗ ' + response.data.message + '</strong></p></div>');
                        }
                    },
                    error: function(){
                        resultDiv.html('<div class="notice notice-error inline" style="margin: 0; padding: 10px;"><p><strong>✗ <?php _e('Ajax error occurred', 'snn'); ?></strong></p></div>');
                    },
                    complete: function(){
                        btn.prop('disabled', false).text(originalText);
                        setTimeout(function(){ resultDiv.fadeOut(); }, 5000);
                    }
                });
            });
            
            // Preview email
            $('.snn-preview-test').on('click', function(){
                var btn = $(this);
                var type = btn.data('type');
                var message = $('#snn-test-email-message').val();
                
                btn.prop('disabled', true);
                var originalText = btn.text();
                btn.text('<?php _e('Loading...', 'snn'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'snn_preview_email',
                        nonce: '<?php echo wp_create_nonce('snn_preview_email'); ?>',
                        type: type,
                        message: message
                    },
                    success: function(response){
                        if(response.success){
                            $('#snn-preview-content').html(response.data.html);
                            $('#snn-preview-modal').fadeIn();
                        } else {
                            alert(response.data.message || '<?php _e('Preview failed', 'snn'); ?>');
                        }
                    },
                    error: function(){
                        alert('<?php _e('Ajax error occurred', 'snn'); ?>');
                    },
                    complete: function(){
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Close preview modal
            $('#snn-close-preview, #snn-preview-modal').on('click', function(e){
                if(e.target === this){
                    $('#snn-preview-modal').fadeOut();
                }
            });
        });
        </script>
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

// AJAX handler for sending test emails
add_action('wp_ajax_snn_send_test_email', 'snn_handle_send_test_email');
function snn_handle_send_test_email() {
    check_ajax_referer('snn_test_email', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied', 'snn')]);
    }
    
    $type = sanitize_text_field($_POST['type']);
    $recipient = sanitize_email($_POST['recipient']);
    $message = sanitize_textarea_field($_POST['message']);
    
    if (empty($recipient) || !is_email($recipient)) {
        wp_send_json_error(['message' => __('Invalid email address', 'snn')]);
    }
    
    $result = false;
    $response_message = '';
    
    switch($type) {
        case 'custom':
            $subject = sprintf(__('[Test] Email from %s', 'snn'), get_bloginfo('name'));
            $result = wp_mail($recipient, $subject, $message);
            $response_message = $result 
                ? sprintf(__('Test email sent successfully to %s', 'snn'), $recipient)
                : __('Failed to send test email. Check your email server configuration.', 'snn');
            break;
            
        case 'password_reset':
            $user = wp_get_current_user();
            $key = get_password_reset_key($user);
            
            if (!is_wp_error($key)) {
                $subject = sprintf(__('[%s] Password Reset', 'snn'), get_bloginfo('name'));
                $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
                $message = sprintf(__('Someone has requested a password reset for the following account:

Site Name: %s
Username: %s

If this was a mistake, just ignore this email and nothing will happen.

To reset your password, visit the following address:

%s', 'snn'), get_bloginfo('name'), $user->user_login, $reset_url);
                
                $result = wp_mail($recipient, $subject, $message);
                $response_message = $result 
                    ? sprintf(__('Password reset email sent to %s', 'snn'), $recipient)
                    : __('Failed to send password reset email.', 'snn');
            } else {
                $response_message = __('Failed to generate password reset key.', 'snn');
            }
            break;
            
        default:
            wp_send_json_error(['message' => __('Invalid test type', 'snn')]);
    }
    
    if ($result) {
        wp_send_json_success(['message' => $response_message]);
    } else {
        wp_send_json_error(['message' => $response_message]);
    }
}

// AJAX handler for email preview
add_action('wp_ajax_snn_preview_email', 'snn_handle_preview_email');
function snn_handle_preview_email() {
    check_ajax_referer('snn_preview_email', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied', 'snn')]);
    }
    
    $type = sanitize_text_field($_POST['type']);
    $custom_message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    $preview_data = snn_get_preview_content($type, $custom_message);
    
    if (!$preview_data) {
        wp_send_json_error(['message' => __('Invalid preview type', 'snn')]);
    }
    
    // Generate the email HTML using the template
    $settings = snn_mail_get_settings();
    
    // Create a fake wp_mail args array
    $args = [
        'message' => $preview_data['message'],
    ];
    
    // Apply the template if enabled
    if ($settings['enabled']) {
        $args = snn_mail_customize_template($args);
        $html_content = $args['message'];
    } else {
        // Show plain version
        $html_content = '<div style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
        $html_content .= '<p style="color: #d63638; margin-bottom: 15px;"><strong>⚠️ ' . __('Custom email templates are currently DISABLED. Enable them in settings to see the styled version.', 'snn') . '</strong></p>';
        $html_content .= '<div style="background: #fff; padding: 15px; border-radius: 4px;">';
        $html_content .= '<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 13px;">' . esc_html($preview_data['message']) . '</pre>';
        $html_content .= '</div></div>';
    }
    
    // Wrap in a preview container with subject line
    $preview_html = '<div style="margin-bottom: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">';
    $preview_html .= '<strong>' . __('Subject:', 'snn') . '</strong> ' . esc_html($preview_data['subject']);
    $preview_html .= '</div>';
    $preview_html .= '<div style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">';
    $preview_html .= $html_content;
    $preview_html .= '</div>';
    
    wp_send_json_success(['html' => $preview_html]);
}

// Get preview content for different email types
function snn_get_preview_content($type, $custom_message = '') {
    $user = wp_get_current_user();
    $site_name = get_bloginfo('name');
    $site_url = home_url();
    
    switch($type) {
        case 'custom':
            return [
                'subject' => sprintf(__('[Test] Email from %s', 'snn'), $site_name),
                'message' => !empty($custom_message) ? $custom_message : __('This is a test email to verify your custom email template.', 'snn'),
            ];
            
        case 'password_reset':
            $reset_url = network_site_url("wp-login.php?action=rp&key=SAMPLE_KEY&login=" . rawurlencode($user->user_login), 'login');
            return [
                'subject' => sprintf(__('[%s] Password Reset', 'snn'), $site_name),
                'message' => sprintf(__('Someone has requested a password reset for the following account:

Site Name: %s
Username: %s

If this was a mistake, just ignore this email and nothing will happen.

To reset your password, visit the following address:

%s', 'snn'), $site_name, $user->user_login, $reset_url),
            ];
            
        case 'new_user':
            return [
                'subject' => sprintf(__('[%s] Your username and password info', 'snn'), $site_name),
                'message' => sprintf(__('Username: newuser123
To set your password, visit the following address:

%s/wp-login.php?action=rp&key=SAMPLE_KEY&login=newuser123

%s', 'snn'), $site_url, $site_url),
            ];
            
        case 'comment':
            return [
                'subject' => sprintf(__('[%s] Please moderate: "Sample Post Title"', 'snn'), $site_name),
                'message' => sprintf(__('A new comment on the post "Sample Post Title" is waiting for your approval

Author: John Doe (email: john@example.com)
Comment:
This is a sample comment that someone left on your blog post. It contains some feedback about your content.

Approve it: %s/wp-admin/comment.php?action=approve&c=123
Trash it: %s/wp-admin/comment.php?action=trash&c=123
Spam it: %s/wp-admin/comment.php?action=spam&c=123

Currently 1 comment is waiting for approval. Please visit the moderation panel:
%s/wp-admin/edit-comments.php?comment_status=moderated', 'snn'), $site_url, $site_url, $site_url, $site_url),
            ];
            
        case 'auto_update_success':
            return [
                'subject' => sprintf(__('[%s] Some plugins and themes have automatically updated', 'snn'), $site_name),
                'message' => sprintf(__('Howdy!

Great news! Your site has been updated automatically.

######################################################
# WordPress update details
######################################################

Updated to WordPress 6.4.2 from 6.4.1

######################################################
# Plugin update details
######################################################

Successfully updated the following plugins:

- Contact Form 7 (5.8.4 to 5.8.5)
- Yoast SEO (21.5 to 21.6)

######################################################
# Theme update details
######################################################

Successfully updated the following themes:

- Twenty Twenty-Four (1.0 to 1.1)

Visit your site: %s
Visit your dashboard: %s/wp-admin/', 'snn'), $site_url, $site_url),
            ];
            
        case 'auto_update_failed':
            return [
                'subject' => sprintf(__('[%s] URGENT: Automatic update failed', 'snn'), $site_name),
                'message' => sprintf(__('Howdy!

We tried to automatically update your site, but unfortunately something went wrong.

######################################################
# Update attempt details
######################################################

FAILED: WordPress 6.4.2

Error message: Download failed. A valid URL was not provided.

######################################################
# Next steps
######################################################

Please update your site manually:
%s/wp-admin/update-core.php

If you need help, please contact your hosting provider or visit:
https://wordpress.org/support/', 'snn'), $site_url),
            ];
            
        case 'email_change':
            return [
                'subject' => sprintf(__('[%s] Email Change Request', 'snn'), $site_name),
                'message' => sprintf(__('Howdy,

You recently requested to have the email address on your account changed.

If this is correct, please click on the following link to confirm the change:
%s/wp-login.php?action=confirmemail&key=SAMPLE_KEY

You can safely ignore and delete this email if you do not want to take this action.

This email has been sent to %s

Regards,
All at %s
%s', 'snn'), $site_url, $user->user_email, $site_name, $site_url),
            ];
            
        default:
            return false;
    }
}