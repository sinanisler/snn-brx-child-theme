<?php
// Add Search Logs submenu in admin
function snn_add_search_logs_page() {
    add_submenu_page(
        'snn-settings',
        'Search Logs',
        'Search Logs',
        'manage_options',
        'snn-search-logs',
        'snn_render_search_logs_page'
    );
}
add_action('admin_menu', 'snn_add_search_logs_page');

// Register the 'snn_search_logs' post type
function snn_register_search_logs_post_type() {
    register_post_type('snn_search_logs', array(
        'labels' => array(
            'name'          => 'Search Logs',
            'singular_name' => 'Search Log',
        ),
        'public'  => false,
        'show_ui' => true,
        'supports' => array('title'),
    ));
}
add_action('init', 'snn_register_search_logs_post_type');

// Register custom taxonomies for IP Address, User Agent, and Search Count
function snn_register_search_logs_taxonomies() {
    register_taxonomy('snn_ip_address', 'snn_search_logs', array(
        'labels' => array(
            'name'          => 'IP Addresses',
            'singular_name' => 'IP Address',
        ),
        'public'       => false,
        'hierarchical' => false,
        'show_ui'      => true,
    ));

    register_taxonomy('snn_user_agent', 'snn_search_logs', array(
        'labels' => array(
            'name'          => 'User Agents',
            'singular_name' => 'User Agent',
        ),
        'public'       => false,
        'hierarchical' => false,
        'show_ui'      => true,
    ));

    register_taxonomy('snn_search_count', 'snn_search_logs', array(
        'labels' => array(
            'name'          => 'Search Counts',
            'singular_name' => 'Search Count',
        ),
        'public'       => false,
        'hierarchical' => false,
        'show_ui'      => false,
    ));
}
add_action('init', 'snn_register_search_logs_taxonomies');

// Log search queries
function snn_log_search_query($query) {
    if (!empty($query) && get_option('snn_search_logging_enabled') === '1') {
        // Check if a term for this query already exists in snn_search_count
        $term = get_term_by('name', $query, 'snn_search_count');

        if ($term && !is_wp_error($term)) {
            // Increment the search count by updating the term
            wp_update_term($term->term_id, 'snn_search_count', array(
                'name' => $query,
                'slug' => $term->slug,
                'count' => $term->count + 1, // WP normally recalculates 'count', but we'll keep the user's original code
            ));
        } else {
            // Create a new term with an initial count of 1
            wp_insert_term($query, 'snn_search_count', array(
                'slug'  => sanitize_title($query),
                'count' => 1,
            ));

            // After creating, get the new term again if needed
            $term = get_term_by('name', $query, 'snn_search_count');
        }

        // Log the search query in a custom post type
        $post_data = array(
            'post_type'   => 'snn_search_logs',
            'post_status' => 'publish',
            'post_title'  => $query,
        );
        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Assign IP and User Agent
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown IP';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown User Agent';

            wp_set_object_terms($post_id, $ip_address, 'snn_ip_address', false);
            wp_set_object_terms($post_id, $user_agent, 'snn_user_agent', false);

            // Also assign the search_count term to this log so the built-in taxonomy 'count' can auto-increment properly
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($post_id, (int) $term->term_id, 'snn_search_count', true);
            }
        }
    }
}
// Hook into search queries and log them if main query is a search
add_action('pre_get_posts', function ($query) {
    if ($query->is_main_query() && $query->is_search() && !is_admin()) {
        snn_log_search_query($query->get('s'));
    }
});

