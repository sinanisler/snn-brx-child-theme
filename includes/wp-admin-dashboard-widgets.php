<?php 



add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {
?>  

<style>
.postbox-header{border-bottom:none !important; }
.postbox{border: none !important; box-shadow: none !important; }
.index-php h1{ display:none }
.index-php .dashboard-widgets h1{ display:block !important}
</style>

<?php
}

