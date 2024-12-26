<?php

function snn_add_external_documentation_submenu() {
    global $submenu;

    $parent_slug = 'snn-settings';
    $menu_title = 'Documentation ➤';
    $capability = 'manage_options';
    $external_url = 'https://github.com/sinanisler/snn-brx-child-theme/wiki';

    if (isset($submenu[$parent_slug])) {
        $submenu[$parent_slug][] = array(
            $menu_title,
            $capability,
            $external_url,
            $menu_title
        );
    }
}
add_action('admin_menu', 'snn_add_external_documentation_submenu', 999);

function snn_add_inline_admin_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#adminmenu a').filter(function() {
            return $(this).text().trim() === 'Documentation ➤';
        }).attr('target', '_blank');
    });
    </script>
    <?php
}
add_action('admin_footer', 'snn_add_inline_admin_script');
?>
