<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use Bricks\Element;
use Bricks\Frontend;

class SNN_Query_Nestable extends Element {
    public $category     = 'snn';
    public $name         = 'snn-query-nestable';
    public $icon         = 'ti-layout-grid2';
    public $css_selector = '.snn-query-nestable-wrapper';
    public $nestable     = true;

    public function get_label() {
        return esc_html__( 'Query (Nestable)', 'bricks' );
    }

    public function set_controls() {

        $this->controls['no_wrapper'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'No Wrapper', 'bricks' ),
            'type'    => 'checkbox',
            'default' => false,
        ];

        $this->controls['query_args'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Query', 'bricks' ),
            'type'    => 'query',
            'default' => [
                'post_type' => 'post',
            ],
            'description' => esc_html__( 'Choose what to loop. Any nested elements will be repeated for each post.', 'bricks' ),
        ];
        
    }

    public function render() {
        $query_args = $this->settings['query_args'];
        $posts_query = new \WP_Query( $query_args );
        $no_wrapper = ! empty( $this->settings['no_wrapper'] );
        $wrapper_id = 'snn-query-' . uniqid();

        if ( $posts_query->have_posts() ) {
            if ( ! $no_wrapper ) {
                $this->set_attribute( '_root', 'class', 'snn-query-nestable-wrapper' );
                $this->set_attribute( '_root', 'id', $wrapper_id );
                echo '<div ' . $this->render_attributes('_root') . '>';
            }

            while ( $posts_query->have_posts() ) : $posts_query->the_post();
                echo Frontend::render_children( $this );
            endwhile;

            wp_reset_postdata();

            if ( ! $no_wrapper ) {
                echo '</div>';
            }
        } else {
            if ( ! $no_wrapper ) {
                $this->set_attribute( '_root', 'class', 'snn-query-nestable-wrapper' );
                $this->set_attribute( '_root', 'id', $wrapper_id );
                echo '<div ' . $this->render_attributes('_root') . '>';
            }
            esc_html_e( 'No posts matched your criteria.', 'bricks' );
            if ( ! $no_wrapper ) {
                echo '</div>';
            }
        }
    }
}
?>
