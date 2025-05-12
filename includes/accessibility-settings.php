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
        __('Accessibility Widget Color', 'snn'),
        'snn_main_color_callback',
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
    $sanitized['main_color']              = ! empty( $input['main_color'] )
        ? sanitize_hex_color( $input['main_color'] )
        : '#07757f';
    $sanitized['btn_width']              = ! empty( $input['btn_width'] )  ? absint( $input['btn_width'] )  : 45;
    $sanitized['btn_height']             = ! empty( $input['btn_height'] ) ? absint( $input['btn_height'] ) : 45;
    $align = isset( $input['btn_alignment'] ) ? $input['btn_alignment'] : 'left';
    $sanitized['btn_alignment']          = in_array( $align, ['left','right'], true ) ? $align : 'left';
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
    $val = ! empty($opt['main_color']) ? $opt['main_color'] : '#07757f';
    ?>
    <input type="color" name="snn_accessibility_settings[main_color]" value="<?php echo esc_attr($val); ?>">
    <?php
}
function snn_btn_width_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_width']) ? $opt['btn_width'] : 45;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_width]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}
function snn_btn_height_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_height']) ? $opt['btn_height'] : 45;
    ?>
    <input type="number" name="snn_accessibility_settings[btn_height]" value="<?php echo esc_attr($val); ?>" min="0" step="1"> px
    <?php
}
function snn_btn_alignment_callback() {
    $opt = get_option('snn_accessibility_settings');
    $val = ! empty($opt['btn_alignment']) ? $opt['btn_alignment'] : 'left';
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

// Enqueue widget JS in head, but skip when Bricks editor is running.
function snn_enqueue_accessibility_widget_head() {
    if ( isset( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) {
        return;
    }
    $opt = get_option('snn_accessibility_settings');
    if ( ! empty( $opt['enqueue_accessibility'] ) ) {
        $src  = get_stylesheet_directory_uri() . '/assets/js/accessibility.min.js';
        $path = get_stylesheet_directory() . '/assets/js/accessibility.min.js';
        $ver  = file_exists( $path ) ? filemtime( $path ) : false;
        wp_enqueue_script( 'snn-accessibility-widget', $src, [], $ver, false );
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_accessibility_widget_head');

// Output inline JS & styles in footer, but skip when Bricks editor is running.
function snn_output_accessibility_widget_footer() {
    if ( isset( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) {
        return;
    }
    $opt = get_option('snn_accessibility_settings');
    if ( empty( $opt['enqueue_accessibility'] ) ) {
        return;
    }
    $c  = $opt['main_color'];
    $w  = $opt['btn_width'];
    $h  = $opt['btn_height'];
    $a  = $opt['btn_alignment'];
    $l  = $opt['btn_spacing_left'];
    $b  = $opt['btn_spacing_bottom'];
    $r  = $opt['btn_spacing_right'];
    ?>
    <script>
    const mainColor = '<?php echo esc_js($c); ?>';
    function applyStylesToMenuBtn(btn){
        if(!btn) return;
        btn.style.setProperty('outline',`5px solid ${mainColor}`,'important');
        btn.style.setProperty('background',mainColor,'important');
        btn.style.setProperty('background',`linear-gradient(96deg,${mainColor}0,${mainColor}100%)`,'important');
    }
    function applyStylesToMenuHeader(h){
        if(!h) return;
        h.style.setProperty('background-color',mainColor,'important');
    }
    function checkAndStyleElements(){
        const btn = document.querySelector('.asw-menu-btn');
        const hdr = document.querySelector('.asw-menu-header');
        if(btn) applyStylesToMenuBtn(btn);
        if(hdr) applyStylesToMenuHeader(hdr);
        if(btn && hdr) observer.disconnect();
    }
    const observer = new MutationObserver(checkAndStyleElements);
    observer.observe(document.body,{childList:true,subtree:true});
    document.addEventListener("DOMContentLoaded",checkAndStyleElements);
    </script>
    <style>
    :root {
        --main-color: <?php echo esc_html($c); ?>;
        --btn-width: <?php echo esc_html($w); ?>px;
        --btn-height: <?php echo esc_html($h); ?>px;
    }
    <?php if ( 'left' === $a ): ?>
    .asw-menu-btn {
        left: <?php echo esc_html($l); ?>px !important;
        bottom: <?php echo esc_html($b); ?>px !important;
        right: <?php echo esc_html($r); ?>px !important;
    }
    <?php else: ?>
    .asw-menu-btn {
        right: <?php echo esc_html($r); ?>px !important;
        bottom: <?php echo esc_html($b); ?>px !important;
        left: auto !important;
    }
    <?php endif; ?>
    .asw-footer { display: none !important; }
    .asw-menu-content {
        max-height: 100% !important;
        padding-top: 15px !important;
        padding-bottom: 15px !important;
        overflow: auto !important;
    }
    .asw-menu-header svg,
    .asw-menu-header svg path { fill: var(--main-color) !important; }
    .asw-btn { aspect-ratio: 6/3.8 !important; }
    .asw-adjust-font {
        display: flex !important;
        justify-content: space-between;
    }
    .asw-adjust-font > div { margin-top: 0 !important; gap: 5px; }
    .asw-menu-header div[role=button] { padding: 8px !important; }
    .asw-menu-btn svg {
        width: <?php echo esc_html($w / 1.4); ?>px !important;
        height: <?php echo esc_html($w / 1.4); ?>px !important;
        min-width: auto !important;
        min-height: auto !important;
    }
    .asw-menu, .asw-widget {
        user-select: auto !important;
        outline: 2px !important;
    }
    </style>
    <?php
}
add_action('wp_footer', 'snn_output_accessibility_widget_footer');