<?php
// Add the Accessibility Settings submenu page.
function snn_add_accessibility_settings_submenu() {
    add_submenu_page(
        'snn-settings',                   // Parent slug.
        'Accessibility Settings',         // Page title.
        'Accessibility Settings',         // Menu title.
        'manage_options',                 // Capability.
        'snn-accessibility-settings',     // Menu slug.
        'snn_render_accessibility_settings'// Callback function.
    );
}
add_action('admin_menu', 'snn_add_accessibility_settings_submenu');

// Render the Accessibility Settings page.
function snn_render_accessibility_settings() {
    ?>
    <div class="wrap">
        <h1>Accessibility Settings</h1>
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
    // Register the settings group and its sanitization callback.
    register_setting(
        'snn_accessibility_settings_group',
        'snn_accessibility_settings',
        'snn_sanitize_accessibility_settings'
    );

    // Add a section for the accessibility settings.
    add_settings_section(
        'snn_accessibility_settings_section',
        'Accessibility Settings',
        'snn_accessibility_settings_section_callback',
        'snn-accessibility-settings'
    );

    // Field: Enable Accessibility Widget.
    add_settings_field(
        'enqueue_accessibility',
        'Enable Accessibility Widget',
        'snn_enqueue_accessibility_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );

    // Field: Accessibility Widget Color.
    add_settings_field(
        'main_color',
        'Accessibility Widget Color',
        'snn_main_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
}
add_action('admin_init', 'snn_register_accessibility_settings');

// Sanitization callback for accessibility settings.
function snn_sanitize_accessibility_settings($input) {
    $sanitized = array();
    $sanitized['enqueue_accessibility'] = isset($input['enqueue_accessibility']) && $input['enqueue_accessibility'] ? 1 : 0;

    if (isset($input['main_color'])) {
        $sanitized['main_color'] = sanitize_hex_color($input['main_color']);
    }
    if (empty($sanitized['main_color'])) {
        $sanitized['main_color'] = '#07757f';
    }
    return $sanitized;
}

// Section callback.
function snn_accessibility_settings_section_callback() {
    echo '<p>Configure the accessibility options for your site below.</p>';
}

// Field callback: Enable Accessibility Widget.
function snn_enqueue_accessibility_callback() {
    $options = get_option('snn_accessibility_settings');
    ?>
    <input type="checkbox" name="snn_accessibility_settings[enqueue_accessibility]" value="1" <?php checked(1, isset($options['enqueue_accessibility']) ? $options['enqueue_accessibility'] : 0); ?>>
    <p>Enabling this setting will load the Accessibility Widget script and its associated styles on your website.</p>
    <?php
}

// Field callback: Accessibility Widget Color.
function snn_main_color_callback() {
    $options = get_option('snn_accessibility_settings');
    $value = isset($options['main_color']) ? $options['main_color'] : '#0062ad';
    ?>
    <input type="color" name="snn_accessibility_settings[main_color]" value="<?php echo esc_attr($value); ?>">
    <p>Select the main color for the Accessibility Widget.</p>
    <?php
}

// Optionally, output the Accessibility Widget on the front end if enabled.
function snn_output_accessibility_widget() {
    $options = get_option('snn_accessibility_settings');
    if ( isset($options['enqueue_accessibility']) && $options['enqueue_accessibility'] ) {
        $main_color = isset($options['main_color']) && $options['main_color'] ? $options['main_color'] : '#07757f';
        // Output the external widget script.
        echo '<script src="https://website-widgets.pages.dev/dist/sienna.min.js"></script>';
        ?>
        <script>
        const mainColor = '<?php echo esc_js($main_color); ?>';
        function applyStylesToMenuBtn(button) {
            if (!button) return;
            button.style.setProperty('outline', `5px solid ${mainColor}`, 'important');
            button.style.setProperty('background', mainColor, 'important');
            button.style.setProperty('background', `linear-gradient(96deg, ${mainColor} 0, ${mainColor} 100%)`, 'important');
        }
        function applyStylesToMenuHeader(header) {
            if (!header) return;
            header.style.setProperty('background-color', mainColor, 'important');
        }
        function checkAndStyleElements() {
            const menuBtn = document.querySelector('.asw-menu-btn');
            const menuHeader = document.querySelector('.asw-menu-header');
            if (menuBtn) applyStylesToMenuBtn(menuBtn);
            if (menuHeader) applyStylesToMenuHeader(menuHeader);
        }
        document.addEventListener("DOMContentLoaded", checkAndStyleElements);
        </script>
        <style>
        :root {
            --main-color: <?php echo esc_html($main_color); ?>;
        }
        .asw-footer {
            display: none !important;
        }
        .asw-menu-content {
            max-height: 100% !important;
            padding-bottom: 40px !important;
        }
        .asw-menu-header svg,
        .asw-menu-header svg path {
            fill: var(--main-color) !important;
        }
        </style>
        <?php
    }
}
add_action('wp_footer', 'snn_output_accessibility_widget');
?>
