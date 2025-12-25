<?php 



add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {
?>  

<style>
.postbox-header{border-bottom:none !important; }
.postbox{border: none !important; box-shadow: none !important; }
.sticky-menu h1:not(.inside h1){ display:none }
</style>

<?php
}

