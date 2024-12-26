<?php


function snn_register_301_redirects_post_type() {
    register_post_type(
        'snn_301_redirects',
        array(
            'public'             => false,
            'show_ui'            => false,
            'publicly_queryable' => false,
            'rewrite'            => false,
            'label'              => 'SNN 301 Redirects'
        )
    );
}
add_action('init', 'snn_register_301_redirects_post_type');


function snn_add_301_redirects_page() {
    add_submenu_page(
        'snn-settings',               
        '301 Redirects',            
        '301 Redirects',            
        'manage_options',           
        'snn-301-redirects',        
        'snn_render_301_redirects_page' 
    );
}
add_action('admin_menu', 'snn_add_301_redirects_page');


function snn_normalize_path($url) {

    $url = preg_replace('/^https?:\/\/[^\/]+/i', '', $url);


    if (substr($url, 0, 1) !== '/') {
        $url = '/' . $url;
    }

    if ($url !== '/' && substr($url, -1) === '/') {
        $url = rtrim($url, '/');
    }
    return strtolower($url);
}

function snn_validate_url($url) {

    if (substr($url, 0, 1) === '/') {
        return true;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    }
    return false;
}


function snn_render_301_redirects_page() {
    global $wpdb;


    if (isset($_POST['submit_redirect']) && check_admin_referer('snn_301_redirect_nonce')) {
        $redirect_from = snn_normalize_path(sanitize_text_field($_POST['redirect_from']));
        $redirect_to   = sanitize_text_field($_POST['redirect_to']);

        if (!snn_validate_url($redirect_to)) {
            echo '<div class="notice notice-error"><p>Invalid redirect destination URL!</p></div>';
        } else {

            $existing_redirect = get_posts(array(
                'post_type'      => 'snn_301_redirects',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => 'redirect_from',
                        'value'   => $redirect_from,
                        'compare' => '='
                    )
                )
            ));

            if (!empty($existing_redirect)) {
                echo '<div class="notice notice-error"><p>A redirect for this path already exists!</p></div>';
            } else {

                $post_data = array(
                    'post_type'   => 'snn_301_redirects',
                    'post_status' => 'publish',
                    'post_title'  => $redirect_from
                );
                $post_id = wp_insert_post($post_data);
                if ($post_id) {
                    update_post_meta($post_id, 'redirect_from', $redirect_from);
                    update_post_meta($post_id, 'redirect_to', $redirect_to);
                    update_post_meta($post_id, 'created_date', current_time('mysql'));


                    update_post_meta($post_id, 'redirect_clicks', 0);


                    flush_rewrite_rules();

                    echo '<div class="notice notice-success"><p>Redirect added successfully!</p></div>';
                }
            }
        }
    }


    if (isset($_POST['delete_redirect']) && check_admin_referer('snn_301_redirect_delete_nonce')) {
        $post_id = intval($_POST['redirect_id']);
        if (wp_delete_post($post_id, true)) {

            flush_rewrite_rules();

            echo '<div class="notice notice-success"><p>Redirect deleted successfully!</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>301 Redirect Rules</h1>


        <div class="postbox">
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('snn_301_redirect_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="redirect_from">Redirect From</label></th>
                            <td>
                                <input type="text" id="redirect_from" name="redirect_from" class="regular-text" required>
                                <p class="description">Enter the path (e.g., /old-page or /category/old-post)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="redirect_to">Redirect To</label></th>
                            <td>
                                <input type="text" id="redirect_to" name="redirect_to" class="regular-text" required>
                                <p class="description">Enter the full URL or path (e.g., https://example.com/new-page or /new-page)</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit_redirect" class="button button-primary" value="Add Redirect">
                    </p>
                </form>
            </div>
        </div>


        <?php
        $redirects = get_posts(array(
            'post_type'      => 'snn_301_redirects',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ));
        if ($redirects) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Redirect From</th>
                        <th>Redirect To</th>
                        <th>Added Date</th>
                        <th>Clicks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($redirects as $redirect) : 
                        $clicks = (int) get_post_meta($redirect->ID, 'redirect_clicks', true);
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(home_url(get_post_meta($redirect->ID, 'redirect_from', true))); ?>" target="_blank">
                                    <?php echo esc_html(get_post_meta($redirect->ID, 'redirect_from', true)); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(get_post_meta($redirect->ID, 'redirect_to', true)); ?>" target="_blank">
                                    <?php echo esc_html(get_post_meta($redirect->ID, 'redirect_to', true)); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(get_post_meta($redirect->ID, 'created_date', true)); ?></td>
                            <td><?php echo esc_html($clicks); ?></td>
                            <td>
                                <form method="post" action="" style="display:inline;">
                                    <?php wp_nonce_field('snn_301_redirect_delete_nonce'); ?>
                                    <input type="hidden" name="redirect_id" value="<?php echo $redirect->ID; ?>">
                                    <input type="submit" name="delete_redirect" class="button button-small button-link-delete" value="Delete" onclick="return confirm('Are you sure you want to delete this redirect?');">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No redirects found.</p>
        <?php endif; ?>
    </div>
    <?php
}


function snn_handle_301_redirects() {

    if (is_admin()) return;


    $current_path = snn_normalize_path($_SERVER['REQUEST_URI']);


    $path_without_query = strtok($current_path, '?');


    $redirects = get_posts(array(
        'post_type'      => 'snn_301_redirects',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => 'redirect_from',
                'value'   => $path_without_query,
                'compare' => '='
            )
        )
    ));


    if (!empty($redirects)) {
        $redirect_id = $redirects[0]->ID;
        $redirect_to = get_post_meta($redirect_id, 'redirect_to', true);

        if ($redirect_to) {

            $clicks = (int) get_post_meta($redirect_id, 'redirect_clicks', true);
            $clicks++;
            update_post_meta($redirect_id, 'redirect_clicks', $clicks);


            $query_string = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if ($query_string) {

                $redirect_to .= (strpos($redirect_to, '?') !== false) ? '&' : '?';
                $redirect_to .= $query_string;
            }


            if (strpos($redirect_to, 'http') !== 0 && strpos($redirect_to, '//') !== 0) {
                $redirect_to = home_url($redirect_to);
            }


            $redirect_to_path = snn_normalize_path($redirect_to);
            if ($redirect_to_path !== $current_path) {
                nocache_headers();


                $is_external = (strpos($redirect_to, home_url()) !== 0);
                if ($is_external) {
                    wp_redirect($redirect_to, 301, 'SNN 301 Redirects');
                    exit;
                } else {

                    wp_safe_redirect($redirect_to, 301, 'SNN 301 Redirects');
                    exit;
                }
            }
        }
    }
}
add_action('template_redirect', 'snn_handle_301_redirects');


function snn_activate_301_redirects() {
    snn_register_301_redirects_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'snn_activate_301_redirects');

function snn_deactivate_301_redirects() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'snn_deactivate_301_redirects');
