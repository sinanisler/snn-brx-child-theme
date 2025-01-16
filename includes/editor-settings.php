<?php

add_action('admin_menu', 'custom_editor_settings_page');
function custom_editor_settings_page() {
    add_submenu_page(
        'snn-settings',
        'Editor Settings',
        'Editor Settings',
        'manage_options',
        'editor-settings',
        'snn_render_editor_settings_page',
        2
    );
}

function snn_render_editor_settings_page() {
    ?>
    <div class="wrap">
        <h1>Bricks Builder Editor Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snn_editor_settings_group');
            do_settings_sections('snn-editor-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'snn_register_editor_settings');
function snn_register_editor_settings() {
    register_setting(
        'snn_editor_settings_group',
        'snn_editor_settings',
        'snn_sanitize_editor_settings'
    );

    add_settings_section(
        'snn_editor_settings_section',
        'Editor Settings',
        'snn_editor_settings_section_callback',
        'snn-editor-settings'
    );

    add_settings_field(
        'hide_element_icons',
        'Hide Elements Icons on Bricks Editor',
        'snn_hide_element_icons_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'make_compact_but_keep_icons',
        'Make Elements Compact But Keep Icons on Bricks Editor',
        'snn_make_compact_but_keep_icons_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'make_elements_wide',
        'Make Elements Wide on Bricks Editor',
        'snn_make_elements_wide_callback',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );

    add_settings_field(
        'snn_bricks_builder_color_fix_field',
        'Bricks Builder Global Colors Sync with Color Palette <br><span style="color:Red">EXPERIMENTAL</span>',
        'snn_render_checkbox_field',
        'snn-editor-settings',
        'snn_editor_settings_section'
    );
}

function snn_sanitize_editor_settings($input) {
    $sanitized = array();

    // Sanitize existing settings
    $sanitized['snn_bricks_builder_color_fix'] = isset($input['snn_bricks_builder_color_fix']) && $input['snn_bricks_builder_color_fix'] ? 1 : 0;

    // Sanitize the three new settings
    $sanitized['hide_element_icons'] = isset($input['hide_element_icons']) && $input['hide_element_icons'] ? 1 : 0;
    $sanitized['make_compact_but_keep_icons'] = isset($input['make_compact_but_keep_icons']) && $input['make_compact_but_keep_icons'] ? 1 : 0;
    $sanitized['make_elements_wide'] = isset($input['make_elements_wide']) && $input['make_elements_wide'] ? 1 : 0;

    return $sanitized;
}

function snn_editor_settings_section_callback() {
    ?>
    <p>
        Configure Bricks Builder editor-specific settings below.<br>
    </p>
    <?php
}

function snn_render_checkbox_field() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['snn_bricks_builder_color_fix']) ? $options['snn_bricks_builder_color_fix'] : 0;
    ?>
    <input type="checkbox" id="snn_bricks_builder_color_fix" name="snn_editor_settings[snn_bricks_builder_color_fix]" value="1" <?php checked(1, $checked, true); ?> />
    <label for="snn_bricks_builder_color_fix">
        Enable Bricks Builder Editor Color Fix<br>
        This setting will show the primary global color variables inside all color palettes.<br>
        It will load those color palettes as :root frontend colors as well.<br>
    </label>
    <?php
}

function snn_hide_element_icons_callback() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['hide_element_icons']) ? $options['hide_element_icons'] : 0;
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[hide_element_icons]" value="1" <?php checked(1, $checked, true); ?>>
        Hide Elements Icons on Bricks Editor
    </label>
    <?php
}

function snn_make_compact_but_keep_icons_callback() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['make_compact_but_keep_icons']) ? $options['make_compact_but_keep_icons'] : 0;
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[make_compact_but_keep_icons]" value="1" <?php checked(1, $checked, true); ?>>
        Make Elements Compact But Keep Icons
    </label>
    <?php
}

function snn_make_elements_wide_callback() {
    $options = get_option('snn_editor_settings');
    $checked = isset($options['make_elements_wide']) ? $options['make_elements_wide'] : 0;
    ?>
    <label>
        <input type="checkbox" name="snn_editor_settings[make_elements_wide]" value="1" <?php checked(1, $checked, true); ?>>
        Make Elements Wide on Bricks Editor
    </label>
    <?php
}

