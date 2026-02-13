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

add_filter( 'bricks/dynamic_data/render_tag', 'render_parents_child_posts_count_tag', 20, 3 );
function render_parents_child_posts_count_tag( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{parents_child_posts_count}' ) {
        return $tag;
    }

    $current_post_id = get_the_ID();

    if ( ! $current_post_id ) {
        return 0;
    }

    $current_post = get_post( $current_post_id );

    if ( ! $current_post || ! $current_post->post_parent ) {
        return 0;
    }

    // Get all children of the parent using get_children()
    $args = [
        'post_parent'    => $current_post->post_parent,
        'post_type'      => $current_post->post_type,
        'post_status'    => 'publish',
        'numberposts'    => -1,
    ];

    $children = get_children( $args );

    return count( $children );
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parents_child_posts_count_tag_in_content', 20, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parents_child_posts_count_tag_in_content', 20, 2 );
function render_parents_child_posts_count_tag_in_content( $content, $post, $context = 'text' ) {
    if ( ! is_string( $content ) ) {
        return $content;
    }

    if ( strpos( $content, '{parents_child_posts_count}' ) !== false ) {
        $count   = render_parents_child_posts_count_tag( '{parents_child_posts_count}', $post, $context );
        $content = str_replace( '{parents_child_posts_count}', $count, $content );
    }

    return $content;
}
