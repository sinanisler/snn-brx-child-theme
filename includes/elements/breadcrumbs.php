<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Bricks\Element;

class SNN_Breadcrumbs_Element extends Element {
    public $category     = 'snn';
    public $name         = 'snn-breadcrumbs';
    public $icon         = 'ti-angle-double-right';
    public $css_selector = '.brxe-snn-breadcrumbs';
    public $scripts      = [];

    public function get_label() {
        return esc_html__( 'SNN Breadcrumbs', 'snn' );
    }

    public function set_controls() {
        // Breadcrumb Items Repeater
        $this->controls['breadcrumb_items'] = [
            'tab'            => 'content',
            'label'          => esc_html__( 'Breadcrumb Items', 'snn' ),
            'type'           => 'repeater',
            'titleProperty'  => 'item_type',
            'placeholder'    => esc_html__( 'Breadcrumb Item', 'snn' ),
            'default'        => [
                [
                    'item_type'  => 'home',
                    'icon'       => [
                        'library' => 'themify',
                        'icon'    => 'ti-home',
                    ],
                    'show_icon'  => true,
                    'custom_text' => '',
                    'home_text'   => '',
                ],
                [
                    'item_type'  => 'current_post',
                    'icon'       => [],
                    'show_icon'  => false,
                    'custom_text' => '',
                ],
            ],
            'fields'         => [
                'item_type' => [
                    'label'   => esc_html__( 'Item Type', 'snn' ),
                    'type'    => 'select',
                    'options' => [
                        'home'                      => esc_html__( 'Home', 'snn' ),
                        'current_post'              => esc_html__( 'Current Post Title', 'snn' ),
                        'all_post_ancestors'        => esc_html__( 'All Post Ancestors (Recursive)', 'snn' ),
                        'post_type_archive'         => esc_html__( 'Post Type Archive', 'snn' ),
                        'post_type_archive_current' => esc_html__( 'Current Post Type Archive', 'snn' ),
                        'current_term'              => esc_html__( 'Current Term (Auto Detect)', 'snn' ),
                        'all_term_ancestors'        => esc_html__( 'All Term Ancestors (Recursive)', 'snn' ),
                        'category'                  => esc_html__( 'Current Category', 'snn' ),
                        'all_category_ancestors'    => esc_html__( 'All Category Ancestors (Recursive)', 'snn' ),
                        'tag'                       => esc_html__( 'Current Tag', 'snn' ),
                        'custom_taxonomy'           => esc_html__( 'Custom Taxonomy Term', 'snn' ),
                        'author'                    => esc_html__( 'Post Author', 'snn' ),
                        'author_archive'            => esc_html__( 'Author Archive Link', 'snn' ),
                        'date_year'                 => esc_html__( 'Date Archive - Year', 'snn' ),
                        'date_month'                => esc_html__( 'Date Archive - Month', 'snn' ),
                        'date_day'                  => esc_html__( 'Date Archive - Day', 'snn' ),
                        'search_query'              => esc_html__( 'Search Query', 'snn' ),
                        'search_results'            => esc_html__( 'Search Results Label', 'snn' ),
                        '404_page'                  => esc_html__( '404 Page', 'snn' ),
                        'blog_page'                 => esc_html__( 'Blog Page', 'snn' ),
                        'shop_page'                 => esc_html__( 'Shop Page (WooCommerce)', 'snn' ),
                        'current_product_categories' => esc_html__( 'Product Categories (WooCommerce)', 'snn' ),
                        'pagination'                => esc_html__( 'Pagination (Page X)', 'snn' ),
                        'bbpress_forum'             => esc_html__( 'bbPress Forum', 'snn' ),
                        'bbpress_topic'             => esc_html__( 'bbPress Topic', 'snn' ),
                        'custom_text'               => esc_html__( 'Custom Text', 'snn' ),
                        'custom_link'               => esc_html__( 'Custom Link', 'snn' ),
                    ],
                    'default' => 'current_post',
                ],
                'home_text' => [
                    'label'       => esc_html__( 'Custom Home Text', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'Leave empty for site name', 'snn' ),
                    'required'    => ['item_type', '=', 'home'],
                ],
                'taxonomy_name' => [
                    'label'       => esc_html__( 'Taxonomy Slug', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'e.g., product_cat, genre', 'snn' ),
                    'required'    => ['item_type', '=', 'custom_taxonomy'],
                ],
                'icon' => [
                    'label'   => esc_html__( 'Icon', 'snn' ),
                    'type'    => 'icon',
                    'default' => [],
                ],
                'show_icon' => [
                    'label'   => esc_html__( 'Show Icon', 'snn' ),
                    'type'    => 'checkbox',
                    'default' => false,
                ],
                'custom_text' => [
                    'label'       => esc_html__( 'Custom Text', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'Enter custom text', 'snn' ),
                    'required'    => ['item_type', '=', ['custom_text', 'custom_link', '404_page', 'search_results']],
                ],
                'custom_url' => [
                    'label'       => esc_html__( 'Custom URL', 'snn' ),
                    'type'        => 'text',
                    'placeholder' => esc_html__( 'Enter custom URL', 'snn' ),
                    'required'    => ['item_type', '=', 'custom_link'],
                ],
            ],
        ];

        // Separator Control
        $this->controls['separator'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Separator', 'snn' ),
            'type'    => 'text',
            'default' => '/',
            'css'     => [
                [
                    'selector' => '.breadcrumb-separator',
                ],
            ],
        ];

        // Separator Icon
        $this->controls['separator_icon'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Separator Icon', 'snn' ),
            'type'  => 'icon',
            'css'   => [
                [
                    'selector' => '.breadcrumb-separator',
                ],
            ],
        ];

        // Typography Controls
        $this->controls['typography'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Typography', 'snn' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '',
                ],
            ],
        ];

        // Link Color
        $this->controls['link_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Link Color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => 'a',
                ],
            ],
        ];

        // Link Hover Color
        $this->controls['link_hover_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Link Hover Color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => 'a:hover',
                ],
            ],
        ];

        // Current Item Color
        $this->controls['current_color'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Current Item Color', 'snn' ),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.breadcrumb-current',
                ],
            ],
        ];

        // Separator Spacing
        $this->controls['separator_spacing'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Separator Spacing', 'snn' ),
            'type'  => 'spacing',
            'css'   => [
                [
                    'property' => 'margin',
                    'selector' => '.breadcrumb-separator',
                ],
            ],
            'default' => [
                'left'  => '10px',
                'right' => '10px',
            ],
        ];

        // Icon Size
        $this->controls['icon_size'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Icon Size', 'snn' ),
            'type'  => 'number',
            'unit'  => 'px',
            'css'   => [
                [
                    'property' => 'font-size',
                    'selector' => '.breadcrumb-icon',
                ],
            ],
        ];

        // Icon Spacing
        $this->controls['icon_spacing'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Icon Spacing', 'snn' ),
            'type'  => 'number',
            'unit'  => 'px',
            'css'   => [
                [
                    'property' => 'margin-right',
                    'selector' => '.breadcrumb-icon',
                ],
            ],
            'default' => 5,
        ];

        // Schema.org Markup
        $this->controls['enable_schema'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Enable Schema.org Markup', 'snn' ),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // Max Title Length
        $this->controls['max_title_length'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'Max Title Length', 'snn' ),
            'type'        => 'number',
            'default'     => 0,
            'min'         => 0,
            'placeholder' => esc_html__( '0 = No limit', 'snn' ),
        ];
    }

    private function truncate_title( $title ) {
        $max_length = intval( $this->settings['max_title_length'] ?? 0 );
        if ( $max_length > 0 && mb_strlen( $title ) > $max_length ) {
            return mb_strimwidth( $title, 0, $max_length, '...' );
        }
        return $title;
    }

    private function get_item_content( $item ) {
        $item_type      = $item['item_type'] ?? 'current_post';
        $custom_text    = $item['custom_text'] ?? '';
        $custom_url     = $item['custom_url'] ?? '';
        $home_text      = $item['home_text'] ?? '';
        $taxonomy_name  = $item['taxonomy_name'] ?? '';

        switch ( $item_type ) {
            case 'home':
                $title = ! empty( $home_text ) ? $home_text : get_bloginfo( 'name' );
                $url   = home_url( '/' );
                return [
                    'html' => '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>',
                    'url'  => $url,
                    'name' => $title,
                ];

            case 'current_post':
                $title = $this->truncate_title( get_the_title() );
                return [
                    'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $title ) . '</span>',
                    'url'     => get_permalink(),
                    'name'    => $title,
                    'current' => true,
                ];

            case 'all_post_ancestors':
                $post = get_post();
                if ( ! $post || ! $post->post_parent ) {
                    return [];
                }
                $ancestors = get_post_ancestors( $post->ID );
                $ancestors = array_reverse( $ancestors );
                $items = [];
                foreach ( $ancestors as $ancestor_id ) {
                    $ancestor_title = $this->truncate_title( get_the_title( $ancestor_id ) );
                    $items[] = [
                        'html' => '<a href="' . esc_url( get_permalink( $ancestor_id ) ) . '">' . esc_html( $ancestor_title ) . '</a>',
                        'url'  => get_permalink( $ancestor_id ),
                        'name' => $ancestor_title,
                    ];
                }
                return $items;

            case 'post_type_archive':
            case 'post_type_archive_current':
                $post_type = get_post_type();
                $post_type_obj = get_post_type_object( $post_type );
                if ( $post_type_obj && $post_type_obj->has_archive ) {
                    $archive_link = get_post_type_archive_link( $post_type );
                    $archive_title = $post_type_obj->labels->name;
                    if ( $item_type === 'post_type_archive_current' ) {
                        return [
                            'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $archive_title ) . '</span>',
                            'url'     => $archive_link,
                            'name'    => $archive_title,
                            'current' => true,
                        ];
                    }
                    return [
                        'html' => '<a href="' . esc_url( $archive_link ) . '">' . esc_html( $archive_title ) . '</a>',
                        'url'  => $archive_link,
                        'name' => $archive_title,
                    ];
                }
                return [];

            case 'current_term':
                $queried_object = get_queried_object();
                if ( $queried_object && isset( $queried_object->term_id ) ) {
                    $term_name = $this->truncate_title( $queried_object->name );
                    return [
                        'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $term_name ) . '</span>',
                        'url'     => get_term_link( $queried_object ),
                        'name'    => $term_name,
                        'current' => true,
                    ];
                }
                return [];

            case 'all_term_ancestors':
                $queried_object = get_queried_object();
                if ( $queried_object && isset( $queried_object->term_id ) && $queried_object->parent ) {
                    $ancestors = get_ancestors( $queried_object->term_id, $queried_object->taxonomy, 'taxonomy' );
                    $ancestors = array_reverse( $ancestors );
                    $items = [];
                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor_term = get_term( $ancestor_id, $queried_object->taxonomy );
                        if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
                            $ancestor_name = $this->truncate_title( $ancestor_term->name );
                            $items[] = [
                                'html' => '<a href="' . esc_url( get_term_link( $ancestor_term ) ) . '">' . esc_html( $ancestor_name ) . '</a>',
                                'url'  => get_term_link( $ancestor_term ),
                                'name' => $ancestor_name,
                            ];
                        }
                    }
                    return $items;
                }
                return [];

            case 'category':
                $categories = get_the_category();
                if ( ! empty( $categories ) ) {
                    $category = $categories[0];
                    $cat_name = $this->truncate_title( $category->name );
                    return [
                        'html' => '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '">' . esc_html( $cat_name ) . '</a>',
                        'url'  => get_category_link( $category->term_id ),
                        'name' => $cat_name,
                    ];
                }
                return [];

            case 'all_category_ancestors':
                $categories = get_the_category();
                if ( ! empty( $categories ) ) {
                    $category = $categories[0];
                    $items = [];
                    $ancestors = get_ancestors( $category->term_id, 'category', 'taxonomy' );
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor_cat = get_category( $ancestor_id );
                        if ( $ancestor_cat ) {
                            $ancestor_name = $this->truncate_title( $ancestor_cat->name );
                            $items[] = [
                                'html' => '<a href="' . esc_url( get_category_link( $ancestor_cat->term_id ) ) . '">' . esc_html( $ancestor_name ) . '</a>',
                                'url'  => get_category_link( $ancestor_cat->term_id ),
                                'name' => $ancestor_name,
                            ];
                        }
                    }
                    // Add current category
                    $cat_name = $this->truncate_title( $category->name );
                    $items[] = [
                        'html' => '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '">' . esc_html( $cat_name ) . '</a>',
                        'url'  => get_category_link( $category->term_id ),
                        'name' => $cat_name,
                    ];
                    return $items;
                }
                return [];

            case 'tag':
                $tags = get_the_tags();
                if ( ! empty( $tags ) ) {
                    $tag = $tags[0];
                    $tag_name = $this->truncate_title( $tag->name );
                    return [
                        'html' => '<a href="' . esc_url( get_tag_link( $tag->term_id ) ) . '">' . esc_html( $tag_name ) . '</a>',
                        'url'  => get_tag_link( $tag->term_id ),
                        'name' => $tag_name,
                    ];
                }
                return [];

            case 'custom_taxonomy':
                if ( ! empty( $taxonomy_name ) ) {
                    $terms = get_the_terms( get_the_ID(), $taxonomy_name );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                        $term = $terms[0];
                        $term_name = $this->truncate_title( $term->name );
                        return [
                            'html' => '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term_name ) . '</a>',
                            'url'  => get_term_link( $term ),
                            'name' => $term_name,
                        ];
                    }
                }
                return [];

            case 'author':
                $author_name = get_the_author();
                return [
                    'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $author_name ) . '</span>',
                    'url'     => get_author_posts_url( get_the_author_meta( 'ID' ) ),
                    'name'    => $author_name,
                    'current' => true,
                ];

            case 'author_archive':
                $author_name = get_the_author();
                $author_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
                return [
                    'html' => '<a href="' . esc_url( $author_url ) . '">' . esc_html( $author_name ) . '</a>',
                    'url'  => $author_url,
                    'name' => $author_name,
                ];

            case 'date_year':
                if ( is_date() || is_singular() ) {
                    $year = get_the_date( 'Y' );
                    $year_link = get_year_link( $year );
                    return [
                        'html' => '<a href="' . esc_url( $year_link ) . '">' . esc_html( $year ) . '</a>',
                        'url'  => $year_link,
                        'name' => $year,
                    ];
                }
                return [];

            case 'date_month':
                if ( is_date() || is_singular() ) {
                    $month = get_the_date( 'F Y' );
                    $month_link = get_month_link( get_the_date( 'Y' ), get_the_date( 'm' ) );
                    return [
                        'html' => '<a href="' . esc_url( $month_link ) . '">' . esc_html( $month ) . '</a>',
                        'url'  => $month_link,
                        'name' => $month,
                    ];
                }
                return [];

            case 'date_day':
                if ( is_date() || is_singular() ) {
                    $day = get_the_date();
                    return [
                        'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $day ) . '</span>',
                        'url'     => '',
                        'name'    => $day,
                        'current' => true,
                    ];
                }
                return [];

            case 'search_query':
                if ( is_search() ) {
                    $query = get_search_query();
                    return [
                        'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $query ) . '</span>',
                        'url'     => get_search_link( $query ),
                        'name'    => $query,
                        'current' => true,
                    ];
                }
                return [];

            case 'search_results':
                $label = ! empty( $custom_text ) ? $custom_text : esc_html__( 'Search Results', 'snn' );
                return [
                    'html' => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $label ) . '</span>',
                    'url'  => '',
                    'name' => $label,
                    'current' => true,
                ];

            case '404_page':
                $label = ! empty( $custom_text ) ? $custom_text : esc_html__( '404 - Page Not Found', 'snn' );
                return [
                    'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $label ) . '</span>',
                    'url'     => '',
                    'name'    => $label,
                    'current' => true,
                ];

            case 'blog_page':
                $blog_page_id = get_option( 'page_for_posts' );
                if ( $blog_page_id ) {
                    $blog_title = $this->truncate_title( get_the_title( $blog_page_id ) );
                    $blog_url = get_permalink( $blog_page_id );
                    return [
                        'html' => '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_title ) . '</a>',
                        'url'  => $blog_url,
                        'name' => $blog_title,
                    ];
                }
                return [];

            case 'shop_page':
                if ( function_exists( 'wc_get_page_id' ) ) {
                    $shop_page_id = wc_get_page_id( 'shop' );
                    if ( $shop_page_id > 0 ) {
                        $shop_title = $this->truncate_title( get_the_title( $shop_page_id ) );
                        $shop_url = get_permalink( $shop_page_id );
                        return [
                            'html' => '<a href="' . esc_url( $shop_url ) . '">' . esc_html( $shop_title ) . '</a>',
                            'url'  => $shop_url,
                            'name' => $shop_title,
                        ];
                    }
                }
                return [];

            case 'current_product_categories':
                if ( function_exists( 'wc_get_product_terms' ) ) {
                    $terms = wc_get_product_terms( get_the_ID(), 'product_cat', [ 'orderby' => 'parent' ] );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                        $items = [];
                        $term = $terms[0];
                        $ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
                        $ancestors = array_reverse( $ancestors );
                        foreach ( $ancestors as $ancestor_id ) {
                            $ancestor_term = get_term( $ancestor_id, 'product_cat' );
                            if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
                                $ancestor_name = $this->truncate_title( $ancestor_term->name );
                                $items[] = [
                                    'html' => '<a href="' . esc_url( get_term_link( $ancestor_term ) ) . '">' . esc_html( $ancestor_name ) . '</a>',
                                    'url'  => get_term_link( $ancestor_term ),
                                    'name' => $ancestor_name,
                                ];
                            }
                        }
                        $term_name = $this->truncate_title( $term->name );
                        $items[] = [
                            'html' => '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term_name ) . '</a>',
                            'url'  => get_term_link( $term ),
                            'name' => $term_name,
                        ];
                        return $items;
                    }
                }
                return [];

            case 'pagination':
                $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : 0 );
                if ( $paged > 1 ) {
                    $page_label = sprintf( esc_html__( 'Page %s', 'snn' ), $paged );
                    return [
                        'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $page_label ) . '</span>',
                        'url'     => '',
                        'name'    => $page_label,
                        'current' => true,
                    ];
                }
                return [];

            case 'bbpress_forum':
                if ( function_exists( 'bbp_get_forum_id' ) && function_exists( 'bbp_get_forum_title' ) ) {
                    $forum_id = bbp_get_forum_id();
                    $forum_title = $this->truncate_title( bbp_get_forum_title( $forum_id ) );
                    $forum_url = get_permalink( $forum_id );
                    return [
                        'html' => '<a href="' . esc_url( $forum_url ) . '">' . esc_html( $forum_title ) . '</a>',
                        'url'  => $forum_url,
                        'name' => $forum_title,
                    ];
                }
                return [];

            case 'bbpress_topic':
                if ( function_exists( 'bbp_get_topic_id' ) && function_exists( 'bbp_get_topic_title' ) ) {
                    $topic_id = bbp_get_topic_id();
                    $topic_title = $this->truncate_title( bbp_get_topic_title( $topic_id ) );
                    return [
                        'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $topic_title ) . '</span>',
                        'url'     => get_permalink( $topic_id ),
                        'name'    => $topic_title,
                        'current' => true,
                    ];
                }
                return [];

            case 'custom_text':
                $text = ! empty( $custom_text ) ? $custom_text : '';
                return [
                    'html'    => '<span class="breadcrumb-current" aria-current="page">' . esc_html( $text ) . '</span>',
                    'url'     => '',
                    'name'    => $text,
                    'current' => true,
                ];

            case 'custom_link':
                if ( ! empty( $custom_text ) && ! empty( $custom_url ) ) {
                    return [
                        'html' => '<a href="' . esc_url( $custom_url ) . '">' . esc_html( $custom_text ) . '</a>',
                        'url'  => $custom_url,
                        'name' => $custom_text,
                    ];
                }
                return [];

            default:
                return [];
        }
    }

    private function get_separator() {
        $separator_html = '<span class="breadcrumb-separator">';

        if ( ! empty( $this->settings['separator_icon'] ) ) {
            ob_start();
            echo \Bricks\Helpers::render_control_icon( $this->settings['separator_icon'], [] );
            $separator_html .= ob_get_clean();
        } elseif ( ! empty( $this->settings['separator'] ) ) {
            $separator_html .= esc_html( $this->settings['separator'] );
        } else {
            $separator_html .= '/';
        }

        $separator_html .= '</span>';

        return $separator_html;
    }

    public function render() {
        $items = $this->settings['breadcrumb_items'] ?? [];
        $enable_schema = $this->settings['enable_schema'] ?? true;

        if ( empty( $items ) ) {
            echo '<div ' . $this->render_attributes( '_root' ) . '>';
            echo esc_html__( 'No breadcrumb items defined.', 'snn' );
            echo '</div>';
            return;
        }

        echo '<div ' . $this->render_attributes( '_root' ) . '>';

        // Start nav with schema markup
        $nav_attrs = 'class="breadcrumbs-wrapper" aria-label="Breadcrumb"';
        if ( $enable_schema ) {
            $nav_attrs .= ' itemscope itemtype="https://schema.org/BreadcrumbList"';
        }
        echo '<nav ' . $nav_attrs . '>';

        $output = [];
        $position = 1;

        foreach ( $items as $index => $item ) {
            $content = $this->get_item_content( $item );

            if ( empty( $content ) ) {
                continue;
            }

            // Handle multiple items (like ancestors)
            $content_items = is_array( $content ) && isset( $content['html'] ) ? [ $content ] : ( is_array( $content ) ? $content : [] );

            if ( empty( $content_items ) ) {
                continue;
            }

            foreach ( $content_items as $content_item ) {
                if ( empty( $content_item['html'] ) ) {
                    continue;
                }

                $item_wrapper_attrs = 'class="breadcrumb-item"';
                if ( $enable_schema ) {
                    $item_wrapper_attrs .= ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"';
                }

                $item_html = '<span ' . $item_wrapper_attrs . '>';

                // Add icon if enabled
                if ( ! empty( $item['show_icon'] ) && ! empty( $item['icon'] ) ) {
                    $item_html .= '<span class="breadcrumb-icon">';
                    ob_start();
                    echo \Bricks\Helpers::render_control_icon( $item['icon'], [] );
                    $item_html .= ob_get_clean();
                    $item_html .= '</span>';
                }

                // Add schema markup to content
                if ( $enable_schema ) {
                    $is_current = $content_item['current'] ?? false;
                    $url = $content_item['url'] ?? '';
                    $name = $content_item['name'] ?? '';

                    if ( ! $is_current && ! empty( $url ) ) {
                        // Replace <a> tag with schema markup
                        $item_html .= '<a href="' . esc_url( $url ) . '" itemprop="item">';
                        $item_html .= '<span itemprop="name">' . esc_html( $name ) . '</span>';
                        $item_html .= '</a>';
                    } else {
                        // Current item or text-only
                        $item_html .= '<span itemprop="name">' . esc_html( $name ) . '</span>';
                    }

                    $item_html .= '<meta itemprop="position" content="' . esc_attr( $position ) . '" />';
                } else {
                    // No schema, use original HTML
                    $item_html .= $content_item['html'];
                }

                $item_html .= '</span>';

                $output[] = $item_html;
                $position++;
            }
        }

        echo implode( $this->get_separator(), $output );

        echo '</nav>';
        echo '</div>';
    }
}
?>
