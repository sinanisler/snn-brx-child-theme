<?php

// Function to add the settings field for custom HTML in the head
function wp_head_html_frontend_setting_field() {
    add_settings_field(
        'wp_head_html_frontend',                  // ID of the field
        'Custom HTML for WP Head (Frontend Only)', // Label of the field
        'wp_head_html_frontend_callback',         // Callback function to render the field
        'snn-settings',                           // Page on which to display this field
        'snn_general_section'                     // Section to which this field belongs
    );
    register_setting('snn_settings_group', 'wp_head_html_frontend');
}
add_action('admin_init', 'wp_head_html_frontend_setting_field');

// Callback function to render the HTML textarea in the admin settings
function wp_head_html_frontend_callback() {
    $html = get_option('wp_head_html_frontend', ''); // Get the custom HTML, with a default fallback of empty string
    
    // Output a textarea for custom HTML input
    echo '<textarea name="wp_head_html_frontend" id="wp_head_html_frontend" class="large-text code" rows="10">' . esc_textarea($html) . '</textarea>';
    echo '<p>Enter custom HTML for the front-end. This HTML will be applied site-wide and will appear in the head section.</p>';
    echo '<a href="https://patorjk.com/software/taag/#p=display&f=ANSI%20Regular&t=Hello%20World" target="_blank">Text to ASCI ART</a>. If you want to add this ASCII art, don\'t forget to comment it like this: <code>&lt;!-- ASCII art --&gt;</code>.';
}

// Print the custom HTML in the head of the site
function wp_head_print_html_in_head() {
    $custom_html = get_option('wp_head_html_frontend'); // Retrieve custom HTML from settings
    if (!empty($custom_html)) {
        echo $custom_html;
    }
}
add_action('wp_head', 'wp_head_print_html_in_head', 1); // Add to wp_head with high priority

?>
