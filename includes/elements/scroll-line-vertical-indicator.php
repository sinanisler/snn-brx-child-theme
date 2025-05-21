<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Bricks\Element;

class Snn_Scroll_Line_Vertical_Indicator extends Element {
    public $category     = 'snn';
    public $name         = 'snn-scroll-line-vertical-indicator';
    public $icon         = 'ti-more';
    public $css_selector = '.snn-scroll-line-vertical-indicator-wrapper';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Scroll Line Vertical Indicator', 'snn' );
    }

    public function set_controls() {
        $this->controls['dom_selector'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dom Selector', 'snn' ),
            'type'    => 'text',
            'default' => 'comment',
            'description' => esc_html__( 'DOMs selector "comment" or ".post" or ".my-comment"', 'snn' ),
        ];
        $this->controls['indicator_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Line Height', 'snn' ),
            'type'    => 'number',
            'default' => '300px',
            'step'    => 1,
            'placeholder' => '300px',
            'inline' => true,
        ];
        $this->controls['indicator_width'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Line Width', 'snn' ),
            'type'    => 'number',
            'default' => '4px',
            'step'    => 1,
            'placeholder' => '4px',
            'inline' => true,
        ];
        $this->controls['indicator_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Line Color', 'snn' ),
            'type'  => 'color',
            'default' => [
                'hex' => '#cccccc'
            ],
        ];
        $this->controls['dot_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Dot Color', 'snn' ),
            'type'  => 'color',
            'default' => [
                'hex' => '#333333'
            ],
        ];
        $this->controls['dot_font_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Dot Font Color', 'snn' ),
            'type'  => 'color',
            'default' => [
                'hex' => '#fff'
            ],
        ];
        $this->controls['dot_width'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Width (px)', 'snn' ),
            'type'    => 'number',
            'default' => '40px',
            'placeholder' => '40px',
            'min'     => 8,
            'max'     => 60,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['dot_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Height (px)', 'snn' ),
            'type'    => 'number',
            'default' => '20px',
            'placeholder' => '20px',
            'min'     => 8,
            'max'     => 60,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['dot_border_radius'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Border Radius (px)', 'snn' ),
            'type'    => 'number',
            'default' => '5px',
            'placeholder' => '5px',
            'min'     => 0,
            'max'     => 100,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['dot_font_size'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Font Size (px)', 'snn' ),
            'type'    => 'number',
            'default' => '12px',
            'placeholder' => '12px',
            'min'     => 6,
            'max'     => 36,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['fixed_position'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Fixed Position', 'snn' ),
            'type'  => 'checkbox',
            'default' => false,
        ];
    }

    // Helper: robust color value (string/array/variable)
    private function get_color_val($val) {
        if (is_array($val)) {
            // Prefer rgba, fallback to hex, fallback to first key.
            if (!empty($val['rgba'])) return $val['rgba'];
            if (!empty($val['hex'])) return $val['hex'];
            if (!empty($val['rgb'])) return $val['rgb'];
            foreach ($val as $v) if ($v) return $v;
            return '';
        }
        if (is_string($val) && trim($val) !== '') return $val;
        return '';
    }

    public function render() {
        $dom_selector = !empty($this->settings['dom_selector']) ? $this->settings['dom_selector'] : 'comment';
        $indicator_height = !empty($this->settings['indicator_height']) ? $this->settings['indicator_height'] : '300px';
        $indicator_width = !empty($this->settings['indicator_width']) ? $this->settings['indicator_width'] : '4px';
        $indicator_color = $this->get_color_val($this->settings['indicator_color']);
        $dot_color = $this->get_color_val($this->settings['dot_color']);

        // Fix: check key existence for dot_font_color, fallback to #fff if not set
        $dot_font_color = isset($this->settings['dot_font_color']) ? $this->get_color_val($this->settings['dot_font_color']) : '#fff';

        $dot_width = isset($this->settings['dot_width']) ? intval($this->settings['dot_width']) : 18;
        $dot_height = isset($this->settings['dot_height']) ? intval($this->settings['dot_height']) : 18;
        $dot_border_radius = isset($this->settings['dot_border_radius']) ? intval($this->settings['dot_border_radius']) : 50;
        $dot_font_size = isset($this->settings['dot_font_size']) ? intval($this->settings['dot_font_size']) : 12;
        $fixed_position = !empty($this->settings['fixed_position']);
        $dot_width_css = $dot_width . 'px';
        $dot_height_css = $dot_height . 'px';
        $dot_border_radius_css = $dot_border_radius . 'px';
        $dot_font_size_css = $dot_font_size . 'px';
        $unique = 'scroll-indicator-' . uniqid();
        $this->set_attribute('_root', 'class', [ 'snn-scroll-line-vertical-indicator-wrapper', $unique ]);
        echo '<div ' . $this->render_attributes('_root') . '>';
        echo '<style>
            /* Wrapper */
            .' . $unique . ' {
                ' . ( $fixed_position ?
                    'position: fixed;
                    top: 50%;
                    right: 20px;
                    transform: translateY(-50%);
                    z-index: 1000;
                    display: flex;
                    flex-direction: column;
                    align-items: center;'
                    :
                    'display: block;
                    position: relative;
                    flex-direction: column;
                    align-items: center;'
                ) . '
            }
            /* Line Container */
            .' . $unique . ' .scroll-line-container {
                position: relative;
                height: ' . $indicator_height . ';
                width: auto;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            /* Scroll Line */
            .' . $unique . ' .scroll-line {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                width: ' . $indicator_width . ';
                height: 100%;
                background-color: ' . $indicator_color . ';
                border-radius: 2px;
                z-index: 0;
            }
            /* Scroll Dot */
            .' . $unique . ' .scroll-indicator {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                width: ' . $dot_width_css . ';
                height: ' . $dot_height_css . ';
                background: ' . $dot_color . ';
                border-radius: ' . $dot_border_radius_css . ';
                color: ' . $dot_font_color . ';
                font-size: ' . $dot_font_size_css . ';
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                top: 0;
                z-index: 1;
                will-change: top;
            }
        </style>';
        echo '<div class="scroll-line-container">
                <div class="scroll-line"></div>
                <div class="scroll-indicator">1</div>
            </div>';
        ?>
        <script>
        (function(){
            const root = document.querySelector('.<?php echo $unique; ?>');
            if (!root) return;
            const indicator = root.querySelector('.scroll-indicator');
            const scrollLine = root.querySelector('.scroll-line');
            const selector = '<?php echo esc_js($dom_selector); ?>';
            const dotHeight = <?php echo $dot_height; ?>;
            let postCount = 0;
            let currentTop = 0;
            let targetTop = 0;
            let currentDisplayNum = '-';
            let animationRunning = false;
            function getPostsCount() {
                try {
                    return document.querySelectorAll(selector).length;
                } catch(e) {
                    return 0;
                }
            }
            function calculatePosition() {
                postCount = getPostsCount();
                if (postCount === 0) {
                    targetTop = 0;
                    currentDisplayNum = '-';
                    return;
                }
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                const docHeight = document.body.scrollHeight - window.innerHeight;
                const percent = Math.min(Math.max(docHeight ? (scrollTop / docHeight) : 0, 0), 1);
                const lineHeight = scrollLine.clientHeight;
                targetTop = percent * (lineHeight - dotHeight);
                const postNumber = Math.round(postCount * percent);
                currentDisplayNum = Math.min(Math.max(1, postNumber + 1), postCount);
            }
            function animate() {
                currentTop += (targetTop - currentTop) * 0.15;
                indicator.style.top = `${currentTop}px`;
                if (indicator.textContent != currentDisplayNum) {
                    indicator.textContent = currentDisplayNum;
                }
                if (Math.abs(targetTop - currentTop) > 0.5) {
                    requestAnimationFrame(animate);
                } else {
                    currentTop = targetTop;
                    animationRunning = false;
                }
            }
            function requestAnimation() {
                calculatePosition();
                if (!animationRunning) {
                    animationRunning = true;
                    requestAnimationFrame(animate);
                }
            }
            document.addEventListener('DOMContentLoaded', requestAnimation);
            window.addEventListener('scroll', requestAnimation, {passive:true});
            window.addEventListener('resize', requestAnimation, {passive:true});
        })();
        </script>
        <?php
        echo '</div>';
    }
}
?>
