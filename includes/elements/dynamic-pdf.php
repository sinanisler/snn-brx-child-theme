<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;

class Snn_Dynamic_PDF extends Element {
    public $category     = 'snn';
    public $name         = 'dynamic-pdf';
    public $icon         = 'ti-file';
    public $css_selector = '.snn-pdf-viewer-wrapper';
    public $scripts      = ['snnPdfViewer'];

    public function get_label() {
        return esc_html__( 'Dynamic PDF Embed', 'snn' );
    }

    public function set_controls() {
        $this->controls['pdf_file'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'PDF File', 'snn' ),
            'type'  => 'file',
            'description' => esc_html__( 'Enter the full PDF URL', 'snn' ),
        ];

        $this->controls['cover_image'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Cover Image', 'snn' ),
            'type'  => 'image',
            'description' => esc_html__( 'Leave empty to auto-load PDF', 'snn' ),
        ];

        $this->controls['chevron_color'] = [
            'tab'    => 'content',
            'label'  => esc_html__( 'Chevron Color', 'snn' ),
            'type'   => 'color',
            'inline' => true,
            'default' => [
                'hex' => '#000000',
            ],
        ];

        $this->controls['chevron_size'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Chevron Size', 'snn' ),
            'type'    => 'number',
            'unit'    => 'px',
            'default' => 36,
            'inline'  => true,
        ];

        $this->controls['left_arrow_icon'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Left Arrow Icon', 'snn' ),
            'type'    => 'icon',
            'default' => [
                'library' => 'themify',
                'icon'    => 'ti-angle-left',
            ],
        ];

        $this->controls['right_arrow_icon'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Right Arrow Icon', 'snn' ),
            'type'    => 'icon',
            'default' => [
                'library' => 'themify',
                'icon'    => 'ti-angle-right',
            ],
        ];

        $this->controls['two_page_view'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Display Two Pages', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'default' => false,
            'description' => esc_html__( 'Show two PDF pages side by side.', 'snn' ),
        ];

        $this->controls['show_download'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Show Download Button', 'snn' ),
            'type'    => 'checkbox',
            'inline'  => true,
            'default' => false,
        ];

        $this->controls['download_text'] = [
            'tab'      => 'content',
            'label'    => esc_html__( 'Download Text', 'snn' ),
            'type'     => 'text',
            'default'  => 'Download PDF',
            'required' => ['show_download', '=', true],
        ];

        $this->controls['download_typography'] = [
            'tab'      => 'style',
            'label'    => esc_html__( 'Download Button Typography', 'snn' ),
            'type'     => 'typography',
            'css'      => [
                [
                    'property' => 'font',
                    'selector' => '.snn-pdf-download-btn',
                ],
            ],
            'required' => ['show_download', '=', true],
        ];

        $this->controls['download_bg'] = [
            'tab'      => 'style',
            'label'    => esc_html__( 'Download Background', 'snn' ),
            'type'     => 'color',
            'inline'   => true,
            'css'      => [
                [
                    'property' => 'background-color',
                    'selector' => '.snn-pdf-download-btn',
                ],
            ],
            'default'  => [
                'hex' => '#ff0050',
            ],
            'required' => ['show_download', '=', true],
        ];

        $this->controls['download_color'] = [
            'tab'      => 'style',
            'label'    => esc_html__( 'Download Text Color', 'snn' ),
            'type'     => 'color',
            'inline'   => true,
            'css'      => [
                [
                    'property' => 'color',
                    'selector' => '.snn-pdf-download-btn',
                ],
            ],
            'default'  => [
                'hex' => '#ffffff',
            ],
            'required' => ['show_download', '=', true],
        ];

        $this->controls['download_hover_bg'] = [
            'tab'      => 'style',
            'label'    => esc_html__( 'Download Hover Background', 'snn' ),
            'type'     => 'color',
            'inline'   => true,
            'css'      => [
                [
                    'property' => 'background-color',
                    'selector' => '.snn-pdf-download-btn:hover',
                ],
            ],
            'default'  => [
                'hex' => '#d40045',
            ],
            'required' => ['show_download', '=', true],
        ];

        $this->controls['download_hover_color'] = [
            'tab'      => 'style',
            'label'    => esc_html__( 'Download Hover Text Color', 'snn' ),
            'type'     => 'color',
            'inline'   => true,
            'css'      => [
                [
                    'property' => 'color',
                    'selector' => '.snn-pdf-download-btn:hover',
                ],
            ],
            'default'  => [
                'hex' => '#ffffff',
            ],
            'required' => ['show_download', '=', true],
        ];

