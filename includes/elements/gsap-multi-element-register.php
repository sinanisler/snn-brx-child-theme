<?php

$targets = [ 'section', 'container', 'block', 'div' , 'heading' , 'text-basic' , 'text' ];

add_action( 'init', function () {
    global $targets;
    
    foreach ( $targets as $name ) {
        add_filter( "bricks/elements/{$name}/controls", function ( $controls ) {
            
            $controls['custom_data_animate_dynamic_elements'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Select Animation', 'snn' ),
                'type'        => 'select',
                'options'     => [

					'style_start-opacity' => esc_html__('Opacity Start', 'snn'),
                    'style_end-opacity'   => esc_html__('Opacity End', 'snn'),
                    





					'custom' => esc_html__('/ Custom data-animate', 'snn'),
                ],
                'default'     => '',
                'multiple'    => true,
                'searchable'  => true,
                'clearable'   => true,
                'description' => esc_html__('Select one or more animation properties.', 'snn'),
            ];

            // -- CONDITIONAL CONTROLS  --
            
            $controls['custom_data_animate_start_opacity_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Opacity', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0, 1, 0.1',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_start-opacity' ],
				'inline'	  => true,
            ];

            $controls['custom_data_animate_end_opacity_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Opacity', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0, 1, 0.1',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-opacity' ],
				'inline'	  => true,
            ];

        

			$controls['custom_data_animate_dynamic_elements_custom'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Custom Animations', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'style_start-transform:scale(0), style_end-transform:scale(1)',
                'description' => 'Write any valid data-animate css values 
					<a href="https://github.com/sinanisler/data-animate?tab=readme-ov-file#how-it-works" 
					style="font-weight:bold" target="_blank">EXAMPLES ➤</a>',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'custom' ],
            ];

            return $controls;
        }, 20 );
    }
} );

add_filter( 'bricks/element/render_attributes', function( $attributes, $key, $element ) {
    global $targets;

    $selected_options = $element->settings['custom_data_animate_dynamic_elements'] ?? [];
    $custom_value     = $element->settings['custom_data_animate_dynamic_elements_custom'] ?? '';
    $start_opacity    = $element->settings['custom_data_animate_start_opacity_value'] ?? '';
    $end_opacity      = $element->settings['custom_data_animate_end_opacity_value'] ?? '';

    if ( ! in_array( $element->name, $targets, true ) || empty( $selected_options ) ) {
        return $attributes; 
    }

    if ( ! is_array( $selected_options ) ) {
        $selected_options = explode( ',', $selected_options );
    }

    $final_attributes = [];

    foreach ($selected_options as $option) {
        switch ($option) {
            case 'style_start-opacity':
                if ( $start_opacity !== '' ) {
                    $final_attributes[] = "style_start-opacity:" . esc_attr($start_opacity);
                }
                break;
            case 'style_end-opacity':
                if ( $end_opacity !== '' ) {
                    $final_attributes[] = "style_end-opacity:" . esc_attr($end_opacity);
                }
                break;
            case 'custom':
                if ( $custom_value !== '' ) {
                    $final_attributes[] = $custom_value;
                }
                break;
            default:
                $final_attributes[] = $option;
                break;
        }
    }
    
    if ( ! empty( $final_attributes ) ) {
        $attributes[ $key ]['data-animate'] = implode( ',', array_unique($final_attributes) );
    }

    return $attributes;

}, 1000, 3 );
