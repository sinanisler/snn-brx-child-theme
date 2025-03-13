<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bricks\Frontend;

class Prefix_Element_Compare_Image extends \Bricks\Element {
    public $category    = 'snn';
    public $name        = 'compare-image';
    public $icon        = 'ti-image';
    public $scripts     = [];
    public $nestable    = false;

    public function get_label() {
        return esc_html__('Compare Image', 'bricks');
    }

    public function set_control_groups() {
        // Define control groups here if needed.
    }

    public function set_controls() {
        // Left Image
        $this->controls['left_image'] = [
            'tab'   => 'content',
            'label' => esc_html__('Left Image', 'bricks'),
            'type'  => 'image',
        ];

        // Right Image
        $this->controls['right_image'] = [
            'tab'   => 'content',
            'label' => esc_html__('Right Image', 'bricks'),
            'type'  => 'image',
        ];

        // Left Label
        $this->controls['left_label'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Left Label', 'bricks'),
            'type'    => 'text',
            'default' => esc_html__('Original', 'bricks'),
        ];

        // Right Label
        $this->controls['right_label'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Right Label', 'bricks'),
            'type'    => 'text',
            'default' => esc_html__('Modified', 'bricks'),
        ];

        // Slider Icon Control
        $this->controls['slider_icon'] = [
            'tab'     => 'content',
            'label'   => esc_html__('Slider Icon', 'bricks'),
            'type'    => 'icon',
            'default' => [
                'library' => 'themify',
                'icon'    => 'ti-arrows-horizontal',
            ],
        ];

        // Slider Icon Color Control
        $this->controls['slider_icon_color'] = [
            'tab'    => 'content',
            'label'  => esc_html__('Slider Icon Color', 'bricks'),
            'type'   => 'color',
            'inline' => true,
            'css'    => [
                [
                    'property' => 'color',
                    'selector' => '.handle i',
                ],
            ],
            'default' => [
                'hex' => '#ffffff',
            ],
        ];

        // Slider Button Background Color Control
        $this->controls['slider_button_bg'] = [
            'tab'    => 'content',
            'label'  => esc_html__('Slider Button Background Color', 'bricks'),
            'type'   => 'color',
            'inline' => true,
            'css'    => [
                [
                    'property' => 'background-color',
                    'selector' => '.handle',
                ],
            ],
            'default' => [
                'hex' => '#212121',
            ],
        ];
    }

