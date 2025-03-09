<?php

// Add the Other Settings submenu page.
function snn_add_other_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        'Other Settings',
        'Other Settings',
        'manage_options',
        'snn-other-settings',
        'snn_render_other_settings',
        1
    );
}
add_action('admin_menu', 'snn_add_other_settings_submenu');

function snn_render_other_settings() {
    ?>
    <div class="wrap">
        <h1>Other Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_other_settings_group');
                do_settings_sections('snn-other-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_other_settings() {
    register_setting(
        'snn_other_settings_group',
        'snn_other_settings',
        'snn_sanitize_other_settings'
    );

    add_settings_section(
        'snn_other_settings_section',
        'Other Settings',
        'snn_other_settings_section_callback',
        'snn-other-settings'
    );

    add_settings_field(
        'enqueue_gsap',
        'Enable GSAP and Lottie Element',
        'snn_enqueue_gsap_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'revisions_limit',
        'Limit Post Revisions',
        'snn_revisions_limit_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    // New field: Enable Custom Codes Backup.
    add_settings_field(
        'backup_custom_codes',
        'Enable Custom Codes Backup',
        'snn_backup_custom_codes_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'auto_update_bricks',
        'Auto Update Bricks Theme (Main Theme Only)',
        'snn_auto_update_bricks_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'move_bricks_menu',
        'Move Bricks Menu to End',
        'snn_move_bricks_menu_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'disable_comments',
        'Disable Comments',
        'snn_disable_comments_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'enable_thumbnail_column',
        'Enable Thumbnail Column in Post Tables',
        'snn_enable_thumbnail_column_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'disable_dashboard_widgets',
        'Disable Default Dashboard Widgets',
        'snn_disable_dashboard_widgets_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'dashboard_custom_metabox_content',
        'Dashboard Custom Metabox Content',
        'snn_dashboard_custom_metabox_content_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );
}
add_action('admin_init', 'snn_register_other_settings');

function snn_sanitize_other_settings($input) {
    $sanitized = array();

    $sanitized['enqueue_gsap'] = isset($input['enqueue_gsap']) && $input['enqueue_gsap'] ? 1 : 0;

    if (isset($input['revisions_limit']) && $input['revisions_limit'] !== '') {
        $sanitized['revisions_limit'] = intval($input['revisions_limit']);
    } else {
        $sanitized['revisions_limit'] = '';
    }

    // Sanitize new backup setting.
    $sanitized['backup_custom_codes'] = isset($input['backup_custom_codes']) && $input['backup_custom_codes'] ? 1 : 0;

    $sanitized['auto_update_bricks'] = isset($input['auto_update_bricks']) && $input['auto_update_bricks'] ? 1 : 0;
    $sanitized['move_bricks_menu'] = isset($input['move_bricks_menu']) && $input['move_bricks_menu'] ? 1 : 0;
    $sanitized['disable_comments'] = isset($input['disable_comments']) && $input['disable_comments'] ? 1 : 0;
    $sanitized['enable_thumbnail_column'] = isset($input['enable_thumbnail_column']) && $input['enable_thumbnail_column'] ? 1 : 0;
    $sanitized['disable_dashboard_widgets'] = isset($input['disable_dashboard_widgets']) && $input['disable_dashboard_widgets'] ? 1 : 0;

    if (isset($input['dashboard_custom_metabox_content'])) {
        $sanitized['dashboard_custom_metabox_content'] = wp_kses_post($input['dashboard_custom_metabox_content']);
    } else {
        $sanitized['dashboard_custom_metabox_content'] = '';
    }

    return $sanitized;
}

function snn_other_settings_section_callback() {
    echo '<p>Configure additional settings for your site below.</p>';
}

function snn_enqueue_gsap_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enqueue_gsap]" value="1" <?php checked(1, isset($options['enqueue_gsap']) ? $options['enqueue_gsap'] : 0); ?>>
    <p>
        Enabling this setting will enqueue the GSAP library and its associated scripts on your website.<br>
        GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.<br><br>
        - Ability to create GSAP animations with just data-animate attributes.<br>
        - gsap.min.php: The core GSAP library.<br>
        - ScrollTrigger.min.php: A GSAP plugin that enables scroll-based animations.<br>
        - gsap-data-animate.php: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.<br>
        - lottie.min.php and Lottie Element<br><br>
        Read <a href="https://github.com/sinanisler/snn-brx-child-theme/wiki/GSAP-ScrollTrigger-Animations" target="_blank">
            Documentation and Examples</a> for more details.
    </p>
    <?php
}

function snn_revisions_limit_callback() {
    $options = get_option('snn_other_settings');
    $value = (isset($options['revisions_limit']) && $options['revisions_limit'] !== '' && intval($options['revisions_limit']) > 0) 
             ? intval($options['revisions_limit']) 
             : '';
    ?>
    <input type="number" name="snn_other_settings[revisions_limit]" value="<?php echo esc_attr($value); ?>" placeholder="9999">
    <?php
}

function snn_backup_custom_codes_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[backup_custom_codes]" value="1" <?php checked(1, isset($options['backup_custom_codes']) ? $options['backup_custom_codes'] : 0); ?>>
    <p>
        Enabling this setting will create daily backups of your <code>custom-codes-here.php</code> file for the last 14 days.<br>
        The backup is saved in the database as plain text. Download links will be available in the Theme Editor when editing this file.
    </p>
    <?php
}

