<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Element;

if ( ! function_exists( 'bricks_render_icon' ) ) {
    function bricks_render_icon( $icon_settings, $classes = [] ) {
        if ( empty( $icon_settings ) || ! is_array( $icon_settings ) || empty( $icon_settings['icon'] ) ) {
            return;
        }
        $icon_class   = $icon_settings['icon'];
        $class_string = implode( ' ', $classes );
        echo '<i class="' . esc_attr( $icon_class . ' ' . $class_string ) . '"></i>';
    }
}

if ( ! class_exists( 'Like_Button_Element' ) ) {
    class Like_Button_Element extends Element {
        public $category = 'snn';
        public $name     = 'like_button';
        public $icon     = 'ti-heart';
        public $scripts  = [];
        public $nestable = false;

        public function get_label() {
            return esc_html__( 'Like Button', 'bricks' );
        }

        public function set_controls() {
            $this->controls['button_icon'] = [
                'tab'     => 'content',
                'type'    => 'icon',
                'label'   => esc_html__( 'Button Icon', 'bricks' ),
                'default' => [ 'library' => 'themify', 'icon' => 'ti-heart' ],
            ];

            $this->controls['liked_icon'] = [
                'tab'     => 'content',
                'type'    => 'icon',
                'label'   => esc_html__( 'Liked Icon', 'bricks' ),
                'default' => [ 'library' => 'themify', 'icon' => 'ti-heart-filled' ],
            ];

            $this->controls['show_like_count'] = [
                'tab'     => 'content',
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Show Like Count', 'bricks' ),
                'inline'  => true,
                'small'   => true,
                'default' => true,
            ];

            $this->controls['identifier'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Custom Identifier', 'bricks' ),
                'default'     => '',
                'description' => esc_html__( 'Leave blank to use the current post ID.', 'bricks' ),
            ];
        }

        public function render() {
            $icon_settings  = $this->settings['button_icon'] ?? [];
            $liked_icon_set = $this->settings['liked_icon'] ?? [];

            // Retrieve the custom identifier as provided.
            $custom_identifier = $this->settings['identifier'] ?? '';
            if ( is_array( $custom_identifier ) && isset( $custom_identifier['raw'] ) ) {
                $custom_identifier = $custom_identifier['raw'];
            }
            
            // Use the custom identifier if available; otherwise, use the current post ID.
            $identifier = ! empty( $custom_identifier ) ? $custom_identifier : get_the_ID();

            // Retrieve like count and check if the current user's IP has already liked.
            $like_count = snn_get_like_count( $identifier );
            $liked_ips  = snn_get_like_ips( $identifier );
            $liked      = in_array( $_SERVER['REMOTE_ADDR'], $liked_ips, true );

            // Set attributes on the root element.
            $this->set_attribute( '_root', 'class', [ 'brxe-like-button', 'like-button-element' ] );
            $this->set_attribute( '_root', 'data-identifier', $identifier );
            $this->set_attribute( '_root', 'data-count', $like_count );

            // Render the like button element.
            echo '<div ' . $this->render_attributes( '_root' ) . ' onclick="snn_likeButton(this)">';
                echo '<span class="button-icon default-icon" style="' . ( $liked ? 'display:none;' : 'display:inline;' ) . '">';
                    bricks_render_icon( $icon_settings );
                echo '</span>';
                echo '<span class="button-icon liked-icon" style="' . ( $liked ? 'display:inline;' : 'display:none;' ) . '">';
                    bricks_render_icon( $liked_icon_set );
                echo '</span>';
                if ( ! empty( $this->settings['show_like_count'] ) ) {
                    echo '<span class="snn-like-count">' . intval( $like_count ) . '</span>';
                }
                // Hidden input for JavaScript.
                echo '<input type="hidden" class="like-button-identifier" value="' . $identifier . '">';
            echo '</div>';
        }
    }
}

if ( function_exists( 'bricks' ) ) {
    bricks()->elements->register_element( new Like_Button_Element() );
}


function snn_get_like_ips( $identifier ) {
    $key = sanitize_key( 'snn_like_ips_' . $identifier );
    $ips = get_option( $key, [] );
    return is_array( $ips ) ? $ips : [];
}


function snn_update_like_ips( $identifier, $ips ) {
    $key = sanitize_key( 'snn_like_ips_' . $identifier );
    update_option( $key, $ips );
}


function snn_get_like_count( $identifier ) {
    $ips = snn_get_like_ips( $identifier );
    return count( $ips );
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'snn/v1', '/like', [
        'methods'             => 'POST',
        'callback'            => 'snn_handle_like',
        'permission_callback' => '__return_true',
    ] );
} );

