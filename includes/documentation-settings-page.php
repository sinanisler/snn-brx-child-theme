<?php
// Hook into 'admin_menu' to modify the admin menu
function snn_add_external_documentation_submenu() {
    global $submenu;

    $parent_slug = 'snn-settings'; // Replace with your actual parent menu slug
    $menu_title = 'Documentation';  // The text to be displayed for the submenu
    $capability = 'manage_options'; // Capability required to access this submenu
    $external_url = 'https://github.com/sinanisler/snn-brx-child-theme/wiki'; // Replace with your external URL

    // Ensure the parent menu exists
    if ( isset( $submenu[ $parent_slug ] ) ) {
        // Add the external link as a submenu item
        $submenu[ $parent_slug ][] = array(
            $menu_title,     // Menu title
            $capability,     // Capability
            $external_url,   // URL
            $menu_title      // Description (optional)
        );
    }
}
add_action( 'admin_menu', 'snn_add_external_documentation_submenu', 999 );

// Add inline JavaScript to set target="_blank"
function snn_add_inline_admin_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Replace 'Documentation' with the exact submenu title
        $('#adminmenu a').filter(function() {
            return $(this).text().trim() === 'Documentation';
        }).attr('target', '_blank');
    });
    </script>
    <?php
}
add_action( 'admin_footer', 'snn_add_inline_admin_script' );
?>