function snn_auto_update_bricks_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[auto_update_bricks]" value="1" <?php checked(1, isset($options['auto_update_bricks']) ? $options['auto_update_bricks'] : 0); ?>>
    <?php
}

function snn_move_bricks_menu_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[move_bricks_menu]" value="1" <?php checked(1, isset($options['move_bricks_menu']) ? $options['move_bricks_menu'] : 0); ?>>
    <?php
}

function snn_disable_comments_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <label>
        <input type="checkbox" name="snn_other_settings[disable_comments]" value="1" <?php checked(1, isset($options['disable_comments']) ? $options['disable_comments'] : 0); ?>>
        Disable all comments on the site
    </label>
    <?php
}

function snn_enable_thumbnail_column_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enable_thumbnail_column]" value="1" <?php checked(1, isset($options['enable_thumbnail_column']) ? $options['enable_thumbnail_column'] : 0); ?>>
    <p>
        Enabling this setting will add a "Thumbnail" column to your post tables in the admin dashboard.<br>
        This allows you to see the featured image of each post directly in the list view.
    </p>
    <?php
}

function snn_disable_dashboard_widgets_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[disable_dashboard_widgets]" value="1" <?php checked(1, isset($options['disable_dashboard_widgets']) ? $options['disable_dashboard_widgets'] : 0); ?>>
    <p>
        Enabling this setting will remove several default dashboard widgets from the WordPress admin dashboard.<br>
        This helps in decluttering the dashboard and focusing on the essential information.
    </p>
    <?php
}

function snn_dashboard_custom_metabox_content_callback() {
    $options = get_option('snn_other_settings');
    $content = isset($options['dashboard_custom_metabox_content']) ? $options['dashboard_custom_metabox_content'] : '';
    ?>
    <textarea name="snn_other_settings[dashboard_custom_metabox_content]" rows="5" class="large-text" style="max-width:600px"><?php echo esc_textarea($content); ?></textarea>
    <p>
        Enter the HTML content for the custom dashboard metabox. You can include HTML tags for formatting, and now you can also paste shortcodes which will be executed.
    </p>
    <?php
}

function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', SNN_URL_ASSETS . 'js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', SNN_URL_ASSETS . 'js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', SNN_URL_ASSETS . 'js/gsap-data-animate.js?v0.03', array(), null, true);
        wp_enqueue_script('lottie-js', SNN_URL_ASSETS . 'js/lottie.min.js', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');
add_action('admin_enqueue_scripts', 'snn_enqueue_gsap_scripts');

function snn_limit_post_revisions($num, $post) {
    $options = get_option('snn_other_settings');
    if (isset($options['revisions_limit']) && intval($options['revisions_limit']) > 0) {
        return intval($options['revisions_limit']);
    }
    return $num;
}
add_filter('wp_revisions_to_keep', 'snn_limit_post_revisions', 10, 2);

function snn_auto_update_bricks_theme($update, $item) {
    $options = get_option('snn_other_settings');
    if (isset($options['auto_update_bricks']) && $options['auto_update_bricks'] && isset($item->theme) && $item->theme === 'bricks') {
        return true;
    }
    return $update;
}
add_filter('auto_update_theme', 'snn_auto_update_bricks_theme', 10, 2);

function snn_custom_menu_order($menu_ord) {
    $options = get_option('snn_other_settings');
    if (isset($options['move_bricks_menu']) && $options['move_bricks_menu']) {
        if (!$menu_ord) return true;
        $bricks_menu = null;
        foreach ($menu_ord as $index => $item) {
            if ($item === 'bricks') {
                $bricks_menu = $item;
                unset($menu_ord[$index]);
                break;
            }
        }
        if ($bricks_menu) {
            $menu_ord[] = $bricks_menu;
        }
        return $menu_ord;
    }
    return $menu_ord;
}
add_filter('menu_order', 'snn_custom_menu_order');
add_filter('custom_menu_order', '__return_true');

function snn_hide_comments_section() {
    $options = get_option('snn_other_settings');
    if (isset($options['disable_comments']) && $options['disable_comments']) {
        echo '<style>#menu-comments { display: none !important; }</style>';
        update_option('comment_registration', 1);
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
    } else {
        update_option('comment_registration', 0);
    }
}
add_action('admin_head', 'snn_hide_comments_section');

function snn_add_thumbnail_column() {
    $options = get_option('snn_other_settings');
    if (isset($options['enable_thumbnail_column']) && $options['enable_thumbnail_column']) {
        add_filter('manage_posts_columns', 'snn_add_thumbnail_column_header');
        add_action('manage_posts_custom_column', 'snn_display_thumbnail_column', 10, 2);

        $post_types = get_post_types(array('public' => true), 'names');
        foreach ($post_types as $post_type) {
            if ($post_type === 'post' || $post_type === 'product') {
                continue;
            }
            if (!post_type_supports($post_type, 'thumbnail')) {
                continue;
            }
            add_filter("manage_edit-{$post_type}_columns", 'snn_add_thumbnail_column_header');
            add_action("manage_{$post_type}_posts_custom_column", 'snn_display_thumbnail_column', 10, 2);
        }
    }
}
add_action('admin_init', 'snn_add_thumbnail_column');

function snn_add_thumbnail_column_style() {
    $options = get_option('snn_other_settings');
    if (isset($options['enable_thumbnail_column'])) {
        echo '<style>.post_thumbnail img:nth-child(2) { display: none; }</style>';
    }
}
add_action('admin_head', 'snn_add_thumbnail_column_style');

function snn_add_thumbnail_column_header($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['post_thumbnail'] = __('Thumbnail');
        }
    }
    return $new_columns;
}

