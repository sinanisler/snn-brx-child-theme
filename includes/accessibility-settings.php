<?php
// Add the Accessibility Settings submenu page.
function snn_add_accessibility_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __('Accessibility Settings', 'snn'),
        __('Accessibility Settings', 'snn'),
        'manage_options',
        'snn-accessibility-settings',
        'snn_render_accessibility_settings'
    );
}
add_action('admin_menu', 'snn_add_accessibility_settings_submenu');

// Render the Accessibility Settings page.
function snn_render_accessibility_settings() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_accessibility_settings_group');
                do_settings_sections('snn-accessibility-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the Accessibility Settings.
function snn_register_accessibility_settings() {
    register_setting(
        'snn_accessibility_settings_group',
        'snn_accessibility_settings',
        'snn_sanitize_accessibility_settings'
    );

    add_settings_section(
        'snn_accessibility_settings_section',
        __('Accessibility Settings', 'snn'),
        'snn_accessibility_settings_section_callback',
        'snn-accessibility-settings'
    );

    add_settings_field(
        'enqueue_accessibility',
        __('Enable Accessibility Widget', 'snn'),
        'snn_enqueue_accessibility_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'main_color',
        __('Primary Color', 'snn'),
        'snn_main_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'secondary_color',
        __('Secondary Color', 'snn'),
        'snn_secondary_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'option_bg_color',
        __('Option Background Color', 'snn'),
        'snn_option_bg_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'option_text_color',
        __('Option Text Color', 'snn'),
        'snn_option_text_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'option_icon_color',
        __('Option Icon Color', 'snn'),
        'snn_option_icon_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'btn_width',
        __('Button Width (px)', 'snn'),
        'snn_btn_width_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'btn_height',
        __('Button Height (px)', 'snn'),
        'snn_btn_height_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'btn_alignment',
        __('Button Alignment', 'snn'),
        'snn_btn_alignment_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'btn_spacing_left',
        __('Left Spacing (px)', 'snn'),
        'snn_btn_spacing_left_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'btn_spacing_bottom',
        __('Bottom Spacing (px)', 'snn'),
        'snn_btn_spacing_bottom_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
    add_settings_field(
        'btn_spacing_right',
        __('Right Spacing (px)', 'snn'),
        'snn_btn_spacing_right_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
}
add_action('admin_init', 'snn_register_accessibility_settings');

// Sanitization callback.
function snn_sanitize_accessibility_settings( $input ) {
    $sanitized = [];
    $sanitized['enqueue_accessibility'] = ! empty( $input['enqueue_accessibility'] ) ? 1 : 0;
    
    // Helper function to sanitize color values
    $sanitize_color = function( $color, $default ) {
        if ( ! empty( $color ) ) {
            $color = trim( $color );
            // Basic sanitization - remove potentially harmful characters but allow CSS color formats
            $color = preg_replace('/[<>"\']/', '', $color);
            return $color;
        }
        return $default;
    };
    
    // Sanitize all color inputs
    $sanitized['main_color']        = $sanitize_color( $input['main_color'] ?? '', '#1663d7' );
    $sanitized['secondary_color']   = $sanitize_color( $input['secondary_color'] ?? '', '#ffffff' );
    $sanitized['option_bg_color']   = $sanitize_color( $input['option_bg_color'] ?? '', '#ffffff' );
    $sanitized['option_text_color'] = $sanitize_color( $input['option_text_color'] ?? '', '#333333' );
    $sanitized['option_icon_color'] = $sanitize_color( $input['option_icon_color'] ?? '', '#000000' );
    
    $sanitized['btn_width']              = ! empty( $input['btn_width'] )  ? absint( $input['btn_width'] )  : 55;
    $sanitized['btn_height']             = ! empty( $input['btn_height'] ) ? absint( $input['btn_height'] ) : 55;
    $align = isset( $input['btn_alignment'] ) ? $input['btn_alignment'] : 'right';
    $sanitized['btn_alignment']          = in_array( $align, ['left','right'], true ) ? $align : 'right';
    $sanitized['btn_spacing_left']       = ! empty( $input['btn_spacing_left'] )   ? absint( $input['btn_spacing_left'] )   : 20;
    $sanitized['btn_spacing_bottom']     = ! empty( $input['btn_spacing_bottom'] ) ? absint( $input['btn_spacing_bottom'] ) : 20;
    $sanitized['btn_spacing_right']      = ! empty( $input['btn_spacing_right'] )  ? absint( $input['btn_spacing_right'] )  : 20;
    return $sanitized;
}

// Section callback.
function snn_accessibility_settings_section_callback() {
    echo '<p>' . esc_html__( 'Configure the accessibility options for your site below.', 'snn' ) . '</p>';
}

// Field callbacks.
function snn_enqueue_accessibility_callback() {
    $opt = get_option('snn_accessibility_settings');
    ?>
    <input type="checkbox" name="snn_accessibility_settings[enqueue_accessibility]" value="1"
        <?php checked(1, ! empty($opt['enqueue_accessibility']) ? $opt['enqueue_accessibility'] : 0); ?>>
    <p><?php esc_html_e( 'Load the Accessibility Widget script.', 'snn' ); ?></p>
    <?php
}
function snn_main_color_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['main_color']) ? $opt['main_color'] : '#1663d7';
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" id="snn_color_picker_main" value="<?php echo esc_attr($val); ?>" style="width: 50px; height: 40px; border: none; cursor: pointer;">
        <input type="text" name="snn_accessibility_settings[main_color]" id="snn_color_input_main" value="<?php echo esc_attr($val); ?>" placeholder="e.g., #1663d7, rgb(22, 99, 215)" style="width: 300px; padding: 5px;">
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('snn_color_picker_main');
        const colorInput = document.getElementById('snn_color_input_main');
        
        colorPicker.addEventListener('input', function() {
            colorInput.value = this.value;
        });
        
        colorInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorPicker.value = value;
            }
        });
    });
    </script>
    <?php
}
function snn_secondary_color_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['secondary_color']) ? $opt['secondary_color'] : '#ffffff';
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" id="snn_color_picker_secondary" value="<?php echo esc_attr($val); ?>" style="width: 50px; height: 40px; border: none; cursor: pointer;">
        <input type="text" name="snn_accessibility_settings[secondary_color]" id="snn_color_input_secondary" value="<?php echo esc_attr($val); ?>" placeholder="e.g., #ffffff" style="width: 300px; padding: 5px;">
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('snn_color_picker_secondary');
        const colorInput = document.getElementById('snn_color_input_secondary');
        
        colorPicker.addEventListener('input', function() {
            colorInput.value = this.value;
        });
        
        colorInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorPicker.value = value;
            }
        });
    });
    </script>
    <?php
}
function snn_option_bg_color_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['option_bg_color']) ? $opt['option_bg_color'] : '#ffffff';
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" id="snn_color_picker_option_bg" value="<?php echo esc_attr($val); ?>" style="width: 50px; height: 40px; border: none; cursor: pointer;">
        <input type="text" name="snn_accessibility_settings[option_bg_color]" id="snn_color_input_option_bg" value="<?php echo esc_attr($val); ?>" placeholder="e.g., #ffffff" style="width: 300px; padding: 5px;">
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('snn_color_picker_option_bg');
        const colorInput = document.getElementById('snn_color_input_option_bg');
        
        colorPicker.addEventListener('input', function() {
            colorInput.value = this.value;
        });
        
        colorInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorPicker.value = value;
            }
        });
    });
    </script>
    <?php
}
function snn_option_text_color_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['option_text_color']) ? $opt['option_text_color'] : '#333333';
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" id="snn_color_picker_option_text" value="<?php echo esc_attr($val); ?>" style="width: 50px; height: 40px; border: none; cursor: pointer;">
        <input type="text" name="snn_accessibility_settings[option_text_color]" id="snn_color_input_option_text" value="<?php echo esc_attr($val); ?>" placeholder="e.g., #333333" style="width: 300px; padding: 5px;">
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('snn_color_picker_option_text');
        const colorInput = document.getElementById('snn_color_input_option_text');
        
        colorPicker.addEventListener('input', function() {
            colorInput.value = this.value;
        });
        
        colorInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorPicker.value = value;
            }
        });
    });
    </script>
    <?php
}
function snn_option_icon_color_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['option_icon_color']) ? $opt['option_icon_color'] : '#000000';
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" id="snn_color_picker_option_icon" value="<?php echo esc_attr($val); ?>" style="width: 50px; height: 40px; border: none; cursor: pointer;">
        <input type="text" name="snn_accessibility_settings[option_icon_color]" id="snn_color_input_option_icon" value="<?php echo esc_attr($val); ?>" placeholder="e.g., #000000" style="width: 300px; padding: 5px;">
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('snn_color_picker_option_icon');
        const colorInput = document.getElementById('snn_color_input_option_icon');
        
        colorPicker.addEventListener('input', function() {
            colorInput.value = this.value;
        });
        
        colorInput.addEventListener('input', function() {
            const value = this.value.trim();
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorPicker.value = value;
            }
        });
    });
    </script>
    <?php
}
function snn_btn_width_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_width']) ? $opt['btn_width'] : 55;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_width]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}
function snn_btn_height_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_height']) ? $opt['btn_height'] : 55;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_height]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}
function snn_btn_alignment_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_alignment']) ? $opt['btn_alignment'] : 'right';
    ?>
    <select name="snn_accessibility_settings[btn_alignment]">
        <option value="left" <?php selected($val, 'left'); ?>><?php esc_html_e( 'Left', 'snn' ); ?></option>
        <option value="right" <?php selected($val, 'right'); ?>><?php esc_html_e( 'Right', 'snn' ); ?></option>
    </select>
    <?php
}
function snn_btn_spacing_left_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_spacing_left']) ? $opt['btn_spacing_left'] : 20;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_spacing_left]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}
function snn_btn_spacing_bottom_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_spacing_bottom']) ? $opt['btn_spacing_bottom'] : 20;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_spacing_bottom]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}
function snn_btn_spacing_right_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_spacing_right']) ? $opt['btn_spacing_right'] : 20;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_spacing_right]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}

