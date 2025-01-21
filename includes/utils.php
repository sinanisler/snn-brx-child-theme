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
<?php }
add_action('admin_head', 'snn_custom_css_utils');







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
