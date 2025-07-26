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

                    // Group: Scale Individual Axis
                    'style_start-scaleX'          => esc_html__('Scale X Start', 'snn'),
                    'style_end-scaleX'            => esc_html__('Scale X End', 'snn'),
                    'style_start-scaleY'          => esc_html__('Scale Y Start', 'snn'),
                    'style_end-scaleY'            => esc_html__('Scale Y End', 'snn'),

                    // Group: Skew
                    'style_start-skewX'           => esc_html__('Skew X Start', 'snn'),
                    'style_end-skewX'             => esc_html__('Skew X End', 'snn'),
                    'style_start-skewY'           => esc_html__('Skew Y Start', 'snn'),
                    'style_end-skewY'             => esc_html__('Skew Y End', 'snn'),

                    // Group: 3D Rotations
                    'style_start-rotateX'         => esc_html__('Rotate X Start', 'snn'),
                    'style_end-rotateX'           => esc_html__('Rotate X End', 'snn'),
                    'style_start-rotateY'         => esc_html__('Rotate Y Start', 'snn'),
                    'style_end-rotateY'           => esc_html__('Rotate Y End', 'snn'),
                    'style_start-rotateZ'         => esc_html__('Rotate Z Start', 'snn'),
                    'style_end-rotateZ'           => esc_html__('Rotate Z End', 'snn'),

                    // Group: Perspective
                    'style_start-perspective'     => esc_html__('Perspective Start', 'snn'),
                    'style_end-perspective'       => esc_html__('Perspective End', 'snn'),

                    // Group: Color Animations
                    'style_start-color'           => esc_html__('Text Color Start', 'snn'),
                    'style_end-color'             => esc_html__('Text Color End', 'snn'),
                    'style_start-background-color' => esc_html__('Background Color Start', 'snn'),
                    'style_end-background-color'   => esc_html__('Background Color End', 'snn'),
                    'style_start-border-color'    => esc_html__('Border Color Start', 'snn'),
                    'style_end-border-color'      => esc_html__('Border Color End', 'snn'),
                    'style_start-fill'            => esc_html__('SVG Fill Start', 'snn'),
                    'style_end-fill'              => esc_html__('SVG Fill End', 'snn'),
                    'style_start-stroke'          => esc_html__('SVG Stroke Start', 'snn'),
                    'style_end-stroke'            => esc_html__('SVG Stroke End', 'snn'),

                    // Group: Layout Properties - Margin
                    'style_start-margin'          => esc_html__('Margin Start', 'snn'),
                    'style_end-margin'            => esc_html__('Margin End', 'snn'),
                    'style_start-margin-top'      => esc_html__('Margin Top Start', 'snn'),
                    'style_end-margin-top'        => esc_html__('Margin Top End', 'snn'),
                    'style_start-margin-right'    => esc_html__('Margin Right Start', 'snn'),
                    'style_end-margin-right'      => esc_html__('Margin Right End', 'snn'),
                    'style_start-margin-bottom'   => esc_html__('Margin Bottom Start', 'snn'),
                    'style_end-margin-bottom'     => esc_html__('Margin Bottom End', 'snn'),
                    'style_start-margin-left'     => esc_html__('Margin Left Start', 'snn'),
                    'style_end-margin-left'       => esc_html__('Margin Left End', 'snn'),

                    // Group: Layout Properties - Padding
                    'style_start-padding'         => esc_html__('Padding Start', 'snn'),
                    'style_end-padding'           => esc_html__('Padding End', 'snn'),
                    'style_start-padding-top'     => esc_html__('Padding Top Start', 'snn'),
                    'style_end-padding-top'       => esc_html__('Padding Top End', 'snn'),
                    'style_start-padding-right'   => esc_html__('Padding Right Start', 'snn'),
                    'style_end-padding-right'     => esc_html__('Padding Right End', 'snn'),
                    'style_start-padding-bottom'  => esc_html__('Padding Bottom Start', 'snn'),
                    'style_end-padding-bottom'    => esc_html__('Padding Bottom End', 'snn'),
                    'style_start-padding-left'    => esc_html__('Padding Left Start', 'snn'),
                    'style_end-padding-left'      => esc_html__('Padding Left End', 'snn'),

                    // Group: Layout Properties - Position
                    'style_start-top'             => esc_html__('Top Position Start', 'snn'),
                    'style_end-top'               => esc_html__('Top Position End', 'snn'),
                    'style_start-right'           => esc_html__('Right Position Start', 'snn'),
                    'style_end-right'             => esc_html__('Right Position End', 'snn'),
                    'style_start-bottom'          => esc_html__('Bottom Position Start', 'snn'),
                    'style_end-bottom'            => esc_html__('Bottom Position End', 'snn'),
                    'style_start-left'            => esc_html__('Left Position Start', 'snn'),
                    'style_end-left'              => esc_html__('Left Position End', 'snn'),
                    'style_start-z-index'         => esc_html__('Z-Index Start', 'snn'),
                    'style_end-z-index'           => esc_html__('Z-Index End', 'snn'),

                    // Group: Border Radius
                    'style_start-border-radius'   => esc_html__('Border Radius Start', 'snn'),
                    'style_end-border-radius'     => esc_html__('Border Radius End', 'snn'),

                    // Group: Border Properties
                    'style_start-border-width'    => esc_html__('Border Width Start', 'snn'),
                    'style_end-border-width'      => esc_html__('Border Width End', 'snn'),
                    'style_start-outline'         => esc_html__('Outline Start', 'snn'),
                    'style_end-outline'           => esc_html__('Outline End', 'snn'),

                    // Group: Font Size
                    'style_start-font-size'       => esc_html__('Font Size Start', 'snn'),
                    'style_end-font-size'         => esc_html__('Font Size End', 'snn'),

                    // Group: Font Weight
                    'style_start-font-weight'     => esc_html__('Font Weight Start', 'snn'),
                    'style_end-font-weight'       => esc_html__('Font Weight End', 'snn'),

                    // Group: Text Properties
                    'style_start-line-height'     => esc_html__('Line Height Start', 'snn'),
                    'style_end-line-height'       => esc_html__('Line Height End', 'snn'),
                    'style_start-letter-spacing'  => esc_html__('Letter Spacing Start', 'snn'),
                    'style_end-letter-spacing'    => esc_html__('Letter Spacing End', 'snn'),
                    'style_start-word-spacing'    => esc_html__('Word Spacing Start', 'snn'),
                    'style_end-word-spacing'      => esc_html__('Word Spacing End', 'snn'),
                    'style_start-text-indent'     => esc_html__('Text Indent Start', 'snn'),
                    'style_end-text-indent'       => esc_html__('Text Indent End', 'snn'),

                    // Group: Advanced Effects
                    'style_start-clip-path'       => esc_html__('Clip Path Start', 'snn'),
                    'style_end-clip-path'         => esc_html__('Clip Path End', 'snn'),
                    'style_start-mask'            => esc_html__('Mask Start', 'snn'),
                    'style_end-mask'              => esc_html__('Mask End', 'snn'),
                    'style_start-box-shadow'      => esc_html__('Box Shadow Start', 'snn'),
                    'style_end-box-shadow'        => esc_html__('Box Shadow End', 'snn'),
                    'style_start-text-shadow'     => esc_html__('Text Shadow Start', 'snn'),
                    'style_end-text-shadow'       => esc_html__('Text Shadow End', 'snn'),

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

            // Individual Scale Controls
            $controls['custom_data_animate_start_scaleX_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Scale X', 'snn'), 'type' => 'number',
                'placeholder' => '0.5', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-scaleX'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_scaleX_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Scale X', 'snn'), 'type' => 'number',
                'placeholder' => '1', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-scaleX'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_scaleY_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Scale Y', 'snn'), 'type' => 'number',
                'placeholder' => '0.5', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-scaleY'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_scaleY_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Scale Y', 'snn'), 'type' => 'number',
                'placeholder' => '1', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-scaleY'], 'inline' => true,
            ];

            // Skew Controls
            $controls['custom_data_animate_start_skewX_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Skew X', 'snn'), 'type' => 'text',
                'placeholder' => '0deg, 15deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-skewX'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_skewX_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Skew X', 'snn'), 'type' => 'text',
                'placeholder' => '45deg, 0deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-skewX'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_skewY_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Skew Y', 'snn'), 'type' => 'text',
                'placeholder' => '0deg, 15deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-skewY'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_skewY_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Skew Y', 'snn'), 'type' => 'text',
                'placeholder' => '45deg, 0deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-skewY'], 'inline' => true,
            ];

            // 3D Rotation Controls
            $controls['custom_data_animate_start_rotateX_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Rotate X', 'snn'), 'type' => 'text',
                'placeholder' => '0deg, 90deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-rotateX'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_rotateX_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Rotate X', 'snn'), 'type' => 'text',
                'placeholder' => '360deg, 0deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-rotateX'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_rotateY_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Rotate Y', 'snn'), 'type' => 'text',
                'placeholder' => '0deg, 90deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-rotateY'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_rotateY_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Rotate Y', 'snn'), 'type' => 'text',
                'placeholder' => '360deg, 0deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-rotateY'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_rotateZ_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Rotate Z', 'snn'), 'type' => 'text',
                'placeholder' => '0deg, 90deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-rotateZ'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_rotateZ_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Rotate Z', 'snn'), 'type' => 'text',
                'placeholder' => '360deg, 0deg', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-rotateZ'], 'inline' => true,
            ];

            // Perspective Controls
            $controls['custom_data_animate_start_perspective_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Perspective', 'snn'), 'type' => 'text',
                'placeholder' => '1000px, 500px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-perspective'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_perspective_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Perspective', 'snn'), 'type' => 'text',
                'placeholder' => '2000px, 800px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-perspective'], 'inline' => true,
            ];

            // Color Controls
            $controls['custom_data_animate_start_color_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Text Color', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-color'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_color_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Text Color', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-color'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_background_color_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Background Color', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-background-color'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_background_color_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Background Color', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-background-color'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_border_color_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Border Color', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-border-color'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_border_color_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Border Color', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-border-color'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_fill_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start SVG Fill', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-fill'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_fill_value'] = [
                'tab' => 'content', 'label' => esc_html__('End SVG Fill', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-fill'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_stroke_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start SVG Stroke', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-stroke'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_stroke_value'] = [
                'tab' => 'content', 'label' => esc_html__('End SVG Stroke', 'snn'), 'type' => 'color',
                'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-stroke'], 'inline' => true,
            ];

            // Margin Controls
            $controls['custom_data_animate_start_margin_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Margin', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px 20px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-margin'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_margin_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Margin', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-margin'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_margin_top_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Margin Top', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-margin-top'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_margin_top_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Margin Top', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-margin-top'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_margin_right_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Margin Right', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-margin-right'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_margin_right_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Margin Right', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-margin-right'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_margin_bottom_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Margin Bottom', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-margin-bottom'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_margin_bottom_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Margin Bottom', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-margin-bottom'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_margin_left_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Margin Left', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-margin-left'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_margin_left_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Margin Left', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-margin-left'], 'inline' => true,
            ];

            // Padding Controls
            $controls['custom_data_animate_start_padding_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Padding', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px 20px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-padding'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_padding_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Padding', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-padding'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_padding_top_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Padding Top', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-padding-top'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_padding_top_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Padding Top', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-padding-top'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_padding_right_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Padding Right', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-padding-right'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_padding_right_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Padding Right', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-padding-right'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_padding_bottom_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Padding Bottom', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-padding-bottom'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_padding_bottom_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Padding Bottom', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-padding-bottom'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_padding_left_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Padding Left', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 10px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-padding-left'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_padding_left_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Padding Left', 'snn'), 'type' => 'text',
                'placeholder' => '20px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-padding-left'], 'inline' => true,
            ];

            // Position Controls
            $controls['custom_data_animate_start_top_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Top Position', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-top'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_top_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Top Position', 'snn'), 'type' => 'text',
                'placeholder' => '100px, 10%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-top'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_right_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Right Position', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-right'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_right_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Right Position', 'snn'), 'type' => 'text',
                'placeholder' => '100px, 10%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-right'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_bottom_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Bottom Position', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-bottom'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_bottom_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Bottom Position', 'snn'), 'type' => 'text',
                'placeholder' => '100px, 10%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-bottom'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_left_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Left Position', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 50%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-left'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_left_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Left Position', 'snn'), 'type' => 'text',
                'placeholder' => '100px, 10%', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-left'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_z_index_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Z-Index', 'snn'), 'type' => 'number',
                'placeholder' => '1, 10', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-z-index'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_z_index_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Z-Index', 'snn'), 'type' => 'number',
                'placeholder' => '100, 1', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-z-index'], 'inline' => true,
            ];

            // Border Width Controls
            $controls['custom_data_animate_start_border_width_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Border Width', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 1px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-border-width'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_border_width_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Border Width', 'snn'), 'type' => 'text',
                'placeholder' => '5px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-border-width'], 'inline' => true,
            ];

            // Outline Controls
            $controls['custom_data_animate_start_outline_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Outline', 'snn'), 'type' => 'text',
                'placeholder' => 'none, 1px solid red', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-outline'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_outline_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Outline', 'snn'), 'type' => 'text',
                'placeholder' => '3px solid blue, none', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-outline'], 'inline' => true,
            ];

            // Text Properties Controls
            $controls['custom_data_animate_start_line_height_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Line Height', 'snn'), 'type' => 'text',
                'placeholder' => '1, 1.5', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-line-height'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_line_height_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Line Height', 'snn'), 'type' => 'text',
                'placeholder' => '2, 1', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-line-height'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_letter_spacing_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Letter Spacing', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 2px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-letter-spacing'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_letter_spacing_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Letter Spacing', 'snn'), 'type' => 'text',
                'placeholder' => '5px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-letter-spacing'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_word_spacing_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Word Spacing', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 5px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-word-spacing'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_word_spacing_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Word Spacing', 'snn'), 'type' => 'text',
                'placeholder' => '10px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-word-spacing'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_text_indent_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Text Indent', 'snn'), 'type' => 'text',
                'placeholder' => '0px, 20px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-text-indent'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_text_indent_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Text Indent', 'snn'), 'type' => 'text',
                'placeholder' => '50px, 0px', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-text-indent'], 'inline' => true,
            ];

            // Advanced Effects Controls
            $controls['custom_data_animate_start_clip_path_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Clip Path', 'snn'), 'type' => 'text',
                'placeholder' => 'circle(0%), polygon(0 0, 0 0, 0 0)', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-clip-path'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_clip_path_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Clip Path', 'snn'), 'type' => 'text',
                'placeholder' => 'circle(100%), polygon(0 0, 100% 0, 100% 100%, 0 100%)', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-clip-path'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_mask_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Mask', 'snn'), 'type' => 'text',
                'placeholder' => 'none, url(#mask)', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-mask'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_mask_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Mask', 'snn'), 'type' => 'text',
                'placeholder' => 'url(#mask2), none', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-mask'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_box_shadow_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Box Shadow', 'snn'), 'type' => 'text',
                'placeholder' => 'none, 0 0 10px rgba(0,0,0,0.5)', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-box-shadow'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_box_shadow_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Box Shadow', 'snn'), 'type' => 'text',
                'placeholder' => '0 0 20px rgba(0,0,0,0.8), none', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-box-shadow'], 'inline' => true,
            ];
            $controls['custom_data_animate_start_text_shadow_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Text Shadow', 'snn'), 'type' => 'text',
                'placeholder' => 'none, 1px 1px 2px rgba(0,0,0,0.5)', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-text-shadow'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_text_shadow_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Text Shadow', 'snn'), 'type' => 'text',
                'placeholder' => '2px 2px 4px rgba(0,0,0,0.8), none', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-text-shadow'], 'inline' => true,
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
                'tab' => 'content', 'label' => esc_html__('Start Background Size', 'snn'), 'type' => 'text',
                'placeholder' => '100%, cover', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-background-size'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_background_size_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Background Size', 'snn'), 'type' => 'text',
                'placeholder' => '120%, auto', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_end-background-size'], 'inline' => true,
            ];

            // Background Position Controls
            $controls['custom_data_animate_start_background_position_value'] = [
                'tab' => 'content', 'label' => esc_html__('Start Background Position', 'snn'), 'type' => 'text',
                'placeholder' => '0% 50%, center', 'required' => ['custom_data_animate_dynamic_elements', 'includes', 'style_start-background-position'], 'inline' => true,
            ];
            $controls['custom_data_animate_end_background_position_value'] = [
                'tab' => 'content', 'label' => esc_html__('End Background Position', 'snn'), 'type' => 'text',
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

add_filter( 'bricks/element/render_attributes', function( $attributes, $key, $element ) {
    global $targets;

    $selected_options = $element->settings['custom_data_animate_dynamic_elements'] ?? [];
    $custom_value     = $element->settings['custom_data_animate_dynamic_elements_custom'] ?? '';

    if ( ! in_array( $element->name, $targets, true ) || empty( $selected_options ) ) {
        return $attributes; 
    }

    if ( ! is_array( $selected_options ) ) {
        $selected_options = explode( ',', $selected_options );
    }

    $final_attributes = [];

    foreach ($selected_options as $option) {
        
        // --- BACKWARD COMPATIBILITY & PROCESSING LOGIC ---
        
        if (str_starts_with($option, 'style_start-')) {
            // Check if it's an old value (contains ':') or a new key
            if (strpos($option, ':') !== false) {
                $final_attributes[] = $option; // It's an old value, use it directly
            } else {
                // It's a new key, get the value from its dedicated field
                $property = str_replace('style_start-', '', $option);
                $setting_key = 'custom_data_animate_start_' . str_replace('-', '_', $property) . '_value';
                $value = $element->settings[$setting_key] ?? '';
                if ($value !== '') {
                    $final_attributes[] = $option . ':' . esc_attr($value);
                }
            }
        } 
        elseif (str_starts_with($option, 'style_end-')) {
            // Check if it's an old value (contains ':') or a new key
            if (strpos($option, ':') !== false) {
                $final_attributes[] = $option; // It's an old value, use it directly
            } else {
                // It's a new key, get the value from its dedicated field
                $property = str_replace('style_end-', '', $option);
                $setting_key = 'custom_data_animate_end_' . str_replace('-', '_', $property) . '_value';
                $value = $element->settings[$setting_key] ?? '';
                if ($value !== '') {
                    $final_attributes[] = $option . ':' . esc_attr($value);
                }
            }
        } 
        elseif (in_array($option, ['start', 'end', 'duration', 'stagger', 'delay'])) {
            // New system keys that require a value
            $setting_key = 'custom_data_animate_' . $option . '_value';
            $value = $element->settings[$setting_key] ?? '';
            if ($value !== '') {
                $final_attributes[] = $option . ':' . esc_attr($value);
            }
        }
        elseif ($option === 'custom') {
            // Handle the custom text input
            if ($custom_value !== '') {
                $final_attributes[] = $custom_value;
            }
        } 
        else {
            $final_attributes[] = $option;
        }
    }
    
    if ( ! empty( $final_attributes ) ) {
        // Use array_unique to prevent duplicates if both old and new data somehow coexist.
        $attributes[ $key ]['data-animate'] = implode( ',', array_unique($final_attributes) );
    }

    return $attributes;

}, 1000, 3 );
