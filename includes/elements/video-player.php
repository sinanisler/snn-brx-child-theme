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
            'placeholder' => 'e.g., https://example.com/your-video.mp4',
            'description' => esc_html__( 'Enter a direct URL to your video file. This will override the Media Library selection.', 'snn' ),
        ];

        $this->controls['poster_image'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Poster Image', 'snn' ),
            'type'  => 'image',
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
        ];

        $this->controls['player_height'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Height', 'snn' ),
            'type'  => 'number',
            'units' => ['px', 'vh'],
            'default' => '400px',
        ];

        $this->controls['player_max_width'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Max Width', 'snn' ),
            'type'  => 'number',
            'units' => ['px', '%'],
            'default' => '100%',
        ];

        $this->controls['primary_accent_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Primary Accent', 'snn' ),
            'type'  => 'color',
            'default' => 'rgba(0, 0, 0, 1)',
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
        $manual_video_url = ! empty( $settings['video_url_manual'] ) ? $settings['video_url_manual'] : '';
        $video_url = ! empty( $manual_video_url ) ? $manual_video_url : $video_file_url;

        $poster_url = ! empty( $settings['poster_image']['id'] ) ? wp_get_attachment_image_url( $settings['poster_image']['id'], 'full' ) : '';
        $chapters   = $settings['chapters'] ?? [];

        $autoplay           = ! empty( $settings['autoplay'] );
        $muted              = ! empty( $settings['muted'] );
        $loop               = ! empty( $settings['loop'] );
        $disable_autohide = ! empty( $settings['disable_autohide'] );

        $player_height      = $settings['player_height'] ?? '400px';
        $player_max_width = $settings['player_max_width'] ?? '896px';

        $accent_color        = $settings['primary_accent_color']['raw'] ?? $settings['primary_accent_color']['hex'] ?? '#ffd64f';
        $text_color          = $settings['text_color']['raw'] ?? $settings['text_color']['hex'] ?? '#ffffff';
        $slider_track        = $settings['slider_track_color']['raw'] ?? $settings['slider_track_color']['hex'] ?? 'rgba(255, 255, 255, 0.3)';
        $chapter_dot_color = $settings['chapter_dot_color']['raw'] ?? $settings['chapter_dot_color']['hex'] ?? '#ffffff';
        $btn_hover_bg        = $settings['button_hover_background']['raw'] ?? $settings['button_hover_background']['hex'] ?? 'rgba(255, 255, 255, 0.2)';
        $button_color        = $settings['button_color']['raw'] ?? $settings['button_color']['hex'] ?? 'rgba(255, 255, 255, 1)';
        $tooltip_text_color  = $settings['tooltip_text_color']['raw'] ?? $settings['tooltip_text_color']['hex'] ?? 'rgba(0, 0, 0, 1)';

        echo "<div {$this->render_attributes('_root')}>";

        echo "<style>
            #" . esc_attr($root_id) . " { --primary-accent-color: {$accent_color}; --text-color: {$text_color}; --slider-track-color: {$slider_track}; --chapter-dot-color: {$chapter_dot_color}; --button-hover-background: {$btn_hover_bg}; --player-height: {$player_height}; --player-max-width: {$player_max_width}; --button-color: {$button_color}; width: 100%; max-width: var(--player-max-width); margin-left: auto; margin-right: auto; }
            #" . esc_attr($root_id) . " { --primary-accent-color: {$accent_color}; --text-color: {$text_color}; --slider-track-color: {$slider_track}; --chapter-dot-color: {$chapter_dot_color}; --button-hover-background: {$btn_hover_bg}; --player-height: {$player_height}; --player-max-width: {$player_max_width}; --button-color: {$button_color}; --tooltip-text-color: {$tooltip_text_color}; width: 100%; max-width: var(--player-max-width); margin-left: auto; margin-right: auto; }
            #" . esc_attr($root_id) . " .snn-video-container { position: relative; background-color: #000; overflow: hidden; height: var(--player-height); }
            #" . esc_attr($root_id) . " .snn-video-container video { width: 100%; height: 100%; display: block; object-fit: cover; }
            #" . esc_attr($root_id) . " .snn-video-container:fullscreen { width: 100vw; height: 100vh; max-width: 100%; border-radius: 0; }
            #" . esc_attr($root_id) . " .snn-controls-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; opacity: 0; transition: opacity 0.3s ease-in-out; }
            #" . esc_attr($root_id) . " .snn-video-container:hover .snn-controls-overlay, #" . esc_attr($root_id) . " .snn-video-container.snn-controls-visible .snn-controls-overlay { opacity: 1; }
            #" . esc_attr($root_id) . " .snn-controls-hidden .snn-controls-overlay { cursor: none; opacity: 0; pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-controls-bar-container { padding: 9px 15px; }
            #" . esc_attr($root_id) . " .snn-progress-container { position: relative; margin-bottom: 7.5px; height: 5px; }
            #" . esc_attr($root_id) . " .snn-progress-tooltip { position: absolute; background-color: var(--primary-accent-color); color: var(--text-color); font-size: 12px; border-radius: 3.75px; padding: 3.75px 7.5px; bottom: 100%; margin-bottom: 8px; pointer-events: none; opacity: 0; transition: opacity 0.2s; white-space: nowrap; transform: translateX(-50%); max-width: 200px; overflow: hidden; text-overflow: ellipsis; z-index: 10; }
            #" . esc_attr($root_id) . " .snn-progress-tooltip { position: absolute; background-color: var(--primary-accent-color); color: var(--tooltip-text-color); font-size: 12px; border-radius: 3.75px; padding: 3.75px 7.5px; bottom: 100%; margin-bottom: 8px; pointer-events: none; opacity: 0; transition: opacity 0.2s; white-space: nowrap; transform: translateX(-50%); max-width: 200px; overflow: hidden; text-overflow: ellipsis; z-index: 10; }
            #" . esc_attr($root_id) . " .snn-chapter-dots-container { position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; z-index: 5; }
            #" . esc_attr($root_id) . " .snn-chapter-sections-container { position: absolute; width: 100%; height: 100%; top: 0; left: 0; display: flex; z-index: 3; pointer-events: all; }
            #" . esc_attr($root_id) . " .snn-chapter-section { position: relative; height: 5px; background: transparent; transition: height 0.15s ease; cursor: pointer; display: flex; align-items: flex-end; }
            #" . esc_attr($root_id) . " .snn-chapter-section:hover { transform:scaleY(1.6)}
            #" . esc_attr($root_id) . " .snn-chapter-section-fill { position: absolute; bottom: 0; left: 0; width: 0%; height: 100%; background: var(--primary-accent-color); transition: width 0.1s linear; pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-chapter-section-bg { position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; background: var(--slider-track-color); pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-controls-bar { display: flex; align-items: center; justify-content: space-between; color: var(--text-color); }
            #" . esc_attr($root_id) . " .snn-controls-left, #" . esc_attr($root_id) . " .snn-controls-right { display: flex; align-items: center; gap: 10px; }
            #" . esc_attr($root_id) . " .snn-control-button { background: none; border: none; color: var(--button-color); padding: 5px; border-radius: 9999px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s; filter:drop-shadow(0px 0px 2px #00000099) }
            #" . esc_attr($root_id) . " .snn-control-button:hover { background-color: var(--button-hover-background); }
            #" . esc_attr($root_id) . " .snn-control-button svg { width: 30px; height: 30px; fill: currentColor; }
            #" . esc_attr($root_id) . " .snn-volume-container { display: flex; align-items: center; position: relative; }
            #" . esc_attr($root_id) . " .snn-volume-container .snn-volume-slider { width: 0; transition: width 0.3s ease; opacity: 0; }
            #" . esc_attr($root_id) . " .snn-volume-container:hover .snn-volume-slider { width: 75px; opacity: 1; }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider { -webkit-appearance: none; appearance: none; width: 100%; height: 5px; background: transparent; cursor: pointer; border-radius: 5px; position: absolute; top: 0; left: 0; z-index: 0; }
            #" . esc_attr($root_id) . " .snn-volume-slider { -webkit-appearance: none; appearance: none; height: 5px; background: var(--slider-track-color); cursor: pointer; border-radius: 5px; transition: height 0.2s ease, width 0.3s ease, opacity 0.3s ease; margin-left: 7.5px; position: relative; }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; background: var(--primary-accent-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--text-color); transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider:hover::-webkit-slider-thumb { transform: scale(1.1); }
            #" . esc_attr($root_id) . " .snn-progress-bar.snn-video-slider::-moz-range-thumb { width: 16px; height: 16px; background: var(--primary-accent-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--text-color); }
            #" . esc_attr($root_id) . " .snn-volume-slider:hover { height: 8px; }
            #" . esc_attr($root_id) . " .snn-volume-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; background: var(--primary-accent-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--text-color); transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-volume-slider:hover::-webkit-slider-thumb { transform: scale(1.1); }
            #" . esc_attr($root_id) . " .snn-volume-slider::-moz-range-thumb { width: 16px; height: 16px; background: var(--primary-accent-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--text-color); }
            #" . esc_attr($root_id) . " .snn-chapter-dot { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 4px; height: 5px; background: var(--chapter-dot-color); border-radius: 2px; cursor: pointer; transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-chapter-dot:hover { transform: translate(-50%, -50%) scale(1.5); }
            #" . esc_attr($root_id) . " .snn-hidden { display: none !important; }
        </style>";

        ?>
        <div class="snn-video-container">
            <video class="snn-video" poster="<?php echo esc_url( $poster_url ); ?>" playsinline
                <?php echo $autoplay ? 'autoplay' : ''; ?>
                <?php echo $muted ? 'muted' : ''; ?>
                <?php echo $loop ? 'loop' : ''; ?>
            >
                <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>

            <div class="snn-controls-overlay">
                <div class="snn-controls-bar-container">
                    <div class="snn-progress-container">
                        <div class="snn-progress-tooltip">00:00</div>
                        <div class="snn-chapter-sections-container"></div>
                        <input type="range" class="snn-video-slider snn-progress-bar" min="0" max="100" step="0.1" value="0">
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
                DISABLE_AUTOHIDE: <?php echo json_encode($disable_autohide); ?>
            };

            const ICONS = {
                play: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 6v12l10-6z"></path></svg>`,
                pause: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>`,
                volumeHigh: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"></path></svg>`,
                volumeMute: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"></path></svg>`,
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
            const timeDisplay             = playerWrapper.querySelector('.snn-time-display');
            const chapterDotsContainer = playerWrapper.querySelector('.snn-chapter-dots-container');
            const chapterSectionsContainer = playerWrapper.querySelector('.snn-chapter-sections-container');
            const progressTooltip         = playerWrapper.querySelector('.snn-progress-tooltip');
            const fullscreenIcon          = playerWrapper.querySelector('.snn-fullscreen-icon');
            const fullscreenExitIcon      = playerWrapper.querySelector('.snn-fullscreen-exit-icon');

            if (!video || !controlsOverlay || !playPauseBtn) return;

            let isSeeking = false, inactivityTimer, lastVolume = video.volume, isPlayerInView = false;
            let chapterSections = [];

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
                const progress = bar.value;
                const accentColor = getComputedStyle(playerWrapper).getPropertyValue('--primary-accent-color').trim();
                const trackColor = getComputedStyle(playerWrapper).getPropertyValue('--slider-track-color').trim();
                bar.style.background = `linear-gradient(to right, ${accentColor} ${progress}%, ${trackColor} ${progress}%)`;
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
                if (isSeeking || isNaN(video.duration)) return;
                if (progressBar) progressBar.value = (video.currentTime / video.duration) * 100;
                if (timeDisplay) timeDisplay.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration || 0)}`;
                updateChapterSectionsFill();
            };

            const hideControls = () => {
                if (video.paused || CONFIG.DISABLE_AUTOHIDE) return;
                videoContainer?.classList.add('snn-controls-hidden');
                videoContainer?.classList.remove('snn-controls-visible');
            };

            const showControls = () => {
                videoContainer?.classList.remove('snn-controls-hidden');
                videoContainer?.classList.add('snn-controls-visible');
                clearTimeout(inactivityTimer);
                if (!CONFIG.DISABLE_AUTOHIDE) {
                    inactivityTimer = setTimeout(hideControls, CONFIG.INACTIVITY_TIMEOUT);
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
                    hideControls();
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