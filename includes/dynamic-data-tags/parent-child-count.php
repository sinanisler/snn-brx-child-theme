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

    // DEBUG OUTPUT - FRONTEND
    $debug = '<div style="background:#000;color:#0f0;padding:20px;margin:10px 0;font-family:monospace;font-size:12px;border:2px solid #0f0;overflow:auto;max-height:500px;">';
    $debug .= '<strong>🔍 PARENT CHILD COUNT DEBUG (RECURSIVE)</strong><br><br>';
    $debug .= '📌 Current Post ID: ' . (is_object($post) ? $post->ID : 'NOT AN OBJECT') . '<br>';
    $debug .= '📄 Post Type: ' . (is_object($post) ? $post->post_type : 'N/A') . '<br>';
    $debug .= '📊 Post Title: ' . (is_object($post) && isset($post->post_title) ? $post->post_title : 'N/A') . '<br><br>';

    $debug .= '🔎 Getting ALL descendants recursively...<br><br>';
    
    // Get ALL descendants recursively
    $all_descendants = snn_parents_get_all_descendants( $post->ID, $post->post_type );
    
    $debug .= '📈 <strong>Total Descendants Found: ' . count($all_descendants) . '</strong><br><br>';
    if ( !empty($all_descendants) ) {
        $debug .= '🆔 All Descendant IDs: ' . implode(', ', array_keys($all_descendants)) . '<br><br>';
        $debug .= '<strong>Full List:</strong><br>';
        foreach ( $all_descendants as $desc_id => $desc ) {
            $depth = '';
            $current = $desc;
            while ( $current->post_parent != $post->ID && $current->post_parent != 0 ) {
                $depth .= '&nbsp;&nbsp;';
                $current = get_post( $current->post_parent );
            }
            $debug .= $depth . '└─ ID ' . $desc_id . ': ' . $desc->post_title . ' (parent: ' . $desc->post_parent . ')<br>';
        }
    } else {
        $debug .= '⚠️ No descendants found for this post!<br>';
    }
    
    $debug .= '</div>';
    $debug .= '<strong style="font-size:24px;">' . count( $all_descendants ) . '</strong>';

    return $debug;
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
