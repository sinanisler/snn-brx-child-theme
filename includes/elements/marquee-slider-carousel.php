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
            'css'     => [
                [
                    'property' => 'gap',
                    'selector' => '.marquee__track',
                ],
            ],
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

        $this->controls['enableDragScroll'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Drag Scroll', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
            'inline'  => true,
            'description' => esc_html__( 'Let users grab & drag the marquee, flick to slide faster. Momentum gradually slows back to normal speed — like phone scrolling.', 'snn' ),
        ];

        $this->controls['verticalMaxHeight'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Vertical Max Height', 'snn' ),
            'type'    => 'number',
            'units'   => ['px', 'rem', 'em', 'vh'],
            'default' => '500px',
            'css'     => [
                [
                    'property' => 'max-height',
                    'selector' => '',
                ],
            ],
            'inline'  => true,
            'required' => ['direction', '=', ['up', 'down']],
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
            'default' => '100',
            'css'     => [
                [
                    'property' => 'height',
                    'selector' => '.marquee__item img',
                ],
            ],
        ];

        $this->controls['imageAspectRatio'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Image Aspect Ratio', 'snn' ),
            'type'    => 'number',
            'default' => '',
            'placeholder' => '1',
            'step'    => 0.1,
            'min'     => 0.1,
            'css'     => [
                [
                    'property' => 'aspect-ratio',
                    'selector' => '.marquee__item img',
                ],
            ],
            'inline'  => true,
        ];

        $this->controls['imageObjectFit'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Image Object Fit', 'snn' ),
            'type'    => 'select',
            'options' => [
                'cover'      => esc_html__( 'Cover', 'snn' ),
                'contain'    => esc_html__( 'Contain', 'snn' ),
                'fill'       => esc_html__( 'Fill', 'snn' ),
                'none'       => esc_html__( 'None', 'snn' ),
                'scale-down' => esc_html__( 'Scale Down', 'snn' ),
            ],
            'default' => 'cover',
            'css'     => [
                [
                    'property' => 'object-fit',
                    'selector' => '.marquee__item img',
                ],
            ],
            'inline'  => true,
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
        $enable_drag    = $settings['enableDragScroll'] ?? false;
        $duration       = $settings['duration'] ?? 30;
        $vertical_max_height = $settings['verticalMaxHeight'] ?? '500px'; 

        echo "<style>
            /* Marquee Container */
            .{$unique_id} {
                --marquee-gap: {$gap};
                --marquee-duration: {$duration}s;
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
            " . ($enable_drag ? "
            /* Drag scroll enabled: cursor affordance & touch handling */
            .{$unique_id}.snn-marquee--draggable {
                cursor: grab;
                touch-action: none;
            }
            .{$unique_id}.snn-marquee--draggable.is-dragging {
                cursor: grabbing;
            }
            .{$unique_id}.snn-marquee--draggable .marquee__track {
                user-select: none;
                -webkit-user-select: none;
            }
            " : "
            /* CSS Animation (default mode) */
            /* Horizontal Animation */
            .{$unique_id}[data-direction=\"left\"] .marquee__track {
                animation: marquee-horizontal var(--marquee-duration) linear infinite var(--marquee-direction);
            }
            .{$unique_id}[data-direction=\"right\"] .marquee__track {
                animation: marquee-horizontal var(--marquee-duration) linear infinite reverse;
            }
            " . ($pause_on_hover ? "
            .{$unique_id}:hover .marquee__track {
                animation-play-state: paused;
            }
            " : "") . "
            /* Pause when not in view (CSS-only mode) */
            .{$unique_id}:not(.is-in-view) .marquee__track {
                animation-play-state: paused;
            }
            ") . "

            /* Vertical Container */
            .{$unique_id}[data-direction=\"up\"],
            .{$unique_id}[data-direction=\"down\"] {
                max-height: {$vertical_max_height};
            }
            .{$unique_id}[data-direction=\"up\"] .marquee__track,
            .{$unique_id}[data-direction=\"down\"] .marquee__track {
                flex-direction: column;
                min-width: auto;
                min-height: 100%;
            }
            " . (!$enable_drag ? "
            .{$unique_id}[data-direction=\"up\"] .marquee__track {
                animation: marquee-vertical var(--marquee-duration) linear infinite var(--marquee-direction);
            }
            .{$unique_id}[data-direction=\"down\"] .marquee__track {
                animation: marquee-vertical var(--marquee-duration) linear infinite reverse;
            }
            " : "") . "

            /* --- Content Item Styles --- */
            .{$unique_id} .marquee__item {
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                overflow: hidden;
            }
            .{$unique_id} .marquee__item--text {
                white-space: normal;
                overflow: hidden;
                text-overflow: ellipsis;
                text-align: center;
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

            /* --- Keyframes (always defined for reference / non-drag mode) --- */
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
                    echo '<div class="marquee__item--text">' . wp_kses_post( $item['text'] ) . '</div>';
                }

                echo "</{$tag}>";
            }
        }
        echo '</div>'; // .marquee__track

        // --- Inline JavaScript ---
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const marqueeEl = document.querySelector('.<?php echo esc_js( $unique_id ); ?>');
                if (!marqueeEl) return;

                const direction = marqueeEl.dataset.direction || 'left';
                const isVertical = direction === 'up' || direction === 'down';
                const enableDrag = <?php echo json_encode( $enable_drag ); ?>;
                const pauseOnHover = <?php echo json_encode( $pause_on_hover ); ?>;
                const durationSec = parseFloat(marqueeEl.dataset.duration) || 30;

                // --- Shared: Clone logic ---
                const cloneItems = () => {
                    const track = marqueeEl.querySelector('.marquee__track');
                    if (!track || track.children.length === 0) return { originalItems: [], contentSize: 0, track: null };

                    const originalItems = Array.from(track.querySelectorAll('.marquee__item:not(.is-clone)'));
                    track.querySelectorAll('.is-clone').forEach(clone => clone.remove());

                    if (originalItems.length === 0) return { originalItems: [], contentSize: 0, track: null };

                    const containerSize = isVertical ? marqueeEl.offsetHeight : marqueeEl.offsetWidth;
                    let trackSize = isVertical ? track.scrollHeight : track.scrollWidth;

                    if (trackSize >= containerSize) {
                        while (trackSize < containerSize * 2) {
                            originalItems.forEach(item => {
                                const clone = item.cloneNode(true);
                                clone.setAttribute('aria-hidden', 'true');
                                clone.classList.add('is-clone');
                                track.appendChild(clone);
                            });
                            trackSize = isVertical ? track.scrollHeight : track.scrollWidth;
                        }
                    }

                    // Measure one set of original items (the loop distance)
                    let originalSize = 0;
                    originalItems.forEach(item => {
                        originalSize += isVertical ? item.offsetHeight : item.offsetWidth;
                    });
                    const computedGap = parseFloat(getComputedStyle(track).gap) || 0;
                    if (originalItems.length > 1 && !isNaN(computedGap)) {
                        originalSize += computedGap * (originalItems.length - 1);
                    }

                    return { originalItems, contentSize: originalSize, track };
                };

                <?php if ( $enable_drag ) : ?>
                // ============================================================
                // DRAG-SCROLL MODE: JS-driven animation with flick inertia
                // ============================================================
                marqueeEl.classList.add('snn-marquee--draggable');

                let position = 0;
                let velocity = 0;          // px/s
                let isDragging = false;
                let isHovering = false;
                let isInView = true;
                let animationId = null;
                let lastTimestamp = 0;
                let contentSize = 0;
                let autoSpeed = 0;
                const directionSign = (direction === 'left' || direction === 'up') ? -1 : 1;

                // Velocity tracking for flick calculation
                let pointerStartX = 0, pointerStartY = 0;
                let positionAtDragStart = 0;
                let velocitySamples = [];
                const VELOCITY_SAMPLE_WINDOW = 100; // ms

                // Physics — tuned for soft phone-like feel
                const FRICTION = 0.97;           // per frame @ 60fps
                const VELOCITY_THRESHOLD = 0.5;  // px/frame — resume auto below this
                const MIN_VELOCITY = 0.1;        // snap to zero

                const tick = (timestamp) => {
                    if (!lastTimestamp) lastTimestamp = timestamp;
                    let dt = (timestamp - lastTimestamp) / 1000;
                    lastTimestamp = timestamp;
                    if (dt > 0.15) dt = 0.016;   // clamp tab-switch jumps
                    if (dt <= 0) dt = 0.016;

                    if (!isDragging && isInView && contentSize > 0) {
                        if (Math.abs(velocity) > VELOCITY_THRESHOLD) {
                            // Flick decay — frame-rate-independent exponential friction
                            velocity *= Math.pow(FRICTION, dt * 60);
                            if (Math.abs(velocity) < MIN_VELOCITY) velocity = 0;
                            position += velocity * dt;
                        } else {
                            // Resume normal auto-scroll
                            velocity = 0;
                            if (!isHovering || !pauseOnHover) {
                                position += autoSpeed * directionSign * dt;
                            }
                        }
                    }

                    // Seamless infinite loop wrapping
                    if (contentSize > 0) {
                        while (position <= -contentSize) position += contentSize;
                        while (position > 0) position -= contentSize;
                    }

                    const track = marqueeEl.querySelector('.marquee__track');
                    if (track) {
                        const prop = isVertical ? 'translateY' : 'translateX';
                        track.style.transform = `${prop}(${position}px)`;
                        track.style.willChange = 'transform';
                    }

                    animationId = requestAnimationFrame(tick);
                };

                // --- Pointer event handlers ---
                const onPointerDown = (e) => {
                    if (e.button !== undefined && e.button !== 0) return;
                    isDragging = true;
                    marqueeEl.classList.add('is-dragging');
                    pointerStartX = e.clientX;
                    pointerStartY = e.clientY;
                    positionAtDragStart = position;
                    velocitySamples = [{ time: performance.now(), pos: isVertical ? e.clientY : e.clientX }];
                    marqueeEl.setPointerCapture(e.pointerId);
                    e.preventDefault();
                };

                const onPointerMove = (e) => {
                    if (!isDragging) return;
                    const currentPos = isVertical ? e.clientY : e.clientX;
                    const startPos = isVertical ? pointerStartY : pointerStartX;
                    position = positionAtDragStart + (currentPos - startPos);

                    const now = performance.now();
                    velocitySamples.push({ time: now, pos: currentPos });
                    while (velocitySamples.length > 1 && now - velocitySamples[0].time > VELOCITY_SAMPLE_WINDOW) {
                        velocitySamples.shift();
                    }
                    e.preventDefault();
                };

                const onPointerUp = (e) => {
                    if (!isDragging) return;
                    isDragging = false;
                    marqueeEl.classList.remove('is-dragging');
                    marqueeEl.releasePointerCapture(e.pointerId);

                    // Calculate flick velocity (px/s) from recent samples
                    if (velocitySamples.length >= 2) {
                        const first = velocitySamples[0];
                        const last = velocitySamples[velocitySamples.length - 1];
                        const timeDelta = (last.time - first.time) / 1000;
                        const posDelta = last.pos - first.pos;
                        if (timeDelta > 0.01) {
                            velocity = posDelta / timeDelta;
                            // Invert: dragging right → positive delta → content should move right
                            // But our position decreases for left direction, so keep as-is
                        }
                    }
                    velocitySamples = [];
                };

                const onHoverEnter = () => { isHovering = true; };
                const onHoverLeave = () => { isHovering = false; };

                // --- Visibility observer ---
                const visObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        isInView = entry.isIntersecting;
                        if (isInView) {
                            marqueeEl.classList.add('is-in-view');
                        } else {
                            marqueeEl.classList.remove('is-in-view');
                        }
                    });
                }, { threshold: 0.1 });
                visObserver.observe(marqueeEl);

                // --- Resize observer (re-clone + recalc on size change) ---
                let resizeTimer = null;
                const resizeObserver = new ResizeObserver(() => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        const result = cloneItems();
                        if (result.contentSize > 0) {
                            contentSize = result.contentSize;
                            autoSpeed = contentSize / Math.max(durationSec, 1);
                            // Re-wrap position into new bounds
                            while (position <= -contentSize) position += contentSize;
                            while (position > 0) position -= contentSize;
                        }
                    }, 200);
                });
                resizeObserver.observe(marqueeEl);

                // --- Bind events ---
                marqueeEl.addEventListener('pointerdown', onPointerDown);
                marqueeEl.addEventListener('pointermove', onPointerMove);
                marqueeEl.addEventListener('pointerup', onPointerUp);
                marqueeEl.addEventListener('pointercancel', onPointerUp);
                marqueeEl.addEventListener('pointerleave', onPointerUp);
                marqueeEl.addEventListener('mouseenter', onHoverEnter);
                marqueeEl.addEventListener('mouseleave', onHoverLeave);

                // --- Init ---
                setTimeout(() => {
                    const result = cloneItems();
                    if (result.contentSize > 0) {
                        contentSize = result.contentSize;
                        autoSpeed = contentSize / Math.max(durationSec, 1);
                    }
                    // Start the animation loop
                    lastTimestamp = 0;
                    if (animationId) cancelAnimationFrame(animationId);
                    animationId = requestAnimationFrame(tick);
                }, 100);

                <?php else : ?>
                // ============================================================
                // CSS ANIMATION MODE (default): clone + observe only
                // ============================================================
                const setupMarquee = () => {
                    cloneItems();
                };

                const resizeObserver = new ResizeObserver(() => setupMarquee());
                resizeObserver.observe(marqueeEl);

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

                setTimeout(setupMarquee, 100);
                <?php endif; ?>
            });
        </script>
        <?php

        echo '</div>'; // .snn-marquee-wrapper
    }
}