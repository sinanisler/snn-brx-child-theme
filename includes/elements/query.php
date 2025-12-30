<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use Bricks\Element;
use Bricks\Frontend;
use Bricks\Query;

class SNN_Query_Nestable extends Element {
    public $category     = 'snn';
    public $name         = 'snn-query-nestable';
    public $icon         = 'ti-layout-grid2';
    public $css_selector = '.snn-query-nestable-wrapper';
    public $nestable     = true;

    public function get_label() {
        return esc_html__( 'Query (Nestable)', 'snn' );
    }

    public function set_controls() {

        $this->controls['no_wrapper'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'No Wrapper', 'snn' ),
            'type'    => 'checkbox',
        ];

        // ====================
        // POST TYPE & STATUS
        // ====================
        
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $post_type_options = [ 'any' => esc_html__( 'Any', 'snn' ) ];
        foreach ( $post_types as $pt ) {
            $post_type_options[ $pt->name ] = $pt->label;
        }

        $this->controls['post_type'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Type', 'snn' ),
            'type'    => 'select',
            'options' => $post_type_options,
            'multiple' => true,
            'placeholder' => esc_html__( 'Select post types', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post_status'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Status', 'snn' ),
            'type'    => 'select',
            'options' => [
                'publish'    => esc_html__( 'Publish', 'snn' ),
                'pending'    => esc_html__( 'Pending', 'snn' ),
                'draft'      => esc_html__( 'Draft', 'snn' ),
                'auto-draft' => esc_html__( 'Auto-Draft', 'snn' ),
                'future'     => esc_html__( 'Future', 'snn' ),
                'private'    => esc_html__( 'Private', 'snn' ),
                'inherit'    => esc_html__( 'Inherit', 'snn' ),
                'trash'      => esc_html__( 'Trash', 'snn' ),
                'any'        => esc_html__( 'Any', 'snn' ),
            ],
            'multiple' => true,
            'placeholder' => esc_html__( 'Select status', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // PAGINATION
        // ====================

        $this->controls['posts_per_page'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Posts Per Page', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Use -1 to show all posts', 'snn' ),
        ];

        $this->controls['offset'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Offset', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Number of posts to skip', 'snn' ),
        ];

