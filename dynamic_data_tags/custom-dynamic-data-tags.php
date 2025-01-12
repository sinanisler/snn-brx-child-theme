<?php
// Add this code to your active theme's functions.php file or within a custom plugin

/**
 * ========================================
 * Dynamic Tags for Bricks Builder - SNN BRX
 * ========================================
 * This file contains custom dynamic tags for Bricks Builder,
 * enhancing functionality by allowing dynamic data insertion.
 * 
 * Tags Included:
 * - {taxonomy_term_slug:taxonomy}
 * - {taxonomy_color_tag:taxonomy}
 * - {current_user_first_name}
 * - {current_user_fields:field}
 * - {estimated_post_read_time}
 * - {current_user_id}
 * - {post_count:post_type:taxonomy:term}
 * - {parent_link}
 */

 
/**
 * ----------------------------------------
 * 6. Current User ID Tag
 * ----------------------------------------
 * Usage: {current_user_id}
 * Description: Displays the ID of the current logged-in user or 'Guest' if no user is logged in.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_current_user_id_tag_to_builder' );
function add_current_user_id_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{current_user_id}',
        'label' => 'Current User ID',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_current_user_id_bricks() {
    if ( is_user_logged_in() ) {
        return esc_html( get_current_user_id() );
    }

    // Return a default value or message if no user is logged in
    return ''; // Or return '';
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_current_user_id_tag', 10, 3 );
function render_current_user_id_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === '{current_user_id}' ) {
        return get_current_user_id_bricks();
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'replace_current_user_id_in_content', 20, 3 );
add_filter( 'bricks/frontend/render_data', 'replace_current_user_id_in_content', 20, 2 );
function replace_current_user_id_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{current_user_id}' ) !== false ) {
        $user_id = get_current_user_id_bricks();
        $content = str_replace( '{current_user_id}', $user_id, $content );
    }
    return $content;
}

// Register shortcode [current_user_id]
add_shortcode( 'current_user_id', 'shortcode_current_user_id' );
function shortcode_current_user_id() {
    if ( is_user_logged_in() ) {
        return esc_html( get_current_user_id() );
    }

    // Return a default value or message if no user is logged in
    return ''; // Or return '';
}

/**
 * ----------------------------------------
 * 7. Post Count Tag
 * ----------------------------------------
 * Usage: {post_count:post_type} or {post_count:post_type:taxonomy:term_slug}
 * Description: Displays the count of posts for a specified post type, optionally filtered by taxonomy and term slug.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_post_count_tag_to_builder' );
function add_post_count_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{post_count}',
        'label' => 'Post Count',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_post_count( $post_type, $taxonomy = '', $term_slug = '' ) {
    if ( ! empty( $taxonomy ) && ! empty( $term_slug ) ) {
        $term = get_term_by( 'slug', $term_slug, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            return intval( $term->count );
        }
    } else {
        $count = wp_count_posts( $post_type );
        return isset( $count->publish ) ? intval( $count->publish ) : 0;
    }
    return 0;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_post_count_tag', 10, 3 );
function render_post_count_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{post_count:' ) === 0 ) {
        $parts      = explode( ':', trim( $tag, '{}' ) );
        $post_type  = isset( $parts[1] ) ? sanitize_key( $parts[1] ) : '';
        $taxonomy   = isset( $parts[2] ) ? sanitize_key( $parts[2] ) : '';
        $term_slug  = isset( $parts[3] ) ? sanitize_key( $parts[3] ) : '';
        return get_post_count( $post_type, $taxonomy, $term_slug );
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_post_count_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_post_count_in_content', 10, 2 );
function render_post_count_in_content( $content, $post, $context = 'text' ) {
    if ( preg_match_all( '/\{post_count:([\w-]+)(?::([\w-]+):([\w-]+))?\}/', $content, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $match ) {
            $post_type = sanitize_key( $match[1] );
            $taxonomy  = isset( $match[2] ) ? sanitize_key( $match[2] ) : '';
            $term_slug = isset( $match[3] ) ? sanitize_key( $match[3] ) : '';
            $post_count = get_post_count( $post_type, $taxonomy, $term_slug );
            $content    = str_replace( $match[0], intval( $post_count ), $content );
        }
    }
    return $content;
}

/**
 * ----------------------------------------
 * 8. Parent Link Tag
 * ----------------------------------------
 * Usage: {parent_link}
 * Description: Displays the parent post/page's title as a clickable link on a child post/page.
 */
add_filter( 'bricks/dynamic_tags_list', 'register_parent_link_tag' );
function register_parent_link_tag( $tags ) {
    $tags[] = [
        'name'  => '{parent_link}',
        'label' => 'Parent Title and Link',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_parent_link_tag', 10, 3 );
function render_parent_link_tag( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{parent_link}' ) { // Include braces
        return $tag;
    }

    if ( $post->post_parent ) {
        $parent_post = get_post( $post->post_parent );

        if ( $parent_post ) {
            $parent_title = get_the_title( $parent_post );
            $parent_link  = get_permalink( $parent_post );

            return '<a href="' . esc_url( $parent_link ) . '">' . esc_html( $parent_title ) . '</a>';
        }
    }

    return '';
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parent_link_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parent_link_tag_in_content', 10, 2 );
function render_parent_link_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{parent_link}' ) !== false ) {
        $parent_link = render_parent_link_tag( '{parent_link}', $post, $context );
        $content     = str_replace( '{parent_link}', $parent_link, $content );
    }

    return $content;
}
