<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Element_LottieAnimation extends \Bricks\Element {

    public $category     = 'snn';
    public $name         = 'lottieanimation';
    public $icon         = 'fas fa-film';
    public $css_selector = '.custom-lottie-animation-wrapper';

    public function get_label() {
        return 'Lottie Animation';
    }

    public function set_controls() {
        // Existing Controls...

        // Lottie JSON Upload
        $this->controls['lottie_json'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Lottie JSON File', 'snn' ),
            'type'        => 'file',
            'accept'      => '.json',
            'description' => "Upload your Lottie JSON file here",
        ];

        // Lottie JSON URL
        $this->controls['lottie_json_url'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Or External JSON URL', 'snn' ),
            'type'        => 'text',
            'placeholder' => esc_html__( 'https://example.com/animation.json', 'snn' ),
            'description' => "Paste an external Lottie JSON URL or attachment ID here (overrides uploaded file)",
        ];

        // Loop Option
        $this->controls['loop'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Loop Animation', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
        ];

        // Autoplay Option
        $this->controls['autoplay'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Autoplay Animation', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => true,
        ];

        // Autoplay on Viewport Entry
        $this->controls['autoplay_on_viewport'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Autoplay on Viewport Entry', 'snn' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
        ];

        // Play on Hover
        $this->controls['play_on_hover'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Play on Hover', 'snn' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Reset State on Mouse Leave (New Control)
        $this->controls['reset_on_mouse_leave'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Reset/Reverse State on Mouse Leave', 'snn' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Pause on Mouse Leave
        $this->controls['pause_on_mouse_leave'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Pause on Mouse Leave', 'snn' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Play on Click (optional toggle)
        $this->controls['play_on_click'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Play on Click', 'snn' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Animation Speed
        $this->controls['animation_speed'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animation Speed', 'snn' ),
            'type'    => 'number',
            'default' => 1.0,
            'step'    => 0.1,
            'min'     => 0.1,
            'max'     => 5.0,
            'description' => "",
        ];

        // Animation Height
        $this->controls['animation_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animation Height (px)', 'snn' ),
            'type'    => 'number',
            'default' => 400,
            'min'     => 100,
            'step'    => 10,
        ];

        // GSAP Scroll Trigger Controls - Only show if GSAP is enabled
        $options = get_option('snn_other_settings');
        if ( ! empty( $options['enqueue_gsap'] ) ) {
            // Scroll Trigger Option
            $this->controls['scroll_trigger'] = [
                'tab'     => 'content',
                'label'   => esc_html__( 'Enable Scroll Trigger', 'snn' ),
                'type'    => 'checkbox',
                'inline'  => true,
                'small'   => true,
                'default' => false,
                'description' => "",
            ];

            // Scroll Trigger Start
            $this->controls['scroll_trigger_start'] = [
                'tab'     => 'content',
                'label'   => esc_html__( 'Scroll Trigger Start', 'snn' ),
                'type'    => 'text',
                'default' => 'top center',
                'description' => "
                    Examples: <br>top 50%<br>top 90%
                ",
            ];

            // Scroll Trigger End
            $this->controls['scroll_trigger_end'] = [
                'tab'     => 'content',
                'label'   => esc_html__( 'Scroll Trigger End', 'snn' ),
                'type'    => 'text',
                'default' => 'bottom center',
                'description' => "
                    Examples: <br>bottom 50%<br>bottom 90%
                    <p data-control='info'>
                        Scroll Start and Stop can be counter-intuitive.
                        Enable markers and test it out.
                    </p>
                ",
            ];

            // Scroll Trigger Markers
            $this->controls['scroll_trigger_markers'] = [
                'tab'     => 'content',
                'label'   => esc_html__( 'Show Scroll Trigger Markers', 'snn' ),
                'type'    => 'checkbox',
                'inline'  => true,
                'small'   => true,
                'default' => false,
            ];
        }

        /**
         * **New Link Control Added Below**
         */
        // Link Control
        $this->controls['animation_link'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Link', 'snn' ),
            'type'        => 'link',
            'pasteStyles' => false,
            'placeholder' => esc_html__( 'http://yoursite.com', 'snn' ),
            // You can uncomment and customize the 'exclude' parameter if needed
            // 'exclude'     => [
            //     'rel',
            //     'newTab',
            // ],
        ];
    }

    public function render() {

        // Enqueue Lottie library
        wp_enqueue_script('lottie-js', SNN_URL_ASSETS . 'js/lottie.min.js', array(), null, array('in_footer' => true));

        // Existing Settings Retrieval
        // Check for external URL first, then fall back to uploaded file
        $lottie_json_url_raw = isset($this->settings['lottie_json_url']) ? $this->settings['lottie_json_url'] : '';

        // If the value is numeric, treat it as an attachment ID
        if ( is_numeric($lottie_json_url_raw) && ! empty($lottie_json_url_raw) ) {
            $lottie_json_url = wp_get_attachment_url(intval($lottie_json_url_raw));
        } else {
            $lottie_json_url = ! empty($lottie_json_url_raw) ? esc_url($lottie_json_url_raw) : '';
        }

        $lottie_json_file = isset($this->settings['lottie_json']['url']) ? esc_url($this->settings['lottie_json']['url']) : '';
        $lottie_json      = ! empty($lottie_json_url) ? $lottie_json_url : $lottie_json_file;

        $loop             = ! empty($this->settings['loop']) ? 'true' : 'false';
        $autoplay         = ! empty($this->settings['autoplay']) ? 'true' : 'false';
        $autoplay_on_viewport = ! empty($this->settings['autoplay_on_viewport']) ? 'true' : 'false';

        $play_on_hover    = ! empty($this->settings['play_on_hover']) ? 'true' : 'false';
        $reset_on_mouse_leave = ! empty($this->settings['reset_on_mouse_leave']) ? 'true' : 'false';
        $pause_on_mouse_leave = ! empty($this->settings['pause_on_mouse_leave']) ? 'true' : 'false';
        $play_on_click    = ! empty($this->settings['play_on_click']) ? 'true' : 'false';

        $animation_speed  = isset($this->settings['animation_speed']) ? floatval($this->settings['animation_speed']) : 1.0;
        $animation_height = isset($this->settings['animation_height']) ? intval($this->settings['animation_height']) : 400;

        $scroll_trigger   = ! empty($this->settings['scroll_trigger']);
        $scroll_trigger_start = isset($this->settings['scroll_trigger_start']) ? esc_js($this->settings['scroll_trigger_start']) : 'top center';
        $scroll_trigger_end   = isset($this->settings['scroll_trigger_end'])   ? esc_js($this->settings['scroll_trigger_end'])   : 'bottom top';
        $scroll_trigger_markers = ! empty($this->settings['scroll_trigger_markers']) ? 'true' : 'false';

        // **Retrieve Link Settings**
        $animation_link = isset($this->settings['animation_link']) ? $this->settings['animation_link'] : null;
        $link_url       = isset($animation_link['url']) ? esc_url($animation_link['url']) : '';
        $link_target    = isset($animation_link['target']) && $animation_link['target'] ? ' target="_blank"' : '';
        $link_nofollow  = isset($animation_link['rel']) && strpos($animation_link['rel'], 'nofollow') !== false ? ' rel="nofollow"' : '';

        if ( empty( $lottie_json ) ) {
            echo '<p>' . esc_html__( 'Please upload a Lottie JSON file or provide an external JSON URL.', 'snn' ) . '</p>';
            return;
        }

        $animation_id = 'custom-lottie-animation-' . uniqid();

        // **Start Output Buffering to Handle Optional Link Wrapping**
        ob_start();
        if ( ! empty( $link_url ) ) {
            echo '<a
            href="' . $link_url . '"' . $link_target . $link_nofollow . '
            style="width: 100%; display: block; cursor:pointer;"
            class="lottie-link">';
        }
        ?>
        <div
            id="<?php echo esc_attr($animation_id); ?>"
            class="custom-lottie-animation-wrapper"
            style="height: <?php echo esc_attr($animation_height); ?>px; width: 100%; max-width: 100%;
                   cursor: <?php echo ($play_on_click === 'true') ? 'pointer' : ''; ?>;"
        ></div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {

            var autoplayOnViewport = '<?php echo esc_js($autoplay_on_viewport); ?>' === 'true';
            var shouldAutoplay = '<?php echo esc_js($autoplay); ?>' === 'true';

            var lottieAnimation = lottie.loadAnimation({
                container: document.getElementById('<?php echo esc_js($animation_id); ?>'),
                renderer: 'svg',
                loop: <?php echo esc_js($loop); ?>,
                autoplay: autoplayOnViewport ? false : shouldAutoplay,
                path: '<?php echo esc_js($lottie_json); ?>',
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid meet'
                }
            });

            // Set the initial speed
            lottieAnimation.setSpeed(<?php echo floatval($animation_speed); ?>);

            // Handle Autoplay on Viewport Entry
            if (autoplayOnViewport && shouldAutoplay) {
                var container = document.getElementById('<?php echo esc_js($animation_id); ?>');
                var hasPlayed = false;

                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting && !hasPlayed) {
                            lottieAnimation.play();
                            hasPlayed = true;
                            // If loop is disabled, we can observe again after animation completes
                            if (<?php echo esc_js($loop); ?> === false) {
                                lottieAnimation.addEventListener('complete', function() {
                                    hasPlayed = false;
                                });
                            }
                        }
                    });
                }, {
                    threshold: 0.1 // Trigger when at least 10% of the element is visible
                });

                observer.observe(container);
            }

            // If scroll trigger is enabled, attach the ScrollTrigger logic
            <?php if ( $scroll_trigger ): ?>
            gsap.registerPlugin(ScrollTrigger);
            gsap.to({}, {
                scrollTrigger: {
                    trigger: "#<?php echo esc_js($animation_id); ?>",
                    start: "<?php echo esc_js($scroll_trigger_start); ?>",
                    end: "<?php echo esc_js($scroll_trigger_end); ?>",
                    scrub: true,
                    markers: <?php echo esc_js($scroll_trigger_markers); ?>,
                    onUpdate: function(self) {
                        var progress = Math.min(self.progress.toFixed(3), 0.990);
                        lottieAnimation.goToAndStop(progress * lottieAnimation.totalFrames, true);
                    }
                }
            });
            <?php endif; ?>

            // Named resetListener to avoid infinite loops
            function resetListener() {
                // Once we're at or before frame 1, consider that "fully reversed"
                if (lottieAnimation.currentFrame <= 1) {
                    // Remove the listener first to avoid re-entry
                    lottieAnimation.removeEventListener('enterFrame', resetListener);

                    // Stop exactly at frame 0
                    lottieAnimation.goToAndStop(0, true);

                    // Restore direction to forward
                    lottieAnimation.setDirection(1);
                }
            }

            // Handle Play on Hover
            if ('<?php echo esc_js($play_on_hover); ?>' === 'true') {
                var container = document.getElementById('<?php echo esc_js($animation_id); ?>');

                container.addEventListener('mouseenter', function() {
                    // If reset_on_mouse_leave is enabled, we always start fresh from 0
                    if ('<?php echo esc_js($reset_on_mouse_leave); ?>' === 'true') {
                        lottieAnimation.goToAndPlay(0, true);
                    } else {
                        // Otherwise just continue playing wherever it left off
                        lottieAnimation.play();
                    }
                });

                container.addEventListener('mouseleave', function() {
                    // If reset is enabled, reverse back to the start
                    if ('<?php echo esc_js($reset_on_mouse_leave); ?>' === 'true') {
                        // Remove any old references to the reset listener
                        lottieAnimation.removeEventListener('enterFrame', resetListener);

                        // Reverse direction and start playing
                        lottieAnimation.setDirection(-1);
                        lottieAnimation.play();

                        // Attach the named listener to detect when we've hit frame ~0
                        lottieAnimation.addEventListener('enterFrame', resetListener);

                    } else if ('<?php echo esc_js($pause_on_mouse_leave); ?>' === 'true') {
                        // If not resetting, but we do want to pause
                        lottieAnimation.pause();
                    }
                });
            }

            // Handle Play on Click
            if ('<?php echo esc_js($play_on_click); ?>' === 'true') {
                var container = document.getElementById('<?php echo esc_js($animation_id); ?>');

                // Ensure the cursor indicates interactivity
                container.style.cursor = 'pointer';

                container.addEventListener('click', function() {
                    // Restart the animation from the beginning
                    lottieAnimation.stop();
                    lottieAnimation.goToAndPlay(0, true);
                });
            }
        });
        </script>

        <?php
        if ( ! empty( $link_url ) ) {
            echo '</a>';
        }
        // **End Output Buffering and Output Content**
        echo ob_get_clean();
    }
}

add_action( 'bricks_register_elements', function() {
    \Bricks\Element::register_element( 'Custom_Element_LottieAnimation', 'lottieanimation' );
} );
?>