        $this->controls['paged'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Paged', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Page number (0 = current page)', 'snn' ),
        ];

        $this->controls['nopaging'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'No Paging', 'snn' ),
            'type'    => 'checkbox',
        ];

        $this->controls['ignore_sticky_posts'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Ignore Sticky Posts', 'snn' ),
            'type'    => 'checkbox',
        ];

        // ====================
        // ORDER & ORDERBY
        // ====================

        $this->controls['orderby'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Order By', 'snn' ),
            'type'    => 'select',
            'options' => [
                'none'           => esc_html__( 'None', 'snn' ),
                'ID'             => esc_html__( 'ID', 'snn' ),
                'author'         => esc_html__( 'Author', 'snn' ),
                'title'          => esc_html__( 'Title', 'snn' ),
                'name'           => esc_html__( 'Name (slug)', 'snn' ),
                'type'           => esc_html__( 'Post Type', 'snn' ),
                'date'           => esc_html__( 'Date', 'snn' ),
                'modified'       => esc_html__( 'Modified Date', 'snn' ),
                'parent'         => esc_html__( 'Parent', 'snn' ),
                'rand'           => esc_html__( 'Random', 'snn' ),
                'comment_count'  => esc_html__( 'Comment Count', 'snn' ),
                'relevance'      => esc_html__( 'Relevance (search)', 'snn' ),
                'menu_order'     => esc_html__( 'Menu Order', 'snn' ),
                'meta_value'     => esc_html__( 'Meta Value', 'snn' ),
                'meta_value_num' => esc_html__( 'Meta Value (Numeric)', 'snn' ),
                'post__in'       => esc_html__( 'post__in order', 'snn' ),
            ],
            'inline'  => true,
        ];

        $this->controls['order'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Order', 'snn' ),
            'type'    => 'select',
            'options' => [
                'ASC'  => esc_html__( 'Ascending', 'snn' ),
                'DESC' => esc_html__( 'Descending', 'snn' ),
            ],
            'inline'  => true,
        ];

        $this->controls['meta_key'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Meta Key', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Enter meta key', 'snn' ),
            'description' => esc_html__( 'Required for meta_value orderby', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // SEARCH
        // ====================

        $this->controls['s'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Search', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Search keyword', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // AUTHOR
        // ====================

        $this->controls['author'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Author ID', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1 or 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated author IDs', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['author_name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Author Name', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'user_nicename', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // CATEGORY
        // ====================

        $this->controls['cat'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Category', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Category ID(s)', 'snn' ),
            'description' => esc_html__( 'e.g., 1 or 1,2,3 or -1 to exclude', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['category_name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Category Slug', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'category-slug', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // TAG
        // ====================

        $this->controls['tag'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Tag Slug', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'tag-slug', 'snn' ),
            'description' => esc_html__( 'Comma-separated for OR, + for AND', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['tag_id'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Tag ID', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Tag ID', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // SPECIFIC POSTS
        // ====================

        $this->controls['p'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post ID', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Single post by ID', 'snn' ),
        ];

        $this->controls['name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Slug', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'post-slug', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post__in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Include Post IDs', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated post IDs', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post__not_in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Exclude Post IDs', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated post IDs', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // POST PARENT
        // ====================

        $this->controls['post_parent'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Parent ID', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Parent post ID', 'snn' ),
            'description' => esc_html__( 'Get children of this parent. Use 0 for top-level posts only. Use {post_id} for current post.', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post_parent__in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Parent IDs (Include)', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated parent IDs to include', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post_parent__not_in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Parent IDs (Exclude)', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated parent IDs to exclude', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['direct_children_only'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Direct Children Only', 'snn' ),
            'type'    => 'checkbox',
            'description' => esc_html__( 'When using post_parent, get only direct children (not grandchildren)', 'snn' ),
        ];

        // ====================
        // PASSWORD
        // ====================

        $this->controls['has_password'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Has Password', 'snn' ),
            'type'    => 'select',
            'options' => [
                ''      => esc_html__( 'Any', 'snn' ),
                'true'  => esc_html__( 'Has Password', 'snn' ),
                'false' => esc_html__( 'No Password', 'snn' ),
            ],
            'inline'  => true,
        ];

        // ====================
        // DATE QUERY
        // ====================

        $this->controls['year'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Year', 'snn' ),
            'type'    => 'number',
            'placeholder' => esc_html__( 'e.g., 2024', 'snn' ),
        ];

        $this->controls['monthnum'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Month', 'snn' ),
            'type'    => 'number',
            'placeholder' => esc_html__( '1-12', 'snn' ),
        ];

        $this->controls['day'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Day', 'snn' ),
            'type'    => 'number',
            'placeholder' => esc_html__( '1-31', 'snn' ),
        ];

        // ====================
        // COMMENT
        // ====================

        $this->controls['comment_count'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Comment Count', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Filter by comment count', 'snn' ),
        ];

        $this->controls['comment_count_compare'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Comment Count Compare', 'snn' ),
            'type'    => 'select',
            'options' => [
                '='  => '=',
                '!=' => '!=',
                '>'  => '>',
                '>=' => '>=',
                '<'  => '<',
                '<=' => '<=',
            ],
            'inline'  => true,
        ];

        // ====================
        // PERFORMANCE
        // ====================

        $this->controls['no_found_rows'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'No Found Rows', 'snn' ),
            'type'    => 'checkbox',
            'description' => esc_html__( 'Improves performance if pagination not needed', 'snn' ),
        ];

        $this->controls['cache_results'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Cache Results', 'snn' ),
            'type'    => 'checkbox',
        ];

        $this->controls['clear_query_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Clear Query Cache', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
            'description' => esc_html__( 'Clear WordPress cache before running query to ensure fresh results', 'snn' ),
        ];

        $this->controls['update_post_meta_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Update Post Meta Cache', 'snn' ),
            'type'    => 'checkbox',
        ];

        $this->controls['update_post_term_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Update Post Term Cache', 'snn' ),
            'type'    => 'checkbox',
        ];

        // ====================
        // ADVANCED / JSON
        // ====================

        $this->controls['tax_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Tax Query (JSON)', 'snn' ),
            'type'    => 'textarea',
            'placeholder' => esc_html__( 'Enter JSON for tax_query', 'snn' ),
            'description' => esc_html__( 'Advanced: JSON format for complex taxonomy queries', 'snn' ),
        ];

        $this->controls['meta_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Meta Query (JSON)', 'snn' ),
            'type'    => 'textarea',
            'placeholder' => esc_html__( 'Enter JSON for meta_query', 'snn' ),
            'description' => esc_html__( 'Advanced: JSON format for complex meta queries', 'snn' ),
        ];

        $this->controls['date_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Date Query (JSON)', 'snn' ),
            'type'    => 'textarea',
            'placeholder' => esc_html__( 'Enter JSON for date_query', 'snn' ),
            'description' => esc_html__( 'Advanced: JSON format for complex date queries', 'snn' ),
        ];

        // ====================
        // EMPTY MESSAGE
        // ====================

        $this->controls['empty_message'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Empty Message', 'snn' ),
            'type'        => 'text',
            'placeholder' => esc_html__( 'No posts found', 'snn' ),
            'inline'      => true,
        ];

        // ====================
        // DEBUG
        // ====================

        $this->controls['debug'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Debug', 'snn' ),
            'type'    => 'checkbox',
            'description' => esc_html__( 'Show debug information for troubleshooting', 'snn' ),
        ];
    }

    public function render() {
        $settings   = $this->settings;
        $no_wrapper = ! empty( $settings['no_wrapper'] );
        $wrapper_id = 'snn-query-' . $this->id;
        $debug_mode = ! empty( $settings['debug'] );

        // Clear query cache if enabled (default: true)
        $clear_cache = isset( $settings['clear_query_cache'] ) ? $settings['clear_query_cache'] : true;
        if ( $clear_cache ) {
            wp_cache_delete( 'last_changed', 'posts' );
        }

        // Build WP_Query args from individual controls
        $query_args = $this->build_query_args( $settings );

        // Create WP_Query with the query args
        $posts_query = new \WP_Query( $query_args );

        // Debug output
        if ( $debug_mode ) {
            echo '<div class="snn-query-debug" style="background: #f0f0f0; border: 2px solid #333; padding: 20px; margin: 20px 0; font-family: monospace; font-size: 12px;">';
            echo '<h3 style="margin-top: 0; color: #d00;">🐛 DEBUG MODE</h3>';
            
            echo '<h4>Settings (from controls):</h4>';
            echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
            echo esc_html( print_r( $settings, true ) );
            echo '</pre>';
            
            echo '<h4>Built Query Args:</h4>';
            echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
            echo esc_html( print_r( $query_args, true ) );
            echo '</pre>';
            
            echo '<h4>Query Results:</h4>';
            echo '<p><strong>Posts Found:</strong> ' . $posts_query->found_posts . '</p>';
            echo '<p><strong>Post Count:</strong> ' . $posts_query->post_count . '</p>';
            echo '<p><strong>Current Post ID:</strong> ' . get_the_ID() . '</p>';
            
            echo '<h4>SQL Query:</h4>';
            echo '<pre style="background: #fff; padding: 10px; overflow-x: auto; word-wrap: break-word; white-space: pre-wrap;">';
            echo esc_html( $posts_query->request );
            echo '</pre>';
            
            if ( $posts_query->have_posts() ) {
                echo '<h4>Found Posts:</h4>';
                echo '<ul>';
                foreach ( $posts_query->posts as $post ) {
                    echo '<li>ID: ' . $post->ID . ' | Title: ' . esc_html( $post->post_title ) . ' | Parent: ' . $post->post_parent . '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        }

        if ( $posts_query->have_posts() ) {
            // Output wrapper opening tag
            if ( ! $no_wrapper ) {
                $this->set_attribute( '_root', 'class', 'snn-query-nestable-wrapper' );
                $this->set_attribute( '_root', 'id', $wrapper_id );
                echo '<div ' . $this->render_attributes( '_root' ) . '>';
            }

            // Store original query for Bricks dynamic data
            global $wp_query;
            $original_query = $wp_query;
            
            // Temporarily replace global query for Bricks dynamic data to work
            $wp_query = $posts_query;

            // Loop through posts
            while ( $posts_query->have_posts() ) {
                $posts_query->the_post();
                
                // Set up postdata for dynamic data
                setup_postdata( get_the_ID() );
                
                // Render nested children for each post
                echo Frontend::render_children( $this );
            }

            // Reset post data
            wp_reset_postdata();
            
            // Restore original query
            $wp_query = $original_query;

            // Output wrapper closing tag
            if ( ! $no_wrapper ) {
                echo '</div>';
            }
        } else {
            // No posts found - render empty state
            if ( ! $no_wrapper ) {
                $this->set_attribute( '_root', 'class', 'snn-query-nestable-wrapper snn-query-empty' );
                $this->set_attribute( '_root', 'id', $wrapper_id );
                echo '<div ' . $this->render_attributes( '_root' ) . '>';
            }

            $empty_message = ! empty( $settings['empty_message'] ) ? $settings['empty_message'] : esc_html__( 'No posts matched your criteria.', 'snn' );
            echo '<p class="snn-query-empty-message">' . esc_html( $empty_message ) . '</p>';

            if ( ! $no_wrapper ) {
                echo '</div>';
            }
        }
    }

    /**
     * Render dynamic data tags in control values
     * Converts Bricks dynamic tags like {post_id} to their actual values
     */
    private function render_control_dynamic_data( $value, $post_id = null ) {
        if ( $value === null || $value === '' ) {
            return $value;
        }

        // If it's not a string, return as-is
        if ( ! is_string( $value ) ) {
            return $value;
        }

        // Check if value contains dynamic data markers (curly braces)
        if ( strpos( $value, '{' ) === false ) {
            return $value;
        }

        // Get current post ID for context if not provided
        if ( $post_id === null ) {
            $post_id = get_the_ID();
        }

        // Use Bricks' dynamic data rendering function
        if ( function_exists( 'bricks_render_dynamic_data' ) ) {
            return bricks_render_dynamic_data( $value, $post_id );
        }

        // Fallback: Try Bricks class method (older versions)
        if ( class_exists( '\Bricks\Integrations\Dynamic_Data\Providers' ) && method_exists( '\Bricks\Integrations\Dynamic_Data\Providers', 'render_content' ) ) {
            $post = get_post( $post_id );
            return \Bricks\Integrations\Dynamic_Data\Providers::render_content( $value, $post );
        }

        return $value;
    }

    /**
     * Build WP_Query arguments from individual control values
     */
    private function build_query_args( $settings ) {
        $args = [];

        // POST TYPE
        if ( ! empty( $settings['post_type'] ) ) {
            $args['post_type'] = $settings['post_type'];
        } else {
            $args['post_type'] = 'post';
        }

        // POST STATUS
        if ( ! empty( $settings['post_status'] ) ) {
            $args['post_status'] = $settings['post_status'];
        }

        // PAGINATION
        if ( isset( $settings['posts_per_page'] ) && $settings['posts_per_page'] !== '' ) {
            $args['posts_per_page'] = intval( $settings['posts_per_page'] );
        }

        if ( isset( $settings['offset'] ) && $settings['offset'] > 0 ) {
            $args['offset'] = intval( $settings['offset'] );
        }

        if ( isset( $settings['paged'] ) && $settings['paged'] > 0 ) {
            $args['paged'] = intval( $settings['paged'] );
        } elseif ( ! empty( $settings['paged'] ) && $settings['paged'] === 0 ) {
            // Use current page
            $args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
        }

        if ( ! empty( $settings['nopaging'] ) ) {
            $args['nopaging'] = true;
        }

        if ( isset( $settings['ignore_sticky_posts'] ) ) {
            $args['ignore_sticky_posts'] = ! empty( $settings['ignore_sticky_posts'] );
        }

        // ORDER
        if ( ! empty( $settings['orderby'] ) ) {
            $args['orderby'] = $settings['orderby'];
        }

        if ( ! empty( $settings['order'] ) ) {
            $args['order'] = $settings['order'];
        }

        if ( ! empty( $settings['meta_key'] ) ) {
            $args['meta_key'] = $this->render_control_dynamic_data( $settings['meta_key'] );
        }

        // SEARCH
        if ( ! empty( $settings['s'] ) ) {
            $args['s'] = $this->render_control_dynamic_data( $settings['s'] );
        }

        // AUTHOR
        if ( ! empty( $settings['author'] ) ) {
            $args['author'] = $this->render_control_dynamic_data( $settings['author'] );
        }

        if ( ! empty( $settings['author_name'] ) ) {
            $args['author_name'] = $this->render_control_dynamic_data( $settings['author_name'] );
        }

        // CATEGORY
        if ( ! empty( $settings['cat'] ) ) {
            $args['cat'] = $this->render_control_dynamic_data( $settings['cat'] );
        }

        if ( ! empty( $settings['category_name'] ) ) {
            $args['category_name'] = $this->render_control_dynamic_data( $settings['category_name'] );
        }

        // TAG
        if ( ! empty( $settings['tag'] ) ) {
            $args['tag'] = $this->render_control_dynamic_data( $settings['tag'] );
        }

        if ( ! empty( $settings['tag_id'] ) ) {
            $tag_id = $this->render_control_dynamic_data( $settings['tag_id'] );
            $args['tag_id'] = intval( $tag_id );
        }

        // SPECIFIC POSTS
        if ( ! empty( $settings['p'] ) ) {
            $p = $this->render_control_dynamic_data( $settings['p'] );
            $args['p'] = intval( $p );
        }

        if ( ! empty( $settings['name'] ) ) {
            $args['name'] = $this->render_control_dynamic_data( $settings['name'] );
        }

        if ( ! empty( $settings['post__in'] ) ) {
            // Render dynamic data first, then convert to array
            $post_in_value = $this->render_control_dynamic_data( $settings['post__in'] );
            $post_ids = array_map( 'intval', array_filter( explode( ',', $post_in_value ), function($val) {
                return trim( $val ) !== '';
            } ) );
            if ( ! empty( $post_ids ) ) {
                $args['post__in'] = $post_ids;
            }
        }

        if ( ! empty( $settings['post__not_in'] ) ) {
            // Render dynamic data first, then convert to array
            $post_not_in_value = $this->render_control_dynamic_data( $settings['post__not_in'] );
            $post_ids = array_map( 'intval', array_filter( explode( ',', $post_not_in_value ), function($val) {
                return trim( $val ) !== '';
            } ) );
            if ( ! empty( $post_ids ) ) {
                $args['post__not_in'] = $post_ids;
            }
        }

        // POST PARENT - CRITICAL: Render dynamic data before processing
        if ( isset( $settings['post_parent'] ) && $settings['post_parent'] !== '' ) {
            // Render dynamic data first (e.g., {post_id} -> 370)
            $post_parent_value = $this->render_control_dynamic_data( $settings['post_parent'] );
            $args['post_parent'] = intval( $post_parent_value );
        }

        if ( isset( $settings['post_parent__in'] ) && $settings['post_parent__in'] !== '' ) {
            // Render dynamic data first
            $parent_ids_str = $this->render_control_dynamic_data( $settings['post_parent__in'] );
            $parent_ids_str = trim( $parent_ids_str );

            if ( $parent_ids_str !== '' ) {
                $parent_ids = array_map( 'intval', array_map( 'trim', explode( ',', $parent_ids_str ) ) );
                if ( ! empty( $parent_ids ) || $parent_ids === [0] ) {
                    $args['post_parent__in'] = $parent_ids;
                }
            }
        }

        if ( isset( $settings['post_parent__not_in'] ) && $settings['post_parent__not_in'] !== '' ) {
            // Render dynamic data first
            $parent_ids_str = $this->render_control_dynamic_data( $settings['post_parent__not_in'] );
            $parent_ids_str = trim( $parent_ids_str );
            
            if ( $parent_ids_str !== '' ) {
                $parent_ids = array_map( 'intval', array_map( 'trim', explode( ',', $parent_ids_str ) ) );
                if ( ! empty( $parent_ids ) || $parent_ids === [0] ) {
                    $args['post_parent__not_in'] = $parent_ids;
                }
            }
        }

        // Direct children only - exclude grandchildren
        if ( ! empty( $settings['direct_children_only'] ) && isset( $args['post_parent'] ) && $args['post_parent'] > 0 ) {
            // Get all children of the parent
            $direct_children = get_children( [
                'post_parent' => $args['post_parent'],
                'post_type'   => $args['post_type'],
                'fields'      => 'ids',
            ] );
            
            if ( ! empty( $direct_children ) ) {
                // Override post_parent with post__in to get only direct children
                unset( $args['post_parent'] );
                $args['post__in'] = $direct_children;
            }
        }

        // PASSWORD
        if ( isset( $settings['has_password'] ) && $settings['has_password'] !== '' ) {
            if ( $settings['has_password'] === 'true' ) {
                $args['has_password'] = true;
            } elseif ( $settings['has_password'] === 'false' ) {
                $args['has_password'] = false;
            }
        }

        // DATE
        if ( ! empty( $settings['year'] ) ) {
            $args['year'] = intval( $settings['year'] );
        }

        if ( ! empty( $settings['monthnum'] ) ) {
            $args['monthnum'] = intval( $settings['monthnum'] );
        }

        if ( ! empty( $settings['day'] ) ) {
            $args['day'] = intval( $settings['day'] );
        }

        // COMMENT COUNT
        if ( isset( $settings['comment_count'] ) && $settings['comment_count'] !== '' ) {
            $comment_count = intval( $settings['comment_count'] );
            if ( ! empty( $settings['comment_count_compare'] ) ) {
                $args['comment_count'] = [
                    'value'   => $comment_count,
                    'compare' => $settings['comment_count_compare'],
                ];
            } else {
                $args['comment_count'] = $comment_count;
            }
        }

        // PERFORMANCE
        if ( ! empty( $settings['no_found_rows'] ) ) {
            $args['no_found_rows'] = true;
        }

        if ( isset( $settings['cache_results'] ) ) {
            $args['cache_results'] = ! empty( $settings['cache_results'] );
        }

        if ( isset( $settings['update_post_meta_cache'] ) ) {
            $args['update_post_meta_cache'] = ! empty( $settings['update_post_meta_cache'] );
        }

        if ( isset( $settings['update_post_term_cache'] ) ) {
            $args['update_post_term_cache'] = ! empty( $settings['update_post_term_cache'] );
        }

        // ADVANCED JSON QUERIES - Also render dynamic data in JSON
        if ( ! empty( $settings['tax_query'] ) ) {
            $tax_query_str = $this->render_control_dynamic_data( $settings['tax_query'] );
            $tax_query = json_decode( $tax_query_str, true );
            if ( is_array( $tax_query ) ) {
                $args['tax_query'] = $tax_query;
            }
        }

        if ( ! empty( $settings['meta_query'] ) ) {
            $meta_query_str = $this->render_control_dynamic_data( $settings['meta_query'] );
            $meta_query = json_decode( $meta_query_str, true );
            if ( is_array( $meta_query ) ) {
                $args['meta_query'] = $meta_query;
            }
        }

        if ( ! empty( $settings['date_query'] ) ) {
            $date_query_str = $this->render_control_dynamic_data( $settings['date_query'] );
            $date_query = json_decode( $date_query_str, true );
            if ( is_array( $date_query ) ) {
                $args['date_query'] = $date_query;
            }
        }

        return $args;
    }
}
?>