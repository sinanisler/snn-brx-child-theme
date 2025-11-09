<?php 


/**
 * ----------------------------------------
 * Parent ID Tag
 * ----------------------------------------
 * Usage: {parent_id}
 * Description: Returns the parent post/page ID. If no parent exists, returns the current post ID.
 */
add_filter( 'bricks/dynamic_tags_list', 'register_parent_id_tag' );
function register_parent_id_tag( $tags ) {
    $tags[] = [
        'name'  => '{parent_id}',
        'label' => 'Parent ID',
        'group' => 'SNN',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_parent_id_tag', 10, 3 );
function render_parent_id_tag( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{parent_id}' ) {
        return $tag;
    }

    // If post has a parent, return parent ID, otherwise return current post ID
    if ( $post->post_parent ) {
        return $post->post_parent;
    }

    return $post->ID;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parent_id_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parent_id_tag_in_content', 10, 2 );
function render_parent_id_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{parent_id}' ) !== false ) {
        $parent_id = render_parent_id_tag( '{parent_id}', $post, $context );
        $content   = str_replace( '{parent_id}', $parent_id, $content );
    }

    return $content;
}
