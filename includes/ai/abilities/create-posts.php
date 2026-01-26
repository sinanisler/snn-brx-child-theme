<?php
/**
 * Create Post Ability
 * Registers the snn/create-post ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_create_post_ability' );
function snn_register_create_post_ability() {
    wp_register_ability(
        'snn/create-post',
        array(
            'label'       => __( 'Create Post', 'wp-abilities' ),
            'description' => __( 'Creates a new WordPress post or page with specified title, content (HTML supported), excerpt, status (draft/publish/pending/private), categories, and tags. Automatically sanitizes input and sets the current user as author. Returns the new post ID, permalink, and edit URL. Use this when you need to programmatically create content, import posts, generate articles, or add new pages to the site. Always create as draft first unless explicitly instructed to publish immediately.

CRITICAL: Content must be valid HTML with ALL tags properly closed to prevent broken blocks:
- Good: <h2>Title</h2><p>Introduction text.</p><ul><li>Point 1</li><li>Point 2</li></ul><p>Conclusion.</p>
- Bad: <p>Text without closing <h2>Title</h2> More loose text (will break)
- Lists must use: <ul><li>Item</li></ul> or <ol><li>Item</li></ol>
- No empty blocks: <p></p> (remove these)

See includes/ai/docs/block-generation-rules.md for complete guidelines.', 'wp-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'title', 'content' ),
                'properties' => array(
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'Post title.',
                        'minLength'   => 1,
                        'maxLength'   => 200,
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Post content (clean, valid HTML with ALL tags properly closed). WordPress will parse this into blocks. Use proper structure: <h2>Heading</h2><p>Paragraph</p><ul><li>List item</li></ul>. Avoid unclosed tags, empty blocks, or loose text.',
                        'minLength'   => 1,
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Post status (draft, publish, pending).',
                        'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
                        'default'     => 'draft',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type (post, page).',
                        'enum'        => array( 'post', 'page' ),
                        'default'     => 'post',
                    ),
                    'excerpt' => array(
                        'type'        => 'string',
                        'description' => 'Optional post excerpt.',
                    ),
                    'categories' => array(
                        'type'        => 'array',
                        'description' => 'Array of category IDs.',
                        'items'       => array( 'type' => 'integer' ),
                    ),
                    'tags' => array(
                        'type'        => 'array',
                        'description' => 'Array of tag names.',
                        'items'       => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'  => array(
                        'type'        => 'integer',
                        'description' => 'Created post ID',
                    ),
                    'url' => array(
                        'type'        => 'string',
                        'description' => 'Post permalink',
                    ),
                    'edit_url' => array(
                        'type'        => 'string',
                        'description' => 'Edit URL in admin',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Post status',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_data = array(
                    'post_title'   => sanitize_text_field( $input['title'] ),
                    'post_content' => wp_kses_post( $input['content'] ),
                    'post_status'  => $input['status'] ?? 'draft',
                    'post_type'    => $input['post_type'] ?? 'post',
                    'post_author'  => get_current_user_id(),
                );

                // Add optional excerpt
                if ( ! empty( $input['excerpt'] ) ) {
                    $post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
                }

                // Insert the post
                $post_id = wp_insert_post( $post_data, true );

                if ( is_wp_error( $post_id ) ) {
                    return $post_id;
                }

                // Set categories if provided
                if ( ! empty( $input['categories'] ) && is_array( $input['categories'] ) ) {
                    wp_set_post_categories( $post_id, $input['categories'] );
                }

                // Set tags if provided
                if ( ! empty( $input['tags'] ) && is_array( $input['tags'] ) ) {
                    wp_set_post_tags( $post_id, $input['tags'] );
                }

                return array(
                    'id'       => $post_id,
                    'url'      => get_permalink( $post_id ),
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'status'   => get_post_status( $post_id ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'publish_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => false,
            ),
        )
    );
}
