<?php

// 1. Add "Other Settings" Submenu Under "snn-settings"
function snn_add_other_settings_submenu() {
    add_submenu_page(
        'snn-settings',             // Parent slug
        'Other Settings',           // Page title
        'Other Settings',           // Menu title
        'manage_options',           // Capability
        'snn-other-settings',       // Menu slug
        'snn_render_other_settings' // Callback function
    );
}
add_action('admin_menu', 'snn_add_other_settings_submenu');

// 2. Render the "Other Settings" Page
function snn_render_other_settings() {
    ?>
    <div class="wrap">
        <h1>Other Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_other_settings_group'); // Must match register_setting
                do_settings_sections('snn-other-settings');    // Must match add_settings_section
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 3. Register Settings, Sections, and Fields
function snn_register_other_settings() {
    // Register the settings
    register_setting(
        'snn_other_settings_group', // Option group, must match settings_fields
        'snn_other_settings',       // Option name
        'snn_sanitize_other_settings' // Sanitize callback
    );

    // Add a settings section
    add_settings_section(
        'snn_other_settings_section',          // ID
        'Other Settings',                      // Title
        'snn_other_settings_section_callback', // Callback
        'snn-other-settings'                   // Page, must match do_settings_sections
    );

    // Add Enable GSAP field
    add_settings_field(
        'enqueue_gsap',                       // ID
        'Enable GSAP',                        // Title
        'snn_enqueue_gsap_callback',          // Callback
        'snn-other-settings',                 // Page
        'snn_other_settings_section'          // Section
    );

    // Add Limit Post Revisions field
    add_settings_field(
        'revisions_limit',                    // ID
        'Limit Post Revisions',               // Title
        'snn_revisions_limit_callback',       // Callback
        'snn-other-settings',                 // Page
        'snn_other_settings_section'          // Section
    );

    // Add Auto Update Bricks Theme field
    add_settings_field(
        'auto_update_bricks',                 // ID
        'Auto Update Bricks Theme (Main Theme Only)', // Title
        'snn_auto_update_bricks_callback',    // Callback
        'snn-other-settings',                 // Page
        'snn_other_settings_section'          // Section
    );

    // Add Move Bricks Menu to End field
    add_settings_field(
        'move_bricks_menu',                   // ID
        'Move Bricks Menu to End',            // Title
        'snn_move_bricks_menu_callback',      // Callback
        'snn-other-settings',                 // Page
        'snn_other_settings_section'          // Section
    );
}
add_action('admin_init', 'snn_register_other_settings');

// 4. Sanitize Settings Input
function snn_sanitize_other_settings($input) {
    $sanitized = array();

    // Sanitize Enable GSAP
    $sanitized['enqueue_gsap'] = isset($input['enqueue_gsap']) && $input['enqueue_gsap'] ? 1 : 0;

    // Sanitize Revisions Limit
    if (isset($input['revisions_limit'])) {
        $sanitized['revisions_limit'] = intval($input['revisions_limit']);
    }

    // Sanitize Auto Update Bricks Theme
    $sanitized['auto_update_bricks'] = isset($input['auto_update_bricks']) && $input['auto_update_bricks'] ? 1 : 0;

    // Sanitize Move Bricks Menu to End
    $sanitized['move_bricks_menu'] = isset($input['move_bricks_menu']) && $input['move_bricks_menu'] ? 1 : 0;

    return $sanitized;
}

// 5. Section Callback
function snn_other_settings_section_callback() {
    echo '<p>Configure additional settings for your site below.</p>';
}

// 6. Callback Functions for Each Setting Field

// Enable GSAP Callback
function snn_enqueue_gsap_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enqueue_gsap]" value="1" <?php checked(1, isset($options['enqueue_gsap']) ? $options['enqueue_gsap'] : 0); ?>>
    <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website.</p>
    <ul>
        <li><code>gsap.min.js</code>: The core GSAP library.</li>
        <li><code>ScrollTrigger.min.js</code>: A GSAP plugin that enables scroll-based animations.</li>
        <li><code>gsap-data-animate.js</code>: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.</li>
        <li>Read <a href="https://github.com/sinanisler/snn-brx-child-theme/wiki/GSAP-ScrollTrigger-Animations" target="_blank">Documentation</a> for more details and examples.</li>
    </ul>
    <?php
}

// Limit Post Revisions Callback
function snn_revisions_limit_callback() {
    $options = get_option('snn_other_settings');
    $value = isset($options['revisions_limit']) ? intval($options['revisions_limit']) : '';
    ?>
    <input type="number" name="snn_other_settings[revisions_limit]" value="<?php echo esc_attr($value); ?>" placeholder="500">
    <p>Set the maximum number of revisions to keep for each post. Default is Unlimited.</p>
    <?php
}

// Auto Update Bricks Theme Callback
function snn_auto_update_bricks_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[auto_update_bricks]" value="1" <?php checked(1, isset($options['auto_update_bricks']) ? $options['auto_update_bricks'] : 0); ?>>
    <p>Enabling this setting will automatically update the Bricks theme whenever a new version is available.</p>
    <?php
}

// Move Bricks Menu to End Callback
function snn_move_bricks_menu_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[move_bricks_menu]" value="1" <?php checked(1, isset($options['move_bricks_menu']) ? $options['move_bricks_menu'] : 0); ?>>
    <p>Enabling this setting will move the Bricks menu item to the end of the WordPress admin menu.</p>
    <?php
}

// 7. Enqueue GSAP Scripts Based on Settings
function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', get_stylesheet_directory_uri() . '/js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', get_stylesheet_directory_uri() . '/js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        // wp_enqueue_script('gsap-tm-js', get_stylesheet_directory_uri() . '/js/TweenMax.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', get_stylesheet_directory_uri() . '/js/gsap-data-animate.js?v0.01', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');

// 8. Limit Post Revisions Based on Settings
function snn_limit_post_revisions($num, $post) {
    $options = get_option('snn_other_settings');
    if (isset($options['revisions_limit']) && intval($options['revisions_limit']) > 0) {
        return intval($options['revisions_limit']);
    }
    return $num;
}
add_filter('wp_revisions_to_keep', 'snn_limit_post_revisions', 10, 2);

// 9. Auto Update Bricks Theme Based on Settings
function snn_auto_update_bricks_theme($update, $item) {
    $options = get_option('snn_other_settings');
    if (isset($options['auto_update_bricks']) && $options['auto_update_bricks'] && isset($item->theme) && $item->theme === 'bricks') {
        return true;
    }
    return $update;
}
add_filter('auto_update_theme', 'snn_auto_update_bricks_theme', 10, 2);

// 10. Custom Menu Order Based on Settings
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