function snn_add_inline_css_if_bricks_run() {
    // Ensure this runs only on the frontend
    if (is_admin()) {
        return;
    }

    $options_editor = get_option('snn_editor_settings'); // New option group

    if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
        // Initialize CSS variable
        $inline_css = '';

        // Handle settings from Editor Settings
        if (isset($options_editor['hide_element_icons']) && $options_editor['hide_element_icons']) {
            // CSS for hiding element icons
            $inline_css .= '
                .bricks-add-element .element-icon {
                    display: none;
                }
                #bricks-panel-elements .sortable-wrapper{
                    margin:0 0 5px;
                    padding-left:8px;
                    padding-right:8px;
                }
                #bricks-panel-elements-categories .category-title{
                    padding-left:8px;
                    padding-right:8px;
                }
                #bricks-panel-elements-categories .category-title{
                    line-height:0;
                    padding-top:10px;
                    padding-bottom:10px;
                }
                .bricks-add-element .element-label{
                    box-shadow:0 0 ;
                    font-size:14px;
                    padding: 0 3px;
                    line-height:30px;
                }
            ';
        }

        if (isset($options_editor['make_compact_but_keep_icons']) && $options_editor['make_compact_but_keep_icons']) {
            // CSS for making elements compact but keeping icons
            $inline_css .= '
                .bricks-add-element .element-icon {
                    float: left;
                    width: 24px;
                    height: auto;
                    font-size: 14px;
                    line-height: 32px;
                }
                #bricks-panel-elements .sortable-wrapper{
                    margin:0 0 5px;
                    padding-left:8px;
                    padding-right:8px;
                }
                #bricks-panel-elements-categories .category-title{
                    padding-left:8px;
                    padding-right:8px;
                }
                #bricks-panel-elements-categories .category-title{
                    line-height:0;
                    padding-top:10px;
                    padding-bottom:10px;
                }
                .bricks-add-element .element-label{
                    box-shadow:0 0 ;
                    font-size:14px;
                    padding: 0 3px;
                    line-height:30px;
                }
            ';
        }

        if (isset($options_editor['make_elements_wide']) && $options_editor['make_elements_wide']) {
            // CSS for making elements wide
            $inline_css .= '
                #bricks-panel-elements .sortable-wrapper{
                    grid-template-columns: 1fr;
                }
            ';
        }

        if (!empty($inline_css)) {
            echo '<style>' . $inline_css . '</style>';
        }
    }
}
add_action('wp_head', 'snn_add_inline_css_if_bricks_run');

