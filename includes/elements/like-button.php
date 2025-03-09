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
                'description' => esc_html__( 'Leave blank to use element ID.', 'bricks' ),
            ];
        }

        public function render() {
            $icon_settings  = $this->settings['button_icon'] ?? [];
            $liked_icon_set = $this->settings['liked_icon'] ?? [];

            // Get the custom identifier exactly as provided
            $custom_identifier = $this->settings['identifier'] ?? '';
            if ( is_array( $custom_identifier ) && isset( $custom_identifier['raw'] ) ) {
                $custom_identifier = $custom_identifier['raw'];
            }
            
            // Use the custom identifier if available; otherwise, use the persistent Bricks element ID.
            $identifier = ! empty( $custom_identifier ) ? $custom_identifier : 'like-button-' . $this->id;

            // Get the like count and check if the current user has liked this item.
            $like_count = function_exists( 'snn_get_like_count' ) ? snn_get_like_count( $identifier ) : 0;
            $post       = snn_get_like_post( $identifier );
            $liked      = false;
            if ( $post ) {
                $liked_ips = get_post_meta( $post->ID, '_snn_like_ips', true );
                $liked     = is_array( $liked_ips ) && in_array( $_SERVER['REMOTE_ADDR'], $liked_ips, true );
            }

            // Set attributes on the root element.
            $this->set_attribute( '_root', 'class', [ 'brxe-like-button', 'like-button-element' ] );
            $this->set_attribute( '_root', 'data-identifier', $identifier );

            // Render the element.
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
                // Include a hidden input with the identifier for use in JavaScript.
                echo '<input type="hidden" class="like-button-identifier" value="' . $identifier . '">';
            echo '</div>';
        }
    }
}

// Register the Like Button element if Bricks is available.
if ( function_exists( 'bricks' ) ) {
    bricks()->elements->register_element( new Like_Button_Element() );
}

/**
 * Register Custom Post Type for Storing Likes.
 */
add_action( 'init', 'snn_register_like_post_type' );
function snn_register_like_post_type() {
    $args = [
        'public'          => false,
        'show_ui'         => false,
        'capability_type' => 'post',
        'supports'        => [ 'title' ],
    ];
    register_post_type( 'snn_like', $args );
}

/**
 * Helper function to retrieve or create a like record.
 */
function snn_get_like_post( $identifier ) {
    if ( ! is_string( $identifier ) ) {
        $identifier = '';
    }
    $post = get_page_by_path( $identifier, OBJECT, 'snn_like' );
    if ( ! $post ) {
        $post_id = wp_insert_post( [
            'post_title'  => $identifier,
            'post_name'   => $identifier,
            'post_type'   => 'snn_like',
            'post_status' => 'private',
        ] );
        if ( ! is_wp_error( $post_id ) ) {
            $post = get_post( $post_id );
            update_post_meta( $post->ID, '_snn_like_count', 0 );
            update_post_meta( $post->ID, '_snn_like_ips', [] );
        }
    }
    return $post;
}

/**
 * Helper function to get the like count.
 */
function snn_get_like_count( $identifier ) {
    $post  = snn_get_like_post( $identifier );
    return intval( get_post_meta( $post->ID, '_snn_like_count', true ) );
}

/**
 * REST API Endpoint to Handle Like Requests (Toggle Behavior).
 */
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
    $post    = snn_get_like_post( $identifier );
    if ( ! $post ) {
        return new WP_Error( 'post_error', 'Could not create like record', [ 'status' => 500 ] );
    }

    $ips = get_post_meta( $post->ID, '_snn_like_ips', true );
    if ( ! is_array( $ips ) ) {
        $ips = [];
    }

    if ( in_array( $user_ip, $ips, true ) ) {
        // User already liked; remove the like.
        $ips   = array_diff( $ips, [ $user_ip ] );
        $liked = false;
    } else {
        // Add the like.
        $ips[] = $user_ip;
        $liked = true;
    }
    $ips = array_values( $ips );
    update_post_meta( $post->ID, '_snn_like_ips', $ips );
    update_post_meta( $post->ID, '_snn_like_count', count( $ips ) );

    return rest_ensure_response( [ 'count' => count( $ips ), 'liked' => $liked ] );
}

/**
 * Inline JavaScript for AJAX Handling.
 */
add_action( 'wp_footer', 'snn_inline_like_script' );
function snn_inline_like_script() {
    ?>
    <script type="text/javascript">
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
                        } else {
                            defaultIcon.style.display = 'inline';
                            likedIcon.style.display   = 'none';
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
?>
