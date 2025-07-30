<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;

class Snn_Marquee_Slider_Carousel extends Element {
    public $category     = 'snn'; // Using 'snn' category as per your examples
    public $name         = 'snn-marquee-slider';
    public $icon         = 'ti-layout-slider-alt'; // A suitable icon for a marquee/slider
    public $css_selector = '.snn-marquee-wrapper';
    public $scripts      = []; // Scripts will be enqueued inline
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Marquee Slider Carousel', 'bricks' );
    }

    public function set_controls() {
        // --- Repeater for Marquee Items ---
        $this->controls['items'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Marquee Items', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => 'text', // Use the 'text' field for the repeater item title
            'placeholder'   => esc_html__( 'Marquee Item', 'bricks' ),
            'fields'        => [
                'image' => [
                    'label' => esc_html__( 'Image', 'bricks' ),
                    'type'  => 'image',
                ],
                'text' => [
                    'label' => esc_html__( 'Text', 'bricks' ),
                    'type'  => 'text',
                ],
                 'link' => [
                    'label' => esc_html__( 'Link (Optional)', 'bricks' ),
                    'type'  => 'link',
                ],
            ],
        ];

        // --- Marquee Settings ---
        $this->controls['direction'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Direction', 'bricks' ),
            'type'    => 'select',
            'options' => [
                'left'  => esc_html__( 'Left', 'bricks' ),
                'right' => esc_html__( 'Right', 'bricks' ),
                'up'    => esc_html__( 'Up', 'bricks' ),
                'down'  => esc_html__( 'Down', 'bricks' ),
            ],
            'default' => 'left',
            'inline'  => true,
        ];

        $this->controls['duration'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Duration (Speed)', 'bricks' ),
            'type'    => 'number',
            'unit'    => 's',
            'default' => 30,
            'inline'  => true,
        ];

        $this->controls['gap'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Gap', 'bricks' ),
            'type'    => 'number',
            'units'   => ['px', 'rem', 'em', '%'],
            'default' => '20px',
            'inline'  => true,
        ];

        $this->controls['pauseOnHover'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Pause on Hover', 'bricks' ),
            'type'    => 'checkbox',
            'default' => false,
            'inline'  => true,
        ];
        
        $this->controls['enableFade'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Edge Fade', 'bricks' ),
            'type'    => 'checkbox',
            'default' => false,
            'inline'  => true,
        ];

        // --- Item Style Settings ---
        $this->controls['itemWidth'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Item Max Width', 'bricks' ),
            'type'    => 'number',
            'units'   => ['px', 'rem', 'em', '%'],
            'default' => '',
            'css'     => [
                [
                    'property' => 'max-width', 
                    'selector' => '.marquee__item',
                ],
            ],
            'inline'  => true,
        ];

        $this->controls['imageHeight'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Image Height', 'bricks' ),
            'type'    => 'number',
            'units'   => ['px', 'rem', 'em'],
            'default' => '50px',
            'css'     => [
                [
                    'property' => 'height',
                    'selector' => '.marquee__item img',
                ],
            ],
        ];

        $this->controls['imageEffect'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Image Effect on Hover', 'bricks' ),
            'type'    => 'select',
            'options' => [
                'none'      => esc_html__( 'None', 'bricks' ),
                'grayscale' => esc_html__( 'Grayscale to Color', 'bricks' ),
                'opacity'   => esc_html__( 'Fade In', 'bricks' ),
            ],
            'default' => 'none',
            'inline'  => true,
        ];

        $this->controls['textTypography'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Text Typography', 'bricks' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'typography',
                    'selector' => '.marquee__item--text',
                ],
            ],
        ];
    }

    public function render() {
        $settings = $this->settings;
        $unique_id = 'snn-marquee-' . $this->id;

        // Set root attributes
        $this->set_attribute( '_root', 'class', ['snn-marquee-wrapper', $unique_id] );
        $this->set_attribute( '_root', 'data-direction', $settings['direction'] ?? 'left' );
        $this->set_attribute( '_root', 'data-duration', ($settings['duration'] ?? 30) . 's' );

        // --- Start Output ---
        echo '<div ' . $this->render_attributes( '_root' ) . '>';

        // --- Dynamic CSS Generation ---
        $gap = $settings['gap'] ?? '1.5rem';
        $pause_on_hover = $settings['pauseOnHover'] ?? false;
        $image_effect = $settings['imageEffect'] ?? 'none';
        $enable_fade = $settings['enableFade'] ?? false; 

        echo "<style>
            /* Marquee Container */
            .{$unique_id} {
                --marquee-gap: {$gap};
                --marquee-duration: " . ($settings['duration'] ?? 30) . "s;
                --marquee-direction: forwards;
                display: flex;
                overflow: hidden;
                user-select: none;
                width: 100%;
                position: relative;
            }

            /* Marquee Track */
            .{$unique_id} .marquee__track {
                display: flex;
                flex-shrink: 0;
                justify-content: flex-start;
                gap: var(--marquee-gap);
                min-width: 100%;
            }

            /* Pause on Hover */
            " . ($pause_on_hover ? "
            .{$unique_id}:hover .marquee__track {
                animation-play-state: paused;
            }
            " : "") . "

            /* --- Edge Fade Effect (Conditional) --- */
            " . ($enable_fade ? "
            .{$unique_id}[data-direction=\"left\"],
            .{$unique_id}[data-direction=\"right\"] {
                -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
                mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
            }
            .{$unique_id}[data-direction=\"up\"],
            .{$unique_id}[data-direction=\"down\"] {
                -webkit-mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
                mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
            }
            " : "") . "

            /* --- Directional Styles --- */
            /* Horizontal Animation */
            .{$unique_id}[data-direction=\"left\"] .marquee__track {
                animation: marquee-horizontal var(--marquee-duration) linear infinite var(--marquee-direction);
            }
            .{$unique_id}[data-direction=\"right\"] .marquee__track {
                animation: marquee-horizontal var(--marquee-duration) linear infinite reverse;
            }

            /* Vertical Container & Animation */
            .{$unique_id}[data-direction=\"up\"],
            .{$unique_id}[data-direction=\"down\"] {
                max-height: 500px;
            }
            .{$unique_id}[data-direction=\"up\"] .marquee__track,
            .{$unique_id}[data-direction=\"down\"] .marquee__track {
                flex-direction: column;
                min-width: auto;
                min-height: 100%;
            }
            .{$unique_id}[data-direction=\"up\"] .marquee__track {
                animation: marquee-vertical var(--marquee-duration) linear infinite var(--marquee-direction);
            }
            .{$unique_id}[data-direction=\"down\"] .marquee__track {
                animation: marquee-vertical var(--marquee-duration) linear infinite reverse;
            }

            /* --- Content Item Styles --- */
            .{$unique_id} .marquee__item {
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                overflow: hidden; /* Added to contain children */
            }
            .{$unique_id} .marquee__item--text {
                white-space: normal; /* Changed from nowrap to normal */
                overflow: hidden; /* Added to hide overflow text */
                text-overflow: ellipsis; /* Added to show ... for truncated text */
                text-align: center; /* Added for better look on wrapped text */
            }
            .{$unique_id} .marquee__item img {
                width: auto;
                max-width: none;
                transition: all 0.3s ease;
            }

            /* Image Hover Effects */
            " . ($image_effect === 'grayscale' ? "
            .{$unique_id} .marquee__item img {
                filter: grayscale(1);
                opacity: 0.7;
            }
            .{$unique_id} .marquee__item:hover img {
                filter: grayscale(0);
                opacity: 1;
            }
            " : "") . "
            " . ($image_effect === 'opacity' ? "
            .{$unique_id} .marquee__item img {
                opacity: 0.6;
            }
            .{$unique_id} .marquee__item:hover img {
                opacity: 1;
            }
            " : "") . "

            /* --- Keyframes & Accessibility --- */
            @keyframes marquee-horizontal {
                from { transform: translateX(0); }
                to { transform: translateX(calc(-50% - var(--marquee-gap) / 2)); }
            }
            @keyframes marquee-vertical {
                from { transform: translateY(0); }
                to { transform: translateY(calc(-50% - var(--marquee-gap) / 2)); }
            }

            /* Accessibility - Respects user's motion preference */
            @media (prefers-reduced-motion: reduce) {
                .{$unique_id} .marquee__track {
                    animation-play-state: paused !important;
                }
            }

            /* Performance - Pauses animation when element is not in view */
            .{$unique_id}:not(.is-in-view) .marquee__track {
                animation-play-state: paused;
            }
        </style>";

        // --- HTML Rendering ---
        echo '<div class="marquee__track">';
        if ( ! empty( $settings['items'] ) && is_array( $settings['items'] ) ) {
            foreach ( $settings['items'] as $item ) {
                $has_link = ! empty( $item['link']['url'] );
                $tag = $has_link ? 'a' : 'div';

                // Set link attributes if a link is provided
                if ( $has_link ) {
                    $this->set_link_attributes( "item-{$item['_id']}", $item['link'] );
                }

                echo "<{$tag} class='marquee__item' " . ($has_link ? $this->render_attributes( "item-{$item['_id']}" ) : '') . ">";

                if ( ! empty( $item['image']['id'] ) ) {
                    echo wp_get_attachment_image( $item['image']['id'], 'full', false, ['loading' => 'lazy'] );
                }
                if ( ! empty( $item['text'] ) ) {
                    echo '<div class="marquee__item--text">' . esc_html( $item['text'] ) . '</div>';
                }

                echo "</{$tag}>";
            }
        }
        echo '</div>'; // .marquee__track

        // --- Inline JavaScript for cloning and observation ---
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const marqueeEl = document.querySelector('.<?php echo esc_js( $unique_id ); ?>');
                if (!marqueeEl) return;

                const setupMarquee = (el) => {
                    const track = el.querySelector('.marquee__track');
                    if (!track || track.children.length <= 1) return;

                    // Clone items for a seamless loop
                    const originalItems = Array.from(track.children);
                    originalItems.forEach(item => {
                        const clone = item.cloneNode(true);
                        clone.setAttribute('aria-hidden', 'true');
                        track.appendChild(clone);
                    });
                };

                // IntersectionObserver for performance
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in-view');
                        } else {
                            entry.target.classList.remove('is-in-view');
                        }
                    });
                }, { threshold: 0.1 });

                setupMarquee(marqueeEl);
                observer.observe(marqueeEl);
            });
        </script>
        <?php

        echo '</div>'; // .snn-marquee-wrapper
    }
}