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

					'style_start-opacity'   => esc_html__('Opacity Start', 'snn'),
                    'style_end-opacity'     => esc_html__('Opacity End', 'snn'),

					'style_start-transform' => esc_html__('Transform Start', 'snn'),
                    'style_end-transform'   => esc_html__('Transform End', 'snn'),

					'style_start-filter'    => esc_html__('Filter Start', 'snn'),
                    'style_end-filter'      => esc_html__('Filter End', 'snn'),

					'custom'                => esc_html__('/ Custom data-animate', 'snn'),
                ],
                'default'     => '',
                'multiple'    => true,
                'searchable'  => true,
                'clearable'   => true,
                'description' => esc_html__('Select one or more animation properties.', 'snn'),
            ];





            // -- CONDITIONAL CONTROLS --

            // Opacity Start Value control
            $controls['custom_data_animate_start_opacity_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Opacity', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0, 1, 0.1',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_start-opacity' ],
                'inline'      => true,
            ];

            $controls['custom_data_animate_end_opacity_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Opacity', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0, 1, 0.1',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-opacity' ],
                'inline'      => true,
            ];

            // Transform Start Value control (new)
            $controls['custom_data_animate_start_transform_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Transform', 'snn' ),
                'type'        => 'text', // Transform values are strings (e.g., 'scale(0.5) rotate(45deg)')
                'placeholder' => 'scale(0), translateX(100px)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_start-transform' ],
                'inline'      => true,
            ];

            // Transform End Value control (new)
            $controls['custom_data_animate_end_transform_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Transform', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'scale(1), translateX(0)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-transform' ],
                'inline'      => true,
            ];

            // Filter Start Value control (new)
            $controls['custom_data_animate_start_filter_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Filter', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'blur(10px), grayscale(100%)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_start-filter' ],
                'inline'      => true,
            ];

            // Filter End Value control (new)
            $controls['custom_data_animate_end_filter_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Filter', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'blur(0), grayscale(0)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-filter' ],
                'inline'      => true,
            ];
        
            // Custom data-animate text input control
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

    // Only proceed if the element is in our target list and animation options are selected
    if ( ! in_array( $element->name, $targets, true ) || empty( $selected_options ) ) {
        return $attributes; 
    }

    // Ensure selected_options is an array, even if only one option was selected (Bricks might return a string)
    if ( ! is_array( $selected_options ) ) {
        $selected_options = explode( ',', $selected_options );
    }

    $final_attributes = [];

    foreach ($selected_options as $option) {
        if (str_starts_with($option, 'style_start-')) {
            $property = str_replace('style_start-', '', $option);
            $setting_key = 'custom_data_animate_start_' . str_replace('-', '_', $property) . '_value';
            
            $value = $element->settings[$setting_key] ?? '';
            
            if ($value !== '') {
                $final_attributes[] = $option . ':' . esc_attr($value);
            }
        } 
        elseif (str_starts_with($option, 'style_end-')) {
            $property = str_replace('style_end-', '', $option);
            $setting_key = 'custom_data_animate_end_' . str_replace('-', '_', $property) . '_value';
            
            $value = $element->settings[$setting_key] ?? '';
            
            if ($value !== '') {
                $final_attributes[] = $option . ':' . esc_attr($value);
            }
        } 
        elseif ($option === 'custom') {
            if ($custom_value !== '') {
                $final_attributes[] = $custom_value;
            }
        } 
        else {
            $final_attributes[] = $option;
        }
    }
    
    if ( ! empty( $final_attributes ) ) {
        $attributes[ $key ]['data-animate'] = implode( ',', array_unique($final_attributes) );
    }

    return $attributes;

}, 1000, 3 );
