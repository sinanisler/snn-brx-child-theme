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

function snn_enqueue_interactions_admin_scripts($hook) {
    if (!isset($_GET['page']) || $_GET['page'] !== 'snn-interactions') {
        return;
    }
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'snn_enqueue_interactions_admin_scripts');

function snn_render_interactions_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Interactions & Animations', 'snn'); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('snn_interactions_settings_group');
                do_settings_sections('snn-interactions');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function snn_register_interactions_settings() {
    register_setting(
        'snn_interactions_settings_group',
        'snn_interactions_settings',
        'snn_sanitize_interactions_settings'
    );

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

    add_settings_field(
        'enable_page_transitions',
        __('Enable Page Transitions', 'snn'),
        'snn_enable_page_transitions_callback',
        'snn-interactions',
        'snn_interactions_section'
    );
}
add_action('admin_init', 'snn_register_interactions_settings');

function snn_sanitize_interactions_settings($input) {
    $sanitized = array();

    // GSAP settings
    $sanitized['enqueue_gsap'] = isset($input['enqueue_gsap']) && $input['enqueue_gsap'] ? 1 : 0;

    // Lenis settings
    $sanitized['enable_lenis'] = isset($input['enable_lenis']) && $input['enable_lenis'] ? 1 : 0;
    $sanitized['lenis_autoRaf'] = isset($input['lenis_autoRaf']) && $input['lenis_autoRaf'] ? 1 : 0;
    $sanitized['lenis_smoothWheel'] = isset($input['lenis_smoothWheel']) && $input['lenis_smoothWheel'] ? 1 : 0;
    $sanitized['lenis_syncTouch'] = isset($input['lenis_syncTouch']) && $input['lenis_syncTouch'] ? 1 : 0;
    $sanitized['lenis_infinite'] = isset($input['lenis_infinite']) && $input['lenis_infinite'] ? 1 : 0;
    $sanitized['lenis_overscroll'] = isset($input['lenis_overscroll']) && $input['lenis_overscroll'] ? 1 : 0;

    // Lenis numeric settings
    $sanitized['lenis_duration'] = isset($input['lenis_duration']) ? floatval($input['lenis_duration']) : 1.2;
    $sanitized['lenis_lerp'] = isset($input['lenis_lerp']) ? floatval($input['lenis_lerp']) : 0.1;
    $sanitized['lenis_wheelMultiplier'] = isset($input['lenis_wheelMultiplier']) ? floatval($input['lenis_wheelMultiplier']) : 1;
    $sanitized['lenis_syncTouchLerp'] = isset($input['lenis_syncTouchLerp']) ? floatval($input['lenis_syncTouchLerp']) : 0.075;
    $sanitized['lenis_touchMultiplier'] = isset($input['lenis_touchMultiplier']) ? floatval($input['lenis_touchMultiplier']) : 1;
    $sanitized['lenis_touchInertiaExponent'] = isset($input['lenis_touchInertiaExponent']) ? floatval($input['lenis_touchInertiaExponent']) : 1.7;

    // Lenis select settings
    $sanitized['lenis_orientation'] = isset($input['lenis_orientation']) ? sanitize_text_field($input['lenis_orientation']) : 'vertical';
    $sanitized['lenis_gestureOrientation'] = isset($input['lenis_gestureOrientation']) ? sanitize_text_field($input['lenis_gestureOrientation']) : 'vertical';
    $sanitized['lenis_easing'] = isset($input['lenis_easing']) ? sanitize_text_field($input['lenis_easing']) : 'default';

    // Page Transitions settings
    $sanitized['enable_page_transitions'] = isset($input['enable_page_transitions']) && $input['enable_page_transitions'] ? 1 : 0;
    $sanitized['page_transition_type'] = isset($input['page_transition_type']) ? sanitize_text_field($input['page_transition_type']) : 'fade';
    $sanitized['page_transition_overlay_color'] = isset($input['page_transition_overlay_color']) ? sanitize_hex_color($input['page_transition_overlay_color']) : '#000000';
    $sanitized['page_transition_show_logo'] = isset($input['page_transition_show_logo']) && $input['page_transition_show_logo'] ? 1 : 0;
    $sanitized['page_transition_logo'] = isset($input['page_transition_logo']) ? absint($input['page_transition_logo']) : 0;
    $sanitized['page_transition_duration'] = isset($input['page_transition_duration']) ? floatval($input['page_transition_duration']) : 1.5;

    return $sanitized;
}

