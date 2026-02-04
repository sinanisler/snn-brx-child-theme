<?php
// The Page for Interactions and Animations Settings
function snn_add_interactions_page() {
    add_submenu_page(
        'snn-settings',
        __('Interactions', 'snn'),
        __('Interactions', 'snn'),
        'manage_options',
        'snn-interactions',
        'snn_render_interactions_page'
    );
}
add_action('admin_menu', 'snn_add_interactions_page');

function snn_render_interactions_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Interactions & Animations', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_other_settings_group');
                do_settings_sections('snn-interactions');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_interactions_settings() {
    add_settings_section(
        'snn_interactions_section',
        __('Animation Settings', 'snn'),
        'snn_interactions_section_callback',
        'snn-interactions'
    );

    add_settings_field(
        'enqueue_gsap',
        __('Enable GSAP and GSAP Elements', 'snn'),
        'snn_enqueue_gsap_callback',
        'snn-interactions',
        'snn_interactions_section'
    );

    add_settings_field(
        'enable_lenis',
        __('Enable Lenis Smooth Scroll', 'snn'),
        'snn_enable_lenis_callback',
        'snn-interactions',
        'snn_interactions_section'
    );
}
add_action('admin_init', 'snn_register_interactions_settings');

function snn_interactions_section_callback() {
    echo '<p>' . esc_html__( 'Configure animation and interaction settings for your site below.', 'snn' ) . '</p>';
}

function snn_enqueue_gsap_callback() {
    $options = get_option('snn_other_settings');
    ?>
    <input type="checkbox" name="snn_other_settings[enqueue_gsap]" value="1" <?php checked(1, isset($options['enqueue_gsap']) ? $options['enqueue_gsap'] : 0); ?>>
    <p>
        <?php _e('Enabling this setting will enqueue the GSAP library and its associated scripts on your website.', 'snn'); ?><br>
        <?php _e('GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.', 'snn'); ?><br><br>
        - <?php _e('Ability to create GSAP animations with just data-animate attributes.', 'snn'); ?><br>
        - <?php _e('gsap.min.php: The core GSAP library.', 'snn'); ?><br>
        - <?php _e('ScrollTrigger.min.php: A GSAP plugin that enables scroll-based animations.', 'snn'); ?><br>
        - <?php _e('gsap-data-animate.php: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.', 'snn'); ?><br>
    </p>
    <?php
}

function snn_enqueue_gsap_scripts() {
    $options = get_option('snn_other_settings');
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', SNN_URL_ASSETS . 'js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', SNN_URL_ASSETS . 'js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', SNN_URL_ASSETS . 'js/gsap-data-animate.js?v0.05', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');
add_action('admin_enqueue_scripts', 'snn_enqueue_gsap_scripts');

