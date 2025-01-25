<?php 
// WP-Admin Backend Custom JS and CSS in <head>
function snn_custom_css_utils() { ?>
    <style>
    #toplevel_page_snn-settings li a{
        line-height:1.1 !important;
    }
    #toplevel_page_snn-settings li a:hover{
        font-weight:700
    }


    </style>


    <?php if (isset($_GET['bricks'])) { ?>
        <style>
        [data-control-key="position_start_horizontal"],
        [data-control-key="position_start_vertical"] {
            float:left !important;
            padding:10px;
        }
        </style>
    <?php }?>

<?php }
add_action('admin_head', 'snn_custom_css_utils');


// Add custom CSS to the footer based on query parameter
function snn_custom_css_utils_wp_footer() {
    if (isset($_GET['bricks'])) { ?>
    <style>
        [data-controlkey="gsap_animations"] .repeater-item-inner,
        [data-controlkey="gsap_animations"] .repeater-item-inner {
            float: left !important;
            width: 50%;
        }
        [data-controlkey="gsap_animations"] .control-inline{
            gap:2px !important
        }
        [data-controlkey="gsap_animations"] .bricks-sortable-item .control{
            margin-left:8px !important;
            margin-right:8px !important;
        }
        [data-controlkey="gsap_animations"] .sortable-title{
            padding-left:8px !important;
        }
    </style>
    <?php
    }
}

// Hook the function to the WordPress 'wp_footer' action
add_action('wp_footer', 'snn_custom_css_utils_wp_footer');














// Enable JSON lottie file uploads in the Media Library
function allow_json_upload($mimes) {
    // Add .json to the list of allowed mime types
    $mimes['json'] = 'application/json';
    return $mimes;
}
add_filter('upload_mimes', 'allow_json_upload');

// Fix MIME type check for JSON uploads (for security and compatibility)
function check_json_filetype($data, $file, $filename, $mimes) {
    // Get the file extension
    $filetype = wp_check_filetype($filename, $mimes);
    
    // If the extension is JSON, update the type and ext
    if ($filetype['ext'] === 'json') {
        $data['ext'] = 'json';
        $data['type'] = 'application/json';
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'check_json_filetype', 10, 4);

// Allow JSON files to bypass the upload restriction in WordPress
function allow_unfiltered_json_upload($file) {
    // Check for JSON file type
    if ($file['type'] === 'application/json') {
        // No error for JSON files
        $file['error'] = false;
    }
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'allow_unfiltered_json_upload');

// Display JSON files properly in the Media Library (optional)
function enable_json_preview_in_media_library($response, $attachment, $meta) {
    // Ensure the file is a JSON file
    if ($response['mime'] === 'application/json') {
        // Provide a basic preview message for JSON files
        $response['uploaded_filename'] = basename(get_attached_file($attachment->ID));
        $response['url'] = wp_get_attachment_url($attachment->ID);
    }
    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'enable_json_preview_in_media_library', 10, 3);