function snn_interactions_section_callback() {
    echo '<p>' . esc_html__( 'Configure animation and interaction settings for your site below.', 'snn' ) . '</p>';
}

/**
 * Get interactions settings with backward compatibility.
 * Checks new location first, falls back to old location if needed.
 *
 * @return array The interactions settings
 */
function snn_get_interactions_settings() {
    $new_settings = get_option('snn_interactions_settings');
    $old_settings = get_option('snn_other_settings');

    // If new settings exist and have at least one interactions-related key, use them
    if ($new_settings && (
        isset($new_settings['enqueue_gsap']) ||
        isset($new_settings['enable_lenis']) ||
        isset($new_settings['enable_page_transitions'])
    )) {
        return $new_settings;
    }

    // Fall back to old settings for backward compatibility
    if ($old_settings) {
        return $old_settings;
    }

    // Return empty array if neither exists
    return array();
}

/**
 * One-time migration of interactions settings from old location to new location.
 * This function runs once and migrates existing settings.
 */
function snn_migrate_interactions_settings() {
    // Check if migration has already been done
    if (get_option('snn_interactions_migrated')) {
        return;
    }

    $old_settings = get_option('snn_other_settings');
    $new_settings = get_option('snn_interactions_settings');

    // Only migrate if old settings exist and new settings don't
    if ($old_settings && !$new_settings) {
        $interactions_keys = array(
            'enqueue_gsap',
            'enable_lenis',
            'lenis_autoRaf',
            'lenis_duration',
            'lenis_lerp',
            'lenis_wheelMultiplier',
            'lenis_smoothWheel',
            'lenis_orientation',
            'lenis_gestureOrientation',
            'lenis_syncTouch',
            'lenis_syncTouchLerp',
            'lenis_touchMultiplier',
            'lenis_touchInertiaExponent',
            'lenis_infinite',
            'lenis_overscroll',
            'lenis_easing',
            'enable_page_transitions',
            'page_transition_type'
        );

        $migrated_settings = array();
        foreach ($interactions_keys as $key) {
            if (isset($old_settings[$key])) {
                $migrated_settings[$key] = $old_settings[$key];
            }
        }

        // Save migrated settings to new location if any were found
        if (!empty($migrated_settings)) {
            update_option('snn_interactions_settings', $migrated_settings);

            // Remove interactions settings from old location
            foreach ($interactions_keys as $key) {
                unset($old_settings[$key]);
            }
            update_option('snn_other_settings', $old_settings);
        }
    }

    // Mark migration as complete
    update_option('snn_interactions_migrated', true);
}
add_action('admin_init', 'snn_migrate_interactions_settings');

