<?php
/**
 * Edit Block Content Ability
 *
 * This ability provides surgical editing capabilities for WordPress block content.
 * Unlike generate-block-pattern which creates complete new sections, this ability
 * allows precise modifications of existing blocks.
 *
 * CAPABILITIES:
 * - Insert blocks at specific positions (between existing blocks)
 * - Replace specific block ranges
 * - Delete blocks by index range
 * - Find and replace blocks by content or type
 * - Update specific attributes without recreating blocks
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_edit_block_content_ability' );
function snn_register_edit_block_content_ability() {
    wp_register_ability(
        'snn/edit-block-content',
        array(
            'label'       => __( 'Edit Block Content', 'snn' ),
            'description' => __( 'Surgically edit existing block content with precision. Use this for targeted modifications without affecting other blocks.

WHEN TO USE THIS ABILITY:
- User wants to update/modify a SPECIFIC section or block (e.g., "update the pricing section", "change the hero button text")
- User wants to insert content BETWEEN existing blocks (e.g., "add a testimonial after the services section")
- User wants to delete specific blocks (e.g., "remove the third FAQ item")
- User wants to find and replace text/blocks (e.g., "replace all Read More buttons with Get Started")

AVAILABLE EDIT ACTIONS:

1. INSERT_AT_INDEX - Insert new blocks at a specific position
   - Use when user says: "add X after/before Y", "insert X at position N"
   - Parameters: insert_index (required), content (required)
   - Example: Insert testimonial after services section

2. REPLACE_BLOCK_RANGE - Replace a range of blocks
   - Use when user says: "replace the X section", "change blocks 3-5"
   - Parameters: start_index (required), end_index (required), content (required)
   - Example: Replace pricing table with new one

3. DELETE_BLOCKS - Delete blocks by index range
   - Use when user says: "remove X section", "delete the third item"
   - Parameters: start_index (required), end_index (optional, defaults to start_index)
   - Example: Remove FAQ item

4. FIND_AND_REPLACE_SECTION - Find section by heading and replace it
   - Use when user says: "update the About Us section", "change the Services heading"
   - Parameters: section_identifier (required), content (required)
   - Example: Update "Who We Are" section content
   - NOTE: This finds the heading block matching identifier and replaces it + all blocks until next heading

5. FIND_AND_REPLACE_TEXT - Find and replace text within blocks
   - Use when user says: "change all mentions of X to Y", "replace the word X"
   - Parameters: find_text (required), replace_text (required), block_types (optional array)
   - Example: Replace "Read More" with "Learn More" in all buttons

PARAMETERS:
- post_id: (integer, required) Post ID to edit
- action: (string, required) Edit action type (insert_at_index, replace_block_range, delete_blocks, find_and_replace_section, find_and_replace_text)
- content: (string) Block markup to insert/replace (required for insert/replace actions)
- insert_index: (integer) Position to insert blocks (0 = start, -1 = end)
- start_index: (integer) Start index for range operations
- end_index: (integer) End index for range operations
- section_identifier: (string) Heading text to find for section replacement
- find_text: (string) Text to find for text replacement
- replace_text: (string) Text to replace with
- block_types: (array) Optional: limit operations to specific block types (e.g., ["core/button", "core/paragraph"])

RETURN FORMAT:
Returns client_command for real-time editor updates with:
- type: "edit_block_content"
- action: the edit action performed
- All relevant parameters for JavaScript execution
- requires_client_update: true

EXAMPLES:

1. Insert testimonial after hero:
{
  "post_id": 123,
  "action": "insert_at_index",
  "insert_index": 1,
  "content": "<!-- wp:quote -->...</quote><!-- /wp:quote -->"
}

2. Replace pricing section (blocks 5-8):
{
  "post_id": 123,
  "action": "replace_block_range",
  "start_index": 5,
  "end_index": 8,
  "content": "<!-- wp:group -->...new pricing...<!-- /wp:group -->"
}

3. Delete third FAQ item:
{
  "post_id": 123,
  "action": "delete_blocks",
  "start_index": 2
}

4. Update About Us section:
{
  "post_id": 123,
  "action": "find_and_replace_section",
  "section_identifier": "About Us",
  "content": "<!-- wp:heading --><h2>About Us</h2><!-- /wp:heading --><!-- wp:paragraph --><p>New content</p><!-- /wp:paragraph -->"
}

5. Replace button text:
{
  "post_id": 123,
  "action": "find_and_replace_text",
  "find_text": "Read More",
  "replace_text": "Learn More",
  "block_types": ["core/button"]
}

CRITICAL WORDPRESS BLOCK RULES:
- All HTML tags must be properly closed
- Block comments must have opening and closing pairs
- JSON attributes must be valid (no trailing commas)
- Escape % symbols as %% in inline styles
- See generate-block-pattern ability for full block syntax rules', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id', 'action' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Post ID to edit.',
                    ),
                    'action' => array(
                        'type'        => 'string',
                        'enum'        => array( 'insert_at_index', 'replace_block_range', 'delete_blocks', 'find_and_replace_section', 'find_and_replace_text' ),
                        'description' => 'Type of edit action to perform.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Block markup content (required for insert/replace actions).',
                    ),
                    'insert_index' => array(
                        'type'        => 'integer',
                        'description' => 'Position to insert blocks (0 = start, -1 = end).',
                    ),
                    'start_index' => array(
                        'type'        => 'integer',
                        'description' => 'Start index for range operations.',
                    ),
                    'end_index' => array(
                        'type'        => 'integer',
                        'description' => 'End index for range operations (inclusive).',
                    ),
                    'section_identifier' => array(
                        'type'        => 'string',
                        'description' => 'Heading text to find for section replacement.',
                    ),
                    'find_text' => array(
                        'type'        => 'string',
                        'description' => 'Text to find for replacement.',
                    ),
                    'replace_text' => array(
                        'type'        => 'string',
                        'description' => 'Text to replace with.',
                    ),
                    'block_types' => array(
                        'type'        => 'array',
                        'items'       => array( 'type' => 'string' ),
                        'description' => 'Optional: limit operations to specific block types.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether edit was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'blocks_affected' => array(
                        'type'        => 'integer',
                        'description' => 'Number of blocks affected',
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
                $post_id = $input['post_id'];
                $action = $input['action'];

                // Check permissions
                if ( ! current_user_can( 'edit_posts' ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit posts.', 'snn' ) );
                }

                // Verify post exists and user can edit it
                $post = get_post( $post_id );
                if ( ! $post ) {
                    return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                }
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                }

                // Build client command based on action
                $client_command = array(
                    'type'    => 'edit_block_content',
                    'action'  => $action,
                    'post_id' => $post_id,
                );

                $message = '';
                $blocks_affected = 0;

                // Handle different actions
                switch ( $action ) {
                    case 'insert_at_index':
                        if ( ! isset( $input['content'] ) || ! isset( $input['insert_index'] ) ) {
                            return new WP_Error( 'missing_params', __( 'content and insert_index are required for insert_at_index action.', 'snn' ) );
                        }
                        $client_command['content'] = $input['content'];
                        $client_command['insert_index'] = $input['insert_index'];

                        // Count blocks in content
                        preg_match_all( '/<!-- wp:([a-z-\/]+)/', $input['content'], $matches );
                        $blocks_affected = count( $matches[0] );

                        $position = $input['insert_index'] === -1 ? 'end' : "position {$input['insert_index']}";
                        $message = sprintf( 'Inserted %d block(s) at %s. Remember to save your changes.', $blocks_affected, $position );
                        break;

                    case 'replace_block_range':
                        if ( ! isset( $input['content'] ) || ! isset( $input['start_index'] ) || ! isset( $input['end_index'] ) ) {
                            return new WP_Error( 'missing_params', __( 'content, start_index, and end_index are required for replace_block_range action.', 'snn' ) );
                        }
                        $client_command['content'] = $input['content'];
                        $client_command['start_index'] = $input['start_index'];
                        $client_command['end_index'] = $input['end_index'];

                        preg_match_all( '/<!-- wp:([a-z-\/]+)/', $input['content'], $matches );
                        $blocks_affected = count( $matches[0] );

                        $range = $input['start_index'] === $input['end_index'] ? "block {$input['start_index']}" : "blocks {$input['start_index']}-{$input['end_index']}";
                        $message = sprintf( 'Replaced %s with %d new block(s). Remember to save your changes.', $range, $blocks_affected );
                        break;

                    case 'delete_blocks':
                        if ( ! isset( $input['start_index'] ) ) {
                            return new WP_Error( 'missing_params', __( 'start_index is required for delete_blocks action.', 'snn' ) );
                        }
                        $client_command['start_index'] = $input['start_index'];
                        $client_command['end_index'] = isset( $input['end_index'] ) ? $input['end_index'] : $input['start_index'];

                        $blocks_affected = $client_command['end_index'] - $client_command['start_index'] + 1;
                        $range = $client_command['start_index'] === $client_command['end_index'] ? "block {$client_command['start_index']}" : "blocks {$client_command['start_index']}-{$client_command['end_index']}";
                        $message = sprintf( 'Deleted %s (%d block(s)). Remember to save your changes.', $range, $blocks_affected );
                        break;

                    case 'find_and_replace_section':
                        if ( ! isset( $input['content'] ) || ! isset( $input['section_identifier'] ) ) {
                            return new WP_Error( 'missing_params', __( 'content and section_identifier are required for find_and_replace_section action.', 'snn' ) );
                        }
                        $client_command['content'] = $input['content'];
                        $client_command['section_identifier'] = $input['section_identifier'];

                        preg_match_all( '/<!-- wp:([a-z-\/]+)/', $input['content'], $matches );
                        $blocks_affected = count( $matches[0] );

                        $message = sprintf( 'Updated "%s" section with %d block(s). Remember to save your changes.', $input['section_identifier'], $blocks_affected );
                        break;

                    case 'find_and_replace_text':
                        if ( ! isset( $input['find_text'] ) || ! isset( $input['replace_text'] ) ) {
                            return new WP_Error( 'missing_params', __( 'find_text and replace_text are required for find_and_replace_text action.', 'snn' ) );
                        }
                        $client_command['find_text'] = $input['find_text'];
                        $client_command['replace_text'] = $input['replace_text'];
                        if ( isset( $input['block_types'] ) ) {
                            $client_command['block_types'] = $input['block_types'];
                        }

                        $message = sprintf( 'Replaced "%s" with "%s". Remember to save your changes.', $input['find_text'], $input['replace_text'] );
                        break;

                    default:
                        return new WP_Error( 'invalid_action', __( 'Invalid action specified.', 'snn' ) );
                }

                // Return instruction for JavaScript to execute
                return array(
                    'success'                   => true,
                    'message'                   => $message,
                    'blocks_affected'           => $blocks_affected,
                    'requires_client_update'    => true,
                    'client_command'            => $client_command,
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'meta' => array(
                'show_in_rest'              => true,
                'readonly'                  => false,
                'destructive'               => false,
                'idempotent'                => true,
                'requires_client_execution' => true,
            ),
        )
    );
}
