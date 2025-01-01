<?php

// Frontend Head Inline JS and CSS, This will load in the <head>
// ASCII ART https://patorjk.com/software/taag/#p=display&f=ANSI%20Regular&t=sinanisler.com
function snn_custom_inline_frontend_head_code() {    ?>
<style type="text/css">

</style>
<script>

</script>
<?php }
add_action( 'wp_head', 'snn_custom_inline_frontend_head_code', 1 );


// Frontend Footer Inline JS and CSS,  This will load just before the </body>
function snn_custom_footer_inline() { ?>
<style>

</style>
<script>

</script>
<?php }
add_action('wp_footer', 'snn_custom_footer_inline', 9999);


// WP-Admin Backend Custom JS and CSS in <head>
function snn_custom_css() { ?>
<style>

</style>
<script>

</script>
<?php }
add_action('admin_head', 'snn_custom_css');