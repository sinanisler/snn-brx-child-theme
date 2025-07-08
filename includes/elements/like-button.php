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
            return esc_html__( 'Like Button (Logged User Only)', 'snn' );
        }

        public function set_controls() {
            $this->controls['button_icon'] = [
                'tab'     => 'content',
                'type'    => 'icon',
                'label'   => esc_html__( 'Button Icon', 'snn' ),
                'default' => [ 'library' => 'fontawesomeRegular', 'icon' => 'fa fa-heart' ],
            ];

            $this->controls['liked_icon'] = [
                'tab'     => 'content',
                'type'    => 'icon',
                'label'   => esc_html__( 'Liked Icon', 'snn' ),
                'default' => [ 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-heart' ],
            ];

            $this->controls['show_like_count'] = [
                'tab'     => 'content',
                'type'    => 'checkbox',
                'label'   => esc_html__( 'Show Like Count', 'snn' ),
                'inline'  => true,
                'small'   => true,
                'default' => true,
            ];

            $this->controls['identifier'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Custom Identifier', 'snn' ),
                'default'     => '',
                'description' => 'Leave blank to use current post ID. <br><br>',
            ];

            // Settings for custom balloon texts
            $this->controls['balloon_text_like'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Balloon Text (Like)', 'snn' ),
                'default'     => esc_html__( 'Like', 'snn' ),
                'placeholder' => esc_html__( 'Like', 'snn' ),
            ];

            $this->controls['balloon_text_unlike'] = [
                'tab'         => 'content',
                'type'        => 'text',
                'label'       => esc_html__( 'Balloon Text (Unlike)', 'snn' ),
                'default'     => esc_html__( 'Unlike', 'snn' ),
                'placeholder' => esc_html__( 'Unlike', 'snn' ),
                'description' => '',
            ];
        }

        public function render() {
            // If user is not logged in, render absolutely nothing.
            if ( ! is_user_logged_in() ) {
                return;
            }

            $settings            = $this->settings;
            $icon_settings       = $settings['button_icon'] ?? [];
            $liked_icon_set      = $settings['liked_icon'] ?? [];
            $custom_identifier   = $settings['identifier'] ?? '';

            if ( is_array( $custom_identifier ) && isset( $custom_identifier['raw'] ) ) {
                $custom_identifier = $custom_identifier['raw'];
            }
            $identifier = ! empty( $custom_identifier ) ? $custom_identifier : get_the_ID();

            $like_count = snn_get_like_count( $identifier );
            $liked      = snn_has_user_liked( $identifier );

            // Retrieve balloon text settings
            $balloon_text_like   = ! empty( $settings['balloon_text_like'] ) ? $settings['balloon_text_like'] : esc_html__( 'Click to Like', 'snn' );
            $balloon_text_unlike = ! empty( $settings['balloon_text_unlike'] ) ? $settings['balloon_text_unlike'] : esc_html__( 'Click to Unlike', 'snn' );

            // Simplified balloon text logic since user must be logged in to see this.
            $balloon_text = $liked ? $balloon_text_unlike : $balloon_text_like;

            $this->set_attribute( '_root', 'class', [ 'brxe-like-button', 'like-button-element' ] );
            $this->set_attribute( '_root', 'data-identifier', $identifier );
            $this->set_attribute( '_root', 'data-count', $like_count );
            $this->set_attribute( '_root', 'data-liked', $liked ? 'true' : 'false' );
            $this->set_attribute( '_root', 'data-logged-only', 'true' );

            // Set balloon text attributes
            $this->set_attribute( '_root', 'data-balloon', $balloon_text );
            $this->set_attribute( '_root', 'data-balloon-pos', 'top' );
            $this->set_attribute( '_root', 'data-balloon-text-like', $balloon_text_like );
            $this->set_attribute( '_root', 'data-balloon-text-unlike', $balloon_text_unlike );

            echo '<div ' . $this->render_attributes( '_root' ) . ' onclick="snn_likeButton(this)" style="cursor:pointer;">';
            echo '<span class="button-icon default-icon" style="' . ( $liked ? 'display:none;' : 'display:inline;' ) . '">';
            bricks_render_icon( $icon_settings );
            echo '</span>';
            echo '<span class="button-icon liked-icon" style="' . ( $liked ? 'display:inline;' : 'display:none;' ) . '">';
            bricks_render_icon( $liked_icon_set );
            echo '</span>';
            if ( ! empty( $settings['show_like_count'] ) ) {
                echo '<span class="snn-like-count" style="margin-left:1rem">' . intval( $like_count ) . '</span>';
            }
            echo '</div>';
        }
    }
}

if ( function_exists( 'bricks' ) ) {
    bricks()->elements->register_element( new Like_Button_Element() );
}

