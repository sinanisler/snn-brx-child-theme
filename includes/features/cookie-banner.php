<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

define('SNN_OPTIONS', 'snn_cookie_settings_options');

function snn_is_cookie_banner_enabled() {
    $options = get_option( SNN_OPTIONS );
    return ( !empty($options['snn_cookie_settings_enable_cookie_banner']) && $options['snn_cookie_settings_enable_cookie_banner'] === 'yes' );
}

// Banner layout templates. "sync" holds the settings applied in the admin when a layout is picked.
function snn_cookie_banner_layouts() {
    return array(
        'box'          => array( 'label' => __('Floating Box', 'snn'),     'sync' => array( 'banner_width' => '420',  'banner_position' => 'left',  'banner_vertical_position' => 'bottom', 'enable_overlay' => 'no' ) ),
        'bar'          => array( 'label' => __('Full Width Bar', 'snn'),   'sync' => array( 'banner_vertical_position' => 'bottom', 'enable_overlay' => 'no' ) ),
        'floating_bar' => array( 'label' => __('Floating Bar', 'snn'),     'sync' => array( 'banner_width' => '1000', 'banner_vertical_position' => 'bottom', 'enable_overlay' => 'no' ) ),
        'modal'        => array( 'label' => __('Centered Modal', 'snn'),   'sync' => array( 'banner_width' => '520',  'enable_overlay' => 'yes', 'overlay_color' => '#000000', 'overlay_opacity' => '0.5' ) ),
        'panel'        => array( 'label' => __('Side Panel', 'snn'),       'sync' => array( 'banner_width' => '400',  'banner_position' => 'right', 'enable_overlay' => 'no' ) ),
    );
}

// Visual style presets. Picking one fills the existing style fields, which stay editable.
function snn_cookie_banner_presets() {
    return array(
        'light' => array( 'label' => __('Light', 'snn'), 'values' => array( 'banner_bg_color' => '#ffffff', 'banner_text_color' => '#1f2937', 'button_bg_color' => '#111827', 'button_text_color' => '#ffffff', 'banner_border_radius' => '12', 'button_border_radius' => '8',   'banner_shadow_color' => '#000000', 'banner_shadow_spread' => '30', 'banner_bg_opacity' => '1',    'banner_blur' => '0' ) ),
        'dark'  => array( 'label' => __('Dark', 'snn'),  'values' => array( 'banner_bg_color' => '#111827', 'banner_text_color' => '#f3f4f6', 'button_bg_color' => '#ffffff', 'button_text_color' => '#111827', 'banner_border_radius' => '12', 'button_border_radius' => '8',   'banner_shadow_color' => '#000000', 'banner_shadow_spread' => '30', 'banner_bg_opacity' => '1',    'banner_blur' => '0' ) ),
        'glass' => array( 'label' => __('Glass', 'snn'), 'values' => array( 'banner_bg_color' => '#ffffff', 'banner_text_color' => '#111827', 'button_bg_color' => '#111827', 'button_text_color' => '#ffffff', 'banner_border_radius' => '16', 'button_border_radius' => '10',  'banner_shadow_color' => '#000000', 'banner_shadow_spread' => '40', 'banner_bg_opacity' => '0.65', 'banner_blur' => '16' ) ),
        'brand' => array( 'label' => __('Brand', 'snn'), 'values' => array( 'banner_bg_color' => '#2563eb', 'banner_text_color' => '#ffffff', 'button_bg_color' => '#ffffff', 'button_text_color' => '#1d4ed8', 'banner_border_radius' => '12', 'button_border_radius' => '8',   'banner_shadow_color' => '#1e3a8a', 'banner_shadow_spread' => '30', 'banner_bg_opacity' => '1',    'banner_blur' => '0' ) ),
        'soft'  => array( 'label' => __('Soft', 'snn'),  'values' => array( 'banner_bg_color' => '#fff7ed', 'banner_text_color' => '#431407', 'button_bg_color' => '#ea580c', 'button_text_color' => '#ffffff', 'banner_border_radius' => '24', 'button_border_radius' => '999', 'banner_shadow_color' => '#7c2d12', 'banner_shadow_spread' => '30', 'banner_bg_opacity' => '1',    'banner_blur' => '0' ) ),
    );
}

function snn_cookie_hex_to_rgba( $hex, $alpha ) {
    $hex = ltrim( (string) $hex, '#' );
    if ( strlen($hex) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if ( ! preg_match('/^[0-9a-fA-F]{6}$/', $hex) ) {
        return '#' . $hex;
    }
    return sprintf( 'rgba(%d,%d,%d,%s)', hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)), (float) $alpha );
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

