<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;

class SNN_Audio_Player_Element extends Element {
    public $category     = 'snn'; // Custom category for your elements
    public $name         = 'snn-audio-player';
    public $icon         = 'ti-music-alt'; // A suitable icon from Themify Icons
    public $css_selector = '.snn-audio-player-wrapper';
    public $scripts      = []; // Scripts will be enqueued in the render method
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Audio Player', 'bricks' );
    }

    public function set_controls() {
        $this->controls['audio_file'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Audio File (Media Library)', 'bricks' ),
            'type'  => 'audio', // Allows selecting audio from the media library
        ];

        $this->controls['audio_url_manual'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Audio URL (Manual)', 'bricks' ),
            'type'        => 'text',
            'placeholder' => 'e.g., https://example.com/your-audio.mp3',
            'description' => esc_html__( 'Enter a direct URL to your audio file. This will override the Media Library selection.', 'bricks' ),
        ];
        
        $this->controls['cover_image'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Cover Image', 'bricks' ),
            'type'  => 'image',
        ];

        $this->controls['autoplay'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Autoplay', 'bricks' ),
            'type'  => 'checkbox',
            'default' => false,
        ];

        $this->controls['muted'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Muted', 'bricks' ),
            'type'  => 'checkbox',
            'default' => false,
        ];

        $this->controls['chapters'] = [
            'tab'           => 'content',
            'label'         => esc_html__( 'Audio Chapters', 'bricks' ),
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
            'units' => ['px'],
            'default' => '200px',
        ];

        $this->controls['player_max_width'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Max Width', 'bricks' ),
            'type'  => 'number',
            'units' => ['px', '%'],
            'default' => '100%',
        ];

        // Color controls
        $this->controls['player_background_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Player Background', 'bricks' ),
            'type'  => 'color',
            'default' => '#111827', // Default dark background
        ];

        $this->controls['primary_accent_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Primary Accent', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(239, 68, 68, 1)', // Raw RGBA default (red-500)
        ];

        $this->controls['text_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Text & Icons Color', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)', // Raw RGBA default
        ];

        // New control for button background color
        $this->controls['button_background_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Background', 'bricks' ),
            'type'  => 'color',
            'default' => 'transparent', // Default to transparent
        ];

        $this->controls['slider_track_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Slider Track', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 0.3)',
        ];

        $this->controls['chapter_dot_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Chapter Dot', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 1)',
        ];

        $this->controls['button_hover_background'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Hover BG', 'bricks' ),
            'type'  => 'color',
            'default' => 'rgba(255, 255, 255, 0.2)',
        ];
    }

    public function render() {
        $settings = $this->settings;

        if ( ! empty( $this->attributes['_root']['id'] ) ) {
            $root_id = $this->attributes['_root']['id'];
        } else {
            $root_id = 'snn-audio-player-' . uniqid();
            $this->set_attribute( '_root', 'id', $root_id );
        }

        $this->set_attribute( '_root', 'class', 'brxe-snn-audio-player snn-audio-player-wrapper' );

        // Get settings with defaults
        $audio_file_url = ! empty( $settings['audio_file']['url'] ) ? $settings['audio_file']['url'] : '';
        $manual_audio_url = ! empty( $settings['audio_url_manual'] ) ? $settings['audio_url_manual'] : '';
        $audio_url = ! empty( $manual_audio_url ) ? $manual_audio_url : $audio_file_url;
        
        $cover_image_url = ! empty( $settings['cover_image']['id'] ) ? wp_get_attachment_image_url( $settings['cover_image']['id'], 'medium' ) : '';
        if (empty($cover_image_url) && !empty($settings['cover_image']['url'])) {
            $cover_image_url = $settings['cover_image']['url'];
        }

        $chapters = $settings['chapters'] ?? [];

        $autoplay = ! empty( $settings['autoplay'] );
        $muted    = ! empty( $settings['muted'] );

        // Layout settings
        $player_height    = $settings['player_height'] ?? '200px';
        $player_max_width = $settings['player_max_width'] ?? '100%';

        // Color settings
        $player_bg_color     = $settings['player_background_color']['raw'] ?? $settings['player_background_color']['hex'] ?? '#111827';
        $accent_color        = $settings['primary_accent_color']['raw'] ?? $settings['primary_accent_color']['hex'] ?? '#ef4444';
        $text_color          = $settings['text_color']['raw'] ?? $settings['text_color']['hex'] ?? '#ffffff';
        $button_bg_color     = $settings['button_background_color']['raw'] ?? $settings['button_background_color']['hex'] ?? 'transparent'; // Get new button background color
        $slider_track        = $settings['slider_track_color']['raw'] ?? $settings['slider_track_color']['hex'] ?? 'rgba(255, 255, 255, 0.3)';
        $chapter_dot_color   = $settings['chapter_dot_color']['raw'] ?? $settings['chapter_dot_color']['hex'] ?? '#ffffff';
        $btn_hover_bg        = $settings['button_hover_background']['raw'] ?? $settings['button_hover_background']['hex'] ?? 'rgba(255, 255, 255, 0.2)';

        echo "<div {$this->render_attributes('_root')}>";

        echo "<style>
            /* Scoping all styles to the unique root ID */
            #" . esc_attr($root_id) . " { 
                --primary-accent-color: {$accent_color}; 
                --text-color: {$text_color}; 
                --button-background-color: {$button_bg_color}; /* New CSS variable */
                --slider-track-color: {$slider_track}; 
                --chapter-dot-color: {$chapter_dot_color}; 
                --button-hover-background: {$btn_hover_bg}; 
                --player-height: {$player_height}; 
                --player-max-width: {$player_max_width}; 
                --player-background-color: {$player_bg_color}; 
                width: 100%; 
                max-width: var(--player-max-width); 
                margin-left: auto; 
                margin-right: auto; 
            }
            #" . esc_attr($root_id) . " .snn-audio-container { 
                display: flex; 
                align-items: center; 
                background-color: var(--player-background-color); 
                overflow: hidden; 
                height: var(--player-height); 
                padding: 16px; 
                gap: 16px; 
            }
            #" . esc_attr($root_id) . " .snn-audio-cover-art { 
                width: calc(var(--player-height) - 32px); 
                height: calc(var(--player-height) - 32px); 
                object-fit: cover; 
                flex-shrink: 0; 
                border-radius: 4px; 
                " . (empty($cover_image_url) ? 'display: none;' : '') . "
            }
            #" . esc_attr($root_id) . " .snn-audio-controls-wrapper { 
                display: flex; 
                flex-direction: column; 
                justify-content: center; 
                width: 100%; 
                color: var(--text-color); 
            }
            #" . esc_attr($root_id) . " .snn-audio-progress-container { 
                position: relative; 
                margin-bottom: 8px; 
                width: 100%; 
            }
            #" . esc_attr($root_id) . " .snn-audio-progress-tooltip { 
                position: absolute; 
                background-color: var(--primary-accent-color); 
                color: var(--text-color); 
                font-size: 12px; 
                border-radius: 4px; 
                padding: 4px 8px; 
                top: -32px; 
                pointer-events: none; 
                opacity: 0; 
                transition: opacity 0.2s; 
                white-space: nowrap; 
                transform: translateX(-50%); 
            }
            #" . esc_attr($root_id) . " .snn-audio-chapter-dots-container { 
                position: absolute; 
                width: 100%; 
                height: 100%; 
                top: 0px; 
                left: 0px; 
                pointer-events: none; 
            }
            #" . esc_attr($root_id) . " .snn-audio-controls-bar { 
                display: flex; 
                align-items: center; 
                justify-content: space-between; 
            }
            #" . esc_attr($root_id) . " .snn-audio-controls-left, 
            #" . esc_attr($root_id) . " .snn-audio-controls-center, 
            #" . esc_attr($root_id) . " .snn-audio-controls-right { 
                display: flex; 
                align-items: center; 
                gap: 8px; 
            }
            #" . esc_attr($root_id) . " .snn-audio-control-button { 
                background: var(--button-background-color); /* Applied new variable */
                border: none; 
                color: var(--text-color); 
                padding: 8px; 
                border-radius: 9999px; 
                cursor: pointer; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                transition: background-color 0.2s; 
            }
            #" . esc_attr($root_id) . " .snn-audio-control-button:hover { 
                background-color: var(--button-hover-background); 
            }
            #" . esc_attr($root_id) . " .snn-audio-control-button svg { 
                width: 24px; 
                height: 24px; 
                fill: currentColor; 
            }
            /* Rotate rewind button icon */
            #" . esc_attr($root_id) . " .snn-rewind-btn svg {
                transform: rotate(180deg);
            }
            #" . esc_attr($root_id) . " .snn-play-pause-btn { 
                width: 56px; 
                height: 56px; 
            } 
            #" . esc_attr($root_id) . " .snn-play-pause-btn svg { 
                width: 100%; 
                height: 100%; 
            } 
            #" . esc_attr($root_id) . " .snn-volume-container { 
                display: flex; 
                align-items: center; 
            }
            #" . esc_attr($root_id) . " .snn-volume-slider { 
                width: 80px; 
                margin-left: 8px; 
            }
            #" . esc_attr($root_id) . " .snn-audio-slider { 
                -webkit-appearance: none; 
                appearance: none; 
                width: 100%; 
                height: 5px; 
                background: var(--slider-track-color); 
                cursor: pointer; 
                border-radius: 5px; 
                transition: height 0.2s ease; 
            }
            #" . esc_attr($root_id) . " .snn-audio-slider:hover { 
                height: 8px; 
            }
            #" . esc_attr($root_id) . " .snn-audio-slider::-webkit-slider-thumb { 
                -webkit-appearance: none; 
                appearance: none; 
                width: 16px; 
                height: 16px; 
                background: var(--primary-accent-color); 
                border-radius: 50%; 
                cursor: pointer; 
                border: 2px solid var(--text-color); 
                transition: transform 0.2s ease; 
            }
            #" . esc_attr($root_id) . " .snn-audio-slider:hover::-webkit-slider-thumb { 
                transform: scale(1.1); 
            }
            #" . esc_attr($root_id) . " .snn-audio-slider::-moz-range-thumb { 
                width: 16px; 
                height: 16px; 
                background: var(--primary-accent-color); 
                border-radius: 50%; 
                cursor: pointer; 
                border: 2px solid var(--text-color); 
            }
            #" . esc_attr($root_id) . " .snn-audio-chapter-dot { 
                position: absolute; 
                top: 60%; 
                transform: translate(-50%, -50%); 
                width: 10px; 
                height: 10px; 
                background: var(--chapter-dot-color); 
                border-radius: 50%; 
                cursor: pointer; 
                transition: transform 0.2s ease; 
            }
            #" . esc_attr($root_id) . " .snn-audio-chapter-dot:hover { 
                transform: translate(-50%, -50%) scale(1.5); 
            }
            #" . esc_attr($root_id) . " .snn-audio-chapter-tooltip { 
                position: absolute; 
                background-color: var(--primary-accent-color); 
                color: var(--text-color); 
                font-size: 12px; 
                border-radius: 4px; 
                padding: 4px 8px; 
                bottom: 20px; 
                pointer-events: none; 
                opacity: 0; 
                transition: opacity 0.2s; 
                white-space: nowrap; 
                transform: translateX(-50%); 
                z-index: 10; 
            }
            #" . esc_attr($root_id) . " .snn-time-display { 
                font-size: 14px; 
                min-width: 90px; 
                text-align: right; 
            }
        </style>";

        ?>
        <div class="snn-audio-container">
            <audio class="snn-audio" style="display:none;"
                <?php echo $autoplay ? 'autoplay' : ''; ?>
                <?php echo $muted ? 'muted' : ''; ?>
            >
                <?php if (!empty($audio_url)) : ?>
                    <source src="<?php echo esc_url( $audio_url ); ?>" type="audio/mpeg">
                <?php endif; ?>
                Your browser does not support the audio element.
            </audio>
            
            <?php if (!empty($cover_image_url)) : ?>
                <img src="<?php echo esc_url( $cover_image_url ); ?>" alt="Album Cover" class="snn-audio-cover-art">
            <?php endif; ?>

            <div class="snn-audio-controls-wrapper">
                <div class="snn-audio-progress-container">
                    <div class="snn-audio-progress-tooltip">00:00</div>
                    <input type="range" class="snn-audio-slider snn-audio-progress-bar" min="0" max="100" step="0.1" value="0">
                    <div class="snn-audio-chapter-dots-container"></div>
                    <div class="snn-audio-chapter-tooltip"></div>
                </div>

                <div class="snn-audio-controls-bar">
                    <div class="snn-audio-controls-left">
                            <div class="snn-volume-container">
                                <button class="snn-audio-control-button snn-mute-btn" aria-label="Mute/Unmute"></button>
                                <input type="range" class="snn-audio-slider snn-volume-slider" min="0" max="1" step="0.05" value="1" aria-label="Volume">
                            </div>
                    </div>
                    <div class="snn-audio-controls-center">
                        <button class="snn-audio-control-button snn-rewind-btn" aria-label="Rewind 10 seconds"></button>
                        <button class="snn-audio-control-button snn-play-pause-btn" aria-label="Play/Pause"></button>
                        <button class="snn-audio-control-button snn-forward-btn" aria-label="Forward 10 seconds"></button>
                    </div>
                    <div class="snn-audio-controls-right">
                        <div class="snn-time-display">00:00 / 00:00</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        ?>
        <script>
        // Global map to store player instances (audio element, config, and wrapper)
        // This ensures the global keydown listener can access specific player data.
        window.snnAudioPlayers = window.snnAudioPlayers || {};
        // Global variable to track the ID of the currently playing audio player.
        window.snnCurrentlyPlayingPlayerId = window.snnCurrentlyPlayingPlayerId || null;

        (() => {
            const playerWrapper = document.getElementById('<?php echo esc_js($root_id); ?>');
            // Prevent re-initialization if the script runs multiple times for the same element
            if (!playerWrapper || playerWrapper.dataset.snnAudioPlayerInitialized) {
                return;
            }
            playerWrapper.dataset.snnAudioPlayerInitialized = 'true';

            // --- CONFIGURATION & ELEMENTS ---
            const CONFIG = {
                CHAPTERS: <?php echo json_encode($chapters); ?>,
                KEY_SEEK_SECONDS: 5, // Default seek seconds for keyboard shortcuts
                REWIND_SECONDS: 10,  // Rewind button seconds
                FORWARD_SECONDS: 10, // Forward button seconds
                INITIAL_MUTED: <?php echo json_encode($muted); ?>,
            };

            const ICONS = {
                play: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 6v12l10-6z"></path></svg>`,
                pause: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>`,
                // Use the same forward icon, CSS will rotate it for rewind
                forward: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 6v12l8.5-6M13 6v12l8.5-6" transform="scale(1.2) translate(-2, -2)"></path></svg>`,
                volumeHigh: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"></path></svg>`,
                volumeMute: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"></path></svg>`,
            };

            const audio          = playerWrapper.querySelector('.snn-audio');
            const playPauseBtn   = playerWrapper.querySelector('.snn-play-pause-btn');
            const rewindBtn      = playerWrapper.querySelector('.snn-rewind-btn');
            const forwardBtn     = playerWrapper.querySelector('.snn-forward-btn');
            const muteBtn        = playerWrapper.querySelector('.snn-mute-btn');
            const volumeSlider   = playerWrapper.querySelector('.snn-volume-slider');
            const progressBar    = playerWrapper.querySelector('.snn-audio-progress-bar');
            const timeDisplay    = playerWrapper.querySelector('.snn-time-display');
            const chapterDotsContainer = playerWrapper.querySelector('.snn-audio-chapter-dots-container');
            const progressTooltip  = playerWrapper.querySelector('.snn-audio-progress-tooltip');
            const chapterTooltip   = playerWrapper.querySelector('.snn-audio-chapter-tooltip');

            // If essential elements are missing, stop initialization
            if (!audio || !playPauseBtn) return; 

            // Store this player's instance data in the global map
            window.snnAudioPlayers['<?php echo esc_js($root_id); ?>'] = {
                audio: audio,
                config: CONFIG,
                playerWrapper: playerWrapper
            };

            // Initialize lastVolume on the audio object itself for persistence
            audio.lastVolume = audio.volume;

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
                const progress = (bar.value - bar.min) / (bar.max - bar.min) * 100;
                let accentColor = getComputedStyle(playerWrapper).getPropertyValue('--primary-accent-color').trim();
                let trackColor = getComputedStyle(playerWrapper).getPropertyValue('--slider-track-color').trim();
                
                if (!accentColor) accentColor = '#ef4444'; 
                if (!trackColor) trackColor = 'rgba(255, 255, 255, 0.3)';

                bar.style.background = `linear-gradient(to right, ${accentColor} ${progress}%, ${trackColor} ${progress}%)`;
            };

            const togglePlayPause = () => {
                if (audio.paused || audio.ended) {
                    audio.play();
                } else {
                    audio.pause();
                }
            };

            const updatePlayPauseIcon = () => { 
                if(playPauseBtn) playPauseBtn.innerHTML = audio.paused ? ICONS.play : ICONS.pause; 
            };
            
            const updateMuteIcon = () => { 
                if(muteBtn) muteBtn.innerHTML = audio.muted || audio.volume === 0 ? ICONS.volumeMute : ICONS.volumeHigh; 
            };

            const toggleMute = () => {
                audio.muted = !audio.muted;
                if (audio.muted) {
                    if (audio.volume > 0) audio.lastVolume = audio.volume; // Store current volume before muting
                    audio.volume = 0;
                } else {
                    audio.volume = audio.lastVolume > 0 ? audio.lastVolume : 1; // Restore last volume or default to 1
                }
            };

            const updateProgress = () => {
                // Only update if not actively seeking to prevent jumpiness
                if (audio.dataset.isSeeking === 'true' || isNaN(audio.duration)) return; 
                if (progressBar) progressBar.value = (audio.currentTime / audio.duration) * 100;
                if (timeDisplay) timeDisplay.textContent = `${formatTime(audio.currentTime)} / ${formatTime(audio.duration || 0)}`;
                updateProgressBarFill(progressBar);
            };
            
            const generateChapters = () => {
                if (!chapterDotsContainer || isNaN(audio.duration) || !CONFIG.CHAPTERS) return;
                chapterDotsContainer.innerHTML = '';
                CONFIG.CHAPTERS.forEach(chapter => {
                    const seconds = timeToSeconds(chapter.time);
                    if (seconds > audio.duration) return; // Skip chapters beyond audio duration

                    const dot = document.createElement('div');
                    dot.className = 'snn-audio-chapter-dot';
                    dot.style.left = `${(seconds / audio.duration) * 100}%`;
                    dot.dataset.title = chapter.title;
                    dot.style.pointerEvents = 'auto'; // Make dots clickable

                    dot.addEventListener('click', e => {
                        e.stopPropagation(); // Prevent progress bar click event from firing
                        audio.currentTime = seconds;
                        audio.play();
                    });

                    dot.addEventListener('mouseenter', e => {
                        if (!chapterTooltip) return;
                        chapterTooltip.textContent = dot.dataset.title;
                        chapterTooltip.style.opacity = '1';
                        const dotRect = dot.getBoundingClientRect();
                        const containerRect = chapterDotsContainer.getBoundingClientRect();
                        // Position tooltip relative to the dot
                        chapterTooltip.style.left = `${dotRect.left - containerRect.left + (dotRect.width / 2)}px`;
                    });

                    dot.addEventListener('mouseleave', () => {
                        if (chapterTooltip) chapterTooltip.style.opacity = '0';
                    });

                    chapterDotsContainer.appendChild(dot);
                });
            };

            // --- EVENT LISTENERS (Player-specific) ---
            audio.addEventListener('play', () => {
                // Pause all other audio players when this one starts playing
                Object.keys(window.snnAudioPlayers).forEach(playerId => {
                    if (playerId !== '<?php echo esc_js($root_id); ?>') {
                        const otherPlayer = window.snnAudioPlayers[playerId];
                        if (otherPlayer && otherPlayer.audio && !otherPlayer.audio.paused) {
                            otherPlayer.audio.pause();
                        }
                    }
                });
                
                window.snnCurrentlyPlayingPlayerId = '<?php echo esc_js($root_id); ?>'; // Set this player as active
                updatePlayPauseIcon();
            });
            audio.addEventListener('pause', () => {
                updatePlayPauseIcon();
                // If this player was the active one, clear the active ID
                if (window.snnCurrentlyPlayingPlayerId === '<?php echo esc_js($root_id); ?>') {
                    window.snnCurrentlyPlayingPlayerId = null; 
                }
            });
            audio.addEventListener('ended', () => {
                updatePlayPauseIcon();
                if (window.snnCurrentlyPlayingPlayerId === '<?php echo esc_js($root_id); ?>') {
                    window.snnCurrentlyPlayingPlayerId = null; 
                }
            });
            audio.addEventListener('volumechange', () => {
                if (volumeSlider) volumeSlider.value = audio.volume;
                updateMuteIcon();
                updateProgressBarFill(volumeSlider);
            });
            audio.addEventListener('timeupdate', updateProgress);
            audio.addEventListener('loadedmetadata', () => {
                updateProgress();
                generateChapters();
                // Apply initial muted state after metadata is loaded
                if (CONFIG.INITIAL_MUTED) {
                    audio.muted = true;
                    audio.volume = 0;
                    if (volumeSlider) volumeSlider.value = 0;
                }
                updateMuteIcon();
                updateProgressBarFill(volumeSlider);
            });

            playPauseBtn?.addEventListener('click', (e) => { e.stopPropagation(); togglePlayPause(); });
            rewindBtn?.addEventListener('click', (e) => { e.stopPropagation(); audio.currentTime = Math.max(0, audio.currentTime - CONFIG.REWIND_SECONDS); });
            forwardBtn?.addEventListener('click', (e) => { e.stopPropagation(); audio.currentTime = Math.min(audio.duration, audio.currentTime + CONFIG.FORWARD_SECONDS); });
            muteBtn?.addEventListener('click', (e) => { e.stopPropagation(); toggleMute(); });
            
            volumeSlider?.addEventListener('input', () => {
                audio.muted = false; // Unmute if volume is adjusted
                audio.volume = volumeSlider.value;
            });

            progressBar?.addEventListener('input', e => {
                audio.dataset.isSeeking = 'true'; // Set flag to prevent timeupdate from overriding
                const scrubTime = (e.target.value / 100) * audio.duration;
                if(timeDisplay) timeDisplay.textContent = `${formatTime(scrubTime)} / ${formatTime(audio.duration)}`;
                updateProgressBarFill(progressBar);
            });
            progressBar?.addEventListener('change', e => {
                audio.dataset.isSeeking = 'false'; // Clear flag
                audio.currentTime = (e.target.value / 100) * audio.duration;
            });
            progressBar?.addEventListener('mousemove', e => {
                if (!progressTooltip || isNaN(audio.duration)) return;
                const rect = progressBar.getBoundingClientRect();
                const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                progressTooltip.style.left = `${percent * 100}%`;
                progressTooltip.textContent = formatTime(percent * audio.duration);
                progressTooltip.style.opacity = '1';
            });
            progressBar?.addEventListener('mouseleave', () => { if(progressTooltip) progressTooltip.style.opacity = '0'; });

            // --- INITIALIZATION ---
            // Assign the forward icon to both forward and rewind buttons.
            // CSS will handle the rotation for rewind.
            if (rewindBtn) rewindBtn.innerHTML = ICONS.forward; 
            if (forwardBtn) forwardBtn.innerHTML = ICONS.forward;
            updatePlayPauseIcon();
            updateMuteIcon();
            updateProgressBarFill(progressBar);
            updateProgressBarFill(volumeSlider);
        })();

        // --- GLOBAL KEYDOWN LISTENER (Added only once) ---
        // This listener will control only the currently playing audio player.
        if (!window.snnAudioPlayerGlobalKeyListenerAdded) {
            document.addEventListener('keydown', (e) => {
                // Ignore key events if an input, textarea, or editable element is focused
                const activeEl = document.activeElement;
                if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable)) {
                    return; 
                }

                // Only proceed if there's an actively playing audio player
                if (window.snnCurrentlyPlayingPlayerId && window.snnAudioPlayers[window.snnCurrentlyPlayingPlayerId]) {
                    const activePlayerInstance = window.snnAudioPlayers[window.snnCurrentlyPlayingPlayerId];
                    const activeAudio = activePlayerInstance.audio;
                    const activeConfig = activePlayerInstance.config;

                    if (!activeAudio || !activeConfig) return;

                    switch (e.key.toLowerCase()) {
                        case ' ': 
                        case 'k': 
                            e.preventDefault(); 
                            if (activeAudio.paused || activeAudio.ended) {
                                activeAudio.play();
                            } else {
                                activeAudio.pause();
                            }
                            break;
                        case 'm': 
                            e.preventDefault(); 
                            // Toggle mute for the active player
                            activeAudio.muted = !activeAudio.muted;
                            if (activeAudio.muted) {
                                if (activeAudio.volume > 0) activeAudio.lastVolume = activeAudio.volume; // Store last volume
                                activeAudio.volume = 0;
                            } else {
                                activeAudio.volume = activeAudio.lastVolume > 0 ? activeAudio.lastVolume : 1; // Restore volume
                            }
                            // Manually dispatch 'volumechange' to trigger the active player's UI update
                            activeAudio.dispatchEvent(new Event('volumechange'));
                            break;
                        case 'arrowright': 
                            e.preventDefault(); 
                            activeAudio.currentTime = Math.min(activeAudio.duration, activeAudio.currentTime + activeConfig.KEY_SEEK_SECONDS); 
                            break;
                        case 'arrowleft': 
                            e.preventDefault(); 
                            activeAudio.currentTime = Math.max(0, activeAudio.currentTime - activeConfig.KEY_SEEK_SECONDS); 
                            break;
                    }
                }
            });
            window.snnAudioPlayerGlobalKeyListenerAdded = true; // Set flag to prevent multiple additions
        }
        </script>
        <?php
        echo "</div>"; // End of root element
    }
}