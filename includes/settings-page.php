<?php

function snn_add_menu_page() {
    $dynamic_title = get_option('snn_menu_title', __('SNN Settings', 'snn'));

    add_menu_page(
        __('SNN Settings', 'snn'),
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
            $menu_items = array(
                array('slug' => 'snn-settings',               'label' => $dynamic_title.' '.__('Settings', 'snn'),            'dashicon' => 'dashicons-admin-home'),
                array('slug' => 'editor-settings',            'label' => __('Editor Settings', 'snn'),                        'dashicon' => 'dashicons-edit'),
                array('slug' => 'snn-security',               'label' => __('Security Settings', 'snn'),                      'dashicon' => 'dashicons-shield'),
                array('slug' => 'snn-custom-post-types',      'label' => __('Post Types', 'snn'),                             'dashicon' => 'dashicons-admin-post'),
                array('slug' => 'snn-custom-fields',          'label' => __('Custom Fields', 'snn'),                          'dashicon' => 'dashicons-admin-page'),
                array('slug' => 'snn-taxonomies',             'label' => __('Taxonomies', 'snn'),                             'dashicon' => 'dashicons-category'),
                array('slug' => 'snn-login-settings',         'label' => __('Login Settings', 'snn'),                         'dashicon' => 'dashicons-admin-users'),
                array('slug' => 'snn-404-logs',               'label' => __('404 Logs', 'snn'),                               'dashicon' => 'dashicons-warning'),
                array('slug' => 'snn-301-redirects',          'label' => __('301 Redirects', 'snn'),                          'dashicon' => 'dashicons-share'),
                array('slug' => 'snn-smtp-settings',          'label' => __('Mail SMTP Settings', 'snn'),                     'dashicon' => 'dashicons-email'),
                array('slug' => 'snn-mail-logs',              'label' => __('Mail Logs', 'snn'),                              'dashicon' => 'dashicons-email-alt'),
                array('slug' => 'snn-role-management',        'label' => __('Role Manager', 'snn'),                           'dashicon' => 'dashicons-admin-users'),
                array('slug' => 'snn-cookie-settings',        'label' => __('Cookie Settings', 'snn'),                        'dashicon' => 'dashicons-admin-site'),
                array('slug' => 'snn-accessibility-settings', 'label' => __('Accessibility Settings', 'snn'),                 'dashicon' => 'dashicons-universal-access'),
                array('slug' => 'snn-search-logs',            'label' => __('Search Logs', 'snn'),                            'dashicon' => 'dashicons-search'),
                array('slug' => 'snn-ai-settings',            'label' => __('AI Settings', 'snn'),                            'dashicon' => 'dashicons-nametag'),
                array('slug' => 'snn-block-editor-settings',  'label' => __('Block Editor Settings', 'snn'),                  'dashicon' => 'dashicons-admin-customizer'),
                array('slug' => 'snn-media-settings',         'label' => __('Media Settings', 'snn'),                         'dashicon' => 'dashicons-format-image'),
                array('slug' => 'snn-activity-log',           'label' => __('Activity Logs', 'snn'),                          'dashicon' => 'dashicons-text'),
                array('slug' => 'snn-custom-codes-snippets',  'label' => __('Code Snippets', 'snn'),                          'dashicon' => 'dashicons-editor-code'),
            );
            
            foreach ($menu_items as $item) {
                $url = admin_url('admin.php?page=' . $item['slug']);
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

        <div style="max-width:660px; margin-bottom:40px">
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
    
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_settings_group');
            do_settings_sections('snn-settings');
            submit_button();
            ?>
        </form>
    </div>
    
    <style>
        /* Dashboard buttons grid */
        .snn-dashboard-buttons {
            max-width:1000px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    add_settings_section(
        'snn_general_section',
        __('General Setting', 'snn'),
        'snn_general_section_callback',
        'snn-settings'
    );

    add_settings_field(
        'snn_menu_title_field',
        __('White Label Name', 'snn'),
        'snn_menu_title_field_callback',
        'snn-settings',
        'snn_general_section'
    );
}
add_action('admin_init', 'snn_register_settings');

function snn_general_section_callback() {
    echo '<p>' . esc_html__('General setting for the SNN menu page.', 'snn') . '</p>';
}

function snn_menu_title_field_callback() {
    $menu_title = get_option('snn_menu_title', __('SNN Settings', 'snn'));
    echo '<input type="text" name="snn_menu_title" value="' . esc_attr($menu_title) . '" class="regular-text">';
    echo '<p>' . esc_html__('You can rename SNN Settings title.', 'snn') . '</p>';
}

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
