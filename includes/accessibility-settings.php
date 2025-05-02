<?php
// Add the Accessibility Settings submenu page.
function snn_add_accessibility_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        'Accessibility Settings',
        'Accessibility Settings',
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
    register_setting(
        'snn_accessibility_settings_group',
        'snn_accessibility_settings',
        'snn_sanitize_accessibility_settings'
    );

    add_settings_section(
        'snn_accessibility_settings_section',
        'Accessibility Settings',
        'snn_accessibility_settings_section_callback',
        'snn-accessibility-settings'
    );

    add_settings_field(
        'enqueue_accessibility',
        'Enable Accessibility Widget',
        'snn_enqueue_accessibility_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );

    add_settings_field(
        'main_color',
        'Accessibility Widget Color',
        'snn_main_color_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );

    // New: Button width
    add_settings_field(
        'btn_width',
        'Button Width (px)',
        'snn_btn_width_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );

    // New: Button height
    add_settings_field(
        'btn_height',
        'Button Height (px)',
        'snn_btn_height_callback',
        'snn-accessibility-settings',
        'snn_accessibility_settings_section'
    );
}
add_action('admin_init', 'snn_register_accessibility_settings');

// Sanitization callback.
function snn_sanitize_accessibility_settings( $input ) {
    $sanitized = array();

    $sanitized['enqueue_accessibility'] = ! empty( $input['enqueue_accessibility'] ) ? 1 : 0;

    if ( ! empty( $input['main_color'] ) ) {
        $sanitized['main_color'] = sanitize_hex_color( $input['main_color'] );
    }
    if ( empty( $sanitized['main_color'] ) ) {
        $sanitized['main_color'] = '#07757f';
    }

    // New: sanitize width & height as positive integers, default to 45
    $sanitized['btn_width']  = ! empty( $input['btn_width'] )  ? absint( $input['btn_width'] )  : 45;
    $sanitized['btn_height'] = ! empty( $input['btn_height'] ) ? absint( $input['btn_height'] ) : 45;

    return $sanitized;
}

// Section callback.
function snn_accessibility_settings_section_callback() {
    echo '<p>Configure the accessibility options for your site below.</p>';
}

// Field callbacks.
function snn_enqueue_accessibility_callback() {
    $options = get_option('snn_accessibility_settings');
    ?>
    <input type="checkbox"
           name="snn_accessibility_settings[enqueue_accessibility]"
           value="1"
           <?php checked( 1, ! empty( $options['enqueue_accessibility'] ) ? $options['enqueue_accessibility'] : 0 ); ?>>
    <p>Enabling this setting will load the Accessibility Widget script on your site.</p>
    <?php
}

function snn_main_color_callback() {
    $options = get_option('snn_accessibility_settings');
    $value   = ! empty( $options['main_color'] ) ? $options['main_color'] : '#07757f';
    ?>
    <input type="color"
           name="snn_accessibility_settings[main_color]"
           value="<?php echo esc_attr( $value ); ?>">
    <p>Select the main color for the Accessibility Widget.</p>
    <?php
}

// New: Button width input
function snn_btn_width_callback() {
    $options = get_option('snn_accessibility_settings');
    $value   = ! empty( $options['btn_width'] ) ? $options['btn_width'] : 45;
    ?>
    <input type="number"
           name="snn_accessibility_settings[btn_width]"
           value="<?php echo esc_attr( $value ); ?>"
           min="0" step="1"> px
    <p>Set the width of the accessibility menu button (in pixels).</p>
    <?php
}

// New: Button height input
function snn_btn_height_callback() {
    $options = get_option('snn_accessibility_settings');
    $value   = ! empty( $options['btn_height'] ) ? $options['btn_height'] : 45;
    ?>
    <input type="number"
           name="snn_accessibility_settings[btn_height]"
           value="<?php echo esc_attr( $value ); ?>"
           min="0" step="1"> px
    <p>Set the height of the accessibility menu button (in pixels).</p>
    <?php
}

// 1) Enqueue the widget JS in the head.
function snn_enqueue_accessibility_widget_head() {
    $options = get_option('snn_accessibility_settings');
    if ( ! empty( $options['enqueue_accessibility'] ) ) {
        $handle  = 'snn-accessibility-widget';
        $src     = get_stylesheet_directory_uri() . '/assets/js/accessibility.min.js';
        $path    = get_stylesheet_directory() . '/assets/js/accessibility.min.js';
        $version = file_exists( $path ) ? filemtime( $path ) : false;
        wp_enqueue_script( $handle, $src, array(), $version, false ); // false = load in head
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_accessibility_widget_head');

// 2) Output inline JS & styles in the footer.
function snn_output_accessibility_widget_footer() {
    $options = get_option('snn_accessibility_settings');
    if ( ! empty( $options['enqueue_accessibility'] ) ) {
        $main_color = ! empty( $options['main_color'] )  ? $options['main_color']  : '#07757f';
        $btn_width  = ! empty( $options['btn_width'] )   ? $options['btn_width']   : 45;
        $btn_height = ! empty( $options['btn_height'] )  ? $options['btn_height']  : 45;
        ?>
        <script>
        const mainColor = '<?php echo esc_js( $main_color ); ?>';

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
            if (menuBtn && menuHeader) {
                observer.disconnect();
            }
        }

        const observer = new MutationObserver(checkAndStyleElements);
        observer.observe(document.body, { childList: true, subtree: true });
        document.addEventListener("DOMContentLoaded", checkAndStyleElements);
        </script>
        <style>
        :root {
            --main-color: <?php echo esc_html( $main_color ); ?>;
            --btn-width: <?php echo esc_html( $btn_width ); ?>px;
            --btn-height: <?php echo esc_html( $btn_height ); ?>px;
        }
        .asw-menu-btn {
            width: var(--btn-width) !important;
            height: var(--btn-height) !important;
        }
        .asw-footer {
            display: none !important;
        }
        .asw-menu-content {
            max-height: 100% !important;
            padding-bottom: 40px !important;
            padding-top:15px !important;
            padding-bottom:15px !important;
            overflow:auto !important;
        }
        .asw-menu-header svg,
        .asw-menu-header svg path {
            fill: var(--main-color) !important;
        }
        .asw-btn {
            aspect-ratio: 6 / 3.8 !important;
        }
        .asw-adjust-font{
            display:flex !important;
            justify-content: space-between;
        }
        .asw-adjust-font>div{
            margin-top: 0px !important;
            gap:5px;
        }
        .asw-menu-header div[role=button]{
            padding:8px !important;
        }
        .asw-menu-btn svg {
            width: <?php echo esc_html( $btn_width / 1.4 ); ?>px !important;
            height: <?php echo esc_html( $btn_width / 1.4 ); ?>px !important;
            min-height: auto !important;
            min-width: auto !important;
        }
        .asw-menu, .asw-widget{
        user-select:auto !important;
        outline:2px !important;
        }


        /* this will become a setting for alignment left right */
        .asw-menu-btn{
            left:20px !important;
            bottom:20px !important;
        }

        .asw-menu-btn{
            right:20px !important;
            bottom:20px !important;
            left:auto !important;
        }
        /* this will become a setting for alignment left right */



        </style>
        <?php
    }
}
add_action('wp_footer', 'snn_output_accessibility_widget_footer');
