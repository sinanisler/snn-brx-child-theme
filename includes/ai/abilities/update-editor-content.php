<?php
/**
 * Update Editor Content Ability
 * 
 * This ability updates the block editor content in real-time without requiring a page refresh.
 * It works by sending instructions to the JavaScript side to update the editor state directly.
 * 
 * This is the PREFERRED method when the user is actively editing a post in the block editor,
 * as it provides immediate visual feedback and allows for iteration without losing work.
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_update_editor_content_ability' );
function snn_register_update_editor_content_ability() {
    wp_register_ability(
        'snn/update-editor-content',
        array(
            'label'       => __( 'Update Editor Content (Real-time)', 'snn' ),
            'description' => __( 'Updates the block editor content in real-time without page refresh. USE THIS when the user is actively editing a post in the block editor. This provides immediate visual feedback and allows iteration. For posts not currently being edited, use snn/replace-post-content instead. Use the snn/replace-post-content if user asks about editing a section not the all content itself. Supports smart section updates to find and replace existing content sections.

CRITICAL: Generate clean, valid HTML to prevent broken blocks. Follow these rules:
1. ALWAYS close all HTML tags properly (e.g., <p>Text</p> not <p>Text)
2. Use proper nesting (e.g., <p><strong>Bold</strong></p> not <strong><p>Bold</p></strong>)
3. Lists MUST use <ul> or <ol> with <li> children: <ul><li>Item 1</li><li>Item 2</li></ul>
4. NO empty tags or loose text - wrap everything in block elements
5. Encode special characters (&amp; for &, &lt; for <, etc.)
6. Images need alt attributes: <img src="url.jpg" alt="Description">

GOOD Examples:
- Simple section: <h2>About Us</h2><p>We are a company focused on innovation.</p>
- With list: <h3>Benefits</h3><ul><li>Quality</li><li>Service</li><li>Value</li></ul>
- Multiple paragraphs: <p>First paragraph.</p><p>Second paragraph with <strong>bold text</strong>.</p>

BAD Examples (will cause broken blocks):
- Unclosed tag: <p>Text<p>Another paragraph</p>
- Invalid list: <ul>Item 1 Item 2</ul>
- Loose text: Some text <p>A paragraph</p> More text
- Empty blocks: <p></p>

See full documentation at: includes/ai/docs/block-generation-rules.md', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'content', 'action' ),
                'properties' => array(
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'The new content (clean HTML or plain text). Will be converted to blocks automatically using wp.blocks.parse(). MUST be valid HTML with ALL tags properly closed. Example: <h2>Section Title</h2><p>Paragraph text with <strong>bold</strong> content.</p><ul><li>List item 1</li><li>List item 2</li></ul>',
                        'minLength'   => 1,
                    ),
                    'action' => array(
                        'type'        => 'string',
                        'enum'        => array( 'replace', 'append', 'prepend', 'preview', 'update_section' ),
                        'description' => 'How to apply the content: replace (replace all), append (add to end), prepend (add to start), preview (show without applying), update_section (find and replace a specific section by heading)',
                        'default'     => 'replace',
                    ),
                    'section_identifier' => array(
                        'type'        => 'string',
                        'description' => 'Section heading to find and replace (e.g., "Why Choose Us", "About Us"). Required when action is "update_section". The system will search for this heading and replace it and all content until the next heading.',
                    ),
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Optional: Post ID to verify we\'re editing the right post. If omitted, uses current editor post.',
                    ),
                    'save_immediately' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to auto-save the changes immediately.',
                        'default'     => false,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether update was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'action_type' => array(
                        'type'        => 'string',
                        'description' => 'Type of action performed',
                    ),
                    'requires_client_update' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether this requires JavaScript execution',
                    ),
                    'client_command' => array(
                        'type'        => 'object',
                        'description' => 'Command to be executed by JavaScript',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $content = $input['content'];
                $action = $input['action'] ?? 'replace';
                $post_id = $input['post_id'] ?? null;
                $save_immediately = $input['save_immediately'] ?? false;
                $section_identifier = $input['section_identifier'] ?? null;

                // Validate action
                $valid_actions = array( 'replace', 'append', 'prepend', 'preview', 'update_section' );
                if ( ! in_array( $action, $valid_actions, true ) ) {
                    return new WP_Error( 'invalid_action', __( 'Invalid action type.', 'snn' ) );
                }

                // Validate section_identifier when action is update_section
                if ( $action === 'update_section' && empty( $section_identifier ) ) {
                    return new WP_Error( 'missing_section_identifier', __( 'section_identifier is required when action is "update_section".', 'snn' ) );
                }

                // Check permissions
                if ( ! current_user_can( 'edit_posts' ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit posts.', 'snn' ) );
                }

                // If post_id provided, verify it exists and user can edit it
                if ( $post_id ) {
                    $post = get_post( $post_id );
                    if ( ! $post ) {
                        return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                    }
                    if ( ! current_user_can( 'edit_post', $post_id ) ) {
                        return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                    }
                }

                // Calculate word count for feedback
                $word_count = str_word_count( wp_strip_all_tags( $content ) );

                // Build client command
                $client_command = array(
                    'type' => 'update_editor_content',
                    'content' => $content,
                    'action' => $action,
                    'post_id' => $post_id,
                    'save_immediately' => $save_immediately,
                    'word_count' => $word_count,
                );

                // Add section_identifier for update_section action
                if ( $action === 'update_section' && $section_identifier ) {
                    $client_command['section_identifier'] = $section_identifier;
                }

                // Customize message based on action
                if ( $action === 'update_section' ) {
                    $message = sprintf(
                        __( 'Will update "%s" section in editor (%d words). The system will find and replace this section intelligently.', 'snn' ),
                        $section_identifier,
                        $word_count
                    );
                } else {
                    $message = sprintf(
                        __( 'Content ready to update in editor (%d words). This will update the editor in real-time.', 'snn' ),
                        $word_count
                    );
                }

                // Return instruction for JavaScript to execute
                return array(
                    'success'  => true,
                    'message'  => $message,
                    'action_type' => $action,
                    'section_updated' => $action === 'update_section' ? $section_identifier : null,
                    'requires_client_update' => true,
                    'client_command' => $client_command,
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,  // Not destructive since it doesn't save immediately
                'idempotent'   => true,
                'requires_client_execution' => true,  // Special flag for JavaScript execution
            ),
        )
    );
}
