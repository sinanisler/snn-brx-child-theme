<?php

function snn_add_menu_page() {
    
    $dynamic_title = get_option('snn_menu_title', 'SNN Settings');

    add_menu_page(
        'SNN Settings', 
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
    $dynamic_title = get_option('snn_menu_title', 'SNN Settings');
    ?>
    <div class="wrap">
        <h1><?php echo $dynamic_title; ?> - Bricks Builder Child Theme Settings</h1>
        <div style="max-width:660px; margin-bottom:80px ">
        <p  style="font-size: ; line-height:24px !important;  ">
            SNN-BRX Child theme is designed to give you the tools and solutions for <a href="https://bricksbuilder.io/" target="_blank">Bricks Builder</a>.  
            Post Types, Custom Fields, Taxonomies, SMTP Mail Setting, Custom Login Design, 
            Math Chaptcha for Login/Register, Security Features, 404 Logs, 301 Redirects and some Block Editor Features.
            Everything is straightforward and ready to use. <br><br>
            Enjoy building your site.<br><br>

            <a href="https://academy.bricksbuilder.io/" target="_blank" 
            style="font-size: 16px; text-decoration:none; line-height:40px " >Bricks Builder Docs ➤</a><br>

            <a href="https://github.com/sinanisler/snn-brx-child-theme/wiki" target="_blank" 
            style="font-size: 16px; text-decoration:none; line-height:40px " >Settings Docs ➤</a><br>

            <a href="https://github.com/sinanisler/snn-brx-child-theme/issues" target="_blank" 
            style="font-size: 16px; text-decoration:none; line-height:40px " >BUG Report ➤</a><br>


            <a href="https://github.com/sinanisler/snn-brx-child-theme/discussions" target="_blank" 
            style="font-size: 16px; text-decoration:none; line-height:40px " >Discussions ➤</a>
            

            
             
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
        .wrap {
        }
        .tt1 {
            width: 880px;
            height: 40px;
        }
        .style_css, .head-css, #wp_head_css_frontend, #wp_footer_html_frontend, #wp_head_html_frontend {
            width: 880px;
            height: 220px;
        }
        [type="checkbox"] {
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
        'General Setting',
        'snn_general_section_callback',
        'snn-settings'
    );

    add_settings_field(
        'snn_menu_title_field',
        'White Label Name',
        'snn_menu_title_field_callback',
        'snn-settings',
        'snn_general_section'
    );
}
add_action('admin_init', 'snn_register_settings');

function snn_general_section_callback() {
    echo '<p>General setting for the SNN menu page.</p>';
}

function snn_menu_title_field_callback() {
    $menu_title = get_option('snn_menu_title', 'SNN Settings');
    echo '<input type="text" name="snn_menu_title" value="' . esc_attr($menu_title) . '" class="regular-text">';
    echo '<p>You can rename SNN Settings title.</p>';
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
