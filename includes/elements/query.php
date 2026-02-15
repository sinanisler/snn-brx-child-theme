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

    // ADDED: Static stack to track post context in nested queries
    private static $post_context_stack = [];
    
    // ADDED: Store the query loop object IDs for Bricks dynamic data context
    private static $query_loop_post_id = null;

    public function get_label() {
        return esc_html__( 'Query (Nestable)', 'snn' );
    }
    
    /**
     * Hook into Bricks' dynamic data system to use our loop post ID
     */
    public function __construct( $element = [] ) {
        parent::__construct( $element );
        
        // Filter the post ID that Bricks uses for dynamic data rendering
        // Use very high priority to override other filters
        add_filter( 'bricks/dynamic_data/post_id', [ $this, 'get_loop_post_id' ], 999, 3 );
        
        // Also hook into the content rendering to ensure dynamic tags work
        add_filter( 'bricks/frontend/render_data', [ $this, 'force_loop_context_in_content' ], 5, 3 );
    }
    
    /**
     * Return the current loop post ID for Bricks dynamic data
     */
    public function get_loop_post_id( $post_id, $element = null, $context = 'text' ) {
        // If we're inside our query loop, use our loop post ID
        if ( self::$query_loop_post_id !== null ) {
            return self::$query_loop_post_id;
        }
        return $post_id;
    }
    
    /**
     * Force loop context when rendering child element content
     */
    public function force_loop_context_in_content( $content, $post_or_element, $element = null ) {
        // If we're in a query loop, make sure global $post is set correctly
        if ( self::$query_loop_post_id !== null ) {
            global $post;
            // Ensure the global $post matches our loop post ID
            if ( ! $post || $post->ID !== self::$query_loop_post_id ) {
                $post = get_post( self::$query_loop_post_id );
                if ( $post ) {
                    setup_postdata( $post );
                }
            }
        }
        return $content;
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
            'label'   => esc_html__( 'post_type', 'snn' ),
            'type'    => 'select',
            'options' => $post_type_options,
            'multiple' => true,
            'placeholder' => esc_html__( 'Select post types', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post_status'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'post_status', 'snn' ),
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
            'label'   => esc_html__( 'posts_per_page', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Use -1 to show all posts', 'snn' ),
        ];

        $this->controls['offset'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'offset', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Number of posts to skip', 'snn' ),
        ];

        $this->controls['paged'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'paged', 'snn' ),
            'type'    => 'number',
            'description' => esc_html__( 'Page number (0 = current page)', 'snn' ),
        ];

        $this->controls['nopaging'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'nopaging', 'snn' ),
            'type'    => 'checkbox',
        ];

        $this->controls['ignore_sticky_posts'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'ignore_sticky_posts', 'snn' ),
            'type'    => 'checkbox',
        ];

        // ====================
        // ORDER & ORDERBY
        // ====================

        $this->controls['orderby'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'orderby', 'snn' ),
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
            'label'   => esc_html__( 'order', 'snn' ),
            'type'    => 'select',
            'options' => [
                'ASC'  => esc_html__( 'Ascending', 'snn' ),
                'DESC' => esc_html__( 'Descending', 'snn' ),
            ],
            'inline'  => true,
        ];

        $this->controls['meta_key'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'meta_key', 'snn' ),
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
            'label'   => esc_html__( 's', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Search keyword', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // AUTHOR
        // ====================

        $this->controls['author'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'author', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1 or 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated author IDs', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['author_name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'author_name', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'user_nicename', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // CATEGORY
        // ====================

        $this->controls['cat'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'cat', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Category ID(s)', 'snn' ),
            'description' => esc_html__( 'e.g., 1 or 1,2,3 or -1 to exclude', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['category_name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'category_name', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'category-slug', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // TAG
        // ====================

        $this->controls['tag'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'tag', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'tag-slug', 'snn' ),
            'description' => esc_html__( 'Comma-separated for OR, + for AND', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['tag_id'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'tag_id', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Tag ID', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // SPECIFIC POSTS
        // ====================

        $this->controls['p'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'p', 'snn' ),
            'type'    => 'number',
        ];

        $this->controls['name'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'name', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'post-slug', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post__in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'post__in', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post__not_in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'post__not_in', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'inline'  => true,
        ];

        // ====================
        // POST PARENT
        // ====================

        $this->controls['post_parent'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'post_parent', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'Parent post ID', 'snn' ),
            'description' => esc_html__( 'Get children of this parent. Use 0 for top-level posts only. For nested queries, use {post_id} or {parent_id} - the tag will be processed at the correct time in the loop context.', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post_parent__in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'post_parent__in', 'snn' ),
            'type'    => 'text',
            'placeholder' => esc_html__( 'e.g., 1,2,3', 'snn' ),
            'description' => esc_html__( 'Comma-separated parent IDs to include', 'snn' ),
            'inline'  => true,
        ];

        $this->controls['post_parent__not_in'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'post_parent__not_in', 'snn' ),
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
            'label'   => esc_html__( 'has_password', 'snn' ),
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
            'label'   => esc_html__( 'year', 'snn' ),
            'type'    => 'number',
            'placeholder' => esc_html__( 'e.g., 2024', 'snn' ),
        ];

        $this->controls['monthnum'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'monthnum', 'snn' ),
            'type'    => 'number',
            'placeholder' => esc_html__( '1-12', 'snn' ),
        ];

        $this->controls['day'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'day', 'snn' ),
            'type'    => 'number',
            'placeholder' => esc_html__( '1-31', 'snn' ),
        ];

        // ====================
        // COMMENT
        // ====================

        $this->controls['comment_count'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'comment_count', 'snn' ),
            'type'    => 'number',
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
            'label'   => esc_html__( 'no_found_rows', 'snn' ),
            'type'    => 'checkbox',
            'description' => esc_html__( 'Improves performance if pagination not needed', 'snn' ),
        ];

        // NEW: Suppress Filters
        $this->controls['suppress_filters'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Suppress Filters', 'snn' ),
            'type'    => 'checkbox',
            'description' => esc_html__( 'Useful for archives: prevents themes/plugins from hijacking the query.', 'snn' ),
        ];

        $this->controls['cache_results'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'cache_results', 'snn' ),
            'type'    => 'checkbox',
        ];

        $this->controls['clear_query_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Clear Query Cache', 'snn' ),
            'type'    => 'checkbox',
            'description' => esc_html__( 'Clear WordPress cache before running query to ensure fresh results', 'snn' ),
        ];

        $this->controls['update_post_meta_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'update_post_meta_cache', 'snn' ),
            'type'    => 'checkbox',
        ];

        $this->controls['update_post_term_cache'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'update_post_term_cache', 'snn' ),
            'type'    => 'checkbox',
        ];

        // ====================
        // ADVANCED / JSON
        // ====================

        $this->controls['tax_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'tax_query', 'snn' ),
            'type'    => 'textarea',
            'placeholder' => esc_html__( 'Enter JSON for tax_query', 'snn' ),
            'description' => esc_html__( 'Advanced: JSON format for complex taxonomy queries', 'snn' ),
        ];

        $this->controls['meta_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'meta_query', 'snn' ),
            'type'    => 'textarea',
            'placeholder' => esc_html__( 'Enter JSON for meta_query', 'snn' ),
            'description' => esc_html__( 'Advanced: JSON format for complex meta queries', 'snn' ),
        ];

        $this->controls['date_query'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'date_query', 'snn' ),
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
        // CUSTOM ARGS
        // ====================

        $this->controls['custom_args'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Custom Args (PHP)', 'snn' ),
            'type'    => 'code',
            'mode' => 'generic',
            'placeholder' => "array(\n    'posts_per_page' => 5,\n    'orderby' => 'rand',\n    'tax_query' => array(\n        'relation' => 'OR',\n        array(\n            'taxonomy' => 'category',\n            'field' => 'slug',\n            'terms' => array('quotes'),\n        ),\n    ),\n)",
            'description' => esc_html__( 'Advanced: Enter valid PHP array syntax for WP_Query arguments. These will be merged with (and can override) other control settings. Example: array(\'posts_per_page\' => 5, \'orderby\' => \'rand\')', 'snn' ),
        ];

        // ====================
        // DEBUG
        // ====================

        $this->controls['debug'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Debug', 'snn' ),
            'type'    => 'checkbox',
            'description' => 'Enable comprehensive debug output displaying: <strong>Settings</strong> (raw control values from the element configuration), <strong>Built Query Args</strong> (final WP_Query parameters after processing all settings and dynamic data), <strong>Query Results</strong> (total posts found, post count, and current post ID context), <strong>SQL Query</strong> (the actual database query executed by WordPress), and <strong>Found Posts</strong> (detailed list of matched posts showing IDs, titles, and parent relationships). This powerful troubleshooting tool helps you understand exactly what parameters are being passed to WP_Query, verify dynamic data rendering (like {post_id}), and diagnose why queries may not be returning expected results. <a href="https://developer.wordpress.org/reference/classes/wp_query/" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: underline;">View WP_Query Class Documentation →</a>',
        ];
    }

    public function render() {
        // CRITICAL FIX: Get RAW settings before Bricks processes dynamic data
        // Bricks stores raw settings in $this->element (the raw element data)
        // We need to use these instead of $this->settings which has already been processed
        $raw_settings = isset( $this->element['settings'] ) ? $this->element['settings'] : $this->settings;
        
        $settings   = $this->settings; // Keep this for non-dynamic fields
        $no_wrapper = ! empty( $settings['no_wrapper'] );
        $wrapper_id = 'snn-query-' . $this->id;
        $debug_mode = ! empty( $settings['debug'] );

        // DEBUG: Show raw settings to see if Bricks already processed the tags
        if ( $debug_mode && isset( $raw_settings['post_parent'] ) ) {
            global $snn_raw_settings_log;
            if ( ! isset( $snn_raw_settings_log ) ) {
                $snn_raw_settings_log = [];
            }
            $snn_raw_settings_log[] = [
                'element_id' => $this->id,
                'post_parent_raw' => $raw_settings['post_parent'],
                'post_parent_processed' => $settings['post_parent'],
                'get_the_id' => get_the_ID(),
                'context_stack' => self::$post_context_stack,
            ];
        }

        // Clear query cache if enabled (default: true)
        $clear_cache = isset( $settings['clear_query_cache'] ) ? $settings['clear_query_cache'] : true;
        if ( $clear_cache ) {
            wp_cache_delete( 'last_changed', 'posts' );
        }

        // CRITICAL FIX: Build query args RIGHT NOW, at render time, using RAW settings
        // This ensures that for nested queries, the parent's setup_postdata() 
        // has already been called, making {post_id} resolve correctly
        $query_args = $this->build_query_args( $raw_settings );

        // Create WP_Query with the query args
        $posts_query = new \WP_Query( $query_args );

        // Debug output
        if ( $debug_mode ) {
            echo '<div class="snn-query-debug" style="background: #f0f0f0; border: 2px solid #333; padding: 20px; margin: 20px 0; font-family: monospace; font-size: 12px;">';
            echo '<h3 style="margin-top: 0; color: #d00;">🐛 DEBUG MODE - Element ID: ' . esc_html( $this->id ) . '</h3>';
            echo '<p><strong>Query Level:</strong> ' . ( empty( self::$post_context_stack ) ? 'ROOT (outer query)' : 'NESTED (level ' . count( self::$post_context_stack ) . ')' ) . '</p>';
            
            echo '<h4>Settings (from controls):</h4>';
            echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
            echo esc_html( print_r( $settings, true ) );
            echo '</pre>';
            
            // Show raw settings received by render()
            global $snn_raw_settings_log;
            if ( ! empty( $snn_raw_settings_log ) ) {
                echo '<h4>Raw vs Processed Settings:</h4>';
                echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
                echo esc_html( print_r( $snn_raw_settings_log, true ) );
                echo '</pre>';
            }
            echo '<h4>Built Query Args:</h4>';
            echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
            echo esc_html( print_r( $query_args, true ) );
            echo '</pre>';
            
            echo '<h4>Query Results:</h4>';
            echo '<p><strong>Posts Found:</strong> ' . $posts_query->found_posts . '</p>';
            echo '<p><strong>Post Count:</strong> ' . $posts_query->post_count . '</p>';
            echo '<p><strong>Current Post ID:</strong> ' . get_the_ID() . '</p>';
            echo '<p><strong>Post Context Stack:</strong> ' . ( ! empty( self::$post_context_stack ) ? implode( ' > ', self::$post_context_stack ) : 'Empty' ) . '</p>';
            
            global $post;
            echo '<p><strong>Global $post ID:</strong> ' . ( $post ? $post->ID : 'NULL' ) . '</p>';
            echo '<p><strong>Global $post Parent:</strong> ' . ( $post && $post->post_parent ? $post->post_parent : 'None' ) . '</p>';
            
            // Show dynamic data transformation log
            global $snn_debug_log;
            if ( ! empty( $snn_debug_log ) ) {
                echo '<h4>Dynamic Data Transformations:</h4>';
                echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
                echo esc_html( print_r( $snn_debug_log, true ) );
                echo '</pre>';
            }
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

            // Store original query and post for Bricks dynamic data
            global $wp_query, $post;
            $original_query = $wp_query;
            $original_post = $post;
            
            // Temporarily replace global query for Bricks dynamic data to work
            $wp_query = $posts_query;

            // Loop through posts
            while ( $posts_query->have_posts() ) {
                $posts_query->the_post();
                
                // CRITICAL: Explicitly set global $post and setup post data
                // This ensures Bricks' dynamic data system recognizes the loop context
                global $post;
                $current_post_id = $post->ID;
                
                // Explicitly setup post data - critical for Bricks native tags
                setup_postdata( $post );
                
                // CRITICAL: Set the loop post ID for Bricks dynamic data filter
                // This makes {post_url}, {post_title} etc. work in child elements
                self::$query_loop_post_id = $current_post_id;

                // CRITICAL: Push current post ID onto context stack BEFORE rendering children
                // This ensures nested queries can access the correct parent post ID via {post_id}
                self::$post_context_stack[] = $current_post_id;

                // DEBUG: Show comprehensive context information
                if ( $debug_mode ) {
                    echo '<div style="background: #ffffcc; padding: 10px; margin: 10px 0; border: 2px solid #333;">';
                    echo '<strong>🔄 LOOP CONTEXT FOR POST:</strong><br>';
                    echo '&nbsp;&nbsp;• Post ID: ' . $current_post_id . '<br>';
                    echo '&nbsp;&nbsp;• Post Title: ' . esc_html( get_the_title() ) . '<br>';
                    echo '&nbsp;&nbsp;• Post Slug: ' . esc_html( $post->post_name ) . '<br>';
                    echo '&nbsp;&nbsp;• Post URL: ' . esc_url( get_permalink() ) . '<br>';
                    echo '&nbsp;&nbsp;• Global $post->ID: ' . ( isset($post->ID) ? $post->ID : '<span style="color:red;">NOT SET</span>' ) . '<br>';
                    echo '&nbsp;&nbsp;• get_the_ID(): ' . get_the_ID() . '<br>';
                    echo '&nbsp;&nbsp;• $wp_query->post->ID: ' . ( isset($wp_query->post->ID) ? $wp_query->post->ID : '<span style="color:red;">NOT SET</span>' ) . '<br>';
                    echo '&nbsp;&nbsp;• in_the_loop(): ' . ( in_the_loop() ? '<span style="color:green;">YES</span>' : '<span style="color:red;">NO</span>' ) . '<br>';
                    echo '&nbsp;&nbsp;• self::$query_loop_post_id: ' . ( self::$query_loop_post_id ?? '<span style="color:red;">NOT SET</span>' ) . '<br>';
                    echo '</div>';
                }

                // Render nested children for each post
                // Any nested query elements will now have access to the correct post context
                echo Frontend::render_children( $this );

                // CRITICAL: Pop post ID from context stack AFTER rendering children
                // This restores the correct context for any parent query
                array_pop( self::$post_context_stack );
                
                // Clear the loop post ID
                self::$query_loop_post_id = null;
            }

            // Reset post data
            wp_reset_postdata();
            
            // Restore original query and post
            $wp_query = $original_query;
            if ( isset( $original_post ) ) {
                $post = $original_post;
                if ( $original_post ) {
                    setup_postdata( $original_post );
                }
            }

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
     * COMPLETELY REWRITTEN: Bypass Bricks and use native WordPress global $post
     * * For nested queries, we use the CURRENT global $post (set by setup_postdata)
     * NOT Bricks' dynamic data system which processes tags too early
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

        // CRITICAL: Use WordPress native globals, NOT Bricks
        global $post;
        
        // If no post_id specified, use the current global $post
        if ( $post_id === null && $post ) {
            $post_id = $post->ID;
        } elseif ( $post_id === null ) {
            $post_id = get_the_ID();
        }

        // Simple, direct replacements using WordPress native data
        $replacements = [
            '{post_id}'             => $post_id,
            '{parent_id}'           => ( $post && $post->post_parent ) ? $post->post_parent : $post_id,
            '{parent_id:top_level}' => $this->get_top_level_parent( $post_id ),
        ];
        
        return str_replace( array_keys( $replacements ), array_values( $replacements ), $value );
    }

    /**
     * Get the top-level parent (traverse up the hierarchy)
     */
    private function get_top_level_parent( $post_id ) {
        $current_post = get_post( $post_id );
        if ( ! $current_post ) {
            return $post_id;
        }

        $parent_id = $current_post->post_parent;
        if ( ! $parent_id ) {
            return $post_id;
        }

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

    /**
     * Build WP_Query arguments from individual control values
     * * IMPORTANT: In nested queries, this is called INSIDE the parent query loop,
     * AFTER setup_postdata() has been called, so dynamic data like {post_id}
     * will resolve to the correct parent post ID via the context stack.
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

        // POST PARENT - CRITICAL FIX: Dynamic data is now rendered at the RIGHT TIME
        // When this function is called inside a nested query loop, the context stack
        // contains the parent post ID, so {post_id} will resolve correctly
        if ( isset( $settings['post_parent'] ) && $settings['post_parent'] !== '' ) {
            $original_value = $settings['post_parent'];
            // Render dynamic data NOW (when we're in the correct post context)
            $post_parent_value = $this->render_control_dynamic_data( $settings['post_parent'] );
            
            // DEBUG: Log the transformation if debug mode is on
            global $snn_debug_log, $post;
            if ( ! isset( $snn_debug_log ) ) {
                $snn_debug_log = [];
            }
            $snn_debug_log[] = [
                'original' => $original_value,
                'rendered' => $post_parent_value,
                'global_post_id' => $post ? $post->ID : 'NULL',
                'global_post_parent' => $post && $post->post_parent ? $post->post_parent : 'None',
                'context_stack' => self::$post_context_stack,
                'get_the_id' => get_the_ID(),
            ];
            
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

        // ADDED: Suppress filters
        if ( ! empty( $settings['suppress_filters'] ) ) {
            $args['suppress_filters'] = true;
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

        // CUSTOM ARGS - Process PHP code that defines query args
        if ( ! empty( $settings['custom_args'] ) ) {
            $custom_args_str = trim( $settings['custom_args'] );

            // Render dynamic data in custom args string
            $custom_args_str = $this->render_control_dynamic_data( $custom_args_str );

            // Use eval to execute the PHP code
            try {
                $custom_args = null;

                // Check if code starts with 'array(' or '[' - simple array definition
                if ( strpos( $custom_args_str, 'array(' ) === 0 || strpos( $custom_args_str, '[' ) === 0 ) {
                    // Prepend 'return ' if not already there
                    if ( strpos( $custom_args_str, 'return' ) !== 0 ) {
                        $custom_args_str = 'return ' . $custom_args_str . ';';
                    }

                    $custom_args = @eval( $custom_args_str );
                } else {
                    // Multi-line PHP code with logic
                    // Wrap code to capture $args variable
                    $wrapped_code = '
                        $custom_args = null;
                        ' . $custom_args_str . '
                        if (isset($args) && is_array($args)) {
                            return $args;
                        }
                        return null;
                    ';

                    $custom_args = @eval( $wrapped_code );
                }

                // Validate the result is an array and merge
                if ( is_array( $custom_args ) ) {
                    // Merge custom args with existing args (custom args override)
                    $args = array_merge( $args, $custom_args );
                }
            } catch ( \Exception $e ) {
                // Silent fail - don't break the query if custom args are invalid
                // In debug mode, this will be visible in the query args output
            }
        }

        return $args;
    }
}
?>