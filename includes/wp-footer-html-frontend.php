<?php

// Function to add the settings field for custom HTML in the footer
function wp_footer_html_frontend_setting_field() {
    add_settings_field(
        'wp_footer_html_frontend',               // ID of the field
        'Custom HTML for WP Footer (Frontend Only)',             // Label of the field
        'wp_footer_html_frontend_callback',      // Callback function to render the field
        'snn-settings',                          // Page on which to display this field
        'snn_general_section'                    // Section to which this field belongs
    );
    register_setting('snn_settings_group', 'wp_footer_html_frontend'); // Register the settings group and option
}
add_action('admin_init', 'wp_footer_html_frontend_setting_field');

// Callback function to render the HTML textarea in the admin settings
function wp_footer_html_frontend_callback() {
    $html = get_option('wp_footer_html_frontend', ''); // Get the custom HTML, with a default fallback of empty string
    
    // Output a textarea for custom HTML input
    echo '<textarea name="wp_footer_html_frontend" id="wp_footer_html_frontend" class="large-text code" rows="10">' . esc_textarea($html) . '</textarea>';
    echo '<p>Enter custom HTML (including scripts or styles) for the front-end. This HTML will be output directly in the footer section.</p>';
}

// Print the custom HTML in the footer of the site
function wp_footer_print_html_in_footer() {
    $custom_html = get_option('wp_footer_html_frontend'); // Retrieve custom HTML from settings
    if (!empty($custom_html)) {
        echo $custom_html; // Echo directly without any wrapping
    }
}
add_action('wp_footer', 'wp_footer_print_html_in_footer', 1); // Add to wp_footer with high priority

?>