function snn_handle_like( WP_REST_Request $request ) {
    $identifier = $request->get_param( 'identifier' ) ?? '';
    if ( is_array( $identifier ) && isset( $identifier['raw'] ) ) {
        $identifier = $identifier['raw'];
    }

    if ( empty( $identifier ) ) {
        return new WP_Error( 'no_identifier', 'No identifier provided', [ 'status' => 400 ] );
    }

    $user_ip = $_SERVER['REMOTE_ADDR'];
    $ips     = snn_get_like_ips( $identifier );

    if ( in_array( $user_ip, $ips, true ) ) {
        // Remove the IP if already liked.
        $ips   = array_diff( $ips, [ $user_ip ] );
        $liked = false;
    } else {
        // Add the IP to the array.
        $ips[] = $user_ip;
        $liked = true;
    }
    // Re-index the array.
    $ips = array_values( $ips );
    snn_update_like_ips( $identifier, $ips );

    return rest_ensure_response( [ 'count' => count( $ips ), 'liked' => $liked ] );
}


add_action( 'wp_footer', 'snn_inline_like_script' );
function snn_inline_like_script() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize like buttons on page load
            const likeButtons = document.querySelectorAll('.brxe-like-button');
            likeButtons.forEach(function(button) {
                const identifier = button.getAttribute('data-identifier');
                if (identifier) {
                    // Check localStorage to see if this content was liked
                    const isLiked = localStorage.getItem('snn_liked_' + identifier) === 'true';
                    const serverCount = parseInt(button.getAttribute('data-count') || '0', 10);
                    const wasLikedByIP = button.querySelector('.liked-icon').style.display === 'inline';
                    const defaultIcon = button.querySelector('.default-icon');
                    const likedIcon = button.querySelector('.liked-icon');
                    const countSpan = button.querySelector('.snn-like-count');
                    
                    // Logic to handle count correction based on localStorage state
                    let adjustedCount = serverCount;
                    
                    // If liked in localStorage but not counted in server (user liked on another device but not this one)
                    if (isLiked && !wasLikedByIP) {
                        // We need to get the stored count value if available
                        const storedCount = localStorage.getItem('snn_like_count_' + identifier);
                        if (storedCount) {
                            adjustedCount = parseInt(storedCount, 10);
                        }
                    }
                    
                    // Update the UI
                    if (defaultIcon && likedIcon) {
                        if (isLiked) {
                            defaultIcon.style.display = 'none';
                            likedIcon.style.display = 'inline';
                        } else {
                            defaultIcon.style.display = 'inline';
                            likedIcon.style.display = 'none';
                        }
                    }
                    
                    // Update count display
                    if (countSpan) {
                        countSpan.textContent = adjustedCount;
                    }
                }
            });
        });

        function snn_likeButton(el) {
            var identifier = el.getAttribute('data-identifier');
            if (el.getAttribute('data-processing') === 'true') {
                return;
            }
            el.setAttribute('data-processing', 'true');

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "<?php echo esc_url( rest_url( 'snn/v1/like' ) ); ?>", true);
            xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var response = JSON.parse(xhr.responseText);
                    var defaultIcon = el.querySelector('.default-icon');
                    var likedIcon   = el.querySelector('.liked-icon');
                    if (defaultIcon && likedIcon) {
                        if (response.liked) {
                            defaultIcon.style.display = 'none';
                            likedIcon.style.display   = 'inline';
                            // Save liked state to localStorage
                            localStorage.setItem('snn_liked_' + identifier, 'true');
                            // Save count to localStorage
                            localStorage.setItem('snn_like_count_' + identifier, response.count);
                        } else {
                            defaultIcon.style.display = 'inline';
                            likedIcon.style.display   = 'none';
                            // Remove liked state from localStorage
                            localStorage.removeItem('snn_liked_' + identifier);
                            // Save updated count to localStorage
                            localStorage.setItem('snn_like_count_' + identifier, response.count);
                        }
                    }
                    var countSpan = el.querySelector('.snn-like-count');
                    if (countSpan) {
                        countSpan.textContent = response.count;
                    }
                }
                el.removeAttribute('data-processing');
            };
            xhr.onerror = function() {
                el.removeAttribute('data-processing');
            };
            xhr.send(JSON.stringify({ identifier: identifier }));
        }
    </script>
    <?php
}


add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'snn/v1/like' ) !== false ) {
        return true;
    }
    return $result;
}, 99 );
?>