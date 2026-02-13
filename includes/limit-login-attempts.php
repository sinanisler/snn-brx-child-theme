<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Limit Login Attempts Feature
 * Tracks and blocks login attempts based on IP address
 */

// Get user's IP address
function snn_get_user_ip() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return sanitize_text_field($ip);
}

// Check if login attempts feature is enabled
function snn_is_limit_login_enabled() {
    $options = get_option('snn_security_options');
    return isset($options['enable_limit_login']) && $options['enable_limit_login'] == 1;
}

// Get max login attempts allowed
function snn_get_max_login_attempts() {
    $options = get_option('snn_security_options');
    return isset($options['max_login_attempts']) && $options['max_login_attempts'] > 0 
        ? intval($options['max_login_attempts']) 
        : 5;
}

// Get reset time in hours
function snn_get_reset_time() {
    $options = get_option('snn_security_options');
    return isset($options['login_reset_time']) && $options['login_reset_time'] > 0 
        ? intval($options['login_reset_time']) 
        : 24;
}

// Get blocked IPs
function snn_get_blocked_ips() {
    $blocked_ips = get_option('snn_blocked_ips', array());
    return is_array($blocked_ips) ? $blocked_ips : array();
}

// Check if IP is currently blocked
function snn_is_ip_blocked($ip) {
    if (!snn_is_limit_login_enabled()) {
        return false;
    }

    $blocked_ips = snn_get_blocked_ips();
    
    if (!isset($blocked_ips[$ip])) {
        return false;
    }

    $block_data = $blocked_ips[$ip];
    $reset_hours = snn_get_reset_time();
    $block_time = $block_data['blocked_at'];
    $current_time = current_time('timestamp');
    
    // Check if block has expired
    if (($current_time - $block_time) > ($reset_hours * HOUR_IN_SECONDS)) {
        // Block expired, remove it
        unset($blocked_ips[$ip]);
        update_option('snn_blocked_ips', $blocked_ips);
        
        // Also clear failed attempts for this IP
        $failed_attempts = get_option('snn_failed_login_attempts', array());
        if (isset($failed_attempts[$ip])) {
            unset($failed_attempts[$ip]);
            update_option('snn_failed_login_attempts', $failed_attempts);
        }
        
        return false;
    }
    
    return true;
}

// Block login page if IP is blocked
function snn_check_blocked_ip_before_login() {
    if (!snn_is_limit_login_enabled()) {
        return;
    }

    $ip = snn_get_user_ip();
    
    if (snn_is_ip_blocked($ip)) {
        $blocked_ips = snn_get_blocked_ips();
        $block_data = $blocked_ips[$ip];
        $reset_hours = snn_get_reset_time();
        
        $time_remaining = $reset_hours * HOUR_IN_SECONDS - (current_time('timestamp') - $block_data['blocked_at']);
        $hours_remaining = ceil($time_remaining / HOUR_IN_SECONDS);
        
        wp_die(
            sprintf(
                __('<strong>ERROR</strong>: Your IP address has been temporarily blocked due to too many failed login attempts. Please try again in %d hours.', 'snn'),
                $hours_remaining
            ),
            __('Login Blocked', 'snn'),
            array('response' => 403)
        );
    }
}
add_action('login_init', 'snn_check_blocked_ip_before_login');

// Track failed login attempts
function snn_track_failed_login($username) {
    if (!snn_is_limit_login_enabled()) {
        return;
    }

    $ip = snn_get_user_ip();
    $failed_attempts = get_option('snn_failed_login_attempts', array());
    
    if (!isset($failed_attempts[$ip])) {
        $failed_attempts[$ip] = array(
            'count' => 0,
            'last_attempt' => current_time('timestamp')
        );
    }
    
    // Check if we should reset the counter based on reset time
    $reset_hours = snn_get_reset_time();
    $last_attempt_time = $failed_attempts[$ip]['last_attempt'];
    $current_time = current_time('timestamp');
    
    // Reset counter if enough time has passed and IP is not blocked
    if (($current_time - $last_attempt_time) > ($reset_hours * HOUR_IN_SECONDS) && !snn_is_ip_blocked($ip)) {
        $failed_attempts[$ip]['count'] = 0;
    }
    
    // Increment failed attempt count
    $failed_attempts[$ip]['count']++;
    $failed_attempts[$ip]['last_attempt'] = $current_time;
    
    update_option('snn_failed_login_attempts', $failed_attempts);
    
    // Check if we need to block this IP
    $max_attempts = snn_get_max_login_attempts();
    
    if ($failed_attempts[$ip]['count'] >= $max_attempts) {
        snn_block_ip($ip, $username);
    }
}
add_action('wp_login_failed', 'snn_track_failed_login');

// Block an IP address
function snn_block_ip($ip, $username = '') {
    $blocked_ips = snn_get_blocked_ips();
    
    $blocked_ips[$ip] = array(
        'blocked_at' => current_time('timestamp'),
        'username' => sanitize_text_field($username),
        'attempts' => 0
    );
    
    // Get the failed attempt count before blocking
    $failed_attempts = get_option('snn_failed_login_attempts', array());
    if (isset($failed_attempts[$ip])) {
        $blocked_ips[$ip]['attempts'] = $failed_attempts[$ip]['count'];
    }
    
    update_option('snn_blocked_ips', $blocked_ips);
    
    // Log the block event
    do_action('snn_ip_blocked', $ip, $username);
}

// Reset failed attempts on successful login
function snn_reset_failed_attempts($username, $user) {
    if (!snn_is_limit_login_enabled()) {
        return;
    }

    $ip = snn_get_user_ip();
    $failed_attempts = get_option('snn_failed_login_attempts', array());
    
    if (isset($failed_attempts[$ip])) {
        unset($failed_attempts[$ip]);
        update_option('snn_failed_login_attempts', $failed_attempts);
    }
}
add_action('wp_login', 'snn_reset_failed_attempts', 10, 2);

// Clear all blocked IPs (admin function)
function snn_clear_all_blocked_ips() {
    delete_option('snn_blocked_ips');
    delete_option('snn_failed_login_attempts');
    return true;
}

// Handle clear blocks button
function snn_handle_clear_blocks() {
    if (isset($_POST['snn_clear_blocked_ips']) && check_admin_referer('snn_clear_blocked_ips_action', 'snn_clear_blocked_ips_nonce')) {
        if (current_user_can('manage_options')) {
            snn_clear_all_blocked_ips();
            add_settings_error(
                'snn_security_options',
                'blocks_cleared',
                __('All blocked IPs have been cleared successfully.', 'snn'),
                'success'
            );
        }
    }
}
add_action('admin_init', 'snn_handle_clear_blocks');

// Display current blocked IPs count in admin
function snn_get_blocked_ips_count() {
    $blocked_ips = snn_get_blocked_ips();
    $active_blocks = 0;
    
    foreach ($blocked_ips as $ip => $data) {
        if (snn_is_ip_blocked($ip)) {
            $active_blocks++;
        }
    }
    
    return $active_blocks;
}
