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
add_filter( 'bricks/dynamic_tags_list', 'snn_parents_register_child_posts_count_tag' );
function snn_parents_register_child_posts_count_tag( $tags ) {
    $tags[] = [
        'name'  => '{parents_child_posts_count}',
        'label' => 'Parent\'s Child Posts Count',
        'group' => 'SNN',
    ];
    return $tags;
}

// Recursive function to get ALL descendants - v2
function snn_parents_get_all_descendants( $post_id, $post_type ) {
    $all_descendants = array();
    
    // Get direct children
    $children = get_children( array(
        'post_parent'    => $post_id,
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'numberposts'    => -1,
    ) );
    
    foreach ( $children as $child_id => $child ) {
        // Add this child
        $all_descendants[$child_id] = $child;
        
        // Recursively get this child's children
        $grandchildren = snn_parents_get_all_descendants( $child_id, $post_type );
        $all_descendants = array_merge( $all_descendants, $grandchildren );
    }
    
    return $all_descendants;
}

add_filter( 'bricks/dynamic_data/render_tag', 'snn_parents_render_child_posts_count_tag', 10, 3 );
function snn_parents_render_child_posts_count_tag( $tag, $post, $context = 'text' ) {
    if ( strpos( $tag, '{parents_child_posts_count}' ) === false ) {
        return $tag;
    }

    // Get ALL descendants recursively
    $all_descendants = snn_parents_get_all_descendants( $post->ID, $post->post_type );
    return count( $all_descendants );
}

add_filter( 'bricks/dynamic_data/render_content', 'snn_parents_render_child_posts_count_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'snn_parents_render_child_posts_count_tag_in_content', 10, 2 );
function snn_parents_render_child_posts_count_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{parents_child_posts_count}' ) !== false ) {
        $count   = snn_parents_render_child_posts_count_tag( '{parents_child_posts_count}', $post, $context );
        $content = str_replace( '{parents_child_posts_count}', $count, $content );
    }

    return $content;
}
