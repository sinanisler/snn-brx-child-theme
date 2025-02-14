<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define('SNN_OPTIONS', 'snn_cookie_settings_options');

function snn_add_cookie_settings_submenu() {
    add_submenu_page(
        'snn-settings',               // Parent menu slug (existing main menu)
        'SNN Cookie Settings',        // Page title
        'Cookie Settings',            // Submenu title
        'manage_options',             // Capability
        'snn-cookie-settings',        // Menu slug
        'snn_options_page'            // Callback function
    );
}
add_action('admin_menu', 'snn_add_cookie_settings_submenu', 10);

function snn_options_page() {
    // Security: Ensure the current user has proper capability
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'snn-cookie-banner'));
    }
    
    // Handle form submission
    if ( isset($_POST['snn_options_nonce']) && wp_verify_nonce( $_POST['snn_options_nonce'], 'snn_save_options' ) ) {
        $options = array();
        // ----- General Settings Tab -----
        $options['snn_cookie_settings_enable_cookie_banner'] = isset($_POST['snn_cookie_settings_enable_cookie_banner']) ? 'yes' : 'no';
        $options['snn_cookie_settings_banner_description']   = isset($_POST['snn_cookie_settings_banner_description']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_description']) ) : '';
        $options['snn_cookie_settings_accept_button']        = isset($_POST['snn_cookie_settings_accept_button']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_accept_button']) ) : '';
        $options['snn_cookie_settings_deny_button']          = isset($_POST['snn_cookie_settings_deny_button']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_deny_button']) ) : '';
        $options['snn_cookie_settings_preferences_button']   = isset($_POST['snn_cookie_settings_preferences_button']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_preferences_button']) ) : '';
        $options['snn_cookie_settings_banner_position']      = isset($_POST['snn_cookie_settings_banner_position']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_position']) ) : '';
        $options['snn_cookie_settings_banner_bg_color']      = isset($_POST['snn_cookie_settings_banner_bg_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_bg_color']) ) : '';
        $options['snn_cookie_settings_banner_text_color']    = isset($_POST['snn_cookie_settings_banner_text_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_text_color']) ) : '';
        $options['snn_cookie_settings_button_bg_color']      = isset($_POST['snn_cookie_settings_button_bg_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_button_bg_color']) ) : '';
        $options['snn_cookie_settings_button_text_color']    = isset($_POST['snn_cookie_settings_button_text_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_button_text_color']) ) : '';
        
        // ----- Scripts & Services Tab -----
        $options['snn_cookie_settings_enable_consent_mode'] = isset($_POST['snn_cookie_settings_enable_consent_mode']) ? 'yes' : 'no';
        
        $services = array();
        if ( isset($_POST['snn_cookie_settings_services']) && is_array($_POST['snn_cookie_settings_services']) ) {
            foreach( $_POST['snn_cookie_settings_services'] as $service ) {
                if ( empty( $service['name'] ) ) {
                    continue; // Skip if no service name is provided.
                }
                $service_data = array();
                $service_data['name'] = sanitize_text_field( wp_unslash($service['name']) );
                // Save the script exactly as provided (unsanitized, unslashed)
                $service_data['script'] = isset($service['script']) ? wp_unslash($service['script']) : '';
                $service_data['position'] = isset($service['position']) ? sanitize_text_field( wp_unslash($service['position']) ) : 'body_bottom';
                $service_data['mandatory'] = isset($service['mandatory']) ? 'yes' : 'no';
                $services[] = $service_data;
            }
        }
        $options['snn_cookie_settings_services'] = $services;
        
        // Custom CSS remains unsanitized
        $options['snn_cookie_settings_custom_css'] = isset($_POST['snn_cookie_settings_custom_css']) ? wp_unslash($_POST['snn_cookie_settings_custom_css']) : '';
        
        update_option( SNN_OPTIONS, $options );
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    
    // Get existing options or set defaults
    $options = get_option( SNN_OPTIONS );
    if ( !is_array($options) ) {
        $options = array(
            'snn_cookie_settings_enable_cookie_banner' => 'no',
            'snn_cookie_settings_banner_description'   => 'This website uses cookies for analytics and functionality.',
            'snn_cookie_settings_accept_button'        => 'Accept',
            'snn_cookie_settings_deny_button'          => 'Deny',
            'snn_cookie_settings_preferences_button'   => 'Preferences',
            'snn_cookie_settings_services'             => array(),
            'snn_cookie_settings_custom_css'           => '',
            'snn_cookie_settings_banner_position'      => 'left',
            'snn_cookie_settings_banner_bg_color'      => '#333333',
            'snn_cookie_settings_banner_text_color'    => '#ffffff',
            'snn_cookie_settings_button_bg_color'      => '#555555',
            'snn_cookie_settings_button_text_color'    => '#ffffff',
            'snn_cookie_settings_enable_consent_mode'  => 'yes'
        );
    }
    ?>
    <div class="wrap">
        <h1>Cookie Banner</h1>
        <!-- Admin CSS -->
        <style>
            .snn-textarea { width: 500px; }
            .snn-input { width: 300px; }
            .snn-color-picker { }
            .snn-services-repeater .snn-service-item { margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; max-width:600px }
            .snn-custom-css-textarea { width: 500px; }
            /* Tabs styling */
            .snn-tab { cursor:pointer; display: inline-block; margin-right: 10px; padding: 8px 12px; border: 1px solid #ccc; border-bottom: none; background: #f1f1f1; }
            .snn-tab.active { background: #fff; font-weight: bold; }
            .snn-tab-content { border: 1px solid #ccc; padding: 15px; display: none; }
            .snn-tab-content.active { display: block; }
            .snn-service-item label { display: block; margin-bottom: 5px; }
            .snn-service-item input[type="text"],
            .snn-service-item textarea { width: 100%; }
            .snn-service-item .snn-radio-group label { margin-right: 10px; }
        </style>
        <!-- Tabs Navigation -->
        <div class="snn-tabs">
            <span class="snn-tab active" data-tab="general">General Settings</span>
            <span class="snn-tab" data-tab="scripts">Scripts &amp; Services</span>
        </div>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_options', 'snn_options_nonce' ); ?>
            <!-- General Settings Tab Content -->
            <div id="general" class="snn-tab-content active">
                <table class="form-table">
                    <!-- New: Enable Cookie Banner -->
                    <tr valign="top">
                        <th scope="row">Enable Cookie Banner</th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_cookie_banner" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_cookie_banner']) ? $options['snn_cookie_settings_enable_cookie_banner'] : 'no'), 'yes'); ?>>
                            <span class="description">Check to enable the Cookie Banner on your site.</span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Description</th>
                        <td>
                            <textarea name="snn_cookie_settings_banner_description" rows="3" class="snn-textarea snn-banner-description"><?php echo isset($options['snn_cookie_settings_banner_description']) ? esc_textarea($options['snn_cookie_settings_banner_description']) : ''; ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Accept Button Text</th>
                        <td>
                            <input type="text" name="snn_cookie_settings_accept_button" value="<?php echo isset($options['snn_cookie_settings_accept_button']) ? esc_attr($options['snn_cookie_settings_accept_button']) : ''; ?>" class="snn-input snn-accept-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Deny Button Text</th>
                        <td>
                            <input type="text" name="snn_cookie_settings_deny_button" value="<?php echo isset($options['snn_cookie_settings_deny_button']) ? esc_attr($options['snn_cookie_settings_deny_button']) : ''; ?>" class="snn-input snn-deny-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Preferences Button Text</th>
                        <td>
                            <input type="text" name="snn_cookie_settings_preferences_button" value="<?php echo isset($options['snn_cookie_settings_preferences_button']) ? esc_attr($options['snn_cookie_settings_preferences_button']) : ''; ?>" class="snn-input snn-preferences-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Position</th>
                        <td>
                            <select name="snn_cookie_settings_banner_position" class="snn-select snn-banner-position">
                                <option value="left" <?php selected((isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : ''), 'left'); ?>>Left</option>
                                <option value="middle" <?php selected((isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : ''), 'middle'); ?>>Middle</option>
                                <option value="right" <?php selected((isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : ''), 'right'); ?>>Right</option>
                            </select>
                            <p class="description">Select the horizontal position of the cookie banner on your website.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Background Color</th>
                        <td>
                            <input type="color" name="snn_cookie_settings_banner_bg_color" value="<?php echo isset($options['snn_cookie_settings_banner_bg_color']) ? esc_attr($options['snn_cookie_settings_banner_bg_color']) : ''; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Text Color</th>
                        <td>
                            <input type="color" name="snn_cookie_settings_banner_text_color" value="<?php echo isset($options['snn_cookie_settings_banner_text_color']) ? esc_attr($options['snn_cookie_settings_banner_text_color']) : ''; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Button Background Color</th>
                        <td>
                            <input type="color" name="snn_cookie_settings_button_bg_color" value="<?php echo isset($options['snn_cookie_settings_button_bg_color']) ? esc_attr($options['snn_cookie_settings_button_bg_color']) : ''; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Button Text Color</th>
                        <td>
                            <input type="color" name="snn_cookie_settings_button_text_color" value="<?php echo isset($options['snn_cookie_settings_button_text_color']) ? esc_attr($options['snn_cookie_settings_button_text_color']) : ''; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Scripts & Services Tab Content -->
            <div id="scripts" class="snn-tab-content">
                <!-- Added description for Scripts & Services -->
                <p class="description">
                Use this tab to add or modify services to ensure they load according to user consent preferences.
                    <br>
                    - <strong>Service Name</strong>: The name of the service (e.g., Google Analytics).
                    <br>
                    - <strong>Script Code</strong>: The script or HTML code that will be executed when the user accepts cookies.
                    <br>
                    - <strong>Script Position</strong>: Where on the page the script should be inserted (Head, Body Top, or Body Bottom).
                    <br>
                    - <strong>Mandatory Feature</strong>: If checked, this service will always be active and cannot be disabled by the user.
                    <br>
                    
                </p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row" style="width:330px">Enable Google Consent Mode v2</th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_consent_mode" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_consent_mode']) ? $options['snn_cookie_settings_enable_consent_mode'] : 'no'), 'yes'); ?>>
                            <span class="description">When enabled, the plugin will update Google Consent Mode (gtag) based on user consent.</span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Services (Repeater)</th>
                        <td>
                            <div id="services-repeater" class="snn-services-repeater">
                                <?php 
                                if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) {
                                    $index = 0;
                                    foreach ( $options['snn_cookie_settings_services'] as $service ) {
                                        ?>
                                        <div class="snn-service-item">
                                            <label>Service Name:
                                                <input type="text" name="snn_cookie_settings_services[<?php echo $index; ?>][name]" value="<?php echo isset($service['name']) ? esc_attr($service['name']) : ''; ?>" class="snn-input snn-service-name">
                                            </label>
                                            <label>Service Script Code (HTML allowed):
                                                <textarea name="snn_cookie_settings_services[<?php echo $index; ?>][script]" rows="4" class="snn-textarea snn-service-script-code"><?php echo isset($service['script']) ? $service['script'] : ''; ?></textarea>
                                            </label>
                                            <label>Script Position:</label>
                                            <div class="snn-radio-group">
                                                <label><input type="radio" name="snn_cookie_settings_services[<?php echo $index; ?>][position]" value="head" <?php checked((isset($service['position']) ? $service['position'] : ''), 'head'); ?>> Head</label>
                                                <label><input type="radio" name="snn_cookie_settings_services[<?php echo $index; ?>][position]" value="body_top" <?php checked((isset($service['position']) ? $service['position'] : ''), 'body_top'); ?>> Body Top</label>
                                                <label><input type="radio" name="snn_cookie_settings_services[<?php echo $index; ?>][position]" value="body_bottom" <?php checked((isset($service['position']) ? $service['position'] : ''), 'body_bottom'); ?>> Body Bottom</label>
                                            </div>
                                            <label>
                                                <input type="checkbox" name="snn_cookie_settings_services[<?php echo $index; ?>][mandatory]" value="yes" <?php checked((isset($service['mandatory']) ? $service['mandatory'] : 'no'), 'yes'); ?>> Mandatory Feature
                                            </label>
                                            <button class="remove-service snn-remove-service button">Remove</button>
                                        </div>
                                        <?php
                                        $index++;
                                    }
                                } else {
                                    // Output one empty service item if none exist.
                                    ?>
                                    <div class="snn-service-item">
                                        <label>Service Name:
                                            <input type="text" name="snn_cookie_settings_services[][name]" value="" class="snn-input snn-service-name">
                                        </label>
                                        <label>Service Script Code (HTML allowed):
                                            <textarea name="snn_cookie_settings_services[][script]" rows="4" class="snn-textarea snn-service-script-code"></textarea>
                                        </label>
                                        <label>Script Position:</label>
                                        <div class="snn-radio-group">
                                            <label><input type="radio" name="snn_cookie_settings_services[][position]" value="head" checked> Head</label>
                                            <label><input type="radio" name="snn_cookie_settings_services[][position]" value="body_top"> Body Top</label>
                                            <label><input type="radio" name="snn_cookie_settings_services[][position]" value="body_bottom"> Body Bottom</label>
                                        </div>
                                        <label>
                                            <input type="checkbox" name="snn_cookie_settings_services[][mandatory]" value="yes"> Mandatory Feature
                                        </label>
                                        <button class="remove-service snn-remove-service button">Remove</button>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <button id="add-service" class="button snn-add-service">Add Service</button>
                            <script>
                            (function($){
                                $(document).ready(function(){
                                    $('#add-service').click(function(e){
                                        e.preventDefault();
                                        var newService = '<div class="snn-service-item">' +
                                            '<label>Service Name:' +
                                                '<input type="text" name="snn_cookie_settings_services[][name]" value="" class="snn-input snn-service-name">' +
                                            '</label>' +
                                            '<label>Service Script Code (HTML allowed):' +
                                                '<textarea name="snn_cookie_settings_services[][script]" rows="4" class="snn-textarea snn-service-script-code"></textarea>' +
                                            '</label>' +
                                            '<label>Script Position:</label>' +
                                            '<div class="snn-radio-group">' +
                                                '<label><input type="radio" name="snn_cookie_settings_services[][position]" value="head" checked> Head</label> ' +
                                                '<label><input type="radio" name="snn_cookie_settings_services[][position]" value="body_top"> Body Top</label> ' +
                                                '<label><input type="radio" name="snn_cookie_settings_services[][position]" value="body_bottom"> Body Bottom</label>' +
                                            '</div>' +
                                            '<label><input type="checkbox" name="snn_cookie_settings_services[][mandatory]" value="yes"> Mandatory Feature</label>' +
                                            '<button class="remove-service snn-remove-service button">Remove</button>' +
                                            '</div>';
                                        $('#services-repeater').append(newService);
                                    });
                                    $('#services-repeater').on('click', '.remove-service', function(e){
                                        e.preventDefault();
                                        $(this).closest('.snn-service-item').remove();
                                    });
                                });
                            })(jQuery);
                            </script>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Custom CSS for Cookie Banner</th>
                        <td>
                            <textarea name="snn_cookie_settings_custom_css" rows="5" class="snn-textarea snn-custom-css-textarea"><?php echo isset($options['snn_cookie_settings_custom_css']) ? esc_textarea($options['snn_cookie_settings_custom_css']) : ''; ?></textarea>
                            <p class="description">
                                Use the following CSS selectors to style the banner:<br>
                                <code>.snn-cookie-banner</code> - The cookie banner container<br>
                                <code>.snn-preferences-content</code> - The preferences content container inside the banner<br>
                                <code>.snn-banner-text</code> - The banner text<br>
                                <code>.snn-banner-buttons .snn-button</code> - The banner buttons (Accept, Deny, Preferences)<br>
                                <code>.snn-preferences-title</code> - The title in the preferences content<br>
                                <code>.snn-services-list</code> - The list of services<br>
                                <code>.snn-service-item</code> - Each individual service item
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php submit_button(); ?>
        </form>
        <!-- Tabs Script -->
        <script>
        (function($){
            $(document).ready(function(){
                $('.snn-tab').click(function(){
                    var tab = $(this).data('tab');
                    $('.snn-tab').removeClass('active');
                    $(this).addClass('active');
                    $('.snn-tab-content').removeClass('active');
                    $('#' + tab).addClass('active');
                });
            });
        })(jQuery);
        </script>
    </div>
    <?php
}

/* ============================================================================ 
   FRONTEND COOKIE BANNER, CUSTOM CSS & SCRIPT ENCODING
============================================================================ */

/**
 * 1) Output the cookie banner.
 *    The banner will only output if the admin has enabled it.
 */
function snn_output_cookie_banner() {
    $options = get_option( SNN_OPTIONS );
    if ( ! $options ) {
        return;
    }
    // If the cookie banner is not enabled, do not output it.
    if ( empty($options['snn_cookie_settings_enable_cookie_banner']) || $options['snn_cookie_settings_enable_cookie_banner'] !== 'yes' ) {
        return;
    }
    
    // Determine banner position class and output dynamic CSS
    $position = isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : 'left';
    
    // Check for an existing consent cookie to hide the banner immediately (to avoid flashing)
    $accepted = isset($_COOKIE['snn_cookie_accepted']) ? $_COOKIE['snn_cookie_accepted'] : '';
    $banner_style = ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' style="display: none;"' : '';
    ?>
    <style id="snn-dynamic-styles">
    .snn-cookie-banner {
       position: fixed;
       bottom: 10px;
       width: 400px;
       z-index: 9999;
       padding: 15px;
       background: <?php echo isset($options['snn_cookie_settings_banner_bg_color']) ? esc_attr($options['snn_cookie_settings_banner_bg_color']) : '#333333'; ?>;
       color: <?php echo isset($options['snn_cookie_settings_banner_text_color']) ? esc_attr($options['snn_cookie_settings_banner_text_color']) : '#ffffff'; ?>;
       box-shadow:0px 0px 10px #00000055;
       border-radius:10px;
       margin:10px;
    }
    .snn-cookie-banner.left { left: 0; }
    .snn-cookie-banner.middle { left: 50%; transform: translateX(-50%); }
    .snn-cookie-banner.right { right: 0; }
    
    .snn-preferences-content {
        display: none;
        padding-top: 10px;
    }
    .snn-banner-buttons {
        display: flex;
        flex-direction: row;
    }
    .snn-banner-buttons .snn-button {
        margin-right: 10px;
        background: <?php echo isset($options['snn_cookie_settings_button_bg_color']) ? esc_attr($options['snn_cookie_settings_button_bg_color']) : '#555555'; ?>;
        color: <?php echo isset($options['snn_cookie_settings_button_text_color']) ? esc_attr($options['snn_cookie_settings_button_text_color']) : '#ffffff'; ?>;
        border: none;
        padding: 10px;
        cursor: pointer;
        margin-top:10px;
        border-radius:5px;
        width: 100%;
        text-align: center;
    }
    .snn-banner-buttons .snn-button:last-child {
       margin-right: 0;
    }
    .snn-preferences-title {
        margin-top: 0;
        font-weight:600
    }
    /* Toggle switch style */
    .snn-switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 20px;
    }
    .snn-switch input { display: none; }
    .snn-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #d9534f;
      transition: .4s;
      border-radius: 20px;
    }
    .snn-slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 2px;
      bottom: 2px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    .snn-switch input:checked + .snn-slider {
      background-color: #5cb85c;
    }
    .snn-switch input:checked + .snn-slider:before {
      transform: translateX(20px);
    }
    /* Disabled (mandatory) switch styling */
    .snn-switch input:disabled + .snn-slider {
      background-color: #ccc;
      cursor: not-allowed;
    }
    /* Responsive Styles for small screens */
    @media (max-width: 768px) {
      .snn-cookie-banner {
          width: calc(100% - 20px);
          left: 0 !important;
          right: 0 !important;
          transform: none !important;
          padding: 10px;
      }
      .snn-banner-buttons {
          display: flex;
          flex-direction: column;
      }
      .snn-banner-buttons .snn-button {
          margin-top:8px;
          width: 100%;
          text-align: center;
      }
      .snn-banner-buttons .snn-button:last-child {
          margin-bottom: 0;
      }
    }
    </style>
    <div id="snn-cookie-banner" class="snn-cookie-banner <?php echo esc_attr($position); ?>"<?php echo $banner_style; ?>>
        <div class="snn-preferences-content">
            <div class="snn-preferences-title">Cookie Preferences</div>
            <?php if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) { ?>
                <ul class="snn-services-list" style="list-style: none; padding: 0;">
                <?php foreach ( $options['snn_cookie_settings_services'] as $index => $service ) { ?>
                    <li class="snn-service-item" style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <span class="snn-service-name">
                            <?php echo esc_html( $service['name'] ); ?>
                            <?php if ( isset($service['mandatory']) && $service['mandatory'] === 'yes' ) { ?>
                                <span style="color:black; font-weight:bold">*</span>
                            <?php } ?>
                        </span>
                        <label class="snn-switch">
                            <input type="checkbox" data-service-index="<?php echo esc_attr($index); ?>" class="snn-service-toggle" <?php echo (isset($service['mandatory']) && $service['mandatory'] === 'yes') ? 'checked disabled' : 'checked'; ?>>
                            <span class="snn-slider"></span>
                        </label>
                    </li>
                <?php } ?>
                </ul>
            <?php } ?>
        </div>
        <p class="snn-banner-text"><?php echo esc_html( isset($options['snn_cookie_settings_banner_description']) ? $options['snn_cookie_settings_banner_description'] : '' ); ?></p>
        <div class="snn-banner-buttons">
            <button class="snn-button snn-accept"><?php echo esc_html( isset($options['snn_cookie_settings_accept_button']) ? $options['snn_cookie_settings_accept_button'] : 'Accept' ); ?></button>
            <button class="snn-button snn-deny"><?php echo esc_html( isset($options['snn_cookie_settings_deny_button']) ? $options['snn_cookie_settings_deny_button'] : 'Deny' ); ?></button>
            <button class="snn-button snn-preferences"><?php echo esc_html( isset($options['snn_cookie_settings_preferences_button']) ? $options['snn_cookie_settings_preferences_button'] : 'Preferences' ); ?></button>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'snn_output_cookie_banner');

/**
 * 2) Output service scripts as Base64-encoded data in hidden divs.
 *    This replaces the previous static script areas.
 */
function snn_output_service_scripts() {
    $options = get_option( SNN_OPTIONS );
    if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) {
        foreach ( $options['snn_cookie_settings_services'] as $index => $service ) {
            if ( ! empty( $service['script'] ) ) {
                ?>
                <div 
                    id="snn-service-script-<?php echo esc_attr($index); ?>" 
                    class="snn-service-script" 
                    data-script="<?php echo esc_attr( base64_encode($service['script']) ); ?>" 
                    data-position="<?php echo esc_attr( isset($service['position']) ? $service['position'] : 'body_bottom' ); ?>" 
                    style="display: none;">
                </div>
                <?php
            }
        }
    }
}
add_action('wp_footer', 'snn_output_service_scripts', 99);

