<?php

// Add the Other Settings submenu page.
function snn_add_other_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __('Other Settings', 'snn'),
        __('Other Settings', 'snn'),
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
        <h1><?php _e('Other Settings', 'snn'); ?></h1>
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
        __('Other Settings', 'snn'),
        'snn_other_settings_section_callback',
        'snn-other-settings'
    );

    add_settings_field(
        'revisions_limit',
        __('Limit Post Revisions (Per Post)', 'snn'),
        'snn_revisions_limit_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'move_bricks_menu',
        __('Move Bricks Menu to End', 'snn'),
        'snn_move_bricks_menu_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'disable_comments',
        __('Require Login to Comment', 'snn'),
        'snn_disable_comments_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'disable_comments_completely',
        __('Disable Comments Completely', 'snn'),
        'snn_disable_comments_completely_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'enable_thumbnail_column',
        __('Enable Thumbnail Column in Post Tables', 'snn'),
        'snn_enable_thumbnail_column_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'disable_dashboard_widgets',
        __('Disable Default Dashboard Widgets', 'snn'),
        'snn_disable_dashboard_widgets_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    add_settings_field(
        'dashboard_custom_metabox_content',
        __('Dashboard Custom Metabox Content', 'snn'),
        'snn_dashboard_custom_metabox_content_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );
}
add_action('admin_init', 'snn_register_other_settings');

function snn_sanitize_other_settings($input) {
    $sanitized = array();

    if (isset($input['revisions_limit']) && $input['revisions_limit'] !== '') {
        $sanitized['revisions_limit'] = intval($input['revisions_limit']);
    } else {
        $sanitized['revisions_limit'] = '';
    }

    $sanitized['move_bricks_menu'] = isset($input['move_bricks_menu']) && $input['move_bricks_menu'] ? 1 : 0;
    $sanitized['disable_comments'] = isset($input['disable_comments']) && $input['disable_comments'] ? 1 : 0;
    $sanitized['disable_comments_completely'] = isset($input['disable_comments_completely']) && $input['disable_comments_completely'] ? 1 : 0;
    $sanitized['enable_thumbnail_column'] = isset($input['enable_thumbnail_column']) && $input['enable_thumbnail_column'] ? 1 : 0;
    $sanitized['disable_dashboard_widgets'] = isset($input['disable_dashboard_widgets']) && $input['disable_dashboard_widgets'] ? 1 : 0;

    if (isset($input['dashboard_custom_metabox_content'])) {
        $sanitized['dashboard_custom_metabox_content'] = $input['dashboard_custom_metabox_content'];
    } else {
        $sanitized['dashboard_custom_metabox_content'] = '';
    }

    // Update comment_registration option only when this setting is saved
    // Enable if either setting requires it
    if ($sanitized['disable_comments'] || $sanitized['disable_comments_completely']) {
        update_option('comment_registration', 1);
    } else {
        update_option('comment_registration', 0);
    }

    return $sanitized;
}

function snn_other_settings_section_callback() {
    echo '<p>' . esc_html__( 'Configure additional settings for your site below.', 'snn' ) . '</p>';
}

function snn_revisions_limit_callback() {
    $options = get_option('snn_other_settings');
    $value = (isset($options['revisions_limit']) && $options['revisions_limit'] !== '' && intval($options['revisions_limit']) > 0)
             ? intval($options['revisions_limit'])
             : '';
    ?>
    <input type="number" name="snn_other_settings[revisions_limit]" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr__( '9999', 'snn' ); ?>">
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
        <?php _e('Require users to be registered and logged in to comment', 'snn'); ?>
    </label>
    <p>
        <?php _e('Enabling this setting will allow only logged-in users to comment. Guest/public commenting will be disabled.', 'snn'); ?>
    </p>
    <?php
}

function snn_disable_comments_completely_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <label>
        <input type="checkbox" name="snn_other_settings[disable_comments_completely]" value="1" <?php checked(1, isset($options['disable_comments_completely']) ? $options['disable_comments_completely'] : 0); ?>>
        <?php _e('Disable all comments site-wide', 'snn'); ?>
    </label>
    <p>
        <?php _e('Enabling this setting will completely disable all comments on the site. No one will be able to comment, even logged-in users.', 'snn'); ?>
    </p>
    <?php
}

function snn_enable_thumbnail_column_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enable_thumbnail_column]" value="1" <?php checked(1, isset($options['enable_thumbnail_column']) ? $options['enable_thumbnail_column'] : 0); ?>>
    <p>
        <?php _e('Enabling this setting will add a "Thumbnail" column to your post tables in the admin dashboard.', 'snn'); ?><br>
        <?php _e('This allows you to see the featured image of each post directly in the list view.', 'snn'); ?>
    </p>
    <?php
}