function snn_display_thumbnail_column($column, $post_id) {
    if ($column === 'post_thumbnail') {
        $post_thumbnail_id = get_post_thumbnail_id($post_id);
        if ($post_thumbnail_id) {
            $post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
            echo '<img src="' . esc_url($post_thumbnail_img[0]) . '" width="80" />';
        } else {
            echo __('--');
        }
    }
}

function snn_remove_thumbnail_column_for_product($columns) {
    if (isset($columns['post_thumbnail'])) {
        unset($columns['post_thumbnail']);
    }
    return $columns;
}
add_filter('manage_edit-product_columns', 'snn_remove_thumbnail_column_for_product', 20);

function snn_maybe_remove_dashboard_widgets() {
    $options = get_option('snn_other_settings');
    if (isset($options['disable_dashboard_widgets']) && $options['disable_dashboard_widgets']) {
        remove_action('welcome_panel', 'wp_welcome_panel');
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    }
}
add_action('wp_dashboard_setup', 'snn_maybe_remove_dashboard_widgets');

function snn_maybe_add_dashboard_custom_metabox() {
    $options = get_option('snn_other_settings');
    if (!empty($options['dashboard_custom_metabox_content'])) {
        add_meta_box(
            'snn_custom_dashboard_metabox',
            'Welcome',
            'snn_display_custom_dashboard_metabox',
            'dashboard',
            'normal',
            'high'
        );
    }
}
add_action('wp_dashboard_setup', 'snn_maybe_add_dashboard_custom_metabox');

/**
 * Display custom dashboard metabox content.
 *
 * If the textarea content contains any shortcode syntax, we "delay"
 * the execution of frontend head/footer actions so that all enqueued frontend
 * styles/scripts (even those added very late) are captured.
 *
 * We also remove the deprecated wp_admin_bar_header action and force-print
 * the inline CSS with ID "bricks-frontend-inline-inline-css" if missing.
 */
function snn_display_custom_dashboard_metabox() {
    $options = get_option('snn_other_settings');
    $content = isset($options['dashboard_custom_metabox_content']) ? $options['dashboard_custom_metabox_content'] : '';

    $current_user = wp_get_current_user();
    $content = str_replace('{first_name}', esc_html($current_user->user_firstname), $content);
    $content = str_replace('{homepage_url}', esc_url(home_url('/')), $content);

    // Check for shortcode syntax in the content.
    if (preg_match('/\[[^\]]+\]/', $content)) {

        // Remove the deprecated admin bar header to avoid its notice.
        remove_action('wp_head', 'wp_admin_bar_header');

        // Capture very-late head resources.
        ob_start();
        do_action('wp_head');
        $frontend_head = ob_get_clean();

        // Capture extra styles and scripts.
        ob_start();
        wp_print_styles();
        wp_print_scripts();
        $extra_resources = ob_get_clean();

        // Ensure the inline CSS with ID "bricks-frontend-inline-inline-css" is present.
        if (false === strpos($frontend_head, "bricks-frontend-inline-inline-css")) {
            ob_start();
            wp_print_styles('bricks-frontend-inline-inline-css');
            $bricks_inline_css = ob_get_clean();
            $frontend_head .= $bricks_inline_css;
            echo '
            <style>
                .postbox-container{width:100% !important}
                .postbox-header, #screen-meta-links {display:none}
                .inside{margin:0 !important; padding:0 !important}
                #wpcontent{padding-left:0 !important}
                .wrap{margin:0 !important; width:100% !important; display:flex !important; flex-direction: column; overflow-x: hidden;}
                #dashboard-widgets{padding:0 !important}
                .wrap h1:first-of-type{display:none}
                .postbox{border:none !important}
            </style>
            ';
        }

        // Capture footer resources.
        ob_start();
        do_action('wp_footer');
        $frontend_footer = ob_get_clean();

        echo $frontend_head . $extra_resources;
        echo do_shortcode($content);
        echo $frontend_footer;
    } else {
        echo do_shortcode($content);
    }
}

