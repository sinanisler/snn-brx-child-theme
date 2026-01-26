<?php
/**
 * Insert Block Content Ability
 * Registers the snn/insert-block-content ability for the WordPress Abilities API
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_insert_block_content_ability' );
function snn_register_insert_block_content_ability() {
    wp_register_ability(
        'snn/insert-block-content',
        array(
            'label'       => __( 'Insert Block Content', 'snn' ),
            'description' => __( 'Inserts new content as blocks into the WordPress block editor (Gutenberg). Supports inserting paragraphs, headings, lists, and other block types. Use this to add content to the current post being edited in the block editor. The block will be inserted at the end of the post or at a specific position if specified.

IMPORTANT: Generate valid HTML to prevent broken blocks. Examples:
- Paragraph: <p>Your text content here</p>
- Heading: <h2>Section Heading</h2>
- List: <ul><li>Item 1</li><li>Item 2</li></ul>
- Quote: <blockquote><p>Quote text</p></blockquote>

Avoid: unclosed tags, empty blocks, loose text without wrapper tags. See includes/ai/docs/block-generation-rules.md', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id', 'content' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to insert content into.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Content to insert (plain text or valid HTML with ALL tags properly closed). For lists use: <ul><li>Item</li></ul>. For headings: <h2>Title</h2>. Avoid empty tags or unclosed elements.',
                        'minLength'   => 1,
                    ),
                    'block_type' => array(
                        'type'        => 'string',
                        'description' => 'Block type to create (paragraph, heading, list, etc.).',
                        'enum'        => array( 'paragraph', 'heading', 'list', 'quote', 'code', 'html' ),
                        'default'     => 'paragraph',
                    ),
                    'position' => array(
                        'type'        => 'string',
                        'description' => 'Where to insert: "end" (default) or "start".',
                        'enum'        => array( 'end', 'start' ),
                        'default'     => 'end',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether insertion was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'block_data' => array(
                        'type'        => 'object',
                        'description' => 'Data about the inserted block',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                $content = $input['content'];
                $block_type = $input['block_type'] ?? 'paragraph';
                $position = $input['position'] ?? 'end';

                // Verify post exists
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                }

                // Create block based on type
                $block_html = '';
                switch ( $block_type ) {
                    case 'heading':
                        $block_html = '<!-- wp:heading --><h2>' . wp_kses_post( $content ) . '</h2><!-- /wp:heading -->';
                        break;
                    case 'list':
                        $items = explode( "\n", $content );
                        $list_items = '';
                        foreach ( $items as $item ) {
                            if ( trim( $item ) ) {
                                $list_items .= '<li>' . wp_kses_post( trim( $item ) ) . '</li>';
                            }
                        }
                        $block_html = '<!-- wp:list --><ul>' . $list_items . '</ul><!-- /wp:list -->';
                        break;
                    case 'quote':
                        $block_html = '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . wp_kses_post( $content ) . '</p></blockquote><!-- /wp:quote -->';
                        break;
                    case 'code':
                        $block_html = '<!-- wp:code --><pre class="wp-block-code"><code>' . esc_html( $content ) . '</code></pre><!-- /wp:code -->';
                        break;
                    case 'html':
                        $block_html = '<!-- wp:html -->' . wp_kses_post( $content ) . '<!-- /wp:html -->';
                        break;
                    case 'paragraph':
                    default:
                        $block_html = '<!-- wp:paragraph --><p>' . wp_kses_post( $content ) . '</p><!-- /wp:paragraph -->';
                        break;
                }

                // Get current content
                $current_content = $post->post_content;

                // Insert block at specified position
                if ( $position === 'start' ) {
                    $new_content = $block_html . "\n\n" . $current_content;
                } else {
                    $new_content = $current_content . "\n\n" . $block_html;
                }

                // Update post
                $result = wp_update_post( array(
                    'ID'           => $post_id,
                    'post_content' => $new_content,
                ), true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                return array(
                    'success'  => true,
                    'message'  => sprintf( __( 'Successfully inserted %s block at %s of post.', 'snn' ), $block_type, $position ),
                    'block_data' => array(
                        'type'     => $block_type,
                        'position' => $position,
                        'content'  => $content,
                    ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
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
