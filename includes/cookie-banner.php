<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

define('SNN_OPTIONS', 'snn_cookie_settings_options');

function snn_is_cookie_banner_enabled() {
    $options = get_option( SNN_OPTIONS );
    return ( !empty($options['snn_cookie_settings_enable_cookie_banner']) && $options['snn_cookie_settings_enable_cookie_banner'] === 'yes' );
}

function snn_add_cookie_settings_submenu() {
    add_submenu_page(
        'snn-settings',               
        __('SNN Cookie Settings', 'snn'),        
        __('Cookie Settings', 'snn'),          
        'manage_options',          
        'snn-cookie-settings',      
        'snn_options_page'            
    );
}
add_action('admin_menu', 'snn_add_cookie_settings_submenu', 10);

function snn_options_page() {
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'snn'));
    }
    
    if ( isset($_POST['snn_options_nonce']) && wp_verify_nonce( $_POST['snn_options_nonce'], 'snn_save_options' ) ) {
        $options = array();
        $options['snn_cookie_settings_enable_cookie_banner'] = isset($_POST['snn_cookie_settings_enable_cookie_banner']) ? 'yes' : 'no';
        $options['snn_cookie_settings_disable_for_logged_in']  = isset($_POST['snn_cookie_settings_disable_for_logged_in']) ? 'yes' : 'no';
        // NEW: Disable Scripts for Logged-In Users option
        $options['snn_cookie_settings_disable_scripts_for_logged_in'] = isset($_POST['snn_cookie_settings_disable_scripts_for_logged_in']) ? 'yes' : 'no';

        // Allow <p> tags with style and class, plus basic tags.
        $allowed = array(
            'p' => array(
                'style' => array(),
                'class' => array(),
            ),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'a' => array(
                'href' => array(),
                'title' => array(),
            ),
        );
        $options['snn_cookie_settings_banner_description'] = isset($_POST['snn_cookie_settings_banner_description']) ? wp_unslash($_POST['snn_cookie_settings_banner_description']) : '';
        $options['snn_cookie_settings_additional_description'] = isset($_POST['snn_cookie_settings_additional_description']) ? wp_unslash($_POST['snn_cookie_settings_additional_description']) : '';
        $options['snn_cookie_settings_enable_legal_text'] = isset($_POST['snn_cookie_settings_enable_legal_text']) ? 'yes' : 'no';
        
        $options['snn_cookie_settings_accept_button']        = isset($_POST['snn_cookie_settings_accept_button']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_accept_button']) ) : '';
        $options['snn_cookie_settings_deny_button']          = isset($_POST['snn_cookie_settings_deny_button']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_deny_button']) ) : '';
        $options['snn_cookie_settings_preferences_button']   = isset($_POST['snn_cookie_settings_preferences_button']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_preferences_button']) ) : '';
        $options['snn_cookie_settings_banner_position']      = isset($_POST['snn_cookie_settings_banner_position']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_position']) ) : '';
        $options['snn_cookie_settings_banner_vertical_position'] = isset($_POST['snn_cookie_settings_banner_vertical_position']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_vertical_position']) ) : '';
        $options['snn_cookie_settings_enable_overlay']       = isset($_POST['snn_cookie_settings_enable_overlay']) ? 'yes' : 'no';
        $options['snn_cookie_settings_overlay_color']        = isset($_POST['snn_cookie_settings_overlay_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_overlay_color']) ) : '';
        $options['snn_cookie_settings_overlay_opacity']      = isset($_POST['snn_cookie_settings_overlay_opacity']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_overlay_opacity']) ) : '';
        $options['snn_cookie_settings_banner_shadow_color']  = isset($_POST['snn_cookie_settings_banner_shadow_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_shadow_color']) ) : '';
        $options['snn_cookie_settings_banner_shadow_spread'] = isset($_POST['snn_cookie_settings_banner_shadow_spread']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_shadow_spread']) ) : '';
        $options['snn_cookie_settings_banner_bg_color']      = isset($_POST['snn_cookie_settings_banner_bg_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_bg_color']) ) : '';
        $options['snn_cookie_settings_banner_text_color']    = isset($_POST['snn_cookie_settings_banner_text_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_text_color']) ) : '';
        $options['snn_cookie_settings_button_bg_color']      = isset($_POST['snn_cookie_settings_button_bg_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_button_bg_color']) ) : '';
        $options['snn_cookie_settings_button_text_color']    = isset($_POST['snn_cookie_settings_button_text_color']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_button_text_color']) ) : '';
        $options['snn_cookie_settings_banner_width']         = isset($_POST['snn_cookie_settings_banner_width']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_width']) ) : '';
        $options['snn_cookie_settings_banner_border_radius'] = isset($_POST['snn_cookie_settings_banner_border_radius']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_banner_border_radius']) ) : '';
        $options['snn_cookie_settings_button_border_radius'] = isset($_POST['snn_cookie_settings_button_border_radius']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_button_border_radius']) ) : '';
        
        $services = array();
        if ( isset($_POST['snn_cookie_settings_services']) && is_array($_POST['snn_cookie_settings_services']) ) {
            foreach( $_POST['snn_cookie_settings_services'] as $service ) {
                if ( empty( $service['name'] ) ) {
                    continue;
                }
                $service_data = array();
                $service_data['name'] = sanitize_text_field( wp_unslash($service['name']) );
                $service_data['description'] = isset($service['description']) ? wp_unslash($service['description']) : ''; // NEW: Service Description
                $service_data['script'] = isset($service['script']) ? wp_unslash($service['script']) : '';
                $service_data['position'] = isset($service['position']) ? sanitize_text_field( wp_unslash($service['position']) ) : 'body_bottom';
                $service_data['mandatory'] = isset($service['mandatory']) ? 'yes' : 'no';
                $services[] = $service_data;
            }
        }
        $options['snn_cookie_settings_services'] = $services;
        
        $options['snn_cookie_settings_custom_css'] = isset($_POST['snn_cookie_settings_custom_css']) ? wp_unslash($_POST['snn_cookie_settings_custom_css']) : '';
        
        update_option( SNN_OPTIONS, $options );
        echo '<div class="updated"><p>' . __('Settings saved.', 'snn') . '</p></div>';
    }
    
    $options = get_option( SNN_OPTIONS );
    if ( !is_array($options) ) {
        $options = array(
            'snn_cookie_settings_enable_cookie_banner' => 'no',
            'snn_cookie_settings_disable_for_logged_in'  => 'no',
            'snn_cookie_settings_disable_scripts_for_logged_in' => 'no',
            'snn_cookie_settings_banner_description'   => __('This website uses cookies for analytics and functionality.', 'snn'),
            'snn_cookie_settings_additional_description' => '<p style="text-align: center;"><a href="#">Imprint</a> - <a href="#">Privacy Policy</a></p>',
            'snn_cookie_settings_enable_legal_text' => 'no',
            'snn_cookie_settings_accept_button'        => __('Accept', 'snn'),
            'snn_cookie_settings_deny_button'          => __('Deny', 'snn'),
            'snn_cookie_settings_preferences_button'   => __('Preferences', 'snn'),
            'snn_cookie_settings_services'             => array(
                array(
                    'name' => '',
                    'description' => '', // NEW: Default for Service Description
                    'script' => '',
                    'position' => 'body_bottom',
                    'mandatory' => 'no'
                )
            ),
            'snn_cookie_settings_custom_css'           => '',
            'snn_cookie_settings_banner_position'      => 'left',
            'snn_cookie_settings_banner_vertical_position' => 'bottom',
            'snn_cookie_settings_enable_overlay'       => 'no',
            'snn_cookie_settings_overlay_color'        => '#ffffff',
            'snn_cookie_settings_overlay_opacity'      => '0.5',
            'snn_cookie_settings_banner_bg_color'      => '#ffffff',
            'snn_cookie_settings_banner_text_color'    => '#000000',
            'snn_cookie_settings_button_bg_color'      => '#000000',
            'snn_cookie_settings_button_text_color'    => '#ffffff',
            'snn_cookie_settings_banner_width'         => '400',
            'snn_cookie_settings_banner_border_radius' => '0',
            'snn_cookie_settings_button_border_radius' => '0'
        );
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Cookie Banner', 'snn'); ?></h1>
        <style>
            .snn-textarea { width: 500px; }
            .snn-input { width: 300px; }
            .snn-color-picker { }
            .snn-services-repeater .snn-service-item { margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; max-width:600px }
            .snn-custom-css-textarea { width: 500px; }
            .snn-tab { cursor:pointer; display: inline-block; margin-right: 10px; padding: 8px 12px; border: 1px solid #ccc; border-bottom: none; background: #f1f1f1; }
            .snn-tab.active { background: #fff; font-weight: bold; }
            .snn-tab-content { border: 1px solid #ccc; padding: 15px; display: none; }
            .snn-tab-content.active { display: block; }
            .snn-service-item label { display: block; margin-bottom: 5px; }
            .snn-service-item input[type="text"],
            .snn-service-item textarea { width: 100%; }
            .snn-service-item .snn-radio-group label { margin-right: 10px; }
        </style>
        <div class="snn-tabs">
            <span class="snn-tab active" data-tab="general"><?php _e('General Settings', 'snn'); ?></span>
            <span class="snn-tab" data-tab="scripts"><?php _e('Scripts & Services', 'snn'); ?></span>
            <span class="snn-tab" data-tab="styles"><?php _e('Styles and Layout', 'snn'); ?></span>
        </div>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_options', 'snn_options_nonce' ); ?>
            <div id="general" class="snn-tab-content active">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Cookie Banner', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_cookie_banner" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_cookie_banner']) ? $options['snn_cookie_settings_enable_cookie_banner'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to enable the Cookie Banner on your site.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Disable for Logged-In Users', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_disable_for_logged_in" value="yes" <?php checked((isset($options['snn_cookie_settings_disable_for_logged_in']) ? $options['snn_cookie_settings_disable_for_logged_in'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to disable the Cookie Banner for users who are logged in.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Disable Scripts for Logged-In Users', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_disable_scripts_for_logged_in" value="yes" <?php checked((isset($options['snn_cookie_settings_disable_scripts_for_logged_in']) ? $options['snn_cookie_settings_disable_scripts_for_logged_in'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to disable the scripts loading for logged-in users.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cookie Banner Description', 'snn'); ?></th>
                        <td>
                            <?php 
                            wp_editor( 
                                isset($options['snn_cookie_settings_banner_description']) ? $options['snn_cookie_settings_banner_description'] : '', 
                                'snn_cookie_settings_banner_description_editor', 
                                array(
                                    'textarea_name' => 'snn_cookie_settings_banner_description',
                                    'textarea_rows' => 3,
                                ) 
                            ); 
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Legal Text or Links', 'snn'); ?></th>
                        <td>
                            <?php 
                            wp_editor( 
                                isset($options['snn_cookie_settings_additional_description']) ? $options['snn_cookie_settings_additional_description'] : '', 
                                'snn_cookie_settings_additional_description_editor', 
                                array(
                                    'textarea_name' => 'snn_cookie_settings_additional_description',
                                    'textarea_rows' => 3,
                                ) 
                            ); 
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Legal Text/Links', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_legal_text" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_legal_text']) ? $options['snn_cookie_settings_enable_legal_text'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to enable the legal text/links in the cookie banner.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Accept Button Text', 'snn'); ?></th>
                        <td>
                            <input type="text" name="snn_cookie_settings_accept_button" value="<?php echo isset($options['snn_cookie_settings_accept_button']) ? esc_attr($options['snn_cookie_settings_accept_button']) : ''; ?>" class="snn-input snn-accept-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Deny Button Text', 'snn'); ?></th>
                        <td>
                            <input type="text" name="snn_cookie_settings_deny_button" value="<?php echo isset($options['snn_cookie_settings_deny_button']) ? esc_attr($options['snn_cookie_settings_deny_button']) : ''; ?>" class="snn-input snn-deny-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Preferences Button Text', 'snn'); ?></th>
                        <td>
                            <input type="text" name="snn_cookie_settings_preferences_button" value="<?php echo isset($options['snn_cookie_settings_preferences_button']) ? esc_attr($options['snn_cookie_settings_preferences_button']) : ''; ?>" class="snn-input snn-preferences-button">
                        </td>
                    </tr>
                </table>
            </div>
            <div id="scripts" class="snn-tab-content">
                <p class="description">
                <?php _e('Use this tab to add or modify services to ensure they load according to user consent preferences.', 'snn'); ?>
                    <br>
                    - <strong><?php _e('Service Name', 'snn'); ?></strong>: <?php _e('The name of the service (e.g., Google Analytics).', 'snn'); ?>
                    <br>
                    - <strong><?php _e('Script Code', 'snn'); ?></strong>: <?php _e('The script or HTML code that will be executed when the user accepts cookies.', 'snn'); ?>
                    <br>
                    - <strong><?php _e('Script Position', 'snn'); ?></strong>: <?php _e('Where on the page the script should be inserted (Head, Body Top, or Body Bottom).', 'snn'); ?>
                    <br>
                    - <strong><?php _e('Mandatory Feature', 'snn'); ?></strong>: <?php _e('If checked, this service will always be active and cannot be disabled by the user.', 'snn'); ?>
                    <br>
                </p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Services (Repeater)', 'snn'); ?></th>
                        <td>
                            <div id="services-repeater" class="snn-services-repeater">
                                <?php 
                                $service_index = 0;
                                if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) {
                                    foreach ( $options['snn_cookie_settings_services'] as $service ) {
                                        ?>
                                    <div class="snn-service-item">
                                        <label><?php _e('Service Name:', 'snn'); ?>
                                            <input type="text" name="snn_cookie_settings_services[<?php echo $service_index; ?>][name]" value="<?php echo isset($service['name']) ? esc_attr($service['name']) : ''; ?>" class="snn-input snn-service-name">
                                        </label>
                                        <label><?php _e('Service Description:', 'snn'); ?>
                                            <textarea name="snn_cookie_settings_services[<?php echo $service_index; ?>][description]" rows="2" class="snn-textarea snn-service-description"><?php echo isset($service['description']) ? $service['description'] : ''; ?></textarea>
                                        </label>
                                        <label><?php _e('Service Script Code (HTML allowed):', 'snn'); ?>
                                            <textarea name="snn_cookie_settings_services[<?php echo $service_index; ?>][script]" rows="4" class="snn-textarea snn-service-script-code"><?php echo isset($service['script']) ? $service['script'] : ''; ?></textarea>
                                            </label>
                                            <label><?php _e('Script Position:', 'snn'); ?></label>
                                            <div class="snn-radio-group">
                                                <label><input type="radio" name="snn_cookie_settings_services[<?php echo $service_index; ?>][position]" value="head" <?php checked((isset($service['position']) ? $service['position'] : ''), 'head'); ?>> <?php _e('Head', 'snn'); ?></label>
                                                <label><input type="radio" name="snn_cookie_settings_services[<?php echo $service_index; ?>][position]" value="body_top" <?php checked((isset($service['position']) ? $service['position'] : ''), 'body_top'); ?>> <?php _e('Body Top', 'snn'); ?></label>
                                                <label><input type="radio" name="snn_cookie_settings_services[<?php echo $service_index; ?>][position]" value="body_bottom" <?php checked((isset($service['position']) ? $service['position'] : ''), 'body_bottom'); ?>> <?php _e('Body Bottom', 'snn'); ?></label>
                                            </div>
                                            <label>
                                                <input type="checkbox" name="snn_cookie_settings_services[<?php echo $service_index; ?>][mandatory]" value="yes" <?php checked((isset($service['mandatory']) ? $service['mandatory'] : 'no'), 'yes'); ?>> <?php _e('Mandatory Feature', 'snn'); ?>
                                            </label>
                                            <button class="remove-service snn-remove-service button"><?php _e('Remove', 'snn'); ?></button>
                                        </div>
                                        <?php
                                        $service_index++;
                                    }
                                } else {
                                    ?>
                                    <div class="snn-service-item">
                                        <label><?php _e('Service Name:', 'snn'); ?>
                                            <input type="text" name="snn_cookie_settings_services[0][name]" value="" class="snn-input snn-service-name">
                                        </label>
                                        <label><?php _e('Service Description:', 'snn'); ?>
                                            <textarea name="snn_cookie_settings_services[0][description]" rows="2" class="snn-textarea snn-service-description"></textarea>
                                        </label>
                                        <label><?php _e('Service Script Code (HTML allowed):', 'snn'); ?>
                                            <textarea name="snn_cookie_settings_services[0][script]" rows="4" class="snn-textarea snn-service-script-code"></textarea>
                                        </label>
                                        <label><?php _e('Script Position:', 'snn'); ?></label>
                                        <div class="snn-radio-group">
                                            <label><input type="radio" name="snn_cookie_settings_services[0][position]" value="head"> <?php _e('Head', 'snn'); ?></label>
                                            <label><input type="radio" name="snn_cookie_settings_services[0][position]" value="body_top"> <?php _e('Body Top', 'snn'); ?></label>
                                            <label><input type="radio" name="snn_cookie_settings_services[0][position]" value="body_bottom" checked> <?php _e('Body Bottom', 'snn'); ?></label>
                                        </div>
                                        <label>
                                            <input type="checkbox" name="snn_cookie_settings_services[0][mandatory]" value="yes"> <?php _e('Mandatory Feature', 'snn'); ?>
                                        </label>
                                        <button class="remove-service snn-remove-service button"><?php _e('Remove', 'snn'); ?></button>
                                    </div>
                                    <?php
                                    $service_index = 1; 
                                }
                                ?>
                            </div>
                            <button id="add-service" class="button snn-add-service"><?php _e('Add Service', 'snn'); ?></button>
                            <script>
                            (function($){
                                $(document).ready(function(){
                                    var serviceIndex = <?php echo $service_index; ?>;
                                    $('#add-service').click(function(e){
                                        e.preventDefault();
                                        var newService = '<div class="snn-service-item">' +
                                            '<label><?php _e('Service Name:', 'snn'); ?>' +
                                                '<input type="text" name="snn_cookie_settings_services[' + serviceIndex + '][name]" value="" class="snn-input snn-service-name">' +
                                            '</label>' +
                                            '<label><?php _e('Service Description:', 'snn'); ?>' +
                                                '<textarea name="snn_cookie_settings_services[' + serviceIndex + '][description]" rows="2" class="snn-textarea snn-service-description"></textarea>' +
                                            '</label>' +
                                            '<label><?php _e('Service Script Code (HTML allowed):', 'snn'); ?>' +
                                                '<textarea name="snn_cookie_settings_services[' + serviceIndex + '][script]" rows="4" class="snn-textarea snn-service-script-code"></textarea>' +
                                            '</label>' +
                                            '<label><?php _e('Script Position:', 'snn'); ?></label>' +
                                            '<div class="snn-radio-group">' +
                                                '<label><input type="radio" name="snn_cookie_settings_services[' + serviceIndex + '][position]" value="head"> <?php _e('Head', 'snn'); ?></label> ' +
                                                '<label><input type="radio" name="snn_cookie_settings_services[' + serviceIndex + '][position]" value="body_top"> <?php _e('Body Top', 'snn'); ?></label> ' +
                                                '<label><input type="radio" name="snn_cookie_settings_services[' + serviceIndex + '][position]" value="body_bottom" checked> <?php _e('Body Bottom', 'snn'); ?></label>' +
                                            '</div>' +
                                            '<label><input type="checkbox" name="snn_cookie_settings_services[' + serviceIndex + '][mandatory]" value="yes"> <?php _e('Mandatory Feature', 'snn'); ?></label>' +
                                            '<button class="remove-service snn-remove-service button"><?php _e('Remove', 'snn'); ?></button>' +
                                            '</div>';
                                        $('#services-repeater').append(newService);
                                        serviceIndex++;
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
                </table>
            </div>
            <div id="styles" class="snn-tab-content">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Cookie Banner Background Color', 'snn'); ?></th>
                        <td>
                            <input type="color" name="snn_cookie_settings_banner_bg_color" value="<?php echo isset($options['snn_cookie_settings_banner_bg_color']) ? esc_attr($options['snn_cookie_settings_banner_bg_color']) : '#333333'; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cookie Banner Text Color', 'snn'); ?></th>
                        <td>
                            <input type="color" name="snn_cookie_settings_banner_text_color" value="<?php echo isset($options['snn_cookie_settings_banner_text_color']) ? esc_attr($options['snn_cookie_settings_banner_text_color']) : '#ffffff'; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Button Background Color', 'snn'); ?></th>
                        <td>
                            <input type="color" name="snn_cookie_settings_button_bg_color" value="<?php echo isset($options['snn_cookie_settings_button_bg_color']) ? esc_attr($options['snn_cookie_settings_button_bg_color']) : '#555555'; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Button Text Color', 'snn'); ?></th>
                        <td>
                            <input type="color" name="snn_cookie_settings_button_text_color" value="<?php echo isset($options['snn_cookie_settings_button_text_color']) ? esc_attr($options['snn_cookie_settings_button_text_color']) : '#ffffff'; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Banner Width', 'snn'); ?></th>
                        <td>
                            <input type="number" name="snn_cookie_settings_banner_width" value="<?php echo isset($options['snn_cookie_settings_banner_width']) ? esc_attr($options['snn_cookie_settings_banner_width']) : '400'; ?>" class="snn-input">
                            <p class="description"><?php _e('Width of the cookie banner in pixels.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Banner Border Radius', 'snn'); ?></th>
                        <td>
                            <input type="number" name="snn_cookie_settings_banner_border_radius" value="<?php echo isset($options['snn_cookie_settings_banner_border_radius']) ? esc_attr($options['snn_cookie_settings_banner_border_radius']) : '10'; ?>" class="snn-input">
                            <p class="description"><?php _e('Border radius of the cookie banner in pixels.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Button Border Radius', 'snn'); ?></th>
                        <td>
                            <input type="number" name="snn_cookie_settings_button_border_radius" value="<?php echo isset($options['snn_cookie_settings_button_border_radius']) ? esc_attr($options['snn_cookie_settings_button_border_radius']) : '5'; ?>" class="snn-input">
                            <p class="description"><?php _e('Border radius of the buttons in pixels.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cookie Banner Position', 'snn'); ?></th>
                        <td>
                            <select name="snn_cookie_settings_banner_position" class="snn-select snn-banner-position">
                                <option value="left" <?php selected((isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : ''), 'left'); ?>><?php _e('Left', 'snn'); ?></option>
                                <option value="middle" <?php selected((isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : ''), 'middle'); ?>><?php _e('Middle', 'snn'); ?></option>
                                <option value="right" <?php selected((isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : ''), 'right'); ?>><?php _e('Right', 'snn'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select the horizontal position of the cookie banner on your website.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Cookie Banner Vertical Position', 'snn'); ?></th>
                        <td>
                            <select name="snn_cookie_settings_banner_vertical_position" class="snn-select snn-banner-vertical-position">
                                <option value="bottom" <?php selected((isset($options['snn_cookie_settings_banner_vertical_position']) ? $options['snn_cookie_settings_banner_vertical_position'] : ''), 'bottom'); ?>><?php _e('Bottom', 'snn'); ?></option>
                                <option value="middle" <?php selected((isset($options['snn_cookie_settings_banner_vertical_position']) ? $options['snn_cookie_settings_banner_vertical_position'] : ''), 'middle'); ?>><?php _e('Middle', 'snn'); ?></option>
                                <option value="top" <?php selected((isset($options['snn_cookie_settings_banner_vertical_position']) ? $options['snn_cookie_settings_banner_vertical_position'] : ''), 'top'); ?>><?php _e('Top', 'snn'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select the vertical position of the cookie banner on your website.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Overlay Color', 'snn'); ?></th>
                        <td>
                            <input type="color" name="snn_cookie_settings_overlay_color" value="<?php echo isset($options['snn_cookie_settings_overlay_color']) ? esc_attr($options['snn_cookie_settings_overlay_color']) : '#000000'; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Overlay Opacity', 'snn'); ?></th>
                        <td>
                            <input type="number" step="0.1" min="0" max="1" name="snn_cookie_settings_overlay_opacity" value="<?php echo isset($options['snn_cookie_settings_overlay_opacity']) ? esc_attr($options['snn_cookie_settings_overlay_opacity']) : '0.5'; ?>" class="snn-input">
                            <p class="description"><?php _e('Set the opacity of the overlay (0 = transparent, 1 = opaque).', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Overlay', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_overlay" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_overlay']) ? $options['snn_cookie_settings_enable_overlay'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to enable a full page overlay behind the cookie banner.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Banner Shadow Color', 'snn'); ?></th>
                        <td>
                            <input type="color" name="snn_cookie_settings_banner_shadow_color" value="<?php echo isset($options['snn_cookie_settings_banner_shadow_color']) ? esc_attr($options['snn_cookie_settings_banner_shadow_color']) : '#000000'; ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Banner Shadow Spread', 'snn'); ?></th>
                        <td>
                            <input type="number" name="snn_cookie_settings_banner_shadow_spread" value="<?php echo isset($options['snn_cookie_settings_banner_shadow_spread']) ? esc_attr($options['snn_cookie_settings_banner_shadow_spread']) : '10'; ?>" class="snn-input">
                            <p class="description"><?php _e('Set the spread radius of the box shadow in pixels.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Custom CSS for Cookie Banner', 'snn'); ?></th>
                        <td>
                            <textarea name="snn_cookie_settings_custom_css" rows="5" class="snn-textarea snn-custom-css-textarea"><?php echo isset($options['snn_cookie_settings_custom_css']) ? esc_textarea($options['snn_cookie_settings_custom_css']) : ''; ?></textarea>
                            <p class="description">
                                <?php _e('Use the following CSS selectors to style the banner:', 'snn'); ?><br>
                                <code>.snn-cookie-banner</code> - <?php _e('The cookie banner container', 'snn'); ?><br>
                                <code>.snn-preferences-content</code> - <?php _e('The preferences content container inside the banner', 'snn'); ?><br>
                                <code>.snn-banner-text</code> - <?php _e('The banner text', 'snn'); ?><br>
                                <code>.snn-banner-buttons .snn-button</code> - <?php _e('The banner buttons (Accept, Deny, Preferences)', 'snn'); ?><br>
                                <code>.snn-preferences-title</code> - <?php _e('The title in the preferences content', 'snn'); ?><br>
                                <code>.snn-services-list</code> - <?php _e('The list of services', 'snn'); ?><br>
                                <code>.snn-service-item</code> - <?php _e('Each individual service item', 'snn'); ?><br>
                                <code>.snn-legal-text</code> - <?php _e('Bottom Rich Text', 'snn'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php submit_button(); ?>
        </form>
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


function snn_output_cookie_banner() {
    $options = get_option( SNN_OPTIONS );
    if ( ! $options ) {
        return;
    }
    if ( empty($options['snn_cookie_settings_enable_cookie_banner']) || $options['snn_cookie_settings_enable_cookie_banner'] !== 'yes' ) {
        return;
    }
    if ( ! empty($options['snn_cookie_settings_disable_for_logged_in']) && $options['snn_cookie_settings_disable_for_logged_in'] === 'yes' && is_user_logged_in() ) {
        return;
    }
    
    $position = isset($options['snn_cookie_settings_banner_position']) ? $options['snn_cookie_settings_banner_position'] : 'left';
    $vertical_position = isset($options['snn_cookie_settings_banner_vertical_position']) ? $options['snn_cookie_settings_banner_vertical_position'] : 'bottom';
    $enable_overlay = isset($options['snn_cookie_settings_enable_overlay']) ? $options['snn_cookie_settings_enable_overlay'] : 'no';
    $overlay_color = isset($options['snn_cookie_settings_overlay_color']) ? $options['snn_cookie_settings_overlay_color'] : '#000000';
    $overlay_opacity = isset($options['snn_cookie_settings_overlay_opacity']) ? $options['snn_cookie_settings_overlay_opacity'] : '0.5';
    $banner_width = isset($options['snn_cookie_settings_banner_width']) ? $options['snn_cookie_settings_banner_width'] : '400';
    $banner_border_radius = isset($options['snn_cookie_settings_banner_border_radius']) ? $options['snn_cookie_settings_banner_border_radius'] : '10';
    $button_border_radius = isset($options['snn_cookie_settings_button_border_radius']) ? $options['snn_cookie_settings_button_border_radius'] : '5';
    
    $accepted = isset($_COOKIE['snn_cookie_accepted']) ? $_COOKIE['snn_cookie_accepted'] : '';
    $banner_style = ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' style="display: none;"' : '';
    $overlay_style = ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' style="display: none;"' : '';
    ?>
    <?php if ($enable_overlay === 'yes') : ?>
    <div id="snn-cookie-overlay" class="snn-cookie-overlay"<?php echo $overlay_style; ?> style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: <?php echo esc_attr($overlay_color); ?>; opacity: <?php echo esc_attr($overlay_opacity); ?>; z-index: 9998;<?php echo ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' display: none;' : ''; ?>"></div>
    <?php endif; ?>
    
    <style id="snn-dynamic-styles">
        .snn-cookie-banner {position:fixed;<?php if ($vertical_position === 'top') : ?>top:10px;<?php elseif ($vertical_position === 'middle') : ?>top:50%;transform:translateY(-50%);<?php else : ?>bottom:10px;<?php endif; ?>width:<?php echo esc_attr($banner_width); ?>px;z-index:9999;padding:20px;background:<?php echo isset($options['snn_cookie_settings_banner_bg_color']) ? esc_attr($options['snn_cookie_settings_banner_bg_color']) : '#333333'; ?>;color:<?php echo isset($options['snn_cookie_settings_banner_text_color']) ? esc_attr($options['snn_cookie_settings_banner_text_color']) : '#ffffff'; ?>;box-shadow:0px 0px <?php echo esc_attr($options['snn_cookie_settings_banner_shadow_spread']); ?>px <?php echo esc_attr($options['snn_cookie_settings_banner_shadow_color']); ?>44;border-radius:<?php echo esc_attr($banner_border_radius); ?>px;margin:10px;}
        .snn-cookie-banner.left{left:0;}
        .snn-cookie-banner.middle{left:50%;<?php if ($vertical_position === 'middle') : ?>transform:translate(-50%,-50%);<?php else : ?>transform:translateX(-50%);<?php endif; ?>}
        .snn-cookie-banner.right{right:0;}
        .snn-preferences-content{display:none;}
        .snn-banner-buttons{display:flex;flex-direction:row;gap:10px}
        .snn-banner-text{margin-bottom:10px;}
        .snn-service-name span{font-weight:900;}
        .snn-legal-text{font-size:14px;margin-top:10px;}
        .snn-banner-buttons .snn-button{background:<?php echo isset($options['snn_cookie_settings_button_bg_color']) ? esc_attr($options['snn_cookie_settings_button_bg_color']) : '#555555'; ?>;color:<?php echo isset($options['snn_cookie_settings_button_text_color']) ? esc_attr($options['snn_cookie_settings_button_text_color']) : '#ffffff'; ?>;border:none;padding:10px;cursor:pointer;border-radius:<?php echo esc_attr($button_border_radius); ?>px;width:100%;text-align:center;}
        .snn-banner-buttons .snn-button:last-child{margin-right:0;}
        .snn-preferences-title{margin-top:0;font-weight:600;text-align:center;}
        .snn-switch{position:relative;display:inline-block;width:40px;height:20px;}
        .snn-switch input{display:none;}
        .snn-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#d9534f;transition:.4s;border-radius:20px;}
        .snn-slider:before{position:absolute;content:"";height:16px;width:16px;left:2px;bottom:2px;background-color:white;transition:.4s;border-radius:50%;}
        .snn-switch input:checked+.snn-slider{background-color:#5cb85c;}
        .snn-switch input:checked+.snn-slider:before{transform:translateX(20px);}
        .snn-switch input:disabled+.snn-slider{background-color:#ccc;cursor:not-allowed;}
        @media (max-width:768px){
            .snn-cookie-banner{width:calc(100% - 20px);left:0!important;right:0!important;transform:none!important;padding:10px;}
            .snn-banner-buttons{display:flex;flex-direction:column;}
            .snn-banner-buttons .snn-button{width:100%;text-align:center;}
            .snn-banner-buttons .snn-button:last-child{margin-bottom:0;}
        }
    </style>
    <div id="snn-cookie-banner" class="snn-cookie-banner <?php echo esc_attr($position); ?>"<?php echo $banner_style; ?>>
        <div class="snn-preferences-content">
            <div class="snn-preferences-title"><?php _e('Cookie Preferences', 'snn'); ?></div>
            <?php if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) { ?>
                <ul class="snn-services-list" style="list-style: none; padding: 0;">
                <?php foreach ( $options['snn_cookie_settings_services'] as $index => $service ) { ?>
                    <li class="snn-service-item" style="margin-bottom: 10px; display: flex; flex-direction: column; align-items: flex-start; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; width: 100%;">
                            <span class="snn-service-name">
                                <strong><?php echo esc_html( $service['name'] ); ?></strong>
                                <?php if ( isset($service['mandatory']) && $service['mandatory'] === 'yes' ) { ?>
                                    <span>
                                            <?php _e('*', 'snn'); ?> 
                                    </span>
                                <?php } ?>
                            </span>
                            <label class="snn-switch">
                                <input type="checkbox" data-service-index="<?php echo esc_attr($index); ?>" class="snn-service-toggle" <?php echo (isset($service['mandatory']) && $service['mandatory'] === 'yes') ? 'checked disabled' : 'checked'; ?>>
                                <span class="snn-slider"></span>
                            </label>
                        </div>
                        <?php if ( !empty($service['description']) ) { ?>
                            <p class="snn-service-description-text" style="margin-top: 5px; margin-bottom: 0; font-size: 0.9em;"><?php echo esc_html( $service['description'] ); ?></p>
                        <?php } ?>
                    </li>
                <?php } ?>
                </ul>
            <?php } ?>
        </div>
        <div class="snn-banner-text">
            <?php 
            // Output the banner description allowing inline styles.
            $allowed = array(
                'p' => array(
                    'style' => array(),
                    'class' => array(),
                ),
                'br' => array(),
                'strong' => array(),
                'em' => array(),
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                ),
            );
            echo  $options['snn_cookie_settings_banner_description'];
            ?>
        </div>
        <div class="snn-banner-buttons">
            <button class="snn-button snn-accept"><?php echo esc_html( isset($options['snn_cookie_settings_accept_button']) ? $options['snn_cookie_settings_accept_button'] : __('Accept', 'snn') ); ?></button>
            <button class="snn-button snn-deny"><?php echo esc_html( isset($options['snn_cookie_settings_deny_button']) ? $options['snn_cookie_settings_deny_button'] : __('Deny', 'snn') ); ?></button>
            <button class="snn-button snn-preferences"><?php echo esc_html( isset($options['snn_cookie_settings_preferences_button']) ? $options['snn_cookie_settings_preferences_button'] : __('Preferences', 'snn') ); ?></button>
        </div>
        <?php if ( !empty($options['snn_cookie_settings_enable_legal_text']) && $options['snn_cookie_settings_enable_legal_text'] === 'yes' ) : ?>
        <div class="snn-legal-text">
            <?php 
            echo  $options['snn_cookie_settings_additional_description'];
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
add_action('wp_footer', 'snn_output_cookie_banner');

function snn_output_service_scripts() {
    if ( ! snn_is_cookie_banner_enabled() ) {
        return;
    }
    $options = get_option( SNN_OPTIONS );
    if ( is_user_logged_in() && !empty($options['snn_cookie_settings_disable_scripts_for_logged_in']) && $options['snn_cookie_settings_disable_scripts_for_logged_in'] === 'yes' ) {
         return;
    }
    
    if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) {
        foreach ( $options['snn_cookie_settings_services'] as $index => $service ) {
            if ( ! empty( $service['script'] ) ) {
                ?>
                <div 
                    id="snn-service-script-<?php echo esc_attr($index); ?>" 
                    class="snn-service-script" 
                    data-script="<?php echo esc_attr( base64_encode($service['script']) ); ?>" 
                    data-position="<?php echo esc_attr( isset($service['position']) ? $service['position'] : 'body_bottom' ); ?>"
                    data-mandatory="<?php echo (isset($service['mandatory']) && $service['mandatory'] === 'yes') ? 'yes' : 'no'; ?>"
                    data-description="<?php echo esc_attr( isset($service['description']) ? $service['description'] : '' ); ?>" 
                    style="display: none;">
                </div>
                <?php
            }
        }
    }
}
add_action('wp_footer', 'snn_output_service_scripts', 99);

function snn_output_banner_js() {
    if ( ! snn_is_cookie_banner_enabled() ) {
        return;
    }
    $options = get_option(SNN_OPTIONS);
    if ( is_user_logged_in() && !empty($options['snn_cookie_settings_disable_scripts_for_logged_in']) && $options['snn_cookie_settings_disable_scripts_for_logged_in'] === 'yes' ) {
         return;
    }
    $cookie_banner_enabled = ( isset($options['snn_cookie_settings_enable_cookie_banner']) && $options['snn_cookie_settings_enable_cookie_banner'] === 'yes' ) ? 'true' : 'false';
    ?>

<script>
    (function(){
        function setCookie(n,v,d){var e="";if(d){var t=new Date;t.setTime(t.getTime()+864e5*d),e="; expires="+t.toUTCString()}document.cookie=n+"="+(v||"")+e+"; path=/"}
        function getCookie(n){for(var e=n+"=",t=document.cookie.split(";"),i=0;i<t.length;i++){for(var o=t[i];" "==o.charAt(0);)o=o.substring(1,o.length);if(0==o.indexOf(e))return o.substring(e.length,o.length)}return null}
        function eraseCookie(n){document.cookie=n+"=; Max-Age=-99999999; path=/"}
        var cookieBannerEnabled=<?php echo $cookie_banner_enabled; ?>;
        function injectScript(c,p){var d=document.createElement("div");d.innerHTML=c;d.querySelectorAll("script").forEach(function(s){var n=document.createElement("script");for(var i=0;i<s.attributes.length;i++){var a=s.attributes[i];n.setAttribute(a.name,a.value)}n.text=s.text||"";"head"===p?document.head.appendChild(n):"body_top"===p?document.body.firstChild?document.body.insertBefore(n,document.body.firstChild):document.body.appendChild(n):document.body.appendChild(n)})}
        function injectMandatoryScripts(){document.querySelectorAll('.snn-service-script[data-mandatory="yes"]').forEach(function(d){var e=d.getAttribute("data-script"),p=d.getAttribute("data-position")||"body_bottom";e&&injectScript(atob(e),p)})}
        function injectAllConsentScripts(){document.querySelectorAll('.snn-service-script[data-script]').forEach(function(d){if("yes"!==d.getAttribute("data-mandatory")){var e=d.getAttribute("data-script"),p=d.getAttribute("data-position")||"body_bottom";e&&injectScript(atob(e),p)}})}
        function injectCustomConsentScripts(){var p=getCookie("snn_cookie_services");if(p){var s=JSON.parse(p);document.querySelectorAll('.snn-service-script[data-script]').forEach(function(d){if("yes"!==d.getAttribute("data-mandatory")){var i=d.getAttribute("id").split("-").pop();if(s[i]){var e=d.getAttribute("data-script"),p=d.getAttribute("data-position")||"body_bottom";e&&injectScript(atob(e),p)}}})}}
        injectMandatoryScripts();
        var a=document.querySelector('.snn-accept'),y=document.querySelector('.snn-deny'),r=document.querySelector('.snn-preferences'),b=document.getElementById('snn-cookie-banner'),o=document.getElementById('snn-cookie-overlay');
        a&&a.addEventListener('click',function(){var t=document.querySelectorAll('.snn-service-toggle');if(t.length>0){var s={};t.forEach(function(g){s[g.getAttribute('data-service-index')]=g.checked}),setCookie('snn_cookie_services',JSON.stringify(s),365),setCookie('snn_cookie_accepted','custom',365),injectCustomConsentScripts()}else setCookie('snn_cookie_accepted','true',365),eraseCookie('snn_cookie_services'),injectAllConsentScripts();b&&(b.style.display='none'),o&&(o.style.display='none')});
        y&&y.addEventListener('click',function(){setCookie('snn_cookie_accepted','false',365),eraseCookie('snn_cookie_services'),b&&(b.style.display='none'),o&&(o.style.display='none')});
        r&&r.addEventListener('click',function(){var t=document.querySelector('.snn-preferences-content');t.style.display==='none'||t.style.display===''?t.style.display='block':t.style.display='none'});
        var s=getCookie('snn_cookie_accepted');
        if('true'===s)injectAllConsentScripts(),b&&(b.style.display='none'),o&&(o.style.display='none');else if('false'===s)b&&(b.style.display='none'),o&&(o.style.display='none');else if('custom'===s)injectCustomConsentScripts(),b&&(b.style.display='none'),o&&(o.style.display='none')
    })();
</script>
    <?php
}
add_action('wp_footer', 'snn_output_banner_js', 100);

function snn_output_custom_css() {
    if ( ! snn_is_cookie_banner_enabled() ) {
        return;
    }
    $options = get_option( SNN_OPTIONS );
    if ( !empty($options['snn_cookie_settings_custom_css']) ) {
        echo "<style id='snn-custom-css'>" . $options['snn_cookie_settings_custom_css'] . "</style>";
    }
}
add_action('wp_footer', 'snn_output_custom_css', 999);
?>
