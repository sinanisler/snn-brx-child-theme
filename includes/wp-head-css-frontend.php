<?php

// Function to add the settings field for custom CSS in the head
function wp_head_css_frontend_setting_field() {
    add_settings_field(
        'wp_head_css_frontend',                  // ID of the field
        'Custom CSS for WP Head (Frontend Only)',                // Label of the field
        'wp_head_css_frontend_callback',         // Callback function to render the field
        'snn-settings',                          // Page on which to display this field
        'snn_general_section'                    // Section to which this field belongs
    );
    register_setting('snn_settings_group', 'wp_head_css_frontend'); // Register the settings group and option
}
add_action('admin_init', 'wp_head_css_frontend_setting_field');

// Callback function to render the CSS textarea in the admin settings
function wp_head_css_frontend_callback() {
    $css = get_option('wp_head_css_frontend', ''); // Get the custom CSS, with a default fallback of empty string
    
    // Output a textarea for custom CSS input
    echo '<textarea name="wp_head_css_frontend" id="wp_head_css_frontend" class="large-text code" rows="10">' . esc_textarea($css) . '</textarea>';
    echo '<p>Enter custom CSS for the front-end. This CSS will be applied site-wide and will appear in the head section.</p>';
    echo '<a href="https://patorjk.com/software/taag/#p=display&f=ANSI%20Regular&t=Hello%20World" target="_blank">Text to ASCI ART</a>.';
}

// Print the custom CSS in the head of the site
function wp_head_print_styles_in_head() {
    $custom_css = get_option('wp_head_css_frontend'); // Retrieve custom CSS from settings
    if (!empty($custom_css)) {
        echo '<style>' . $custom_css . '</style>';
    }
}
add_action('wp_head', 'wp_head_print_styles_in_head', 1); // Add to wp_head with high priority

?>
