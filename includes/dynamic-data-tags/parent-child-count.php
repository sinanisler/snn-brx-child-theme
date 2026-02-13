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

    // DEBUG OUTPUT - FRONTEND
    $debug = '<div style="background:#000;color:#0f0;padding:20px;margin:10px 0;font-family:monospace;font-size:12px;border:2px solid #0f0;">';
    $debug .= '<strong>🔍 PARENT CHILD COUNT DEBUG</strong><br><br>';
    $debug .= '📌 Current Post ID: ' . (is_object($post) ? $post->ID : 'NOT AN OBJECT') . '<br>';
    $debug .= '📄 Post Type: ' . (is_object($post) ? $post->post_type : 'N/A') . '<br>';
    $debug .= '📊 Post Title: ' . (is_object($post) && isset($post->post_title) ? $post->post_title : 'N/A') . '<br><br>';

    // Get all children of the CURRENT post
    $args = array(
        'post_parent'    => $post->ID,  // Use current post ID, not parent!
        'post_type'      => $post->post_type,
        'post_status'    => 'publish',
        'numberposts'    => -1,
    );
    
    $debug .= '🔎 Looking for children where post_parent = ' . $post->ID . '<br><br>';
    
    $children = get_children( $args );
    
    $debug .= '📈 <strong>Children Found: ' . count($children) . '</strong><br>';
    if ( !empty($children) ) {
        $debug .= '🆔 Children IDs: ' . implode(', ', array_keys($children)) . '<br>';
        foreach ( $children as $child_id => $child ) {
            $debug .= '&nbsp;&nbsp;- ID ' . $child_id . ': ' . $child->post_title . '<br>';
        }
    } else {
        $debug .= '⚠️ No children found for this post!<br>';
    }
    
    $debug .= '</div>';
    $debug .= '<strong style="font-size:24px;">' . count( $children ) . '</strong>';

    return $debug;
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