    public function render() {
        // Unique class to scope our element styles and scripts
        $unique_class = 'compare-image-' . uniqid();
        $this->set_attribute('_root', 'class', [$unique_class]);

        // Retrieve settings
        $left_image_id    = isset($this->settings['left_image']['id']) ? $this->settings['left_image']['id'] : '';
        $left_image_size  = isset($this->settings['left_image']['size']) ? $this->settings['left_image']['size'] : 'full';
        $right_image_id   = isset($this->settings['right_image']['id']) ? $this->settings['right_image']['id'] : '';
        $right_image_size = isset($this->settings['right_image']['size']) ? $this->settings['right_image']['size'] : 'full';

        $left_label  = isset($this->settings['left_label']) ? esc_html($this->settings['left_label']) : '';
        $right_label = isset($this->settings['right_label']) ? esc_html($this->settings['right_label']) : '';

        // Get the image tags (or fallback text if not set)
        $left_image_tag  = $left_image_id ? wp_get_attachment_image($left_image_id, $left_image_size, false, ['class' => 'original-image']) : '<p>No left image selected.</p>';
        $right_image_tag = $right_image_id ? wp_get_attachment_image($right_image_id, $right_image_size, false, ['class' => 'modified-image']) : '<p>No right image selected.</p>';
        ?>
        <div <?php echo $this->render_attributes('_root'); ?>>
            <style>
                .<?php echo $unique_class; ?> .comparison-container { 
                    max-width: 100%;
                    position: relative;
                    display: inline-block;
                    overflow: hidden;
                }
                .<?php echo $unique_class; ?> .original-image {
                    display: block;
                    width: 100%;
                    height: auto;
                }
                .<?php echo $unique_class; ?> .modified-image {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: auto;
                    clip-path: inset(0 50% 0 0);
                }
                .<?php echo $unique_class; ?> .handle {
                    position: absolute;
                    left: calc(50% - 16px);
                    top: 50%;
                    transform: translateY(-50%);
                    width: 36px;
                    height: 36px;
                    border-radius: 50%;
                    box-shadow:
                        0 0 0 6px rgba(0, 0, 0, 0.2),
                        0 0 3px rgba(0, 0, 0, 0.6),
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
                    cursor: pointer;
                    z-index: 2;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .<?php echo $unique_class; ?> .label {
                    position: absolute;
                    bottom: 10px;
                    background: rgba(0, 0, 0, 0.5);
                    color: #fff;
                    font-size: 14px;
                    padding: 5px 10px;
                    pointer-events: none;
                }
                .<?php echo $unique_class; ?> .label.original {
                    left: 10px;
                }
                .<?php echo $unique_class; ?> .label.modified {
                    right: 10px;
                }
            </style>

            <div class="comparison-container">
                <!-- Left/original image -->
                <?php echo $left_image_tag; ?>

                <!-- Right/modified image -->
                <?php echo $right_image_tag; ?>

                <div class="handle">
                    <?php
                    if (isset($this->settings['slider_icon'])) {
                        // Check if the Helpers class exists before using it
                        if (class_exists('Helpers')) {
                            Helpers::render_control_icon($this->settings['slider_icon'], ['handle-icon']);
                        } else {
                            // Fallback: manually render the icon using the provided settings
                            $icon_settings = $this->settings['slider_icon'];
                            if (isset($icon_settings['icon'])) {
                                echo '<i class="' . esc_attr($icon_settings['icon']) . ' handle-icon"></i>';
                            }
                        }
                    }
                    ?>
                </div>

                <!-- Labels -->
                <?php if (!empty($left_label)): ?>
                    <div class="label original"><?php echo $left_label; ?></div>
                <?php endif; ?>

                <?php if (!empty($right_label)): ?>
                    <div class="label modified"><?php echo $right_label; ?></div>
                <?php endif; ?>
            </div>

            <script>
            (function(){
                const container   = document.querySelector('.<?php echo $unique_class; ?> .comparison-container');
                if (!container) return;

                const modifiedImg = container.querySelector('.modified-image');
                const handle      = container.querySelector('.handle');
                if (!modifiedImg || !handle) return;

                let dragging = false;

                const startDrag = (event) => {
                    event.preventDefault();
                    dragging = true;

                    document.addEventListener('mousemove', onDrag);
                    document.addEventListener('touchmove', onDrag, { passive: false });
                    document.addEventListener('mouseup', stopDrag);
                    document.addEventListener('touchend', stopDrag);
                };

                const onDrag = (event) => {
                    if (!dragging) return;

                    let clientX = event.clientX;
                    if (event.touches && event.touches.length > 0) {
                        clientX = event.touches[0].clientX;
                        event.preventDefault();
                    }

                    const bounds = container.getBoundingClientRect();
                    let xPos = clientX - bounds.left;

                    if (xPos < 0) xPos = 0;
                    if (xPos > bounds.width) xPos = bounds.width;

                    const ratio = xPos / bounds.width;

                    handle.style.left = `calc(${(ratio * 100)}% - 16px)`;

                    const rightClip = 100 - (ratio * 100);
                    modifiedImg.style.clipPath = `inset(0 ${rightClip}% 0 0)`;
                    modifiedImg.style.webkitClipPath = `inset(0 ${rightClip}% 0 0)`;
                };

                const stopDrag = () => {
                    dragging = false;
                    document.removeEventListener('mousemove', onDrag);
                    document.removeEventListener('touchmove', onDrag);
                    document.removeEventListener('mouseup', stopDrag);
                    document.removeEventListener('touchend', stopDrag);
                };

                handle.addEventListener('mousedown', startDrag);
                handle.addEventListener('touchstart', startDrag, { passive: false });
            })();
            </script>
        </div>
        <?php
    }
}
?>
