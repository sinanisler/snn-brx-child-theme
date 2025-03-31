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
                'default' => [ 'library' => 'fontawesomeRegular', 'icon' => 'fa fa-heart' ],
            ];

            $this->controls['liked_icon'] = [
                'tab'     => 'content',
                'type'    => 'icon',
                'label'   => esc_html__( 'Liked Icon', 'bricks' ),
                'default' => [ 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-heart' ],
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
                'description' => 'Leave blank to use current post ID. <br><br>',
            ];

            // New settings for custom balloon texts
            $this->controls['balloon_text_like'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Balloon Text (Like)', 'bricks' ),
                'default'     => esc_html__( 'Like', 'bricks' ),
                'placeholder' => esc_html__( 'Like', 'bricks' ),
            ];

            $this->controls['balloon_text_unlike'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Balloon Text (Unlike)', 'bricks' ),
                'default'     => esc_html__( 'Unlike', 'bricks' ),
                'placeholder' => esc_html__( 'Unlike', 'bricks' ),
                'description' => '',
            ];

            $this->controls['balloon_text_login_to_like'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Balloon Text (Logged Only)', 'bricks' ),
                'default'     => esc_html__( 'Login to Like', 'bricks' ),
                'placeholder' => esc_html__( 'Login to Like', 'bricks' ),
                'description' => '<br><br>',
            ];

            // New setting: Logged User Only
            $this->controls['logged_user_only'] = [
                'tab'     => 'content',
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Logged User Only', 'bricks' ),
                'inline'  => true,
                'small'   => true,
                'default' => false,
                'description' => "
<p data-control='info' style='line-height:1'>
When this feature is enabled likes are stored within the post meta and user meta.<br>
- post meta: _snn_liked_by = array( userID, userID, ... )<br>
- user meta:  _snn_liked_posts = array( postID, postID, ... )<br>
- For example for query: Meta Key: _snn_liked_by and Compare: LIKE<br><br>
After that we can get the custom field and count the array to get total likes for the post<br><br>
\$like_count = get_post_meta( get_the_ID(), '_snn_liked_by', true );<br>
echo count( \$like_count );
</p>
                ",
            ];
        }

        public function render() {
            $icon_settings      = $this->settings['button_icon'] ?? [];
            $liked_icon_set     = $this->settings['liked_icon'] ?? [];
            $custom_identifier  = $this->settings['identifier'] ?? '';

            if ( is_array( $custom_identifier ) && isset( $custom_identifier['raw'] ) ) {
                $custom_identifier = $custom_identifier['raw'];
            }
            $identifier = ! empty( $custom_identifier ) ? $custom_identifier : get_the_ID();

            // Retrieve the "Logged User Only" setting
            $logged_user_only = ! empty( $this->settings['logged_user_only'] );

            $like_count = snn_get_like_count( $identifier, $logged_user_only );
            $liked      = snn_has_user_liked( $identifier, $logged_user_only );

            // Retrieve balloon text settings
            $balloon_text_like   = ! empty( $this->settings['balloon_text_like'] ) ? $this->settings['balloon_text_like'] : esc_html__( 'Click to Like', 'bricks' );
            $balloon_text_unlike = ! empty( $this->settings['balloon_text_unlike'] ) ? $this->settings['balloon_text_unlike'] : esc_html__( 'Click to Unlike', 'bricks' );
            $balloon_text_login  = ! empty( $this->settings['balloon_text_login_to_like'] ) ? $this->settings['balloon_text_login_to_like'] : esc_html__( 'Login to Like', 'bricks' );

            // Set balloon text based on state:
            // If "Logged User Only" is enabled and the user is not logged in, use the login text.
            if ( $logged_user_only && ! is_user_logged_in() ) {
                $balloon_text = $balloon_text_login;
            } else {
                $balloon_text = $liked ? $balloon_text_unlike : $balloon_text_like;
            }

            $this->set_attribute( '_root', 'class', [ 'brxe-like-button', 'like-button-element' ] );
            $this->set_attribute( '_root', 'data-identifier', $identifier );
            $this->set_attribute( '_root', 'data-count', $like_count );
            $this->set_attribute( '_root', 'data-liked', $liked ? 'true' : 'false' );
            $this->set_attribute( '_root', 'data-logged-only', $logged_user_only ? 'true' : 'false' );

            // Set balloon text attributes
            $this->set_attribute( '_root', 'data-balloon', $balloon_text );
            $this->set_attribute( '_root', 'data-balloon-pos', 'top' );
            $this->set_attribute( '_root', 'data-balloon-text-like', $balloon_text_like );
            $this->set_attribute( '_root', 'data-balloon-text-unlike', $balloon_text_unlike );
            $this->set_attribute( '_root', 'data-balloon-text-login-to-like', $balloon_text_login );

            echo '<div ' . $this->render_attributes( '_root' ) . ' onclick="snn_likeButton(this)" style="cursor:pointer;">';
                echo '<span class="button-icon default-icon" style="' . ( $liked ? 'display:none;' : 'display:inline;' ) . '">';
                    bricks_render_icon( $icon_settings );
                echo '</span>';
                echo '<span class="button-icon liked-icon" style="' . ( $liked ? 'display:inline;' : 'display:none;' ) . '">';
                    bricks_render_icon( $liked_icon_set );
                echo '</span>';
                if ( ! empty( $this->settings['show_like_count'] ) ) {
                    echo '<span class="snn-like-count" style="margin-left:1rem">' . intval( $like_count ) . '</span>';
                }
            echo '</div>';
        }
    }
}