// Render Search Logs admin page
function snn_render_search_logs_page() {
    // Process form submissions
    if (isset($_POST['snn_search_logging_submit'])) {
        // Update "Enable Search Logging" setting
        $enabled = isset($_POST['snn_search_logging_enabled']) ? '1' : '0';
        update_option('snn_search_logging_enabled', $enabled);

        // Update maximum log size limit
        if (isset($_POST['snn_search_log_size_limit'])) {
            update_option('snn_search_log_size_limit', intval($_POST['snn_search_log_size_limit']));
        }
    }

    // Process "Clear All Logs" button
    if (isset($_POST['snn_clear_search_logs'])) {
        // Delete all snn_search_logs posts
        $logs_to_delete = get_posts(array(
            'post_type'      => 'snn_search_logs',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        if (!empty($logs_to_delete)) {
            foreach ($logs_to_delete as $log_id) {
                wp_delete_post($log_id, true);
            }
        }
        // You can uncomment this section if you want to also remove the search_count terms completely:
        /*
        $terms = get_terms(array(
            'taxonomy'   => 'snn_search_count',
            'hide_empty' => false,
        ));
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, 'snn_search_count');
            }
        }
        */
        echo '<div class="updated"><p>All search logs have been cleared.</p></div>';
    }

    $logging_enabled = get_option('snn_search_logging_enabled') === '1';
    $log_size_limit  = get_option('snn_search_log_size_limit', 100);
    ?>
    <div class="wrap">
        <h1>Search Logs</h1>

        <form method="post" action="">
            <label>
                <input type="checkbox" name="snn_search_logging_enabled" 
                       <?php checked($logging_enabled); ?>>
                Enable Search Logging
            </label>
            <br><br>
            
            <label>
                Maximum number of logs to keep:
                <input type="number" name="snn_search_log_size_limit" 
                       value="<?php echo esc_attr($log_size_limit); ?>" 
                       min="1" style="width: 100px;">
            </label>
            <br><br>
            
            <?php submit_button('Save Changes', 'primary', 'snn_search_logging_submit', false); ?>
        </form>

        <?php if ($logging_enabled): ?>
            <form method="post" action="">
                <?php submit_button('Clear All Logs', 'delete', 'snn_clear_search_logs'); ?>
            </form>

            <div style="display: flex; gap: 20px; margin-top:20px;">
                <!-- Search Logs Table -->
                <div style="flex: 1;">
                    <h2>Recent Search Logs</h2>
                    <?php
                    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                    $logs_per_page = 100;

                    $args = array(
                        'post_type'      => 'snn_search_logs',
                        'posts_per_page' => $logs_per_page,
                        'paged'          => $paged,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    );
                    $logs_query = new WP_Query($args);
                    $logs = $logs_query->posts;
                    ?>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Search Query</th>
                                <th>Date</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($logs) {
                                foreach ($logs as $log) {
                                    $ip_term  = wp_get_object_terms($log->ID, 'snn_ip_address');
                                    $ua_term  = wp_get_object_terms($log->ID, 'snn_user_agent');
                                    $ip_value = (!empty($ip_term) && !is_wp_error($ip_term)) ? $ip_term[0]->name : 'N/A';
                                    $ua_value = (!empty($ua_term) && !is_wp_error($ua_term)) ? $ua_term[0]->name : 'N/A';
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($log->post_title); ?></td>
                                        <td><?php echo esc_html(get_the_date('', $log->ID)); ?></td>
                                        <td>
                                        <a href="https://radar.cloudflare.com/ip/<?php echo esc_html($ip_value); ?>" target="_blank">
                                            <?php echo esc_html($ip_value); ?>
                                        </a>
                                        </td>
                                        <td><?php echo esc_html($ua_value); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="4">No logs found.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>

                    <?php
                    // Pagination
                    $total_pages = $logs_query->max_num_pages;

                    if ($total_pages > 1) {
                        echo '<div class="tablenav bottom">';
                        echo paginate_links(array(
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $total_pages,
                        ));
                        echo '</div>';
                    }

                    wp_reset_postdata();
                    ?>
                </div>

                <!-- Top 100 Searches Table -->
                <div style="flex: 1;">
                    <h2>Top 100 Searches</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Search Query</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $terms = get_terms(array(
                                'taxonomy'   => 'snn_search_count',
                                'orderby'    => 'count',
                                'order'      => 'DESC',
                                'number'     => 100,
                                'hide_empty' => false,
                            ));

                            if ($terms && !is_wp_error($terms)) {
                                foreach ($terms as $term) {
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($term->name); ?></td>
                                        <td><?php echo esc_html($term->count); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="2">No searches found.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
