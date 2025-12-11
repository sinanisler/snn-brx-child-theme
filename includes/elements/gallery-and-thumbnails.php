<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;

class Snn_Gallery_And_Thumbnails extends Element {
    public $category      = 'snn';
    public $name          = 'snn-gallery-thumbnails';
    public $icon          = 'ti-gallery';
    public $css_selector  = '.snn-gallery-wrapper';
    public $scripts       = [];
    public $nestable      = false;

    public function get_label() {
        return esc_html__( 'Image Gallery and Thumbnails', 'snn' );
    }

    public function set_controls() {
        // --- Image Gallery Control ---
        $this->controls['images'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Images', 'snn' ),
            'type'        => 'image-gallery',
            'placeholder' => esc_html__( 'Select images from media library', 'snn' ),
        ];

        // --- Thumbnail Position ---
        $this->controls['thumbnailPosition'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Thumbnail Position', 'snn' ),
            'type'    => 'select',
            'options' => [
                'bottom' => esc_html__( 'Bottom', 'snn' ),
                'top'    => esc_html__( 'Top', 'snn' ),
                'left'   => esc_html__( 'Left', 'snn' ),
                'right'  => esc_html__( 'Right', 'snn' ),
            ],
            'default' => 'bottom',
            'inline'  => true,
        ];

        // --- Gap Between Main and Thumbnails ---
        $this->controls['gap'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Gap', 'snn' ),
            'type'    => 'number',
            'units'   => true,
            'css'     => [
                [
                    'property' => 'gap',
                    'selector' => '.snn-gallery',
                ],
            ],
            'default' => '15px',
            'inline'  => true,
        ];

        // --- Thumbnail Gap ---
        $this->controls['thumbnailGap'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Thumbnail Gap', 'snn' ),
            'type'    => 'number',
            'units'   => true,
            'css'     => [
                [
                    'property' => 'gap',
                    'selector' => '.snn-thumbnails',
                ],
            ],
            'default' => '10px',
            'inline'  => true,
        ];

        // --- Thumbnail Size ---
        $this->controls['thumbnailSize'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Thumbnail Size', 'snn' ),
            'type'    => 'number',
            'units'   => true,
            'css'     => [
                [
                    'property' => 'width',
                    'selector' => '.snn-thumbnail',
                ],
                [
                    'property' => 'height',
                    'selector' => '.snn-thumbnail',
                ],
                [
                    'property' => 'width',
                    'selector' => '.snn-thumbnails-left .snn-thumbnails',
                ],
                [
                    'property' => 'width',
                    'selector' => '.snn-thumbnails-right .snn-thumbnails',
                ],
            ],
            'default' => '80px',
            'inline'  => true,
        ];

        // --- Main Image Aspect Ratio ---
        $this->controls['mainImageAspectRatio'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Main Image Aspect Ratio', 'snn' ),
            'type'    => 'number',
            'units'   => false,
            'css'     => [
                [
                    'property' => 'aspect-ratio',
                    'selector' => '.snn-main-image-item',
                ],
            ],
            'default' => '1',
            'inline'  => true,
        ];

        // --- Enable Zoom on Hover ---
        $this->controls['enableZoom'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Thumbnail Zoom on Hover', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
            'inline'  => true,
        ];
    }

    public function render() {
        $settings = $this->settings;

        // Set root element attributes
        $this->set_attribute( '_root', 'class', 'snn-gallery-wrapper' );
        $this->set_attribute( '_root', 'data-position', isset($settings['thumbnailPosition']) ? $settings['thumbnailPosition'] : 'bottom' );

        // Get the actual element ID with brxe- prefix for CSS/JS targeting
        $element_id = 'brxe-' . $this->id;

        // Output root opening tag
        echo "<div {$this->render_attributes( '_root' )}>";

        // Get settings
        $thumbnail_position   = isset($settings['thumbnailPosition']) ? $settings['thumbnailPosition'] : 'bottom';
        $enable_zoom          = isset($settings['enableZoom']) ? $settings['enableZoom'] : true;

        // Static CSS (non-responsive styles only)
        ?>
        <style>
            /* Gallery Container */
            #<?php echo $element_id; ?> {
                max-width: 100%;
                margin: 0 auto;
            }

