<?php 



add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {
?>  

<style>
.postbox-header{border-bottom:none !important; }
.postbox{border: none !important; box-shadow: none !important; }
h1:has(.ui-sortable-handle){ display:none }
</style>

<?php
}

