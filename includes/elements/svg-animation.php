<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Custom Bricks element for SVG SMIL Animations.
 *
 * Allows users to paste SVG code with SMIL or CSS animations
 * and control its playbook (autoplay, loop, speed, hover/click triggers for SMIL).
 * CSS animations within the SVG will play according to their own CSS rules.
 */
class Custom_Element_SvgAnimation_Code extends \Bricks\Element {

    public $category     = 'snn';
    public $name         = 'svg_animation_code';
    public $icon         = 'fas fa-code';
    public $css_selector = '.svg-animation-wrapper';

    public function get_label() {
        return esc_html__( 'SVG Animation', 'bricks' );
    }

    public function set_controls() {
        $this->controls['svg_code'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'SVG Code with SMIL/CSS Animations', 'bricks' ),
            'type'        => 'code',
            'mode'        => 'htmlmixed',
            'theme'       => 'dracula',
            'placeholder' => esc_html__( '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">...</svg>', 'bricks' ),
            'description' => esc_html__( 'Paste your SVG code here. For SMIL animations you want to control with these settings (autoplay, hover, click, speed, loop), ensure their "begin" attribute is set to "indefinite" for best results. CSS animations will play based on their own defined styles.', 'bricks' ),
        ];

        $this->controls['loop'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Loop Animation (SMIL)', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => esc_html__( 'If checked, makes SMIL animations loop indefinitely. Affects SMIL only.', 'bricks' ),
        ];

        $this->controls['autoplay'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Autoplay Animation (SMIL)', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => true,
            'description' => esc_html__( 'If checked, SMIL animations will attempt to play automatically on load. Affects SMIL only.', 'bricks' ),
        ];

        $this->controls['play_on_hover'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Play on Hover (SMIL)', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => esc_html__( 'If checked, SMIL animations will play when the mouse hovers over the element. Affects SMIL only.', 'bricks' ),
        ];

        $this->controls['reset_on_mouse_leave'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Reset on Mouse Leave (SMIL)', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => esc_html__( 'If "Play on Hover" is active, checking this will reset SMIL animations to their beginning when the mouse leaves. Affects SMIL only.', 'bricks' ),
            'requires'    => [['play_on_hover', '=', true]],
        ];

        $this->controls['pause_on_mouse_leave'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Pause on Mouse Leave (SMIL)', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => esc_html__( 'If "Play on Hover" is active and "Reset on Mouse Leave" is not, checking this will pause SMIL animations when the mouse leaves. Affects SMIL only.', 'bricks' ),
            'requires'    => [['play_on_hover', '=', true], ['reset_on_mouse_leave', '!=', true]],
        ];

        $this->controls['play_on_click'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Play on Click (SMIL)', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => esc_html__( 'If checked, SMIL animations will play when the element is clicked (if not already a link). Affects SMIL only.', 'bricks' ),
        ];

        $this->controls['animation_speed'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Animation Speed (SMIL)', 'bricks' ),
            'type'        => 'number',
            'default'     => 1.0,
            'step'        => 0.1,
            'min'         => 0.01,
            'max'         => 10.0,
            'description' => esc_html__( 'Adjusts the speed of SMIL animations. 1 is normal speed. Affects SMIL only.', 'bricks' ),
        ];

        $this->controls['animation_height'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Animation Height (px)', 'bricks' ),
            'type'        => 'number',
            'default'     => 300,
            'min'         => 50,
            'step'        => 10,
            'description' => esc_html__( 'Sets the container height for the SVG.', 'bricks' ),
        ];

