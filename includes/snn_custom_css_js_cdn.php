<?php

// Functions to add settings fields and register CSS URLs
function snn_register_css_fields() {
    for ($i = 1; $i <= 4; $i++) {
        add_settings_field(
            'snn_css_url_' . $i,
            'Custom CSS CDN URL ' . $i,
            'snn_css_url_callback',
            'snn-settings',
            'snn_general_section',
            array('label_for' => 'snn_css_url_' . $i)
        );
        register_setting('snn_settings_group', 'snn_css_url_' . $i);
    }
}
add_action('admin_init', 'snn_register_css_fields');

// Callback to render CSS URL input fields
function snn_css_url_callback($args) {
    $css_url = get_option($args['label_for'], '');
    echo '<input type="url" name="' . $args['label_for'] . '" id="' . $args['label_for'] . '" value="' . esc_attr($css_url) . '" class="regular-text">';
}

// Load CSS in both the block editor and frontend
function snn_load_css_editor_and_footer() {
    for ($i = 1; $i <= 4; $i++) {
        $css_url = get_option('snn_css_url_' . $i);
        if (!empty($css_url)) {
            echo '<link rel="stylesheet" href="' . esc_url($css_url) . '">';
        }
    }
}
add_action('admin_head', 'snn_load_css_editor_and_footer');
add_action('wp_head', 'snn_load_css_editor_and_footer');
add_action('wp_footer', 'snn_load_css_editor_and_footer');

// Functions to add settings fields and register JavaScript URLs
function snn_register_js_fields() {
    for ($i = 1; $i <= 4; $i++) {
        add_settings_field(
            'snn_js_url_' . $i,
            'Custom JavaScript CDN URL ' . $i,
            'snn_js_url_callback',
            'snn-settings',
            'snn_general_section',
            array('label_for' => 'snn_js_url_' . $i)
        );
        register_setting('snn_settings_group', 'snn_js_url_' . $i);
    }
}
add_action('admin_init', 'snn_register_js_fields');

// Callback to render JavaScript URL input fields
function snn_js_url_callback($args) {
    $js_url = get_option($args['label_for'], '');
    echo '<input type="url" name="' . $args['label_for'] . '" id="' . $args['label_for'] . '" value="' . esc_attr($js_url) . '" class="regular-text">';
}

// Load JS in both the block editor and frontend footer
function snn_load_js_editor_and_footer() {
    for ($i = 1; $i <= 4; $i++) {
        $js_url = get_option('snn_js_url_' . $i);
        if (!empty($js_url)) {
            echo '<script src="' . esc_url($js_url) . '"></script>';
        }
    }
}
add_action('admin_head', 'snn_load_js_editor_and_footer');
add_action('wp_head', 'snn_load_js_editor_and_footer');
add_action('wp_footer', 'snn_load_js_editor_and_footer');

?>