function snn_enable_lenis_callback() {
    $options = get_option('snn_other_settings');
    $enabled = isset($options['enable_lenis']) ? $options['enable_lenis'] : 0; ?>
    <div class="lenis-settings">
        <input type="checkbox" id="enable_lenis" name="snn_other_settings[enable_lenis]" value="1" <?php checked(1, $enabled); ?>> <label for="enable_lenis"><strong><?php _e('Enable Lenis Smooth Scroll', 'snn'); ?></strong></label>
        <p><?php _e('Lenis is a lightweight, robust, and performant smooth scroll library designed for creating smooth scrolling experiences.', 'snn'); ?></p>
        <div class="lenis-config <?php echo $enabled ? '' : 'lenis-disabled'; ?>">
            <h4><?php _e('Basic Settings', 'snn'); ?></h4>
            <div class="lenis-field"><label><input type="checkbox" name="snn_other_settings[lenis_autoRaf]" value="1" <?php checked(1, isset($options['lenis_autoRaf']) ? $options['lenis_autoRaf'] : 1); ?>> <?php _e('Auto RAF (Recommended)', 'snn'); ?></label><p class="description"><?php _e('Automatically run requestAnimationFrame loop. Keep this enabled for best performance.', 'snn'); ?></p></div>
            <div class="lenis-field"><label><?php _e('Duration (seconds)', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_other_settings[lenis_duration]" value="<?php echo isset($options['lenis_duration']) ? esc_attr($options['lenis_duration']) : '1.2'; ?>"></label><p class="description"><?php _e('Animation duration in seconds. Default: 1.2', 'snn'); ?></p></div>
            <div class="lenis-field"><label><?php _e('Lerp (smoothness)', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.01" min="0.01" max="1" name="snn_other_settings[lenis_lerp]" value="<?php echo isset($options['lenis_lerp']) ? esc_attr($options['lenis_lerp']) : '0.1'; ?>"></label><p class="description"><?php _e('Linear interpolation intensity (0.01 to 1). Lower = smoother. Default: 0.1', 'snn'); ?></p></div>
            <div class="lenis-field"><label><?php _e('Wheel Multiplier', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_other_settings[lenis_wheelMultiplier]" value="<?php echo isset($options['lenis_wheelMultiplier']) ? esc_attr($options['lenis_wheelMultiplier']) : '1'; ?>"></label><p class="description"><?php _e('Mouse wheel scroll speed. Default: 1', 'snn'); ?></p></div>
            <div class="lenis-field"><label><input type="checkbox" name="snn_other_settings[lenis_smoothWheel]" value="1" <?php checked(1, isset($options['lenis_smoothWheel']) ? $options['lenis_smoothWheel'] : 1); ?>> <?php _e('Smooth Wheel Events', 'snn'); ?></label><p class="description"><?php _e('Smooth the scroll initiated by wheel events. Default: enabled', 'snn'); ?></p></div>
            <details class="lenis-accordion">
                <summary class="lenis-field lenis-summary"><?php _e('Advanced Settings', 'snn'); ?></summary>
                <div class="lenis-accordion-content">
                    <div class="lenis-field"><label><?php _e('Orientation', 'snn'); ?>: <select name="snn_other_settings[lenis_orientation]" class="lenis-select"><option value="vertical" <?php selected(isset($options['lenis_orientation']) ? $options['lenis_orientation'] : 'vertical', 'vertical'); ?>>Vertical</option><option value="horizontal" <?php selected(isset($options['lenis_orientation']) ? $options['lenis_orientation'] : 'vertical', 'horizontal'); ?>>Horizontal</option></select></label><p class="description"><?php _e('Scrolling orientation. Default: vertical', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Gesture Orientation', 'snn'); ?>: <select name="snn_other_settings[lenis_gestureOrientation]" class="lenis-select"><option value="vertical" <?php selected(isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical', 'vertical'); ?>>Vertical</option><option value="horizontal" <?php selected(isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical', 'horizontal'); ?>>Horizontal</option><option value="both" <?php selected(isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical', 'both'); ?>>Both</option></select></label><p class="description"><?php _e('Touch gesture orientation. Default: vertical', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><input type="checkbox" name="snn_other_settings[lenis_syncTouch]" value="1" <?php checked(1, isset($options['lenis_syncTouch']) ? $options['lenis_syncTouch'] : 0); ?>> <?php _e('Sync Touch', 'snn'); ?></label><p class="description"><?php _e('Mimic touch device scroll (can be unstable on iOS<16). Default: disabled', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Sync Touch Lerp', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.001" min="0.001" max="1" name="snn_other_settings[lenis_syncTouchLerp]" value="<?php echo isset($options['lenis_syncTouchLerp']) ? esc_attr($options['lenis_syncTouchLerp']) : '0.075'; ?>"></label><p class="description"><?php _e('Lerp applied during syncTouch inertia. Default: 0.075', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Touch Multiplier', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_other_settings[lenis_touchMultiplier]" value="<?php echo isset($options['lenis_touchMultiplier']) ? esc_attr($options['lenis_touchMultiplier']) : '1'; ?>"></label><p class="description"><?php _e('Touch scroll speed multiplier. Default: 1', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Touch Inertia Exponent', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_other_settings[lenis_touchInertiaExponent]" value="<?php echo isset($options['lenis_touchInertiaExponent']) ? esc_attr($options['lenis_touchInertiaExponent']) : '1.7'; ?>"></label><p class="description"><?php _e('Strength of syncTouch inertia. Default: 1.7', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><input type="checkbox" name="snn_other_settings[lenis_infinite]" value="1" <?php checked(1, isset($options['lenis_infinite']) ? $options['lenis_infinite'] : 0); ?>> <?php _e('Infinite Scroll', 'snn'); ?></label><p class="description"><?php _e('Enable infinite scrolling. Requires syncTouch on touch devices. Default: disabled', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><input type="checkbox" name="snn_other_settings[lenis_overscroll]" value="1" <?php checked(1, isset($options['lenis_overscroll']) ? $options['lenis_overscroll'] : 1); ?>> <?php _e('Overscroll', 'snn'); ?></label><p class="description"><?php _e('Similar to CSS overscroll-behavior. Default: enabled', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Easing Function', 'snn'); ?>: <select name="snn_other_settings[lenis_easing]" class="lenis-select"><option value="default" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'default'); ?>>Default (Custom)</option><option value="linear" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'linear'); ?>>Linear</option><option value="easeInQuad" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInQuad'); ?>>Ease In Quad</option><option value="easeOutQuad" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeOutQuad'); ?>>Ease Out Quad</option><option value="easeInOutQuad" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInOutQuad'); ?>>Ease In Out Quad</option><option value="easeInCubic" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInCubic'); ?>>Ease In Cubic</option><option value="easeOutCubic" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeOutCubic'); ?>>Ease Out Cubic</option><option value="easeInOutCubic" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInOutCubic'); ?>>Ease In Out Cubic</option></select></label><p class="description"><?php _e('Easing function for scroll animation. Default: custom exponential', 'snn'); ?></p></div>
                </div>
            </details>
        </div>
        <style>.lenis-config{margin-top:20px}.lenis-disabled{opacity:0.5;pointer-events:none}.lenis-field{margin-bottom:5px}.lenis-input-small{width:80px}.lenis-select{margin-left:10px}.lenis-accordion{border:1px solid #ddd;padding:15px;border-radius:4px}.lenis-summary{cursor:pointer;font-weight:bold;background:#f0f0f1;padding:10px;border-radius:3px}.lenis-accordion[open] .lenis-summary{margin-bottom:5px}.lenis-accordion-content{margin-top:15px}.lenis-settings label{display:inline-block}.lenis-settings .description{font-size:13px;color:#666}</style>
        <script>document.addEventListener('DOMContentLoaded', function() { const enableCheckbox = document.getElementById('enable_lenis'); const configDiv = document.querySelector('.lenis-config'); if (enableCheckbox && configDiv) { enableCheckbox.addEventListener('change', function() { if (this.checked) { configDiv.classList.remove('lenis-disabled'); } else { configDiv.classList.add('lenis-disabled'); } }); } });</script>
    </div>
<?php }

function snn_enqueue_lenis_scripts() {
    $options = get_option('snn_other_settings');

    if (isset($options['enable_lenis']) && $options['enable_lenis']) {
        // Enqueue Lenis library
        wp_enqueue_script('lenis-js', SNN_URL_ASSETS . 'js/lenis.min.js', array(), null, true);

        // Get settings with defaults
        $autoRaf = isset($options['lenis_autoRaf']) ? $options['lenis_autoRaf'] : 1;
        $duration = isset($options['lenis_duration']) ? floatval($options['lenis_duration']) : 1.2;
        $lerp = isset($options['lenis_lerp']) ? floatval($options['lenis_lerp']) : 0.1;
        $wheelMultiplier = isset($options['lenis_wheelMultiplier']) ? floatval($options['lenis_wheelMultiplier']) : 1;
        $smoothWheel = isset($options['lenis_smoothWheel']) ? $options['lenis_smoothWheel'] : 1;

        // Advanced settings
        $orientation = isset($options['lenis_orientation']) ? $options['lenis_orientation'] : 'vertical';
        $gestureOrientation = isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical';
        $syncTouch = isset($options['lenis_syncTouch']) ? $options['lenis_syncTouch'] : 0;
        $syncTouchLerp = isset($options['lenis_syncTouchLerp']) ? floatval($options['lenis_syncTouchLerp']) : 0.075;
        $touchMultiplier = isset($options['lenis_touchMultiplier']) ? floatval($options['lenis_touchMultiplier']) : 1;
        $touchInertiaExponent = isset($options['lenis_touchInertiaExponent']) ? floatval($options['lenis_touchInertiaExponent']) : 1.7;
        $infinite = isset($options['lenis_infinite']) ? $options['lenis_infinite'] : 0;
        $overscroll = isset($options['lenis_overscroll']) ? $options['lenis_overscroll'] : 1;
        $easing = isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default';

        // Easing function mapping
        $easingFunctions = array(
            'default' => '(t) => Math.min(1, 1.001 - Math.pow(2, -10 * t))',
            'linear' => '(t) => t',
            'easeInQuad' => '(t) => t * t',
            'easeOutQuad' => '(t) => t * (2 - t)',
            'easeInOutQuad' => '(t) => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t',
            'easeInCubic' => '(t) => t * t * t',
            'easeOutCubic' => '(t) => (--t) * t * t + 1',
            'easeInOutCubic' => '(t) => t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1'
        );

        $easingFunction = isset($easingFunctions[$easing]) ? $easingFunctions[$easing] : $easingFunctions['default'];

        // Build Lenis configuration
        $inline_script = "
        // Check if URL contains ?bricks=run
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('bricks') || urlParams.get('bricks') !== 'run') {
            const lenis = new Lenis({
                autoRaf: " . ($autoRaf ? 'true' : 'false') . ",
                duration: " . $duration . ",
                easing: " . $easingFunction . ",
                lerp: " . $lerp . ",
                wheelMultiplier: " . $wheelMultiplier . ",
                smoothWheel: " . ($smoothWheel ? 'true' : 'false') . ",
                orientation: '" . esc_js($orientation) . "',
                gestureOrientation: '" . esc_js($gestureOrientation) . "',
                syncTouch: " . ($syncTouch ? 'true' : 'false') . ",
                syncTouchLerp: " . $syncTouchLerp . ",
                touchMultiplier: " . $touchMultiplier . ",
                touchInertiaExponent: " . $touchInertiaExponent . ",
                infinite: " . ($infinite ? 'true' : 'false') . ",
                overscroll: " . ($overscroll ? 'true' : 'false') . "
            });
        }
        ";

        wp_add_inline_script('lenis-js', $inline_script);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_lenis_scripts');