        $this->controls['animation_link'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Link', 'bricks' ),
            'type'        => 'link',
            'pasteStyles' => false,
            'placeholder' => esc_html__( 'https://example.com', 'bricks' ),
            'description' => esc_html__( 'Make the entire SVG animation a clickable link.', 'bricks' ),
        ];
    }

    public function enqueue_scripts() {
        // JavaScript is included inline.
    }

    public function render() {
        $settings = $this->settings;

        $svg_content = isset($settings['svg_code']) ? $settings['svg_code'] : '';

        // --- CORRECTED Boolean control settings from PHP ---
        $loop                 = isset($settings['loop']) && $settings['loop'];
        $autoplay             = isset($settings['autoplay']) && $settings['autoplay']; // Default is true, so this will be true if key is missing unless explicitly set false by Bricks
        // For checkboxes with a default of true, if Bricks doesn't send the key when it's true by default and only sends when it's explicitly set to false, this might need adjustment.
        // However, Bricks usually sends '1' or true if checked, and key is absent or value is false/'0' if unchecked.
        // Assuming default 'true' for autoplay means if key is absent, it should be true. Let's adjust autoplay for this common pattern.
        $autoplay             = (isset($settings['autoplay']) && !$settings['autoplay']) ? false : true; // If 'autoplay' is set AND it's false, then false. Otherwise true (default or checked).

        $play_on_hover        = isset($settings['play_on_hover']) && $settings['play_on_hover'];
        $reset_on_mouse_leave = isset($settings['reset_on_mouse_leave']) && $settings['reset_on_mouse_leave'];
        $pause_on_mouse_leave = isset($settings['pause_on_mouse_leave']) && $settings['pause_on_mouse_leave'];
        $play_on_click        = isset($settings['play_on_click']) && $settings['play_on_click'];
        
        $animation_speed      = isset($settings['animation_speed']) ? floatval($settings['animation_speed']) : 1.0;
        if ($animation_speed <= 0) $animation_speed = 1.0;

        $animation_height = isset($settings['animation_height']) ? intval($settings['animation_height']) : 300;

        $animation_link = isset($settings['animation_link']) ? $settings['animation_link'] : null;
        $link_url       = isset($animation_link['url']) ? esc_url($animation_link['url']) : '';
        $link_target    = isset($animation_link['target']) && $animation_link['target'] ? ' target="_blank"' : '';
        $link_nofollow  = isset($animation_link['rel']) && strpos($animation_link['rel'], 'nofollow') !== false ? ' rel="nofollow"' : '';

        if ( empty( trim( $svg_content ) ) ) {
            echo '<div class="bricks-placeholder" style="height: ' . esc_attr($animation_height) . 'px; display: flex; align-items: center; justify-content: center; background-color: #f0f0f0; border: 1px dashed #ccc; color: #777; text-align: center; padding: 15px;">'
                 . esc_html__( 'SVG Animation: Please paste your SVG code in the element settings.', 'bricks' )
                 . '</div>';
            return;
        }

        $animation_id = 'svg-animation-' . $this->id;
        ob_start();

        if ( ! empty( $link_url ) ) {
            echo '<a href="' . esc_url($link_url) . '"' . $link_target . $link_nofollow . ' class="svg-animation-link" style="display: block; width: 100%; text-decoration: none; line-height: 0;">';
        }

        $this->set_attribute( '_root', 'id', esc_attr( $animation_id ) );
        $this->set_attribute( '_root', 'class', 'svg-animation-wrapper' );
        $cursor_style = ($play_on_click && empty($link_url)) ? " cursor: pointer;" : "";
        $this->set_attribute( '_root', 'style', "height: " . esc_attr($animation_height) . "px; width: 100%; max-width: 100%; overflow: hidden;" . $cursor_style );

        echo "<div {$this->render_attributes( '_root' )}>";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $svg_content;
        echo "</div>";

        if ( ! empty( $link_url ) ) {
            echo '</a>';
        }
        ?>
        <script>
        (function() {
            function initSVGAnimation_<?php echo esc_js(str_replace('-', '_', $this->id)); ?>() {
                const animationId = '<?php echo esc_js($animation_id); ?>';
                const container = document.getElementById(animationId);

                if (!container) {
                    console.warn('SVG Animation (<?php echo esc_js($animation_id); ?>): Container element not found.');
                    return;
                }
                
                const svgElement = container.querySelector('svg');
                if (!svgElement) {
                    console.warn('SVG Animation (<?php echo esc_js($animation_id); ?>): SVG element not found within container.');
                    return;
                }

                const loop = <?php echo $loop ? 'true' : 'false'; ?>;
                const autoplay = <?php echo $autoplay ? 'true' : 'false'; ?>;
                const playOnHover = <?php echo $play_on_hover ? 'true' : 'false'; ?>;
                const resetOnMouseLeave = <?php echo $reset_on_mouse_leave ? 'true' : 'false'; ?>;
                const pauseOnMouseLeave = <?php echo $pause_on_mouse_leave ? 'true' : 'false'; ?>;
                const playOnClick = <?php echo $play_on_click ? 'true' : 'false'; ?>;
                const animationSpeed = <?php echo floatval($animation_speed); ?>;

                let animationElements = Array.from(svgElement.querySelectorAll('animate, animateTransform, animateMotion'));
                
                if (animationElements.length === 0) {
                    // console.log('SVG Animation (<?php echo esc_js($animation_id); ?>): No SMIL animation elements found to control.');
                    return; 
                }

                let originalDurations = new Map();
                let originalRepeatCounts = new Map();

                animationElements.forEach(anim => {
                    originalDurations.set(anim, anim.getAttribute('dur'));
                    const initialRepeatCount = anim.getAttribute('repeatCount');
                    originalRepeatCounts.set(anim, initialRepeatCount ? initialRepeatCount : '1'); 

                    if (anim.tagName.toLowerCase() === 'animatetransform') {
                        const currentTo = anim.getAttribute('to');
                        if (!currentTo || currentTo.trim() === '') {
                            const transformType = anim.getAttribute('type') || 'translate';
                            switch(transformType.toLowerCase()) {
                                case 'translate': anim.setAttribute('to', '0 0'); break;
                                case 'rotate': anim.setAttribute('to', '0'); break;
                                case 'scale': anim.setAttribute('to', '1'); break;
                                default: anim.setAttribute('to', '0 0');
                            }
                        }
                    }

                    if (!anim.getAttribute('begin')) {
                        anim.setAttribute('begin', 'indefinite');
                    }

                    const originalDurValue = originalDurations.get(anim);
                    if (animationSpeed !== 1.0 && animationSpeed > 0 && originalDurValue) {
                        const durationInSeconds = parseSmilDuration(originalDurValue);
                        if (durationInSeconds !== null && !isNaN(durationInSeconds)) {
                            anim.setAttribute('dur', formatSmilDuration(durationInSeconds / animationSpeed));
                        }
                    }

                    // Apply loop setting
                    if (loop) {
                        anim.setAttribute('repeatCount', 'indefinite');
                    } else {
                        const originalRcValue = originalRepeatCounts.get(anim);
                        if (originalRcValue && originalRcValue.toLowerCase() !== 'indefinite' && !isNaN(parseFloat(originalRcValue)) && parseFloat(originalRcValue) > 0) {
                            anim.setAttribute('repeatCount', originalRcValue);
                        } else {
                            anim.setAttribute('repeatCount', '1'); // Default to play once if loop is off and original wasn't a positive finite number
                        }
                    }
                });

                function parseSmilDuration(durStr) {
                    if (!durStr) return null;
                    durStr = String(durStr).trim();
                    let seconds = 0;
                    const clockMatch = durStr.match(/^(?:(\d{1,2}):)?(?:(\d{1,2}):)?(\d+(?:\.\d+)?)$/);

                    if (clockMatch) {
                        const h = parseInt(clockMatch[1], 10) || 0;
                        const m = parseInt(clockMatch[2], 10) || 0;
                        const s = parseFloat(clockMatch[3]) || 0;
                         if (clockMatch[1] !== undefined && clockMatch[2] !== undefined) { // HH:MM:SS
                            seconds = (parseInt(clockMatch[1], 10) * 3600) + (parseInt(clockMatch[2], 10) * 60) + parseFloat(clockMatch[3]);
                        } else if (clockMatch[1] !== undefined) { // MM:SS (clockMatch[1] is minutes, clockMatch[3] is secs)
                            seconds = (parseInt(clockMatch[1], 10) * 60) + parseFloat(clockMatch[3]);
                        } else { // SS
                            seconds = parseFloat(clockMatch[3]);
                        }
                    } else if (durStr.endsWith('ms')) {
                        seconds = parseFloat(durStr) / 1000;
                    } else if (durStr.endsWith('s')) {
                        seconds = parseFloat(durStr);
                    } else if (!isNaN(parseFloat(durStr))) {
                        seconds = parseFloat(durStr);
                    } else {
                        return null;
                    }
                    return isNaN(seconds) ? null : seconds;
                }

                function formatSmilDuration(seconds) {
                    return Math.max(0.001, seconds).toFixed(3) + 's';
                }

                function playAllSmilAnimations() {
                    try {
                        if (svgElement && typeof svgElement.setCurrentTime === 'function') {
                            svgElement.setCurrentTime(0); 
                        }
                        animationElements.forEach(anim => {
                            try {
                                if (typeof anim.beginElement === 'function') {
                                    anim.beginElement();
                                }
                            } catch(e) { console.warn("SVG Animation (<?php echo esc_js($animation_id); ?>): Could not start SMIL element:", anim, e); }
                        });
                        if (svgElement && typeof svgElement.unpauseAnimations === 'function') {
                            svgElement.unpauseAnimations();
                        }
                    } catch(e) { console.warn("SVG Animation (<?php echo esc_js($animation_id); ?>): Error playing SMIL animations:", e); }
                }

                function pauseAllSmilAnimations() {
                    try {
                        if (svgElement && typeof svgElement.pauseAnimations === 'function') {
                            svgElement.pauseAnimations();
                        }
                    } catch(e) { console.warn("SVG Animation (<?php echo esc_js($animation_id); ?>): Error pausing SMIL animations:", e); }
                }

                function resetAllSmilAnimations() {
                    try {
                        if (svgElement && typeof svgElement.setCurrentTime === 'function') {
                            svgElement.setCurrentTime(0);
                        }
                        if (svgElement && typeof svgElement.pauseAnimations === 'function') {
                            svgElement.pauseAnimations();
                        }
                    } catch(e) { console.warn("SVG Animation (<?php echo esc_js($animation_id); ?>): Error resetting SMIL animations:", e); }
                }
                
                setTimeout(function() {
                    if (autoplay) {
                        playAllSmilAnimations();
                    } else {
                        resetAllSmilAnimations(); 
                    }
                }, 100);

                let interactionTarget = container;
                if (container.parentElement && container.parentElement.tagName === 'A' && container.parentElement.classList.contains('svg-animation-link')) {
                    interactionTarget = container.parentElement;
                }

                if (playOnHover) {
                    interactionTarget.addEventListener('mouseenter', playAllSmilAnimations);
                    interactionTarget.addEventListener('mouseleave', function() {
                        if (resetOnMouseLeave) {
                            resetAllSmilAnimations();
                        } else if (pauseOnMouseLeave) {
                            pauseAllSmilAnimations();
                        }
                    });
                }

                if (playOnClick) {
                    const isLinked = interactionTarget.tagName === 'A';
                    if (!isLinked) { 
                        interactionTarget.addEventListener('click', playAllSmilAnimations);
                    }
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSVGAnimation_<?php echo esc_js(str_replace('-', '_', $this->id)); ?>);
            } else {
                initSVGAnimation_<?php echo esc_js(str_replace('-', '_', $this->id)); ?>();
            }
        })();
        </script>
        <?php
        echo ob_get_clean();
    }
}
