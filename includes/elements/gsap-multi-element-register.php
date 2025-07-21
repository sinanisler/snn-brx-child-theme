<?php

// Defines the target elements for the animation controls.
$targets = [ 'section', 'container', 'block', 'div' , 'heading' , 'text-basic' , 'text' ];

// Adds the custom controls to the specified Bricks Builder elements.
add_action( 'init', function () {
    global $targets;
    
    foreach ( $targets as $name ) {
        // Filters the controls for each target element.
        add_filter( "bricks/elements/{$name}/controls", function ( $controls ) {
            
            // Main dropdown to select animation types.
            $controls['custom_data_animate_dynamic_elements'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Select Animation', 'snn' ),
                'type'        => 'select',
                'options'     => [
                    // Group: Opacity
                    'style_start-opacity'         => esc_html__('Opacity Start', 'snn'),
                    'style_end-opacity'           => esc_html__('Opacity End', 'snn'),

                    // Group: Transform (Raw)
                    'style_start-transform'       => esc_html__('Transform Start', 'snn'),
                    'style_end-transform'         => esc_html__('Transform End', 'snn'),

                    // Group: Filter
                    'style_start-filter'          => esc_html__('Filter Start', 'snn'),
                    'style_end-filter'            => esc_html__('Filter End', 'snn'),

                    // Group: Width
                    'style_start-width'           => esc_html__('Width Start', 'snn'),
                    'style_end-width'             => esc_html__('Width End', 'snn'),

                    // Group: Height
                    'style_start-height'          => esc_html__('Height Start', 'snn'),
                    'style_end-height'            => esc_html__('Height End', 'snn'),

                    // Group: Rotate
                    'style_start-rotate'          => esc_html__('Rotate Start', 'snn'),
                    'style_end-rotate'            => esc_html__('Rotate End', 'snn'),

                    // Group: X Movement
                    'style_start-x'               => esc_html__('X Move Start', 'snn'),
                    'style_end-x'                 => esc_html__('X Move End', 'snn'),

                    // Group: Y Movement
                    'style_start-y'               => esc_html__('Y Move Start', 'snn'),
                    'style_end-y'                 => esc_html__('Y Move End', 'snn'),
                    
                    // Group: Scale
                    'style_start-scale'           => esc_html__('Scale Start', 'snn'),
                    'style_end-scale'             => esc_html__('Scale End', 'snn'),

                    // Group: Border Radius
                    'style_start-border-radius'   => esc_html__('Border Radius Start', 'snn'),
                    'style_end-border-radius'     => esc_html__('Border Radius End', 'snn'),

                    // Group: Font Size
                    'style_start-font-size'       => esc_html__('Font Size Start', 'snn'),
                    'style_end-font-size'         => esc_html__('Font Size End', 'snn'),

                    // Group: Font Weight
                    'style_start-font-weight'     => esc_html__('Font Weight Start', 'snn'),
                    'style_end-font-weight'       => esc_html__('Font Weight End', 'snn'),

                    // Group: Background
                    'style_start-background-size'     => esc_html__('Background Size Start', 'snn'),
                    'style_end-background-size'       => esc_html__('Background Size End', 'snn'),
                    'style_start-background-position' => esc_html__('Background Position Start', 'snn'),
                    'style_end-background-position'   => esc_html__('Background Position End', 'snn'),

                    // Group: SplitText
                    'splittext:true'              => esc_html__('Splittext True', 'snn'),
                    'splittext:word'              => esc_html__('Splittext Words', 'snn'),
                    'splittext:line'              => esc_html__('Splittext Line', 'snn'),

                    // Group: ScrollTrigger Options
                    'markers:true'                => esc_html__('Markers True', 'snn'),
                    'scroll:false'                => esc_html__('Scroll False', 'snn'),
                    'loop:true'                   => esc_html__('Loop True', 'snn'),
                    'pin:true'                    => esc_html__('Pin True', 'snn'),
                    'scrub:false'                 => esc_html__('Scrub False', 'snn'),

                    // Group: Responsive Visibility
                    'desktop:false'               => esc_html__('Desktop False', 'snn'),
                    'tablet:false'                => esc_html__('Tablet False', 'snn'),
                    'mobile:false'                => esc_html__('Mobile False', 'snn'),

                    // Group: New Direct Animation Properties
                    'start'                       => esc_html__('Start Trigger', 'snn'),
                    'end'                         => esc_html__('End Trigger', 'snn'),
                    'duration'                    => esc_html__('Duration', 'snn'),
                    'stagger'                     => esc_html__('Stagger', 'snn'),
                    'delay'                       => esc_html__('Delay', 'snn'),
                    
                    // Group: Misc
                    'once:true'                   => esc_html__('Once True', 'snn'),
                    
                    // Group: Custom Input
                    'custom'                      => esc_html__('/ Custom data-animate', 'snn'),
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

            // Opacity End Value control
            $controls['custom_data_animate_end_opacity_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Opacity', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0, 1, 0.1',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-opacity' ],
                'inline'      => true,
            ];

            // Transform Start Value control
            $controls['custom_data_animate_start_transform_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Transform', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'scale(0), translateX(100px)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_start-transform' ],
                'inline'      => true,
            ];

            // Transform End Value control
            $controls['custom_data_animate_end_transform_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Transform', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'scale(1), translateX(0)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-transform' ],
                'inline'      => true,
            ];

            // Filter Start Value control
            $controls['custom_data_animate_start_filter_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Filter', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'blur(10px), grayscale(100%)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_start-filter' ],
                'inline'      => true,
            ];

            // Filter End Value control
            $controls['custom_data_animate_end_filter_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Filter', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'blur(0), grayscale(0)',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'style_end-filter' ],
                'inline'      => true,
            ];

            // Width Controls
            $controls['custom_data_animate_start_width_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Width', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-width'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_width_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Width', 'snn'), 'type' => 'text',
                'placeholder' => '100px, 100%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-width'], 'inline' => true,
            ];

            // Height Controls
            $controls['custom_data_animate_start_height_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Height', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 50vh', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-height'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_height_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Height', 'snn'), 'type' => 'text',
                'placeholder' => '100px, 100vh', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-height'], 'inline' => true,
            ];

            // Rotate Controls
            $controls['custom_data_animate_start_rotate_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Rotate', 'snn'), 'type' => 'text',
                'placeholder' => '0deg, 1rad', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-rotate'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_rotate_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Rotate', 'snn'), 'type' => 'text',
                'placeholder' => '360deg, 2rad', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-rotate'], 'inline' => true,
            ];

            // X Move Controls
            $controls['custom_data_animate_start_x_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start X Move', 'snn'), 'type' => 'text',
                'placeholder' => '-100px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-x'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_x_value'] = [
                'tab' => 'content', 'label' => esc_html__('End X Move', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 0%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-x'], 'inline' => true,
            ];

            // Y Move Controls
            $controls['custom_data_animate_start_y_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Y Move', 'snn'), 'type' => 'text',
                'placeholder' => '-100px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-y'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_y_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Y Move', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 0%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-y'], 'inline' => true,
            ];

            // Scale Controls
            $controls['custom_data_animate_start_scale_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Scale', 'snn'), 'type' => 'number',
                'placeholder' => '0.5', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-scale'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_scale_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Scale', 'snn'), 'type' => 'number',
                'placeholder' => '1', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-scale'], 'inline' => true,
            ];

            // Border Radius Controls
            $controls['custom_data_animate_start_border_radius_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Border Radius', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 0%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-border-radius'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_border_radius_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Border Radius', 'snn'), 'type' => 'text',
                'placeholder' => '25px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-border-radius'], 'inline' => true,
            ];

            // Font Size Controls
            $controls['custom_data_animate_start_font_size_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Font Size', 'snn'), 'type' => 'text',
                'placeholder' => '12px, 1rem', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-font-size'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_font_size_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Font Size', 'snn'), 'type' => 'text',
                'placeholder' => '24px, 2rem', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-font-size'], 'inline' => true,
            ];

            // Font Weight Controls
            $controls['custom_data_animate_start_font_weight_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Font Weight', 'snn'), 'type' => 'number',
                'placeholder' => '400', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-font-weight'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_font_weight_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Font Weight', 'snn'), 'type' => 'number',
                'placeholder' => '700', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-font-weight'], 'inline' => true,
            ];

            // Background Size Controls
            $controls['custom_data_animate_start_background_size_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Background Size', 'snn'), 'type' => 'text', // Changed to text for 'cover', 'auto'
                'placeholder' => '100%, cover', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-background-size'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_background_size_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Background Size', 'snn'), 'type' => 'text', // Changed to text for 'cover', 'auto'
                'placeholder' => '120%, auto', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-background-size'], 'inline' => true,
            ];

            // Background Position Controls
            $controls['custom_data_animate_start_background_position_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Background Position', 'snn'), 'type' => 'text', // Changed to text for 'center'
                'placeholder' => '0% 50%, center', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-background-position'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_background_position_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Background Position', 'snn'), 'type' => 'text', // Changed to text for 'center'
                'placeholder' => '100% 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-background-position'], 'inline' => true,
            ];

            // New: Start Trigger Control
            $controls['custom_data_animate_start_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Start Trigger', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'top center, 20% 80%',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'start' ],
                'inline'      => true,
            ];

            // New: End Trigger Control
            $controls['custom_data_animate_end_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'End Trigger', 'snn' ),
                'type'        => 'text',
                'placeholder' => 'bottom center, 80% 20%',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'end' ],
                'inline'      => true,
            ];

            // New: Duration Control
            $controls['custom_data_animate_duration_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Duration (seconds)', 'snn' ),
                'type'        => 'number',
                'placeholder' => '1, 0.5',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'duration' ],
                'inline'      => true,
            ];

            // New: Stagger Control
            $controls['custom_data_animate_stagger_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Stagger (seconds)', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0.1, 0.05',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'stagger' ],
                'inline'      => true,
            ];

            // New: Delay Control
            $controls['custom_data_animate_delay_value'] = [
                'tab'         => 'content',
                'label'       => esc_html__( 'Delay (seconds)', 'snn' ),
                'type'        => 'number',
                'placeholder' => '0.5, 1',
                'required'    => [ 'custom_data_animate_dynamic_elements', 'includes', 'delay' ],
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

// This filter processes the settings and adds the final `data-animate` attribute to the element.
add_filter( 'bricks/element/render_attributes', function( $attributes, $key, $element ) {
    global $targets;

    $selected_options = $element->settings['custom_data_animate_dynamic_elements'] ?? [];
    $custom_value     = $element->settings['custom_data_animate_dynamic_elements_custom'] ?? '';

    // Only proceed if the element is in our target list and animation options are selected
    if ( ! in_array( $element->name, $targets, true ) || empty( $selected_options ) ) {
        return $attributes; 
    }

    // Ensure selected_options is an array
    if ( ! is_array( $selected_options ) ) {
        $selected_options = explode( ',', $selected_options );
    }

    $final_attributes = [];

    // Loop through each selected option from the dropdown
    foreach ($selected_options as $option) {
        // Handle options that require a user-defined value (e.g., style_start-opacity)
        if (str_starts_with($option, 'style_start-')) {
            $property = str_replace('style_start-', '', $option);
            // Dynamically create the setting key name to look for the value
            $setting_key = 'custom_data_animate_start_' . str_replace('-', '_', $property) . '_value';
            
            $value = $element->settings[$setting_key] ?? '';
            
            if ($value !== '') {
                // Combine the option and its value, e.g., "style_start-opacity:0.1"
                $final_attributes[] = $option . ':' . esc_attr($value);
            }
        } 
        // Handle 'end' state options similarly
        elseif (str_starts_with($option, 'style_end-')) {
            $property = str_replace('style_end-', '', $option);
            $setting_key = 'custom_data_animate_end_' . str_replace('-', '_', $property) . '_value';
            
            $value = $element->settings[$setting_key] ?? '';
            
            if ($value !== '') {
                $final_attributes[] = $option . ':' . esc_attr($value);
            }
        } 
        // Handle new direct animation properties (start, end, duration, stagger, delay)
        elseif (in_array($option, ['start', 'end', 'duration', 'stagger', 'delay'])) {
            $setting_key = 'custom_data_animate_' . $option . '_value';
            $value = $element->settings[$setting_key] ?? '';
            
            if ($value !== '') {
                $final_attributes[] = $option . ':' . esc_attr($value);
            }
        }
        // Handle the custom text input
        elseif ($option === 'custom') {
            if ($custom_value !== '') {
                $final_attributes[] = $custom_value;
            }
        } 
        // Handle simple boolean-like options (e.g., 'pin:true')
        else {
            $final_attributes[] = $option;
        }
    }
    
    // If we have attributes to add, combine them into a single string and add to the element.
    if ( ! empty( $final_attributes ) ) {
        $attributes[ $key ]['data-animate'] = implode( ',', array_unique($final_attributes) );
    }

    return $attributes;

}, 1000, 3 );
