<?php

/**
 * ----------------------------------------
 * Raw All Custom Fields Tag
 * ----------------------------------------
 * Usage: {raw_all_custom_fields}
 * Description: Outputs all custom fields (meta data) for the current post in raw JSON format.
 *
 * Usage: {raw_all_author_fields}
 * Description: Outputs all meta fields for the current author in raw JSON format.
 */

add_filter( 'bricks/dynamic_tags_list', 'add_raw_all_custom_fields_tag_to_builder' );
function add_raw_all_custom_fields_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{raw_all_custom_fields}',
        'label' => 'Raw All Custom Fields',
        'group' => 'SNN',
    ];
    $tags[] = [
        'name'  => '{raw_all_author_fields}',
        'label' => 'Raw All Author Fields',
        'group' => 'SNN',
    ];
    return $tags;
}

function get_raw_all_custom_fields( $post ) {
    if ( $post && isset( $post->ID ) ) {
        // Get all post meta for the current post
        $all_meta = get_post_meta( $post->ID );

        // Process the meta to get single values where appropriate
        $processed_meta = [];
        foreach ( $all_meta as $key => $value ) {
            // Check if it's a single value or array
            if ( is_array( $value ) && count( $value ) === 1 ) {
                $processed_meta[$key] = maybe_unserialize( $value[0] );
            } else {
                $processed_meta[$key] = array_map( 'maybe_unserialize', $value );
            }
        }

        // Return as JSON
        return wp_json_encode( $processed_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }
    return '{}';
}

function get_raw_all_author_fields( $post ) {
    // Get author ID using the same logic as get_contextual_id
    $author_id = null;

    // On author archive pages, return the queried author ID
    if ( is_author() ) {
        $author_id = get_queried_object_id();
    }
    // On singular posts/pages, return the post author
    elseif ( is_singular() ) {
        $author_id = get_post_field( 'post_author', get_the_ID() );
    }
    // In the loop, return current post's author
    elseif ( in_the_loop() && get_the_ID() ) {
        $author_id = get_post_field( 'post_author', get_the_ID() );
    }
    // Fallback to post object if provided
    elseif ( $post && isset( $post->post_author ) ) {
        $author_id = $post->post_author;
    }

    if ( $author_id ) {
        // Get all user meta for the author
        $all_meta = get_user_meta( $author_id );

        // Process the meta to get single values where appropriate
        $processed_meta = [];
        foreach ( $all_meta as $key => $value ) {
            // Check if it's a single value or array
            if ( is_array( $value ) && count( $value ) === 1 ) {
                $processed_meta[$key] = maybe_unserialize( $value[0] );
            } else {
                $processed_meta[$key] = array_map( 'maybe_unserialize', $value );
            }
        }

        // Return as JSON
        return wp_json_encode( $processed_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }
    return '{}';
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_raw_all_custom_fields_tag', 10, 3 );
function render_raw_all_custom_fields_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === '{raw_all_custom_fields}' ) {
        return get_raw_all_custom_fields( $post );
    }
    if ( $tag === '{raw_all_author_fields}' ) {
        return get_raw_all_author_fields( $post );
    }
    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_raw_all_custom_fields_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_raw_all_custom_fields_in_content', 10, 2 );
function render_raw_all_custom_fields_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{raw_all_custom_fields}' ) !== false ) {
        $json_output = get_raw_all_custom_fields( $post );
        $content = str_replace( '{raw_all_custom_fields}', $json_output, $content );
    }
    if ( strpos( $content, '{raw_all_author_fields}' ) !== false ) {
        $json_output = get_raw_all_author_fields( $post );
        $content = str_replace( '{raw_all_author_fields}', $json_output, $content );
    }
    return $content;
}
