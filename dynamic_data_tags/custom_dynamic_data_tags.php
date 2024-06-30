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









// {taxonomy_color_tag:category}
// Taxonomy color custom field 
// The tag can be used with any taxonomy, e.g., {taxonomy_color_tag:category} or 
// {taxonomy_color_tag:custom_taxonomy_name}, to fetch the color.
// Adds the new tag 'taxonomy_color_tag' to the Bricks Builder dynamic tags list with dynamic term support.
add_filter( 'bricks/dynamic_tags_list', 'add_taxonomy_color_tag_to_builder' );
function add_taxonomy_color_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => 'taxonomy_color_tag',
        'label' => 'Taxonomy Color Tag',
        'group' => 'Custom Data',
    ];
    return $tags;
}

// Retrieves the custom field 'color' for any given taxonomy.
function get_taxonomy_color($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (!empty($terms) && !is_wp_error($terms)) {
        $term_id = $terms[0]->term_id; // Get the first term ID
        $color = get_term_meta($term_id, 'color', true);
        return $color ? $color : '#000000'; // Default to black if no color is set
    }
    return '#000000'; // Default to black if no terms found
}

// Renders the 'taxonomy_color_tag' tag by fetching the color for the specified taxonomy.
add_filter( 'bricks/dynamic_data/render_tag', 'render_taxonomy_color_tag', 10, 3 );
function render_taxonomy_color_tag( $tag, $post, $context = 'text' ) {
    if ( strpos($tag, 'taxonomy_color_tag:') === 0 ) {
        $taxonomy = explode(':', $tag)[1];
        return get_taxonomy_color($post->ID, $taxonomy);
    }
    return $tag;
}

// Applies dynamic taxonomy colors to render the color in content.
add_filter( 'bricks/dynamic_data/render_content', 'render_taxonomy_color_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_taxonomy_color_in_content', 10, 2 );
function render_taxonomy_color_in_content( $content, $post, $context = 'text' ) {
    if ( preg_match_all('/{taxonomy_color_tag:([^}]+)}/', $content, $matches) ) {
        foreach ($matches[1] as $index => $taxonomy) {
            $color = get_taxonomy_color($post->ID, $taxonomy);
            $content = str_replace($matches[0][$index], $color, $content);
        }
    }
    return $content;
}




