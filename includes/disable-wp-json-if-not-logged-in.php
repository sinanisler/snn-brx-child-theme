<?php
/**
 * Disable REST API Endpoints for Non-Logged-In Users
 * Allows selective blocking of specific endpoints
 */

/**
 * Get all registered REST API endpoints
 */
function snn_get_rest_endpoints() {
    $server = rest_get_server();
    $endpoints = $server->get_routes();
    
    $grouped_endpoints = [];
    
    foreach ($endpoints as $route => $route_data) {
        // Get namespace from route
        $namespace = 'core';
        if (preg_match('#^/([^/]+)/#', $route, $matches)) {
            $namespace = $matches[1];
        }
        
        // Get methods
        $methods = [];
        foreach ($route_data as $handler) {
            if (isset($handler['methods'])) {
                if (is_array($handler['methods'])) {
                    $methods = array_merge($methods, array_keys($handler['methods']));
                } else {
                    $methods[] = $handler['methods'];
                }
            }
        }
        $methods = array_unique($methods);
        
        if (!isset($grouped_endpoints[$namespace])) {
            $grouped_endpoints[$namespace] = [];
        }
        
        $grouped_endpoints[$namespace][] = [
            'route' => $route,
            'methods' => $methods,
        ];
    }
    
    // Sort namespaces
    ksort($grouped_endpoints);
    
    return $grouped_endpoints;
}

/**
 * Register settings field
 */
function snn_setup_json_disable_field() {
    add_settings_field(
        'disable_json_endpoints',
        __('Disable REST API Endpoints for Guests', 'snn'),
        'snn_json_disable_callback',
        'snn-security',
        'snn_security_main_section'
    );
}
add_action('admin_init', 'snn_setup_json_disable_field');

/**
 * Render the settings field with endpoint list
 */
function snn_json_disable_callback() {
    $options = get_option('snn_security_options', []);
    $disabled_endpoints = isset($options['disabled_rest_endpoints']) ? $options['disabled_rest_endpoints'] : [];
    
    // Get all endpoints
    $grouped_endpoints = snn_get_rest_endpoints();
    
    ?>
    <div class="snn-rest-endpoints-wrapper">
        <p class="description">
            <?php esc_html_e('Select which REST API endpoints should be disabled for non-logged-in users. Logged-in users will still have access.', 'snn'); ?>
        </p>
        
        <div class="snn-select-all-wrapper">
            <label>
                <input type="checkbox" id="snn-select-all-endpoints">
                <strong><?php esc_html_e('Select/Deselect All', 'snn'); ?></strong>
            </label>
        </div>
        
        <div class="snn-endpoints-list">
            <?php foreach ($grouped_endpoints as $namespace => $endpoints): ?>
                <div class="snn-endpoint-namespace">
                    <h4 class="snn-namespace-title">
                        <?php echo esc_html($namespace); ?>
                        <span class="snn-endpoint-count">
                            (<?php echo count($endpoints); ?> <?php esc_html_e('endpoints', 'snn'); ?>)
                        </span>
                    </h4>
                    
                    <div class="snn-namespace-endpoints">
                        <?php foreach ($endpoints as $endpoint): ?>
                            <?php 
                            $endpoint_key = sanitize_key($endpoint['route']);
                            $is_checked = in_array($endpoint['route'], $disabled_endpoints);
                            ?>
                            <label class="snn-endpoint-label">
                                <input 
                                    type="checkbox" 
                                    class="snn-endpoint-checkbox"
                                    name="snn_security_options[disabled_rest_endpoints][]" 
                                    value="<?php echo esc_attr($endpoint['route']); ?>"
                                    <?php checked($is_checked); ?>
                                >
                                <code class="snn-endpoint-route">
                                    <?php echo esc_html($endpoint['route']); ?>
                                </code>
                                <span class="snn-endpoint-methods">
                                    <?php echo esc_html(implode(', ', $endpoint['methods'])); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <p class="description snn-total-endpoints">
            <?php 
            printf(
                esc_html__('Total endpoints found: %d', 'snn'),
                array_sum(array_map('count', $grouped_endpoints))
            ); 
            ?>
        </p>
    </div>
    
    <style>
        .form-table td p {
            margin-top: 0;
            margin-bottom: 0;
        }
        .form-table td ,
        .form-table th{
            padding: 10px;
        }
        .snn-select-all-wrapper {
            margin: 5px 0;
        }
        #snn-select-all-endpoints {
            margin-right: 5px;
        }
        .snn-endpoints-list {
            max-height: 240px;
            overflow-y: auto;
            padding: 5px;
            background: #f9f9f9;
        }
        .snn-endpoint-namespace {
            margin-bottom: 20px;
        }
        .snn-endpoint-namespace:last-child {
            margin-bottom: 0;
        }
        .snn-namespace-title {
            margin: 0 0 10px 0;
            padding: 8px;
            background: #fff;
            border-left: 4px solid #2271b1;
        }
        .snn-endpoint-count {
            font-size: 12px;
            font-weight: normal;
            color: #666;
        }
        .snn-namespace-endpoints {
            padding-left: 15px;
        }
        .snn-endpoint-label {
            display: block;
            padding: 4px;
            background: #fff;
            cursor: pointer;
            transition: background 0.2s;
        }
        .snn-endpoint-label:hover {
            background: #f0f0f1 !important;
        }
        .snn-endpoint-checkbox {
            margin-right: 8px;
        }
        .snn-endpoint-route {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
        .snn-endpoint-methods {
            color: #666;
            font-size: 12px;
            margin-left: 8px;
        }
        .snn-total-endpoints {
            margin-top: 10px;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Select/Deselect all functionality
        $('#snn-select-all-endpoints').on('change', function() {
            $('.snn-endpoint-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // Update select all checkbox based on individual checkboxes
        $('.snn-endpoint-checkbox').on('change', function() {
            var total = $('.snn-endpoint-checkbox').length;
            var checked = $('.snn-endpoint-checkbox:checked').length;
            $('#snn-select-all-endpoints').prop('checked', total === checked);
        });
        
        // Initialize select all checkbox state
        var total = $('.snn-endpoint-checkbox').length;
        var checked = $('.snn-endpoint-checkbox:checked').length;
        $('#snn-select-all-endpoints').prop('checked', total === checked);
    });
    </script>
    <?php
}

/**
 * Block selected REST API endpoints for non-logged-in users
 */
add_filter('rest_authentication_errors', function($result) {
    // Skip if already an error
    if (is_wp_error($result)) {
        return $result;
    }
    
    // Skip if user is logged in
    if (is_user_logged_in()) {
        return $result;
    }
    
    $options = get_option('snn_security_options', []);
    $disabled_endpoints = isset($options['disabled_rest_endpoints']) ? $options['disabled_rest_endpoints'] : [];
    
    // If no endpoints are disabled, allow all
    if (empty($disabled_endpoints)) {
        return $result;
    }
    
    // Get current route
    $current_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
    
    // Normalize route (remove leading slash if present)
    $current_route = '/' . ltrim($current_route, '/');
    
    // Check if current route is in disabled list
    foreach ($disabled_endpoints as $disabled_route) {
        // Exact match or pattern match (for routes with regex)
        if ($current_route === $disabled_route || preg_match('#^' . $disabled_route . '$#', $current_route)) {
            return new WP_Error(
                'rest_forbidden_endpoint',
                __('This endpoint is not available for non-logged-in users.', 'snn'),
                ['status' => 401]
            );
        }
    }
    
    return $result;
}, 10);
?>
