<?php

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

    if (isset($input['revisions_limit'])) {
        $sanitized['revisions_limit'] = intval($input['revisions_limit']);
    }

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
        - gsap.min.js: The core GSAP library.<br>
        - ScrollTrigger.min.js: A GSAP plugin that enables scroll-based animations.<br>
        - gsap-data-animate.js: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.<br>
        - lottie.min.js and Lottie Element<br><br>
        Read <a href="https://github.com/sinanisler/snn-brx-child-theme/wiki/GSAP-ScrollTrigger-Animations" target="_blank">
            Documentation and Examples</a> for more details.
    </p>
    <?php
}

function snn_revisions_limit_callback() {
    $options = get_option('snn_other_settings');
    $value = isset($options['revisions_limit']) ? intval($options['revisions_limit']) : '';
    ?>
    <input type="number" name="snn_other_settings[revisions_limit]" value="<?php echo esc_attr($value); ?>" placeholder="500" min="0">
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
        <input type="checkbox" name="snn_other_settings[disable_comments]" value="1" <?php checked(1, isset($options['disable_comments']) ? $options['disable_comments'] : 0); ?> >
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
        Enter the HTML content for the custom dashboard metabox. You can include HTML tags for formatting.
    </p>
    <?php
}

function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', SNN_URL_ASSETS . '/js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', SNN_URL_ASSETS . '/js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', SNN_URL_ASSETS . '/js/gsap-data-animate.js?v0.03', array(), null, true);
        wp_enqueue_script('lottie-js', SNN_URL_ASSETS . '/js/lottie.min.js', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');

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
        // Add Thumbnail column to the default 'post' post type
        add_filter('manage_posts_columns', 'snn_add_thumbnail_column_header');
        add_action('manage_posts_custom_column', 'snn_display_thumbnail_column', 10, 2);

        // Add Thumbnail column to all registered custom post types (if they support thumbnails) except "product"
        $post_types = get_post_types(array('public' => true), 'names');
        foreach ($post_types as $post_type) {
            if ($post_type === 'post' || $post_type === 'product') {
                continue;
            }
            // Only add the thumbnail column if the post type supports featured images.
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
        if ($key === 'title') { // Insert our column after the title column.
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

function snn_display_custom_dashboard_metabox() {
    $options = get_option('snn_other_settings');
    $content = isset($options['dashboard_custom_metabox_content']) ? $options['dashboard_custom_metabox_content'] : '';

    $current_user = wp_get_current_user();
    $content = str_replace('{first_name}', esc_html($current_user->user_firstname), $content);
    $content = str_replace('{homepage_url}', esc_url(home_url('/')), $content);

    echo $content;
}

?>
