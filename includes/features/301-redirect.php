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
            'label'              => __( 'SNN 301 Redirects', 'snn' ),
            'supports'           => array( 'title' )
        )
    );
}
add_action('init', 'snn_register_301_redirects_post_type');

function snn_register_redirect_logs_post_type() {
    register_post_type(
        'snn_redirect_logs',
        array(
            'public'             => false,
            'show_ui'            => false,
            'publicly_queryable' => false,
            'rewrite'            => false,
            'label'              => __( 'SNN Redirect Logs', 'snn' ),
            'supports'           => array( 'title', 'custom-fields' )
        )
    );
}
add_action('init', 'snn_register_redirect_logs_post_type');

function snn_add_301_redirects_page() {
    add_submenu_page(
        'snn-settings',
        __( '301 Redirects', 'snn' ),
        __( '301 Redirects', 'snn' ),
        'manage_options',
        'snn-301-redirects',
        'snn_render_301_redirects_page'
    );
}
add_action('admin_menu', 'snn_add_301_redirects_page');

function snn_normalize_path($url) {
    // strip domain
    $url = preg_replace('/^https?:\/\/[^\/]+/i', '', $url);
    // decode any percent-encoding (so “%C3%B6” → “ö”)
    $url = rawurldecode( $url );
    // ensure leading slash
    if (substr($url, 0, 1) !== '/') {
        $url = '/' . $url;
    }
    // remove trailing slash except for root
    if ($url !== '/' && substr($url, -1) === '/') {
        $url = rtrim($url, '/');
    }
    // lowercase using mb to handle UTF-8
    if (function_exists('mb_strtolower')) {
        $url = mb_strtolower($url, 'UTF-8');
    } else {
        $url = strtolower($url);
    }
    return $url;
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

function snn_clear_redirects_cache() {
    delete_transient('snn_all_redirects_cache');
}

function snn_get_all_redirects() {
    // If we are not in the admin area, try to get from transient cache first.
    if ( ! is_admin() ) {
        $redirects = get_transient('snn_all_redirects_cache');
        // If the cache is not empty, return it.
        if (false !== $redirects) {
            return $redirects;
        }
    }

    // If we are in the admin area, or the transient is empty, query the database.
    $redirect_posts = get_posts(array(
        'post_type'      => 'snn_301_redirects',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ));

    $redirects = array();
    if ($redirect_posts) {
        foreach ($redirect_posts as $post) {
            // Store the data in a structured array for easier access
            $redirects[$post->ID] = array(
                'ID'              => $post->ID,
                'redirect_from'   => get_post_meta($post->ID, 'redirect_from', true),
                'redirect_to'     => get_post_meta($post->ID, 'redirect_to', true),
                'created_date'    => get_post_meta($post->ID, 'created_date', true),
                'redirect_clicks' => (int) get_post_meta($post->ID, 'redirect_clicks', true),
            );
        }
    }

    // If we are not in the admin area, set the transient for future front-end requests.
    if ( ! is_admin() ) {
        set_transient('snn_all_redirects_cache', $redirects, 12 * HOUR_IN_SECONDS);
    }

    return $redirects;
}


function snn_render_301_redirects_page() {
    global $wpdb;

    // Handle Import Redirects
    if (isset($_POST['import_redirects']) && check_admin_referer('snn_301_import_nonce')) {
        $import_data = sanitize_textarea_field($_POST['import_json']);
        // Stripslashes is needed because WordPress adds them to POST data
        $redirects_to_import = json_decode(stripslashes($import_data), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($redirects_to_import)) {
            $imported_count = 0;
            $skipped_count = 0;
            foreach ($redirects_to_import as $item) {
                if (isset($item['from']) && isset($item['to'])) {
                    $redirect_from = snn_normalize_path(sanitize_text_field($item['from']));
                    $redirect_to   = sanitize_text_field($item['to']);

                    if (!snn_validate_url($redirect_to)) {
                        $skipped_count++;
                        continue;
                    }

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

                    if (empty($existing_redirect)) {
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
                            $imported_count++;
                        } else {
                            $skipped_count++;
                        }
                    } else {
                        $skipped_count++;
                    }
                }
            }
            if ($imported_count > 0) {
                snn_clear_redirects_cache(); // Clear cache on change
                flush_rewrite_rules();
                echo '<div class="notice notice-success"><p>' . sprintf(esc_html__('%d redirects imported successfully!', 'snn'), $imported_count) . '</p></div>';
            }
            if ($skipped_count > 0) {
                echo '<div class="notice notice-warning"><p>' . sprintf(esc_html__('%d redirects were skipped (invalid format or already exist).', 'snn'), $skipped_count) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Invalid JSON format.', 'snn') . '</p></div>';
        }
    }

    // Handle Add Redirect
    if (isset($_POST['submit_redirect']) && check_admin_referer('snn_301_redirect_nonce')) {
        $redirect_from = snn_normalize_path(sanitize_text_field($_POST['redirect_from']));
        $redirect_to   = sanitize_text_field($_POST['redirect_to']);

        if (!snn_validate_url($redirect_to)) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid redirect destination URL!', 'snn' ) . '</p></div>';
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
                echo '<div class="notice notice-error"><p>' . esc_html__( 'A redirect for this path already exists!', 'snn' ) . '</p></div>';
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

                    snn_clear_redirects_cache(); // Clear cache on change
                    flush_rewrite_rules();
                    echo '<div class="notice notice-success"><p>' . esc_html__( 'Redirect added successfully!', 'snn' ) . '</p></div>';
                }
            }
        }
    }

    // Handle Delete Redirect
    if (isset($_POST['delete_redirect']) && check_admin_referer('snn_301_redirect_delete_nonce')) {
        $post_id = intval($_POST['redirect_id']);
        if (wp_delete_post($post_id, true)) {
            snn_clear_redirects_cache(); // Clear cache on change
            flush_rewrite_rules();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Redirect deleted successfully!', 'snn' ) . '</p></div>';
        }
    }

    // Handle Edit Redirect
    if (isset($_POST['edit_redirect']) && check_admin_referer('snn_301_redirect_edit_nonce')) {
        $post_id = intval($_POST['redirect_id']);
        $new_redirect_from = snn_normalize_path(sanitize_text_field($_POST['edit_redirect_from']));
        $new_redirect_to   = sanitize_text_field($_POST['edit_redirect_to']);

        if (!snn_validate_url($new_redirect_to)) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid redirect destination URL!', 'snn' ) . '</p></div>';
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
                echo '<div class="notice notice-error"><p>' . esc_html__( 'A redirect for this path already exists!', 'snn' ) . '</p></div>';
            } else {
                wp_update_post(array(
                    'ID'         => $post_id,
                    'post_title' => $new_redirect_from,
                ));
                update_post_meta($post_id, 'redirect_from', $new_redirect_from);
                update_post_meta($post_id, 'redirect_to', $new_redirect_to);

                snn_clear_redirects_cache(); // Clear cache on change
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Redirect updated successfully!', 'snn' ) . '</p></div>';
            }
        }
    }

    // Handle Clear All Logs
    if (isset($_POST['clear_all_logs']) && check_admin_referer('snn_301_clear_logs_nonce')) {
        $all_logs = get_posts(array(
            'post_type'      => 'snn_redirect_logs',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ));
        if (!empty($all_logs)) {
            foreach ($all_logs as $log_post) {
                wp_delete_post($log_post->ID, true);
            }
        }
        echo '<div class="notice notice-success"><p>' . esc_html__( 'All logs have been cleared!', 'snn' ) . '</p></div>';
    }

    // Handle Update Settings (Maximum Logs & Days to Keep Logs)
    if (isset($_POST['save_settings']) && check_admin_referer('snn_301_update_settings_nonce')) {
        $max_logs = intval($_POST['max_logs_to_keep']);
        $days_to_keep = intval($_POST['days_to_keep_logs']);
        $error = false;
        if ($max_logs < 1) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'The maximum number of logs must be at least 1.', 'snn' ) . '</p></div>';
            $error = true;
        }
        if ($days_to_keep < 1) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'The number of days must be at least 1.', 'snn' ) . '</p></div>';
            $error = true;
        }
        if (!$error) {
            update_option('snn_max_logs_to_keep', $max_logs);
            update_option('snn_days_to_keep_logs', $days_to_keep);
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings updated successfully!', 'snn' ) . '</p></div>';
        }
    }

    $max_logs = get_option('snn_max_logs_to_keep', 100);
    $days_to_keep = get_option('snn_days_to_keep_logs', 30);
    $recent_logs = get_posts(array(
        'post_type'      => 'snn_redirect_logs',
        'posts_per_page' => $max_logs,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ));
    ?>

    <div class="wrap">

        <h2 class="nav-tab-wrapper">
            <a href="#tab1" class="nav-tab nav-tab-active" data-tab="tab1"><?php esc_html_e( '301 Redirect Rules', 'snn' ); ?></a>
            <a href="#tab2" class="nav-tab" data-tab="tab2"><?php esc_html_e( 'Recent Redirect Logs', 'snn' ); ?></a>
        </h2>

        <div id="tab1" class="tab-content" style="display: block;">
            <h1><?php esc_html_e( '301 Redirect Rules', 'snn' ); ?></h1>

            <button id="show-add-redirect-form" class="button button-primary" style="margin-bottom: 15px;"><?php esc_html_e( 'Add Redirect', 'snn' ); ?></button>
            <button id="show-import-form" class="button" style="margin-bottom: 15px; margin-left: 5px;"><?php esc_html_e( 'Import', 'snn' ); ?></button>
            <button id="show-export-form" class="button" style="margin-bottom: 15px; margin-left: 5px;"><?php esc_html_e( 'Export', 'snn' ); ?></button>

            <!-- Import Form -->
            <div class="postbox" id="import-redirect-form" style="display: none;">
                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('snn_301_import_nonce'); ?>
                        <h3><?php esc_html_e( 'Import Redirects', 'snn' ); ?></h3>
                        <p><?php esc_html_e( 'Paste your JSON data here. The format should be an array of objects, like: [{"from":"/old-path","to":"/new-path"}, ...]', 'snn' ); ?></p>
                        <textarea name="import_json" class="widefat" rows="10" placeholder='[{"from":"/old-url","to":"/new-url"}]'></textarea>
                        <p class="submit">
                            <input type="submit" name="import_redirects" class="button button-primary" value="<?php esc_attr_e( 'Import', 'snn' ); ?>">
                            <button type="button" id="cancel-import-redirect" class="button"><?php esc_html_e( 'Cancel', 'snn' ); ?></button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Export Form -->
            <div class="postbox" id="export-redirect-form" style="display: none;">
                <div class="inside">
                    <h3><?php esc_html_e( 'Export Redirects', 'snn' ); ?></h3>
                    <p><?php esc_html_e( 'Copy the JSON data below to back up or transfer your redirects.', 'snn' ); ?></p>
                    <textarea id="export_json" class="widefat" rows="10" readonly></textarea>
                    <p class="submit">
                         <button type="button" id="cancel-export-redirect" class="button"><?php esc_html_e( 'Close', 'snn' ); ?></button>
                    </p>
                </div>
            </div>

            <!-- Add Redirect Form -->
            <div class="postbox" id="add-redirect-form" style="display: none;">
                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('snn_301_redirect_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th>
                                    <label for="redirect_from"><?php esc_html_e( 'Redirect From', 'snn' ); ?></label>
                                    <p class="description"><?php esc_html_e( 'Enter the path (e.g., /old-page or /category/old-post). Use /* at the end to match everything after.', 'snn' ); ?></p>
                                </th>
                                <td>
                                    <input type="text" id="redirect_from" name="redirect_from" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="redirect_to"><?php esc_html_e( 'Redirect To', 'snn' ); ?></label>
                                    <p class="description"><?php esc_html_e( 'Enter the full URL or path (e.g., https://example.com/new-page or /new-page). Use /* at the end if you used /* in the "Redirect From."', 'snn' ); ?></p>
                                </th>
                                <td>
                                    <input type="text" id="redirect_to" name="redirect_to" class="regular-text" required>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="submit_redirect" class="button button-primary" value="<?php esc_attr_e( 'Add Redirect', 'snn' ); ?>">
                            <button type="button" id="cancel-add-redirect" class="button"><?php esc_html_e( 'Cancel', 'snn' ); ?></button>
                        </p>
                    </form>
                </div>
            </div>

            <?php
            // Get redirects from our new cached function
            $redirects = snn_get_all_redirects();
            
            // Sort by clicks for display purposes
            if ($redirects) {
                uasort($redirects, function($a, $b) {
                    return $b['redirect_clicks'] <=> $a['redirect_clicks'];
                });
            }

            if ($redirects) : ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Redirect From', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Redirect To', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Added Date', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Clicks', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'snn' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($redirects as $redirect) :
                        $redirect_id   = $redirect['ID'];
                        $redirect_from = $redirect['redirect_from'];
                        $redirect_to   = $redirect['redirect_to'];
                        $created_date  = $redirect['created_date'];
                        $clicks        = $redirect['redirect_clicks'];
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
                                    <input type="submit" name="delete_redirect" class="button button-small button-link-delete" value="<?php esc_attr_e( 'Delete', 'snn' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this redirect?', 'snn' ); ?>');">
                                </form>
                                <button
                                    type="button"
                                    class="button button-small edit-redirect"
                                    data-redirect-id="<?php echo esc_attr($redirect_id); ?>"
                                    data-redirect-from="<?php echo esc_attr($redirect_from); ?>"
                                    data-redirect-to="<?php echo esc_attr($redirect_to); ?>"
                                    >
                                    <?php esc_html_e( 'Edit', 'snn' ); ?>
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
                                            <th><?php esc_html_e( 'Redirect From', 'snn' ); ?></th>
                                            <td>
                                                <input type="text" name="edit_redirect_from" id="edit-redirect-from-<?php echo esc_attr($redirect_id); ?>" class="regular-text" required>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Redirect To', 'snn' ); ?></th>
                                            <td>
                                                <input type="text" name="edit_redirect_to" id="edit-redirect-to-<?php echo esc_attr($redirect_id); ?>" class="regular-text" required>
                                            </td>
                                        </tr>
                                    </table>
                                    <p class="submit">
                                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'snn' ); ?></button>
                                        <button type="button" class="button cancel-edit" data-redirect-id="<?php echo esc_attr($redirect_id); ?>"><?php esc_html_e( 'Cancel', 'snn' ); ?></button>
                                    </p>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No redirects found.', 'snn' ); ?></p>
            <?php endif; ?>
        </div>

        <div id="tab2" class="tab-content" style="display: none;">
            <h2><?php esc_html_e( 'Recent Redirect Logs', 'snn' ); ?></h2>

            <form method="post" action="" style="margin-bottom: 2em;">
                <?php wp_nonce_field('snn_301_update_settings_nonce'); ?>
                <table class="form-table1">
                    <tr>
                        <th scope="row"><label for="max_logs_to_keep"><?php esc_html_e( 'Max number of logs to keep', 'snn' ); ?></label></th>
                        <td>
                            <input type="number" id="max_logs_to_keep" name="max_logs_to_keep" value="<?php echo esc_attr($max_logs); ?>" min="1" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="days_to_keep_logs"><?php esc_html_e( 'Max days to keep logs', 'snn' ); ?></label></th>
                        <td>
                            <input type="number" id="days_to_keep_logs" name="days_to_keep_logs" value="<?php echo esc_attr($days_to_keep); ?>" min="1" class="small-text">
                        </td>
                    </tr>
                </table>
                <p class="submit" style="margin-top:0; padding-top:0">
                    <input type="submit" name="save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'snn' ); ?>">
                </p>
            </form>

            <form method="post" action="" style="margin-bottom: 1em;">
                <?php wp_nonce_field('snn_301_clear_logs_nonce'); ?>
                <input type="submit" name="clear_all_logs" class="button button-secondary" value="<?php esc_attr_e( 'Clear All Logs', 'snn' ); ?>"
                       onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs? This action cannot be undone.', 'snn' ); ?>');">
            </form>

            <?php if (!empty($recent_logs)): ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Requested URL', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Redirected URL', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'IP Address', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'User Agent', 'snn' ); ?></th>
                            <th><?php esc_html_e( 'Referral', 'snn' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <?php
                                $redirect_from = get_post_meta($log->ID, 'redirect_from', true);
                                $redirect_to   = get_post_meta($log->ID, 'redirect_to', true);
                                $created_date  = get_post_meta($log->ID, 'created_date', true);
                                $ip_address    = get_post_meta($log->ID, 'ip_address', true);
                                $user_agent    = get_post_meta($log->ID, 'user_agent', true);
                                $referral      = get_post_meta($log->ID, 'referral', true);
                            ?>
                            <tr>
                                <td><?php echo esc_html($created_date); ?></td>
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
                                <td>
                                    <a href="https://radar.cloudflare.com/ip/<?php echo esc_html(preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $ip_address, $matches) ? $matches[1] : ''); ?>" target="_blank" class="ip-out-cloudflare">
                                        <?php echo esc_html($ip_address); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($user_agent); ?></td>
                                <td><?php echo esc_html($referral); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php esc_html_e( 'No recent logs found.', 'snn' ); ?></p>
            <?php endif; ?>
        </div>

    </div><script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple tab switching
        const tabLinks = document.querySelectorAll('.nav-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = this.getAttribute('data-tab');

                tabLinks.forEach(function(tab) {
                    tab.classList.remove('nav-tab-active');
                });
                tabContents.forEach(function(content) {
                    content.style.display = 'none';
                });

                this.classList.add('nav-tab-active');
                document.getElementById(target).style.display = 'block';
            });
        });

        // --- Form Toggle Logic ---
        const showAddRedirectButton = document.getElementById('show-add-redirect-form');
        const addRedirectForm = document.getElementById('add-redirect-form');
        const cancelAddRedirectButton = document.getElementById('cancel-add-redirect');

        const showImportButton = document.getElementById('show-import-form');
        const importForm = document.getElementById('import-redirect-form');
        const cancelImportButton = document.getElementById('cancel-import-redirect');

        const showExportButton = document.getElementById('show-export-form');
        const exportForm = document.getElementById('export-redirect-form');
        const cancelExportButton = document.getElementById('cancel-export-redirect');

        const mainButtons = [showAddRedirectButton, showImportButton, showExportButton];
        const allForms = [addRedirectForm, importForm, exportForm];

        function hideAllActionForms() {
            allForms.forEach(form => { if(form) form.style.display = 'none' });
            mainButtons.forEach(btn => { if(btn) btn.style.display = 'inline-block' });
        }

        // --- Random String Generator for Short URLs ---
        function generateRandomString(length) {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        function getExistingRedirects() {
            const existingRedirects = new Set();
            document.querySelectorAll('.edit-redirect').forEach(button => {
                if (button.dataset.redirectFrom) {
                    existingRedirects.add(button.dataset.redirectFrom);
                }
            });
            return existingRedirects;
        }

        // Show Add Redirect Form
        if (showAddRedirectButton && addRedirectForm) {
            showAddRedirectButton.addEventListener('click', function() {
                hideAllActionForms();
                addRedirectForm.style.display = 'block';
                showAddRedirectButton.style.display = 'none';

                // Generate and check for unique random short URL
                const existingRedirects = getExistingRedirects();
                let randomPath;
                do {
                    randomPath = '/' + generateRandomString(5);
                } while (existingRedirects.has(randomPath));

                document.getElementById('redirect_from').value = randomPath;
                document.getElementById('redirect_to').focus();
            });
        }
        if (cancelAddRedirectButton) {
            cancelAddRedirectButton.addEventListener('click', hideAllActionForms);
        }

        // Show Import Form
        if (showImportButton && importForm) {
            showImportButton.addEventListener('click', function() {
                hideAllActionForms();
                importForm.style.display = 'block';
                showImportButton.style.display = 'none';
            });
        }
        if (cancelImportButton) {
            cancelImportButton.addEventListener('click', hideAllActionForms);
        }

        // Show Export Form
        if (showExportButton && exportForm) {
            showExportButton.addEventListener('click', function() {
                hideAllActionForms();
                exportForm.style.display = 'block';
                showExportButton.style.display = 'none';

                // Generate JSON for export
                const redirects = [];
                document.querySelectorAll('tr[id^="redirect-row-"]').forEach(row => {
                    const fromLink = row.cells[0].querySelector('a');
                    const toLink = row.cells[1].querySelector('a');
                    if (fromLink && toLink) {
                        redirects.push({
                            from: fromLink.textContent.trim(),
                            to: toLink.textContent.trim()
                        });
                    }
                });
                document.getElementById('export_json').value = JSON.stringify(redirects, null, 2);
            });
        }
        if (cancelExportButton) {
            cancelExportButton.addEventListener('click', hideAllActionForms);
        }

        // Hide all edit forms
        function hideAllEditForms() {
            const editFormRows = document.querySelectorAll('tr[id^="edit-form-row-"]');
            editFormRows.forEach(function(row) {
                row.style.display = 'none';
            });
        }

        // Edit button click event
        const editRedirectButtons = document.querySelectorAll('.edit-redirect');
        editRedirectButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const redirectId   = this.dataset.redirectId;
                const redirectFrom = this.dataset.redirectFrom;
                const redirectTo   = this.dataset.redirectTo;

                hideAllEditForms();

                const editFormRow = document.getElementById('edit-form-row-' + redirectId);
                if (editFormRow) {
                    editFormRow.style.display = 'table-row';
                }

                document.getElementById('edit-redirect-from-' + redirectId).value = redirectFrom;
                document.getElementById('edit-redirect-to-' + redirectId).value   = redirectTo;
            });
        });

        // Cancel edit
        const cancelEditButtons = document.querySelectorAll('.cancel-edit');
        cancelEditButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const redirectId = this.dataset.redirectId;
                const editFormRow = document.getElementById('edit-form-row-' + redirectId);
                if (editFormRow) {
                    editFormRow.style.display = 'none';
                }
            });
        });
    });
    </script>
    <?php
}

