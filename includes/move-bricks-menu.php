<?php

function snn_custom_menu_order($menu_ord) {
    $options = get_option('snn_settings');
    if (isset($options['move_bricks_menu']) && $options['move_bricks_menu']) {
        if (!$menu_ord) return true;
        foreach ($menu_ord as $index => $item) {
            if ($item == 'bricks') {
                $bricks_menu = $item;
                unset($menu_ord[$index]);
                break;
            }
        }
        if (isset($bricks_menu)) {
            $menu_ord[] = $bricks_menu;
        }
        return $menu_ord;
    }
    return $menu_ord;
}
add_filter('menu_order', 'snn_custom_menu_order');
add_filter('custom_menu_order', function () { return true; });

function snn_move_bricks_menu_setting_field() {
    add_settings_field(
        'snn_move_bricks_menu',
        'Move Bricks Menu to End',
        'snn_move_bricks_menu_callback',
        'snn-settings',
        'snn_general_section'
    );
}
add_action('admin_init', 'snn_move_bricks_menu_setting_field');

function snn_move_bricks_menu_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="checkbox" name="snn_settings[move_bricks_menu]" value="1" <?php checked(isset($options['move_bricks_menu']), 1); ?>>
    <p>Enabling this setting will move the Bricks menu item to the end of the WordPress admin menu.</p>
    <?php
}