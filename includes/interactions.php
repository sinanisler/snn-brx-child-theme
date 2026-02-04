<?php
// The Page for Interactions and Animations Settings
function snn_add_interactions_page() {
    add_submenu_page(
        'snn-settings',
        __('Interactions', 'snn'),
        __('Interactions', 'snn'),
        'manage_options',
        'snn-interactions',
        'snn_render_interactions_page'
    );
}
add_action('admin_menu', 'snn_add_interactions_page');

function snn_render_interactions_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Interactions & Animations', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_other_settings_group');
                do_settings_sections('snn-interactions');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_interactions_settings() {
    add_settings_section(
        'snn_interactions_section',
        __('Animation Settings', 'snn'),
        'snn_interactions_section_callback',
        'snn-interactions'
    );

    add_settings_field(
        'enqueue_gsap',
        __('Enable GSAP and GSAP Elements', 'snn'),
        'snn_enqueue_gsap_callback',
        'snn-interactions',
        'snn_interactions_section'
    );
}
add_action('admin_init', 'snn_register_interactions_settings');

function snn_interactions_section_callback() {
    echo '<p>' . esc_html__( 'Configure animation and interaction settings for your site below.', 'snn' ) . '</p>';
}

function snn_enqueue_gsap_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enqueue_gsap]" value="1" <?php checked(1, isset($options['enqueue_gsap']) ? $options['enqueue_gsap'] : 0); ?>>
    <p>
        <?php _e('Enabling this setting will enqueue the GSAP library and its associated scripts on your website.', 'snn'); ?><br>
        <?php _e('GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.', 'snn'); ?><br><br>
        - <?php _e('Ability to create GSAP animations with just data-animate attributes.', 'snn'); ?><br>
        - <?php _e('gsap.min.php: The core GSAP library.', 'snn'); ?><br>
        - <?php _e('ScrollTrigger.min.php: A GSAP plugin that enables scroll-based animations.', 'snn'); ?><br>
        - <?php _e('gsap-data-animate.php: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.', 'snn'); ?><br>
    </p>
    <?php
}

function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', SNN_URL_ASSETS . 'js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', SNN_URL_ASSETS . 'js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', SNN_URL_ASSETS . 'js/gsap-data-animate.js?v0.05', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');
add_action('admin_enqueue_scripts', 'snn_enqueue_gsap_scripts');