function snn_handle_301_redirects() {
    if (is_admin()) return;

    $request_uri  = $_SERVER['REQUEST_URI'];
    $parsed_url   = parse_url($request_uri);
    $path         = isset($parsed_url['path']) ? rawurldecode($parsed_url['path']) : '/';
    $current_path = snn_normalize_path($path);
    $query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

    // Get all 301 redirect rules from our new cached function
    $all_redirects = snn_get_all_redirects();

    if (empty($all_redirects)) {
        return;
    }

    $exact_redirects = array();
    $wildcard_redirects = array();

    // Separate redirects into exact and wildcard matches for correct processing order
    foreach ($all_redirects as $redirect) {
        if (isset($redirect['redirect_from'])) {
            if (substr($redirect['redirect_from'], -2) === '/*') {
                $wildcard_redirects[] = $redirect;
            } else {
                $exact_redirects[] = $redirect;
            }
        }
    }

    // First process exact (non-wildcard) redirects
    foreach ($exact_redirects as $redirect) {
        $redirect_from = $redirect['redirect_from'];
        if ($redirect_from === $current_path || $redirect_from === $current_path . '?' . $query_string) {
            $redirect_to = $redirect['redirect_to'];
            if ($query_string) {
                $redirect_to .= (strpos($redirect_to, '?') !== false) ? '&' : '?';
                $redirect_to .= $query_string;
            }
            if (strpos($redirect_to, 'http') !== 0) {
                $redirect_to = home_url($redirect_to);
            }
            
            // Update click count directly in the DB. This does not invalidate the cache.
            $clicks = (int) get_post_meta($redirect['ID'], 'redirect_clicks', true);
            update_post_meta($redirect['ID'], 'redirect_clicks', $clicks + 1);

            snn_log_redirect($redirect_from, $redirect_to);
            nocache_headers();
            wp_redirect($redirect_to, 301);
            exit;
        }
    }

    // Then process wildcard redirects (redirects ending with "/*")
    foreach ($wildcard_redirects as $redirect) {
        $redirect_from = $redirect['redirect_from'];
        $redirect_to   = $redirect['redirect_to'];
        $base_from     = substr($redirect_from, 0, -2);
        
        if ($current_path === $base_from || strpos($current_path, $base_from . '/') === 0) {
            $leftover = '';
            if (strlen($current_path) > strlen($base_from)) {
                $leftover = substr($current_path, strlen($base_from));
            }
            $leftover = ltrim($leftover, '/');

            // Prevent directory traversal attacks
            if (strpos($leftover, '..') !== false) {
                continue;
            }

            $base_to = $redirect_to;
            if (substr($redirect_to, -2) === '/*') {
                $base_to = substr($redirect_to, 0, -2);
            }
            $final_destination = rtrim($base_to, '/');
            if ($leftover !== '') {
                $final_destination .= '/' . $leftover;
            }
            if ($query_string) {
                $final_destination .= (strpos($final_destination, '?') !== false) ? '&' : '?';
                $final_destination .= $query_string;
            }
            if (strpos($final_destination, 'http') !== 0) {
                $final_destination = home_url($final_destination);
            }
            
            // Update click count directly in the DB
            $clicks = (int) get_post_meta($redirect['ID'], 'redirect_clicks', true);
            update_post_meta($redirect['ID'], 'redirect_clicks', $clicks + 1);

            snn_log_redirect($current_path, $final_destination);
            nocache_headers();
            wp_redirect($final_destination, 301);
            exit;
        }
    }
}
add_action('template_redirect', 'snn_handle_301_redirects', 0);

