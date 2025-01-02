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
        'Enable GSAP and Lottie',
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
        'hide_element_icons',
        'Hide Element Icons on Bricks (Advanced)',
        'snn_hide_element_icons_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    // New Setting: Custom Admin Post Order
    add_settings_field(
        'custom_admin_post_order',
        'Custom Admin Post Types Order by Date',
        'snn_custom_admin_post_order_callback',
        'snn-other-settings',
        'snn_other_settings_section'
    );

    // === New Setting: Enable GitHub-Based Child Theme Updates ===
    add_settings_field(
        'enable_github_updates',
        'Enable GitHub-Based Child Theme Updates (Experimental)',
        'snn_enable_github_updates_callback',
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

    $sanitized['hide_element_icons'] = isset($input['hide_element_icons']) && $input['hide_element_icons'] ? 1 : 0;

    $sanitized['custom_admin_post_order'] = isset($input['custom_admin_post_order']) && $input['custom_admin_post_order'] ? 1 : 0;

    // Sanitize the new GitHub Updates setting
    $sanitized['enable_github_updates'] = isset($input['enable_github_updates']) && $input['enable_github_updates'] ? 1 : 0;

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

function snn_hide_element_icons_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <label>
        <input type="checkbox" name="snn_other_settings[hide_element_icons]" value="1" <?php checked(1, isset($options['hide_element_icons']) ? $options['hide_element_icons'] : 0); ?>>
        Hide Element Icons on Bricks Builder
    </label>
    <?php
}

function snn_custom_admin_post_order_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <label>
        <input type="checkbox" name="snn_other_settings[custom_admin_post_order]" value="1" <?php checked(1, isset($options['custom_admin_post_order']) ? $options['custom_admin_post_order'] : 0); ?> >
        Enable Custom Order by Date for Pages and Post Types
    </label>
    <?php
}

function snn_enable_github_updates_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <label>
        <input type="checkbox" name="snn_other_settings[enable_github_updates]" value="1" <?php checked(1, isset($options['enable_github_updates']) ? $options['enable_github_updates'] : 0); ?>>
        Enable GitHub-Based Child Theme Updates<br>
        When this feature is enabled, the theme can check if a new version has been released. <br>
        If a new version is available, updates can be performed from the <a href="<?php echo get_bloginfo('url'); ?>/wp-admin/themes.php">themes</a> page.
    </label>
    <?php
}

function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', get_stylesheet_directory_uri() . '/js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', get_stylesheet_directory_uri() . '/js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', get_stylesheet_directory_uri() . '/js/gsap-data-animate.js?v0.02', array(), null, true);
        wp_enqueue_script('lottie-js', get_stylesheet_directory_uri() . '/js/lottie.min.js', array(), null, true);
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

function snn_add_inline_css_if_bricks_run() {
    $options = get_option('snn_other_settings');
    if (isset($options['hide_element_icons']) && $options['hide_element_icons']) {
        if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
            echo '<style>
                #bricks-panel-elements .sortable-wrapper{
                    margin:0 0 5px;
                    padding-left:8px;
                    padding-right:8px;
                }
                #bricks-panel-elements-categories .category-title{
                    padding-left:8px;
                    padding-right:8px;
                }
                .bricks-add-element .element-icon {
                    display: none;
                }
                .bricks-add-element{
                
                }
                #bricks-panel-elements-categories .category-title{
                    line-height:0;
                    padding-top:10px;
                    padding-bottom:10px;
                }
                .bricks-add-element .element-label{
                    box-shadow:0 0 ;
                    font-size:14px;
                    padding: 0 3px;
                    line-height:30px;
                }
            </style>';
        }
    }
}
add_action('wp_head', 'snn_add_inline_css_if_bricks_run');

function snn_custom_admin_post_order( $wp_query ) {
    $options = get_option('snn_other_settings');
    if (isset($options['custom_admin_post_order']) && $options['custom_admin_post_order']) {
        if (is_admin()) {
            $post_type = isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : '';

            if ( 'post' == $post_type || is_array($post_type) || is_string($post_type) ) {
                if (!isset($_GET['orderby'])) {
                    $wp_query->set('orderby', 'date');
                    $wp_query->set('order', 'DESC'); 
                }
            }
        }
    }
}
add_filter('pre_get_posts', 'snn_custom_admin_post_order');

