<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

    if (isset($_POST['edit_redirect']) && check_admin_referer('snn_301_redirect_edit_nonce')) {
        $post_id = intval($_POST['redirect_id']);
        $new_redirect_from = snn_normalize_path(sanitize_text_field($_POST['edit_redirect_from']));
        $new_redirect_to   = sanitize_text_field($_POST['edit_redirect_to']);

        if (!snn_validate_url($new_redirect_to)) {
            echo '<div class="notice notice-error"><p>Invalid redirect destination URL!</p></div>';
        } else {
            $existing_redirect = get_posts(array(
                'post_type'      => 'snn_301_redirects',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => 'redirect_from',
                        'value'   => $new_redirect_from,
                        'compare' => '='
                    )
                ),
                'exclude'        => array($post_id),
            ));

            if (!empty($existing_redirect)) {
                echo '<div class="notice notice-error"><p>A redirect for this path already exists!</p></div>';
            } else {
                wp_update_post(array(
                    'ID'         => $post_id,
                    'post_title' => $new_redirect_from,
                ));
                update_post_meta($post_id, 'redirect_from', $new_redirect_from);
                update_post_meta($post_id, 'redirect_to', $new_redirect_to);

                echo '<div class="notice notice-success"><p>Redirect updated successfully!</p></div>';
            }
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
                    $redirect_id   = $redirect->ID;
                    $redirect_from = get_post_meta($redirect_id, 'redirect_from', true);
                    $redirect_to   = get_post_meta($redirect_id, 'redirect_to', true);
                    $created_date  = get_post_meta($redirect_id, 'created_date', true);
                    $clicks        = (int) get_post_meta($redirect_id, 'redirect_clicks', true);
                    ?>
                    <tr id="redirect-row-<?php echo esc_attr($redirect_id); ?>">
                        <td>
                            <a href="<?php echo esc_url(home_url($redirect_from)); ?>" target="_blank">
                                <?php echo esc_html($redirect_from); ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($redirect_to); ?>" target="_blank">
                                <?php echo esc_html($redirect_to); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($created_date); ?></td>
                        <td><?php echo esc_html($clicks); ?></td>
                        <td>
                            <form method="post" action="" style="display:inline;">
                                <?php wp_nonce_field('snn_301_redirect_delete_nonce'); ?>
                                <input type="hidden" name="redirect_id" value="<?php echo esc_attr($redirect_id); ?>">
                                <input type="submit" name="delete_redirect" class="button button-small button-link-delete" value="Delete" onclick="return confirm('Are you sure you want to delete this redirect?');">
                            </form>

                            <button
                                type="button"
                                class="button button-small edit-redirect"
                                data-redirect-id="<?php echo esc_attr($redirect_id); ?>"
                                data-redirect-from="<?php echo esc_attr($redirect_from); ?>"
                                data-redirect-to="<?php echo esc_attr($redirect_to); ?>"
                                >
                                Edit
                            </button>
                        </td>
                    </tr>

                    <tr id="edit-form-row-<?php echo esc_attr($redirect_id); ?>" style="display: none;">
                        <td colspan="5">
                            <form method="post" action="">
                                <?php wp_nonce_field('snn_301_redirect_edit_nonce'); ?>
                                <input type="hidden" name="edit_redirect" value="1">
                                <input type="hidden" name="redirect_id" value="<?php echo esc_attr($redirect_id); ?>">

                                <table class="form-table" style="margin: 0;">
                                    <tr>
                                        <th>Redirect From</th>
                                        <td>
                                            <input type="text" name="edit_redirect_from" id="edit-redirect-from-<?php echo esc_attr($redirect_id); ?>" class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Redirect To</th>
                                        <td>
                                            <input type="text" name="edit_redirect_to" id="edit-redirect-to-<?php echo esc_attr($redirect_id); ?>" class="regular-text" required>
                                        </td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <button type="submit" class="button button-primary">Save</button>
                                    <button type="button" class="button cancel-edit" data-redirect-id="<?php echo esc_attr($redirect_id); ?>">Cancel</button>
                                </p>
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

    <script>
    (function($){
        $('.edit-redirect').on('click', function(){
            var redirectId = $(this).data('redirect-id');
            var redirectFrom = $(this).data('redirect-from');
            var redirectTo = $(this).data('redirect-to');

            $('tr[id^="edit-form-row-"]').hide();

            $('#edit-form-row-' + redirectId).show();

            $('#edit-redirect-from-' + redirectId).val(redirectFrom);
            $('#edit-redirect-to-' + redirectId).val(redirectTo);
        });

        $('.cancel-edit').on('click', function(){
            var redirectId = $(this).data('redirect-id');
            $('#edit-form-row-' + redirectId).hide();
        });
    })(jQuery);
    </script>

    <?php
}

function snn_handle_301_redirects() {
    if (is_admin()) return;

    $request_uri  = $_SERVER['REQUEST_URI'];
    $parsed_url   = parse_url($request_uri);
    $current_path = isset($parsed_url['path']) ? snn_normalize_path($parsed_url['path']) : '/';
    $query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

    $redirects = get_posts(array(
        'post_type'      => 'snn_301_redirects',
        'posts_per_page' => -1
    ));

    foreach ($redirects as $redirect) {
        $redirect_from = get_post_meta($redirect->ID, 'redirect_from', true);
        $redirect_to   = get_post_meta($redirect->ID, 'redirect_to', true);

        if ($redirect_from === $current_path || $redirect_from === $current_path . '?' . $query_string) {
            if ($query_string) {
                $redirect_to .= (strpos($redirect_to, '?') !== false) ? '&' : '?';
                $redirect_to .= $query_string;
            }

            if (strpos($redirect_to, 'http') !== 0) {
                $redirect_to = home_url($redirect_to);
            }

            $clicks = (int) get_post_meta($redirect->ID, 'redirect_clicks', true);
            update_post_meta($redirect->ID, 'redirect_clicks', $clicks + 1);

            nocache_headers();
            wp_redirect($redirect_to, 301);
            exit;
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
