<?php
/**
 * SNN Abilities - Example Implementations
 * 
 * Collection of example abilities demonstrating various use cases
 * for the SNN Abilities API.
 * 
 * @package SNN_Abilities_Examples
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register all example abilities on API initialization
 */
add_action( 'snn_abilities_api_init', 'snn_register_example_abilities' );

function snn_register_example_abilities(): void {

    // =========================================================================
    // ABILITY 1: Site Information
    // =========================================================================
    snn_register_ability(
        'snn/site-info',
        array(
            'label'       => __( 'Get Site Info', 'snn-abilities' ),
            'description' => __( 'Retrieves basic information about the WordPress site.', 'snn-abilities' ),
            'category'    => 'site',
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'name'        => array( 
                        'type'        => 'string',
                        'description' => 'Site name/title',
                    ),
                    'description' => array( 
                        'type'        => 'string',
                        'description' => 'Site tagline/description',
                    ),
                    'url'         => array( 
                        'type'        => 'string',
                        'description' => 'Site home URL',
                    ),
                    'admin_email' => array( 
                        'type'        => 'string',
                        'description' => 'Administrator email address',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                return array(
                    'name'        => get_bloginfo( 'name' ),
                    'description' => get_bloginfo( 'description' ),
                    'url'         => get_bloginfo( 'url' ),
                    'admin_email' => get_bloginfo( 'admin_email' ),
                );
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 2: Get Posts
    // =========================================================================
    snn_register_ability(
        'snn/get-posts',
        array(
            'label'       => __( 'Get Posts', 'snn-abilities' ),
            'description' => __( 'Retrieves a list of posts with optional filtering.', 'snn-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type to retrieve (post, page, or custom).',
                        'default'     => 'post',
                    ),
                    'posts_per_page' => array(
                        'type'        => 'integer',
                        'description' => 'Number of posts to retrieve. Use -1 for all.',
                        'default'     => 10,
                        'minimum'     => -1,
                    ),
                    'category' => array(
                        'type'        => 'string',
                        'description' => 'Category slug to filter by.',
                    ),
                    'orderby' => array(
                        'type'        => 'string',
                        'description' => 'Field to order results by (date, title, modified).',
                        'enum'        => array( 'date', 'title', 'modified', 'rand' ),
                        'default'     => 'date',
                    ),
                    'order' => array(
                        'type'        => 'string',
                        'description' => 'Sort order (ASC or DESC).',
                        'enum'        => array( 'ASC', 'DESC' ),
                        'default'     => 'DESC',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'      => array( 
                            'type'        => 'integer',
                            'description' => 'Post ID',
                        ),
                        'title'   => array( 
                            'type'        => 'string',
                            'description' => 'Post title',
                        ),
                        'url'     => array( 
                            'type'        => 'string',
                            'description' => 'Post permalink',
                        ),
                        'excerpt' => array( 
                            'type'        => 'string',
                            'description' => 'Post excerpt (first 30 words)',
                        ),
                        'date'    => array( 
                            'type'        => 'string',
                            'description' => 'Post publication date',
                        ),
                        'author'  => array( 
                            'type'        => 'string',
                            'description' => 'Post author display name',
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'post_type'      => $input['post_type'] ?? 'post',
                    'posts_per_page' => $input['posts_per_page'] ?? 10,
                    'post_status'    => 'publish',
                    'orderby'        => $input['orderby'] ?? 'date',
                    'order'          => $input['order'] ?? 'DESC',
                );

                if ( ! empty( $input['category'] ) ) {
                    $args['category_name'] = sanitize_text_field( $input['category'] );
                }

                $posts  = get_posts( $args );
                $result = array();

                foreach ( $posts as $post ) {
                    $author = get_userdata( $post->post_author );
                    
                    $result[] = array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'url'     => get_permalink( $post ),
                        'excerpt' => wp_trim_words( $post->post_content, 30 ),
                        'date'    => get_the_date( 'Y-m-d H:i:s', $post ),
                        'author'  => $author ? $author->display_name : '',
                    );
                }

                return $result;
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 3: Create Post
    // =========================================================================
    snn_register_ability(
        'snn/create-post',
        array(
            'label'       => __( 'Create Post', 'snn-abilities' ),
            'description' => __( 'Creates a new post with the provided title and content.', 'snn-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'title', 'content' ),
                'properties' => array(
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'Post title.',
                        'minLength'   => 1,
                        'maxLength'   => 200,
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Post content (HTML allowed).',
                        'minLength'   => 1,
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Post status (draft, publish, pending).',
                        'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
                        'default'     => 'draft',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type (post, page).',
                        'enum'        => array( 'post', 'page' ),
                        'default'     => 'post',
                    ),
                    'excerpt' => array(
                        'type'        => 'string',
                        'description' => 'Optional post excerpt.',
                    ),
                    'categories' => array(
                        'type'        => 'array',
                        'description' => 'Array of category IDs.',
                        'items'       => array( 'type' => 'integer' ),
                    ),
                    'tags' => array(
                        'type'        => 'array',
                        'description' => 'Array of tag names.',
                        'items'       => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'  => array( 
                        'type'        => 'integer',
                        'description' => 'Created post ID',
                    ),
                    'url' => array( 
                        'type'        => 'string',
                        'description' => 'Post permalink',
                    ),
                    'edit_url' => array( 
                        'type'        => 'string',
                        'description' => 'Edit URL in admin',
                    ),
                    'status' => array( 
                        'type'        => 'string',
                        'description' => 'Post status',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_data = array(
                    'post_title'   => sanitize_text_field( $input['title'] ),
                    'post_content' => wp_kses_post( $input['content'] ),
                    'post_status'  => $input['status'] ?? 'draft',
                    'post_type'    => $input['post_type'] ?? 'post',
                    'post_author'  => get_current_user_id(),
                );

                // Add optional excerpt
                if ( ! empty( $input['excerpt'] ) ) {
                    $post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
                }

                // Insert the post
                $post_id = wp_insert_post( $post_data, true );

                if ( is_wp_error( $post_id ) ) {
                    return $post_id;
                }

                // Set categories if provided
                if ( ! empty( $input['categories'] ) && is_array( $input['categories'] ) ) {
                    wp_set_post_categories( $post_id, $input['categories'] );
                }

                // Set tags if provided
                if ( ! empty( $input['tags'] ) && is_array( $input['tags'] ) ) {
                    wp_set_post_tags( $post_id, $input['tags'] );
                }

                return array(
                    'id'       => $post_id,
                    'url'      => get_permalink( $post_id ),
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'status'   => get_post_status( $post_id ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'publish_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => false,
            ),
        )
    );

    // =========================================================================
    // ABILITY 4: Search Content
    // =========================================================================
    snn_register_ability(
        'snn/search',
        array(
            'label'       => __( 'Search Content', 'snn-abilities' ),
            'description' => __( 'Searches posts, pages, and custom post types.', 'snn-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'query' ),
                'properties' => array(
                    'query' => array(
                        'type'        => 'string',
                        'description' => 'Search query string.',
                        'minLength'   => 1,
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Limit search to specific post type.',
                        'default'     => 'any',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum results to return.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                    'offset' => array(
                        'type'        => 'integer',
                        'description' => 'Number of results to skip (for pagination).',
                        'default'     => 0,
                        'minimum'     => 0,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'total' => array(
                        'type'        => 'integer',
                        'description' => 'Total number of results found',
                    ),
                    'returned' => array(
                        'type'        => 'integer',
                        'description' => 'Number of results returned',
                    ),
                    'results' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type'       => 'object',
                            'properties' => array(
                                'id'      => array( 'type' => 'integer' ),
                                'title'   => array( 'type' => 'string' ),
                                'type'    => array( 'type' => 'string' ),
                                'url'     => array( 'type' => 'string' ),
                                'excerpt' => array( 'type' => 'string' ),
                                'date'    => array( 'type' => 'string' ),
                            ),
                        ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    's'              => sanitize_text_field( $input['query'] ),
                    'post_type'      => $input['post_type'] ?? 'any',
                    'posts_per_page' => $input['limit'] ?? 10,
                    'offset'         => $input['offset'] ?? 0,
                    'post_status'    => 'publish',
                );

                $query   = new WP_Query( $args );
                $results = array();

                foreach ( $query->posts as $post ) {
                    $results[] = array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'type'    => $post->post_type,
                        'url'     => get_permalink( $post ),
                        'excerpt' => wp_trim_words( $post->post_content, 20 ),
                        'date'    => get_the_date( 'Y-m-d H:i:s', $post ),
                    );
                }

                return array(
                    'total'    => $query->found_posts,
                    'returned' => count( $results ),
                    'results'  => $results,
                );
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 5: Get Current User
    // =========================================================================
    snn_register_ability(
        'snn/current-user',
        array(
            'label'       => __( 'Get Current User', 'snn-abilities' ),
            'description' => __( 'Retrieves information about the currently logged-in user.', 'snn-abilities' ),
            'category'    => 'users',
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'           => array( 
                        'type'        => 'integer',
                        'description' => 'User ID',
                    ),
                    'username'     => array( 
                        'type'        => 'string',
                        'description' => 'Login username',
                    ),
                    'email'        => array( 
                        'type'        => 'string',
                        'description' => 'User email address',
                    ),
                    'display_name' => array( 
                        'type'        => 'string',
                        'description' => 'Display name',
                    ),
                    'first_name'   => array( 
                        'type'        => 'string',
                        'description' => 'First name',
                    ),
                    'last_name'    => array( 
                        'type'        => 'string',
                        'description' => 'Last name',
                    ),
                    'roles'        => array( 
                        'type'        => 'array',
                        'description' => 'User roles',
                        'items'       => array( 'type' => 'string' ),
                    ),
                    'capabilities' => array( 
                        'type'        => 'object',
                        'description' => 'User capabilities',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $user = wp_get_current_user();

                if ( 0 === $user->ID ) {
                    return new WP_Error(
                        'not_logged_in',
                        'No user is currently logged in.',
                        array( 'status' => 401 )
                    );
                }

                return array(
                    'id'           => $user->ID,
                    'username'     => $user->user_login,
                    'email'        => $user->user_email,
                    'display_name' => $user->display_name,
                    'first_name'   => $user->first_name,
                    'last_name'    => $user->last_name,
                    'roles'        => $user->roles,
                    'capabilities' => $user->allcaps,
                );
            },
            'permission_callback' => 'is_user_logged_in',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 6: Update Post
    // =========================================================================
    snn_register_ability(
        'snn/update-post',
        array(
            'label'       => __( 'Update Post', 'snn-abilities' ),
            'description' => __( 'Updates an existing post with new content.', 'snn-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'ID of the post to update.',
                    ),
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'New post title.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'New post content.',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'New post status.',
                        'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
                    ),
                    'excerpt' => array(
                        'type'        => 'string',
                        'description' => 'New post excerpt.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'       => array( 'type' => 'integer' ),
                    'updated'  => array( 'type' => 'boolean' ),
                    'url'      => array( 'type' => 'string' ),
                    'modified' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id = absint( $input['post_id'] );
                
                // Check if post exists
                if ( ! get_post( $post_id ) ) {
                    return new WP_Error(
                        'post_not_found',
                        sprintf( 'Post with ID %d not found.', $post_id ),
                        array( 'status' => 404 )
                    );
                }

                $update_data = array( 'ID' => $post_id );

                if ( isset( $input['title'] ) ) {
                    $update_data['post_title'] = sanitize_text_field( $input['title'] );
                }

                if ( isset( $input['content'] ) ) {
                    $update_data['post_content'] = wp_kses_post( $input['content'] );
                }

                if ( isset( $input['status'] ) ) {
                    $update_data['post_status'] = sanitize_text_field( $input['status'] );
                }

                if ( isset( $input['excerpt'] ) ) {
                    $update_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
                }

                $result = wp_update_post( $update_data, true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                $post = get_post( $post_id );

                return array(
                    'id'       => $post_id,
                    'updated'  => true,
                    'url'      => get_permalink( $post ),
                    'modified' => $post->post_modified,
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 7: Delete Post
    // =========================================================================
    snn_register_ability(
        'snn/delete-post',
        array(
            'label'       => __( 'Delete Post', 'snn-abilities' ),
            'description' => __( 'Deletes a post (moves to trash or permanently deletes).', 'snn-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'post_id' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'ID of the post to delete.',
                    ),
                    'force_delete' => array(
                        'type'        => 'boolean',
                        'description' => 'Permanently delete instead of moving to trash.',
                        'default'     => false,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'      => array( 'type' => 'integer' ),
                    'deleted' => array( 'type' => 'boolean' ),
                    'status'  => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_id      = absint( $input['post_id'] );
                $force_delete = $input['force_delete'] ?? false;
                
                // Check if post exists
                if ( ! get_post( $post_id ) ) {
                    return new WP_Error(
                        'post_not_found',
                        sprintf( 'Post with ID %d not found.', $post_id ),
                        array( 'status' => 404 )
                    );
                }

                $result = wp_delete_post( $post_id, $force_delete );

                if ( ! $result ) {
                    return new WP_Error(
                        'delete_failed',
                        'Failed to delete post.',
                        array( 'status' => 500 )
                    );
                }

                return array(
                    'id'      => $post_id,
                    'deleted' => true,
                    'status'  => $force_delete ? 'permanently_deleted' : 'trashed',
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'delete_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => true,
                'idempotent'   => false,
            ),
        )
    );

    // =========================================================================
    // ABILITY 8: Get Categories
    // =========================================================================
    snn_register_ability(
        'snn/get-categories',
        array(
            'label'       => __( 'Get Categories', 'snn-abilities' ),
            'description' => __( 'Retrieves all post categories.', 'snn-abilities' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'hide_empty' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to hide categories with no posts.',
                        'default'     => false,
                    ),
                    'orderby' => array(
                        'type'        => 'string',
                        'description' => 'Field to order by.',
                        'enum'        => array( 'name', 'count', 'id' ),
                        'default'     => 'name',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'          => array( 'type' => 'integer' ),
                        'name'        => array( 'type' => 'string' ),
                        'slug'        => array( 'type' => 'string' ),
                        'description' => array( 'type' => 'string' ),
                        'count'       => array( 'type' => 'integer' ),
                        'url'         => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'taxonomy'   => 'category',
                    'hide_empty' => $input['hide_empty'] ?? false,
                    'orderby'    => $input['orderby'] ?? 'name',
                );

                $categories = get_terms( $args );

                if ( is_wp_error( $categories ) ) {
                    return $categories;
                }

                $result = array();
                foreach ( $categories as $category ) {
                    $result[] = array(
                        'id'          => $category->term_id,
                        'name'        => $category->name,
                        'slug'        => $category->slug,
                        'description' => $category->description,
                        'count'       => $category->count,
                        'url'         => get_term_link( $category ),
                    );
                }

                return $result;
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 9: Calculate
    // =========================================================================
    snn_register_ability(
        'snn/calculate',
        array(
            'label'       => __( 'Calculate', 'snn-abilities' ),
            'description' => __( 'Performs basic mathematical operations.', 'snn-abilities' ),
            'category'    => 'general',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'operation', 'a', 'b' ),
                'properties' => array(
                    'operation' => array(
                        'type'        => 'string',
                        'description' => 'Mathematical operation to perform.',
                        'enum'        => array( 'add', 'subtract', 'multiply', 'divide' ),
                    ),
                    'a' => array(
                        'type'        => 'number',
                        'description' => 'First number.',
                    ),
                    'b' => array(
                        'type'        => 'number',
                        'description' => 'Second number.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'operation' => array( 'type' => 'string' ),
                    'a'         => array( 'type' => 'number' ),
                    'b'         => array( 'type' => 'number' ),
                    'result'    => array( 'type' => 'number' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $a = floatval( $input['a'] );
                $b = floatval( $input['b'] );
                $operation = $input['operation'];

                $result = match ( $operation ) {
                    'add'      => $a + $b,
                    'subtract' => $a - $b,
                    'multiply' => $a * $b,
                    'divide'   => $b != 0 ? $a / $b : null,
                    default    => null,
                };

                if ( null === $result && $operation === 'divide' ) {
                    return new WP_Error(
                        'division_by_zero',
                        'Cannot divide by zero.',
                        array( 'status' => 400 )
                    );
                }

                return array(
                    'operation' => $operation,
                    'a'         => $a,
                    'b'         => $b,
                    'result'    => $result,
                );
            },
            'permission_callback' => '__return_true',
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );

    // =========================================================================
    // ABILITY 10: Get Media
    // =========================================================================
    snn_register_ability(
        'snn/get-media',
        array(
            'label'       => __( 'Get Media', 'snn-abilities' ),
            'description' => __( 'Retrieves media items from the media library.', 'snn-abilities' ),
            'category'    => 'media',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'mime_type' => array(
                        'type'        => 'string',
                        'description' => 'Filter by MIME type (image/jpeg, image/png, etc).',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Number of items to retrieve.',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'        => array( 'type' => 'integer' ),
                        'title'     => array( 'type' => 'string' ),
                        'url'       => array( 'type' => 'string' ),
                        'mime_type' => array( 'type' => 'string' ),
                        'filesize'  => array( 'type' => 'integer' ),
                        'width'     => array( 'type' => 'integer' ),
                        'height'    => array( 'type' => 'integer' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'posts_per_page' => $input['limit'] ?? 10,
                );

                if ( ! empty( $input['mime_type'] ) ) {
                    $args['post_mime_type'] = sanitize_text_field( $input['mime_type'] );
                }

                $attachments = get_posts( $args );
                $result = array();

                foreach ( $attachments as $attachment ) {
                    $metadata = wp_get_attachment_metadata( $attachment->ID );
                    
                    $result[] = array(
                        'id'        => $attachment->ID,
                        'title'     => $attachment->post_title,
                        'url'       => wp_get_attachment_url( $attachment->ID ),
                        'mime_type' => get_post_mime_type( $attachment->ID ),
                        'filesize'  => filesize( get_attached_file( $attachment->ID ) ),
                        'width'     => $metadata['width'] ?? null,
                        'height'    => $metadata['height'] ?? null,
                    );
                }

                return $result;
            },
            'permission_callback' => function() {
                return current_user_can( 'upload_files' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => true,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        )
    );
}