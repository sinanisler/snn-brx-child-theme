<?php

function snn_add_menu_page() {
    add_menu_page(
        'SNN Settings', // Page title
        'SNN Settings', // Menu title
        'manage_options', // Capability
        'snn-settings', // Menu slug
        'snn_settings_page_callback', // Function
        '', // Icon URL (optional)
        99 // Position (optional, set to a high number to make it the last item)
    );
}
add_action('admin_menu', 'snn_add_menu_page');

function snn_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>SNN Bricks Builder Child Theme Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_settings_group');
            do_settings_sections('snn-settings');
            submit_button();
            ?>
        </form>
    </div>

    <style>
        .wrap{
         }
        .tt1  {
            width: 880px;
            height: 40px;
        }
        .style_css , .head-css , #wp_head_css_frontend , #wp_footer_html_frontend , #wp_head_html_frontend {
            width: 880px;
            height: 220px;
        }
        [type="checkbox"] {
            width: 16px !important;
            height: 16px !important;
            float: left;
            margin-right: 10px !important;
        }
        #snn_custom_css{
            width: 880px;
            height:330px ;
        }
    </style>
    <?php
}

function snn_register_settings() {
    register_setting('snn_settings_group', 'snn_settings');

    add_settings_section(
        'snn_general_section',
        'Enable or Disable Settings Depending on Your Project Needs and Requirements',
        'snn_general_section_callback',
        'snn-settings'
    );
}
add_action('admin_init', 'snn_register_settings');

function snn_general_section_callback() {
    echo '<br><br>';
}











// Customizer Activator - Empty Setting
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

function mytheme_footer_custom_css() {
    // Empty
}
add_action( 'wp_footer', 'mytheme_footer_custom_css' );














?>
