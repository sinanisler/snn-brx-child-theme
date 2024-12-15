<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
exit;
}

// Register the submenu for Block Theme JSON
function snn_add_block_theme_json_submenu() {
add_submenu_page(
'snn-settings', // Parent slug (SNN Settings menu)
'Block Theme JSON', // Page title
'Block Theme JSON', // Menu title
'manage_options', // Capability
'snn-block-theme-json', // Menu slug
'snn_block_theme_json_page_callback' // Function
);
}
add_action('admin_menu', 'snn_add_block_theme_json_submenu');

// Callback function for displaying the settings page
function snn_block_theme_json_page_callback() {
// Get the path to the child theme's theme.json file
$theme_json_path = get_stylesheet_directory() . '/theme.json';

// Check if the theme.json file exists
if (!file_exists($theme_json_path)) {
echo '<div class="notice notice-error"><p>The <code>theme.json</code> file does not exist in the current child theme.</p></div>';
return;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Verify nonce
if (!isset($_POST['snn_theme_json_nonce']) || !wp_verify_nonce($_POST['snn_theme_json_nonce'], 'snn_theme_json_edit')) {
    wp_die(__('Invalid nonce. Please try again.'));
}

// Ensure the user has proper permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Sanitize and validate JSON content
$new_content = wp_unslash($_POST['theme_json_content']);
$decoded_json = json_decode($new_content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo '<div class="notice notice-error"><p>Invalid JSON format. Please fix the errors and try again.</p></div>';
} else {
    // Attempt to write the content to the theme.json file
    if (file_put_contents($theme_json_path, $new_content) === false) {
        echo '<div class="notice notice-error"><p>Failed to save the <code>theme.json</code> file. Please check file permissions.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><code>theme.json</code> file updated successfully!</p></div>';
    }
}
}

// Get the current content of the theme.json file
$current_content = file_get_contents($theme_json_path);
if ($current_content === false) {
echo '<div class="notice notice-error"><p>Unable to read the <code>theme.json</code> file. Please check file permissions.</p></div>';
return;
}

// Beautify the JSON content for easier editing
$current_content = json_encode(json_decode($current_content, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

<div class="wrap">
<h1>Block Theme JSON Editor</h1>
<p>
    The "Block Theme JSON Editor" is a WordPress admin tool that enables you to edit the theme.json file of a child theme directly from the dashboard, 
    <br>providing a secure and user-friendly interface for managing theme configurations.
</p>
<form method="post">
    <?php wp_nonce_field('snn_theme_json_edit', 'snn_theme_json_nonce'); ?>

    <textarea name="theme_json_content" style="width: 100%; height: 600px; font-family: monospace;">
<?php echo esc_textarea($current_content); ?>
    </textarea>

    <p><strong>Note:</strong> Ensure that the JSON is valid before saving changes.</p>

    <?php submit_button('Save Theme.json'); ?>
</form>
</div>

<script>
// Confirmation dialog before form submission
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to save changes to theme.json?')) {
        e.preventDefault();
    }
});
</script>

<?php
}