function snn_disable_dashboard_widgets_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[disable_dashboard_widgets]" value="1" <?php checked(1, isset($options['disable_dashboard_widgets']) ? $options['disable_dashboard_widgets'] : 0); ?>>
    <p>
        <?php _e('Enabling this setting will remove several default dashboard widgets from the WordPress admin dashboard.', 'snn'); ?><br>
        <?php _e('This helps in decluttering the dashboard and focusing on the essential information.', 'snn'); ?>
    </p>
    <?php
}

function snn_dashboard_custom_metabox_content_callback() {
    $options = get_option('snn_other_settings');
    $content = isset($options['dashboard_custom_metabox_content']) ? $options['dashboard_custom_metabox_content'] : '';
    ?>
    <?php
        wp_editor($content, 'dashboard_custom_metabox_content', array(
            'textarea_name' => 'snn_other_settings[dashboard_custom_metabox_content]',
            'textarea_rows' => 10,
            'tinymce'       => true,
        ));
    ?>
    <p>
        <?php _e('Enter the HTML content for the custom dashboard metabox.', 'snn'); ?>
        <br><?php _e('You can include HTML tags for formatting, and now you can also paste shortcodes which will be executed.', 'snn'); ?>
        <style>
            #wp-dashboard_custom_metabox_content-wrap{max-width:600px }
        </style>
    </p>
    <?php
}

function snn_limit_post_revisions($num, $post) {
    $options = get_option('snn_other_settings');
    if (isset($options['revisions_limit']) && intval($options['revisions_limit']) > 0) {
        return intval($options['revisions_limit']);
    }
    return $num;
}
add_filter('wp_revisions_to_keep', 'snn_limit_post_revisions', 10, 2);

function snn_custom_menu_order($menu_ord) {
    $options = get_option('snn_other_settings');
    if (isset($options['move_bricks_menu']) && $options['move_bricks_menu']) {
        if (!$menu_ord) {
            return true;
        }
        $bricks_menu = null;
        foreach ($menu_ord as $i => $item) {
            if ($item === 'bricks') {
                $bricks_menu = $item;
                unset($menu_ord[$i]);
                break;
            }
        }
        if ($bricks_menu) {
            $target_index = 99;
            $menu_ord = array_values($menu_ord);
            if (count($menu_ord) >= $target_index) {
                array_splice($menu_ord, $target_index, 0, array($bricks_menu));
            } else {
                $menu_ord[] = $bricks_menu;
            }
        }
        return $menu_ord;
    }
    return $menu_ord;
}
add_filter('menu_order', 'snn_custom_menu_order');
add_filter('custom_menu_order', '__return_true');

function snn_hide_comments_section() {
    $options = get_option('snn_other_settings');
    
    // Completely disable comments if the complete disable option is checked
    if (isset($options['disable_comments_completely']) && $options['disable_comments_completely']) {
        echo '<style>#menu-comments { display: none !important; }</style>';
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
    }
    // Note: The 'disable_comments' setting (require login) is handled by WordPress
    // via the comment_registration option set in the sanitize function
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
            $new_columns['post_thumbnail'] = __('Thumbnail', 'snn');
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
            echo esc_html__('--', 'snn');
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
            __('Welcome', 'snn'),
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
 */
function snn_display_custom_dashboard_metabox() {
    $options = get_option('snn_other_settings');
    $content = isset($options['dashboard_custom_metabox_content']) ? $options['dashboard_custom_metabox_content'] : '';

    $current_user = wp_get_current_user();
    $content = str_replace('{first_name}', esc_html($current_user->user_firstname), $content);
    $content = str_replace('{homepage_url}', esc_url(home_url('/')), $content);

    if (preg_match('/\[[^\]]+\]/', $content)) {
        remove_action('wp_head', 'wp_admin_bar_header');

        ob_start();
        do_action('wp_head');
        $frontend_head = ob_get_clean();

        ob_start();
        wp_print_styles();
        wp_print_scripts();
        $extra_resources = ob_get_clean();

        if (false === strpos($frontend_head, "bricks-frontend-inline-inline-css")) {
            ob_start();
            wp_print_styles('bricks-frontend-inline-inline-css');
            $bricks_inline_css = ob_get_clean();
            $frontend_head .= $bricks_inline_css;
            echo '
            <style>
                .postbox-container { width: 100% !important; }
                .postbox-header, #screen-meta-links { display: none; }
                .inside { margin: 0 !important; padding: 0 !important; }
                #wpcontent { padding-left: 0 !important; }
                .wrap { margin: 0 !important; width: 100% !important; display: flex !important; flex-direction: column; overflow-x: hidden; }
                #dashboard-widgets { padding: 0 !important; }
                .wrap h1:first-of-type { display: none; }
                .postbox { border: none !important; }
            </style>
            ';
        }

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
