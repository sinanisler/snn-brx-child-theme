<?php 


/**
 * ----------------------------------------
 * Parent's Child Posts Count Tag
 * ----------------------------------------
 * Usage: {parents_child_posts_count}
 * Description:
 * - Returns the count of all child posts belonging to the current post's parent
 * - If the current post has no parent, returns 0
 */
add_filter( 'bricks/dynamic_tags_list', 'register_parents_child_posts_count_tag' );
function register_parents_child_posts_count_tag( $tags ) {
    $tags[] = [
        'name'  => '{parents_child_posts_count}',
        'label' => 'Parent\'s Child Posts Count',
        'group' => 'SNN',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_parents_child_posts_count_tag', 10, 3 );
function render_parents_child_posts_count_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{parents_child_posts_count}' ) === false ) {
        return $tag;
    }

    // If the post has no parent, return 0
    if ( ! $post->post_parent ) {
        return 0;
    }

    // Get the parent ID
    $parent_id = $post->post_parent;

    // Query for all child posts of the parent
    $args = [
        'post_parent'    => $parent_id,
        'post_type'      => $post->post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids', // Only get IDs for performance
    ];

    $child_posts = get_posts( $args );

    // Return the count
    return count( $child_posts );
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parents_child_posts_count_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parents_child_posts_count_tag_in_content', 10, 2 );
function render_parents_child_posts_count_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{parents_child_posts_count}' ) !== false ) {
        $count   = render_parents_child_posts_count_tag( '{parents_child_posts_count}', $post, $context );
        $content = str_replace( '{parents_child_posts_count}', $count, $content );
    }

    return $content;
}
