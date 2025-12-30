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
            'default' => false,
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
            'default' => 'post',
            'multiple' => true,
            'placeholder' => esc_html__( 'Select post types', 'snn' ),
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
            'default'  => [ 'publish' ],
            'placeholder' => esc_html__( 'Select status', 'snn' ),
        ];

        // ====================
        // PAGINATION
        // ====================

        $this->controls['posts_per_page'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Posts Per Page', 'snn' ),
            'type'    => 'number',
            'default' => 10,
            'description' => esc_html__( 'Use -1 to show all posts', 'snn' ),
        ];

        $this->controls['offset'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Offset', 'snn' ),
            'type'    => 'number',
            'default' => 0,
            'description' => esc_html__( 'Number of posts to skip', 'snn' ),
        ];

        $this->controls['paged'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Paged', 'snn' ),
            'type'    => 'number',
            'default' => 0,
            'description' => esc_html__( 'Page number (0 = current page)', 'snn' ),
        ];

        $this->controls['nopaging'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'No Paging', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
        ];

        $this->controls['ignore_sticky_posts'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Ignore Sticky Posts', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
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
            'default' => 'date',
        ];

        $this->controls['order'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Order', 'snn' ),
            'type'    => 'select',
            'options' => [
                'ASC'  => esc_html__( 'Ascending', 'snn' ),
                'DESC' => esc_html__( 'Descending', 'snn' ),
            ],
            'default' => 'DESC',
        ];

        $this->controls['meta_key'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Meta Key', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Enter meta key', 'snn' ),
            'description' => esc_html__( 'Required for meta_value orderby', 'snn' ),
        ];

        // ====================
        // SEARCH
        // ====================

        $this->controls['s'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Search', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Search keyword', 'snn' ),
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
        ];

        $this->controls['author_name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Author Name', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'user_nicename', 'snn' ),
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
        ];

        $this->controls['category_name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Category Slug', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'category-slug', 'snn' ),
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
        ];

        $this->controls['tag_id'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Tag ID', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Tag ID', 'snn' ),
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
        ];

        $this->controls['post__in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Include Post IDs', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated post IDs', 'snn' ),
        ];

        $this->controls['post__not_in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Exclude Post IDs', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated post IDs', 'snn' ),
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
        ];

        $this->controls['post_parent__in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Parent IDs (Include)', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated parent IDs to include', 'snn' ),
        ];

        $this->controls['post_parent__not_in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Post Parent IDs (Exclude)', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated parent IDs to exclude', 'snn' ),
        ];

        $this->controls['direct_children_only'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Direct Children Only', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
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
            'default' => '',
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
            'default' => '=',
        ];

        // ====================
        // PERFORMANCE
        // ====================

        $this->controls['no_found_rows'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'No Found Rows', 'snn' ),
            'type'    => 'checkbox',
            'default' => false,
            'description' => esc_html__( 'Improves performance if pagination not needed', 'snn' ),
        ];

        $this->controls['cache_results'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Cache Results', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
        ];

        $this->controls['update_post_meta_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Update Post Meta Cache', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
        ];

        $this->controls['update_post_term_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Update Post Term Cache', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
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
            'default'     => esc_html__( 'No posts matched your criteria.', 'snn' ),
            'placeholder' => esc_html__( 'No posts found', 'snn' ),
        ];
    }

    public function render() {
        $settings   = $this->settings;
        $no_wrapper = ! empty( $settings['no_wrapper'] );
        $wrapper_id = 'snn-query-' . $this->id;

        // Build WP_Query args from individual controls
        $query_args = $this->build_query_args( $settings );

        // Create WP_Query with the query args
        $posts_query = new \WP_Query( $query_args );

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
            $args['meta_key'] = $settings['meta_key'];
        }

        // SEARCH
        if ( ! empty( $settings['s'] ) ) {
            $args['s'] = $settings['s'];
        }

        // AUTHOR
        if ( ! empty( $settings['author'] ) ) {
            $args['author'] = $settings['author'];
        }

        if ( ! empty( $settings['author_name'] ) ) {
            $args['author_name'] = $settings['author_name'];
        }

        // CATEGORY
        if ( ! empty( $settings['cat'] ) ) {
            $args['cat'] = $settings['cat'];
        }

        if ( ! empty( $settings['category_name'] ) ) {
            $args['category_name'] = $settings['category_name'];
        }

        // TAG
        if ( ! empty( $settings['tag'] ) ) {
            $args['tag'] = $settings['tag'];
        }

        if ( ! empty( $settings['tag_id'] ) ) {
            $args['tag_id'] = intval( $settings['tag_id'] );
        }

        // SPECIFIC POSTS
        if ( ! empty( $settings['p'] ) ) {
            $p_value = $this->parse_dynamic_value( $settings['p'] );
            $args['p'] = intval( $p_value );
        }

        if ( ! empty( $settings['name'] ) ) {
            $args['name'] = $settings['name'];
        }

        if ( ! empty( $settings['post__in'] ) ) {
            // Convert comma-separated string to array of integers
            $post_in_str = $this->parse_dynamic_value( $settings['post__in'] );
            $post_ids = array_map( 'intval', array_filter( explode( ',', $post_in_str ) ) );
            if ( ! empty( $post_ids ) ) {
                $args['post__in'] = $post_ids;
            }
        }

        if ( ! empty( $settings['post__not_in'] ) ) {
            // Convert comma-separated string to array of integers
            $post_not_in_str = $this->parse_dynamic_value( $settings['post__not_in'] );
            $post_ids = array_map( 'intval', array_filter( explode( ',', $post_not_in_str ) ) );
            if ( ! empty( $post_ids ) ) {
                $args['post__not_in'] = $post_ids;
            }
        }

        // POST PARENT
        if ( isset( $settings['post_parent'] ) && $settings['post_parent'] !== '' ) {
            $parent_id = $this->parse_dynamic_value( $settings['post_parent'] );
            $args['post_parent'] = intval( $parent_id );
        }

        if ( ! empty( $settings['post_parent__in'] ) ) {
            $parent_ids_str = $this->parse_dynamic_value( $settings['post_parent__in'] );
            $parent_ids = array_map( 'intval', array_filter( explode( ',', $parent_ids_str ) ) );
            if ( ! empty( $parent_ids ) ) {
                $args['post_parent__in'] = $parent_ids;
            }
        }

        if ( ! empty( $settings['post_parent__not_in'] ) ) {
            $parent_ids_str = $this->parse_dynamic_value( $settings['post_parent__not_in'] );
            $parent_ids = array_map( 'intval', array_filter( explode( ',', $parent_ids_str ) ) );
            if ( ! empty( $parent_ids ) ) {
                $args['post_parent__not_in'] = $parent_ids;
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

        // ADVANCED JSON QUERIES
        if ( ! empty( $settings['tax_query'] ) ) {
            $tax_query = json_decode( $settings['tax_query'], true );
            if ( is_array( $tax_query ) ) {
                $args['tax_query'] = $tax_query;
            }
        }

        if ( ! empty( $settings['meta_query'] ) ) {
            $meta_query = json_decode( $settings['meta_query'], true );
            if ( is_array( $meta_query ) ) {
                $args['meta_query'] = $meta_query;
            }
        }

        if ( ! empty( $settings['date_query'] ) ) {
            $date_query = json_decode( $settings['date_query'], true );
            if ( is_array( $date_query ) ) {
                $args['date_query'] = $date_query;
            }
        }

        return $args;
    }

    /**
     * Parse dynamic values in controls
     * Supports: {post_id}, {current_post}, {author_id}, {term_id}
     */
    private function parse_dynamic_value( $value ) {
        if ( empty( $value ) ) {
            return $value;
        }

        // Handle {post_id} or {current_post}
        if ( strpos( $value, '{post_id}' ) !== false || strpos( $value, '{current_post}' ) !== false ) {
            $post_id = get_the_ID();
            $value = str_replace( [ '{post_id}', '{current_post}' ], $post_id, $value );
        }

        // Handle {author_id}
        if ( strpos( $value, '{author_id}' ) !== false ) {
            $author_id = get_post_field( 'post_author', get_the_ID() );
            $value = str_replace( '{author_id}', $author_id, $value );
        }

        // Handle {term_id} - get first term from current post's main taxonomy
        if ( strpos( $value, '{term_id}' ) !== false ) {
            $post_type = get_post_type();
            $taxonomies = get_object_taxonomies( $post_type );
            $term_id = 0;
            
            if ( ! empty( $taxonomies ) ) {
                $terms = get_the_terms( get_the_ID(), $taxonomies[0] );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $term_id = $terms[0]->term_id;
                }
            }
            
            $value = str_replace( '{term_id}', $term_id, $value );
        }

        // Handle {parent_id} - get parent of current post
        if ( strpos( $value, '{parent_id}' ) !== false ) {
            $parent_id = wp_get_post_parent_id( get_the_ID() );
            $value = str_replace( '{parent_id}', $parent_id, $value );
        }

        return $value;
    }
}
?>
