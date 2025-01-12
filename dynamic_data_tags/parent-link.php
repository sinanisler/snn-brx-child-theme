<?php 


/**
 * ----------------------------------------
 * Parent Link Tag
 * ----------------------------------------
 * Usage: {parent_link}
 * Description: Displays the parent post/page's title as a clickable link on a child post/page.
 */
add_filter( 'bricks/dynamic_tags_list', 'register_parent_link_tag' );
function register_parent_link_tag( $tags ) {
    $tags[] = [
        'name'  => '{parent_link}',
        'label' => 'Parent Title and Link',
        'group' => 'SNN',
    ];
    return $tags;
}

add_filter( 'bricks/dynamic_data/render_tag', 'render_parent_link_tag', 10, 3 );
function render_parent_link_tag( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{parent_link}' ) { // Include braces
        return $tag;
    }

    if ( $post->post_parent ) {
        $parent_post = get_post( $post->post_parent );

        if ( $parent_post ) {
            $parent_title = get_the_title( $parent_post );
            $parent_link  = get_permalink( $parent_post );

            return '<a href="' . esc_url( $parent_link ) . '" title="' . esc_url( $parent_title ) . '">' . esc_html( $parent_title ) . '</a>';
        }
    }

    return '';
}

add_filter( 'bricks/dynamic_data/render_content', 'render_parent_link_tag_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_parent_link_tag_in_content', 10, 2 );
function render_parent_link_tag_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{parent_link}' ) !== false ) {
        $parent_link = render_parent_link_tag( '{parent_link}', $post, $context );
        $content     = str_replace( '{parent_link}', $parent_link, $content );
    }

    return $content;
}
