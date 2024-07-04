<?php 









// {taxonomy_term_slug:taxonomy}
// Use {taxonomy_term_slug:category} to insert slugs of categories assigned to the post.
// Adds a new dynamic tag 'taxonomy_term_slug' to the Bricks Builder tags list.
add_filter( 'bricks/dynamic_tags_list', 'add_taxonomy_term_slug_tag_to_builder' );
function add_taxonomy_term_slug_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{taxonomy_term_slug:category}',
        'label' => 'Taxonomy Term Slug',
        'group' => 'SNN BRX',
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
        'name'  => '{taxonomy_color_tag}',
        'label' => 'Taxonomy Color Tag',
        'group' => 'SNN BRX',
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









// {current_user_first_name}
// Get current user first_name or get user_login name as default
// Adds a new tag 'current_user_first_name' to the Bricks Builder dynamic tags list.
add_filter( 'bricks/dynamic_tags_list', 'add_current_user_first_name_tag_to_builder' );
function add_current_user_first_name_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{current_user_first_name}',
        'label' => 'Current User First Name',
        'group' => 'SNN BRX',
    ];

    return $tags;
}

// Retrieves the first name of the current user or falls back to the username.
function get_current_user_first_name() {
    $current_user = wp_get_current_user();
    if ( $current_user->ID !== 0 ) {
        $first_name = get_user_meta( $current_user->ID, 'first_name', true );
        return !empty( $first_name ) ? $first_name : $current_user->user_login;
    }
    return '';
}

// Renders the 'current_user_first_name' tag by fetching the current user's first name or username.
add_filter( 'bricks/dynamic_data/render_tag', 'render_current_user_first_name_tag', 10, 3 );
function render_current_user_first_name_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === 'current_user_first_name' ) {
        return get_current_user_first_name();
    }
    return $tag;
}

// Replaces the '{current_user_first_name}' placeholder in content with the current user's first name or username.
add_filter( 'bricks/dynamic_data/render_content', 'render_current_user_first_name_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_current_user_first_name_in_content', 10, 2 );
function render_current_user_first_name_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{current_user_first_name}' ) !== false ) {
        $first_name = get_current_user_first_name();
        $content = str_replace( '{current_user_first_name}', $first_name, $content );
    }
    return $content;
}








// {estimated_post_read_time}
// Adds a new dynamic tag 'estimated_post_read_time' to Bricks Builder for displaying estimated post read time.
add_filter( 'bricks/dynamic_tags_list', 'add_estimated_post_read_time_tag_to_builder' );

function add_estimated_post_read_time_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{estimated_post_read_time}',
        'label' => 'Estimated Post Read Time',
        'group' => 'SNN BRX',
    ];

    return $tags;
}

// Calculates the estimated read time based on word count. Assumes an average reading speed of 200 words per minute.
function calculate_estimated_read_time() {
    global $post;
    $word_count = str_word_count( strip_tags( $post->post_content ) );
    $read_time = ceil( $word_count / 200 ); // Average reading speed: 200 words per minute
    return $read_time;
}

// Renders the 'estimated_post_read_time' tag by fetching the estimated read time.
add_filter( 'bricks/dynamic_data/render_tag', 'render_estimated_post_read_time_tag', 10, 3 );
function render_estimated_post_read_time_tag( $tag, $post, $context = 'text' ) {
    if ( $tag === 'estimated_post_read_time' ) {
        return calculate_estimated_read_time();
    }
    return $tag;
}

// Replaces the '{estimated_post_read_time}' placeholder in content with the actual estimated read time.
add_filter( 'bricks/dynamic_data/render_content', 'render_estimated_post_read_time_in_content', 10, 3 );
add_filter( 'bricks/frontend/render_data', 'render_estimated_post_read_time_in_content', 10, 2 );
function render_estimated_post_read_time_in_content( $content, $post, $context = 'text' ) {
    if ( strpos( $content, '{estimated_post_read_time}' ) !== false ) {
        $read_time = calculate_estimated_read_time();
        $content = str_replace( '{estimated_post_read_time}', $read_time, $content );
    }
    return $content;
}






































// {current_author_id}
// Adds a new dynamic tag 'current_author_id' to Bricks Builder for displaying current author ID.
// checks if the current page is an author archive page using is_author() and retrieves the author ID 
// with get_queried_object_id() if it is. Otherwise, it returns an empty string.
add_filter( 'bricks/dynamic_tags_list', 'add_current_author_id_tag_to_builder' );

function add_current_author_id_tag_to_builder( $tags ) {
    $tags[] = [
        'name'  => '{current_author_id}',
        'label' => 'Current Author ID',
        'group' => 'SNN BRX',
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















// {post_count:post_type_name}  
// {post_count:post_type_name:taxonomy_name:term_slug}
// Get post count for post type or post type with taxonomy count
// Adds a new tag 'post_count' to the Bricks Builder dynamic tags list with support for any post type and taxonomy.
add_filter('bricks/dynamic_tags_list', 'add_post_count_tag_to_builder');
function add_post_count_tag_to_builder($tags) {
    $tags[] = [
        'name'  => '{post_count:post_type_name}',
        'label' => 'Post Count',
        'group' => 'SNN BRX',
    ];
    return $tags;
}

// Retrieves the total count of a specified post type, optionally filtered by a taxonomy term.
function get_post_count($post_type, $taxonomy = '', $term_slug = '') {
    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'fields'         => 'ids'
    ];
    if (!empty($taxonomy) && !empty($term_slug)) {
        $args['tax_query'] = [
            [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $term_slug,
            ],
        ];
    }
    $query = new WP_Query($args);
    return $query->found_posts;
}

// Renders the 'post_count' tag by fetching the count dynamically.
add_filter('bricks/dynamic_data/render_tag', 'render_post_count_tag', 10, 3);
function render_post_count_tag($tag, $post, $context = 'text') {
    if (strpos($tag, 'post_count:') === 0) {
        $parts = explode(':', $tag);
        $post_type = $parts[1] ?? '';
        $taxonomy = $parts[2] ?? '';
        $term_slug = $parts[3] ?? '';
        return get_post_count($post_type, $taxonomy, $term_slug);
    }
    return $tag;
}

// Applies dynamic tags to render the post count in content.
add_filter('bricks/dynamic_data/render_content', 'render_post_count_in_content', 10, 3);
add_filter('bricks/frontend/render_data', 'render_post_count_in_content', 10, 2);
function render_post_count_in_content($content, $post, $context = 'text') {
    if (preg_match_all('/{post_count:([\w-]+)(?::([\w-]+):([\w-]+))?}/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $post_type = $match[1];
            $taxonomy = $match[2] ?? '';
            $term_slug = $match[3] ?? '';
            $post_count = get_post_count($post_type, $taxonomy, $term_slug);
            $content = str_replace($match[0], $post_count, $content);
        }
    }
    return $content;
}