if ( function_exists( 'bricks' ) ) {
    bricks()->elements->register_element( new Like_Button_Element() );
}

/**
 * TOKEN HANDLING FUNCTIONS
 */

// Hook early to ensure the token is set before headers are sent.
add_action('init', 'snn_init_dynamic_token');
function snn_init_dynamic_token() {
    snn_get_or_create_token();
}

function snn_get_or_create_token() {
    // If token does not exist in cookie, create one.
    if ( ! isset( $_COOKIE['snn_dynamic_token'] ) ) {
        $token = bin2hex( random_bytes( 16 ) ); // 32-char hex token.
        if ( ! headers_sent() ) {
            // For localhost, use '/' for path.
            setcookie( 'snn_dynamic_token', $token, time() + 3600, '/' );
        }
        set_transient( 'snn_token_' . $token, true, 3600 );
        return $token;
    }
    $token = sanitize_text_field( $_COOKIE['snn_dynamic_token'] );
    // Refresh token if expired.
    if ( ! get_transient( 'snn_token_' . $token ) ) {
        $token = bin2hex( random_bytes( 16 ) );
        if ( ! headers_sent() ) {
            setcookie( 'snn_dynamic_token', $token, time() + 3600, '/' );
        }
        set_transient( 'snn_token_' . $token, true, 3600 );
    }
    return $token;
}

// Note: The token validation function is no longer used.
function snn_validate_token( $token ) {
    return true;
}

/**
 * REAL IP RETRIEVAL
 */