            #<?php echo $element_id; ?> .snn-gallery {
                display: flex;
            }

            /* Position Layouts */
            #<?php echo $element_id; ?> .snn-gallery.snn-thumbnails-bottom {
                flex-direction: column;
            }

            #<?php echo $element_id; ?> .snn-gallery.snn-thumbnails-top {
                flex-direction: column-reverse;
            }

            #<?php echo $element_id; ?> .snn-gallery.snn-thumbnails-left {
                flex-direction: row-reverse;
            }

            #<?php echo $element_id; ?> .snn-gallery.snn-thumbnails-right {
                flex-direction: row;
            }

            /* Main Image */
            #<?php echo $element_id; ?> .snn-main-image {
                flex: 1;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
            }

            #<?php echo $element_id; ?> .snn-main-image-item {
                display: none;
                width: 100%;
            }

            #<?php echo $element_id; ?> .snn-main-image-item.active {
                display: block;
            }

            #<?php echo $element_id; ?> .snn-main-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            /* Thumbnails Container */
            #<?php echo $element_id; ?> .snn-thumbnails {
                display: flex;
            }

            /* Thumbnail Layout Adjustments */
            #<?php echo $element_id; ?> .snn-thumbnails-bottom .snn-thumbnails,
            #<?php echo $element_id; ?> .snn-thumbnails-top .snn-thumbnails {
                flex-direction: row;
                justify-content: center;
                flex-wrap: wrap;
            }

            #<?php echo $element_id; ?> .snn-thumbnails-left .snn-thumbnails,
            #<?php echo $element_id; ?> .snn-thumbnails-right .snn-thumbnails {
                flex-direction: column;
            }

            /* Thumbnail Items */
            #<?php echo $element_id; ?> .snn-thumbnail {
                cursor: pointer;
                overflow: hidden;
                transition: all 0.3s ease;
                flex-shrink: 0;
            }

            #<?php echo $element_id; ?> .snn-thumbnails-left .snn-thumbnail,
            #<?php echo $element_id; ?> .snn-thumbnails-right .snn-thumbnail {
                width: 100%;
            }

            #<?php echo $element_id; ?> .snn-thumbnail img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform 0.3s ease;
            }

            <?php if ($enable_zoom): ?>
            #<?php echo $element_id; ?> .snn-thumbnail:hover {
                transform: scale(1.05);
            }
            <?php endif; ?>

            /* Accessibility */
            @media (prefers-reduced-motion: reduce) {
                #<?php echo $element_id; ?> .snn-thumbnail,
                #<?php echo $element_id; ?> .snn-thumbnail img {
                    transition: none !important;
                    transform: none !important;
                }
            }
        </style>
        <?php

        // HTML Rendering
        if ( isset( $settings['images']['images'] ) && is_array( $settings['images']['images'] ) ) {
            $images = $settings['images']['images'];
            $default_size = isset( $settings['images']['size'] ) ? $settings['images']['size'] : 'large';
            
            echo '<div class="snn-gallery snn-thumbnails-' . esc_attr( $thumbnail_position ) . '">';
            
            // Main Image Container
            echo '<div class="snn-main-image">';
            foreach ( $images as $index => $image ) {
                if ( isset( $image['id'] ) ) {
                    $is_active = $index === 0 ? ' active' : '';
                    echo '<div class="snn-main-image-item' . esc_attr( $is_active ) . '" data-index="' . esc_attr( $index ) . '">';
                    echo wp_get_attachment_image(
                        $image['id'],
                        $default_size,
                        false,
                        ['class' => 'snn-main-image-img']
                    );
                    echo '</div>';
                }
            }
            echo '</div>';

            // Thumbnails
            echo '<div class="snn-thumbnails">';
            foreach ( $images as $index => $image ) {
                if ( isset( $image['id'] ) ) {
                    $is_active = $index === 0 ? ' active' : '';
                    echo '<div class="snn-thumbnail' . esc_attr( $is_active ) . '" data-index="' . esc_attr( $index ) . '">';
                    echo wp_get_attachment_image(
                        $image['id'],
                        'thumbnail',
                        false
                    );
                    echo '</div>';
                }
            }
            echo '</div>';
            
            echo '</div>';
        } else {
            echo '<p style="padding: 20px; text-align: center;">' . esc_html__( 'No image(s) selected.', 'bricks' ) . '</p>';
        }

        // JavaScript
        ?>
        <script>
            (function() {
                const galleryEl = document.getElementById('<?php echo esc_js( $element_id ); ?>');
                if (!galleryEl) return;

                const mainImages = galleryEl.querySelectorAll('.snn-main-image-item');
                const thumbnails = galleryEl.querySelectorAll('.snn-thumbnail');

                if (mainImages.length === 0 || thumbnails.length === 0) return;

                thumbnails.forEach((thumbnail, index) => {
                    thumbnail.addEventListener('click', function() {
                        const targetIndex = this.getAttribute('data-index');
                        
                        thumbnails.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');

                        mainImages.forEach(img => img.classList.remove('active'));
                        const targetMainImage = galleryEl.querySelector('.snn-main-image-item[data-index="' + targetIndex + '"]');
                        if (targetMainImage) {
                            targetMainImage.classList.add('active');
                        }
                    });

                    thumbnail.setAttribute('tabindex', '0');
                    thumbnail.setAttribute('role', 'button');
                    thumbnail.setAttribute('aria-label', 'View image ' + (index + 1));
                    
                    thumbnail.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    });
                });
            })();
        </script>
        <?php

        echo '</div>';
    }
}