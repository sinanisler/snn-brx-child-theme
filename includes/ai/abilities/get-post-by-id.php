<?php 
/**
 * Get Post By ID Ability
 * Registers the core/get-post-by-id ability for the WordPress Abilities API
 */

// Register category
add_action( 'wp_abilities_api_categories_init', 'snn_register_content_category_get_by_id' );
function snn_register_content_category_get_by_id() {
    // Only register if not already registered
    if ( ! wp_has_ability_category( 'content' ) ) {
        wp_register_ability_category(
            'content',
            array(
                'label'       => __( 'Content Management', 'snn' ),
                'description' => __( 'Abilities for managing posts, pages, and content.', 'snn' ),
            )
        );
    }
}

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_post_by_id_ability' );
function snn_register_get_post_by_id_ability() {
    wp_register_ability(
        'core/get-post-by-id',
        array(
            'label'       => __( 'Get Post By ID', 'wp-abilities' ),
            'description' => __( 'Retrieves detailed information about a specific post.', 'wp-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'ID of the post to retrieve.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'       => array( 'type' => 'integer' ),
                    'title'    => array( 'type' => 'string' ),
                    'content'  => array( 'type' => 'string' ),
                    'excerpt'  => array( 'type' => 'string' ),
                    'status'   => array( 'type' => 'string' ),
                    'type'     => array( 'type' => 'string' ),
                    'url'      => array( 'type' => 'string' ),
                    'edit_url' => array( 'type' => 'string' ),
                    'author'   => array( 'type' => 'object' ),
                    'date'     => array( 'type' => 'string' ),
                    'modified' => array( 'type' => 'string' ),
                    'categories' => array( 'type' => 'array' ),
                    'tags'     => array( 'type' => 'array' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                $post = get_post( $post_id );

                if ( ! $post ) {
                    return new WP_Error(
                        'post_not_found',
                        sprintf( 'Post with ID %d not found.', $post_id ),
                        array( 'status' => 404 )
                    );
                }

                $author = get_userdata( $post->post_author );
                $categories = get_the_category( $post_id );
                $tags = get_the_tags( $post_id );

                return array(
                    'id'       => $post->ID,
                    'title'    => $post->post_title,
                    'content'  => $post->post_content,
                    'excerpt'  => $post->post_excerpt,
                    'status'   => $post->post_status,
                    'type'     => $post->post_type,
                    'url'      => get_permalink( $post ),
                    'edit_url' => get_edit_post_link( $post, 'raw' ),
                    'author'   => array(
                        'id'   => $author->ID,
                        'name' => $author->display_name,
                    ),
                    'date'     => get_the_date( 'Y-m-d H:i:s', $post ),
                    'modified' => $post->post_modified,
                    'categories' => $categories ? array_map( function( $cat ) {
                        return array( 'id' => $cat->term_id, 'name' => $cat->name, 'slug' => $cat->slug );
                    }, $categories ) : array(),
                    'tags' => $tags ? array_map( function( $tag ) {
                        return array( 'id' => $tag->term_id, 'name' => $tag->name, 'slug' => $tag->slug );
                    }, $tags ) : array(),
                );
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
