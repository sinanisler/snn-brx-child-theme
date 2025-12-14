<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;

class Snn_Marquee_Slider_Carousel extends Element {
    public $category      = 'snn'; // Using 'snn' category as per your examples
    public $name          = 'snn-marquee-slider';
    public $icon          = 'ti-layout-slider-alt'; // A suitable icon for a marquee/slider
    public $css_selector  = ''; // Empty = styles apply to root element (backwards compatible with Bricks)
    public $scripts       = []; // Scripts will be enqueued inline
    public $nestable      = false;

    public function get_label() {
        return esc_html__( 'Marquee Slider Carousel', 'snn' );
    }

    public function set_controls() {
        // --- Repeater for Marquee Items ---
        $this->controls['items'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Marquee Items', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'text', // Use the 'text' field for the repeater item title
            'placeholder'   => esc_html__( 'Marquee Item', 'snn' ),
            'fields'        => [
                'image' => [
                    'label' => esc_html__( 'Image', 'snn' ),
                    'type'  => 'image',
                ],
                'text' => [
                    'label' => esc_html__( 'Text', 'snn' ),
                    'type'  => 'text',
                ],
                 'link' => [
                    'label' => esc_html__( 'Link (Optional)', 'snn' ),
                    'type'  => 'link',
                ],
            ],
        ];

        // --- Marquee Settings ---
        $this->controls['direction'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Direction', 'snn' ),
            'type'    => 'select',
            'options' => [
                'left'  => esc_html__( 'Left', 'snn' ),
                'right' => esc_html__( 'Right', 'snn' ),
                'up'    => esc_html__( 'Up', 'snn' ),
                'down'  => esc_html__( 'Down', 'snn' ),
            ],
            'default' => 'left',
            'inline'  => true,
        ];

        $this->controls['duration'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Duration (Speed)', 'snn' ),
            'type'    => 'number',
            'unit'    => 's',
            'default' => 30,
            'inline'  => true,
        ];

        $this->controls['gap'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Gap', 'snn' ),
            'type'    => 'number',
            'units'   => ['px', 'rem', 'em', '%'],
            'default' => '20px',
            'inline'  => true,
        ];

        $this->controls['pauseOnHover'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Pause on Hover', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
            'inline'  => true,
        ];
        
        $this->controls['enableFade'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Edge Fade', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
            'inline'  => true,
        ];

        // --- Item Style Settings ---
        $this->controls['itemWidth'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Item Max Width', 'snn' ),
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
            'label'   => esc_html__( 'Image Height', 'snn' ),
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
            'label'   => esc_html__( 'Image Effect on Hover', 'snn' ),
            'type'    => 'select',
            'options' => [
                'none'      => esc_html__( 'None', 'snn' ),
                'grayscale' => esc_html__( 'Grayscale to Color', 'snn' ),
                'opacity'   => esc_html__( 'Fade In', 'snn' ),
            ],
            'default' => 'none',
            'inline'  => true,
        ];

        $this->controls['textTypography'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Text Typography', 'snn' ),
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
        $settings  = $this->settings;
        $unique_id = 'snn-marquee-' . $this->id;

        // Set root element attributes (MUST be done before render_attributes)
        // This adds to Bricks' native classes without overriding them
        $this->set_attribute( '_root', 'class', ['snn-marquee-wrapper', $unique_id] );
        $this->set_attribute( '_root', 'data-direction', $settings['direction'] ?? 'left' );
        $this->set_attribute( '_root', 'data-duration', ($settings['duration'] ?? 30) . 's' );

        // --- Start Output ---
        // render_attributes('_root') now includes: Bricks ID, Bricks classes, our classes, and data attributes
        echo "<div {$this->render_attributes( '_root' )}>";

        // --- Dynamic CSS Generation ---
        $gap            = $settings['gap'] ?? '1.5rem';
        $pause_on_hover = $settings['pauseOnHover'] ?? false;
        $image_effect   = $settings['imageEffect'] ?? 'none';
        $enable_fade    = $settings['enableFade'] ?? false; 

        echo "<style>
            /* Marquee Container */
            .{$unique_id} {
                --marquee-gap: {$gap};
                --marquee-duration: " . ($settings['duration'] ?? 30) . "s;
                --marquee-direction: forwards;
                display: flex;
                overflow: hidden;
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
                overflow:  ; /* Added to contain children */
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

            /* --- FIX: Pause on Hover (Moved here to ensure it overrides animation) --- */
            " . ($pause_on_hover ? "
            .{$unique_id}:hover .marquee__track {
                animation-play-state: paused;
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
            foreach ( $settings['items'] as $index => $item ) {
                $has_link = ! empty( $item['link']['url'] );
                $tag      = $has_link ? 'a' : 'div';
                $item_id  = "item-{$this->id}-{$index}"; // Create unique ID using element ID and index

                // Set link attributes if a link is provided
                if ( $has_link ) {
                    $this->set_link_attributes( $item_id, $item['link'] );
                }

                echo "<{$tag} class='marquee__item' " . ($has_link ? $this->render_attributes( $item_id ) : '') . ">";

                if ( ! empty( $item['image']['id'] ) ) {
                    echo wp_get_attachment_image( $item['image']['id'], 'full', false, ['loading' => 'eager'] );
                }
                if ( ! empty( $item['text'] ) ) {
                    echo '<div class="marquee__item--text">' . esc_html( $item['text'] ) . '</div>';
                }

                echo "</{$tag}>";
            }
        }
        echo '</div>'; // .marquee__track

        // --- Inline JavaScript for robust cloning and observation ---
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const marqueeEl = document.querySelector('.<?php echo esc_js( $unique_id ); ?>');
                if (!marqueeEl) return;

                const setupMarquee = () => {
                    const track = marqueeEl.querySelector('.marquee__track');
                    if (!track || track.children.length === 0) return;

                    const direction = marqueeEl.dataset.direction || 'left';
                    const isVertical = direction === 'up' || direction === 'down';
                    
                    // Store original items and remove any pre-existing clones
                    const originalItems = Array.from(track.querySelectorAll('.marquee__item:not(.is-clone)'));
                    track.querySelectorAll('.is-clone').forEach(clone => clone.remove());

                    if (originalItems.length === 0) return;

                    // Get initial sizes
                    const containerSize = isVertical ? marqueeEl.offsetHeight : marqueeEl.offsetWidth;
                    let trackSize = isVertical ? track.scrollHeight : track.scrollWidth;
                    
                    // If content is smaller than the container, cloning is not needed.
                    if (trackSize >= containerSize) {
                        // **This is the core fix:** Clone items until the track is at least double the container size.
                        // This guarantees there's always content to fill the space, preventing the "jump".
                        while (trackSize < containerSize * 2) {
                            originalItems.forEach(item => {
                                const clone = item.cloneNode(true);
                                clone.setAttribute('aria-hidden', 'true');
                                clone.classList.add('is-clone'); // Add a class for easy identification
                                track.appendChild(clone);
                            });
                            trackSize = isVertical ? track.scrollHeight : track.scrollWidth;
                        }
                    }
                };

                // Use ResizeObserver for robust handling of responsive changes.
                const resizeObserver = new ResizeObserver(() => setupMarquee());
                resizeObserver.observe(marqueeEl);
                
                // IntersectionObserver for performance (pauses when off-screen).
                const visibilityObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in-view');
                        } else {
                            entry.target.classList.remove('is-in-view');
                        }
                    });
                }, { threshold: 0.1 });

                visibilityObserver.observe(marqueeEl);
                
                // Initial setup. A small timeout can help ensure all assets are loaded and dimensions are correct.
                setTimeout(setupMarquee, 100);
            });
        </script>
        <?php

        echo '</div>'; // .snn-marquee-wrapper
    }
}