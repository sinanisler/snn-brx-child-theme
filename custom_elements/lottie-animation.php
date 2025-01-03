<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
} 

$options = get_option('snn_other_settings');
if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {

class Custom_Element_LottieAnimation extends \Bricks\Element {

    public $category     = 'snn';
    public $name         = 'lottieanimation';
    public $icon         = 'fas fa-film';
    public $css_selector = '.custom-lottie-animation-wrapper';

    public function get_label() {
        return 'Lottie Animation';
    }

    public function set_controls() {

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

        // Animation Speed
        $this->controls['animation_speed'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animation Speed', 'bricks' ),
            'type'    => 'number',
            'default' => 1.0,
            'step'    => 0.1,
            'min'     => 0.1,
            'max'     => 5.0,
            'description' => "
                can be; <b>0.5</b> or <b>1</b> or <b>2</b> ...etc
            ",
        ];

        // Animation Height
        $this->controls['animation_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Animation Height (px)', 'bricks' ),
            'type'    => 'number',
            'default' => 400,
            'min'     => 100,
            'step'    => 10,
            'description' => "<br><br>",
        ];

        // Scroll Trigger Option
        $this->controls['scroll_trigger'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Scroll Trigger', 'bricks' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'small'   => true,
            'default' => false,
            'description' => "Animate your lottie on scroll.",
        ];

        // Scroll Trigger Start
        $this->controls['scroll_trigger_start'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Trigger Start', 'bricks' ),
            'type'    => 'text',
            'default' => 'top center',
            'description' => "
                top 50%<br>
                top 90%
            ",
        ];

        // Scroll Trigger End
        $this->controls['scroll_trigger_end'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Trigger End', 'bricks' ),
            'type'    => 'text',
            'default' => 'bottom center',
            'description' => "
                bottom 50%<br>
                bottom 90%
                <br><br>
                <p  data-control='info'>
                    Scroll Start and Stop settings little bit counter-intuitive. To start early you need top 90% or top bottom. Enable markers and test it out to learn it.
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
    }

    public function render() {
        // Retrieve settings
        $lottie_json      = isset( $this->settings['lottie_json']['url'] ) ? esc_url( $this->settings['lottie_json']['url'] ) : '';
        $loop             = isset( $this->settings['loop'] ) && $this->settings['loop'] === true ? 'true' : 'false';
        $autoplay         = isset( $this->settings['autoplay'] ) && $this->settings['autoplay'] === true ? 'true' : 'false';
        $animation_speed  = isset( $this->settings['animation_speed'] ) ? floatval( $this->settings['animation_speed'] ) : 1.0;
        $animation_height = isset( $this->settings['animation_height'] ) ? intval( $this->settings['animation_height'] ) : 400;
        $scroll_trigger   = isset( $this->settings['scroll_trigger'] ) && $this->settings['scroll_trigger'] === true ? true : false;
        $scroll_trigger_start = isset( $this->settings['scroll_trigger_start'] ) ? esc_js( $this->settings['scroll_trigger_start'] ) : 'top center';
        $scroll_trigger_end   = isset( $this->settings['scroll_trigger_end'] ) ? esc_js( $this->settings['scroll_trigger_end'] ) : 'bottom top';
        $scroll_trigger_markers = isset( $this->settings['scroll_trigger_markers'] ) && $this->settings['scroll_trigger_markers'] === true ? 'true' : 'false';

        if ( empty( $lottie_json ) ) {
            echo '<p>' . esc_html__( 'Please upload a Lottie JSON file.', 'bricks' ) . '</p>';
            return;
        }

        // Generate unique ID for animation container
        $animation_id = 'custom-lottie-animation-' . uniqid();
        ?>

        <!-- Lottie Animation Container -->
        <div id="<?php echo esc_attr( $animation_id ); ?>" class="custom-lottie-animation-wrapper"
             style="height: <?php echo esc_attr( $animation_height ); ?>px; width: 100%; max-width: 100%;">
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var lottieAnimation = lottie.loadAnimation({
                container: document.getElementById('<?php echo esc_js( $animation_id ); ?>'), // the dom element
                renderer: 'svg',
                loop: <?php echo esc_js( $loop ); ?>,
                autoplay: <?php echo esc_js( $autoplay ); ?>,
                path: '<?php echo esc_js( $lottie_json ); ?>', // the path to the animation JSON file
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid meet'
                }
            });

            // Set animation speed
            lottieAnimation.setSpeed(<?php echo esc_js( $animation_speed ); ?>);
            // lottieAnimation.setDirection(1);

            <?php if ( $scroll_trigger ): ?>
            // Initialize GSAP ScrollTrigger for Lottie
            gsap.registerPlugin(ScrollTrigger);
            gsap.to({}, {
                scrollTrigger: {
                    trigger: "#<?php echo esc_js( $animation_id ); ?>",
                    start: "<?php echo esc_js( $scroll_trigger_start ); ?>",
                    end: "<?php echo esc_js( $scroll_trigger_end ); ?>",
                    scrub: true,
                    markers: <?php echo esc_js( $scroll_trigger_markers ); ?>,
                    onUpdate: function(self) {
                        var progress = Math.min(self.progress.toFixed(3), 0.990);
                        lottieAnimation.goToAndStop(progress * lottieAnimation.totalFrames, true);
                    }
                }
            });
            <?php endif; ?>
        });
        </script>

        <?php
    }
}

// Register the Custom Element
add_action( 'bricks_register_elements', function() {
    \Bricks\Element::register_element( 'Custom_Element_LottieAnimation', 'lottieanimation' );
} );

}
