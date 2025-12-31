<?php


/**
 * ----------------------------------------
 * Parent Detection Tag
 * ----------------------------------------
 * Usage: {parent_detection}
 * Description: Outputs "grandparent" if the post has no parent, or "child" if it has any parent(s).
 */
add_filter( 'bricks/dynamic_tags_list', 'register_parent_detection_tag' );
function register_parent_detection_tag( $tags ) {
    $tags[] = [
        'name'  => '{parent_detection}',
        'label' => 'Parent Detection',
        'group' => 'SNN',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_parent_detection_tag', 10, 3 );
function render_parent_detection_tag( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{parent_detection}' ) {
        return $tag;
    }

    // Check if the current post has any ancestors
    $ancestors = get_post_ancestors( $post );

    // If there are no ancestors, this is a top-level post (grandparent)
    // Otherwise, it's a child (regardless of depth)
    return empty( $ancestors ) ? 'grandparent' : 'child';
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parent_detection_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parent_detection_tag_in_content', 10, 2 );
function render_parent_detection_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{parent_detection}' ) !== false ) {
        $parent_detection = render_parent_detection_tag( '{parent_detection}', $post, $context );
        $content          = str_replace( '{parent_detection}', $parent_detection, $content );
    }

    return $content;
}
