<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;

class SNN_Video_Player_Element extends Element {
    public $category     = 'snn'; // Custom category for your elements
    public $name         = 'snn-video-player';
    public $icon         = 'ti-control-play'; // A suitable icon from Themify Icons
    public $css_selector = '.snn-player-wrapper';
    public $scripts      = []; // Scripts will be enqueued in the render method
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Video Player', 'bricks' );
    }

    public function set_controls() {
        $this->controls['video_file'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Video File (Media Library)', 'bricks' ),
            'type'  => 'video', // Allows selecting video from the media library
            'default' => [
                'url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
        ];

        $this->controls['video_url_manual'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Video URL (Manual)', 'bricks' ),
            'type'          => 'text',
            'placeholder' => 'e.g., https://example.com/your-video.mp4',
            'description' => esc_html__( 'Enter a direct URL to your video file. This will override the Media Library selection.', 'bricks' ),
        ];

        $this->controls['poster_image'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Poster Image', 'bricks' ),
            'type'  => 'image',
        ];

        $this->controls['autoplay'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Autoplay', 'bricks' ),
            'type'  => 'checkbox',
            'default' => false, // Default to off
        ];

        $this->controls['muted'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Muted', 'bricks' ),
            'type'  => 'checkbox',
            'default' => false, // Default to off
        ];

        $this->controls['disable_autohide'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Disable Auto Hide Controls', 'bricks' ),
            'type'  => 'checkbox',
            'default' => false, // Default to off
        ];

        $this->controls['chapters'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Video Chapters', 'bricks' ),
            'type'          => 'repeater',
            'titleProperty' => 'title',
            'default'       => [
                ['title' => 'Be Brave', 'time'  => '0:05'],
            ],
            'fields'        => [
                'title' => ['label' => esc_html__( 'Title', 'bricks' ), 'type'  => 'text'],
                'time'  => ['label' => esc_html__( 'Time (e.g., 1:45)', 'bricks' ), 'type' => 'text', 'placeholder' => 'm:ss'],
            ],
        ];

        $this->controls['player_height'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Height', 'bricks' ),
            'type'  => 'number',
            'units' => ['px', 'vh'],
            'default' => '400px',
        ];

        $this->controls['player_max_width'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Max Width', 'bricks' ),
            'type'  => 'number',
            'units' => ['px', '%'],
            'default' => '100%',
        ];

        // Modified color controls to use 'raw' defaults
        $this->controls['primary_accent_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Primary Accent', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(0, 0, 0, 1)', // Raw RGBA default
        ];

        $this->controls['text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Text & Icons Color', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)', // Raw RGBA default
        ];

        $this->controls['slider_track_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Slider Track', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 0.3)', // Already raw, kept as is
        ];

        $this->controls['chapter_dot_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Chapter Dot', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)', // Raw RGBA default
        ];

        $this->controls['button_hover_background'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Hover BG', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 0.2)', // Already raw, kept as is
        ];

        // New control for button color
        $this->controls['button_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Color', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)', // Default to white
        ];
    }

    public function render() {
        $settings = $this->settings;

        // Adopt the robust ID generation from the working example.
        // This ensures the element always has a unique ID for CSS and JS scoping.
        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'snn-player-' . uniqid();
            $this->set_attribute( '_root', 'id', $root_id );
        }

        // Add a class to the root element for general styling and identification.
        $this->set_attribute( '_root', 'class', 'brxe-snn-video-player snn-player-wrapper' );

        // Get settings with defaults
        $video_file_url = ! empty( $settings['video_file']['url'] ) ? $settings['video_file']['url'] : 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        $manual_video_url = ! empty( $settings['video_url_manual'] ) ? $settings['video_url_manual'] : '';

        // Prioritize manual URL if provided, otherwise use media library URL
        $video_url = ! empty( $manual_video_url ) ? $manual_video_url : $video_file_url;

        $poster_url = ! empty( $settings['poster_image']['id'] ) ? wp_get_attachment_image_url( $settings['poster_image']['id'], 'full' ) : '';
        $chapters   = $settings['chapters'] ?? [];

        $autoplay           = ! empty( $settings['autoplay'] );
        $muted              = ! empty( $settings['muted'] );
        $disable_autohide = ! empty( $settings['disable_autohide'] );

        // Layout settings
        $player_height      = $settings['player_height'] ?? '400px';
        $player_max_width = $settings['player_max_width'] ?? '896px';

        // Color settings - Prioritize 'raw', then 'hex', then default
        $accent_color        = $settings['primary_accent_color']['raw'] ?? $settings['primary_accent_color']['hex'] ?? '#3b82f6';
        $text_color          = $settings['text_color']['raw'] ?? $settings['text_color']['hex'] ?? '#ffffff';
        $slider_track        = $settings['slider_track_color']['raw'] ?? $settings['slider_track_color']['hex'] ?? 'rgba(255, 255, 255, 0.3)';
        $chapter_dot_color = $settings['chapter_dot_color']['raw'] ?? $settings['chapter_dot_color']['hex'] ?? '#ffffff';
        $btn_hover_bg        = $settings['button_hover_background']['raw'] ?? $settings['button_hover_background']['hex'] ?? 'rgba(255, 255, 255, 0.2)';
        $button_color        = $settings['button_color']['raw'] ?? $settings['button_color']['hex'] ?? 'rgba(255, 255, 255, 1)'; // New button color

        // Start rendering the root element
        echo "<div {$this->render_attributes('_root')}>";

        // Output the scoped CSS. Using esc_attr() for security.
        echo "<style>
            /* Scoping all styles to the unique root ID */
            #" . esc_attr($root_id) . " { --primary-accent-color: {$accent_color}; --text-color: {$text_color}; --slider-track-color: {$slider_track}; --chapter-dot-color: {$chapter_dot_color}; --button-hover-background: {$btn_hover_bg}; --player-height: {$player_height}; --player-max-width: {$player_max_width}; --button-color: {$button_color}; width: 100%; max-width: var(--player-max-width); margin-left: auto; margin-right: auto; }
            #" . esc_attr($root_id) . " .snn-video-container { position: relative; background-color: #000; overflow: hidden;   /* 0.5rem * 15 */ box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); height: var(--player-height); }
            #" . esc_attr($root_id) . " .snn-video-container video { width: 100%; height: 100%; display: block;     /* 0.5rem * 15 */ object-fit: cover; }
            #" . esc_attr($root_id) . " .snn-video-container:fullscreen { width: 100vw; height: 100vh; max-width: 100%; border-radius: 0; }
            #" . esc_attr($root_id) . " .snn-controls-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; opacity: 0; transition: opacity 0.3s ease-in-out;   }
            #" . esc_attr($root_id) . " .snn-video-container:hover .snn-controls-overlay, #" . esc_attr($root_id) . " .snn-video-container.snn-controls-visible .snn-controls-overlay { opacity: 1; }
            #" . esc_attr($root_id) . " .snn-controls-hidden .snn-controls-overlay { cursor: none; opacity: 0; pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-controls-bar-container { padding: 9px 15px; /* 0.6rem * 15, 1rem * 15 */ }
            #" . esc_attr($root_id) . " .snn-progress-container { position: relative; margin-bottom: 7.5px; /* 0.5rem * 15 */ }
            #" . esc_attr($root_id) . " .snn-progress-tooltip { position: absolute; background-color: var(--primary-accent-color); color: var(--text-color); font-size: 14px; /* 0.75rem * 15 */ border-radius: 3.75px; /* 0.25rem * 15 */ padding: 3.75px 7.5px; /* 0.25rem * 15, 0.5rem * 15 */ top: -30px; /* -2rem * 15 */ pointer-events: none; opacity: 0; transition: opacity 0.2s; white-space: nowrap; transform: translateX(-50%); }
            #" . esc_attr($root_id) . " .snn-chapter-dots-container { position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; }
            #" . esc_attr($root_id) . " .snn-controls-bar { display: flex; align-items: center; justify-content: space-between; color: var(--text-color); }
            #" . esc_attr($root_id) . " .snn-controls-left, #" . esc_attr($root_id) . " .snn-controls-right { display: flex; align-items: center; gap: 10px; }
            #" . esc_attr($root_id) . " .snn-control-button { background: none; border: none; color: var(--button-color); padding: 5px; border-radius: 9999px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s; }
            #" . esc_attr($root_id) . " .snn-control-button:hover { background-color: var(--button-hover-background); }
            #" . esc_attr($root_id) . " .snn-control-button svg { width: 30px; height: 30px; fill: currentColor; }
            #" . esc_attr($root_id) . " .snn-volume-container { display: flex; align-items: center; }
            #" . esc_attr($root_id) . " .snn-volume-container .snn-volume-slider { width: 0; transition: width 0.3s ease; opacity: 0; }
            #" . esc_attr($root_id) . " .snn-volume-container:hover .snn-volume-slider { width: 75px; /* 5rem * 15 */ opacity: 1; }
            #" . esc_attr($root_id) . " .snn-volume-slider { margin-left: 7.5px; /* 0.5rem * 15 */ }
            /* Renamed .snn-slider to .snn-video-slider */
            #" . esc_attr($root_id) . " .snn-video-slider { -webkit-appearance: none; appearance: none; width: 100%; height: 5px; background: var(--slider-track-color); cursor: pointer; border-radius: 5px; transition: height 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-video-slider:hover { height: 8px; }
            #" . esc_attr($root_id) . " .snn-video-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; background: var(--primary-accent-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--text-color); transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-video-slider:hover::-webkit-slider-thumb { transform: scale(1.1); }
            #" . esc_attr($root_id) . " .snn-video-slider::-moz-range-thumb { width: 16px; height: 16px; background: var(--primary-accent-color); border-radius: 50%; cursor: pointer; border: 2px solid var(--text-color); }
            #" . esc_attr($root_id) . " .snn-chapter-dot { position: absolute; top: 60%; transform: translate(-50%, -50%); width: 12px; height: 12px; background: var(--chapter-dot-color); border-radius: 50%; cursor: pointer; transition: transform 0.2s ease; }
            #" . esc_attr($root_id) . " .snn-chapter-dot:hover { transform: translate(-50%, -50%) scale(1.5); }
            #" . esc_attr($root_id) . " .snn-hidden { display: none !important; }
            /* Styles for chapter tooltip */
            #" . esc_attr($root_id) . " .snn-chapter-tooltip {
                position: absolute;
                background-color: var(--primary-accent-color);
                color: var(--text-color);
                font-size: 15px; /* 0.75rem * 15 */
                border-radius: 3.75px; /* 0.25rem * 15 */
                padding: 3.75px 7.5px; /* 0.25rem * 15, 0.5rem * 15 */
                bottom: 20px; /* MODIFIED: Position from bottom to grow upwards */
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s;
                white-space: normal; /* Allow text to wrap */
                word-wrap: break-word; /* Break long words */
                max-width: 50%; /* Limit tooltip width */
                text-align: center; /* Center wrapped text */
                transform: translateX(-50%); /* Center horizontally by default */
                z-index: 10; /* Ensure it's above other elements */
            }
        </style>";

        // Output the HTML structure
        ?>
        <div class="snn-video-container">
            <video class="snn-video" poster="<?php echo esc_url( $poster_url ); ?>" playsinline
                <?php echo $autoplay ? 'autoplay' : ''; ?>
                <?php echo $muted ? 'muted' : ''; ?>
            >
                <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>

            <div class="snn-controls-overlay">
                <div class="snn-controls-bar-container">
                    <div class="snn-progress-container">
                        <div class="snn-progress-tooltip">00:00</div>
                        <input type="range" class="snn-video-slider snn-progress-bar" min="0" max="100" step="0.1" value="0">
                        <div class="snn-chapter-dots-container"></div>
                        <div class="snn-chapter-tooltip"></div>
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
        // End of root element div is at the very end

        // Output the JavaScript.
        ?>
        <script>
        // The main problem was running the script inside 'DOMContentLoaded'.
        // The Bricks editor re-renders elements without firing that event.
        // By wrapping the code in an Immediately Invoked Function Expression (IIFE),
        // it runs as soon as the browser parses this script tag, which is right
        // after the element's HTML has been rendered. This works reliably on
        // both the frontend and in the editor.
        (() => {
            // Use the unique root ID to find the correct player instance.
            const playerWrapper = document.getElementById('<?php echo esc_js($root_id); ?>');

            // Guard against this script running on the wrong element or running twice.
            if (!playerWrapper || playerWrapper.dataset.snnPlayerInitialized) {
                return;
            }
            playerWrapper.dataset.snnPlayerInitialized = 'true';

            // --- CONFIGURATION & ELEMENTS ---
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

            // Get all required DOM elements, with checks to prevent errors
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
            const progressTooltip         = playerWrapper.querySelector('.snn-progress-tooltip');
            const chapterTooltip          = playerWrapper.querySelector('.snn-chapter-tooltip');
            const fullscreenIcon          = playerWrapper.querySelector('.snn-fullscreen-icon');
            const fullscreenExitIcon      = playerWrapper.querySelector('.snn-fullscreen-exit-icon');

            if (!video || !controlsOverlay || !playPauseBtn) return; // Essential elements check

            // MODIFIED: Use `isPlayerInView` to track if the player is visible in the viewport.
            let isSeeking = false, inactivityTimer, lastVolume = video.volume, isPlayerInView = false;

            // --- HELPER FUNCTIONS (more readable) ---
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

            // --- CORE PLAYER LOGIC ---
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

            const updateProgress = () => {
                if (isSeeking || isNaN(video.duration)) return;
                if (progressBar) progressBar.value = (video.currentTime / video.duration) * 100;
                if (timeDisplay) timeDisplay.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration || 0)}`;
                updateProgressBarFill(progressBar);
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

            const generateChapters = () => {
                if (!chapterDotsContainer || isNaN(video.duration) || !CONFIG.CHAPTERS) return;
                chapterDotsContainer.innerHTML = '';
                CONFIG.CHAPTERS.forEach(chapter => {
                    const seconds = timeToSeconds(chapter.time);
                    if (seconds > video.duration) return;

                    const dot = document.createElement('div');
                    dot.className = 'snn-chapter-dot';
                    dot.style.left = `${(seconds / video.duration) * 100}%`;
                    dot.dataset.title = chapter.title;
                    dot.style.pointerEvents = 'auto';

                    dot.addEventListener('click', e => {
                        e.stopPropagation();
                        video.currentTime = seconds;
                        video.play();
                    });

                    dot.addEventListener('mouseenter', e => {
                        if (!chapterTooltip || !progressBar) return;
                        chapterTooltip.textContent = dot.dataset.title;
                        chapterTooltip.style.opacity = '1';
                        chapterTooltip.style.left = dot.style.left;
                        chapterTooltip.style.transform = 'translateX(-50%)';
                        setTimeout(() => {
                            if (!playerWrapper.contains(chapterTooltip)) return;
                            const progressBarRect = progressBar.getBoundingClientRect();
                            const tooltipRect = chapterTooltip.getBoundingClientRect();
                            if (tooltipRect.left < progressBarRect.left) {
                                chapterTooltip.style.left = '0px';
                                chapterTooltip.style.transform = 'translateX(0)';
                            } else if (tooltipRect.right > progressBarRect.right) {
                                chapterTooltip.style.left = '100%';
                                chapterTooltip.style.transform = 'translateX(-100%)';
                            }
                        }, 0);
                    });

                    dot.addEventListener('mouseleave', () => {
                        if (chapterTooltip) {
                            chapterTooltip.style.opacity = '0';
                            chapterTooltip.style.transform = 'translateX(-50%)';
                        }
                    });

                    chapterDotsContainer.appendChild(dot);
                });
            };

            const handleKeydown = (e) => {
                // MODIFIED: Only apply shortcuts if the player is in the viewport.
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

            // --- EVENT LISTENERS ---
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
                generateChapters();
                if (CONFIG.INITIAL_MUTED) {
                    video.muted = true;
                    video.volume = 0;
                    if (volumeSlider) volumeSlider.value = 0;
                }
                updateMuteIcon();
                updateProgressBarFill(volumeSlider);
            });

            // Controls visibility on hover (independent of keyboard shortcuts)
            videoContainer?.addEventListener('mouseenter', showControls);
            videoContainer?.addEventListener('mousemove', showControls);
            videoContainer?.addEventListener('mouseleave', () => {
                if (!CONFIG.DISABLE_AUTOHIDE) {
                    clearTimeout(inactivityTimer);
                    hideControls();
                }
            });

            // Click/Input handlers
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
                updateProgressBarFill(progressBar);
            });
            progressBar?.addEventListener('change', e => {
                isSeeking = false;
                video.currentTime = (e.target.value / 100) * video.duration;
            });
            progressBar?.addEventListener('mousemove', e => {
                if (!progressTooltip || isNaN(video.duration)) return;
                const rect = progressBar.getBoundingClientRect();
                const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                progressTooltip.style.left = `${percent * 100}%`;
                progressTooltip.textContent = formatTime(percent * video.duration);
                progressTooltip.style.opacity = '1';
            });
            progressBar?.addEventListener('mouseleave', () => { if(progressTooltip) progressTooltip.style.opacity = '0'; });

            // Global listeners
            document.addEventListener('keydown', handleKeydown);
            document.addEventListener('fullscreenchange', updateFullscreenIcons);

            // --- NEW: INTERSECTION OBSERVER FOR VIEWPORT-AWARE KEYBOARD SHORTCUTS ---
            const observerCallback = (entries) => {
                entries.forEach(entry => {
                    isPlayerInView = entry.isIntersecting;
                });
            };
            const observer = new IntersectionObserver(observerCallback, {
                root: null, // Observe intersections relative to the viewport
                threshold: 0.25 // Fire when at least 25% of the player is visible
            });
            if (playerWrapper) {
                observer.observe(playerWrapper);
            }

            // --- INITIALIZATION ---
            updatePlayPauseIcon();
            updateMuteIcon();
            updateProgressBarFill(progressBar);
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
        echo "</div>"; // End of root element
    }
}