function snn_github_child_theme_updates() {
    $options = get_option('snn_other_settings');
    if (isset($options['enable_github_updates']) && $options['enable_github_updates']) {

        if ( !defined('CHILD_THEME_GITHUB_USER') ) {
            define( 'CHILD_THEME_GITHUB_USER', 'sinanisler' );             // Your GitHub username
        }
        if ( !defined('CHILD_THEME_GITHUB_REPO') ) {
            define( 'CHILD_THEME_GITHUB_REPO', 'snn-brx-child-theme' );    // Your child theme's GitHub repository name
        }
        if ( !defined('CHILD_THEME_GITHUB_BRANCH') ) {
            define( 'CHILD_THEME_GITHUB_BRANCH', 'main' );                 // Branch to track (usually 'main' or 'master')
        }
        if ( !defined('CHILD_THEME_TAG_PREFIX') ) {
            define( 'CHILD_THEME_TAG_PREFIX', 'v' );                       // Prefix for your tags (e.g., 'v' for 'v1.0.0')
        }

        if ( !defined('CHILD_THEME_RAW_STYLE_URL') ) {
            define( 'CHILD_THEME_RAW_STYLE_URL', 'https://raw.githubusercontent.com/' . CHILD_THEME_GITHUB_USER . '/' . CHILD_THEME_GITHUB_REPO . '/' . CHILD_THEME_GITHUB_BRANCH . '/style.css' );
        }
        if ( !defined('CHILD_THEME_ZIP_URL') ) {
            define( 'CHILD_THEME_ZIP_URL', 'https://github.com/' . CHILD_THEME_GITHUB_USER . '/' . CHILD_THEME_GITHUB_REPO . '/archive/refs/tags/' . CHILD_THEME_TAG_PREFIX . '%s.zip' );
        }
        if ( !defined('CHILD_THEME_RELEASES_URL') ) {
            define( 'CHILD_THEME_RELEASES_URL', 'https://github.com/' . CHILD_THEME_GITHUB_USER . '/' . CHILD_THEME_GITHUB_REPO . '/releases/tag/' . CHILD_THEME_TAG_PREFIX . '%s' );
        }

        function child_theme_github_update( $transient ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            $theme = wp_get_theme();
            $current_version = $theme->get( 'Version' );

            $response = wp_remote_get( CHILD_THEME_RAW_STYLE_URL, array(
                'headers' => array(
                    'User-Agent' => CHILD_THEME_GITHUB_USER . '-theme-update', // GitHub requires a User-Agent header
                ),
                'timeout' => 15,
            ) );

            if ( is_wp_error( $response ) ) {
                return $transient; 
            }

            $style_css = wp_remote_retrieve_body( $response );

            if ( preg_match( '/Version:\s*(.+)/i', $style_css, $matches ) ) {
                $latest_version = trim( $matches[1] );
            } else {
                return $transient; // If version not found, abort
            }

            if ( version_compare( $current_version, $latest_version, '<' ) ) {
                $zip_url = sprintf( CHILD_THEME_ZIP_URL, $latest_version );

                $transient->response[ $theme->get_stylesheet() ] = array(
                    'theme'       => $theme->get_stylesheet(),
                    'new_version' => $latest_version,
                    'url'         => sprintf( CHILD_THEME_RELEASES_URL, $latest_version ),
                    'package'     => $zip_url,
                );
            }

            return $transient;
        }
        add_filter( 'pre_set_site_transient_update_themes', 'child_theme_github_update' );

        function child_theme_github_info( $response, $action, $args ) {
            if ( 'theme_information' !== $action ) {
                return $response;
            }

            $theme = wp_get_theme();
            $current_version = $theme->get( 'Version' );

            $response_style = wp_remote_get( CHILD_THEME_RAW_STYLE_URL, array(
                'headers' => array(
                    'User-Agent' => CHILD_THEME_GITHUB_USER . '-theme-info', 
                ),
                'timeout' => 15,
            ) );

            if ( is_wp_error( $response_style ) ) {
                return $response; 
            }

            $style_css = wp_remote_retrieve_body( $response_style );

            if ( preg_match( '/Description:\s*(.+)/i', $style_css, $matches ) ) {
                $description = trim( $matches[1] );
            } else {
                $description = 'No description available.';
            }

            if ( preg_match( '/Version:\s*(.+)/i', $style_css, $matches ) ) {
                $version = trim( $matches[1] );
            } else {
                $version = '1.0.0';
            }

            $latest_release_url = sprintf( CHILD_THEME_RELEASES_URL, $version );

            if ( !is_object( $response ) ) {
                $response = new stdClass();
            }
            $response->sections = array(
                'description' => $description,
            );

            return $response;
        }
        add_filter( 'themes_api', 'child_theme_github_info', 20, 3 );

        if ( ! class_exists( 'Parsedown' ) ) {
            class Parsedown {
                public function text($text) {
                    $html = wpautop( esc_html( $text ) );
                    return $html;
                }
            }
        }

    }
}
add_action('after_setup_theme', 'snn_github_child_theme_updates');

// THEME UPDATE CHANGE LOG LINK CHANGE to RELEASES
function add_custom_js_to_admin_footer() {
    $screen = get_current_screen();
    if ($screen->base === 'themes') {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        const modal = document.querySelector('.theme-overlay');
                        if (modal && modal.style.display !== 'none') {
                            const links = modal.querySelectorAll('.open-plugin-details-modal');
                            links.forEach(link => {
                                if (link.tagName === 'A') {
                                    link.setAttribute('href', "https://github.com/sinanisler/snn-brx-child-theme/releases");
                                    link.setAttribute('target', '_blank');
                                    link.className = '';
                                }
                            });
                        }
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                document.body.addEventListener('click', function (event) {
                    if (event.target.classList.contains('close') || event.target.closest('.theme-overlay') === null) {
                        observer.disconnect();
                    }
                });
            });
        </script>
        <?php
    }
}
add_action('admin_footer', 'add_custom_js_to_admin_footer');
