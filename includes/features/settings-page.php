<?php

function snn_add_menu_page() {
    $dynamic_title = get_option('snn_menu_title', __('SNN Settings', 'snn'));

    add_menu_page(
        $dynamic_title,
        $dynamic_title,
        'manage_options',
        'snn-settings',
        'snn_settings_page_callback',
        '',
        99
    );
}
add_action('admin_menu', 'snn_add_menu_page');

function snn_settings_page_callback() {
    $dynamic_title = get_option('snn_menu_title', __('SNN Settings', 'snn'));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($dynamic_title); ?> - <?php _e('Bricks Builder Child Theme Settings', 'snn'); ?></h1>
        
        <!-- Dashboard-like grid of big square buttons -->
        <div class="snn-dashboard-buttons">
            <?php
            // Only the features that are currently switched on get a button.
            foreach (snn_get_features() as $slug => $item) {
                if (!snn_feature_enabled($slug)) {
                    continue;
                }

                $url = admin_url('admin.php?page=' . $slug);
                ?>
                <a href="<?php echo esc_url($url); ?>" class="snn-dashboard-button">
                    <span class="dashicons <?php echo esc_attr($item['dashicon']); ?>"></span>
                    <span class="button-label"><?php echo esc_html($item['label']); ?></span>
                </a>
                <?php
            }
            ?>
        </div>
        <!-- End Dashboard Grid --> 

        <div style="max-width:1000px; margin-bottom:40px">
            <p style="line-height:24px !important;">
                <?php _e('This theme is designed to give you the tools and solutions for', 'snn'); ?>
                <a href="https://wordpress.org/" target="_blank"><?php _e('WordPress', 'snn'); ?></a>
                <?php _e('and', 'snn'); ?>
                <a href="https://bricksbuilder.io/" target="_blank"><?php _e('Bricks Builder', 'snn'); ?></a>.
                <?php _e('Post Types, Custom Fields, Taxonomies, SMTP Mail Setting, Custom Login Design,
                Math Chaptcha for Login/Register, Security Features, 404 Logs, 301 Redirects and some Block Editor Features.
                Everything is straightforward and ready to use.', 'snn'); ?>
                <br><br>
                <?php _e('Enjoy building your site.', 'snn'); ?><br><br>
    
                <a href="https://academy.bricksbuilder.io/topic/getting-started/" target="_blank"
                style="font-size: 16px; text-decoration:none; line-height:40px"><?php _e('Bricks Builder Docs ➤', 'snn'); ?></a><br>
    
                <a href="https://www.youtube.com/@bricksbuilder/videos" target="_blank"
                style="font-size: 16px; text-decoration:none; line-height:40px"><?php _e('Bricks Builder Videos ➤', 'snn'); ?></a><br>
            </p>
        </div>
    
        <details class="snn-customizations">
            <summary><?php _e('Customizations', 'snn'); ?></summary>

            <form method="post" action="options.php">
                <?php settings_fields('snn_settings_group'); ?>

                <h3><?php _e('White Label Name', 'snn'); ?></h3>
                <input type="text" name="snn_menu_title" value="<?php echo esc_attr($dynamic_title); ?>" class="regular-text">
                <p class="description"><?php _e('You can rename SNN Settings title.', 'snn'); ?></p>

                <h3><?php _e('Features', 'snn'); ?></h3>
                <p class="description">
                    <?php _e('Every feature is enabled by default. Unchecking one stops the theme from loading it at all, and removes its page from the dashboard above.', 'snn'); ?>
                </p>

                <p class="snn-feature-bulk">
                    <button type="button" class="button" id="snn-select-all"><?php _e('Select All', 'snn'); ?></button>
                    <button type="button" class="button" id="snn-deselect-all"><?php _e('Deselect All', 'snn'); ?></button>
                </p>

                <?php
                // Always post the key, otherwise WordPress skips the option when
                // every box is checked. Empty values are dropped on sanitize.
                ?>
                <input type="hidden" name="snn_disabled_features[]" value="">

                <div class="snn-feature-toggles">
                    <?php
                    $always_on = snn_get_always_on_features();

                    foreach (snn_get_features() as $slug => $item) {
                        if (in_array($slug, $always_on, true)) {
                            continue;
                        }

                        $enabled = snn_feature_enabled($slug);
                        ?>
                        <label class="snn-feature-toggle">
                            <input type="checkbox" class="snn-feature-checkbox" data-slug="<?php echo esc_attr($slug); ?>" <?php checked($enabled); ?>>
                            <input type="hidden" name="snn_disabled_features[]" value="<?php echo esc_attr($slug); ?>" <?php disabled($enabled); ?>>
                            <span class="dashicons <?php echo esc_attr($item['dashicon']); ?>"></span>
                            <span class="snn-feature-label"><?php echo esc_html($item['label']); ?></span>
                        </label>
                        <?php
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </details>
    </div>

    <script>
    (function () {
        var wrap = document.querySelector('.snn-feature-toggles');

        if (!wrap) {
            return;
        }

        // A disabled hidden input is not submitted, so "checked" == enabled ==
        // the slug never reaches snn_disabled_features.
        function sync(checkbox) {
            var hidden = checkbox.parentNode.querySelector('input[type="hidden"]');

            if (hidden) {
                hidden.disabled = checkbox.checked;
            }
        }

        var checkboxes = wrap.querySelectorAll('.snn-feature-checkbox');

        Array.prototype.forEach.call(checkboxes, function (checkbox) {
            sync(checkbox);
            checkbox.addEventListener('change', function () {
                sync(checkbox);
            });
        });

        function setAll(state) {
            Array.prototype.forEach.call(checkboxes, function (checkbox) {
                checkbox.checked = state;
                sync(checkbox);
            });
        }

        document.getElementById('snn-select-all').addEventListener('click', function () {
            setAll(true);
        });

        document.getElementById('snn-deselect-all').addEventListener('click', function () {
            setAll(false);
        });
    })();
    </script>
    
    <style>
        /* Dashboard buttons grid */
        .snn-dashboard-buttons {
            max-width:1000px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 40px;
        }
        .snn-dashboard-button {
            background: #fff;
            border: 1px solid #ccc;
            padding: 20px 10px;
            text-align: center;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, border-color 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: auto;
            text-decoration: none;
        }
        .snn-dashboard-button:hover {
            transform: scale(1.05);
            border-color: #0073aa;
        }
        .snn-dashboard-button .dashicons {
            width:auto;
            font-size: 32px;
            margin-bottom: 30px;
        }
        .snn-dashboard-button .button-label {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        /* Collapsed customizations panel */
        .snn-customizations {
            max-width: 1000px;
            border-radius: 4px;
            margin-bottom: 40px;
        }
        .snn-customizations > summary {
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }
        .snn-customizations[open] > summary {
            border-bottom: 1px solid #eee;
        }
        .snn-customizations > form {
            padding: 0 16px 10px;
        }
        .snn-feature-bulk {
            margin: 10px 0 16px;
        }
        .snn-feature-toggles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 6px 20px;
        }
        .snn-feature-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .snn-feature-toggle .dashicons {
            color: #777;
        }
        .snn-feature-toggle .snn-feature-label {
            font-size: 13px;
        }
        .wrap .snn-feature-toggle [type="checkbox"] {
            float: none;
            margin: 0 4px 0 0 !important;
        }

        /* Existing styles */
        .wrap .tt1 {
            width: 880px;
            height: 40px;
        }
        .wrap h1{
            margin-bottom:10px;
        }
        .wrap .style_css, .wrap .head-css, #wp_head_css_frontend, #wp_footer_html_frontend, #wp_head_html_frontend {
            width: 880px;
            height: 220px;
        }
        .wrap [type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            float: left;
            margin-right: 10px !important;
        }
        #snn_custom_css {
            width: 880px;
            height: 330px;
        }
    </style>
    <?php
}

function snn_register_settings() {
    register_setting('snn_settings_group', 'snn_menu_title');
    register_setting('snn_settings_group', 'snn_disabled_features', array(
        'type'              => 'array',
        'sanitize_callback' => 'snn_sanitize_disabled_features',
        'default'           => array(),
    ));
}
add_action('admin_init', 'snn_register_settings');

function mytheme_customize_register( $wp_customize ) {
    $wp_customize->add_setting( 'footer_custom_css', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ) );

    $wp_customize->add_control( 'footer_custom_css', array(
        'label'       => ' ',
        'section'     => 'custom_css', 
        'settings'    => 'footer_custom_css',
        'type'        => 'checkbox',
        'description' => ' ',
    ) );
}
add_action( 'customize_register', 'mytheme_customize_register' );

?>
