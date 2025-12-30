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

        $this->controls['query_args'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Query', 'snn' ),
            'type'    => 'query',
            'default' => [
                'post_type' => 'post',
            ],
            'description' => esc_html__( 'Choose what to loop. Any nested elements will be repeated for each post.', 'snn' ),
        ];

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
        $query_args = ! empty( $settings['query_args'] ) ? $settings['query_args'] : [];
        $no_wrapper = ! empty( $settings['no_wrapper'] );
        $wrapper_id = 'snn-query-' . $this->id;

        // Ensure query args have required post_type
        if ( empty( $query_args['post_type'] ) ) {
            $query_args['post_type'] = 'post';
        }

        // Create WP_Query with the query args
        $posts_query = new \WP_Query( $query_args );

        if ( $posts_query->have_posts() ) {
            // Output wrapper opening tag
            if ( ! $no_wrapper ) {
                $this->set_attribute( '_root', 'class', 'snn-query-nestable-wrapper' );
                $this->set_attribute( '_root', 'id', $wrapper_id );
                echo '<div ' . $this->render_attributes( '_root' ) . '>';
            }

            // Set query loop for Bricks dynamic data support
            Query::set_loop_object( $this->id, 'wp_query', $posts_query );

            // Loop through posts
            while ( $posts_query->have_posts() ) {
                $posts_query->the_post();
                
                // Render nested children for each post
                echo Frontend::render_children( $this );
            }

            // Reset post data
            wp_reset_postdata();
            
            // Destroy loop object
            Query::destroy_loop_object( $this->id );

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
}
?>
