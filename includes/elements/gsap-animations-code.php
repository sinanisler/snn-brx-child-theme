<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bricks\Frontend;

class Prefix_Element_Gsap_Animations_Simple extends \Bricks\Element {
    public $category     = 'snn';
    public $name         = 'gsap-animations-simple';
    public $icon         = 'ti-bolt-alt';
    public $css_selector = '.snn-gsap-animations-simple-wrapper';
    public $scripts      = [];
    public $nestable     = true;

    public function get_label() {
        return esc_html__( 'GSAP Animations CODE (Nestable)', 'snn' );
    }

    public function set_control_groups() {
        // No groups needed
    }

    public function set_controls() {
        $this->controls['gsap_data_animate'] = [
            'tab'         => 'content',
            'label'       => esc_html__( 'GSAP data-animate CSS', 'snn' ),
            'type'        => 'code',
            'mode'        => 'c',
            'placeholder' => "e.g. x:100, opacity:0, duration:2;",
            'description' => esc_html__( 'Enter your GSAP data-animate string. It will be output as the data-animate attribute.', 'snn' ),
        ];
    }

    public function render() {
        $root_classes = ['snn-gsap-animations-simple-wrapper'];
        $this->set_attribute( '_root', 'class', $root_classes );

        $data_animate = '';
        if ( ! empty( $this->settings['gsap_data_animate'] ) ) {
            $data_animate = ' data-animate="' . esc_attr( $this->settings['gsap_data_animate'] ) . '"';
        }

        echo '<div ' . $this->render_attributes('_root') . $data_animate . '>';
        echo Frontend::render_children( $this );
        echo '</div>';
    }

    public static function render_builder() {
        ?>
        <script type="text/x-template" id="tmpl-bricks-element-gsap-animations-simple">
            <component :is="tag">
                <bricks-element-children :element="element"/>
            </component>
        </script>
        <?php
    }
}

?>