        $this->controls['download_border'] = [
            'tab'      => 'style',
            'label'    => esc_html__( 'Download Button Border', 'snn' ),
            'type'     => 'border',
            'css'      => [
                [
                    'property' => 'border',
                    'selector' => '.snn-pdf-download-btn',
                ],
            ],
            'required' => ['show_download', '=', true],
        ];
    }



    private function parse_color( $color_setting, $default = '#000000' ) {
        if ( empty( $color_setting ) ) {
            return $default;
        }

        if ( is_array( $color_setting ) ) {
            if ( isset( $color_setting['rgb'] ) ) {
                return $color_setting['rgb'];
            }
            if ( isset( $color_setting['hex'] ) ) {
                return $color_setting['hex'];
            }
            if ( isset( $color_setting['raw'] ) ) {
                return $color_setting['raw'];
            }
        }

        return $color_setting;
    }

    private function render_arrow_icon( $icon_settings, $fallback_char, $extra_classes = [] ) {
        if ( ! empty( $icon_settings ) ) {
            if ( class_exists( '\\Bricks\\Helpers' ) && method_exists( '\\Bricks\\Helpers', 'render_control_icon' ) ) {
                \Bricks\Helpers::render_control_icon( $icon_settings, $extra_classes );
                return;
            }

            if ( ! empty( $icon_settings['icon'] ) ) {
                $classes = array_filter( array_merge( $extra_classes, [ $icon_settings['icon'] ] ) );
                echo '<i class="' . esc_attr( implode( ' ', $classes ) ) . '"></i>';
                return;
            }
        }

        echo esc_html( $fallback_char );
    }

    public function render() {
        $settings = $this->settings;

        $pdf_url = ! empty( $settings['pdf_file']['url'] ) ? $settings['pdf_file']['url'] : '';

        if ( empty( $pdf_url ) ) {
            echo '<div style="padding:20px;background:#f3f3f3;text-align:center;">';
            echo '<p><strong>' . esc_html__( 'No PDF URL provided.', 'snn' ) . '</strong></p>';
            echo '<p>' . esc_html__( 'Please add a PDF URL in the element settings.', 'snn' ) . '</p>';
            echo '</div>';
            return;
        }

        $cover_url = '';
        if ( ! empty( $settings['cover_image']['url'] ) ) {
            $cover_url = esc_url( $settings['cover_image']['url'] );
        }

        $chevron_color = $this->parse_color( isset( $settings['chevron_color'] ) ? $settings['chevron_color'] : null, '#000000' );
        $chevron_size  = isset( $settings['chevron_size'] ) ? intval( $settings['chevron_size'] ) : 36;
        $left_arrow_icon = isset( $settings['left_arrow_icon'] ) ? $settings['left_arrow_icon'] : null;
        $right_arrow_icon = isset( $settings['right_arrow_icon'] ) ? $settings['right_arrow_icon'] : null;
        $two_page_view = ! empty( $settings['two_page_view'] );
        $show_download = isset( $settings['show_download'] ) && $settings['show_download'];
        $download_text = isset( $settings['download_text'] ) ? esc_html( $settings['download_text'] ) : 'Download PDF';

        $uid = 'snn-pdf-' . uniqid();

        $this->set_attribute( '_root', 'class', 'snn-pdf-viewer-wrapper' );

        ?>
        <div <?php echo $this->render_attributes( '_root' ); ?>>
            <style>
                .snn-pdf-viewer-wrapper {
                    width: 100%;
                    height: 100%;
                    position: relative;
                }
                #<?php echo $uid; ?>-container {
                    width: 100%;
                    height: 100%;
                    position: relative;
                }
                .snn-pdf-stage {
                    width: 100%;
                    height: 100%;
                    position: relative;
                }
                .snn-pdf-cover {
                    width: 100%;
                    height: 100%;
                }
                .snn-pdf-cover img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    cursor: pointer;
                }
                .snn-pdf-viewer {
                    display: none;
                    width: 100%;
                    height: 100%;
                }
                #<?php echo $uid; ?> {
                    width: 100%;
                    height: 100%;
                    position: relative;
                }
                .snn-pdf-page {
                    width: 100%;
                    height: 100%;
                }
                .snn-pdf-page canvas {
                    width: 100% !important;
                    height: 100% !important;
                    object-fit: contain;
                    display: block;
                }
                .snn-pdf-arrow {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: <?php echo $chevron_size; ?>px;
                    color: <?php echo $chevron_color; ?>;
                    background: none;
                    border: none;
                    cursor: pointer;
                    z-index: 20;
                    user-select: none;
                }
                .snn-pdf-prev {
                    left: 15px;
                }
                .snn-pdf-next {
                    right: 15px;
                }
                .snn-pdf-buttons {
                    margin-top: 10px;
                    display: none;
                    text-align: center;
                }
                .snn-pdf-download-btn {
                    padding: 8px 16px;
                    text-decoration: none;
                    display: inline-block;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .snn-pdf-page-indicator {
                    margin-top: 10px;
                    font-size: 13px;
                    color: #444;
                    display: none;
                    text-align: center;
                }
                .snn-pdf-viewer-wrapper,
                .snn-pdf-viewer-wrapper * {
                    user-select: none;
                }
                .snn-pdf-viewer-wrapper canvas {
                    pointer-events: none;
                }
                #<?php echo $uid; ?>.snn-pdf-two-page {
                    display: flex;
                    gap: 20px;
                    justify-content: center;
                    align-items: stretch;
                }
                #<?php echo $uid; ?>.snn-pdf-two-page .snn-pdf-page {
                    flex: 1 1 50%;
                    width: auto;
                }
                #<?php echo $uid; ?>.snn-pdf-two-page .snn-pdf-page canvas {
                    width: 100% !important;
                    height: auto !important;
                }
            </style>

            <div id="<?php echo $uid; ?>-container">
                <div class="snn-pdf-stage">
                    <?php if ( $cover_url ) : ?>
                    <div class="snn-pdf-cover" id="<?php echo $uid; ?>-cover">
                        <img decoding="async" src="<?php echo $cover_url; ?>" alt="PDF Cover">
                    </div>
                    <?php endif; ?>
                    <div class="snn-pdf-viewer" id="<?php echo $uid; ?>-viewer">
                        <div id="<?php echo $uid; ?>" class="<?php echo $two_page_view ? 'snn-pdf-two-page' : ''; ?>"></div>
                        <span class="snn-pdf-arrow snn-pdf-prev" id="<?php echo $uid; ?>-prev">
                            <?php $this->render_arrow_icon( $left_arrow_icon, '‹', [ 'snn-pdf-arrow-icon' ] ); ?>
                        </span>
                        <span class="snn-pdf-arrow snn-pdf-next" id="<?php echo $uid; ?>-next">
                            <?php $this->render_arrow_icon( $right_arrow_icon, '›', [ 'snn-pdf-arrow-icon' ] ); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="snn-pdf-page-indicator" id="<?php echo $uid; ?>-page-indicator">1 / ?</div>
            <?php if ( $show_download ) : ?>
            <div class="snn-pdf-buttons" id="<?php echo $uid; ?>-buttons">
                <a href="<?php echo $pdf_url; ?>" download type="application/pdf" target="_blank" class="snn-pdf-download-btn">
                    <?php echo $download_text; ?>
                </a>
            </div>
            <?php endif; ?>

            <script src="<?php echo esc_url( SNN_URL_ASSETS . 'js/pdf.min.js' ); ?>"></script>
            <script>
            (function() {
                const uid = '<?php echo $uid; ?>';
                const url = '<?php echo $pdf_url; ?>';
                const flipbook = document.getElementById(uid);
                const wrapper = document.getElementById(uid + '-viewer');
                const cover = document.getElementById(uid + '-cover');
                const pageIndicator = document.getElementById(uid + '-page-indicator');
                const buttons = document.getElementById(uid + '-buttons');
                const nextArrow = document.getElementById(uid + '-next');
                const prevArrow = document.getElementById(uid + '-prev');
                const twoPageView = <?php echo $two_page_view ? 'true' : 'false'; ?>;
                const pageStep = twoPageView ? 2 : 1;
                let currentPage = 1;
                let isNavigating = false;
                let pdfDoc = null;

                <?php if ( $cover_url ) : ?>
                if (cover) {
                    cover.addEventListener('click', function() {
                        wrapper.style.display = 'block';
                        cover.style.display = 'none';
                        if (buttons) buttons.style.display = 'block';
                        if (pageIndicator) pageIndicator.style.display = 'block';
                        initPDF();
                    });
                }
                <?php else : ?>
                setTimeout(function() {
                    wrapper.style.display = 'block';
                    if (buttons) buttons.style.display = 'block';
                    if (pageIndicator) pageIndicator.style.display = 'block';
                    initPDF();
                }, 50);
                <?php endif; ?>

                function initPDF() {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = '<?php echo esc_url( SNN_URL_ASSETS . 'js/pdf.worker.min.js' ); ?>';
                    pdfjsLib.getDocument(url).promise.then(function(pdf) {
                        pdfDoc = pdf;

                        function renderPage(i) {
                            return pdf.getPage(i).then(function(page) {
                                const containerWidth = flipbook.offsetWidth;
                                const containerHeight = flipbook.offsetHeight;
                                const rawViewport = page.getViewport({scale: 1});
                                const scale = Math.min(containerWidth / rawViewport.width, containerHeight / rawViewport.height);
                                const outputScale = window.devicePixelRatio || 1;
                                const viewport = page.getViewport({scale});
                                
                                const pageDiv = document.createElement('div');
                                pageDiv.className = 'snn-pdf-page';
                                pageDiv.setAttribute('data-page', i);
                                
                                const canvas = document.createElement('canvas');
                                const context = canvas.getContext('2d');
                                canvas.width = viewport.width * outputScale;
                                canvas.height = viewport.height * outputScale;
                                canvas.style.width = viewport.width + 'px';
                                canvas.style.height = viewport.height + 'px';
                                context.setTransform(outputScale, 0, 0, outputScale, 0, 0);
                                
                                pageDiv.appendChild(canvas);
                                
                                return page.render({canvasContext: context, viewport}).promise.then(function() {
                                    return pageDiv;
                                });
                            });
                        }

                        function renderPages(startPage) {
                            const pageNumbers = twoPageView ? [startPage, startPage + 1] : [startPage];
                            const validPages = pageNumbers.filter(function(pageNum) {
                                return pageNum >= 1 && pageNum <= pdf.numPages;
                            });

                            if (!validPages.length) {
                                return Promise.resolve([]);
                            }

                            return Promise.all(validPages.map(renderPage));
                        }

                        function getLastStartPage() {
                            if (!twoPageView) {
                                return pdf.numPages;
                            }
                            return pdf.numPages % 2 === 0 ? Math.max(1, pdf.numPages - 1) : pdf.numPages;
                        }

                        function updateIndicator() {
                            if (!pageIndicator) {
                                return;
                            }

                            if (!twoPageView) {
                                pageIndicator.textContent = currentPage + ' / ' + pdf.numPages;
                                return;
                            }

                            const secondPage = Math.min(currentPage + 1, pdf.numPages);
                            if (secondPage > currentPage) {
                                pageIndicator.textContent = currentPage + ' - ' + secondPage + ' / ' + pdf.numPages;
                            } else {
                                pageIndicator.textContent = currentPage + ' / ' + pdf.numPages;
                            }
                        }

                        function goToPage(i) {
                            if (isNavigating) {
                                return;
                            }

                            let targetPage = i;

                            if (twoPageView) {
                                if (targetPage % 2 === 0) {
                                    targetPage = Math.max(1, targetPage - 1);
                                }
                                const lastStart = getLastStartPage();
                                if (targetPage > lastStart) {
                                    targetPage = lastStart;
                                }
                            }

                            if (targetPage < 1) {
                                targetPage = 1;
                            }

                            if (targetPage > pdf.numPages) {
                                targetPage = pdf.numPages;
                            }

                            if (targetPage === currentPage) {
                                return;
                            }

                            isNavigating = true;
                            renderPages(targetPage).then(function(newPages) {
                                const oldPages = flipbook.querySelectorAll('.snn-pdf-page');
                                oldPages.forEach(function(p) { p.remove(); });
                                newPages.forEach(function(pageNode) { flipbook.appendChild(pageNode); });
                                currentPage = targetPage;
                                updateArrows();
                                updateIndicator();
                                isNavigating = false;
                            });
                        }

                        function updateArrows() {
                            if (prevArrow) {
                                prevArrow.style.display = currentPage > 1 ? 'block' : 'none';
                            }
                            if (nextArrow) {
                                if (!twoPageView) {
                                    nextArrow.style.display = currentPage < pdf.numPages ? 'block' : 'none';
                                } else {
                                    nextArrow.style.display = currentPage < getLastStartPage() ? 'block' : 'none';
                                }
                            }
                        }

                        renderPages(currentPage).then(function(pages) {
                            pages.forEach(function(page) { flipbook.appendChild(page); });
                            updateArrows();
                            updateIndicator();
                        });

                        if (nextArrow) {
                            nextArrow.addEventListener('click', function() { goToPage(currentPage + pageStep); });
                        }
                        if (prevArrow) {
                            prevArrow.addEventListener('click', function() { goToPage(currentPage - pageStep); });
                        }

                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'ArrowRight') goToPage(currentPage + pageStep);
                            if (e.key === 'ArrowLeft') goToPage(currentPage - pageStep);
                        });
                    });
                }

                document.addEventListener('contextmenu', function(e) {
                    if (e.target.closest('.snn-pdf-viewer-wrapper')) {
                        e.preventDefault();
                    }
                });
            })();
            </script>
        </div>
        <?php
    }
}