/**
 * 3) Add JavaScript to:
 *    - Save and check user preferences using cookies (with a 365-day expiration)
 *    - Dynamically inject service scripts from the hidden divs based on consent and custom preferences
 *    - Update Google Consent Mode v2 using gtag if enabled
 */
function snn_output_banner_js() {
    $options = get_option(SNN_OPTIONS);
    // Pass the banner-enabled flag to JS.
    $cookie_banner_enabled = ( isset($options['snn_cookie_settings_enable_cookie_banner']) && $options['snn_cookie_settings_enable_cookie_banner'] === 'yes' ) ? 'true' : 'false';
    ?>
    <script>
    (function(){
        // Cookie helper functions
        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days*24*60*60*1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }
        
        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while(c.charAt(0)==' ') c = c.substring(1,c.length);
                if(c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }
        
        function eraseCookie(name) {   
            document.cookie = name+'=; Max-Age=-99999999; path=/';  
        }
        
        // If the cookie banner is disabled in settings, inject all scripts immediately.
        var cookieBannerEnabled = <?php echo $cookie_banner_enabled; ?>;
        if (!cookieBannerEnabled) {
            var hiddenDivs = document.querySelectorAll('.snn-service-script[data-script]');
            hiddenDivs.forEach(function(div){
                var encoded = div.getAttribute('data-script');
                var position = div.getAttribute('data-position') || 'body_bottom';
                if (encoded) {
                    var decoded = atob(encoded);
                    injectScript(decoded, position);
                }
            });
            return;
        }
        
        // Google Consent Mode integration flag from settings
        var enableConsentMode = <?php echo ((isset($options['snn_cookie_settings_enable_consent_mode']) && $options['snn_cookie_settings_enable_consent_mode'] === 'yes') ? 'true' : 'false'); ?>;
        
        function updateGoogleConsent(consentValue) {
            if(enableConsentMode && typeof gtag === 'function'){
                gtag('consent', 'update', {
                    'ad_storage': consentValue,
                    'analytics_storage': consentValue,
                    'ad_user_data': consentValue,
                    'ad_personalization': consentValue
                });
            }
        }
        
        // Dynamically inject script into the specified position
        function injectScript(decodedCode, position) {
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = decodedCode;
        
            var scripts = tempDiv.querySelectorAll('script');
            scripts.forEach(function(s){
                var newScript = document.createElement('script');
                for (var i = 0; i < s.attributes.length; i++) {
                    var attr = s.attributes[i];
                    newScript.setAttribute(attr.name, attr.value);
                }
                newScript.text = s.text || '';
                if (position === 'head') {
                    document.head.appendChild(newScript);
                } else if (position === 'body_top') {
                    var body = document.body;
                    if (body.firstChild) {
                        body.insertBefore(newScript, body.firstChild);
                    } else {
                        body.appendChild(newScript);
                    }
                } else {
                    document.body.appendChild(newScript);
                }
            });
        }
        
        function injectAllConsentScripts() {
            var hiddenDivs = document.querySelectorAll('.snn-service-script[data-script]');
            hiddenDivs.forEach(function(div){
                var encoded = div.getAttribute('data-script');
                var position = div.getAttribute('data-position') || 'body_bottom';
                if (encoded) {
                    var decoded = atob(encoded);
                    injectScript(decoded, position);
                }
            });
        }
        
        function injectCustomConsentScripts() {
            var prefs = getCookie('snn_cookie_services');
            if(prefs) {
                var servicePrefs = JSON.parse(prefs);
                var hiddenDivs = document.querySelectorAll('.snn-service-script[data-script]');
                hiddenDivs.forEach(function(div){
                    var id = div.getAttribute('id'); // format: snn-service-script-INDEX
                    var parts = id.split('-');
                    var index = parts[parts.length-1];
                    if(servicePrefs[index]) {
                        var encoded = div.getAttribute('data-script');
                        var position = div.getAttribute('data-position') || 'body_bottom';
                        if (encoded) {
                            var decoded = atob(encoded);
                            injectScript(decoded, position);
                        }
                    }
                });
            }
        }
        
        // Event handlers for the banner buttons
        var acceptBtn = document.querySelector('.snn-accept');
        var denyBtn = document.querySelector('.snn-deny');
        var prefsBtn = document.querySelector('.snn-preferences');
        var banner = document.getElementById('snn-cookie-banner');
        
        if (acceptBtn) {
            acceptBtn.addEventListener('click', function(){
                var toggles = document.querySelectorAll('.snn-service-toggle');
                if(toggles.length > 0) {
                    var servicePrefs = {};
                    toggles.forEach(function(toggle) {
                        var index = toggle.getAttribute('data-service-index');
                        servicePrefs[index] = toggle.checked;
                    });
                    setCookie('snn_cookie_services', JSON.stringify(servicePrefs), 365);
                    setCookie('snn_cookie_accepted', 'custom', 365);
                    updateGoogleConsent('granted');
                    injectCustomConsentScripts();
                } else {
                    setCookie('snn_cookie_accepted', 'true', 365);
                    eraseCookie('snn_cookie_services');
                    updateGoogleConsent('granted');
                    injectAllConsentScripts();
                }
                if(banner) { banner.style.display = 'none'; }
            });
        }
        if (denyBtn) {
            denyBtn.addEventListener('click', function(){
                setCookie('snn_cookie_accepted', 'false', 365);
                eraseCookie('snn_cookie_services');
                if(banner) { banner.style.display = 'none'; }
                updateGoogleConsent('denied');
            });
        }
        if (prefsBtn) {
            prefsBtn.addEventListener('click', function(){
                var prefsContent = document.querySelector('.snn-preferences-content');
                if (prefsContent.style.display === 'none' || prefsContent.style.display === '') {
                    prefsContent.style.display = 'block';
                } else {
                    prefsContent.style.display = 'none';
                }
            });
        }
        
        // Check cookies for saved consent and act accordingly
        var storedConsent = getCookie('snn_cookie_accepted');
        if (storedConsent === 'true') {
            updateGoogleConsent('granted');
            injectAllConsentScripts();
            if(banner) { banner.style.display = 'none'; }
        } else if (storedConsent === 'false') {
            updateGoogleConsent('denied');
            if(banner) { banner.style.display = 'none'; }
        } else if (storedConsent === 'custom') {
            updateGoogleConsent('granted');
            injectCustomConsentScripts();
            if(banner) { banner.style.display = 'none'; }
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'snn_output_banner_js', 100);

/**
 * 4) Output custom CSS for the cookie banner
 */
function snn_output_custom_css() {
    $options = get_option( SNN_OPTIONS );
    if ( !empty($options['snn_cookie_settings_custom_css']) ) {
        echo "<style id='snn-custom-css'>" . $options['snn_cookie_settings_custom_css'] . "</style>";
    }
}
add_action('wp_head', 'snn_output_custom_css');
?>
