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




    }

    public function render() {
        // Retrieve settings
        $lottie_json      = isset( $this->settings['lottie_json']['url'] ) ? esc_url( $this->settings['lottie_json']['url'] ) : '';
        $loop             = isset( $this->settings['loop'] ) && $this->settings['loop'] === true ? 'true' : 'false';
        $autoplay         = isset( $this->settings['autoplay'] ) && $this->settings['autoplay'] === true ? 'true' : 'false';
        $animation_speed  = isset( $this->settings['animation_speed'] ) ? floatval( $this->settings['animation_speed'] ) : 1.0;
        $animation_height = isset( $this->settings['animation_height'] ) ? intval( $this->settings['animation_height'] ) : 400;

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