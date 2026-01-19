<?php


/**
 * ----------------------------------------
 * Parent Detection Tag
 * ----------------------------------------
 * Usage: {parent_detection} or {parent_detection:depth}
 *
 * Options:
 * - {parent_detection} → Outputs "grandparent" if the post has no parent, or "child" if it has any parent(s)
 * - {parent_detection:depth} → Outputs depth_0, depth_1, depth_2, etc. based on hierarchy level
 */
add_filter( 'bricks/dynamic_tags_list', 'register_parent_detection_tag' );
function register_parent_detection_tag( $tags ) {
    $tags[] = [
        'name'  => '{parent_detection}',
        'label' => 'Parent Detection',
        'group' => 'SNN',
    ];
    $tags[] = [
        'name'  => '{parent_detection:depth}',
        'label' => 'Parent Detection - Depth',
        'group' => 'SNN',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_parent_detection_tag', 10, 3 );
function render_parent_detection_tag( $tag, $post, $context = 'text' ) {
    // Check if the current post has any ancestors
    $ancestors = get_post_ancestors( $post );

    if ( $tag === '{parent_detection}' ) {
        // If there are no ancestors, this is a top-level post (grandparent)
        // Otherwise, it's a child (regardless of depth)
        return empty( $ancestors ) ? 'grandparent' : 'child';
    }

    if ( $tag === '{parent_detection:depth}' ) {
        // Return depth_N where N is the number of ancestors
        $depth = count( $ancestors );
        return 'depth_' . $depth;
    }

    return $tag;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parent_detection_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parent_detection_tag_in_content', 10, 2 );
function render_parent_detection_tag_in_content( $content, $post, $context = 'text' ) {
    // Handle {parent_detection:depth} first (more specific)
    if ( strpos( $content, '{parent_detection:depth}' ) !== false ) {
        $depth_value = render_parent_detection_tag( '{parent_detection:depth}', $post, $context );
        $content     = str_replace( '{parent_detection:depth}', $depth_value, $content );
    }

    // Handle {parent_detection}
    if ( strpos( $content, '{parent_detection}' ) !== false ) {
        $parent_detection = render_parent_detection_tag( '{parent_detection}', $post, $context );
        $content          = str_replace( '{parent_detection}', $parent_detection, $content );
    }

    return $content;
}