// AJAX handler for page scanning
function snn_scan_page_scripts_ajax() {
    check_ajax_referer('snn_scan_page', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permission denied', 'snn'));
    }
    
    $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
    
    if (empty($page_url)) {
        wp_send_json_error(__('No page URL provided', 'snn'));
    }
    
    // Fetch the page content
    $response = wp_remote_get($page_url, array(
        'timeout' => 30,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(__('Failed to fetch page: ', 'snn') . $response->get_error_message());
    }
    
    $html = wp_remote_retrieve_body($response);
    
    if (empty($html)) {
        wp_send_json_error(__('Page content is empty', 'snn'));
    }
    
    // Parse HTML and extract scripts and iframes
    $scripts = array();
    $iframes = array();
    
    // Use DOMDocument to parse HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    // Extract external scripts
    $script_tags = $dom->getElementsByTagName('script');
    foreach ($script_tags as $script) {
        $src = $script->getAttribute('src');
        if (!empty($src)) {
            // Convert relative URLs to absolute
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            } elseif (strpos($src, '/') === 0) {
                $parsed_url = parse_url($page_url);
                $src = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $src;
            } elseif (strpos($src, 'http') !== 0) {
                continue; // Skip relative paths that we can't resolve
            }
            
            // Filter out internal scripts (optional - you can remove this if you want to block all scripts)
            $site_domain = parse_url(home_url(), PHP_URL_HOST);
            $script_domain = parse_url($src, PHP_URL_HOST);
            
            // Include all scripts (both internal and external)
            if (!in_array($src, $scripts)) {
                $scripts[] = $src;
            }
        }
    }
    
    // Extract iframes
    $iframe_tags = $dom->getElementsByTagName('iframe');
    foreach ($iframe_tags as $iframe) {
        $src = $iframe->getAttribute('src');
        if (!empty($src)) {
            // Convert relative URLs to absolute
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            } elseif (strpos($src, '/') === 0) {
                $parsed_url = parse_url($page_url);
                $src = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $src;
            }
            
            if (!in_array($src, $iframes)) {
                $iframes[] = $src;
            }
        }
    }
    
    // Get currently blocked scripts
    $options = get_option(SNN_OPTIONS);
    $blocked_scripts = isset($options['snn_cookie_settings_blocked_scripts']) ? $options['snn_cookie_settings_blocked_scripts'] : array();
    
    wp_send_json_success(array(
        'scripts' => $scripts,
        'iframes' => $iframes,
        'blocked_scripts' => $blocked_scripts
    ));
}
add_action('wp_ajax_snn_scan_page_scripts', 'snn_scan_page_scripts_ajax');


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
    // NEW: Google Analytics Consent Mode
        $options['snn_cookie_settings_enable_ga_consent'] = isset($_POST['snn_cookie_settings_enable_ga_consent']) ? 'yes' : 'no';
    // NEW: Microsoft Clarity Consent Mode
        $options['snn_cookie_settings_enable_clarity_consent'] = isset($_POST['snn_cookie_settings_enable_clarity_consent']) ? 'yes' : 'no';
    // NEW: Preferences Title
    $options['snn_cookie_settings_preferences_title'] = isset($_POST['snn_cookie_settings_preferences_title']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_preferences_title']) ) : '';

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
        $layout = isset($_POST['snn_cookie_settings_layout']) ? sanitize_key( wp_unslash($_POST['snn_cookie_settings_layout']) ) : 'box';
        $options['snn_cookie_settings_layout'] = array_key_exists($layout, snn_cookie_banner_layouts()) ? $layout : 'box';
        $preset = isset($_POST['snn_cookie_settings_preset']) ? sanitize_key( wp_unslash($_POST['snn_cookie_settings_preset']) ) : 'custom';
        $options['snn_cookie_settings_preset'] = ( $preset === 'custom' || array_key_exists($preset, snn_cookie_banner_presets()) ) ? $preset : 'custom';
        $options['snn_cookie_settings_banner_bg_opacity'] = isset($_POST['snn_cookie_settings_banner_bg_opacity']) ? (string) min(1, max(0, (float) $_POST['snn_cookie_settings_banner_bg_opacity'])) : '1';
        $options['snn_cookie_settings_banner_blur'] = isset($_POST['snn_cookie_settings_banner_blur']) ? (string) absint($_POST['snn_cookie_settings_banner_blur']) : '0';
        
        // NEW: Blocked Scripts (Page Scanner Feature)
        $blocked_scripts = array();
        if ( isset($_POST['snn_cookie_settings_blocked_scripts']) && is_array($_POST['snn_cookie_settings_blocked_scripts']) ) {
            foreach( $_POST['snn_cookie_settings_blocked_scripts'] as $blocked_script ) {
                if ( !empty($blocked_script['url']) ) {
                    $script_data = array();
                    $script_data['url'] = sanitize_text_field( wp_unslash($blocked_script['url']) );
                    $script_data['name'] = isset($blocked_script['name']) ? sanitize_text_field( wp_unslash($blocked_script['name']) ) : '';
                    $script_data['description'] = isset($blocked_script['description']) ? sanitize_text_field( wp_unslash($blocked_script['description']) ) : '';
                    $blocked_scripts[] = $script_data;
                }
            }
        }
        $options['snn_cookie_settings_blocked_scripts'] = $blocked_scripts;
        
        // NEW: Iframe Blocking Text
        $options['snn_cookie_settings_iframe_block_text'] = isset($_POST['snn_cookie_settings_iframe_block_text']) ? sanitize_text_field( wp_unslash($_POST['snn_cookie_settings_iframe_block_text']) ) : '';
        
        $services = array();
        if ( isset($_POST['snn_cookie_settings_services']) && is_array($_POST['snn_cookie_settings_services']) ) {
            // Use array_values to reindex and remove gaps - prevents index mismatch
            $posted_services = array_values($_POST['snn_cookie_settings_services']);
            
            foreach( $posted_services as $service ) {
                // Only skip if ALL fields are completely empty (trim whitespace)
                if ( empty( trim($service['name']) ) && 
                     empty( trim($service['description']) ) && 
                     empty( trim($service['script']) ) ) {
                    continue;
                }
                
                $service_data = array();
                $service_data['name'] = isset($service['name']) ? sanitize_text_field( wp_unslash($service['name']) ) : '';
                $service_data['description'] = isset($service['description']) ? wp_unslash($service['description']) : '';
                $service_data['script'] = isset($service['script']) ? wp_unslash($service['script']) : '';
                $service_data['position'] = isset($service['position']) ? sanitize_text_field( wp_unslash($service['position']) ) : 'body_bottom';
                $service_data['mandatory'] = isset($service['mandatory']) ? 'yes' : 'no';
                
                // Always add the service to preserve data
                $services[] = $service_data;
            }
        } else {
            // If no services are posted, preserve existing services to prevent data loss
            $existing_options = get_option( SNN_OPTIONS );
            if ( isset($existing_options['snn_cookie_settings_services']) && is_array($existing_options['snn_cookie_settings_services']) ) {
                $services = $existing_options['snn_cookie_settings_services'];
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
            'snn_cookie_settings_enable_ga_consent' => 'no',
            'snn_cookie_settings_enable_clarity_consent' => 'no',
            'snn_cookie_settings_preferences_title' => __('Cookie Preferences', 'snn'),
            'snn_cookie_settings_banner_description'   => __('This website uses cookies for analytics and functionality.', 'snn'),
            'snn_cookie_settings_additional_description' => '<p style="text-align: center;"><a href="#">' . __('Imprint', 'snn') . '</a> - <a href="#">' . __('Privacy Policy', 'snn') . '</a></p>',
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
            'snn_cookie_settings_banner_width'         => '500',
            'snn_cookie_settings_banner_border_radius' => '0',
            'snn_cookie_settings_button_border_radius' => '0',
            'snn_cookie_settings_layout'               => 'box',
            'snn_cookie_settings_preset'               => 'custom',
            'snn_cookie_settings_banner_bg_opacity'    => '1',
            'snn_cookie_settings_banner_blur'          => '0',
            'snn_cookie_settings_blocked_scripts'      => array(),
            'snn_cookie_settings_iframe_block_text'    => __('Please accept cookies to see the contents of this iframe.', 'snn')
        );
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Cookie Banner', 'snn'); ?></h1>
        <style>
            .snn-textarea { width: 500px; }
            .snn-input { width: 300px; }
            .snn-color-picker { }
            .snn-services-repeater .snn-service-item { margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; max-width:600px; position: relative; }
            .snn-service-actions { position: absolute; top: 8px; right: 8px; display: flex; gap: 6px; }
            .snn-move-btn { background: transparent; border: none; padding: 2px 4px; line-height: 1; cursor: pointer; color: #555; font-size: 14px; }
            .snn-move-btn:hover { color: #000; }
            .snn-custom-css-textarea { width: 500px; }
            .snn-tab { cursor:pointer; display: inline-block; margin-right: 10px; padding: 8px 12px; border: 1px solid #ccc; border-bottom: none; background: #f1f1f1; }
            .snn-tab.active { background: #fff; font-weight: bold; }
            .snn-tab-content { border: 1px solid #ccc; padding: 15px; display: none; }
            .snn-tab-content.active { display: block; }
            .snn-service-item label { display: block; margin-bottom: 5px; }
            .snn-service-item input[type="text"],
            .snn-service-item textarea { width: 100%; }
            .snn-service-item .snn-radio-group label { margin-right: 10px; }
            .snn-picker-heading { margin: 10px 0 4px; font-size: 15px; }
            .snn-picker { display: flex; flex-wrap: wrap; gap: 12px; margin: 12px 0 24px; }
            .snn-picker-card { position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 8px; border: 2px solid #dcdcde; border-radius: 8px; background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
            .snn-picker-card:hover { border-color: #8c8f94; }
            .snn-picker-card.is-selected { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
            .snn-picker-card input { position: absolute; opacity: 0; pointer-events: none; }
            .snn-picker-card input:focus-visible + .snn-thumb { outline: 2px solid #2271b1; outline-offset: 2px; }
            .snn-picker-label { font-size: 12px; font-weight: 600; color: #1d2327; }
            .snn-thumb { position: relative; display: block; width: 132px; height: 84px; border-radius: 4px; overflow: hidden; background: linear-gradient(#e2e4e7 0 10px, #f6f7f7 10px); }
            .snn-thumb::before { content: ""; position: absolute; left: 10px; right: 30px; top: 20px; height: 6px; border-radius: 3px; background: #dcdcde; box-shadow: 0 12px 0 #e6e7e8, 0 24px 0 #e6e7e8; }
            .snn-thumb-banner { position: absolute; display: flex; flex-direction: column; justify-content: center; gap: 3px; padding: 5px; box-sizing: border-box; background: var(--snn-t-bg, #fff); color: var(--snn-t-text, #1f2937); border-radius: 3px; box-shadow: 0 2px 8px rgba(0,0,0,.18); z-index: 1; }
            .snn-thumb-banner i { display: block; height: 3px; width: 100%; border-radius: 2px; background: currentColor; opacity: .55; }
            .snn-thumb-banner i + i { width: 70%; }
            .snn-thumb-banner b { display: block; height: 7px; width: 45%; margin-top: 2px; border-radius: 2px; background: var(--snn-t-btn, #111827); }
            .snn-thumb-box .snn-thumb-banner { left: 6px; bottom: 6px; width: 54px; height: 34px; }
            .snn-thumb-bar .snn-thumb-banner { left: 0; right: 0; bottom: 0; height: 18px; border-radius: 0; flex-direction: row; align-items: center; }
            .snn-thumb-bar .snn-thumb-banner i, .snn-thumb-floating_bar .snn-thumb-banner i { width: 40%; }
            .snn-thumb-bar .snn-thumb-banner i + i, .snn-thumb-floating_bar .snn-thumb-banner i + i { display: none; }
            .snn-thumb-bar .snn-thumb-banner b, .snn-thumb-floating_bar .snn-thumb-banner b { width: 22%; margin: 0 0 0 auto; }
            .snn-thumb-floating_bar .snn-thumb-banner { left: 10px; right: 10px; bottom: 6px; height: 16px; border-radius: 8px; flex-direction: row; align-items: center; }
            .snn-thumb-modal { background: linear-gradient(#9a9ca0 0 10px, #b4b6b9 10px); }
            .snn-thumb-modal::before { opacity: .35; }
            .snn-thumb-modal .snn-thumb-banner { left: 50%; top: 50%; transform: translate(-50%,-40%); width: 64px; height: 40px; align-items: center; }
            .snn-thumb-modal .snn-thumb-banner b { width: 60%; }
            .snn-thumb-panel .snn-thumb-banner { top: 10px; right: 0; bottom: 0; width: 44px; border-radius: 0; justify-content: flex-start; padding-top: 8px; }
            .snn-thumb-panel .snn-thumb-banner b { width: 80%; margin-top: auto; }
            .snn-thumb-preset .snn-thumb-banner { left: 50%; top: 50%; transform: translate(-50%,-40%); width: 88px; height: 44px; }
            .snn-thumb-preset-glass { background: linear-gradient(135deg, #a5b4fc, #f0abfc 60%, #fde68a); }
            .snn-thumb-preset-glass .snn-thumb-banner { backdrop-filter: blur(4px); }
            .snn-thumb-custom .snn-thumb-banner { border-radius: var(--snn-t-radius, 3px); }
            .snn-thumb-custom .snn-thumb-banner b { border-radius: var(--snn-t-btn-radius, 2px); }
        </style>
        <div class="snn-tabs">
            <span class="snn-tab active" data-tab="general"><?php _e('General Settings', 'snn'); ?></span>
            <span class="snn-tab" data-tab="scripts"><?php _e('Scripts & Services', 'snn'); ?></span>
            <span class="snn-tab" data-tab="scanner"><?php _e('Page Scanner', 'snn'); ?></span>
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
                        <th scope="row"><?php _e('Enable Google Analytics Consent Mode', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_ga_consent" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_ga_consent']) ? $options['snn_cookie_settings_enable_ga_consent'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to enable Google Analytics Consent Mode v2. This will automatically send consent status to Google Analytics when users accept/deny cookies.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Microsoft Clarity Consent Mode', 'snn'); ?></th>
                        <td>
                            <input type="checkbox" name="snn_cookie_settings_enable_clarity_consent" value="yes" <?php checked((isset($options['snn_cookie_settings_enable_clarity_consent']) ? $options['snn_cookie_settings_enable_clarity_consent'] : 'no'), 'yes'); ?>>
                            <span class="description"><?php _e('Check to enable Microsoft Clarity Consent Mode v2. This will automatically send consent status to Clarity when users accept/deny cookies.', 'snn'); ?></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Preferences Title', 'snn'); ?></th>
                        <td>
                                <input type="text" name="snn_cookie_settings_preferences_title" value="<?php echo isset($options['snn_cookie_settings_preferences_title']) && $options['snn_cookie_settings_preferences_title'] !== '' ? esc_attr($options['snn_cookie_settings_preferences_title']) : 'Cookie Preferences'; ?>" class="snn-input">
                                <p class="description"><?php _e('Preferences title text in the cookie banner.', 'snn'); ?></p>
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
                    <tr valign="top">
                        <th scope="row"><?php _e('Change Cookie Preferences', 'snn'); ?></th>
                        <td>
                            <div style="background: #f0f8ff; border-left: 4px solid #2271b1; padding: 12px 15px; margin-top: 10px;">
                                <p style="margin: 0 0 10px 0;">
                                    <strong><?php _e('Allow users to change their cookie preferences anytime (GDPR requirement)', 'snn'); ?></strong>
                                </p>
                                <p style="margin: 0 0 10px 0;">
                                    <?php _e('Add the CSS class', 'snn'); ?> <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">.snn-cookie-change</code> <?php _e('to any button or link on your website. When clicked, it will reopen the cookie banner allowing users to modify their preferences.', 'snn'); ?>
                                </p>
                                <p style="margin: 0 0 10px 0;"><strong><?php _e('Examples:', 'snn'); ?></strong></p>
                                <code style="display: block; background: #fff; padding: 8px; border-radius: 3px; margin-bottom: 8px;">&lt;button class="snn-cookie-change"&gt;<?php _e('Change Cookie Settings', 'snn'); ?>&lt;/button&gt;</code>
                                <code style="display: block; background: #fff; padding: 8px; border-radius: 3px; margin-bottom: 8px;">&lt;a href="#" class="snn-cookie-change"&gt;<?php _e('Cookie Preferences', 'snn'); ?>&lt;/a&gt;</code>
                            </div>
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
                                        <div class="snn-service-actions" aria-label="Reorder service">
                                            <button type="button" class="snn-move-btn snn-move-up" title="Move up">▲</button>
                                            <button type="button" class="snn-move-btn snn-move-down" title="Move down">▼</button>
                                        </div>
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
                                        <div class="snn-service-actions" aria-label="Reorder service">
                                            <button type="button" class="snn-move-btn snn-move-up" title="Move up">▲</button>
                                            <button type="button" class="snn-move-btn snn-move-down" title="Move down">▼</button>
                                        </div>
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
                                    
                                    // Function to reindex all services to prevent gaps
                                    function reindexServices() {
                                        $('#services-repeater .snn-service-item').each(function(index) {
                                            $(this).find('input, textarea, select').each(function() {
                                                var name = $(this).attr('name');
                                                if (name && name.includes('snn_cookie_settings_services[')) {
                                                    var newName = name.replace(/snn_cookie_settings_services\[\d+\]/, 'snn_cookie_settings_services[' + index + ']');
                                                    $(this).attr('name', newName);
                                                }
                                            });
                                        });
                                        serviceIndex = $('#services-repeater .snn-service-item').length;
                                        
                                        // Show save reminder after reindexing
                                        showSaveReminder();
                                    }
                                    
                                    // Show a subtle reminder to save after changes
                                    function showSaveReminder() {
                                        var $reminder = $('#save-reminder');
                                        if ($reminder.length === 0) {
                                            $reminder = $('<div id="save-reminder" style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;margin:10px 0;border-radius:4px;display:none;">' +
                                                '<strong><?php _e('Reminder:', 'snn'); ?></strong> ' +
                                                '<?php _e('Services have been reordered or removed. Please save your changes to prevent index mismatches.', 'snn'); ?>' +
                                                '</div>');
                                            $('#services-repeater').before($reminder);
                                        }
                                        $reminder.slideDown();
                                        
                                        // Auto-hide after 10 seconds
                                        setTimeout(function() {
                                            $reminder.slideUp();
                                        }, 10000);
                                    }
                                    
                                    // Hide reminder when save button is clicked
                                    $('input[type="submit"]').on('click', function() {
                                        $('#save-reminder').slideUp();
                                    });
                                    
                                    $('#add-service').click(function(e){
                                        e.preventDefault();
                                        // Reindex before adding new service to prevent gaps
                                        reindexServices();
                                        
                                        var newService = '<div class="snn-service-item">' +
                                            '<div class="snn-service-actions" aria-label="Reorder service">' +
                                                '<button type="button" class="snn-move-btn snn-move-up" title="Move up">▲</button>' +
                                                '<button type="button" class="snn-move-btn snn-move-down" title="Move down">▼</button>' +
                                            '</div>' +
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
                                        // Reindex after removal to prevent gaps
                                        reindexServices();
                                    });
                                    
                                    // Reordering handlers (delegate to container)
                                    $('#services-repeater').on('click', '.snn-move-up', function(e){
                                        e.preventDefault();
                                        var $item = $(this).closest('.snn-service-item');
                                        var $prev = $item.prev('.snn-service-item');
                                        if ($prev.length) {
                                            $item.insertBefore($prev);
                                            // Reindex after reordering
                                            reindexServices();
                                        }
                                    });
                                    
                                    $('#services-repeater').on('click', '.snn-move-down', function(e){
                                        e.preventDefault();
                                        var $item = $(this).closest('.snn-service-item');
                                        var $next = $item.next('.snn-service-item');
                                        if ($next.length) {
                                            $item.insertAfter($next);
                                            // Reindex after reordering
                                            reindexServices();
                                        }
                                    });
                                    
                                    // Initial reindex on page load to fix any existing gaps
                                    reindexServices();
                                });
                            })(jQuery);
                            </script>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="scanner" class="snn-tab-content">
                <h2><?php _e('Page Script Scanner', 'snn'); ?></h2>
                <p class="description">
                    <?php _e('This tool helps you scan any page on your website to detect scripts and iframes. You can then select which scripts to block until users accept cookies.', 'snn'); ?>
                    <br><br>
                    <strong><?php _e('How it works:', 'snn'); ?></strong><br>
                    1. <?php _e('Select a page from the list below (start typing to search)', 'snn'); ?><br>
                    2. <?php _e('Click "Scan Page" to analyze all scripts on that page', 'snn'); ?><br>
                    3. <?php _e('Review the detected scripts and select which ones to block', 'snn'); ?><br>
                    4. <?php _e('Blocked scripts will not load until users accept cookies', 'snn'); ?>
                </p>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Select Page to Scan', 'snn'); ?></th>
                        <td>
                            <input type="text" id="snn-page-url-input" list="snn-page-list" class="snn-input" placeholder="<?php _e('Start typing page title...', 'snn'); ?>" style="width: 400px;">
                            <datalist id="snn-page-list">
                                <?php
                                // Get all published pages and posts
                                $pages = get_posts(array(
                                    'post_type' => array('page', 'post'),
                                    'post_status' => 'publish',
                                    'numberposts' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                
                                // Add homepage
                                echo '<option value="' . esc_url(home_url('/')) . '">' . __('Homepage', 'snn') . '</option>';
                                
                                foreach ($pages as $page) {
                                    echo '<option value="' . esc_url(get_permalink($page->ID)) . '">' . esc_html($page->post_title) . '</option>';
                                }
                                ?>
                            </datalist>
                            <button type="button" id="snn-scan-page-btn" class="button button-primary"><?php _e('Scan Page', 'snn'); ?></button>
                            <div id="snn-scan-loading" style="display:none; margin-top: 10px;">
                                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                                <span><?php _e('Scanning page...', 'snn'); ?></span>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top" id="snn-scan-results-row" style="display: none;">
                        <th scope="row"><?php _e('Detected Scripts', 'snn'); ?></th>
                        <td>
                            <div id="snn-scan-results"></div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Currently Blocked Scripts', 'snn'); ?></th>
                        <td>
                            <div id="snn-blocked-scripts-list">
                                <?php 
                                $blocked_scripts = isset($options['snn_cookie_settings_blocked_scripts']) ? $options['snn_cookie_settings_blocked_scripts'] : array();
                                if (!empty($blocked_scripts)) {
                                    echo '<div style="max-width: 800px;">';
                                    foreach ($blocked_scripts as $index => $script) {
                                        // Support both old format (string) and new format (array with url, name, description)
                                        $script_url = is_array($script) ? $script['url'] : $script;
                                        $script_name = is_array($script) && !empty($script['name']) ? $script['name'] : '';
                                        $script_description = is_array($script) && !empty($script['description']) ? $script['description'] : '';
                                        
                                        echo '<div class="snn-blocked-script-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9; position: relative;">';
                                        echo '<button type="button" class="button button-small snn-remove-blocked-script" style="position: absolute; top: 10px; right: 10px;">' . __('Remove', 'snn') . '</button>';
                                        
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<label style="display: block; margin-bottom: 5px;"><strong>' . __('Service Name:', 'snn') . '</strong></label>';
                                        echo '<input type="text" name="snn_cookie_settings_blocked_scripts[' . $index . '][name]" value="' . esc_attr($script_name) . '" class="regular-text" placeholder="' . __('e.g., Google Analytics', 'snn') . '">';
                                        echo '</div>';
                                        
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<label style="display: block; margin-bottom: 5px;"><strong>' . __('Service Description:', 'snn') . '</strong></label>';
                                        echo '<input type="text" name="snn_cookie_settings_blocked_scripts[' . $index . '][description]" value="' . esc_attr($script_description) . '" class="regular-text" placeholder="' . __('e.g., Analytics for tracking site usage', 'snn') . '">';
                                        echo '</div>';
                                        
                                        echo '<div>';
                                        echo '<label style="display: block; margin-bottom: 5px;"><strong>' . __('Script URL:', 'snn') . '</strong></label>';
                                        echo '<code style="display: block; background: #fff; padding: 8px; word-break: break-all; font-size: 11px; border: 1px solid #ddd;">' . esc_html($script_url) . '</code>';
                                        echo '<input type="hidden" name="snn_cookie_settings_blocked_scripts[' . $index . '][url]" value="' . esc_attr($script_url) . '">';
                                        echo '</div>';
                                        
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<p class="description">' . __('No scripts are currently blocked.', 'snn') . '</p>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Iframe Blocking Text', 'snn'); ?></th>
                        <td>
                            <input type="text" name="snn_cookie_settings_iframe_block_text" value="<?php echo isset($options['snn_cookie_settings_iframe_block_text']) ? esc_attr($options['snn_cookie_settings_iframe_block_text']) : __('Please accept cookies to see the contents of this iframe.', 'snn'); ?>" class="regular-text">
                            <p class="description"><?php _e('This text will be displayed inside blocked iframes until the user accepts cookies.', 'snn'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <script>
                (function($){
                    $(document).ready(function(){
                        // Scan page button handler
                        $('#snn-scan-page-btn').on('click', function(){
                            var pageUrl = $('#snn-page-url-input').val();
                            if (!pageUrl) {
                                alert('<?php echo esc_js(__('Please select or enter a page URL', 'snn')); ?>');
                                return;
                            }
                            
                            $('#snn-scan-loading').show();
                            $('#snn-scan-results-row').hide();
                            
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'snn_scan_page_scripts',
                                    page_url: pageUrl,
                                    nonce: '<?php echo wp_create_nonce('snn_scan_page'); ?>'
                                },
                                success: function(response){
                                    $('#snn-scan-loading').hide();
                                    if (response.success) {
                                        displayScanResults(response.data);
                                        $('#snn-scan-results-row').show();
                                    } else {
                                        alert('<?php echo esc_js(__('Error scanning page:', 'snn')); ?> ' + response.data);
                                    }
                                },
                                error: function(){
                                    $('#snn-scan-loading').hide();
                                    alert('<?php echo esc_js(__('Failed to scan page. Please try again.', 'snn')); ?>');
                                }
                            });
                        });
                        
                        function displayScanResults(data) {
                            var html = '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">';
                            
                            if (data.scripts.length === 0 && data.iframes.length === 0) {
                                html += '<p><?php echo esc_js(__('No external scripts or iframes detected on this page.', 'snn')); ?></p>';
                            } else {
                                // Display scripts
                                if (data.scripts.length > 0) {
                                    html += '<h3><?php echo esc_js(__('Scripts Found:', 'snn')); ?> (' + data.scripts.length + ')</h3>';
                                    html += '<ul style="list-style: none; padding: 0;">';
                                    data.scripts.forEach(function(script){
                                        // Check if script is already blocked (compare URL)
                                        var isBlocked = false;
                                        if (data.blocked_scripts && Array.isArray(data.blocked_scripts)) {
                                            isBlocked = data.blocked_scripts.some(function(blocked) {
                                                var blockedUrl = typeof blocked === 'string' ? blocked : blocked.url;
                                                return blockedUrl === script;
                                            });
                                        }
                                        html += '<li style="margin-bottom: 10px; padding: 10px; background: white; border: 1px solid #ddd;">';
                                        html += '<label style="display: flex; align-items: center; gap: 10px;">';
                                        html += '<input type="checkbox" class="snn-script-to-block" value="' + script + '" ' + (isBlocked ? 'checked disabled' : '') + '>';
                                        html += '<code style="flex: 1; word-break: break-all; font-size: 11px;">' + script + '</code>';
                                        if (isBlocked) {
                                            html += '<span style="color: #d63638; font-weight: bold;">(<?php echo esc_js(__('Already Blocked', 'snn')); ?>)</span>';
                                        }
                                        html += '</label>';
                                        html += '</li>';
                                    });
                                    html += '</ul>';
                                }
                                
                                // Display iframes
                                if (data.iframes.length > 0) {
                                    html += '<h3 style="margin-top: 20px;"><?php echo esc_js(__('Iframes Found:', 'snn')); ?> (' + data.iframes.length + ')</h3>';
                                    html += '<ul style="list-style: none; padding: 0;">';
                                    data.iframes.forEach(function(iframe){
                                        // Check if iframe is already blocked (compare URL)
                                        var isBlocked = false;
                                        if (data.blocked_scripts && Array.isArray(data.blocked_scripts)) {
                                            isBlocked = data.blocked_scripts.some(function(blocked) {
                                                var blockedUrl = typeof blocked === 'string' ? blocked : blocked.url;
                                                return blockedUrl === iframe;
                                            });
                                        }
                                        html += '<li style="margin-bottom: 10px; padding: 10px; background: white; border: 1px solid #ddd;">';
                                        html += '<label style="display: flex; align-items: center; gap: 10px;">';
                                        html += '<input type="checkbox" class="snn-script-to-block" value="' + iframe + '" ' + (isBlocked ? 'checked disabled' : '') + '>';
                                        html += '<code style="flex: 1; word-break: break-all; font-size: 11px;">' + iframe + '</code>';
                                        if (isBlocked) {
                                            html += '<span style="color: #d63638; font-weight: bold;">(<?php echo esc_js(__('Already Blocked', 'snn')); ?>)</span>';
                                        }
                                        html += '</label>';
                                        html += '</li>';
                                    });
                                    html += '</ul>';
                                }
                                
                                html += '<button type="button" id="snn-add-selected-scripts" class="button button-primary" style="margin-top: 15px;"><?php echo esc_js(__('Block Selected Resources', 'snn')); ?></button>';
                            }
                            
                            html += '</div>';
                            $('#snn-scan-results').html(html);
                        }
                        
                        // Helper function to escape HTML for display
                        function escapeHtml(text) {
                            var div = document.createElement('div');
                            div.textContent = text;
                            return div.innerHTML;
                        }
                        
                        // Add selected scripts to blocked list using DOM manipulation
                        $(document).on('click', '#snn-add-selected-scripts', function(){
                            var selectedScripts = [];
                            $('.snn-script-to-block:checked:not(:disabled)').each(function(){
                                selectedScripts.push($(this).val());
                            });
                            
                            if (selectedScripts.length === 0) {
                                alert('<?php echo esc_js(__('Please select at least one script to block', 'snn')); ?>');
                                return;
                            }
                            
                            var $list = $('#snn-blocked-scripts-list');
                            var currentHtml = $list.html();
                            
                            // Remove "no scripts" message if exists
                            if (currentHtml.indexOf('No scripts are currently blocked') !== -1) {
                                $list.html('<div style="max-width: 800px;"></div>');
                            }
                            
                            var $container = $list.find('div').first();
                            if ($container.length === 0) {
                                $list.html('<div style="max-width: 800px;"></div>');
                                $container = $list.find('div').first();
                            }
                            
                            // Get current highest index
                            var currentIndex = $container.find('.snn-blocked-script-item').length;
                            
                            selectedScripts.forEach(function(script){
                                // Create elements using DOM methods to avoid HTML parsing issues
                                var $item = $('<div>', {
                                    'class': 'snn-blocked-script-item',
                                    'style': 'border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9; position: relative;'
                                });
                                
                                // Remove button
                                var $removeBtn = $('<button>', {
                                    'type': 'button',
                                    'class': 'button button-small snn-remove-blocked-script',
                                    'style': 'position: absolute; top: 10px; right: 10px;',
                                    'text': '<?php echo esc_js(__('Remove', 'snn')); ?>'
                                });
                                $item.append($removeBtn);
                                
                                // Service Name section
                                var $nameDiv = $('<div>', {'style': 'margin-bottom: 10px;'});
                                $nameDiv.append($('<label>', {
                                    'style': 'display: block; margin-bottom: 5px;',
                                    'html': '<strong><?php echo esc_js(__('Service Name:', 'snn')); ?></strong>'
                                }));
                                $nameDiv.append($('<input>', {
                                    'type': 'text',
                                    'name': 'snn_cookie_settings_blocked_scripts[' + currentIndex + '][name]',
                                    'value': '',
                                    'class': 'regular-text',
                                    'placeholder': '<?php echo esc_js(__('e.g., Google Analytics', 'snn')); ?>'
                                }));
                                $item.append($nameDiv);
                                
                                // Service Description section
                                var $descDiv = $('<div>', {'style': 'margin-bottom: 10px;'});
                                $descDiv.append($('<label>', {
                                    'style': 'display: block; margin-bottom: 5px;',
                                    'html': '<strong><?php echo esc_js(__('Service Description:', 'snn')); ?></strong>'
                                }));
                                $descDiv.append($('<input>', {
                                    'type': 'text',
                                    'name': 'snn_cookie_settings_blocked_scripts[' + currentIndex + '][description]',
                                    'value': '',
                                    'class': 'regular-text',
                                    'placeholder': '<?php echo esc_js(__('e.g., Analytics for tracking site usage', 'snn')); ?>'
                                }));
                                $item.append($descDiv);
                                
                                // Script URL section
                                var $urlDiv = $('<div>');
                                $urlDiv.append($('<label>', {
                                    'style': 'display: block; margin-bottom: 5px;',
                                    'html': '<strong><?php echo esc_js(__('Script URL:', 'snn')); ?></strong>'
                                }));
                                $urlDiv.append($('<code>', {
                                    'style': 'display: block; background: #fff; padding: 8px; word-break: break-all; font-size: 11px; border: 1px solid #ddd;',
                                    'text': script  // Using .text() automatically escapes HTML
                                }));
                                $urlDiv.append($('<input>', {
                                    'type': 'hidden',
                                    'name': 'snn_cookie_settings_blocked_scripts[' + currentIndex + '][url]',
                                    'value': script  // jQuery automatically escapes attribute values
                                }));
                                $item.append($urlDiv);
                                
                                // Append the complete item to container
                                $container.append($item);
                                currentIndex++;
                            });
                            
                            alert('<?php echo esc_js(__('Scripts added to blocked list. Please add Service Name and Description, then save settings!', 'snn')); ?>');
                            
                            // Disable added checkboxes
                            selectedScripts.forEach(function(script){
                                $('.snn-script-to-block').filter(function() {
                                    return $(this).val() === script;
                                }).prop('disabled', true).prop('checked', true);
                            });
                        });
                        
                        // Remove blocked script
                        $(document).on('click', '.snn-remove-blocked-script', function(){
                            $(this).closest('.snn-blocked-script-item').remove();
                            
                            // Check if list is empty
                            var $container = $('#snn-blocked-scripts-list div');
                            if ($container.find('.snn-blocked-script-item').length === 0) {
                                $('#snn-blocked-scripts-list').html('<p class="description">' + '<?php _e('No scripts are currently blocked.', 'snn'); ?>' + '</p>');
                            }
                        });
                    });
                })(jQuery);
                </script>
            </div>
            <div id="styles" class="snn-tab-content">
                <?php
                $current_layout = isset($options['snn_cookie_settings_layout']) ? $options['snn_cookie_settings_layout'] : 'box';
                $current_preset = isset($options['snn_cookie_settings_preset']) ? $options['snn_cookie_settings_preset'] : 'custom';
                ?>
                <h2 class="snn-picker-heading"><?php _e('Layout', 'snn'); ?></h2>
                <p class="description"><?php _e('Where and how the banner appears. Picking a layout also sets matching position, width and overlay settings below.', 'snn'); ?></p>
                <div class="snn-picker snn-layout-picker">
                    <?php foreach ( snn_cookie_banner_layouts() as $key => $layout ) : ?>
                        <label class="snn-picker-card<?php echo $current_layout === $key ? ' is-selected' : ''; ?>">
                            <input type="radio" name="snn_cookie_settings_layout" value="<?php echo esc_attr($key); ?>" data-sync="<?php echo esc_attr( wp_json_encode($layout['sync']) ); ?>" <?php checked($current_layout, $key); ?>>
                            <span class="snn-thumb snn-thumb-layout snn-thumb-<?php echo esc_attr($key); ?>">
                                <span class="snn-thumb-banner"><i></i><i></i><b></b></span>
                            </span>
                            <span class="snn-picker-label"><?php echo esc_html($layout['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <h2 class="snn-picker-heading"><?php _e('Style Preset', 'snn'); ?></h2>
                <p class="description"><?php _e('Fills the colors, corners and shadow below. You can still fine-tune every value; editing one switches the preset to Custom.', 'snn'); ?></p>
                <div class="snn-picker snn-preset-picker">
                    <?php foreach ( snn_cookie_banner_presets() as $key => $preset ) : $v = $preset['values']; ?>
                        <label class="snn-picker-card<?php echo $current_preset === $key ? ' is-selected' : ''; ?>">
                            <input type="radio" name="snn_cookie_settings_preset" value="<?php echo esc_attr($key); ?>" data-values="<?php echo esc_attr( wp_json_encode($v) ); ?>" <?php checked($current_preset, $key); ?>>
                            <span class="snn-thumb snn-thumb-preset snn-thumb-preset-<?php echo esc_attr($key); ?>">
                                <span class="snn-thumb-banner" style="background:<?php echo esc_attr( snn_cookie_hex_to_rgba($v['banner_bg_color'], $v['banner_bg_opacity']) ); ?>;color:<?php echo esc_attr($v['banner_text_color']); ?>;border-radius:<?php echo esc_attr( min(10, (int) $v['banner_border_radius'] / 2) ); ?>px;">
                                    <i></i><i></i>
                                    <b style="background:<?php echo esc_attr($v['button_bg_color']); ?>;border-radius:<?php echo esc_attr( min(6, (int) $v['button_border_radius'] / 2) ); ?>px;"></b>
                                </span>
                            </span>
                            <span class="snn-picker-label"><?php echo esc_html($preset['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <label class="snn-picker-card<?php echo $current_preset === 'custom' ? ' is-selected' : ''; ?>">
                        <input type="radio" name="snn_cookie_settings_preset" value="custom" <?php checked($current_preset, 'custom'); ?>>
                        <span class="snn-thumb snn-thumb-preset snn-thumb-custom">
                            <span class="snn-thumb-banner"><i></i><i></i><b></b></span>
                        </span>
                        <span class="snn-picker-label"><?php _e('Custom', 'snn'); ?></span>
                    </label>
                </div>

                <h2 class="snn-picker-heading"><?php _e('Fine-tune', 'snn'); ?></h2>
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
                    <tr valign="top" class="snn-row-width">
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
                        <th scope="row"><?php _e('Banner Background Opacity', 'snn'); ?></th>
                        <td>
                            <input type="number" step="0.05" min="0" max="1" name="snn_cookie_settings_banner_bg_opacity" value="<?php echo esc_attr( isset($options['snn_cookie_settings_banner_bg_opacity']) ? $options['snn_cookie_settings_banner_bg_opacity'] : '1' ); ?>" class="snn-input">
                            <p class="description"><?php _e('1 = solid. Lower values make the banner see-through (combine with blur for a glass look).', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Banner Background Blur', 'snn'); ?></th>
                        <td>
                            <input type="number" min="0" max="60" name="snn_cookie_settings_banner_blur" value="<?php echo esc_attr( isset($options['snn_cookie_settings_banner_blur']) ? $options['snn_cookie_settings_banner_blur'] : '0' ); ?>" class="snn-input">
                            <p class="description"><?php _e('Blurs the page behind the banner, in pixels. 0 = off.', 'snn'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" class="snn-row-hpos">
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
                    <tr valign="top" class="snn-row-vpos">
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
                                <code>.snn-legal-text</code> - <?php _e('Bottom Rich Text', 'snn'); ?><br>
                                <br>
                                <strong><?php _e('GDPR Cookie Preference Change:', 'snn'); ?></strong><br>
                                <code>.snn-cookie-change</code> - <?php _e('Add this class to any button or link to allow users to reopen the cookie banner and change their preferences.', 'snn'); ?>
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

                // Visual layout / preset pickers
                var $styles = $('#styles');
                var syncing = false;
                function field(key) { return $styles.find('[name="snn_cookie_settings_' + key + '"]'); }
                function setField(key, value) {
                    var $f = field(key);
                    if ($f.is(':checkbox')) { $f.prop('checked', value === 'yes'); } else { $f.val(value); }
                }
                function markSelected($input) {
                    $input.closest('.snn-picker').find('.snn-picker-card').removeClass('is-selected');
                    $input.closest('.snn-picker-card').addClass('is-selected');
                }
                function refreshThumbs() {
                    var vars = {
                        '--snn-t-bg': field('banner_bg_color').val(),
                        '--snn-t-text': field('banner_text_color').val(),
                        '--snn-t-btn': field('button_bg_color').val(),
                        '--snn-t-radius': Math.min(10, (parseInt(field('banner_border_radius').val(), 10) || 0) / 2) + 'px',
                        '--snn-t-btn-radius': Math.min(6, (parseInt(field('button_border_radius').val(), 10) || 0) / 2) + 'px'
                    };
                    $styles.find('.snn-thumb-layout, .snn-thumb-custom').each(function(){
                        for (var k in vars) { this.style.setProperty(k, vars[k]); }
                    });
                }
                function refreshRows() {
                    var layout = $styles.find('[name="snn_cookie_settings_layout"]:checked').val() || 'box';
                    $styles.find('.snn-row-hpos').toggle(layout === 'box' || layout === 'panel');
                    $styles.find('.snn-row-vpos').toggle(layout === 'box' || layout === 'bar' || layout === 'floating_bar');
                    $styles.find('.snn-row-width').toggle(layout !== 'bar');
                }
                $styles.on('change', '[name="snn_cookie_settings_layout"]', function(){
                    var sync = $(this).data('sync') || {};
                    syncing = true;
                    for (var k in sync) { setField(k, sync[k]); }
                    syncing = false;
                    markSelected($(this));
                    refreshRows();
                });
                $styles.on('change', '[name="snn_cookie_settings_preset"]', function(){
                    var values = $(this).data('values');
                    if (values) {
                        syncing = true;
                        for (var k in values) { setField(k, values[k]); }
                        syncing = false;
                    }
                    markSelected($(this));
                    refreshThumbs();
                });
                // Manually editing a preset-controlled field switches the preset to Custom.
                var presetKeys = ['banner_bg_color','banner_text_color','button_bg_color','button_text_color','banner_border_radius','button_border_radius','banner_shadow_color','banner_shadow_spread','banner_bg_opacity','banner_blur'];
                $styles.on('input change', presetKeys.map(function(k){ return '[name="snn_cookie_settings_' + k + '"]'; }).join(','), function(){
                    if (syncing) { return; }
                    var $custom = $styles.find('[name="snn_cookie_settings_preset"][value="custom"]');
                    if (!$custom.prop('checked')) { $custom.prop('checked', true); markSelected($custom); }
                    refreshThumbs();
                });
                refreshThumbs();
                refreshRows();
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
    $layout = isset($options['snn_cookie_settings_layout']) && array_key_exists($options['snn_cookie_settings_layout'], snn_cookie_banner_layouts()) ? $options['snn_cookie_settings_layout'] : 'box';
    $banner_bg_color = isset($options['snn_cookie_settings_banner_bg_color']) ? $options['snn_cookie_settings_banner_bg_color'] : '#333333';
    $banner_bg_opacity = isset($options['snn_cookie_settings_banner_bg_opacity']) ? (float) $options['snn_cookie_settings_banner_bg_opacity'] : 1;
    $banner_bg = $banner_bg_opacity < 1 ? snn_cookie_hex_to_rgba($banner_bg_color, $banner_bg_opacity) : $banner_bg_color;
    $banner_blur = isset($options['snn_cookie_settings_banner_blur']) ? absint($options['snn_cookie_settings_banner_blur']) : 0;

    $accepted = isset($_COOKIE['snn_cookie_accepted']) ? $_COOKIE['snn_cookie_accepted'] : '';
    $banner_style = ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' style="display: none;"' : '';
    $overlay_style = ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' style="display: none;"' : '';
    ?>
    <?php if ($enable_overlay === 'yes') : ?>
    <div id="snn-cookie-overlay" class="snn-cookie-overlay"<?php echo $overlay_style; ?> style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: <?php echo esc_attr($overlay_color); ?>; opacity: <?php echo esc_attr($overlay_opacity); ?>; z-index: 9998;<?php echo ( in_array($accepted, array('true', 'false', 'custom')) ) ? ' display: none;' : ''; ?>"></div>
    <?php endif; ?>
    
    <style id="snn-dynamic-styles">
        .snn-cookie-banner {position:fixed;box-sizing:border-box;width:<?php echo esc_attr($banner_width); ?>px;max-width:calc(100% - 20px);z-index:9999;padding:20px;background:<?php echo esc_attr($banner_bg); ?>;<?php if ($banner_blur > 0) : ?>-webkit-backdrop-filter:blur(<?php echo $banner_blur; ?>px);backdrop-filter:blur(<?php echo $banner_blur; ?>px);<?php endif; ?>color:<?php echo isset($options['snn_cookie_settings_banner_text_color']) ? esc_attr($options['snn_cookie_settings_banner_text_color']) : '#ffffff'; ?>;box-shadow:0px 0px <?php echo esc_attr($options['snn_cookie_settings_banner_shadow_spread']); ?>px <?php echo esc_attr($options['snn_cookie_settings_banner_shadow_color']); ?>44;border-radius:<?php echo esc_attr($banner_border_radius); ?>px;margin:10px;}
        <?php if ($layout === 'box') : ?>
        .snn-cookie-banner{<?php if ($vertical_position === 'top') : ?>top:10px;<?php elseif ($vertical_position === 'middle') : ?>top:50%;transform:translateY(-50%);<?php else : ?>bottom:10px;<?php endif; ?>}
        .snn-cookie-banner.left{left:0;}
        .snn-cookie-banner.middle{left:50%;<?php if ($vertical_position === 'middle') : ?>transform:translate(-50%,-50%);<?php else : ?>transform:translateX(-50%);<?php endif; ?>}
        .snn-cookie-banner.right{right:0;}
        <?php elseif ($layout === 'bar' || $layout === 'floating_bar') : ?>
        .snn-cookie-banner{display:grid;grid-template-columns:1fr auto;column-gap:24px;align-items:center;<?php echo $vertical_position === 'top' ? 'top' : 'bottom'; ?>:<?php echo $layout === 'bar' ? '0' : '16px'; ?>;}
        .snn-cookie-banner>.snn-preferences-title,.snn-cookie-banner>.snn-preferences-content,.snn-cookie-banner>.snn-legal-text{grid-column:1/-1;}
        .snn-cookie-banner .snn-preferences-title{text-align:left;}
        .snn-cookie-banner .snn-banner-text{margin-bottom:0;}
        .snn-cookie-banner .snn-banner-text p{margin:0;}
        .snn-cookie-banner .snn-banner-buttons .snn-button{width:auto;white-space:nowrap;padding:10px 18px;}
        <?php if ($layout === 'bar') : ?>
        .snn-cookie-banner{left:0;right:0;width:100%;max-width:none;margin:0;border-radius:0;padding:16px max(20px,calc((100% - 1200px)/2));}
        <?php else : ?>
        .snn-cookie-banner{left:50%;transform:translateX(-50%);margin:0;width:calc(100% - 32px);max-width:<?php echo esc_attr($banner_width); ?>px;padding:14px 20px;}
        <?php endif; ?>
        <?php elseif ($layout === 'modal') : ?>
        .snn-cookie-banner{top:50%;left:50%;transform:translate(-50%,-50%);margin:0;max-height:calc(100vh - 40px);overflow:auto;padding:28px;}
        .snn-cookie-banner .snn-preferences-title{font-size:1.25em;}
        .snn-cookie-banner .snn-banner-text{text-align:center;margin-bottom:18px;}
        <?php elseif ($layout === 'panel') : ?>
        .snn-cookie-banner{top:0;bottom:0;<?php echo $position === 'left' ? 'left' : 'right'; ?>:0;margin:0;max-width:100%;overflow:auto;display:flex;flex-direction:column;border-radius:0;padding:28px 24px;}
        .snn-cookie-banner .snn-preferences-title{text-align:left;font-size:1.25em;margin-bottom:12px;}
        .snn-cookie-banner .snn-preferences-content{display:block;order:2;margin:12px 0;}
        .snn-cookie-banner .snn-banner-text{order:1;}
        .snn-cookie-banner .snn-banner-buttons{order:3;margin-top:auto;flex-direction:column;}
        .snn-cookie-banner .snn-legal-text{order:4;}
        .snn-cookie-banner .snn-preferences{display:none;}
        <?php endif; ?>
        .snn-preferences-content{display:none;}
        .snn-banner-buttons{display:flex;flex-direction:row;gap:10px}
        .snn-banner-text{margin-bottom:10px;}
        .snn-service-name span{font-weight:900;}
        .snn-legal-text{margin-top:10px;}
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
            <?php if ($layout === 'modal') : ?>
            .snn-cookie-banner{width:calc(100% - 20px);padding:20px;}
            <?php elseif ($layout === 'panel') : ?>
            .snn-cookie-banner{width:100%;}
            <?php elseif ($layout === 'bar' || $layout === 'floating_bar') : ?>
            .snn-cookie-banner{grid-template-columns:1fr;row-gap:12px;}
            .snn-cookie-banner .snn-banner-buttons .snn-button{white-space:normal;width:100%;}
            <?php else : ?>
            .snn-cookie-banner{width:calc(100% - 20px);left:0!important;right:0!important;transform:none!important;padding:10px;}
            <?php endif; ?>
            .snn-banner-buttons{display:flex;flex-direction:column;}
            .snn-banner-buttons .snn-button{width:100%;text-align:center;}
            .snn-banner-buttons .snn-button:last-child{margin-bottom:0;}
        }
    </style>
    <div id="snn-cookie-banner" class="snn-cookie-banner snn-layout-<?php echo esc_attr($layout); ?> <?php echo esc_attr($position); ?>"<?php echo $banner_style; ?>>
    <div class="snn-preferences-title"><?php echo esc_html( isset($options['snn_cookie_settings_preferences_title']) ? $options['snn_cookie_settings_preferences_title'] : __('Cookie Preferences', 'snn') ); ?></div>
    <div class="snn-preferences-content">
            <?php 
            // Combine services and blocked scripts for display
            $all_services = array();
            
            // Add regular services
            if ( ! empty($options['snn_cookie_settings_services']) && is_array($options['snn_cookie_settings_services']) ) {
                foreach ( $options['snn_cookie_settings_services'] as $index => $service ) {
                    $all_services[] = array(
                        'type' => 'service',
                        'index' => $index,
                        'data' => $service
                    );
                }
            }
            
            // Add blocked scripts as services
            $blocked_scripts = isset($options['snn_cookie_settings_blocked_scripts']) ? $options['snn_cookie_settings_blocked_scripts'] : array();
            if ( ! empty($blocked_scripts) && is_array($blocked_scripts) ) {
                foreach ( $blocked_scripts as $index => $script ) {
                    // Support both old format (string) and new format (array)
                    if ( is_array($script) && !empty($script['url']) ) {
                        $script_name = !empty($script['name']) ? $script['name'] : __('Blocked Script', 'snn') . ' ' . ($index + 1);
                        $script_description = !empty($script['description']) ? $script['description'] : '';
                        
                        $all_services[] = array(
                            'type' => 'blocked_script',
                            'index' => 'blocked_' . $index,
                            'data' => array(
                                'name' => $script_name,
                                'description' => $script_description,
                                'url' => $script['url'],
                                'mandatory' => 'no'
                            )
                        );
                    }
                }
            }
            
            if ( ! empty($all_services) ) { ?>
                <ul class="snn-services-list" style="list-style: none; padding: 0;">
                <?php foreach ( $all_services as $service_item ) { 
                    $service = $service_item['data'];
                    $service_index = $service_item['index'];
                    ?>
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
                                <input type="checkbox" data-service-index="<?php echo esc_attr($service_index); ?>" class="snn-service-toggle" <?php echo (isset($service['mandatory']) && $service['mandatory'] === 'yes') ? 'checked disabled' : 'checked'; ?>>
                                <span class="snn-slider"></span>
                            </label>
                        </div>
                        <?php if ( !empty($service['description']) ) { ?>
                            <p class="snn-service-description-text" style="margin-top: 5px; margin-bottom: 0;  "><?php echo esc_html( $service['description'] ); ?></p>
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

// Output script blocker in head (VERY EARLY)
function snn_output_script_blocker() {
    if ( ! snn_is_cookie_banner_enabled() ) {
        return;
    }
    
    $options = get_option( SNN_OPTIONS );
    if ( is_user_logged_in() && !empty($options['snn_cookie_settings_disable_scripts_for_logged_in']) && $options['snn_cookie_settings_disable_scripts_for_logged_in'] === 'yes' ) {
        return;
    }
    
    $blocked_scripts = isset($options['snn_cookie_settings_blocked_scripts']) ? $options['snn_cookie_settings_blocked_scripts'] : array();
    
    if (empty($blocked_scripts)) {
        return;
    }
    
    // Check if user has already accepted cookies
    $accepted = isset($_COOKIE['snn_cookie_accepted']) ? $_COOKIE['snn_cookie_accepted'] : '';
    
    // If accepted ALL cookies, don't block anything
    if ($accepted === 'true') {
        return;
    }
    
    // If custom consent, we NEED to block scripts and check consent for each one
    // If denied or no consent, we NEED to block everything
    // So we continue and output the blocker script
    
    // Extract URLs from blocked scripts (support both old and new format)
    $blocked_urls = array();
    foreach ($blocked_scripts as $script) {
        if (is_array($script) && isset($script['url'])) {
            $blocked_urls[] = $script['url'];
        } elseif (is_string($script)) {
            $blocked_urls[] = $script;
        }
    }
    
    ?>
    <script id="snn-script-blocker">
    (function(){
        // Blocked scripts list (URLs only)
        var blockedScripts = <?php echo json_encode($blocked_urls); ?>;
        
        // Iframe blocking text
        var iframeBlockText = <?php echo json_encode(isset($options['snn_cookie_settings_iframe_block_text']) ? $options['snn_cookie_settings_iframe_block_text'] : __('Please accept cookies to see the contents of this iframe.', 'snn')); ?>;
        
        // Get cookie helper
        function getCookie(n){for(var e=n+"=",t=document.cookie.split(";"),i=0;i<t.length;i++){for(var o=t[i];" "==o.charAt(0);)o=o.substring(1,o.length);if(0==o.indexOf(e))return o.substring(e.length,o.length)}return null}
        
        // Function to add blocking text to iframe
        function addIframeBlockingText(iframe) {
            // Create a container div to hold the message
            var container = document.createElement('div');
            container.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #f5f5f5; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; font-size: 14px; color: #666; z-index: 10; box-sizing: border-box; padding: 20px; text-align: center;';
            container.innerHTML = iframeBlockText;
            container.setAttribute('data-snn-iframe-blocker', 'true');
            
            // Make iframe container relative if it's not already positioned
            var iframeParent = iframe.parentNode;
            var computedStyle = window.getComputedStyle(iframeParent);
            if (computedStyle.position === 'static') {
                iframeParent.style.position = 'relative';
                iframe.setAttribute('data-snn-parent-position-changed', 'true');
            }
            
            // Insert the container after the iframe
            iframe.parentNode.insertBefore(container, iframe.nextSibling);
        }
        
        // Function to remove blocking text from iframe
        function removeIframeBlockingText(iframe) {
            var container = iframe.parentNode.querySelector('[data-snn-iframe-blocker="true"]');
            if (container) {
                container.remove();
            }
            
            // Restore parent position if we changed it
            if (iframe.getAttribute('data-snn-parent-position-changed')) {
                iframe.parentNode.style.position = '';
                iframe.removeAttribute('data-snn-parent-position-changed');
            }
        }
        
        // Check if we should block scripts based on consent
        function shouldBlockByConsent() {
            var accepted = getCookie('snn_cookie_accepted');
            var consentServices = getCookie('snn_cookie_services');
            
            // If no consent yet, block everything
            if (!accepted) return true;
            
            // If denied, block everything
            if (accepted === 'false') return true;
            
            // If accepted all, don't block anything
            if (accepted === 'true') return false;
            
            // If custom consent exists, check individual services
            if (accepted === 'custom') {
                if (consentServices) {
                    return 'custom'; // Special flag to check individual scripts
                } else {
                    // Custom accepted but no consent data - invalid state, block all
                    return true;
                }
            }
            
            return true; // Default to blocking
        }
        
        // Check if a specific blocked script is allowed by consent
        function isBlockedScriptAllowed(url) {
            var consentServices = getCookie('snn_cookie_services');
            if (!consentServices) return false;
            
            try {
                var consent = JSON.parse(consentServices);
                
                // Find which blocked script index this URL belongs to
                for (var i = 0; i < blockedScripts.length; i++) {
                    if (url.indexOf(blockedScripts[i]) !== -1 || blockedScripts[i].indexOf(url) !== -1) {
                        var blockedKey = 'blocked_' + i;
                        var isAllowed = consent[blockedKey] === true;
                        // Log for debugging
                        if (typeof console !== 'undefined' && console.log) {
                            // console.log('SNN: Checking blocked script:', url, '-> Key:', blockedKey, '-> Allowed:', isAllowed, '-> Consent value:', consent[blockedKey]);
                        }
                        // Return true only if explicitly set to true
                        // If undefined or false, it should be blocked
                        return isAllowed;
                    }
                }
            } catch(e) {
                //console.error('Error parsing consent:', e);
            }
            
            return false;
        }
        
        // Function to check if a URL is blocked
        function isBlocked(url) {
            if (!url) return false;
            
            // Normalize URL
            var normalizedUrl = url;
            if (url.indexOf('//') === 0) {
                normalizedUrl = 'https:' + url;
            }
            
            // Decode HTML entities for comparison
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = normalizedUrl;
            var decodedUrl = tempDiv.textContent || tempDiv.innerText || normalizedUrl;
            
            // Extract domain and path for fallback matching
            function extractDomainPath(urlStr) {
                try {
                    var tempUrl = new URL(urlStr);
                    return tempUrl.hostname + tempUrl.pathname;
                } catch (e) {
                    // Fallback for older browsers or malformed URLs
                    var match = urlStr.match(/^https?:\/\/([^\/\?]+)([^\?]*)/);
                    return match ? match[1] + match[2] : urlStr;
                }
            }
            
            var normalizedDomainPath = extractDomainPath(normalizedUrl);
            var decodedDomainPath = extractDomainPath(decodedUrl);
            
            // Check if this URL is in blocked scripts list
            var isInBlockedList = false;
            for (var i = 0; i < blockedScripts.length; i++) {
                var blockedScript = blockedScripts[i];
                var blockedDomainPath = extractDomainPath(blockedScript);
                
                // First try exact URL matching (with both encoded/decoded versions)
                if (normalizedUrl.indexOf(blockedScript) !== -1 || 
                    blockedScript.indexOf(normalizedUrl) !== -1 ||
                    decodedUrl.indexOf(blockedScript) !== -1 || 
                    blockedScript.indexOf(decodedUrl) !== -1) {
                    isInBlockedList = true;
                    break;
                }
                
                // Fallback: Domain + path matching (without query parameters)
                if (normalizedDomainPath === blockedDomainPath || 
                    decodedDomainPath === blockedDomainPath ||
                    normalizedDomainPath.indexOf(blockedDomainPath) !== -1 ||
                    decodedDomainPath.indexOf(blockedDomainPath) !== -1) {
                    isInBlockedList = true;
                    break;
                }
            }
            
            if (!isInBlockedList) return false; // Not in blocked list, don't block
            
            // Check consent
            var blockStatus = shouldBlockByConsent();
            
            if (blockStatus === true) {
                return true; // Block everything
            } else if (blockStatus === false) {
                return false; // Allow everything
            } else if (blockStatus === 'custom') {
                // Check if this specific script is allowed
                return !isBlockedScriptAllowed(normalizedUrl);
            }
            
            return false;
        }
        
        // Block scripts on initial page load
        // This runs early to catch scripts before they execute
        (function() {
            // Check consent status immediately
            var accepted = getCookie('snn_cookie_accepted');
            
            // Only proceed with blocking if we need to
            if (!accepted || accepted === 'false' || accepted === 'custom') {
                document.addEventListener('DOMContentLoaded', function() {
                    // Block existing scripts that should be blocked
                    var scripts = document.querySelectorAll('script[src]');
                    scripts.forEach(function(script) {
                        if (isBlocked(script.src)) {
                            script.type = 'text/plain';
                            script.setAttribute('data-snn-blocked', 'true');
                        }
                    });
                    
                    // Block existing iframes that should be blocked
                    var iframes = document.querySelectorAll('iframe[src]');
                    iframes.forEach(function(iframe) {
                        if (isBlocked(iframe.src)) {
                            iframe.setAttribute('data-snn-blocked-src', iframe.src);
                            iframe.removeAttribute('src');
                            iframe.setAttribute('data-snn-blocked', 'true');
                            // Add blocking text
                            addIframeBlockingText(iframe);
                        }
                    });
                });
            }
        })();
        
        // Use MutationObserver to block dynamically added scripts and iframes
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.tagName === 'SCRIPT' && node.src && isBlocked(node.src)) {
                        node.type = 'text/plain';
                        node.setAttribute('data-snn-blocked', 'true');
                    }
                    if (node.tagName === 'IFRAME' && node.src && isBlocked(node.src)) {
                        node.setAttribute('data-snn-blocked-src', node.src);
                        node.removeAttribute('src');
                        node.setAttribute('data-snn-blocked', 'true');
                        // Add blocking text
                        addIframeBlockingText(node);
                    }
                });
            });
        });
        
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
        
        // Store observer globally so it can be accessed later
        window.snnScriptObserver = observer;
        window.snnBlockedScripts = blockedScripts;
        window.snnIsBlocked = isBlocked;
        window.snnAddIframeBlockingText = addIframeBlockingText;
        window.snnRemoveIframeBlockingText = removeIframeBlockingText;
    })();
    </script>
    <?php
}
add_action('wp_head', 'snn_output_script_blocker', 2);


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
                    data-service-index="<?php echo esc_attr($index); ?>"
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
    $ga_consent_enabled = ( isset($options['snn_cookie_settings_enable_ga_consent']) && $options['snn_cookie_settings_enable_ga_consent'] === 'yes' ) ? 'true' : 'false';
    $clarity_consent_enabled = ( isset($options['snn_cookie_settings_enable_clarity_consent']) && $options['snn_cookie_settings_enable_clarity_consent'] === 'yes' ) ? 'true' : 'false';
    ?>

<script>
    (function(){
        function setCookie(n,v,d){var e="";if(d){var t=new Date;t.setTime(t.getTime()+864e5*d),e="; expires="+t.toUTCString()}document.cookie=n+"="+(v||"")+e+"; path=/"}
        function getCookie(n){for(var e=n+"=",t=document.cookie.split(";"),i=0;i<t.length;i++){for(var o=t[i];" "==o.charAt(0);)o=o.substring(1,o.length);if(0==o.indexOf(e))return o.substring(e.length,o.length)}return null}
        function eraseCookie(n){document.cookie=n+"=; Max-Age=-99999999; path=/"}
        var cookieBannerEnabled=<?php echo $cookie_banner_enabled; ?>;
        var gaConsentEnabled=<?php echo $ga_consent_enabled; ?>;
        var clarityConsentEnabled=<?php echo $clarity_consent_enabled; ?>;
        
        // Validate consent cookie against current services to prevent index mismatch
        function validateConsentCookie() {
            var consentCookie = getCookie('snn_cookie_services');
            if (!consentCookie) return null;
            
            try {
                var consent = JSON.parse(consentCookie);
                var validIndices = {};
                var hasInvalidIndices = false;
                
                // Get all current service indices from the DOM
                var currentServiceIndices = [];
                document.querySelectorAll('.snn-service-script[data-script]').forEach(function(d) {
                    if (d.getAttribute('data-mandatory') !== 'yes') {
                        var serviceId = d.getAttribute('id');
                        if (serviceId) {
                            var index = serviceId.split('-').pop();
                            currentServiceIndices.push(index);
                        }
                    }
                });
                
                // Check each consent entry against current services
                for (var key in consent) {
                    if (consent.hasOwnProperty(key)) {
                        // Check if this index still exists in current services
                        if (currentServiceIndices.indexOf(key) !== -1) {
                            validIndices[key] = consent[key];
                        } else {
                            hasInvalidIndices = true;
                        }
                    }
                }
                
                // If we found invalid indices or missing services, clear consent
                if (hasInvalidIndices || Object.keys(validIndices).length !== currentServiceIndices.length) {
                    // console.log('SNN Cookie Banner: Service configuration changed. Clearing old consent.');
                    eraseCookie('snn_cookie_services');
                    eraseCookie('snn_cookie_accepted');
                    return null;
                }
                
                return validIndices;
            } catch (e) {
                // Invalid JSON, clear cookie
                eraseCookie('snn_cookie_services');
                eraseCookie('snn_cookie_accepted');
                return null;
            }
        }
        
        function updateGoogleAnalyticsConsent(accepted){
            if(gaConsentEnabled && typeof gtag !== 'undefined'){
                gtag('consent', 'update', {
                    'analytics_storage': accepted ? 'granted' : 'denied',
                    'ad_storage': accepted ? 'granted' : 'denied',
                    'ad_user_data': accepted ? 'granted' : 'denied',
                    'ad_personalization': accepted ? 'granted' : 'denied'
                });
            }
        }
        
        function updateClarityConsent(accepted){
            if(clarityConsentEnabled && typeof window.clarity !== 'undefined'){
                window.clarity('consentv2', {
                    ad_Storage: accepted ? "granted" : "denied",
                    analytics_Storage: accepted ? "granted" : "denied"
                });
            }
        }
        
        function blockAllScripts(){
            // Block scripts
            document.querySelectorAll('script[src]').forEach(function(script){
                if(window.snnIsBlocked && window.snnIsBlocked(script.src)){
                    if(script.type !== 'text/plain'){
                        script.type = 'text/plain';
                        script.setAttribute('data-snn-blocked', 'true');
                    }
                }
            });
            
            // Block iframes
            document.querySelectorAll('iframe[src]').forEach(function(iframe){
                if(window.snnIsBlocked && window.snnIsBlocked(iframe.src)){
                    if(!iframe.hasAttribute('data-snn-blocked')){
                        iframe.setAttribute('data-snn-blocked-src', iframe.src);
                        iframe.removeAttribute('src');
                        iframe.setAttribute('data-snn-blocked', 'true');
                        // Add blocking text using the global function
                        if(window.snnAddIframeBlockingText){
                            window.snnAddIframeBlockingText(iframe);
                        }
                    }
                }
            });
            
            // Restart mutation observer to catch new scripts and iframes
            if(window.snnScriptObserver){
                window.snnScriptObserver.disconnect();
            }
            
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.tagName === 'SCRIPT' && node.src && window.snnIsBlocked && window.snnIsBlocked(node.src)) {
                            node.type = 'text/plain';
                            node.setAttribute('data-snn-blocked', 'true');
                        }
                        if (node.tagName === 'IFRAME' && node.src && window.snnIsBlocked && window.snnIsBlocked(node.src)) {
                            node.setAttribute('data-snn-blocked-src', node.src);
                            node.removeAttribute('src');
                            node.setAttribute('data-snn-blocked', 'true');
                            // Add blocking text using the global function
                            if(window.snnAddIframeBlockingText){
                                window.snnAddIframeBlockingText(node);
                            }
                        }
                    });
                });
            });
            
            observer.observe(document.documentElement, {
                childList: true,
                subtree: true
            });
            
            window.snnScriptObserver = observer;
        }
        
        function unblockScripts(customConsent){
            // If custom consent is provided, only unblock accepted scripts
            if(customConsent){
                document.querySelectorAll('script[data-snn-blocked="true"]').forEach(function(script){
                    if(script.type === 'text/plain'){
                        if(shouldUnblockScript(script.src, customConsent)){
                            // Script is allowed, unblock it
                            var newScript = document.createElement('script');
                            for(var i = 0; i < script.attributes.length; i++){
                                var attr = script.attributes[i];
                                if(attr.name !== 'type' && attr.name !== 'data-snn-blocked'){
                                    newScript.setAttribute(attr.name, attr.value);
                                }
                            }
                            newScript.type = 'text/javascript';
                            script.parentNode.replaceChild(newScript, script);
                        }
                        // If not allowed, keep it blocked (do nothing)
                    }
                });
                
                // Unblock iframes based on consent
                document.querySelectorAll('iframe[data-snn-blocked="true"]').forEach(function(iframe){
                    var src = iframe.getAttribute('data-snn-blocked-src');
                    if(src){
                        if(shouldUnblockScript(src, customConsent)){
                            // Iframe is allowed, unblock it
                            iframe.src = src;
                            iframe.removeAttribute('data-snn-blocked');
                            iframe.removeAttribute('data-snn-blocked-src');
                            // Remove blocking text
                            if(window.snnRemoveIframeBlockingText){
                                window.snnRemoveIframeBlockingText(iframe);
                            }
                        }
                        // If not allowed, keep it blocked (do nothing)
                    }
                });
                
                // IMPORTANT: Keep the mutation observer active to continue blocking scripts
                // that are not allowed by custom consent
                // The observer will check each dynamically added script against consent
            } else {
                // Unblock all scripts
                document.querySelectorAll('script[data-snn-blocked="true"]').forEach(function(script){
                    if(script.type === 'text/plain'){
                        var newScript = document.createElement('script');
                        for(var i = 0; i < script.attributes.length; i++){
                            var attr = script.attributes[i];
                            if(attr.name !== 'type' && attr.name !== 'data-snn-blocked'){
                                newScript.setAttribute(attr.name, attr.value);
                            }
                        }
                        newScript.type = 'text/javascript';
                        script.parentNode.replaceChild(newScript, script);
                    }
                });
                
                // Unblock all iframes
                document.querySelectorAll('iframe[data-snn-blocked="true"]').forEach(function(iframe){
                    var src = iframe.getAttribute('data-snn-blocked-src');
                    if(src){
                        iframe.src = src;
                        iframe.removeAttribute('data-snn-blocked');
                        iframe.removeAttribute('data-snn-blocked-src');
                        // Remove blocking text
                        if(window.snnRemoveIframeBlockingText){
                            window.snnRemoveIframeBlockingText(iframe);
                        }
                    }
                });
                
                // Stop the mutation observer to allow all scripts to load
                if(window.snnScriptObserver){
                    window.snnScriptObserver.disconnect();
                }
            }
        }
        
        function shouldUnblockScript(url, customConsent){
            if(!url || !window.snnBlockedScripts) return true;
            
            // Decode HTML entities for comparison
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = url;
            var decodedUrl = tempDiv.textContent || tempDiv.innerText || url;
            
            // Extract domain and path for fallback matching
            function extractDomainPath(urlStr) {
                try {
                    var tempUrl = new URL(urlStr);
                    return tempUrl.hostname + tempUrl.pathname;
                } catch (e) {
                    // Fallback for older browsers or malformed URLs
                    var match = urlStr.match(/^https?:\/\/([^\/\?]+)([^\?]*)/);
                    return match ? match[1] + match[2] : urlStr;
                }
            }
            
            var urlDomainPath = extractDomainPath(url);
            var decodedDomainPath = extractDomainPath(decodedUrl);
            
            // Find the blocked script index for this URL
            var blockedIndex = -1;
            for(var i = 0; i < window.snnBlockedScripts.length; i++){
                var blockedScript = window.snnBlockedScripts[i];
                var blockedDomainPath = extractDomainPath(blockedScript);
                
                // First try exact URL matching (with both encoded/decoded versions)
                if(url.indexOf(blockedScript) !== -1 || 
                   blockedScript.indexOf(url) !== -1 ||
                   decodedUrl.indexOf(blockedScript) !== -1 || 
                   blockedScript.indexOf(decodedUrl) !== -1){
                    blockedIndex = i;
                    break;
                }
                
                // Fallback: Domain + path matching (without query parameters)
                if (urlDomainPath === blockedDomainPath || 
                    decodedDomainPath === blockedDomainPath ||
                    urlDomainPath.indexOf(blockedDomainPath) !== -1 ||
                    decodedDomainPath.indexOf(blockedDomainPath) !== -1) {
                    blockedIndex = i;
                    break;
                }
            }
            
            if(blockedIndex === -1) return true; // Not in blocked list, allow
            
            // Check if this blocked script was accepted in custom consent
            var blockedKey = 'blocked_' + blockedIndex;
            return customConsent[blockedKey] === true;
        }
        
        // Load saved toggle states from cookie
        function loadToggleStates(){
            var acceptedStatus = getCookie('snn_cookie_accepted');
            var consentCookie = getCookie('snn_cookie_services');
            
            // If denied, set all toggles to OFF (except mandatory)
            if(acceptedStatus === 'false'){
                document.querySelectorAll('.snn-service-toggle').forEach(function(toggle){
                    if(!toggle.disabled){ // Don't change mandatory ones
                        toggle.checked = false;
                    }
                });
                return;
            }
            
            // If accepted all, set all toggles to ON
            if(acceptedStatus === 'true'){
                document.querySelectorAll('.snn-service-toggle').forEach(function(toggle){
                    toggle.checked = true;
                });
                return;
            }
            
            // If custom consent exists, apply it
            if(consentCookie){
                try{
                    var consent = JSON.parse(consentCookie);
                    document.querySelectorAll('.snn-service-toggle').forEach(function(toggle){
                        var index = toggle.getAttribute('data-service-index');
                        if(consent.hasOwnProperty(index)){
                            toggle.checked = consent[index];
                        }else{
                            // If not in consent cookie, default to false
                            if(!toggle.disabled){ // Don't change mandatory ones
                                toggle.checked = false;
                            }
                        }
                    });
                }catch(e){
                   // console.error('Error parsing consent cookie:', e);
                }
            }else{
                // No consent cookie, default all to checked (initial state)
                document.querySelectorAll('.snn-service-toggle').forEach(function(toggle){
                    if(!toggle.disabled){ // Don't change mandatory ones
                        toggle.checked = true;
                    }
                });
            }
        }
        
        function injectScript(c,p){var d=document.createElement("div");d.innerHTML=c;d.querySelectorAll("script").forEach(function(s){var n=document.createElement("script");for(var i=0;i<s.attributes.length;i++){var a=s.attributes[i];n.setAttribute(a.name,a.value)}n.text=s.text||"";"head"===p?document.head.appendChild(n):"body_top"===p?document.body.firstChild?document.body.insertBefore(n,document.body.firstChild):document.body.appendChild(n):document.body.appendChild(n)})}
        function injectMandatoryScripts(){document.querySelectorAll('.snn-service-script[data-mandatory="yes"]').forEach(function(d){var e=d.getAttribute("data-script"),p=d.getAttribute("data-position")||"body_bottom";e&&injectScript(atob(e),p)})}
        function injectAllConsentScripts(){document.querySelectorAll('.snn-service-script[data-script]').forEach(function(d){if("yes"!==d.getAttribute("data-mandatory")){var e=d.getAttribute("data-script"),p=d.getAttribute("data-position")||"body_bottom";e&&injectScript(atob(e),p)}})}
        function injectCustomConsentScripts(){
            var p=getCookie("snn_cookie_services");
            if(p){
                try{
                    var s=JSON.parse(p);
                    document.querySelectorAll('.snn-service-script[data-script]').forEach(function(d){
                        if("yes"!==d.getAttribute("data-mandatory")){
                            var i=d.getAttribute("data-service-index");
                            if(!i){
                                // Fallback to old method if data-service-index not available
                                i=d.getAttribute("id").split("-").pop();
                            }
                            // Only inject if explicitly enabled (true)
                            if(s[i] === true){
                                var e=d.getAttribute("data-script"),pos=d.getAttribute("data-position")||"body_bottom";
                                e&&injectScript(atob(e),pos);
                            }
                        }
                    });
                }catch(e){
                  //  console.error('Error parsing consent:', e);
                }
            }
        }
        injectMandatoryScripts();
        var a=document.querySelector('.snn-accept'),y=document.querySelector('.snn-deny'),r=document.querySelector('.snn-preferences'),b=document.getElementById('snn-cookie-banner'),o=document.getElementById('snn-cookie-overlay');
        
        // Load saved toggle states when preferences are opened
        r&&r.addEventListener('click',function(){
            var t=document.querySelector('.snn-preferences-content');
            if(t.style.display==='none'||t.style.display===''){
                t.style.display='block';
                loadToggleStates(); // Load saved states when opening preferences
            }else{
                t.style.display='none';
            }
        });
        
        a&&a.addEventListener('click',function(){
            var t=document.querySelectorAll('.snn-service-toggle');
            if(t.length>0){
                var s={};
                var hasAnyEnabled = false;
                t.forEach(function(g){
                    var serviceIndex = g.getAttribute('data-service-index');
                    var isChecked = g.checked;
                    s[serviceIndex] = isChecked;
                    if(isChecked) hasAnyEnabled = true;
                });
                setCookie('snn_cookie_services',JSON.stringify(s),365);
                setCookie('snn_cookie_accepted','custom',365);
                
                // Important: Reload page to apply new preferences
                // This ensures all scripts are properly blocked/unblocked
                window.location.reload();
            }else{
                setCookie('snn_cookie_accepted','true',365);
                eraseCookie('snn_cookie_services');
                injectAllConsentScripts();
                updateGoogleAnalyticsConsent(true);
                updateClarityConsent(true);
                unblockScripts();
                b&&(b.style.display='none');
                o&&(o.style.display='none');
            }
        });
        y&&y.addEventListener('click',function(){
            setCookie('snn_cookie_accepted','false',365);
            eraseCookie('snn_cookie_services');
            updateGoogleAnalyticsConsent(false);
            updateClarityConsent(false);
            // Reload page to ensure all scripts are blocked
            window.location.reload();
        });
        
        // Handle cookie preference change buttons (GDPR requirement)
        function setupCookieChangeButtons() {
            var changeButtons = document.querySelectorAll('.snn-cookie-change');
            changeButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show the cookie banner again (do NOT clear cookies here)
                    if (b) {
                        b.style.display = '';
                        // Open preferences panel and load current states
                        var prefsContent = document.querySelector('.snn-preferences-content');
                        if (prefsContent) {
                            prefsContent.style.display = 'block';
                        }
                        // Load current toggle states from cookie
                        loadToggleStates();
                    }
                    if (o) {
                        o.style.display = 'block';
                    }
                });
            });
        }
        
        // Set up change buttons on page load
        setupCookieChangeButtons();
        
        // Also set up change buttons when DOM changes (for dynamically added buttons)
        var changeButtonObserver = new MutationObserver(function() {
            setupCookieChangeButtons();
        });
        changeButtonObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Initialize toggle states on page load
        loadToggleStates();
        
        var s=getCookie('snn_cookie_accepted');
        if('true'===s){
            // Accept all - inject all scripts and unblock all
            injectAllConsentScripts();
            updateGoogleAnalyticsConsent(true);
            updateClarityConsent(true);
            unblockScripts();
            b&&(b.style.display='none');
            o&&(o.style.display='none');
        }else if('false'===s){
            // Deny all - keep everything blocked
            updateGoogleAnalyticsConsent(false);
            updateClarityConsent(false);
            blockAllScripts();
            b&&(b.style.display='none');
            o&&(o.style.display='none');
        }else if('custom'===s){
            // Custom preferences - validate and apply
            var consentCookie = getCookie('snn_cookie_services');
            if(consentCookie){
                try{
                    var consent = JSON.parse(consentCookie);
                    
                   // console.log('SNN Cookie Banner: Applying custom consent preferences', consent);
                    
                    // Step 1: Ensure ALL blocked scripts are blocked first
                    blockAllScripts();
                    
                    // Step 2: Inject only accepted service scripts (from Scripts & Services tab)
                    injectCustomConsentScripts();
                    
                    // Step 3: Unblock only accepted blocked scripts (from Page Scanner tab)
                    // This will check each blocked script against consent
                    unblockScripts(consent);
                    
                    //console.log('SNN Cookie Banner: Custom consent applied successfully');
                    
                    updateGoogleAnalyticsConsent(true);
                    updateClarityConsent(true);
                    b&&(b.style.display='none');
                    o&&(o.style.display='none');
                }catch(e){
                   // console.error('Invalid consent cookie, clearing:', e);
                    eraseCookie('snn_cookie_accepted');
                    eraseCookie('snn_cookie_services');
                    // Banner will show since no valid consent
                }
            }else{
                // No consent cookie but accepted is 'custom' - invalid state, clear it
                eraseCookie('snn_cookie_accepted');
                // Banner will show
            }
        }
        // If no cookie (s is null/undefined), banner shows by default
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