function snn_get_real_ip() {
    $ip_headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ( $ip_headers as $header ) {
        if ( ! empty( $_SERVER[ $header ] ) ) {
            $ips = explode( ',', $_SERVER[ $header ] );
            $ip = trim( $ips[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'];
}

/**
 * ===============
 * OLD STORAGE (IP-based) for fallback if "Logged User Only" is NOT enabled
 * ===============
 */
function snn_get_likes_data_legacy( $identifier ) {
    $key = sanitize_key( 'snn_likes_data_' . $identifier );
    $data = get_option( $key, [] );
    
    if ( ! is_array( $data ) ) {
        $data = [];
        snn_update_likes_data_legacy( $identifier, $data );
    }
    return $data;
}
function snn_update_likes_data_legacy( $identifier, $data ) {
    $key = sanitize_key( 'snn_likes_data_' . $identifier );
    if ( ! is_array( $data ) ) {
        $data = [];
    }
    $data = array_unique( $data );
    update_option( $key, $data, 'no' );
}
function snn_get_like_count_legacy( $identifier ) {
    $likes = snn_get_likes_data_legacy( $identifier );
    return count( $likes );
}
function snn_has_user_liked_legacy( $identifier ) {
    $user_identifier = snn_get_user_identifier();
    $likes = snn_get_likes_data_legacy( $identifier );
    return in_array( $user_identifier, $likes, true );
}

/**
 * ===============
 * NEW STORAGE (User-based) for "Logged User Only" = true
 * ===============
 */
function snn_get_likes_data_user_based( $post_id ) {
    $liked_by = get_post_meta( $post_id, '_snn_liked_by', true );
    if ( ! is_array( $liked_by ) ) {
        $liked_by = [];
    }
    return $liked_by;
}
function snn_get_like_count_user_based( $post_id ) {
    $liked_by = snn_get_likes_data_user_based( $post_id );
    return count( $liked_by );
}
function snn_user_has_liked_post( $post_id, $user_id ) {
    $liked_by = snn_get_likes_data_user_based( $post_id );
    return in_array( $user_id, $liked_by, true );
}
function snn_update_post_like( $post_id, $user_id, $is_liking = true ) {
    $liked_by = snn_get_likes_data_user_based( $post_id );
    $liked_by = array_map( 'intval', $liked_by );

    if ( $is_liking ) {
        if ( ! in_array( $user_id, $liked_by, true ) ) {
            $liked_by[] = $user_id;
        }
    } else {
        $liked_by = array_diff( $liked_by, [ $user_id ] );
    }
    $liked_by = array_unique( $liked_by );
    update_post_meta( $post_id, '_snn_liked_by', $liked_by );
}
function snn_update_user_like( $user_id, $post_id, $is_liking = true ) {
    $liked_posts = get_user_meta( $user_id, '_snn_liked_posts', true );
    if ( ! is_array( $liked_posts ) ) {
        $liked_posts = [];
    }
    $liked_posts = array_map( 'intval', $liked_posts );

    if ( $is_liking ) {
        if ( ! in_array( $post_id, $liked_posts, true ) ) {
            $liked_posts[] = $post_id;
        }
    } else {
        $liked_posts = array_diff( $liked_posts, [ $post_id ] );
    }
    $liked_posts = array_unique( $liked_posts );
    update_user_meta( $user_id, '_snn_liked_posts', $liked_posts );
}

/**
 * ===============
 * SHARED WRAPPERS
 * ===============
 */
function snn_get_user_identifier() {
    if ( is_user_logged_in() ) {
        return 'user_' . get_current_user_id();
    } else {
        return 'ip_' . snn_get_real_ip();
    }
}

/**
 * Main get_like_count function that checks if "logged_user_only" is set
 */
function snn_get_like_count( $identifier, $logged_user_only = false ) {
    if ( $logged_user_only ) {
        $post_id = absint( $identifier );
        if ( $post_id < 1 ) {
            return 0;
        }
        return snn_get_like_count_user_based( $post_id );
    } else {
        return snn_get_like_count_legacy( $identifier );
    }
}

/**
 * Main has_user_liked function that checks if "logged_user_only" is set
 */
function snn_has_user_liked( $identifier, $logged_user_only = false ) {
    if ( $logged_user_only ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $post_id = absint( $identifier );
        if ( $post_id < 1 ) {
            return false;
        }
        $user_id = get_current_user_id();
        return snn_user_has_liked_post( $post_id, $user_id );
    } else {
        return snn_has_user_liked_legacy( $identifier );
    }
}

/**
 * For retrieving all user-likes in the IP-based system
 */
function snn_get_user_likes_legacy() {
    global $wpdb;
    $user_identifier = snn_get_user_identifier();
    $likes = [];

    $like_options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'snn_likes_data_%'"
    );

    foreach ( $like_options as $option ) {
        $data = maybe_unserialize( $option->option_value );
        if ( is_array( $data ) && in_array( $user_identifier, $data, true ) ) {
            $identifier = str_replace( 'snn_likes_data_', '', $option->option_name );
            $likes[] = $identifier;
        }
    }
    return $likes;
}

/**
 * For retrieving all user-likes in user-based system
 */
function snn_get_user_likes_user_based( $user_id ) {
    $liked_posts = get_user_meta( $user_id, '_snn_liked_posts', true );
    if ( ! is_array( $liked_posts ) ) {
        return [];
    }
    return array_map( 'intval', $liked_posts );
}

/**
 * REGISTER REST ENDPOINTS with dynamic token
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'snn/v1', '/(?P<token>[a-zA-Z0-9]{32})/like', [
        'methods'             => 'POST',
        'callback'            => 'snn_handle_like',
        'permission_callback' => '__return_true',
    ]);
    
    register_rest_route( 'snn/v1', '/(?P<token>[a-zA-Z0-9]{32})/get-likes', [
        'methods'             => 'GET',
        'callback'            => 'snn_get_likes_endpoint',
        'permission_callback' => '__return_true',
    ]);
} );

/**
 * GET-LIKES endpoint
 */
function snn_get_likes_endpoint( WP_REST_Request $request ) {
    // Token validation removed.
    $identifier  = sanitize_text_field( $request->get_param( 'identifier' ) );
    $logged_only = $request->get_param( 'loggedOnly' ) === 'true';

    if ( ! empty( $identifier ) ) {
        $like_count = snn_get_like_count( $identifier, $logged_only );
        $liked      = snn_has_user_liked( $identifier, $logged_only );
        
        return rest_ensure_response([
            'count' => $like_count,
            'liked' => $liked,
        ]);
    } else {
        if ( $logged_only && is_user_logged_in() ) {
            $user_id    = get_current_user_id();
            $user_likes = snn_get_user_likes_user_based( $user_id );
            return rest_ensure_response([
                'userLikes' => $user_likes,
            ]);
        } else {
            return rest_ensure_response([
                'userLikes' => snn_get_user_likes_legacy(),
            ]);
        }
    }
}

/**
 * LIKE/UNLIKE endpoint
 */
function snn_handle_like( WP_REST_Request $request ) {
    // Token validation removed.
    $identifier  = sanitize_text_field( $request->get_param( 'identifier' ) );
    $logged_only = $request->get_param( 'loggedOnly' ) === 'true';

    if ( empty( $identifier ) ) {
        return new WP_Error( 'no_identifier', 'No identifier provided', [ 'status' => 400 ] );
    }

    if ( $logged_only ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_logged_in', 'You must be logged in to like', [ 'status' => 403 ] );
        }
        $post_id = absint( $identifier );
        if ( $post_id < 1 ) {
            return new WP_Error( 'invalid_identifier', 'Invalid post identifier', [ 'status' => 400 ] );
        }

        $user_id = get_current_user_id();
        $user_has_liked = snn_user_has_liked_post( $post_id, $user_id );
        if ( $user_has_liked ) {
            snn_update_post_like( $post_id, $user_id, false );
            snn_update_user_like( $user_id, $post_id, false );
            $liked = false;
        } else {
            snn_update_post_like( $post_id, $user_id, true );
            snn_update_user_like( $user_id, $post_id, true );
            $liked = true;
        }
        $count = snn_get_like_count_user_based( $post_id );

        return rest_ensure_response([
            'count' => $count,
            'liked' => $liked,
        ]);
    } else {
        $user_identifier = snn_get_user_identifier();
        $likes_data = snn_get_likes_data_legacy( $identifier );

        if ( in_array( $user_identifier, $likes_data, true ) ) {
            $likes_data = array_values( array_diff( $likes_data, [ $user_identifier ] ) );
            $liked = false;
        } else {
            $likes_data[] = $user_identifier;
            $liked = true;
        }
        snn_update_likes_data_legacy( $identifier, $likes_data );
        
        return rest_ensure_response([
            'count' => count( $likes_data ),
            'liked' => $liked,
        ]);
    }
}

add_action('rest_authentication_errors', function ( $result ) {
    if ( strpos( $_SERVER['REQUEST_URI'], 'snn/v1/' ) !== false ) {
        return true;
    }
    return $result;
}, 99);

/**
 * FRONTEND JS (IN FOOTER)
 */
add_action( 'wp_footer', function () { 
    // Get the dynamic token from the server (and create it if not exists)
    $token = snn_get_or_create_token();
    ?>
<script>
// Expose to JS whether user is logged in and the dynamic token
var snn_is_logged_in = "<?php echo is_user_logged_in() ? 'true' : 'false'; ?>";
var snn_token = "<?php echo $token; ?>";

// Save token to localStorage if not already present.
if(!localStorage.getItem('snn_token')) {
    localStorage.setItem('snn_token', snn_token);
} else {
    snn_token = localStorage.getItem('snn_token');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.brxe-like-button').forEach(function(button) {
        const identifier = button.getAttribute('data-identifier');
        const loggedOnly = button.getAttribute('data-logged-only') === 'true';
        if (!identifier) return;
        
        let url = "<?php echo rest_url('snn/v1/'); ?>" + snn_token + "/get-likes?identifier=" + encodeURIComponent(identifier);
        url += "&loggedOnly=" + (loggedOnly ? 'true' : 'false');

        fetch(url)
            .then(response => response.json())
            .then(data => {
                button.querySelector('.default-icon').style.display = data.liked ? 'none' : 'inline';
                button.querySelector('.liked-icon').style.display   = data.liked ? 'inline' : 'none';
                button.setAttribute('data-liked', data.liked ? 'true' : 'false');
                
                const countElement = button.querySelector('.snn-like-count');
                if (countElement && typeof data.count !== 'undefined') {
                    countElement.textContent = data.count;
                }
                
                if (loggedOnly && snn_is_logged_in === 'false') {
                    let loginText = button.getAttribute('data-balloon-text-login-to-like');
                    button.setAttribute('data-balloon', loginText);
                    button.setAttribute('data-balloon-pos', 'top');
                } else {
                    let likeText   = button.getAttribute('data-balloon-text-like');
                    let unlikeText = button.getAttribute('data-balloon-text-unlike');
                    let balloonText = data.liked ? unlikeText : likeText;
                    button.setAttribute('data-balloon', balloonText);
                    button.setAttribute('data-balloon-pos', 'top');
                }
            })
            .catch(error => console.error('Error checking like status:', error));
    });
});

function snn_likeButton(el) {
    const identifier = el.getAttribute('data-identifier');
    const loggedOnly = el.getAttribute('data-logged-only') === 'true';

    if (loggedOnly && snn_is_logged_in === 'false') {
        // Optionally, provide a visual cue for non-logged in users
        return;
    }

    el.classList.add('snn-loading');
    
    fetch("<?php echo rest_url('snn/v1/'); ?>" + snn_token + "/like", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            identifier: identifier,
            loggedOnly: loggedOnly ? 'true' : 'false'
        })
    })
    .then(response => response.json())
    .then(data => {
        el.querySelector('.default-icon').style.display = data.liked ? 'none' : 'inline';
        el.querySelector('.liked-icon').style.display   = data.liked ? 'inline' : 'none';
        el.setAttribute('data-liked', data.liked ? 'true' : 'false');
        
        const countElement = el.querySelector('.snn-like-count');
        if (countElement && typeof data.count !== 'undefined') {
            countElement.textContent = data.count;
        }
        
        if (loggedOnly && snn_is_logged_in === 'false') {
            let loginText = el.getAttribute('data-balloon-text-login-to-like');
            el.setAttribute('data-balloon', loginText);
            el.setAttribute('data-balloon-pos', 'top');
        } else {
            let likeText   = el.getAttribute('data-balloon-text-like');
            let unlikeText = el.getAttribute('data-balloon-text-unlike');
            let balloonText = data.liked ? unlikeText : likeText;
            el.setAttribute('data-balloon', balloonText);
            el.setAttribute('data-balloon-pos', 'top');
        }

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
<?php 
});
?>