// CORE CHANGE: All server-side hooks now only run for logged-in users.
if ( is_user_logged_in() ) {

    /**
     * TOKEN HANDLING FUNCTIONS
     */
    add_action('init', 'snn_init_dynamic_token');
    function snn_init_dynamic_token() {
        snn_get_or_create_token();
    }

    function snn_get_or_create_token() {
        if ( ! isset( $_COOKIE['snn_dynamic_token'] ) ) {
            $token = bin2hex( random_bytes( 16 ) );
            if ( ! headers_sent() ) {
                setcookie( 'snn_dynamic_token', $token, time() + 3600, '/' );
            }
            set_transient( 'snn_token_' . $token, true, 3600 );
            return $token;
        }
        $token = sanitize_text_field( $_COOKIE['snn_dynamic_token'] );
        if ( ! get_transient( 'snn_token_' . $token ) ) {
            $token = bin2hex( random_bytes( 16 ) );
            if ( ! headers_sent() ) {
                setcookie( 'snn_dynamic_token', $token, time() + 3600, '/' );
            }
            set_transient( 'snn_token_' . $token, true, 3600 );
        }
        return $token;
    }

    /**
     * REGISTER REST ENDPOINTS
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
        // Redundant check, but good for safety. The parent check already handles this.
        if ( ! is_user_logged_in() ) {
            return;
        }

        $token = snn_get_or_create_token();
        ?>

<script>
    var snn_token = "<?php echo $token; ?>";
    if (!localStorage.getItem('snn_token')) {
        localStorage.setItem('snn_token', snn_token);
    } else {
        snn_token = localStorage.getItem('snn_token');
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.brxe-like-button').forEach(function(button) {
            const identifier = button.getAttribute('data-identifier');
            if (!identifier) return;
            let url = "<?php echo rest_url('snn/v1/'); ?>" + snn_token + "/get-likes?identifier=" + encodeURIComponent(identifier) + "&loggedOnly=true";
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    button.querySelector('.default-icon').style.display = data.liked ? 'none' : 'inline';
                    button.querySelector('.liked-icon').style.display = data.liked ? 'inline' : 'none';
                    button.setAttribute('data-liked', data.liked ? 'true' : 'false');
                    const countElement = button.querySelector('.snn-like-count');
                    if (countElement && typeof data.count !== 'undefined') {
                        countElement.textContent = data.count;
                    }
                    let likeText = button.getAttribute('data-balloon-text-like');
                    let unlikeText = button.getAttribute('data-balloon-text-unlike');
                    let balloonText = data.liked ? unlikeText : likeText;
                    button.setAttribute('data-balloon', balloonText);
                    button.setAttribute('data-balloon-pos', 'top');
                })
                .catch(error => console.error('Error checking like status:', error));
        });
    });

    function snn_likeButton(el) {
        const identifier = el.getAttribute('data-identifier');
        el.classList.add('snn-loading');
        fetch("<?php echo rest_url('snn/v1/'); ?>" + snn_token + "/like", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier: identifier, loggedOnly: 'true' })
        })
        .then(response => response.json())
        .then(data => {
            el.querySelector('.default-icon').style.display = data.liked ? 'none' : 'inline';
            el.querySelector('.liked-icon').style.display = data.liked ? 'inline' : 'none';
            el.setAttribute('data-liked', data.liked ? 'true' : 'false');
            const countElement = el.querySelector('.snn-like-count');
            if (countElement && typeof data.count !== 'undefined') {
                countElement.textContent = data.count;
            }
            let likeText = el.getAttribute('data-balloon-text-like');
            let unlikeText = el.getAttribute('data-balloon-text-unlike');
            let balloonText = data.liked ? unlikeText : likeText;
            el.setAttribute('data-balloon', balloonText);
            el.setAttribute('data-balloon-pos', 'top');
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

} // End if ( is_user_logged_in() )

/**
 * ===============
 * USER-BASED STORAGE FUNCTIONS (formerly "NEW STORAGE")
 * ===============
 */
function snn_get_likes_data_user_based( $post_id ) {
    $liked_by = get_post_meta( $post_id, '_snn_liked_by', true );
    return is_array( $liked_by ) ? $liked_by : [];
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
    update_post_meta( $post_id, '_snn_liked_by', array_unique( $liked_by ) );
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
    update_user_meta( $user_id, '_snn_liked_posts', array_unique( $liked_posts ) );
}

function snn_get_user_likes_user_based( $user_id ) {
    $liked_posts = get_user_meta( $user_id, '_snn_liked_posts', true );
    return is_array( $liked_posts ) ? array_map( 'intval', $liked_posts ) : [];
}

/**
 * ===============
 * SIMPLIFIED WRAPPER FUNCTIONS
 * ===============
 */
function snn_get_like_count( $identifier ) {
    $post_id = absint( $identifier );
    return ( $post_id > 0 ) ? snn_get_like_count_user_based( $post_id ) : 0;
}

function snn_has_user_liked( $identifier ) {
    $post_id = absint( $identifier );
    if ( $post_id < 1 || ! is_user_logged_in() ) {
        return false;
    }
    $user_id = get_current_user_id();
    return snn_user_has_liked_post( $post_id, $user_id );
}

/**
 * ===============
 * SIMPLIFIED ENDPOINT HANDLERS
 * ===============
 */
function snn_get_likes_endpoint( WP_REST_Request $request ) {
    $identifier = sanitize_text_field( $request->get_param( 'identifier' ) );
    
    if ( ! empty( $identifier ) ) {
        return rest_ensure_response([
            'count' => snn_get_like_count( $identifier ),
            'liked' => snn_has_user_liked( $identifier ),
        ]);
    } else {
        // This part of the endpoint gets all likes for the current user
        $user_id    = get_current_user_id();
        $user_likes = snn_get_user_likes_user_based( $user_id );
        return rest_ensure_response([
            'userLikes' => $user_likes,
        ]);
    }
}

function snn_handle_like( WP_REST_Request $request ) {
    $identifier = sanitize_text_field( $request->get_param( 'identifier' ) );
    $post_id    = absint( $identifier );

    if ( $post_id < 1 ) {
        return new WP_Error( 'invalid_identifier', 'Invalid post identifier', [ 'status' => 400 ] );
    }

    // The parent hook already checks if user is logged in, but this is a critical check.
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'not_logged_in', 'You must be logged in to like', [ 'status' => 403 ] );
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
    
    return rest_ensure_response([
        'count' => snn_get_like_count_user_based( $post_id ),
        'liked' => $liked,
    ]);
}
?>
