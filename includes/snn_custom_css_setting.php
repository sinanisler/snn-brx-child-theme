<?php

// Function to add the settings field for custom CSS
function snn_custom_css_setting_field() {
    add_settings_field(
        'snn_custom_css',                 // ID of the field
        'Custom CSS for Bricks and Block Editor',    // Label of the field
        'snn_custom_css_callback',        // Callback function to render the field
        'snn-settings',                   // Page on which to display this field
        'snn_general_section'             // Section to which this field belongs
    );
    register_setting('snn_settings_group', 'snn_custom_css'); // Corrected the settings group name
}
add_action('admin_init', 'snn_custom_css_setting_field');

// Callback function to render the CSS textarea in the admin settings
function snn_custom_css_callback() {
    $css = get_option('snn_custom_css', ''); // Get the custom CSS, with a default fallback of empty string
    
    // Output a textarea for custom CSS input
    echo '<textarea name="snn_custom_css" id="snn_custom_css" class="large-text code" rows="10">' . esc_textarea($css) . '</textarea>';
    echo '<p>Enter custom CSS for the block editor and front-end. This CSS will be applied site-wide.</p>';
    echo '<a href="https://github.com/uhub/awesome-css" target="_blank">Some Native CSS Libraries</a> stay away from .js required ones.';
}




// Add custom CSS to the Gutenberg block editor settings
function snn_custom_gutenberg_styles($editor_settings, $editor_context) {
    $custom_css = get_option('snn_custom_css'); // Retrieve custom CSS from settings
    if (!empty($custom_css)) {
        $editor_settings['styles'][] = array('css' => $custom_css);
    }
    return $editor_settings;
}
add_filter('block_editor_settings_all', 'snn_custom_gutenberg_styles', 10, 2);

// Print the custom CSS in the footer of the site
function snn_print_styles_in_footer() {
    $custom_css = get_option('snn_custom_css'); // Retrieve custom CSS from settings
    if (!empty($custom_css)) {
        echo '<style>' . $custom_css . '</style>';
    }
}
add_action('wp_footer', 'snn_print_styles_in_footer');

?>