// Enqueue new snn-accessibility widget JS, but skip when Bricks editor is running.
function snn_enqueue_accessibility_widget_head() {
    if ( isset( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) {
        return;
    }
    $opt = get_option('snn_accessibility_settings');
    if ( ! empty( $opt['enqueue_accessibility'] ) ) {
        $src  = get_stylesheet_directory_uri() . '/assets/js/snn-accessibility.js';
        $path = get_stylesheet_directory() . '/assets/js/snn-accessibility.js';
        $ver  = file_exists( $path ) ? filemtime( $path ) : false;
        wp_enqueue_script( 'snn-accessibility-widget', $src, [], $ver, true );
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_accessibility_widget_head');

// Output widget configuration in head, but skip when Bricks editor is running.
function snn_output_accessibility_widget_config() {
    if ( isset( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) {
        return;
    }
    $opt = get_option('snn_accessibility_settings');
    if ( empty( $opt['enqueue_accessibility'] ) ) {
        return;
    }
    
    // Get all settings with defaults
    $primary       = ! empty($opt['main_color'])        ? $opt['main_color']        : '#1663d7';
    $secondary     = ! empty($opt['secondary_color'])   ? $opt['secondary_color']   : '#ffffff';
    $option_bg     = ! empty($opt['option_bg_color'])   ? $opt['option_bg_color']   : '#ffffff';
    $option_text   = ! empty($opt['option_text_color']) ? $opt['option_text_color'] : '#333333';
    $option_icon   = ! empty($opt['option_icon_color']) ? $opt['option_icon_color'] : '#000000';
    $btn_width     = ! empty($opt['btn_width'])         ? $opt['btn_width']         : 55;
    $btn_height    = ! empty($opt['btn_height'])        ? $opt['btn_height']        : 55;
    $alignment     = ! empty($opt['btn_alignment'])     ? $opt['btn_alignment']     : 'right';
    $spacing_left  = ! empty($opt['btn_spacing_left'])  ? $opt['btn_spacing_left']  : 20;
    $spacing_bottom= ! empty($opt['btn_spacing_bottom']) ? $opt['btn_spacing_bottom']: 20;
    $spacing_right = ! empty($opt['btn_spacing_right']) ? $opt['btn_spacing_right'] : 20;
    
    // Determine spacing values based on alignment
    $side_value = ( 'left' === $alignment ) ? $spacing_left : $spacing_right;
    
    ?>
    <script>
    window.ACCESSIBILITY_WIDGET_CONFIG = {
        colors: {
            primary: '<?php echo esc_js($primary); ?>',
            secondary: '<?php echo esc_js($secondary); ?>',
            optionBg: '<?php echo esc_js($option_bg); ?>',
            optionText: '<?php echo esc_js($option_text); ?>',
            optionIcon: '<?php echo esc_js($option_icon); ?>'
        },
        button: {
            size: '<?php echo esc_js($btn_width); ?>px'
        },
        widgetPosition: {
            side: '<?php echo esc_js($alignment); ?>',
            <?php echo esc_js($alignment); ?>: '<?php echo esc_js($side_value); ?>px',
            bottom: '<?php echo esc_js($spacing_bottom); ?>px'
        }
    };
    </script>
    <?php
}
add_action('wp_head', 'snn_output_accessibility_widget_config', 5);