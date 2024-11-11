<?php 

// Adds custom inline CSS to the footer in WordPress
function admin_footer_custom_footer_inline_css() {
?>
<style>
    .column-file_size {
        width:90px
    }

</style>
<?php
}
add_action('admin_footer', 'admin_footer_custom_footer_inline_css');




