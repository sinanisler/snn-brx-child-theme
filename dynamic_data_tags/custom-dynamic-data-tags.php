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
 * 1. Taxonomy Term Slug Tag
 * ----------------------------------------
 * Usage: {taxonomy_term_slug:taxonomy}
 * Description: Inserts slugs of taxonomy terms assigned to the post.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_taxonomy_term_slug_tag_to_builder' );
function add_taxonomy_term_slug_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{taxonomy_term_slug}',
        'label' => 'Taxonomy Term Slug',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_taxonomy_term_slug( $post, $taxonomy ) {
    if ( $post && isset( $post->ID ) && ! empty( $taxonomy ) ) {
        $terms = get_the_terms( $post->ID, $taxonomy );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $term_slugs = array_map( function( $term ) {
                return esc_html( $term->slug );
            }, $terms );
            return implode( ' ', $term_slugs );
        }
    }
    return '';
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_taxonomy_term_slug_tag', 10, 3 );
function render_taxonomy_term_slug_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{taxonomy_term_slug:' ) === 0 ) {
        $parts = explode( ':', trim( $tag, '{}' ) );
        $taxonomy = isset( $parts[1] ) ? sanitize_key( $parts[1] ) : 'category'; // Default to 'category'
        return get_taxonomy_term_slug( $post, $taxonomy );
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_taxonomy_term_slug_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_taxonomy_term_slug_in_content', 10, 2 );
function render_taxonomy_term_slug_in_content( $content, $post, $context = 'text' ) {
    if ( preg_match_all( '/\{taxonomy_term_slug:([a-zA-Z0-9_\-]+)\}/', $content, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $match ) {
            $taxonomy = sanitize_key( $match[1] );
            $slug     = get_taxonomy_term_slug( $post, $taxonomy );
            $content  = str_replace( $match[0], $slug, $content );
        }
    }
    return $content;
}

/**
 * ----------------------------------------
 * 2. Taxonomy Color Tag
 * ----------------------------------------
 * Usage: {taxonomy_color_tag:taxonomy}
 * Description: Fetches the 'color' custom field for a specified taxonomy term.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_taxonomy_color_tag_to_builder' );
function add_taxonomy_color_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{taxonomy_color_tag}',
        'label' => 'Taxonomy Color Tag',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_taxonomy_color( $post_id, $taxonomy ) {
    if ( ! empty( $taxonomy ) ) {
        $terms = get_the_terms( $post_id, $taxonomy );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $term_id = $terms[0]->term_id; // Get the first term ID
            $color   = get_term_meta( $term_id, 'color', true );
            return $color ? esc_attr( $color ) : '#000000'; // Default to black
        }
    }
    return '#000000'; // Default to black
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_taxonomy_color_tag', 10, 3 );
function render_taxonomy_color_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{taxonomy_color_tag:' ) === 0 ) {
        $parts    = explode( ':', trim( $tag, '{}' ) );
        $taxonomy = isset( $parts[1] ) ? sanitize_key( $parts[1] ) : '';
        return get_taxonomy_color( $post->ID, $taxonomy );
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_taxonomy_color_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_taxonomy_color_in_content', 10, 2 );
function render_taxonomy_color_in_content( $content, $post, $context = 'text' ) {
    if ( preg_match_all( '/\{taxonomy_color_tag:([^}]+)\}/', $content, $matches ) ) {
        foreach ( $matches[1] as $index => $taxonomy ) {
            $taxonomy = sanitize_key( $taxonomy );
            $color    = get_taxonomy_color( $post->ID, $taxonomy );
            $content  = str_replace( $matches[0][ $index ], $color, $content );
        }
    }
    return $content;
}

/**
 * ----------------------------------------
 * 3. Current User First Name Tag
 * ----------------------------------------
 * Usage: {current_user_first_name}
 * Description: Displays the first name of the current logged-in user or their username if the first name is not set.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_current_user_first_name_tag_to_builder' );
function add_current_user_first_name_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{current_user_first_name}',
        'label' => 'Current User First Name',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_current_user_first_name() {
    $current_user = wp_get_current_user();
    if ( $current_user->ID !== 0 ) {
        $first_name = get_user_meta( $current_user->ID, 'first_name', true );
        return ! empty( $first_name ) ? esc_html( $first_name ) : esc_html( $current_user->user_login );
    }
    return '';
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_current_user_first_name_tag', 10, 3 );
function render_current_user_first_name_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === '{current_user_first_name}' ) {
        return get_current_user_first_name();
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_current_user_first_name_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_current_user_first_name_in_content', 10, 2 );
function render_current_user_first_name_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{current_user_first_name}' ) !== false ) {
        $first_name = get_current_user_first_name();
        $content    = str_replace( '{current_user_first_name}', $first_name, $content );
    }
    return $content;
}

/**
 * ----------------------------------------
 * 4. Current User Fields Tag
 * ----------------------------------------
 * Usage: {current_user_fields:field}
 * Description: Fetches various fields of the current user, such as name, firstname, lastname, email, and custom fields.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_current_user_fields_tag_to_builder' );
function add_current_user_fields_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{current_user_fields}',
        'label' => 'Current User Fields',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function get_current_user_field( $field ) {
    $current_user = wp_get_current_user();
    if ( $current_user->ID !== 0 ) {
        switch ( strtolower( $field ) ) {
            case 'name':
                return esc_html( $current_user->display_name );
            case 'firstname':
                return esc_html( get_user_meta( $current_user->ID, 'first_name', true ) );
            case 'lastname':
                return esc_html( get_user_meta( $current_user->ID, 'last_name', true ) );
            case 'email':
                return esc_html( $current_user->user_email );
            default:
                // Handle custom fields, return an empty string if the field is not set
                return esc_html( get_user_meta( $current_user->ID, sanitize_key( $field ), true ) ) ?: '';
        }
    }
    return '';
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_current_user_fields_tag', 10, 3 );
function render_current_user_fields_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{current_user_fields:' ) === 0 ) {
        // Extract the field name after 'current_user_fields:'
        $field = str_replace( '{current_user_fields:', '', trim( $tag, '{}' ) );
        return get_current_user_field( sanitize_key( $field ) );
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_current_user_fields_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_current_user_fields_in_content', 10, 2 );
function render_current_user_fields_in_content( $content, $post, $context = 'text' ) {
    // Find all occurrences of '{current_user_fields:field}'
    if ( preg_match_all( '/\{current_user_fields:([^}]+)\}/', $content, $matches ) ) {
        foreach ( $matches[1] as $field ) {
            $field_value = get_current_user_field( sanitize_key( $field ) );
            // Replace the placeholder with the actual user field value
            $content = str_replace( "{current_user_fields:$field}", $field_value, $content );
        }
    }
    return $content;
}

/**
 * ----------------------------------------
 * 5. Estimated Post Read Time Tag
 * ----------------------------------------
 * Usage: {estimated_post_read_time}
 * Description: Displays the estimated read time for a post based on word count.
 */
add_filter( 'bricks/dynamic_tags_list', 'add_estimated_post_read_time_tag_to_builder' );
function add_estimated_post_read_time_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{estimated_post_read_time}',
        'label' => 'Estimated Post Read Time',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

function calculate_estimated_read_time() {
    global $post;
    if ( ! $post ) {
        return 0;
    }
    $word_count = str_word_count( strip_tags( $post->post_content ) );
    $read_time  = ceil( $word_count / 200 ); // Average reading speed: 200 words per minute
    return intval( $read_time ); // Ensure it's an integer
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_estimated_post_read_time_tag', 10, 3 );
function render_estimated_post_read_time_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === '{estimated_post_read_time}' ) {
        return calculate_estimated_read_time();
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_estimated_post_read_time_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_estimated_post_read_time_in_content', 10, 2 );
function render_estimated_post_read_time_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{estimated_post_read_time}' ) !== false ) {
        $read_time = calculate_estimated_read_time();
        $content   = str_replace( '{estimated_post_read_time}', esc_html( $read_time ), $content );
    }
    return $content;
}

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