/**
 * Custom admin sidebar for the Theme Editor page.
 * When editing custom-codes-here.php in the child theme, and if the backup setting is enabled,
 * this function stores a backup (if one does not exist for today) in the database as a string and lists
 * download links for backups from the last 14 days.
 */
function custom_admin_sidebar() {
    // Ensure we are in the admin area.
    if (!is_admin()) {
        return;
    }

    // Get the current screen.
    $screen = get_current_screen();
    if ($screen->id !== 'theme-editor') {
        return;
    }

    // Get URL parameters.
    $file  = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';
    $theme = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';

    // Check if the correct file and theme are being edited.
    if ($file !== 'custom-codes-here.php' || $theme !== 'snn-brx-child-theme') {
        return;
    }

    // Check if the backup setting is enabled.
    $options = get_option('snn_other_settings');
    $backup_enabled = isset($options['backup_custom_codes']) && $options['backup_custom_codes'];

    $backup_links_html = '';
    if ($backup_enabled) {
        // Get child theme directory and file.
        $child_theme_dir = get_stylesheet_directory();
        $custom_file = $child_theme_dir . '/custom-codes-here.php';

        if (file_exists($custom_file)) {
            // Read file content.
            $content = file_get_contents($custom_file);
            // Today's date.
            $today = date('Y-m-d');
            // Retrieve backups from the database.
            $backups = get_option('snn_custom_codes_backups', array());

            // If today's backup does not exist, add it.
            if (!isset($backups[$today])) {
                $backups[$today] = $content;
            }

            // Remove backups older than 14 days.
            foreach ($backups as $date => $backup_content) {
                if (strtotime($date) < strtotime('-14 days')) {
                    unset($backups[$date]);
                }
            }
            // Update the option.
            update_option('snn_custom_codes_backups', $backups);

            // Generate download links.
            foreach ($backups as $date => $backup_content) {
                $download_url = admin_url('admin-post.php?action=download_custom_codes_backup&backup_date=' . urlencode($date));
                $backup_links_html .= '<li><a href="' . esc_url($download_url) . '" download>Backup from ' . esc_html($date) . '</a></li>';
            }
        }
    }
    ?>
    <style>
        /* Sidebar styling */
        #custom-sidebar {
            position: fixed;
            right: 0;
            bottom: 0;
            width: 200px;
            height: 150px;
            background: #f1f1f1;
            padding: 15px;
            overflow-y: auto;
            z-index: 9999;
        }
        #custom-sidebar h3 {
            margin-top: 0;
            font-size: 18px;
        }
        #custom-sidebar ul {
            list-style: none;
            padding: 0;
        }
        #custom-sidebar ul li {
            margin: 10px 0;
        }
        #wpbody-content {
            margin-right: 260px; /* Push main content */
        }
    </style>

    <div id="custom-sidebar" class="backup-list">
        <h3>Custom Code Backups</h3>
        <ul>
            <?php 
            if ($backup_enabled && !empty($backup_links_html)) {
                echo $backup_links_html;
            } else {
                echo '<li>No backups available.</li>';
            }
            ?>
        </ul>
    </div>
    <?php
}
add_action('admin_footer', 'custom_admin_sidebar');


function snn_download_custom_codes_backup() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized request');
    }

    $date = isset($_GET['backup_date']) ? sanitize_text_field($_GET['backup_date']) : '';
    if (empty($date)) {
        wp_die('Invalid backup date');
    }

    $backups = get_option('snn_custom_codes_backups', array());
    if (!isset($backups[$date])) {
        wp_die('No backup found for this date');
    }

    $backup_content = $backups[$date];

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="custom-codes-here-' . $date . '.php"');
    header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
    header('Content-Length: ' . strlen($backup_content));
    echo $backup_content;
    exit;
}
add_action('admin_post_download_custom_codes_backup', 'snn_download_custom_codes_backup');

?>