function snn_log_redirect($redirect_from, $redirect_to) {
    $log_post = array(
        'post_type'   => 'snn_redirect_logs',
        'post_title'  => sprintf( __( 'Redirect from %1$s to %2$s', 'snn' ), $redirect_from, $redirect_to ),
        'post_status' => 'publish',
        'post_author' => 0,
    );

    $log_id = wp_insert_post($log_post);

    if ($log_id) {
        update_post_meta($log_id, 'redirect_from', $redirect_from);
        update_post_meta($log_id, 'redirect_to', $redirect_to);
        update_post_meta($log_id, 'created_date', current_time('mysql'));
        update_post_meta($log_id, 'ip_address', snn_get_client_ip());
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        update_post_meta($log_id, 'user_agent', $user_agent);
        $referral = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        update_post_meta($log_id, 'referral', $referral);

        // Enforce the maximum number of logs and remove old ones if needed
        snn_enforce_max_logs();
    }
}

function snn_enforce_max_logs() {
    $days_to_keep = get_option('snn_days_to_keep_logs', 30);
    if ($days_to_keep > 0) {
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
        $old_logs = get_posts(array(
            'post_type'      => 'snn_redirect_logs',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'date_query'     => array(
                array(
                    'column' => 'post_date',
                    'before' => $date_threshold,
                ),
            ),
            'fields'         => 'ids',
        ));
        if (!empty($old_logs)) {
            foreach ($old_logs as $log_id) {
                wp_delete_post($log_id, true);
            }
        }
    }

    $max_logs = get_option('snn_max_logs_to_keep', 100);
    $total_logs = wp_count_posts('snn_redirect_logs')->publish;

    if ($total_logs > $max_logs) {
        $logs_to_delete = $total_logs - $max_logs;
        $old_logs = get_posts(array(
            'post_type'      => 'snn_redirect_logs',
            'posts_per_page' => $logs_to_delete,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ));

        if (!empty($old_logs)) {
            foreach ($old_logs as $log_id) {
                wp_delete_post($log_id, true);
            }
        }
    }
}

function snn_get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return sanitize_text_field($ip);
}

function snn_activate_301_redirects() {
    snn_register_301_redirects_post_type();
    snn_register_redirect_logs_post_type();
    snn_clear_redirects_cache(); // Clear cache on activation
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'snn_activate_301_redirects');

function snn_deactivate_301_redirects() {
    snn_clear_redirects_cache(); // Clear cache on deactivation
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'snn_deactivate_301_redirects');
