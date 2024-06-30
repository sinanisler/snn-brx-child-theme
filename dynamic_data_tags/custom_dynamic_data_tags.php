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







// {taxonomy_term_slug:taxonomy}
// Use {taxonomy_term_slug:category} to insert slugs of categories assigned to the post.
// Adds a new dynamic tag 'taxonomy_term_slug' to the Bricks Builder tags list.
add_filter( 'bricks/dynamic_tags_list', 'add_taxonomy_term_slug_tag_to_builder' );
function add_taxonomy_term_slug_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => 'taxonomy_term_slug',
        'label' => 'Taxonomy Term Slug',
        'group' => 'Posts Data',
    ];

    return $tags;
}

// Retrieves the slugs of taxonomy terms associated with a post for a specific taxonomy.
function get_taxonomy_term_slug( $post, $taxonomy ) {
    if ( $post && isset( $post->ID ) && !empty($taxonomy) ) {
        $terms = get_the_terms( $post->ID, $taxonomy );
        if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
            $term_slugs = array_map(function($term) {
                return $term->slug;
            }, $terms);
            return implode(' ', $term_slugs);
        }
    }
    return '';
}

// Renders the 'taxonomy_term_slug' tag by fetching the taxonomy term slugs based on the specified taxonomy.
add_filter( 'bricks/dynamic_data/render_tag', 'render_taxonomy_term_slug_tag', 10, 3 );
function render_taxonomy_term_slug_tag( $tag, $post, $context = 'text' ) {
    if ( strpos($tag, 'taxonomy_term_slug') === 0 ) {
        $parts = explode(':', $tag);
        $taxonomy = isset($parts[1]) ? $parts[1] : 'category'; // Default to 'category' if not specified.
        return get_taxonomy_term_slug( $post, $taxonomy );
    }
    return $tag;
}

// Replaces placeholders like '{taxonomy_term_slug:taxonomy}' in content with actual taxonomy term slugs.
add_filter( 'bricks/dynamic_data/render_content', 'render_taxonomy_term_slug_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_taxonomy_term_slug_in_content', 10, 2 );
function render_taxonomy_term_slug_in_content( $content, $post, $context = 'text' ) {
    if ( preg_match_all('/\{taxonomy_term_slug:([a-zA-Z0-9_\-]+)\}/', $content, $matches, PREG_SET_ORDER) ) {
        foreach ($matches as $match) {
            $taxonomy = $match[1];
            $slug = get_taxonomy_term_slug( $post, $taxonomy );
            $content = str_replace($match[0], $slug, $content);
        }
    }
    return $content;
}












