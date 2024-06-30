<?php 







// {current_author_id}
// Adds a new dynamic tag 'current_author_id' to Bricks Builder for displaying current author ID.
add_filter( 'bricks/dynamic_tags_list', 'add_current_author_id_tag_to_builder' );

function add_current_author_id_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => 'current_author_id',
        'label' => 'Current Author ID',
        'group' => 'Author Data',
    ];

    return $tags;
}

// Retrieves the current author ID on an author archive page.
function get_current_author_id() {
    return is_author() ? get_queried_object_id() : '';
}

// Renders the 'current_author_id' tag by fetching the current author ID.
add_filter( 'bricks/dynamic_data/render_tag', 'render_current_author_id_tag', 10, 3 );
function render_current_author_id_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === 'current_author_id' ) {
        return get_current_author_id();
    }
    return $tag;
}

// Replaces the '{current_author_id}' placeholder in content with the actual current author ID.
add_filter( 'bricks/dynamic_data/render_content', 'render_current_author_id_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_current_author_id_in_content', 10, 2 );
function render_current_author_id_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{current_author_id}' ) !== false ) {
        $author_id = get_current_author_id();
        $content = str_replace( '{current_author_id}', $author_id, $content );
    }
    return $content;
}












// Adds a new tag 'taxonomy_term_slug' to the Bricks Builder dynamic tags list.
add_filter( 'bricks/dynamic_tags_list', 'add_taxonomy_term_slug_tag_to_builder' );
function add_taxonomy_term_slug_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => 'taxonomy_term_slug',
        'label' => 'Taxonomy Term Slug',
        'group' => 'Posts Data',
    ];

    return $tags;
}

// Retrieves the slugs of taxonomy terms associated with a post.
function get_taxonomy_term_slug( $post ) {
    if ( $post && isset( $post->ID ) ) {
          $terms = get_the_terms( $post->ID, 'category' ); // Change 'category' tax if necessary.
          if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
               $category_slugs = array();
               foreach ( $terms as $term ) {
                    $category_slugs[] = $term->slug;
               }
               return implode(' ', $category_slugs);
          }
    }
    return '';
}

// Renders the 'taxonomy_term_slug' tag by fetching the taxonomy term slugs of a post.
add_filter( 'bricks/dynamic_data/render_tag', 'render_taxonomy_term_slug_tag', 10, 3 );
function render_taxonomy_term_slug_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === 'taxonomy_term_slug' ) {
        return get_taxonomy_term_slug( $post );
    }
    return $tag;
}

// Replaces the '{taxonomy_term_slug}' placeholder in content with actual taxonomy term slugs.
add_filter( 'bricks/dynamic_data/render_content', 'render_taxonomy_term_slug_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_taxonomy_term_slug_in_content', 10, 2 );
function render_taxonomy_term_slug_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{taxonomy_term_slug}' ) !== false ) {
        $slug = get_taxonomy_term_slug( $post );
        $content = str_replace( '{taxonomy_term_slug}', $slug, $content );
    }
    return $content;
}

