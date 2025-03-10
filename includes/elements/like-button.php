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
                'description' => esc_html__( 'Leave blank to use current post ID.', 'bricks' ),
            ];
        }

        public function render() {
            $icon_settings  = $this->settings['button_icon'] ?? [];
            $liked_icon_set = $this->settings['liked_icon'] ?? [];

            $custom_identifier = $this->settings['identifier'] ?? '';
            if ( is_array( $custom_identifier ) && isset( $custom_identifier['raw'] ) ) {
                $custom_identifier = $custom_identifier['raw'];
            }
            $identifier = ! empty( $custom_identifier ) ? $custom_identifier : get_the_ID();

            $like_count = snn_get_like_count( $identifier );
            $liked      = snn_has_user_liked( $identifier );

            $this->set_attribute( '_root', 'class', [ 'brxe-like-button', 'like-button-element' ] );
            $this->set_attribute( '_root', 'data-identifier', $identifier );
            $this->set_attribute( '_root', 'data-count', $like_count );
            $this->set_attribute( '_root', 'data-liked', $liked ? 'true' : 'false' );

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
            echo '</div>';
        }
    }
}

if ( function_exists( 'bricks' ) ) {
    bricks()->elements->register_element( new Like_Button_Element() );
}

// Ensure we get a consistent IP address
function snn_get_real_ip() {
    // Check for various server headers that might contain the real IP
    $ip_headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            // Validate IP format
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR']; // Default fallback
}

function snn_get_user_identifier() {
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    } else {
        // Use real IP address with improved detection
        return 'ip_' . snn_get_real_ip();
    }
}

function snn_get_likes_data( $identifier ) {
    $key = sanitize_key( 'snn_likes_data_' . $identifier );
    $data = get_option( $key, [] );
    
    // Make sure it's an array
    if (!is_array($data)) {
        $data = [];
        snn_update_likes_data($identifier, $data);
    }
    
    return $data;
}

function snn_update_likes_data( $identifier, $data ) {
    $key = sanitize_key( 'snn_likes_data_' . $identifier );
    
    // Ensure the data is properly formatted
    if (!is_array($data)) {
        $data = [];
    }
    
    // Remove any duplicates
    $data = array_unique($data);
    
    // Use autoload = no for better performance
    update_option( $key, $data, 'no' );
}

function snn_get_like_count( $identifier ) {
    $likes = snn_get_likes_data( $identifier );
    return count( $likes );
}

function snn_has_user_liked( $identifier ) {
    $user_identifier = snn_get_user_identifier();
    $likes = snn_get_likes_data( $identifier );
    return in_array( $user_identifier, $likes, true );
}

// Get all likes for the current user's IP
function snn_get_user_likes() {
    global $wpdb;
    $user_identifier = snn_get_user_identifier();
    $likes = [];
    
    // Get all like data from database
    $like_options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'snn_likes_data_%'"
    );
    
    foreach ($like_options as $option) {
        $data = maybe_unserialize($option->option_value);
        if (is_array($data) && in_array($user_identifier, $data, true)) {
            // Extract identifier from option name
            $identifier = str_replace('snn_likes_data_', '', $option->option_name);
            $likes[] = $identifier;
        }
    }
    
    return $likes;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'snn/v1', '/like', [
        'methods'             => 'POST',
        'callback'            => 'snn_handle_like',
        'permission_callback' => '__return_true',
    ] );
    
    // Add endpoint to get likes
    register_rest_route( 'snn/v1', '/get-likes', [
        'methods'             => 'GET',
        'callback'            => 'snn_get_likes_endpoint',
        'permission_callback' => '__return_true',
    ] );
} );

function snn_get_likes_endpoint( WP_REST_Request $request ) {
    // Get identifier from request
    $identifier = sanitize_text_field( $request->get_param( 'identifier' ) );
    
    if (!empty($identifier)) {
        // Return data for specific identifier
        $like_count = snn_get_like_count( $identifier );
        $liked = snn_has_user_liked( $identifier );
        
        return rest_ensure_response([
            'count' => $like_count,
            'liked' => $liked,
        ]);
    } else {
        // Return all user likes if no identifier specified
        return rest_ensure_response([
            'userLikes' => snn_get_user_likes(),
        ]);
    }
}

function snn_handle_like( WP_REST_Request $request ) {
    $identifier = sanitize_text_field( $request->get_param( 'identifier' ) );

    if ( empty( $identifier ) ) {
        return new WP_Error( 'no_identifier', 'No identifier provided', [ 'status' => 400 ] );
    }

    $user_identifier = snn_get_user_identifier();
    $likes_data = snn_get_likes_data( $identifier );

    if ( in_array( $user_identifier, $likes_data, true ) ) {
        $likes_data = array_values( array_diff( $likes_data, [ $user_identifier ] ) );
        $liked = false;
    } else {
        $likes_data[] = $user_identifier;
        $liked = true;
    }

    snn_update_likes_data( $identifier, $likes_data );
    
    return rest_ensure_response([
        'count' => count( $likes_data ),
        'liked' => $liked,
    ]);
}

add_action('rest_authentication_errors', function ( $result ) {
    if ( strpos( $_SERVER['REQUEST_URI'], 'snn/v1/' ) !== false ) {
        return true;
    }
    return $result;
}, 99);

add_action( 'wp_footer', function () { ?>
<script>
// Initialize on page load - fetch status for all buttons
document.addEventListener('DOMContentLoaded', function() {
    // For each like button on the page, check its current status
    document.querySelectorAll('.brxe-like-button').forEach(function(button) {
        const identifier = button.getAttribute('data-identifier');
        if (!identifier) return;
        
        // Fetch current status from server
        fetch('<?php echo rest_url('snn/v1/get-likes'); ?>?identifier=' + encodeURIComponent(identifier))
            .then(response => response.json())
            .then(data => {
                // Update UI
                button.querySelector('.default-icon').style.display = data.liked ? 'none' : 'inline';
                button.querySelector('.liked-icon').style.display = data.liked ? 'inline' : 'none';
                button.setAttribute('data-liked', data.liked ? 'true' : 'false');
                
                // Update like count
                const countElement = button.querySelector('.snn-like-count');
                if (countElement) {
                    countElement.textContent = data.count;
                }
            })
            .catch(error => console.error('Error checking like status:', error));
    });
});

function snn_likeButton(el) {
    const identifier = el.getAttribute('data-identifier');
    
    // Show loading state
    el.classList.add('snn-loading');
    
    fetch('<?php echo rest_url('snn/v1/like'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ identifier: identifier })
    })
    .then(response => response.json())
    .then(data => {
        // Update UI
        el.querySelector('.default-icon').style.display = data.liked ? 'none' : 'inline';
        el.querySelector('.liked-icon').style.display = data.liked ? 'inline' : 'none';
        el.setAttribute('data-liked', data.liked ? 'true' : 'false');
        
        // Update like count
        const countElement = el.querySelector('.snn-like-count');
        if (countElement) {
            countElement.textContent = data.count;
        }
        
        // Remove loading state
        el.classList.remove('snn-loading');
    })
    .catch(error => {
        console.error('Error handling like:', error);
        el.classList.remove('snn-loading');
    });
}
</script>

<style>
.brxe-like-button.snn-loading {
    opacity: 0.7;
    pointer-events: none;
}
</style>
<?php });
?>