function snn_enqueue_gsap_callback() {
    $options = snn_get_interactions_settings();
    ?>
    <input type="checkbox" name="snn_interactions_settings[enqueue_gsap]" value="1" <?php checked(1, isset($options['enqueue_gsap']) ? $options['enqueue_gsap'] : 0); ?>>
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
    $options = snn_get_interactions_settings();
    if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
        wp_enqueue_script('gsap-js', SNN_URL_ASSETS . 'js/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap-st-js', SNN_URL_ASSETS . 'js/ScrollTrigger.min.js', array('gsap-js'), null, true);
        wp_enqueue_script('gsap-data-js', SNN_URL_ASSETS . 'js/gsap-data-animate.js?v0.05', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_gsap_scripts');
add_action('admin_enqueue_scripts', 'snn_enqueue_gsap_scripts');

function snn_enable_lenis_callback() {
    $options = snn_get_interactions_settings();
    $enabled = isset($options['enable_lenis']) ? $options['enable_lenis'] : 0; ?>
    <div class="lenis-settings">
        <input type="checkbox" id="enable_lenis" name="snn_interactions_settings[enable_lenis]" value="1" <?php checked(1, $enabled); ?>> <label for="enable_lenis"><strong><?php _e('Enable Lenis Smooth Scroll', 'snn'); ?></strong></label>
        <p><?php _e('Lenis is a lightweight, robust, and performant smooth scroll library designed for creating smooth scrolling experiences.', 'snn'); ?></p>
        <div class="lenis-config <?php echo $enabled ? '' : 'lenis-disabled'; ?>">
            <h4><?php _e('Basic Settings', 'snn'); ?></h4>
            <div class="lenis-field"><label><input type="checkbox" name="snn_interactions_settings[lenis_autoRaf]" value="1" <?php checked(1, isset($options['lenis_autoRaf']) ? $options['lenis_autoRaf'] : 1); ?>> <?php _e('Auto RAF (Recommended)', 'snn'); ?></label><p class="description"><?php _e('Automatically run requestAnimationFrame loop. Keep this enabled for best performance.', 'snn'); ?></p></div>
            <div class="lenis-field"><label><?php _e('Duration (seconds)', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_interactions_settings[lenis_duration]" value="<?php echo isset($options['lenis_duration']) ? esc_attr($options['lenis_duration']) : '1.2'; ?>"></label><p class="description"><?php _e('Animation duration in seconds. Default: 1.2', 'snn'); ?></p></div>
            <div class="lenis-field"><label><?php _e('Lerp (smoothness)', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.01" min="0.01" max="1" name="snn_interactions_settings[lenis_lerp]" value="<?php echo isset($options['lenis_lerp']) ? esc_attr($options['lenis_lerp']) : '0.1'; ?>"></label><p class="description"><?php _e('Linear interpolation intensity (0.01 to 1). Lower = smoother. Default: 0.1', 'snn'); ?></p></div>
            <div class="lenis-field"><label><?php _e('Wheel Multiplier', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_interactions_settings[lenis_wheelMultiplier]" value="<?php echo isset($options['lenis_wheelMultiplier']) ? esc_attr($options['lenis_wheelMultiplier']) : '1'; ?>"></label><p class="description"><?php _e('Mouse wheel scroll speed. Default: 1', 'snn'); ?></p></div>
            <div class="lenis-field"><label><input type="checkbox" name="snn_interactions_settings[lenis_smoothWheel]" value="1" <?php checked(1, isset($options['lenis_smoothWheel']) ? $options['lenis_smoothWheel'] : 1); ?>> <?php _e('Smooth Wheel Events', 'snn'); ?></label><p class="description"><?php _e('Smooth the scroll initiated by wheel events. Default: enabled', 'snn'); ?></p></div>
            <details class="lenis-accordion">
                <summary class="lenis-field lenis-summary"><?php _e('Advanced Settings', 'snn'); ?></summary>
                <div class="lenis-accordion-content">
                    <div class="lenis-field"><label><?php _e('Orientation', 'snn'); ?>: <select name="snn_interactions_settings[lenis_orientation]" class="lenis-select"><option value="vertical" <?php selected(isset($options['lenis_orientation']) ? $options['lenis_orientation'] : 'vertical', 'vertical'); ?>>Vertical</option><option value="horizontal" <?php selected(isset($options['lenis_orientation']) ? $options['lenis_orientation'] : 'vertical', 'horizontal'); ?>>Horizontal</option></select></label><p class="description"><?php _e('Scrolling orientation. Default: vertical', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Gesture Orientation', 'snn'); ?>: <select name="snn_interactions_settings[lenis_gestureOrientation]" class="lenis-select"><option value="vertical" <?php selected(isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical', 'vertical'); ?>>Vertical</option><option value="horizontal" <?php selected(isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical', 'horizontal'); ?>>Horizontal</option><option value="both" <?php selected(isset($options['lenis_gestureOrientation']) ? $options['lenis_gestureOrientation'] : 'vertical', 'both'); ?>>Both</option></select></label><p class="description"><?php _e('Touch gesture orientation. Default: vertical', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><input type="checkbox" name="snn_interactions_settings[lenis_syncTouch]" value="1" <?php checked(1, isset($options['lenis_syncTouch']) ? $options['lenis_syncTouch'] : 0); ?>> <?php _e('Sync Touch', 'snn'); ?></label><p class="description"><?php _e('Mimic touch device scroll (can be unstable on iOS<16). Default: disabled', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Sync Touch Lerp', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.001" min="0.001" max="1" name="snn_interactions_settings[lenis_syncTouchLerp]" value="<?php echo isset($options['lenis_syncTouchLerp']) ? esc_attr($options['lenis_syncTouchLerp']) : '0.075'; ?>"></label><p class="description"><?php _e('Lerp applied during syncTouch inertia. Default: 0.075', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Touch Multiplier', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_interactions_settings[lenis_touchMultiplier]" value="<?php echo isset($options['lenis_touchMultiplier']) ? esc_attr($options['lenis_touchMultiplier']) : '1'; ?>"></label><p class="description"><?php _e('Touch scroll speed multiplier. Default: 1', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Touch Inertia Exponent', 'snn'); ?>: <input type="number" class="lenis-input-small" step="0.1" min="0.1" max="5" name="snn_interactions_settings[lenis_touchInertiaExponent]" value="<?php echo isset($options['lenis_touchInertiaExponent']) ? esc_attr($options['lenis_touchInertiaExponent']) : '1.7'; ?>"></label><p class="description"><?php _e('Strength of syncTouch inertia. Default: 1.7', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><input type="checkbox" name="snn_interactions_settings[lenis_infinite]" value="1" <?php checked(1, isset($options['lenis_infinite']) ? $options['lenis_infinite'] : 0); ?>> <?php _e('Infinite Scroll', 'snn'); ?></label><p class="description"><?php _e('Enable infinite scrolling. Requires syncTouch on touch devices. Default: disabled', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><input type="checkbox" name="snn_interactions_settings[lenis_overscroll]" value="1" <?php checked(1, isset($options['lenis_overscroll']) ? $options['lenis_overscroll'] : 1); ?>> <?php _e('Overscroll', 'snn'); ?></label><p class="description"><?php _e('Similar to CSS overscroll-behavior. Default: enabled', 'snn'); ?></p></div>
                    <div class="lenis-field"><label><?php _e('Easing Function', 'snn'); ?>: <select name="snn_interactions_settings[lenis_easing]" class="lenis-select"><option value="default" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'default'); ?>>Default (Custom)</option><option value="linear" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'linear'); ?>>Linear</option><option value="easeInQuad" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInQuad'); ?>>Ease In Quad</option><option value="easeOutQuad" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeOutQuad'); ?>>Ease Out Quad</option><option value="easeInOutQuad" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInOutQuad'); ?>>Ease In Out Quad</option><option value="easeInCubic" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInCubic'); ?>>Ease In Cubic</option><option value="easeOutCubic" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeOutCubic'); ?>>Ease Out Cubic</option><option value="easeInOutCubic" <?php selected(isset($options['lenis_easing']) ? $options['lenis_easing'] : 'default', 'easeInOutCubic'); ?>>Ease In Out Cubic</option></select></label><p class="description"><?php _e('Easing function for scroll animation. Default: custom exponential', 'snn'); ?></p></div>
                </div>
            </details>
        </div>
        <style>.lenis-config{margin-top:20px}.lenis-disabled{opacity:0.5;pointer-events:none}.lenis-field{margin-bottom:5px}.lenis-input-small{width:80px}.lenis-select{margin-left:10px}.lenis-accordion{border:1px solid #ddd;padding:15px;border-radius:4px}.lenis-summary{cursor:pointer;font-weight:bold;background:#f0f0f1;padding:10px;border-radius:3px}.lenis-accordion[open] .lenis-summary{margin-bottom:5px}.lenis-accordion-content{margin-top:15px}.lenis-settings label{display:inline-block}.lenis-settings .description{font-size:13px;color:#666}</style>
        <script>document.addEventListener('DOMContentLoaded', function() { const enableCheckbox = document.getElementById('enable_lenis'); const configDiv = document.querySelector('.lenis-config'); if (enableCheckbox && configDiv) { enableCheckbox.addEventListener('change', function() { if (this.checked) { configDiv.classList.remove('lenis-disabled'); } else { configDiv.classList.add('lenis-disabled'); } }); } });</script>
    </div>
<?php }

function snn_enable_page_transitions_callback() {
    $options = snn_get_interactions_settings();
    $enabled = isset($options['enable_page_transitions']) ? $options['enable_page_transitions'] : 0;
    $show_logo = isset($options['page_transition_show_logo']) ? $options['page_transition_show_logo'] : 0;
    $logo_id = isset($options['page_transition_logo']) ? $options['page_transition_logo'] : 0;
    $overlay_color = isset($options['page_transition_overlay_color']) ? $options['page_transition_overlay_color'] : '#000000';
    $duration = isset($options['page_transition_duration']) ? $options['page_transition_duration'] : 1.5;
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
    ?>
    <div class="page-transitions-settings">
        <input type="checkbox" id="enable_page_transitions" name="snn_interactions_settings[enable_page_transitions]" value="1" <?php checked(1, $enabled); ?>> <label for="enable_page_transitions"><strong><?php _e('Enable Page Transitions with View Transition API', 'snn'); ?></strong></label>
        <p style="max-width:800px"><?php _e('The View Transition API provides a mechanism for easily creating animated transitions between different website pages. It allows you to create seamless visual transitions when navigating between pages, improving the user experience.', 'snn'); ?></p>
        <p><?php _e('Learn more:', 'snn'); ?> <a href="https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API" target="_blank">View Transitions API - MDN Web Docs</a></p>
        <div class="page-transitions-config <?php echo $enabled ? '' : 'transitions-disabled'; ?>">
            <h4><?php _e('Transition Settings', 'snn'); ?></h4>
            <div class="transitions-field">
                <label><?php _e('Transition Type', 'snn'); ?>:
                    <select name="snn_interactions_settings[page_transition_type]" class="transitions-select">
                        <option value="fade" <?php selected(isset($options['page_transition_type']) ? $options['page_transition_type'] : 'fade', 'fade'); ?>><?php _e('Fade in and Fade out', 'snn'); ?></option>
                    </select>
                </label>
                <p class="description"><?php _e('Select the type of transition effect to use when navigating between pages. Default: Fade in and Fade out', 'snn'); ?></p>
            </div>

            <div class="transitions-field">
                <label><input type="checkbox" id="page_transition_show_logo" name="snn_interactions_settings[page_transition_show_logo]" value="1" <?php checked(1, $show_logo); ?>> <strong><?php _e('Show Logo Overlay During Transition', 'snn'); ?></strong></label>
                <p class="description"><?php _e('When enabled, displays a colored overlay with your logo during page transitions.', 'snn'); ?></p>
            </div>

            <div class="transitions-logo-settings <?php echo $show_logo ? '' : 'logo-disabled'; ?>">
                <div class="transitions-field">
                    <label><?php _e('Overlay Background Color', 'snn'); ?>:</label>
                    <input type="color" name="snn_interactions_settings[page_transition_overlay_color]" value="<?php echo esc_attr($overlay_color); ?>" class="transitions-color-picker">
                    <p class="description"><?php _e('Choose the background color for the transition overlay. Default: #000000 (black)', 'snn'); ?></p>
                </div>

                <div class="transitions-field">
                    <label><?php _e('Transition Duration (seconds)', 'snn'); ?>:</label>
                    <input type="number" name="snn_interactions_settings[page_transition_duration]" value="<?php echo esc_attr($duration); ?>" class="transitions-duration-input" step="0.1" min="0.5" max="5">
                    <p class="description"><?php _e('Total duration of the overlay transition effect in seconds. Default: 1.5s', 'snn'); ?></p>
                </div>

                <div class="transitions-field">
                    <label><?php _e('Transition Logo', 'snn'); ?>:</label>
                    <div class="transitions-logo-wrapper">
                        <input type="hidden" id="page_transition_logo" name="snn_interactions_settings[page_transition_logo]" value="<?php echo esc_attr($logo_id); ?>">
                        <div id="transitions-logo-preview" class="transitions-logo-preview">
                            <?php if ($logo_url) : ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo Preview">
                            <?php endif; ?>
                        </div>
                        <button type="button" id="transitions-logo-upload" class="button"><?php _e('Select Logo', 'snn'); ?></button>
                        <button type="button" id="transitions-logo-remove" class="button" <?php echo !$logo_id ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'snn'); ?></button>
                    </div>
                    <p class="description"><?php _e('Select an image to display as the logo during page transitions.', 'snn'); ?></p>
                </div>
            </div>
        </div>
        <style>
            .page-transitions-config{margin-top:20px}
            .transitions-disabled{opacity:0.5;pointer-events:none}
            .logo-disabled{opacity:0.5;pointer-events:none}
            .transitions-field{margin-bottom:15px}
            .transitions-select{margin-left:10px;min-width:200px}
            .page-transitions-settings label{display:inline-block}
            .page-transitions-settings .description{font-size:13px;color:#666;margin-top:5px}
            .transitions-color-picker{vertical-align:middle;margin-left:10px;width:60px;height:30px;padding:0;border:1px solid #ccc;cursor:pointer}
            .transitions-duration-input{margin-left:10px;width:80px}
            .transitions-logo-wrapper{margin-top:10px;display:flex;align-items:center;gap:10px}
            .transitions-logo-preview{width:100px;height:100px;border:2px dashed #ccc;display:flex;align-items:center;justify-content:center;background:#f9f9f9}
            .transitions-logo-preview img{max-width:100%;max-height:100%;object-fit:contain}
            .transitions-logo-settings{margin-top:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px}
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var enableCheckbox = document.getElementById('enable_page_transitions');
                var configDiv = document.querySelector('.page-transitions-config');
                var showLogoCheckbox = document.getElementById('page_transition_show_logo');
                var logoSettings = document.querySelector('.transitions-logo-settings');

                if (enableCheckbox && configDiv) {
                    enableCheckbox.addEventListener('change', function() {
                        configDiv.classList.toggle('transitions-disabled', !this.checked);
                    });
                }

                if (showLogoCheckbox && logoSettings) {
                    showLogoCheckbox.addEventListener('change', function() {
                        logoSettings.classList.toggle('logo-disabled', !this.checked);
                    });
                }

                // Media uploader
                var uploadBtn = document.getElementById('transitions-logo-upload');
                var removeBtn = document.getElementById('transitions-logo-remove');
                var logoInput = document.getElementById('page_transition_logo');
                var logoPreview = document.getElementById('transitions-logo-preview');
                var mediaFrame;

                if (uploadBtn) {
                    uploadBtn.addEventListener('click', function(e) {
                        e.preventDefault();

                        if (mediaFrame) {
                            mediaFrame.open();
                            return;
                        }

                        mediaFrame = wp.media({
                            title: '<?php _e('Select Transition Logo', 'snn'); ?>',
                            button: { text: '<?php _e('Use this image', 'snn'); ?>' },
                            multiple: false
                        });

                        mediaFrame.on('select', function() {
                            var attachment = mediaFrame.state().get('selection').first().toJSON();
                            logoInput.value = attachment.id;
                            var imgUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                            logoPreview.innerHTML = '<img src="' + imgUrl + '" alt="Logo Preview">';
                            removeBtn.style.display = '';
                        });

                        mediaFrame.open();
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        logoInput.value = '';
                        logoPreview.innerHTML = '';
                        this.style.display = 'none';
                    });
                }
            });
        </script>
    </div>
<?php }

function snn_enqueue_lenis_scripts() {
    $options = snn_get_interactions_settings();

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

/**
 * Add View Transition overlay element to footer
 */
function snn_add_view_transition_overlay() {
    $options = snn_get_interactions_settings();

    if (isset($options['enable_page_transitions']) && $options['enable_page_transitions']) {
        $show_logo = isset($options['page_transition_show_logo']) && $options['page_transition_show_logo'];
        $logo_id = isset($options['page_transition_logo']) ? $options['page_transition_logo'] : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        ?>
        <div id="snn-transition-overlay"<?php echo $show_logo && $logo_url ? ' data-has-logo="true"' : ''; ?>>
            <?php if ($show_logo && $logo_url) : ?>
                <div class="snn-transition-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Loading">
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
add_action('wp_footer', 'snn_add_view_transition_overlay', 100);

/**
 * Enqueue View Transition styles and scripts
 */
function snn_enqueue_page_transitions() {
    $options = snn_get_interactions_settings();

    if (isset($options['enable_page_transitions']) && $options['enable_page_transitions']) {
        $transition_type = isset($options['page_transition_type']) ? $options['page_transition_type'] : 'fade';
        $show_logo = isset($options['page_transition_show_logo']) && $options['page_transition_show_logo'];
        $overlay_color = isset($options['page_transition_overlay_color']) ? $options['page_transition_overlay_color'] : '#000000';
        $duration = isset($options['page_transition_duration']) ? floatval($options['page_transition_duration']) : 1.5;

        // CSS for View Transitions
        $inline_css = "
:root {
    --snn-transition-duration: " . $duration . "s;
}

/* View Transition Overlay */
#snn-transition-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: " . esc_attr($overlay_color) . ";
    display: none;
    z-index: 999999;
    pointer-events: none;
}

#snn-transition-overlay[data-has-logo] {
    view-transition-name: snn-overlay;
}

.snn-transition-logo {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.snn-transition-logo img {
    max-width: 200px;
    max-height: 200px;
    object-fit: contain;
}

/* Page content transitions */
::view-transition-old(root) {
    animation: 90ms cubic-bezier(0.4, 0, 1, 1) both snn-fade-out;
}

::view-transition-new(root) {
    animation: 400ms cubic-bezier(0, 0, 0.2, 1) both snn-fade-in;
    animation-delay: calc(var(--snn-transition-duration) * 0.5);
}

/* Overlay group animation */
::view-transition-group(snn-overlay) {
    animation-duration: var(--snn-transition-duration);
    animation-timing-function: ease-in-out;
}

/* Overlay animation */
::view-transition-new(snn-overlay) {
    animation: snn-overlay-in-out var(--snn-transition-duration) forwards;
}

@keyframes snn-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes snn-fade-out {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes snn-overlay-in-out {
    0% { opacity: 0; transform: scale(1.1); }
    15% { opacity: 1; transform: scale(1); }
    80% { opacity: 1; }
    100% { opacity: 0; }
}
";

        // JavaScript for View Transitions (native JS, no jQuery)
        $inline_script = "
(function() {
    if (!document.startViewTransition) {
        return;
    }

    function isInternalLink(url) {
        try {
            var linkUrl = new URL(url, window.location.origin);
            return linkUrl.origin === window.location.origin &&
                   !linkUrl.hash &&
                   !url.startsWith('mailto:') &&
                   !url.startsWith('tel:') &&
                   linkUrl.href !== window.location.href;
        } catch (e) {
            return false;
        }
    }

    function showOverlay() {
        var overlay = document.getElementById('snn-transition-overlay');
        if (overlay && overlay.dataset.hasLogo) {
            overlay.style.display = 'flex';
        }
    }

    function hideOverlay() {
        var overlay = document.getElementById('snn-transition-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function attachTransitionListeners() {
        document.querySelectorAll('a').forEach(function(link) {
            if (link.dataset.snnTransition) return;
            if (link.target === '_blank') return;
            if (!isInternalLink(link.href)) return;

            link.dataset.snnTransition = 'true';

            link.addEventListener('click', function(e) {
                e.preventDefault();
                var url = this.href;

                var transition = document.startViewTransition(function() {
                    return fetch(url)
                        .then(function(response) { return response.text(); })
                        .then(function(html) {
                            var parser = new DOMParser();
                            var doc = parser.parseFromString(html, 'text/html');

                            // Update the page
                            document.documentElement.innerHTML = doc.documentElement.innerHTML;

                            // Update URL
                            history.pushState(null, '', url);

                            // Show overlay for animation (only if logo enabled)
                            showOverlay();

                            // Re-attach listeners
                            attachTransitionListeners();
                        });
                });

                transition.finished.then(function() {
                    hideOverlay();
                    window.scrollTo(0, 0);
                });
            });
        });
    }

    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        var transition = document.startViewTransition(function() {
            return fetch(window.location.href)
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    document.documentElement.innerHTML = doc.documentElement.innerHTML;
                    showOverlay();
                    attachTransitionListeners();
                });
        });

        transition.finished.then(function() {
            hideOverlay();
        });
    });

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachTransitionListeners);
    } else {
        attachTransitionListeners();
    }
})();
";

        // Register and enqueue styles
        wp_register_style('snn-view-transitions', false);
        wp_enqueue_style('snn-view-transitions');
        wp_add_inline_style('snn-view-transitions', $inline_css);

        // Register and enqueue script
        wp_register_script('snn-view-transitions', false, array(), false, true);
        wp_enqueue_script('snn-view-transitions');
        wp_add_inline_script('snn-view-transitions', $inline_script);
    }
}
add_action('wp_enqueue_scripts', 'snn_enqueue_page_transitions');