// Add inline CSS and JS for Bricks Builder Color Fix
function snn_bricks_builder_color_fix_inline_css() {
    // Ensure this runs only on the frontend
    if (is_admin()) {
        return;
    }

    $options = get_option('snn_editor_settings');
    if (isset($options['snn_bricks_builder_color_fix']) && $options['snn_bricks_builder_color_fix']) { 
        // Function to fetch and output the specified colors as CSS
        function echo_theme_colors_as_css() {
            // Get the serialized option from the WordPress database
            $theme_styles = get_option('bricks_theme_styles');
            
            // Check if the option exists
            if (!$theme_styles) {
                echo '';
                return;
            }
            
            // Unserialize the option to access its data
            $theme_styles_data = maybe_unserialize($theme_styles);
        
            // Ensure the data contains the necessary structure
            if (!isset($theme_styles_data['default_styles']['settings']['colors'])) {
                echo '';
                return;
            }
        
            // Extract colors
            $colors = $theme_styles_data['default_styles']['settings']['colors'];
        
            // List of required colors and their CSS variable mappings
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
        
            // Start outputting CSS variables
            echo "
<style>
/* SNN-BRX Bricks Builder Editor Color Fix Setting  */
:root {\n";
            
            // Loop through the required colors and output them as CSS variables
            foreach ($color_keys as $key => $css_var) {
                if (isset($colors[$key]['hex'])) {
                    $color_value = esc_attr($colors[$key]['hex']); // Sanitize output
                    echo "    --$css_var: $color_value;\n";
                }
            }
            
            // End the CSS block
            echo "}
</style>\n";
        }
        
        // Function to output theme colors as JavaScript variables and unshift color palette
        function generate_theme_colors_js() {
            // Retrieve the serialized theme styles from the WordPress database
            $theme_styles = get_option('bricks_theme_styles');
            
            // Check if the theme styles exist
            if (!$theme_styles) {
                return;
            }
            
            // Unserialize the theme styles to access its data
            $theme_styles_data = maybe_unserialize($theme_styles);
        
            // Check for the necessary structure in theme styles
            if (!isset($theme_styles_data['default_styles']['settings']['colors'])) {
                return;
            }
        
            // Extract the colors
            $colors = $theme_styles_data['default_styles']['settings']['colors'];
        
            // Mapping of color keys to their CSS variable names
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
        
            // Begin the unshift call
            echo 'bricksData.loadData.colorPalette[0].colors.unshift(';
        
            // Initialize an array to hold color objects
            $color_objects = [];
            $index = 1; // Initialize a separate counter for IDs
        
            foreach ($color_keys as $key => $js_var) {
                if (isset($colors[$key]['hex'])) {
                    $color_value = esc_js($colors[$key]['hex']); // Sanitize output
                    $color_objects[] = '    {
        "raw": "var(--' . $js_var . ')", 
        "id": "snn1' . $index . '", 
        "name": "' . $js_var . '" 
    }';
                    $index++;
                }
            }
        
            // Join the color objects with commas
            echo "\n" . implode(",\n", $color_objects) . "\n);";
        }
        
        // Output the CSS variables
        echo_theme_colors_as_css();
        ?>
<script>
<?php
        // Ensure this runs only when Bricks is running
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
    // Ensure this runs only on the frontend
    if (is_admin()) {
        return;
    }

    $options = get_option('snn_editor_settings');
    if (isset($options['snn_bricks_builder_color_fix']) && $options['snn_bricks_builder_color_fix']) { 
        // Function to fetch and output the specified colors as CSS
        function echo_theme_colors_as_css_head() {
            // Get the serialized option from the WordPress database
            $theme_styles = get_option('bricks_theme_styles');
            
            // Check if the option exists
            if (!$theme_styles) {
                echo '';
                return;
            }
            
            // Unserialize the option to access its data
            $theme_styles_data = maybe_unserialize($theme_styles);
        
            // Ensure the data contains the necessary structure
            if (!isset($theme_styles_data['default_styles']['settings']['colors'])) {
                echo '';
                return;
            }
        
            // Extract colors
            $colors = $theme_styles_data['default_styles']['settings']['colors'];
        
            // List of required colors and their CSS variable mappings
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
        
            // Start outputting CSS variables
            echo "
<style>
/* SNN-BRX Bricks Builder Editor Color Fix Setting (HEAD) */
:root {\n";
            
            // Loop through the required colors and output them as CSS variables
            foreach ($color_keys as $key => $css_var) {
                if (isset($colors[$key]['hex'])) {
                    $color_value = esc_attr($colors[$key]['hex']); // Sanitize output
                    echo "    --$css_var: $color_value !important;\n";
                }
            }
            
            // End the CSS block
            echo "}
</style>\n";
        }
        
        // Function to output theme colors as JavaScript variables and unshift color palette
        function generate_theme_colors_js_head() {
            // Retrieve the serialized theme styles from the WordPress database
            $theme_styles = get_option('bricks_theme_styles');
            
            // Check if the theme styles exist
            if (!$theme_styles) {
                return;
            }
            
            // Unserialize the theme styles to access its data
            $theme_styles_data = maybe_unserialize($theme_styles);
        
            // Check for the necessary structure in theme styles
            if (!isset($theme_styles_data['default_styles']['settings']['colors'])) {
                return;
            }
        
            // Extract the colors
            $colors = $theme_styles_data['default_styles']['settings']['colors'];
        
            // Mapping of color keys to their CSS variable names
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
        
            // Begin the unshift call
            echo 'bricksData.loadData.colorPalette[0].colors.unshift(';
        
            // Initialize an array to hold color objects
            $color_objects = [];
            $index = 1; // Initialize a separate counter for IDs
        
            foreach ($color_keys as $key => $js_var) {
                if (isset($colors[$key]['hex'])) {
                    $color_value = esc_js($colors[$key]['hex']); // Sanitize output
                    $color_objects[] = '    {
        "raw": "var(--' . $js_var . ')", 
        "id": "snn1' . $index . '", 
        "name": "' . $js_var . '" 
    }';
                    $index++;
                }
            }
        
            // Join the color objects with commas
            echo "\n" . implode(",\n", $color_objects) . "\n);";
        }
        
        // Output the CSS variables
        echo_theme_colors_as_css_head();
        ?>
<script>
<?php
        // Ensure this runs only when Bricks is running
        if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
            generate_theme_colors_js_head(); 
        }
?>
</script>
        <?php
    }
}
add_action('wp_head', 'snn_bricks_builder_color_fix_inline_css_head', 5);
