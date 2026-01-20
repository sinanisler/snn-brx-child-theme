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
    if ( $tag === '{parent_detection}' ) {
        // If it's an author archive, output "author"
        if ( is_author() ) {
            return 'author';
        }

        // If it's any other archive, output "archive"
        if ( is_archive() ) {
            return 'archive';
        }

        // For singular posts/pages, check hierarchy
        $ancestors = get_post_ancestors( $post );
        return empty( $ancestors ) ? 'grandparent' : 'child';
    }

    if ( $tag === '{parent_detection:depth}' ) {
        // Archives don't have depth
        if ( is_archive() ) {
            return 'depth_0';
        }

        // Return depth_N where N is the number of ancestors
        $ancestors = get_post_ancestors( $post );
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
