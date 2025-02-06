<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option('snn_other_settings');
if ( isset($options['enqueue_gsap']) && $options['enqueue_gsap'] ) {

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
            'tab'    => 'content',
            'label'  => esc_html__( 'Lottie JSON File', 'bricks' ),
            'type'   => 'file',
            'accept' => '.json',
            'description' => "Upload your Lottie JSON file here",
        ];

        // Loop Option
        $this->controls['loop'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Loop Animation', 'bricks' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
        ];

        // Autoplay Option
        $this->controls['autoplay'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Autoplay Animation', 'bricks' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => true,
        ];

        // Play on Hover
        $this->controls['play_on_hover'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Play on Hover', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Pause on Mouse Leave
        $this->controls['pause_on_mouse_leave'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Pause on Mouse Leave', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Play on Click (optional toggle)
        $this->controls['play_on_click'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Play on Click', 'bricks' ),
            'type'        => 'checkbox',
            'inline'      => true,
            'small'       => true,
            'default'     => false,
            'description' => "",
        ];

        // Animation Speed
        $this->controls['animation_speed'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animation Speed', 'bricks' ),
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
            'label'   => esc_html__( 'Animation Height (px)', 'bricks' ),
            'type'    => 'number',
            'default' => 400,
            'min'     => 100,
            'step'    => 10,
        ];

        // Scroll Trigger Option
        $this->controls['scroll_trigger'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Scroll Trigger', 'bricks' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
            'description' => "",
        ];

        // Scroll Trigger Start
        $this->controls['scroll_trigger_start'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Trigger Start', 'bricks' ),
            'type'    => 'text',
            'default' => 'top center',
            'description' => "
                Examples: <br>top 50%<br>top 90%
            ",
        ];

        // Scroll Trigger End
        $this->controls['scroll_trigger_end'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Trigger End', 'bricks' ),
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
            'label'   => esc_html__( 'Show Scroll Trigger Markers', 'bricks' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
        ];

        /**
         * **New Link Control Added Below**
         */
        // Link Control
        $this->controls['animation_link'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Link', 'bricks' ),
            'type'        => 'link',
            'pasteStyles' => false,
            'placeholder' => esc_html__( 'http://yoursite.com', 'bricks' ),
            // You can uncomment and customize the 'exclude' parameter if needed
            // 'exclude'     => [
            //     'rel',
            //     'newTab',
            // ],
        ];
    }

    public function render() {

        // Existing Settings Retrieval
        $lottie_json      = isset($this->settings['lottie_json']['url']) ? esc_url($this->settings['lottie_json']['url']) : '';
        $loop             = ! empty($this->settings['loop']) ? 'true' : 'false';
        $autoplay         = ! empty($this->settings['autoplay']) ? 'true' : 'false';

        $play_on_hover    = ! empty($this->settings['play_on_hover']) ? 'true' : 'false';
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
            echo '<p>' . esc_html__( 'Please upload a Lottie JSON file.', 'bricks' ) . '</p>';
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

            var lottieAnimation = lottie.loadAnimation({
                container: document.getElementById('<?php echo esc_js($animation_id); ?>'),
                renderer: 'svg',
                loop: <?php echo esc_js($loop); ?>,
                autoplay: <?php echo esc_js($autoplay); ?>,
                path: '<?php echo esc_js($lottie_json); ?>',
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid meet'
                }
            });

            lottieAnimation.setSpeed(<?php echo esc_js($animation_speed); ?>);

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

            if ('<?php echo esc_js($play_on_hover); ?>' === 'true') {
                var container = document.getElementById('<?php echo esc_js($animation_id); ?>');
                container.addEventListener('mouseenter', function() {
                    // Changed from goToAndPlay(0, true) to play() to resume from paused state
                    lottieAnimation.play();
                });

                container.addEventListener('mouseleave', function() {
                    if ('<?php echo esc_js($pause_on_mouse_leave); ?>' === 'true') {
                        lottieAnimation.pause();
                    }
                });
            }

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

} // end if enqueue_gsap
?>
