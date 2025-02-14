<?php
/*
function snn_bricks_builder_color_fix_inline_css() {
    // Run only on the frontend.
    if (is_admin()) {
        return;
    }

    $options = get_option('snn_editor_settings');
    if (isset($options['snn_bricks_builder_color_fix']) && $options['snn_bricks_builder_color_fix']) {

        // Output JavaScript to unshift the theme colors into the Bricks color palette.
        function generate_theme_colors_js() {
            $theme_styles = get_option('bricks_theme_styles');
            if (!$theme_styles) {
                return;
            }
            $theme_styles_data = maybe_unserialize($theme_styles);
            $colors = null;
            foreach ($theme_styles_data as $style_key => $style) {
                if (isset($style['settings']['colors'])) {
                    $colors = $style['settings']['colors'];
                    break;
                }
            }
            if (!$colors) {
                return;
            }
            $color_keys = [
                'colorPrimary'   => 'bricks-color-primary',
                'colorSecondary' => 'bricks-color-secondary',
                'colorDark'      => 'bricks-text-dark',
                'colorLight'     => 'bricks-text-light',
                'colorInfo'      => 'bricks-text-info',
                'colorSuccess'   => 'bricks-text-success',
                'colorWarning'   => 'bricks-text-warning',
                'colorDanger'    => 'bricks-text-danger',
                'colorMuted'     => 'bricks-text-muted',
                'colorBorder'    => 'bricks-text-border',
            ];
            echo 'bricksData.loadData.colorPalette[0].colors.unshift(';
            $color_objects = [];
            $index = 1;
            foreach ($color_keys as $key => $js_var) {
                if (isset($colors[$key]['hex'])) {
                    $color_value = esc_js($colors[$key]['hex']);
                    $color_objects[] = '    {
        "raw": "var(--' . $js_var . ')",
        "id": "snn1' . $index . '",
        "name": "' . $js_var . '"
    }';
                    $index++;
                }
            }
            echo "\n" . implode(",\n", $color_objects) . "\n);";
        }

        // Output only the JavaScript in the footer.
        ?>
<script>
<?php
        if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
            generate_theme_colors_js();
        }
?>
</script>
        <?php
    }
}
add_action('wp_footer', 'snn_bricks_builder_color_fix_inline_css', 50);

function snn_bricks_builder_color_fix_inline_css_head() {
    if (is_admin()) {
        return;
    }

    $options = get_option('snn_editor_settings');
    if (isset($options['snn_bricks_builder_color_fix']) && $options['snn_bricks_builder_color_fix']) {

        function generate_theme_colors_js_head() {
            $theme_styles = get_option('bricks_theme_styles');
            if (!$theme_styles) {
                return;
            }
            $theme_styles_data = maybe_unserialize($theme_styles);
            $colors = null;
            foreach ($theme_styles_data as $style_key => $style) {
                if (isset($style['settings']['colors'])) {
                    $colors = $style['settings']['colors'];
                    break;
                }
            }
            if (!$colors) {
                return;
            }
            $color_keys = [
                'colorPrimary'   => 'bricks-color-primary',
                'colorSecondary' => 'bricks-color-secondary',
                'colorDark'      => 'bricks-text-dark',
                'colorLight'     => 'bricks-text-light',
                'colorInfo'      => 'bricks-text-info',
                'colorSuccess'   => 'bricks-text-success',
                'colorWarning'   => 'bricks-text-warning',
                'colorDanger'    => 'bricks-text-danger',
                'colorMuted'     => 'bricks-text-muted',
                'colorBorder'    => 'bricks-text-border',
            ];
            echo 'bricksData.loadData.colorPalette[0].colors.unshift(';
            $color_objects = [];
            $index = 1;
            foreach ($color_keys as $key => $js_var) {
                if (isset($colors[$key]['hex'])) {
                    $color_value = esc_js($colors[$key]['hex']);
                    $color_objects[] = '    {
    "raw": "var(--' . $js_var . ')",
    "id": "snn1' . $index . '",
    "name": "' . $js_var . '"
}';
                    $index++;
                }
            }
            echo "\n" . implode(",\n", $color_objects) . "\n);";
        }

        // Output only the JavaScript in the head.
        ?>
<script>
<?php
        if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
            generate_theme_colors_js_head();
        }
?>
</script>
        <?php
    }
}
add_action('wp_head', 'snn_bricks_builder_color_fix_inline_css_head', 5);


*/
?>
