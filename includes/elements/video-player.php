<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;

class SNN_Video_Player_Element extends Element {
    public $category     = 'snn';
    public $name         = 'snn-video-player';
    public $icon         = 'ti-control-play';
    public $css_selector = '.snn-player-wrapper';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Video Player', 'snn' );
    }

    public function set_controls() {
        $this->controls['video_file'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Video File (Media Library)', 'snn' ),
            'type'  => 'video',
            'default' => [
                'url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
        ];

        $this->controls['video_url_manual'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Video URL (Manual)', 'snn' ),
            'type'          => 'text',
            'placeholder' => 'e.g., https://example.com/your-video.mp4 or use {dynamic_tags}',
            'description' => esc_html__( 'Enter a direct URL to your video file or dynamic tag.', 'snn' ),
        ];

        $this->controls['poster_url'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Poster Image URL', 'snn' ),
            'type'          => 'text',
            'placeholder' => 'e.g., https://example.com/poster.jpg or use {dynamic_tags}',
            'description' => esc_html__( 'Enter a direct URL to your poster image.', 'snn' ),
        ];

        $this->controls['use_featured_image_as_poster'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Use Featured Image as Poster', 'snn' ),
            'type'  => 'checkbox',
            'default' => false,
            'description' => esc_html__( 'Enable this to automatically use the current post\'s featured image as the video poster.', 'snn' ),
        ];

        $this->controls['subtitles'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Subtitles / Captions', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'label',
            'placeholder'   => esc_html__( 'Subtitle language', 'bricks' ),
            'fields'        => [
                'subtitle_file' => [
                    'label' => esc_html__( 'WebVTT File', 'snn' ),
                    'type'  => 'file',
                    'description' => esc_html__( 'Upload a WebVTT subtitle file (.vtt)', 'snn' ),
                ],
                'label' => [
                    'label' => esc_html__( 'Label', 'snn' ),
                    'type'  => 'text',
                    'placeholder' => esc_html__( 'e.g., English, Spanish, French', 'snn' ),
                    'description' => esc_html__( 'Language name shown to users. If empty, will use the filename.', 'snn' ),
                ],
                'srclang' => [
                    'label' => esc_html__( 'Language Code', 'snn' ),
                    'type'  => 'text',
                    'placeholder' => esc_html__( 'e.g., en, es, fr', 'snn' ),
                    'description' => esc_html__( 'ISO 639-1 language code (optional but recommended)', 'snn' ),
                ],
                'is_default' => [
                    'label' => esc_html__( 'Default', 'snn' ),
                    'type'  => 'checkbox',
                    'default' => false,
                    'description' => esc_html__( 'Set as default subtitle', 'snn' ),
                ],
            ],
        ];

        $this->controls['autoplay'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Autoplay', 'snn' ),
            'type'  => 'checkbox',
            'default' => false,
        ];

        $this->controls['muted'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Muted', 'snn' ),
            'type'  => 'checkbox',
            'default' => false,
        ];

        $this->controls['loop'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Loop', 'snn' ),
            'type' => 'checkbox',
            'default' => false,
        ];

        $this->controls['disable_autohide'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Disable Auto Hide Controls', 'snn' ),
            'type'  => 'checkbox',
            'default' => false,
        ];

        $this->controls['enable_chapter_looping'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Enable Chapter Looping', 'snn' ),
            'type'  => 'checkbox',
            'default' => false,
            'description' => esc_html__( 'When enabled, chapters will be automatically loaded from the current post custom fields named "chapter" or "chapters". Use the Double Text custom field for this.', 'snn' ),
        ];

        $this->controls['chapters'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Video Chapters', 'snn' ),
            'type'          => 'repeater',
            'titleProperty' => 'title',
            'default'       => [
                ['title' => 'Intro', 'time'  => '00:00'],
                ['title' => 'Chapter 1', 'time'  => '02:00'],
                ['title' => 'Chapter 2', 'time'  => '04:00'],
            ],
            'fields'        => [
                'title' => ['label' => esc_html__( 'Chapter Title', 'snn' ), 'type'  => 'text'],
                'time'  => ['label' => esc_html__( 'Start Time (e.g., 0:00, 1:45)', 'snn' ), 'type' => 'text', 'placeholder' => 'm:ss'],
            ],
            'required'      => ['enable_chapter_looping', '=', false],
        ];

        $this->controls['player_height'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Height', 'snn' ),
            'type'  => 'number',
            'units' => ['px', 'vh', 'rem', 'em'],
            'css'   => [
                [
                    'property' => 'height',
                    'selector' => '.snn-video-container',
                ],
            ],
            'default' => '400',
        ];

        $this->controls['player_max_width'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Max Width', 'snn' ),
            'type'  => 'number',
            'units' => ['px', '%', 'rem', 'em', 'vw'],
            'css'   => [
                [
                    'property' => 'max-width',
                    'selector' => '',
                ],
            ],
            'default' => '100%',
        ];

        $this->controls['primary_accent_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Primary Accent', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(0, 0, 0, 1)',
        ];

        $this->controls['thumb_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Slider Thumb Color', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)',
        ];

        $this->controls['text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Text & Icons Color', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)',
        ];

        $this->controls['slider_track_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Slider Track', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 0.3)',
        ];

        $this->controls['chapter_dot_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Chapter Dot', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)',
        ];

        $this->controls['button_hover_background'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Hover BG', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 0.2)',
        ];

        $this->controls['button_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Color', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)',
        ];

        $this->controls['tooltip_text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Tooltip Text Color', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)',
        ];

        $this->controls['controls_bar_bg'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Controls Bar Background', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(0, 0, 0, 0.8)',
        ];
    }

    public function render() {
        $settings = $this->settings;

        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'snn-player-' . uniqid();
            $this->set_attribute( '_root', 'id', $root_id );
        }

        $this->set_attribute( '_root', 'class', 'brxe-snn-video-player snn-player-wrapper' );

        $video_file_url = ! empty( $settings['video_file']['url'] ) ? $settings['video_file']['url'] : 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        
        // Render dynamic data for manual URL
        $manual_video_url = ! empty( $settings['video_url_manual'] ) ? $this->render_dynamic_data( $settings['video_url_manual'] ) : '';
        
        // Check if manual URL is an attachment ID (numeric) or a URL
        if ( ! empty( $manual_video_url ) ) {
            if ( is_numeric( $manual_video_url ) ) {
                // It's an attachment ID, get the URL
                $manual_video_url = wp_get_attachment_url( intval( $manual_video_url ) );
            }
            $video_url = $manual_video_url;
        } else {
            $video_url = $video_file_url;
        }

        // Check if featured image should be used as poster
        $use_featured_image = ! empty( $settings['use_featured_image_as_poster'] );
        $poster_url = '';
        
        if ( $use_featured_image && has_post_thumbnail() ) {
            // Use featured image as poster
            $poster_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        } else {
            // Render dynamic data for poster URL
            $poster_url = ! empty( $settings['poster_url'] ) ? $this->render_dynamic_data( $settings['poster_url'] ) : '';
            
            // Check if poster URL is an attachment ID (numeric) or a URL
            if ( ! empty( $poster_url ) ) {
                if ( is_numeric( $poster_url ) ) {
                    // It's an attachment ID, get the URL
                    $poster_url = wp_get_attachment_url( intval( $poster_url ) );
                }
            }
        }
        
        // Process subtitles
        $subtitles = [];
        if ( ! empty( $settings['subtitles'] ) && is_array( $settings['subtitles'] ) ) {
            foreach ( $settings['subtitles'] as $index => $subtitle ) {
                if ( ! empty( $subtitle['subtitle_file'] ) ) {
                    $file_data = $subtitle['subtitle_file'];
                    $subtitle_url = '';
                    $subtitle_label = '';
                    
                    // Get subtitle URL
                    if ( is_array( $file_data ) && ! empty( $file_data['url'] ) ) {
                        $subtitle_url = $file_data['url'];
                        // Get filename from URL if no label provided
                        if ( empty( $subtitle['label'] ) && ! empty( $file_data['filename'] ) ) {
                            $subtitle_label = pathinfo( $file_data['filename'], PATHINFO_FILENAME );
                        }
                    } elseif ( is_string( $file_data ) ) {
                        if ( is_numeric( $file_data ) ) {
                            $subtitle_url = wp_get_attachment_url( intval( $file_data ) );
                            // Get filename from attachment if no label provided
                            if ( empty( $subtitle['label'] ) ) {
                                $subtitle_label = basename( get_attached_file( intval( $file_data ) ), '.vtt' );
                            }
                        } else {
                            $subtitle_url = $file_data;
                            // Get filename from URL if no label provided
                            if ( empty( $subtitle['label'] ) ) {
                                $subtitle_label = basename( $file_data, '.vtt' );
                            }
                        }
                    }
                    
                    // Use custom label if provided
                    if ( ! empty( $subtitle['label'] ) ) {
                        $subtitle_label = $subtitle['label'];
                    }
                    
                    if ( ! empty( $subtitle_url ) && ! empty( $subtitle_label ) ) {
                        $subtitles[] = [
                            'url' => $subtitle_url,
                            'label' => $subtitle_label,
                            'srclang' => ! empty( $subtitle['srclang'] ) ? $subtitle['srclang'] : 'en',
                            'is_default' => ! empty( $subtitle['is_default'] ),
                        ];
                    }
                }
            }
        }
        
        // Check if chapter looping is enabled
        $enable_chapter_looping = ! empty( $settings['enable_chapter_looping'] );
        $chapters = [];
        
        if ( $enable_chapter_looping ) {
            // Load chapters from custom fields named "chapter" or "chapters"
            $post_id = get_the_ID();
            if ( $post_id ) {
                // Try to get custom field data from "chapters" or "chapter"
                $chapters_data = get_post_meta( $post_id, 'chapters', true );
                if ( empty( $chapters_data ) ) {
                    $chapters_data = get_post_meta( $post_id, 'chapter', true );
                }
                
                // Process the chapters data if it's an array
                if ( is_array( $chapters_data ) && ! empty( $chapters_data ) ) {
                    foreach ( $chapters_data as $chapter_item ) {
                        // Check if it's an array with at least 2 elements [time, text]
                        if ( is_array( $chapter_item ) && count( $chapter_item ) >= 2 ) {
                            $chapters[] = [
                                'time'  => $chapter_item[0],
                                'title' => $chapter_item[1],
                            ];
                        }
                    }
                }
            }
        } else {
            // Use manual chapters from the repeater control
            $chapters = $settings['chapters'] ?? [];
        }

        $autoplay           = ! empty( $settings['autoplay'] );
        $muted              = ! empty( $settings['muted'] );
        $loop               = ! empty( $settings['loop'] );
        $disable_autohide = ! empty( $settings['disable_autohide'] );

        $player_height      = $settings['player_height'] ?? '400px';
        $player_max_width = $settings['player_max_width'] ?? '896px';

        $accent_color        = $settings['primary_accent_color']['raw'] ?? $settings['primary_accent_color']['hex'] ?? '#ffd64f';
        $thumb_color         = $settings['thumb_color']['raw'] ?? $settings['thumb_color']['hex'] ?? '#ffffff';
        $text_color          = $settings['text_color']['raw'] ?? $settings['text_color']['hex'] ?? '#ffffff';
        $slider_track        = $settings['slider_track_color']['raw'] ?? $settings['slider_track_color']['hex'] ?? 'rgba(255, 255, 255, 0.3)';
        $chapter_dot_color = $settings['chapter_dot_color']['raw'] ?? $settings['chapter_dot_color']['hex'] ?? '#ffffff';
        $btn_hover_bg        = $settings['button_hover_background']['raw'] ?? $settings['button_hover_background']['hex'] ?? 'rgba(255, 255, 255, 0.2)';
        $button_color        = $settings['button_color']['raw'] ?? $settings['button_color']['hex'] ?? 'rgba(255, 255, 255, 1)';
        $tooltip_text_color  = $settings['tooltip_text_color']['raw'] ?? $settings['tooltip_text_color']['hex'] ?? 'rgba(0, 0, 0, 1)';
        $controls_bar_bg     = $settings['controls_bar_bg']['raw'] ?? $settings['controls_bar_bg']['hex'] ?? 'rgba(0, 0, 0, 0.5)';

        echo "<div {$this->render_attributes('_root')}>";

        echo "<style>
            #" . esc_attr($root_id) . " { --primary-accent-color: {$accent_color}; --thumb-color: {$thumb_color}; --text-color: {$text_color}; --slider-track-color: {$slider_track}; --chapter-dot-color: {$chapter_dot_color}; --button-hover-background: {$btn_hover_bg}; --player-height: {$player_height}; --player-max-width: {$player_max_width}; --button-color: {$button_color}; --tooltip-text-color: {$tooltip_text_color}; --controls-bar-bg: {$controls_bar_bg}; width: 100%; max-width: var(--player-max-width); margin-left: auto; margin-right: auto; }
            #" . esc_attr($root_id) . " .snn-video-container { position: relative; background-color: #000; overflow: hidden; height: var(--player-height); }
            #" . esc_attr($root_id) . " .snn-video-container video { width: 100%; height: 100%; display: block; object-fit: cover; }
            #" . esc_attr($root_id) . " .snn-video-container:fullscreen { width: 100vw; height: 100vh; max-width: 100%; border-radius: 0; }
            #" . esc_attr($root_id) . " .snn-controls-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; opacity: 0; transition: opacity 0.3s ease-in-out; }
            #" . esc_attr($root_id) . " .snn-video-container.snn-controls-visible .snn-controls-overlay { opacity: 1; }
            #" . esc_attr($root_id) . " .snn-controls-hidden .snn-controls-overlay { cursor: none; opacity: 0; pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-controls-bar-container { padding: 0; background: linear-gradient(to top, var(--controls-bar-bg) 0%, rgba(0, 0, 0, 0.2) 100%); }
            #" . esc_attr($root_id) . " .snn-progress-container { position: relative; margin:0 11px 4px 12px; height: 5px; }
            #" . esc_attr($root_id) . " .snn-progress-tooltip { position: absolute; background-color: var(--primary-accent-color); color: var(--tooltip-text-color); font-size: 14px; border-radius: 3.75px; padding: 3.75px 7.5px; bottom: 100%; margin-bottom: 8px; pointer-events: none; opacity: 0; transition: opacity 0.2s; white-space: normal; word-wrap: break-word; transform: translateX(-50%); max-width: 260px; z-index: 10; line-height: 1.4; }
            #" . esc_attr($root_id) . " .snn-chapter-dots-container { position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; z-index: 5; }
            #" . esc_attr($root_id) . " .snn-chapter-sections-container { position: absolute; width: 100%; height: 100%; top: 0; left: 0; display: flex; z-index: 3; pointer-events: all; }
            #" . esc_attr($root_id) . " .snn-chapter-section { position: relative; height: 5px; background: transparent; transition: height 0.15s ease; cursor: pointer; display: flex; align-items: flex-end; }
            #" . esc_attr($root_id) . " .snn-chapter-section:hover { transform:scaleY(1.6)}
            #" . esc_attr($root_id) . " .snn-chapter-section-fill { position: absolute; bottom: 0; left: 0; width: 0%; height: 100%; background: var(--primary-accent-color); transition: width 0.1s linear; pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-chapter-section-bg { position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; background: var(--slider-track-color); pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-controls-bar { display: flex; align-items: center; justify-content: space-between; color: var(--text-color); padding: 0 2px 2px 2px; }
            #" . esc_attr($root_id) . " .snn-controls-left, #" . esc_attr($root_id) . " .snn-controls-right { display: flex; align-items: center; gap: 10px; }
            #" . esc_attr($root_id) . " .snn-control-button { background: none; border: none; color: var(--button-color); padding: 5px; border-radius: 9999px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s; filter:drop-shadow(0px 0px 2px #00000099) }
            #" . esc_attr($root_id) . " .snn-control-button:hover { background-color: var(--button-hover-background); }
            #" . esc_attr($root_id) . " .snn-control-button svg { width: 30px; height: 30px; fill: currentColor; }
            #" . esc_attr($root_id) . " .snn-volume-container { display: flex; align-items: center; position: relative; }
            #" . esc_attr($root_id) . " .snn-volume-container .snn-volume-slider { width: 0; transition: width 0.3s ease; opacity: 0; }
            #" . esc_attr($root_id) . " .snn-volume-container:hover .snn-volume-slider { width: 75px; opacity: 1; }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider { -webkit-appearance: none; appearance: none; width: 100%; height: 5px; background: transparent; cursor: pointer; border-radius: 5px; position: absolute; top: 0; left: 0; z-index: 0; }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 0; height: 0; opacity: 0; }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider::-moz-range-thumb { width: 0; height: 0; opacity: 0; }
            #" . esc_attr($root_id) . " .snn-progress-thumb { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 16px; height: 16px; background: var(--thumb-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--primary-accent-color); transition: transform 0.2s ease; pointer-events: none; z-index: 10; }
            #" . esc_attr($root_id) . " .snn-progress-thumb:hover { transform: translate(-50%, -50%) scale(1.1); }
            #" . esc_attr($root_id) . " .snn-volume-slider { -webkit-appearance: none; appearance: none; height: 5px; background: var(--slider-track-color); cursor: pointer; border-radius: 5px; transition: height 0.2s ease, width 0.3s ease, opacity 0.3s ease; margin-left: 7.5px; position: relative; }
            #" . esc_attr($root_id) . " .snn-volume-slider:hover { height: 8px; }
            #" . esc_attr($root_id) . " .snn-volume-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; background: var(--thumb-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--primary-accent-color); transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-volume-slider:hover::-webkit-slider-thumb { transform: scale(1.1); }
            #" . esc_attr($root_id) . " .snn-volume-slider::-moz-range-thumb { width: 16px; height: 16px; background: var(--thumb-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--primary-accent-color); }
            #" . esc_attr($root_id) . " .snn-chapter-dot { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 5px; height: 6px; background: var(--chapter-dot-color); border-radius: 0px; cursor: pointer; transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-chapter-dot:hover { transform: translate(-50%, -50%) scale(1.5); }
            #" . esc_attr($root_id) . " .snn-cc-container { position: relative; }
            #" . esc_attr($root_id) . " .snn-cc-menu { position: absolute; bottom: 100%; right: 0; margin-bottom: 10px; background-color: rgba(0, 0, 0, 0.9); border-radius: 5px; min-width: 200px; max-height: 250px; overflow-y: auto; display: none; z-index: 100; }
            #" . esc_attr($root_id) . " .snn-cc-menu.snn-show { display: block; }
            #" . esc_attr($root_id) . " .snn-cc-menu::-webkit-scrollbar { width: 6px; }
            #" . esc_attr($root_id) . " .snn-cc-menu::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); border-radius: 3px; }
            #" . esc_attr($root_id) . " .snn-cc-menu::-webkit-scrollbar-thumb { background: var(--primary-accent-color); border-radius: 3px; }
            #" . esc_attr($root_id) . " .snn-cc-menu::-webkit-scrollbar-thumb:hover { background: var(--primary-accent-color); opacity: 0.8; }
            #" . esc_attr($root_id) . " .snn-cc-menu-item { padding: 12px 16px; cursor: pointer; color: var(--text-color); font-size: 14px; transition: background-color 0.2s; border: none; background: none; width: 100%; text-align: left; display: flex; align-items: center; gap: 8px; }
            #" . esc_attr($root_id) . " .snn-cc-menu-item:hover { background-color: var(--button-hover-background); }
            #" . esc_attr($root_id) . " .snn-cc-menu-item.snn-active { background-color: var(--primary-accent-color); color: var(--tooltip-text-color); }
            #" . esc_attr($root_id) . " .snn-cc-menu-item svg { width: 16px; height: 16px; fill: currentColor; }
            #" . esc_attr($root_id) . " .snn-cc-settings-btn { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.1); }
            #" . esc_attr($root_id) . " .snn-cc-settings-panel { display: none; padding: 8px 12px; }
            #" . esc_attr($root_id) . " .snn-cc-settings-panel.snn-show { display: block; }
            #" . esc_attr($root_id) . " .snn-cc-lang-list { display: block; }
            #" . esc_attr($root_id) . " .snn-cc-lang-list.snn-hidden { display: none; }
            #" . esc_attr($root_id) . " .snn-cc-settings-row { margin-bottom: 2px; }
            #" . esc_attr($root_id) . " .snn-cc-settings-row:last-child { margin-bottom: 0; }
            #" . esc_attr($root_id) . " .snn-cc-settings-label { display: block; color: var(--text-color); font-size: 14px; margin-bottom: 0px; font-weight: 500; }
            #" . esc_attr($root_id) . " .snn-cc-settings-input { width: 100%; padding: 6px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 4px; color: var(--text-color); font-size: 13px; }
            #" . esc_attr($root_id) . " .snn-cc-settings-input[type=\"color\"] { height: 24px; cursor: pointer; padding: 0px; }
            #" . esc_attr($root_id) . " .snn-cc-settings-input[type=\"range\"] { padding: 0; height: 6px; }
            #" . esc_attr($root_id) . " .snn-cc-back-btn { display: none; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
            #" . esc_attr($root_id) . " .snn-cc-back-btn.snn-show { display: flex; }
            #" . esc_attr($root_id) . " .snn-cc-back-btn svg { width: 16px; height: 16px; }
            #" . esc_attr($root_id) . " video::cue { font-size: 20px; }
            #" . esc_attr($root_id) . " .snn-hidden { display: none !important; }
        </style>";

        ?>
        <div class="snn-video-container">
            <video class="snn-video" poster="<?php echo esc_url( $poster_url ); ?>" playsinline crossorigin="anonymous"
                <?php echo $autoplay ? 'autoplay' : ''; ?>
                <?php echo $muted ? 'muted' : ''; ?>
                <?php echo $loop ? 'loop' : ''; ?>
            >
                <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
                <?php 
                foreach ( $subtitles as $index => $subtitle ) : 
                    $default_attr = $subtitle['is_default'] ? 'default' : '';
                ?>
                    <track 
                        kind="subtitles" 
                        label="<?php echo esc_attr( $subtitle['label'] ); ?>" 
                        srclang="<?php echo esc_attr( $subtitle['srclang'] ); ?>" 
                        src="<?php echo esc_url( $subtitle['url'] ); ?>"
                        <?php echo $default_attr; ?>
                    >
                <?php endforeach; ?>
                Your browser does not support the video tag.
            </video>

            <div class="snn-controls-overlay">
                <div class="snn-controls-bar-container">
                    <div class="snn-progress-container">
                        <div class="snn-progress-tooltip">00:00</div>
                        <div class="snn-chapter-sections-container"></div>
                        <input type="range" class="snn-video-slider snn-progress-bar" min="0" max="100" step="0.1" value="0">
                        <div class="snn-progress-thumb"></div>
                        <div class="snn-chapter-dots-container"></div>
                    </div>

                    <div class="snn-controls-bar">
                        <div class="snn-controls-left">
                            <button class="snn-control-button snn-play-pause-btn" aria-label="Play/Pause"></button>
                            
                            <div class="snn-volume-container">
                                <button class="snn-control-button snn-mute-btn" aria-label="Mute/Unmute"></button>
                                <input type="range" class="snn-video-slider snn-volume-slider" min="0" max="1" step="0.05" value="1" aria-label="Volume">
                            </div>
                            
                            <div class="snn-time-display">00:00 / 00:00</div>
                        </div>
                        <div class="snn-controls-right">
                            <?php if ( ! empty( $subtitles ) ) : ?>
                            <div class="snn-cc-container">
                                <button class="snn-control-button snn-cc-btn" aria-label="Subtitles">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM4 12h4v2H4v-2zm10 6H4v-2h10v2zm6 0h-4v-2h4v2zm0-4H10v-2h10v2z"></path></svg>
                                </button>
                                <div class="snn-cc-menu">
                                    <button class="snn-cc-back-btn snn-cc-menu-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg>
                                        Back
                                    </button>
                                    <div class="snn-cc-lang-list">
                                        <button class="snn-cc-menu-item snn-cc-off" data-track="-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"></path></svg>
                                            Off
                                        </button>
                                        <?php foreach ( $subtitles as $index => $subtitle ) : ?>
                                        <button class="snn-cc-menu-item <?php echo $subtitle['is_default'] ? 'snn-active' : ''; ?>" data-track="<?php echo $index; ?>">
                                            <?php if ( $subtitle['is_default'] ) : ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg>
                                            <?php endif; ?>
                                            <?php echo esc_html( $subtitle['label'] ); ?>
                                        </button>
                                        <?php endforeach; ?>
                                        <button class="snn-cc-menu-item snn-cc-settings-btn">
                                            <span>Settings</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"></path></svg>
                                        </button>
                                    </div>
                                    <div class="snn-cc-settings-panel">
                                        <div class="snn-cc-settings-row">
                                            <label class="snn-cc-settings-label">Font Size <span class="snn-cc-settings-label" ><span class="snn-cc-font-size-value">20</span>px</span> </label>
                                            <input type="range" class="snn-cc-settings-input snn-cc-font-size" min="10" max="100" value="20" step="2">
                                            
                                        </div>
                                        <div class="snn-cc-settings-row">
                                            <label class="snn-cc-settings-label">Text Color</label>
                                            <input type="color" class="snn-cc-settings-input snn-cc-text-color" value="#ffffff">
                                        </div>
                                        <div class="snn-cc-settings-row">
                                            <label class="snn-cc-settings-label">Background Color</label>
                                            <input type="color" class="snn-cc-settings-input snn-cc-bg-color" value="#000000">
                                        </div>
                                        <div class="snn-cc-settings-row">
                                            <label class="snn-cc-settings-label">Background Opacity</label>
                                            <input type="range" class="snn-cc-settings-input snn-cc-bg-opacity" min="0" max="100" value="80" step="5">
                                            <span class="snn-cc-settings-label" style="text-align: center; margin-top: 4px;"><span class="snn-cc-bg-opacity-value">80</span>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <button class="snn-control-button snn-fullscreen-btn" aria-label="Fullscreen">
                                <svg class="snn-fullscreen-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"></path></svg>
                                <svg class="snn-fullscreen-exit-icon snn-hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        ?>
        <script>
        (() => {
            const playerWrapper = document.getElementById('<?php echo esc_js($root_id); ?>');

            if (!playerWrapper || playerWrapper.dataset.snnPlayerInitialized) {
                return;
            }
            playerWrapper.dataset.snnPlayerInitialized = 'true';

            const CONFIG = {
                INACTIVITY_TIMEOUT: 3000,
                CHAPTERS: <?php echo json_encode($chapters); ?>,
                KEY_SEEK_SECONDS: 5,
                INITIAL_MUTED: <?php echo json_encode($muted); ?>,
                DISABLE_AUTOHIDE: <?php echo json_encode($disable_autohide); ?>,
                HAS_SUBTITLES: <?php echo json_encode( ! empty( $subtitles ) ); ?>
            };

            const ICONS = {
                play: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 6v12l10-6z"></path></svg>`,
                pause: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>`,
                volumeHigh: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"></path></svg>`,
                volumeMute: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"></path></svg>`,
                check: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg>`,
            };

            const videoContainer          = playerWrapper.querySelector('.snn-video-container');
            const video                   = playerWrapper.querySelector('.snn-video');
            const controlsOverlay         = playerWrapper.querySelector('.snn-controls-overlay');
            const controlsBarContainer = playerWrapper.querySelector('.snn-controls-bar-container');
            const playPauseBtn            = playerWrapper.querySelector('.snn-play-pause-btn');
            const muteBtn                 = playerWrapper.querySelector('.snn-mute-btn');
            const volumeSlider            = playerWrapper.querySelector('.snn-volume-slider');
            const fullscreenBtn           = playerWrapper.querySelector('.snn-fullscreen-btn');
            const progressBar             = playerWrapper.querySelector('.snn-progress-bar');
            const progressThumb           = playerWrapper.querySelector('.snn-progress-thumb');
            const timeDisplay             = playerWrapper.querySelector('.snn-time-display');
            const chapterDotsContainer = playerWrapper.querySelector('.snn-chapter-dots-container');
            const chapterSectionsContainer = playerWrapper.querySelector('.snn-chapter-sections-container');
            const progressTooltip         = playerWrapper.querySelector('.snn-progress-tooltip');
            const fullscreenIcon          = playerWrapper.querySelector('.snn-fullscreen-icon');
            const fullscreenExitIcon      = playerWrapper.querySelector('.snn-fullscreen-exit-icon');
            const progressContainer       = playerWrapper.querySelector('.snn-progress-container');
            const ccBtn                   = playerWrapper.querySelector('.snn-cc-btn');
            const ccMenu                  = playerWrapper.querySelector('.snn-cc-menu');
            const ccMenuItems             = playerWrapper.querySelectorAll('.snn-cc-menu-item');
            const ccSettingsBtn           = playerWrapper.querySelector('.snn-cc-settings-btn');
            const ccSettingsPanel         = playerWrapper.querySelector('.snn-cc-settings-panel');
            const ccLangList              = playerWrapper.querySelector('.snn-cc-lang-list');
            const ccBackBtn               = playerWrapper.querySelector('.snn-cc-back-btn');
            const ccFontSizeInput         = playerWrapper.querySelector('.snn-cc-font-size');
            const ccFontSizeValue         = playerWrapper.querySelector('.snn-cc-font-size-value');
            const ccTextColorInput        = playerWrapper.querySelector('.snn-cc-text-color');
            const ccBgColorInput          = playerWrapper.querySelector('.snn-cc-bg-color');
            const ccBgOpacityInput        = playerWrapper.querySelector('.snn-cc-bg-opacity');
            const ccBgOpacityValue        = playerWrapper.querySelector('.snn-cc-bg-opacity-value');

            if (!video || !controlsOverlay || !playPauseBtn || !progressThumb) return;

            let isSeeking = false, inactivityTimer, lastVolume = video.volume, isPlayerInView = false;
            let chapterSections = [];
            let isDraggingThumb = false;

            const timeToSeconds = (timeString) => {
                if (!timeString || typeof timeString !== 'string') return 0;
                const parts = timeString.split(':').map(Number);
                return parts.length === 2 ? (parts[0] * 60) + parts[1] : 0;
            };

            const formatTime = (timeInSeconds) => {
                const s = Math.floor(timeInSeconds % 60);
                const m = Math.floor(timeInSeconds / 60);
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            };

            const updateProgressBarFill = (bar) => {
                if (!bar) return;
                let progress = bar.value;
                // Volume slider has values 0-1, convert to percentage
                if (bar.classList.contains('snn-volume-slider')) {
                    progress = progress * 100;
                }
                const accentColor = getComputedStyle(playerWrapper).getPropertyValue('--primary-accent-color').trim();
                const trackColor = getComputedStyle(playerWrapper).getPropertyValue('--slider-track-color').trim();
                bar.style.background = `linear-gradient(to right, ${accentColor} ${progress}%, ${trackColor} ${progress}%)`;
            };

            const updateProgressThumbPosition = () => {
                if (!progressBar || !progressThumb || isNaN(video.duration)) return;
                const percent = (video.currentTime / video.duration) * 100;
                progressThumb.style.left = `${percent}%`;
            };

            const processChapterSections = () => {
                if (!CONFIG.CHAPTERS || CONFIG.CHAPTERS.length === 0 || isNaN(video.duration)) return [];
                
                const sections = [];
                const sortedChapters = [...CONFIG.CHAPTERS]
                    .map(ch => ({ title: ch.title, startTime: timeToSeconds(ch.time) }))
                    .filter(ch => ch.startTime <= video.duration)
                    .sort((a, b) => a.startTime - b.startTime);

                for (let i = 0; i < sortedChapters.length; i++) {
                    const chapter = sortedChapters[i];
                    const nextChapter = sortedChapters[i + 1];
                    sections.push({
                        title: chapter.title,
                        startTime: chapter.startTime,
                        endTime: nextChapter ? nextChapter.startTime : video.duration
                    });
                }
                return sections;
            };

            const getChapterAtTime = (timeInSeconds, sections) => {
                return sections.find(section => timeInSeconds >= section.startTime && timeInSeconds < section.endTime);
            };

            const togglePlayPause = () => video.paused || video.ended ? video.play() : video.pause();
            const updatePlayPauseIcon = () => { if(playPauseBtn) playPauseBtn.innerHTML = video.paused ? ICONS.play : ICONS.pause; };
            
            const updateMuteIcon = () => { 
                if(muteBtn) muteBtn.innerHTML = video.muted || video.volume === 0 ? ICONS.volumeMute : ICONS.volumeHigh; 
            };

            const toggleMute = () => {
                video.muted = !video.muted;
                if (video.muted) {
                    if (video.volume > 0) {
                        lastVolume = video.volume;
                    }
                    video.volume = 0;
                } else {
                    video.volume = lastVolume > 0 ? lastVolume : 1;
                }
            };

            const toggleFullscreen = () => {
                if (!document.fullscreenElement) {
                    videoContainer?.requestFullscreen().catch(err => console.error(`Fullscreen request failed: ${err.message}`));
                } else {
                    document.exitFullscreen();
                }
            };

            const updateFullscreenIcons = () => {
                const isFullscreen = !!document.fullscreenElement;
                fullscreenIcon?.classList.toggle('snn-hidden', isFullscreen);
                fullscreenExitIcon?.classList.toggle('snn-hidden', !isFullscreen);
            };

            const updateChapterSectionsFill = () => {
                if (!chapterSections.length || isNaN(video.duration)) return;
                
                const currentTime = video.currentTime;
                
                chapterSections.forEach((section) => {
                    const sectionFill = section.element.querySelector('.snn-chapter-section-fill');
                    if (!sectionFill) return;
                    
                    if (currentTime < section.startTime) {
                        sectionFill.style.width = '0%';
                    } else if (currentTime >= section.endTime) {
                        sectionFill.style.width = '100%';
                    } else {
                        const sectionProgress = ((currentTime - section.startTime) / (section.endTime - section.startTime)) * 100;
                        sectionFill.style.width = `${sectionProgress}%`;
                    }
                });
            };

            const updateProgress = () => {
                if (isSeeking || isDraggingThumb || isNaN(video.duration)) return;
                if (progressBar) progressBar.value = (video.currentTime / video.duration) * 100;
                if (timeDisplay) timeDisplay.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration || 0)}`;
                updateProgressThumbPosition();
                updateChapterSectionsFill();
            };

            const hideControls = () => {
                if (CONFIG.DISABLE_AUTOHIDE) return;
                // In fullscreen mode, always allow hiding controls even if paused
                const isFullscreen = !!document.fullscreenElement;
                if (video.paused && !isFullscreen) return;
                videoContainer?.classList.add('snn-controls-hidden');
                videoContainer?.classList.remove('snn-controls-visible');
            };

            const showControls = () => {
                videoContainer?.classList.remove('snn-controls-hidden');
                videoContainer?.classList.add('snn-controls-visible');
                clearTimeout(inactivityTimer);
                if (!CONFIG.DISABLE_AUTOHIDE) {
                    // Use 2 seconds in fullscreen mode
                    const isFullscreen = !!document.fullscreenElement;
                    const timeout = isFullscreen ? 2000 : CONFIG.INACTIVITY_TIMEOUT;
                    inactivityTimer = setTimeout(hideControls, timeout);
                }
            };

            const generateChapterSections = () => {
                if (!chapterSectionsContainer || isNaN(video.duration)) return;
                chapterSectionsContainer.innerHTML = '';
                chapterSections = [];
                
                const sections = processChapterSections();
                if (sections.length === 0) {
                    const defaultSection = document.createElement('div');
                    defaultSection.className = 'snn-chapter-section';
                    defaultSection.style.width = '100%';
                    defaultSection.innerHTML = `
                        <div class="snn-chapter-section-bg"></div>
                        <div class="snn-chapter-section-fill"></div>
                    `;
                    
                    defaultSection.addEventListener('click', (e) => {
                        const rect = defaultSection.getBoundingClientRect();
                        const clickX = e.clientX - rect.left;
                        const percent = clickX / rect.width;
                        video.currentTime = percent * video.duration;
                        video.play();
                    });
                    
                    defaultSection.addEventListener('mousemove', (e) => {
                        if (!progressTooltip || isNaN(video.duration)) return;
                        const rect = defaultSection.getBoundingClientRect();
                        const mouseX = e.clientX - rect.left;
                        const percent = Math.max(0, Math.min(1, mouseX / rect.width));
                        const hoverTime = percent * video.duration;
                        
                        progressTooltip.style.left = `${e.clientX - chapterSectionsContainer.getBoundingClientRect().left}px`;
                        progressTooltip.textContent = formatTime(hoverTime);
                        progressTooltip.style.opacity = '1';
                    });
                    
                    defaultSection.addEventListener('mouseleave', () => {
                        if (progressTooltip) progressTooltip.style.opacity = '0';
                    });
                    
                    chapterSectionsContainer.appendChild(defaultSection);
                    chapterSections.push({
                        startTime: 0,
                        endTime: video.duration,
                        element: defaultSection
                    });
                    return;
                }

                sections.forEach((section) => {
                    const sectionDiv = document.createElement('div');
                    sectionDiv.className = 'snn-chapter-section';
                    const widthPercent = ((section.endTime - section.startTime) / video.duration) * 100;
                    sectionDiv.style.width = `${widthPercent}%`;
                    sectionDiv.dataset.title = section.title;
                    sectionDiv.innerHTML = `
                        <div class="snn-chapter-section-bg"></div>
                        <div class="snn-chapter-section-fill"></div>
                    `;
                    
                    sectionDiv.addEventListener('click', (e) => {
                        const rect = sectionDiv.getBoundingClientRect();
                        const clickX = e.clientX - rect.left;
                        const sectionPercent = clickX / rect.width;
                        const targetTime = section.startTime + (sectionPercent * (section.endTime - section.startTime));
                        video.currentTime = targetTime;
                        video.play();
                    });
                    
                    sectionDiv.addEventListener('mousemove', (e) => {
                        if (!progressTooltip || isNaN(video.duration)) return;
                        const rect = sectionDiv.getBoundingClientRect();
                        const mouseX = e.clientX - rect.left;
                        const sectionPercent = Math.max(0, Math.min(1, mouseX / rect.width));
                        const hoverTime = section.startTime + (sectionPercent * (section.endTime - section.startTime));
                        
                        progressTooltip.style.left = `${e.clientX - chapterSectionsContainer.getBoundingClientRect().left}px`;
                        progressTooltip.textContent = `${formatTime(hoverTime)} - ${section.title}`;
                        progressTooltip.style.opacity = '1';
                    });
                    
                    sectionDiv.addEventListener('mouseleave', () => {
                        if (progressTooltip) progressTooltip.style.opacity = '0';
                    });
                    
                    chapterSectionsContainer.appendChild(sectionDiv);
                    chapterSections.push({
                        ...section,
                        element: sectionDiv
                    });
                });
            };

            const generateChapters = () => {
                if (!chapterDotsContainer || isNaN(video.duration) || !CONFIG.CHAPTERS) return;
                chapterDotsContainer.innerHTML = '';
                
                const sections = processChapterSections();
                if (sections.length === 0) return;

                sections.forEach((section, index) => {
                    if (section.startTime === 0) return;

                    const dot = document.createElement('div');
                    dot.className = 'snn-chapter-dot';
                    dot.style.left = `${(section.startTime / video.duration) * 100}%`;
                    dot.dataset.title = section.title;
                    dot.dataset.time = section.startTime;
                    dot.style.pointerEvents = 'auto';

                    dot.addEventListener('click', e => {
                        e.stopPropagation();
                        video.currentTime = section.startTime;
                        video.play();
                    });

                    chapterDotsContainer.appendChild(dot);
                });
            };

            const handleThumbDrag = (e) => {
                if (!isDraggingThumb || !progressContainer || isNaN(video.duration)) return;
                
                const rect = progressContainer.getBoundingClientRect();
                let clientX = e.clientX || (e.touches && e.touches[0].clientX);
                let x = clientX - rect.left;
                x = Math.max(0, Math.min(x, rect.width));
                
                const percent = (x / rect.width) * 100;
                const scrubTime = (percent / 100) * video.duration;
                
                progressBar.value = percent;
                progressThumb.style.left = `${percent}%`;
                if(timeDisplay) timeDisplay.textContent = `${formatTime(scrubTime)} / ${formatTime(video.duration)}`;
                
                chapterSections.forEach((section) => {
                    const sectionFill = section.element.querySelector('.snn-chapter-section-fill');
                    if (!sectionFill) return;
                    
                    if (scrubTime < section.startTime) {
                        sectionFill.style.width = '0%';
                    } else if (scrubTime >= section.endTime) {
                        sectionFill.style.width = '100%';
                    } else {
                        const sectionProgress = ((scrubTime - section.startTime) / (section.endTime - section.startTime)) * 100;
                        sectionFill.style.width = `${sectionProgress}%`;
                    }
                });
            };

            const stopThumbDrag = (e) => {
                if (!isDraggingThumb) return;
                isDraggingThumb = false;
                
                const percent = parseFloat(progressBar.value);
                video.currentTime = (percent / 100) * video.duration;
                
                document.removeEventListener('mousemove', handleThumbDrag);
                document.removeEventListener('mouseup', stopThumbDrag);
                document.removeEventListener('touchmove', handleThumbDrag);
                document.removeEventListener('touchend', stopThumbDrag);
            };

            progressThumb.addEventListener('mousedown', (e) => {
                isDraggingThumb = true;
                document.addEventListener('mousemove', handleThumbDrag);
                document.addEventListener('mouseup', stopThumbDrag);
            });

            progressThumb.addEventListener('touchstart', (e) => {
                isDraggingThumb = true;
                document.addEventListener('touchmove', handleThumbDrag);
                document.addEventListener('touchend', stopThumbDrag);
            });

            // Load subtitle settings from localStorage
            const loadSubtitleSettings = () => {
                const settings = {
                    fontSize: localStorage.getItem('snn-cc-font-size') || '20',
                    textColor: localStorage.getItem('snn-cc-text-color') || '#ffffff',
                    bgColor: localStorage.getItem('snn-cc-bg-color') || '#000000',
                    bgOpacity: localStorage.getItem('snn-cc-bg-opacity') || '80'
                };
                return settings;
            };

            const saveSubtitleSetting = (key, value) => {
                localStorage.setItem(key, value);
            };

            const applySubtitleStyles = (settings) => {
                const styleId = 'snn-subtitle-styles-' + '<?php echo esc_js($root_id); ?>';
                let styleEl = document.getElementById(styleId);
                if (!styleEl) {
                    styleEl = document.createElement('style');
                    styleEl.id = styleId;
                    document.head.appendChild(styleEl);
                }
                
                const opacity = parseInt(settings.bgOpacity) / 100;
                const bgColorRgb = settings.bgColor.match(/\w\w/g).map(x => parseInt(x, 16));
                const bgColorRgba = `rgba(${bgColorRgb[0]}, ${bgColorRgb[1]}, ${bgColorRgb[2]}, ${opacity})`;
                
                styleEl.textContent = `
                    #<?php echo esc_js($root_id); ?> video::cue {
                        font-size: ${settings.fontSize}px !important;
                        color: ${settings.textColor} !important;
                        background-color: ${bgColorRgba} !important;
                    }
                `;
            };

            // Initialize subtitle settings
            if (CONFIG.HAS_SUBTITLES && ccSettingsPanel) {
                const settings = loadSubtitleSettings();
                
                if (ccFontSizeInput) {
                    ccFontSizeInput.value = settings.fontSize;
                    if (ccFontSizeValue) ccFontSizeValue.textContent = settings.fontSize;
                }
                if (ccTextColorInput) ccTextColorInput.value = settings.textColor;
                if (ccBgColorInput) ccBgColorInput.value = settings.bgColor;
                if (ccBgOpacityInput) {
                    ccBgOpacityInput.value = settings.bgOpacity;
                    if (ccBgOpacityValue) ccBgOpacityValue.textContent = settings.bgOpacity;
                }
                
                applySubtitleStyles(settings);
                
                // Font size change
                ccFontSizeInput?.addEventListener('input', (e) => {
                    const value = e.target.value;
                    if (ccFontSizeValue) ccFontSizeValue.textContent = value;
                    saveSubtitleSetting('snn-cc-font-size', value);
                    const currentSettings = loadSubtitleSettings();
                    applySubtitleStyles(currentSettings);
                });
                
                // Text color change
                ccTextColorInput?.addEventListener('input', (e) => {
                    saveSubtitleSetting('snn-cc-text-color', e.target.value);
                    const currentSettings = loadSubtitleSettings();
                    applySubtitleStyles(currentSettings);
                });
                
                // Background color change
                ccBgColorInput?.addEventListener('input', (e) => {
                    saveSubtitleSetting('snn-cc-bg-color', e.target.value);
                    const currentSettings = loadSubtitleSettings();
                    applySubtitleStyles(currentSettings);
                });
                
                // Background opacity change
                ccBgOpacityInput?.addEventListener('input', (e) => {
                    const value = e.target.value;
                    if (ccBgOpacityValue) ccBgOpacityValue.textContent = value;
                    saveSubtitleSetting('snn-cc-bg-opacity', value);
                    const currentSettings = loadSubtitleSettings();
                    applySubtitleStyles(currentSettings);
                });
                
                // Settings button click
                ccSettingsBtn?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    ccLangList?.classList.add('snn-hidden');
                    ccSettingsPanel?.classList.add('snn-show');
                    ccBackBtn?.classList.add('snn-show');
                });
                
                // Back button click
                ccBackBtn?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    ccLangList?.classList.remove('snn-hidden');
                    ccSettingsPanel?.classList.remove('snn-show');
                    ccBackBtn?.classList.remove('snn-show');
                });
            }

            // Subtitle/CC functionality
            if (CONFIG.HAS_SUBTITLES && ccBtn && ccMenu) {
                ccBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    ccMenu.classList.toggle('snn-show');
                });

                // Add click handlers only to subtitle track items (not Settings or Back buttons)
                ccMenuItems.forEach(item => {
                    // Skip Settings and Back buttons - they have their own handlers
                    if (item.classList.contains('snn-cc-settings-btn') || item.classList.contains('snn-cc-back-btn')) {
                        return;
                    }
                    
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        
                        const trackIndex = parseInt(item.dataset.track);
                        
                        // Remove all active classes and checkmarks from track items only
                        ccMenuItems.forEach(menuItem => {
                            // Skip Settings and Back buttons when removing active states
                            if (menuItem.classList.contains('snn-cc-settings-btn') || menuItem.classList.contains('snn-cc-back-btn')) {
                                return;
                            }
                            
                            menuItem.classList.remove('snn-active');
                            const existingCheck = menuItem.querySelector('svg');
                            if (existingCheck) {
                                existingCheck.remove();
                            }
                        });
                        
                        // Disable all text tracks
                        for (let i = 0; i < video.textTracks.length; i++) {
                            video.textTracks[i].mode = 'disabled';
                        }
                        
                        // Enable selected track or turn off
                        if (trackIndex >= 0 && trackIndex < video.textTracks.length) {
                            video.textTracks[trackIndex].mode = 'showing';
                            item.classList.add('snn-active');
                            // Add checkmark
                            const checkmark = document.createElement('div');
                            checkmark.innerHTML = ICONS.check;
                            item.insertBefore(checkmark.firstChild, item.firstChild);
                        } else {
                            // "Off" was selected
                            item.classList.add('snn-active');
                            const checkmark = document.createElement('div');
                            checkmark.innerHTML = ICONS.check;
                            item.insertBefore(checkmark.firstChild, item.firstChild);
                        }
                        
                        ccMenu.classList.remove('snn-show');
                    });
                });

                // Close CC menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (ccMenu && !ccMenu.contains(e.target) && !ccBtn.contains(e.target)) {
                        ccMenu.classList.remove('snn-show');
                    }
                });
            }

            const handleKeydown = (e) => {
                if (!isPlayerInView) return;

                const activeEl = document.activeElement;
                if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable)) return;

                showControls();
                switch (e.key.toLowerCase()) {
                    case 'f': e.preventDefault(); toggleFullscreen(); break;
                    case ' ': case 'k': e.preventDefault(); togglePlayPause(); break;
                    case 'm': e.preventDefault(); toggleMute(); break;
                    case 'arrowright': e.preventDefault(); video.currentTime = Math.min(video.duration, video.currentTime + CONFIG.KEY_SEEK_SECONDS); break;
                    case 'arrowleft': e.preventDefault(); video.currentTime = Math.max(0, video.currentTime - CONFIG.KEY_SEEK_SECONDS); break;
                    case 'c': 
                        if (CONFIG.HAS_SUBTITLES && ccBtn && ccMenu) {
                            e.preventDefault(); 
                            ccMenu.classList.toggle('snn-show');
                        }
                        break;
                }
            };

            video.addEventListener('play', updatePlayPauseIcon);
            video.addEventListener('pause', updatePlayPauseIcon);
            video.addEventListener('volumechange', () => {
                if (volumeSlider) volumeSlider.value = video.volume;
                updateMuteIcon();
                updateProgressBarFill(volumeSlider);
            });
            video.addEventListener('timeupdate', updateProgress);
            video.addEventListener('loadedmetadata', () => {
                updateProgress();
                generateChapterSections();
                generateChapters();
                if (CONFIG.INITIAL_MUTED) {
                    video.muted = true;
                    video.volume = 0;
                    if (volumeSlider) volumeSlider.value = 0;
                }
                updateMuteIcon();
                updateProgressBarFill(volumeSlider);
            });

            videoContainer?.addEventListener('mouseenter', showControls);
            videoContainer?.addEventListener('mousemove', showControls);
            videoContainer?.addEventListener('mouseleave', () => {
                if (!CONFIG.DISABLE_AUTOHIDE) {
                    clearTimeout(inactivityTimer);
                    inactivityTimer = setTimeout(hideControls, 300);
                }
            });

            controlsOverlay?.addEventListener('click', togglePlayPause);
            controlsOverlay?.addEventListener('dblclick', toggleFullscreen);
            controlsBarContainer?.addEventListener('click', e => e.stopPropagation());
            controlsBarContainer?.addEventListener('dblclick', e => e.stopPropagation());
            playPauseBtn?.addEventListener('click', togglePlayPause);
            muteBtn?.addEventListener('click', toggleMute);
            fullscreenBtn?.addEventListener('click', toggleFullscreen);
            
            volumeSlider?.addEventListener('input', () => {
                video.muted = false;
                video.volume = volumeSlider.value;
            });

            progressBar?.addEventListener('input', e => {
                isSeeking = true;
                const scrubTime = (e.target.value / 100) * video.duration;
                if(timeDisplay) timeDisplay.textContent = `${formatTime(scrubTime)} / ${formatTime(video.duration)}`;
                updateProgressThumbPosition();
                
                const percent = e.target.value / 100;
                chapterSections.forEach((section) => {
                    const sectionFill = section.element.querySelector('.snn-chapter-section-fill');
                    if (!sectionFill) return;
                    
                    if (scrubTime < section.startTime) {
                        sectionFill.style.width = '0%';
                    } else if (scrubTime >= section.endTime) {
                        sectionFill.style.width = '100%';
                    } else {
                        const sectionProgress = ((scrubTime - section.startTime) / (section.endTime - section.startTime)) * 100;
                        sectionFill.style.width = `${sectionProgress}%`;
                    }
                });
            });
            
            progressBar?.addEventListener('change', e => {
                isSeeking = false;
                video.currentTime = (e.target.value / 100) * video.duration;
            });

            document.addEventListener('keydown', handleKeydown);
            document.addEventListener('fullscreenchange', updateFullscreenIcons);

            const observerCallback = (entries) => {
                entries.forEach(entry => {
                    isPlayerInView = entry.isIntersecting;
                });
            };
            const observer = new IntersectionObserver(observerCallback, {
                root: null,
                threshold: 0.25
            });
            if (playerWrapper) {
                observer.observe(playerWrapper);
            }

            updatePlayPauseIcon();
            updateMuteIcon();
            updateProgressBarFill(volumeSlider);
            updateProgressThumbPosition();
            if (CONFIG.DISABLE_AUTOHIDE) {
                showControls();
                videoContainer?.classList.remove('snn-controls-hidden');
                videoContainer?.classList.add('snn-controls-visible');
            } else {
                showControls();
            }
        })();
        </script>
        <?php
        echo "</div>";
    }
}