<?php

function snn_add_other_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        'Other Settings',
        'Other Settings',
        'manage_options',
        'snn-other-settings',
        'snn_render_other_settings'
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
        'Enable GSAP',
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
        Enabling this setting will enqueue the GSAP library and its associated scripts on your website.  <br>
        GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.  <br><br>

        - Ability to craete gsap animations with just data-animate attibutes.<br>
        - gsap.min.js: The core GSAP library.  <br>
        - ScrollTrigger.min.js: A GSAP plugin that enables scroll-based animations.  <br>
        - gsap-data-animate.js: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.  
        <br><br>
        Read <a href="https://github.com/sinanisler/snn-brx-child-theme/wiki/GSAP-ScrollTrigger-Animations" target="_blank">
            Documentation and Examples</a> for more details.
    </p>
    <?php
}

function snn_revisions_limit_callback() {
    $options = get_option('snn_other_settings');
    $value = isset($options['revisions_limit']) ? intval($options['revisions_limit']) : '';
    ?>
    <input type="number" name="snn_other_settings[revisions_limit]" value="<?php echo esc_attr($value); ?>" placeholder="500">
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

function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', get_stylesheet_directory_uri() . '/js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', get_stylesheet_directory_uri() . '/js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', get_stylesheet_directory_uri() . '/js/gsap-data-animate.js?v0.01', array(), null, true);
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

?>
