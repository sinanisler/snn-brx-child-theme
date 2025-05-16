<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
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
            'type'    => 'text',
            'default' => '300px',
            'placeholder' => '300px',
            'inline' => true,
        ];
        $this->controls['indicator_width'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Scroll Line Width', 'snn' ),
            'type'    => 'text',
            'default' => '4px',
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
            'css' => [
                [
                    'property' => 'background-color',
                    'selector' => '.scroll-line',
                ]
            ]
        ];
        $this->controls['dot_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Dot Color', 'snn' ),
            'type'  => 'color',
            'default' => [
                'hex' => '#333333'
            ],
            'css' => [
                [
                    'property' => 'background',
                    'selector' => '.scroll-indicator',
                ]
            ]
        ];
        $this->controls['dot_width'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Width (px)', 'snn' ),
            'type'    => 'number',
            'default' => 18,
            'placeholder' => 18,
            'min'     => 8,
            'max'     => 60,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['dot_height'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Height (px)', 'snn' ),
            'type'    => 'number',
            'default' => 18,
            'placeholder' => 18,
            'min'     => 8,
            'max'     => 60,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['dot_border_radius'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Border Radius (px)', 'snn' ),
            'type'    => 'number',
            'default' => 50,
            'placeholder' => 50,
            'min'     => 0,
            'max'     => 100,
            'step'    => 1,
            'inline'  => true,
        ];
        $this->controls['dot_font_size'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Dot Font Size (px)', 'snn' ),
            'type'    => 'number',
            'default' => 12,
            'placeholder' => 12,
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

    public function render() {
        $dom_selector = $this->settings['dom_selector'] ?: 'comment';
        $indicator_height = isset($this->settings['indicator_height']) ? $this->settings['indicator_height'] : '300px';
        $indicator_width = isset($this->settings['indicator_width']) ? $this->settings['indicator_width'] : '4px';
        $dot_width = isset($this->settings['dot_width']) ? intval($this->settings['dot_width']) : 18;
        $dot_height = isset($this->settings['dot_height']) ? intval($this->settings['dot_height']) : 18;
        $dot_border_radius = isset($this->settings['dot_border_radius']) ? intval($this->settings['dot_border_radius']) : 50;
        $dot_font_size = isset($this->settings['dot_font_size']) ? intval($this->settings['dot_font_size']) : 10;
        $fixed_position = isset($this->settings['fixed_position']) ? (bool)$this->settings['fixed_position'] : false;

        $height_css = esc_attr($indicator_height);
        $width_css = esc_attr($indicator_width);
        $dot_width_css = esc_attr($dot_width) . 'px';
        $dot_height_css = esc_attr($dot_height) . 'px';
        $dot_border_radius_css = esc_attr($dot_border_radius) . 'px';
        $dot_font_size_css = esc_attr($dot_font_size) . 'px';

        $unique = 'scroll-indicator-' . uniqid();
        $this->set_attribute( '_root', 'class', [ 'snn-scroll-line-vertical-indicator-wrapper', $unique ] );
        ?>
        <style>
        .<?php echo esc_attr($unique); ?> {
            <?php if ( $fixed_position ) : ?>
                position: fixed;
                top: 50%;
                right: 20px;
                transform: translateY(-50%);
                z-index: 1000;
                display: flex;
                flex-direction: column;
                align-items: center;
            <?php else : ?>
                display: block;
                position: relative;
                flex-direction: column;
                align-items: center;
            <?php endif; ?>
        }
        .<?php echo esc_attr($unique); ?> .scroll-line-container {
            position: relative;
            height: <?php echo $height_css; ?>;
            width: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .<?php echo esc_attr($unique); ?> .scroll-line {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: <?php echo $width_css; ?>;
            height: 100%;
            background-color: #ccc;
            border-radius: 2px;
            z-index: 0;
        }
        .<?php echo esc_attr($unique); ?> .scroll-indicator {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: <?php echo $dot_width_css; ?>;
            height: <?php echo $dot_height_css; ?>;
            background: #333;
            border-radius: <?php echo $dot_border_radius_css; ?>;
            color: #fff;
            font-size: <?php echo $dot_font_size_css; ?>;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            top: 0;
            z-index: 1;
            will-change: top;
        }
        </style>
        <div <?php echo $this->render_attributes('_root'); ?>>
            <div class="scroll-line-container">
                <div class="scroll-line"></div>
                <div class="scroll-indicator">1</div>
            </div>
        </div>
<script>
(function(){
    const root = document.querySelector('.<?php echo esc_js($unique); ?>');
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
        const percent = Math.min(Math.max(scrollTop / docHeight, 0), 1);

        const lineHeight = scrollLine.clientHeight;
        targetTop = percent * (lineHeight - dotHeight);

        const postNumber = Math.round(postCount * percent);
        currentDisplayNum = Math.min(Math.max(1, postNumber + 1), postCount);
    }

    function animate() {
        currentTop += (targetTop - currentTop) * 0.15; // Smooth easing (15%)

        indicator.style.top = `${currentTop}px`;

        // Update displayed number only if it changed
        if (indicator.textContent != currentDisplayNum) {
            indicator.textContent = currentDisplayNum;
        }

        if (Math.abs(targetTop - currentTop) > 0.5) {
            requestAnimationFrame(animate);
        } else {
            currentTop = targetTop; // Snap to target to prevent jitter
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
    }
}
?>
