<?php 


/**
 * ----------------------------------------
 * Parent ID Tag
 * ----------------------------------------
 * Usage: {parent_id} or {parent_id:only_parent_id}
 * Description:
 * - {parent_id} returns the top-level parent (grandparent) ID
 * - {parent_id:only_parent_id} returns only the direct parent ID (one level up)
 * - If no parent exists, returns the current post ID
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
    if ( strpos( $tag, '{parent_id' ) === false ) {
        return $tag;
    }

    // Check if only_parent_id parameter is used
    $only_direct_parent = strpos( $tag, ':only_parent_id' ) !== false;

    if ( $only_direct_parent ) {
        // Return only the direct parent (one level up)
        if ( $post->post_parent ) {
            return $post->post_parent;
        }
        return $post->ID;
    }

    // Default behavior: Get the top-level parent (grandparent)
    if ( $post->post_parent ) {
        $parent_id = $post->post_parent;

        // Traverse up to find the top-level parent
        while ( $parent_post = get_post( $parent_id ) ) {
            if ( $parent_post->post_parent ) {
                $parent_id = $parent_post->post_parent;
            } else {
                break;
            }
        }

        return $parent_id;
    }

    return $post->ID;
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parent_id_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parent_id_tag_in_content', 10, 2 );
function render_parent_id_tag_in_content( $content, $post, $context = 'text' ) {
    // Handle both {parent_id} and {parent_id:only_parent_id}
    if ( strpos( $content, '{parent_id' ) !== false ) {
        // Replace {parent_id:only_parent_id}
        if ( strpos( $content, '{parent_id:only_parent_id}' ) !== false ) {
            $parent_id = render_parent_id_tag( '{parent_id:only_parent_id}', $post, $context );
            $content   = str_replace( '{parent_id:only_parent_id}', $parent_id, $content );
        }

        // Replace {parent_id}
        if ( strpos( $content, '{parent_id}' ) !== false ) {
            $parent_id = render_parent_id_tag( '{parent_id}', $post, $context );
            $content   = str_replace( '{parent_id}', $parent_id, $content );
        }
    }

    return $content;
}
