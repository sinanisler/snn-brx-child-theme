<?php
/**
 * Get Post Content Ability
 * Registers the snn/get-post-content ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_get_post_content_ability' );
function snn_register_get_post_content_ability() {
    wp_register_ability(
        'snn/get-post-content',
        array(
            'label'       => __( 'Get Post Content', 'snn' ),
            'description' => __( 'Retrieves the full content and metadata of a WordPress post or page. Returns title, content (both raw HTML and plain text), excerpt, status, author, dates, categories, tags, featured image, word count, and block information. Use this to analyze or read existing content, check what\'s in a post before editing, or gather information for SEO analysis.', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to retrieve content from.',
                    ),
                    'include_meta' => array(
                        'type'        => 'boolean',
                        'description' => 'Include post metadata (custom fields).',
                        'default'     => false,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'  => array(
                        'type'        => 'integer',
                        'description' => 'Post ID',
                    ),
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'Post title',
                    ),
                    'content' => array(
                        'type'        => 'object',
                        'description' => 'Post content in various formats',
                    ),
                    'metadata' => array(
                        'type'        => 'object',
                        'description' => 'Post metadata and statistics',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                $include_meta = $input['include_meta'] ?? false;

                // Get post
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'read_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to read this post.', 'snn' ) );
                }

                // Get categories
                $categories = get_the_category( $post_id );
                $category_names = array_map( function( $cat ) {
                    return $cat->name;
                }, $categories );

                // Get tags
                $tags = get_the_tags( $post_id );
                $tag_names = $tags ? array_map( function( $tag ) {
                    return $tag->name;
                }, $tags ) : array();

                // Get featured image
                $thumbnail_id = get_post_thumbnail_id( $post_id );
                $featured_image = null;
                if ( $thumbnail_id ) {
                    $image_data = wp_get_attachment_image_src( $thumbnail_id, 'full' );
                    $featured_image = array(
                        'id'  => $thumbnail_id,
                        'url' => $image_data[0] ?? null,
                        'alt' => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
                    );
                }

                // Build content array
                $content_data = array(
                    'raw'       => $post->post_content,
                    'rendered'  => apply_filters( 'the_content', $post->post_content ),
                    'plain_text' => wp_strip_all_tags( $post->post_content ),
                    'excerpt'   => $post->post_excerpt,
                    'has_blocks' => has_blocks( $post->post_content ),
                );

                // Get block count if using blocks
                if ( $content_data['has_blocks'] ) {
                    $blocks = parse_blocks( $post->post_content );
                    $content_data['block_count'] = count( array_filter( $blocks, function( $block ) {
                        return ! empty( $block['blockName'] );
                    } ) );
                }

                // Build metadata array
                $metadata = array(
                    'status'       => $post->post_status,
                    'type'         => $post->post_type,
                    'author'       => array(
                        'id'   => $post->post_author,
                        'name' => get_the_author_meta( 'display_name', $post->post_author ),
                    ),
                    'dates'        => array(
                        'created'  => $post->post_date,
                        'modified' => $post->post_modified,
                    ),
                    'categories'   => $category_names,
                    'tags'         => $tag_names,
                    'featured_image' => $featured_image,
                    'word_count'   => str_word_count( wp_strip_all_tags( $post->post_content ) ),
                    'comment_count' => $post->comment_count,
                    'permalink'    => get_permalink( $post_id ),
                    'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
                );

                // Include custom meta if requested
                if ( $include_meta ) {
                    $meta_data = get_post_meta( $post_id );
                    // Filter out internal WordPress meta
                    $custom_meta = array();
                    foreach ( $meta_data as $key => $value ) {
                        if ( substr( $key, 0, 1 ) !== '_' ) {
                            $custom_meta[ $key ] = $value;
                        }
                    }
                    $metadata['custom_fields'] = $custom_meta;
                }

                return array(
                    'id'       => $post_id,
                    'title'    => $post->post_title,
                    'content'  => $content_data,
                    'metadata' => $metadata,
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'read' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}
