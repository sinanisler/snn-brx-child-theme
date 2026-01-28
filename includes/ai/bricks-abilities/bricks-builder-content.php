<?php
/**
 * Generate Bricks Builder Content Ability
 *
 * This ability generates rich, detailed Bricks Builder elements using Bricks JSON structure.
 * It can create complete sections like heroes, about sections, services, CTAs, and more.
 *
 * The generated content uses proper Bricks element structure and follows Bricks best practices.
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_generate_bricks_content_ability' );
function snn_register_generate_bricks_content_ability() {
    wp_register_ability(
        'snn/generate-bricks-content',
        array(
            'label'       => __( 'Generate Bricks Content', 'snn' ),
            'description' => __( 'Generates rich, detailed Bricks Builder sections using Bricks JSON structure. Creates complete sections from scratch with professional styling. Use for rapid prototyping and structured layouts in Bricks Builder.

SECTION TYPES (section_type):
- hero: Hero banners with headings, descriptions, CTAs (large text, centered, buttons) [FULL-WIDTH]
- about: About sections with text + imagery (2-column, lists, professional) [CONTAINED]
- services: Service grids (1-4 columns, titles, descriptions) [CONTAINED]
- cta: Call-to-action sections (centered, prominent buttons, conversion-focused) [FULL-WIDTH]
- testimonials: Customer reviews (quotes in columns, attribution) [CONTAINED]
- team: Team member grids (images, names, positions, configurable columns) [CONTAINED]
- stats: Statistics showcase (large numbers, labels, multi-column) [CONTAINED]
- faq: FAQ sections (accordion/expandable Q&A) [CONTAINED]
- custom: Generic flexible section [CONTAINED]

STYLE OPTIONS (style_preference):
modern (clean/contemporary), minimal (essential only), bold (attention-grabbing), elegant (refined), playful (creative/fun), professional (corporate), creative (artistic/unique)

KEY PARAMETERS:
- content_description: BE SPECIFIC about headings, text, items, layout. Good: "Hero with heading \'Transform Business\', subtext about digital services, \'Get Started\' button". Bad: "Make a hero"
- layout_columns: 1-4 columns for grids (services, team, testimonials, stats)
- color_scheme: {background: "#ffffff", text: "#000000", accent: "#0066cc", secondary: "#f5f5f5"}
- spacing: compact (small gaps), normal (balanced), spacious (large gaps)
- action_type: replace (all content), append (add to end), prepend (add to start)

USAGE EXAMPLES:
1. Hero: {section_type: "hero", content_description: "Heading \'Welcome to Our Agency\', description about creative services, \'Start Project\' button", style_preference: "modern", color_scheme: {background: "#000000", text: "#ffffff", accent: "#00ff88"}}
2. Services: {section_type: "services", content_description: "3 services: Web Dev (custom sites), Mobile Apps (iOS/Android), Cloud (scalable)", layout_columns: 3}
3. Stats: {section_type: "stats", content_description: "500+ projects, 50+ team, 98% satisfaction", spacing: "spacious"}
4. Team: {section_type: "team", content_description: "4 leadership members with photos and titles", layout_columns: 4, include_images: true}

AVAILABLE BRICKS ELEMENTS:
Layout: section, container, div, block | Content: heading, text-basic, text, list, image | Interactive: button, form, accordion, tabs | Advanced: code, video, audio, icon

CRITICAL BRICKS JSON STRUCTURE:

1. ELEMENT STRUCTURE:
   - Every element needs: id, name, parent, children[], settings{}
   - IDs must be unique 6-character strings (e.g., "qognhn", "aolxaq")
   - Parent is either 0 (root) or another element\'s ID
   - Children is array of child element IDs

2. SETTINGS PROPERTIES (all prefixed with underscore):
   Layout:
   - _width, _height, _maxWidth (values with units: "800px", "100%")
   - _margin, _padding: {top: "40", right: "20", bottom: "40", left: "20"}
   - _display: "flex", "grid", "block"
   - _flexDirection: "row", "column"
   - _alignItems: "flex-start", "center", "flex-end", "stretch"
   - _justifyContent: "flex-start", "center", "flex-end", "space-between"
   - _columnGap, _rowGap: "20", "40"
   - _gridTemplateColumns: "1fr 1fr", "repeat(3, 1fr)"
   - _gridGap: "20"

   Background & Colors:
   - _background: {color: {raw: "var(--c1)", hex: "#000000"}, image: {url: "..."}, position: "center center"}
   - _gradient: {applyTo: "overlay", colors: [{color: {hex: "#000000"}, stop: "0"}, {...}]}
   - _backgroundColor: {raw: "var(--c1)", hex: "#ffffff"}

   Typography:
   - _typography: {
       font-family: "Poppins" (any Google Fonts name),
       font-size: "48px",
       font-weight: "600",
       line-height: "1.2",
       letter-spacing: "1px",
       text-transform: "uppercase",
       color: {raw: "var(--c2)", hex: "#000000"}
     }
   - Available Google Fonts: Just use the font name directly (e.g., "Inter", "Roboto", "Montserrat", "Playfair Display", "ADLaM Display", "Cabin Condensed", etc.)

   Border:
   - _border: {
       radius: {top: "8", right: "8", bottom: "8", left: "8"},
       width: {top: "2", right: "2", bottom: "2", left: "2"},
       style: "solid",
       color: {raw: "var(--c2)", hex: "#e0e0e0"}
     }

   Responsive:
   - Add :tablet or :mobile_landscape suffix to any property
   - Example: _height:mobile_landscape: "400"

3. COMMON ELEMENTS:

   Section (root container):
   {
     id: "abc123",
     name: "section",
     parent: 0,
     children: ["xyz789"],
     settings: {
       _height: "600",
       _background: {color: {hex: "#ffffff"}},
       _padding: {top: "80", bottom: "80"}
     }
   }

   Container (content wrapper):
   {
     id: "xyz789",
     name: "container",
     parent: "abc123",
     children: [],
     settings: {
       _columnGap: "40",
       _rowGap: "40"
     }
   }

   Heading:
   {
     id: "head01",
     name: "heading",
     parent: "xyz789",
     children: [],
     settings: {
       text: "Your Heading Here",
       tag: "h2",
       _typography: {
         font-size: "48px",
         font-weight: "700",
         line-height: "1.2",
         color: {hex: "#000000"}
       }
     }
   }

   Text:
   {
     id: "text01",
     name: "text-basic",
     parent: "xyz789",
     children: [],
     settings: {
       text: "Your paragraph text here",
       _typography: {
         font-size: "16px",
         line-height: "1.6",
         color: {hex: "#333333"}
       }
     }
   }

   Button:
   {
     id: "btn001",
     name: "button",
     parent: "xyz789",
     children: [],
     settings: {
       text: "Click Me",
       link: {url: "#"},
       _typography: {
         font-size: "16px",
         font-weight: "600",
         color: {hex: "#ffffff"}
       },
       _backgroundColor: {hex: "#0066cc"},
       _padding: {top: "12", right: "32", bottom: "12", left: "32"},
       _border: {radius: {top: "4", right: "4", bottom: "4", left: "4"}}
     }
   }

   Block (div with flex/grid):
   {
     id: "blk001",
     name: "block",
     parent: "xyz789",
     children: [],
     settings: {
       _display: "grid",
       _gridTemplateColumns: "1fr 1fr 1fr",
       _gridGap: "24",
       _gridTemplateColumns:mobile_landscape: "1fr"
     }
   }

   Image:
   {
     id: "img001",
     name: "image",
     parent: "xyz789",
     children: [],
     settings: {
       image: {
         url: "https://placeholder.com/800x600",
         id: 0,
         size: "full"
       },
       _objectFit: "cover",
       _width: "100%",
       _height: "400px"
     }
   }

4. SPACING GUIDELINES:
   - Compact: section padding 40, gaps 16
   - Normal: section padding 80, gaps 24-40
   - Spacious: section padding 120, gaps 40-60

5. COLOR SYSTEM:
   - Use CSS variables: var(--c1), var(--c2), var(--c3) for theme colors
   - Or direct hex codes: #000000, #ffffff
   - Format: {raw: "var(--c1)", hex: "#000000"} or just {hex: "#000000"}

6. RESPONSIVE DESIGN:
   - Use :tablet and :mobile_landscape suffixes
   - Example: _gridTemplateColumns:mobile_landscape: "1fr"
   - Common breakpoints: reduce columns, stack layouts, adjust heights

BEST PRACTICES:
- Use descriptive content_description (specific headings, exact text, structure details)
- Match colors to brand (use color_scheme with hex codes)
- Choose appropriate spacing (compact for dense info, spacious for emphasis)
- Use layout_columns based on content: 1 (detailed), 2 (balanced), 3 (standard grid), 4 (dense grid)
- Always include responsive variants for mobile/tablet
- Generate unique 6-char IDs for each element
- Properly nest parent-child relationships
- Use container elements to constrain content width
- Apply consistent spacing throughout sections', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'section_type', 'content_description' ),
                'properties' => array(
                    'section_type' => array(
                        'type'        => 'string',
                        'enum'        => array( 'hero', 'about', 'services', 'cta', 'testimonials', 'team', 'stats', 'faq', 'custom' ),
                        'description' => 'Type of section to generate. Choose the category that best fits the desired section.',
                    ),
                    'content_description' => array(
                        'type'        => 'string',
                        'description' => 'Detailed description of what the section should contain. Include: main message/heading, key points, number of items, desired layout (columns, rows), style preferences, any special features needed.',
                        'minLength'   => 10,
                    ),
                    'style_preference' => array(
                        'type'        => 'string',
                        'enum'        => array( 'modern', 'minimal', 'bold', 'elegant', 'playful', 'professional', 'creative' ),
                        'description' => 'Overall style/mood for the section design.',
                        'default'     => 'modern',
                    ),
                    'layout_columns' => array(
                        'type'        => 'integer',
                        'description' => 'Number of columns for layouts that support it (1-4). Default: 3',
                        'minimum'     => 1,
                        'maximum'     => 4,
                        'default'     => 3,
                    ),
                    'include_images' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to include image elements with placeholder URLs.',
                        'default'     => true,
                    ),
                    'color_scheme' => array(
                        'type'        => 'object',
                        'description' => 'Optional color scheme. Provide background, text, accent, and secondary colors.',
                        'properties'  => array(
                            'background' => array( 'type' => 'string', 'description' => 'Background color (hex)' ),
                            'text'       => array( 'type' => 'string', 'description' => 'Text color (hex)' ),
                            'accent'     => array( 'type' => 'string', 'description' => 'Accent color for buttons, highlights (hex)' ),
                            'secondary'  => array( 'type' => 'string', 'description' => 'Secondary background color (hex)' ),
                        ),
                    ),
                    'spacing' => array(
                        'type'        => 'string',
                        'enum'        => array( 'compact', 'normal', 'spacious' ),
                        'description' => 'Spacing between elements.',
                        'default'     => 'normal',
                    ),
                    'action_type' => array(
                        'type'        => 'string',
                        'enum'        => array( 'replace', 'append', 'prepend' ),
                        'description' => 'How to insert the content: replace (replace all content), append (add to end), prepend (add to start)',
                        'default'     => 'append',
                    ),
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Optional: Post ID to insert the content into.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether content generation was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'content_json' => array(
                        'type'        => 'object',
                        'description' => 'Generated Bricks JSON structure',
                    ),
                    'content_info' => array(
                        'type'        => 'object',
                        'description' => 'Information about the generated content',
                    ),
                    'requires_client_update' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether this requires JavaScript execution',
                    ),
                    'client_command' => array(
                        'type'        => 'object',
                        'description' => 'Command to be executed by JavaScript',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $section_type = $input['section_type'];
                $content_description = $input['content_description'];
                $style_preference = $input['style_preference'] ?? 'modern';
                $layout_columns = $input['layout_columns'] ?? 3;
                $include_images = $input['include_images'] ?? true;
                $color_scheme = $input['color_scheme'] ?? array();
                $spacing = $input['spacing'] ?? 'normal';
                $action_type = $input['action_type'] ?? 'append';
                $post_id = $input['post_id'] ?? null;

                // Check permissions
                if ( ! current_user_can( 'edit_posts' ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit posts.', 'snn' ) );
                }

                // If post_id provided, verify it exists and user can edit it
                if ( $post_id ) {
                    $post = get_post( $post_id );
                    if ( ! $post ) {
                        return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                    }
                    if ( ! current_user_can( 'edit_post', $post_id ) ) {
                        return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                    }
                }

                // Set spacing values based on preference
                $spacing_map = array(
                    'compact'  => array( 'section' => '40', 'gap' => '16', 'element' => '8' ),
                    'normal'   => array( 'section' => '80', 'gap' => '24', 'element' => '12' ),
                    'spacious' => array( 'section' => '120', 'gap' => '40', 'element' => '20' ),
                );
                $spacing_values = $spacing_map[ $spacing ];

                // Set default colors if not provided
                $bg_color = $color_scheme['background'] ?? '#ffffff';
                $text_color = $color_scheme['text'] ?? '#000000';
                $accent_color = $color_scheme['accent'] ?? '#0066cc';
                $secondary_color = $color_scheme['secondary'] ?? '#f5f5f5';

                // Generate content based on type
                $content_json = snn_generate_bricks_content( array(
                    'type'              => $section_type,
                    'description'       => $content_description,
                    'style'             => $style_preference,
                    'columns'           => $layout_columns,
                    'include_images'    => $include_images,
                    'bg_color'          => $bg_color,
                    'text_color'        => $text_color,
                    'accent_color'      => $accent_color,
                    'secondary_color'   => $secondary_color,
                    'spacing'           => $spacing_values,
                ) );

                // Count elements
                $element_count = count( $content_json['content'] );

                // Build client command - map action_type to correct command type
                $command_type_map = array(
                    'replace' => 'bricks_replace_all',
                    'append'  => 'bricks_add_section',
                    'prepend' => 'bricks_add_section',
                );

                $client_command = array(
                    'type'             => $command_type_map[ $action_type ],
                    'content'          => $content_json,
                    'position'         => ( $action_type === 'prepend' ) ? 'prepend' : 'append',
                    'post_id'          => $post_id,
                    'save_immediately' => false,
                    'element_count'    => $element_count,
                );

                // Return instruction for JavaScript to execute
                return array(
                    'success'                   => true,
                    'message'                   => sprintf(
                        __( 'Generated %s section (%d elements). Ready to insert into Bricks Builder.', 'snn' ),
                        $section_type,
                        $element_count
                    ),
                    'content_json'              => $content_json,
                    'content_info'              => array(
                        'type'          => $section_type,
                        'style'         => $style_preference,
                        'element_count' => $element_count,
                    ),
                    'requires_client_update'    => true,
                    'client_command'            => $client_command,
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'meta' => array(
                'show_in_rest'              => true,
                'readonly'                  => false,
                'destructive'               => false,
                'idempotent'                => true,
                'requires_client_execution' => true,
            ),
        )
    );
}

/**
 * Generate Bricks content structure based on parameters
 */
function snn_generate_bricks_content( $args ) {
    $type = $args['type'];

    // Generate appropriate content based on type
    switch ( $type ) {
        case 'hero':
            return snn_generate_bricks_hero( $args );
        case 'about':
            return snn_generate_bricks_about( $args );
        case 'services':
            return snn_generate_bricks_services( $args );
        case 'cta':
            return snn_generate_bricks_cta( $args );
        case 'testimonials':
            return snn_generate_bricks_testimonials( $args );
        case 'team':
            return snn_generate_bricks_team( $args );
        case 'stats':
            return snn_generate_bricks_stats( $args );
        case 'faq':
            return snn_generate_bricks_faq( $args );
        default:
            return snn_generate_bricks_generic( $args );
    }
}

/**
 * Generate unique Bricks element ID
 */
function snn_generate_element_id() {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $id = '';
    for ( $i = 0; $i < 6; $i++ ) {
        $id .= $chars[ rand( 0, strlen( $chars ) - 1 ) ];
    }
    return $id;
}

/**
 * Generate hero section
 */
function snn_generate_bricks_hero( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];
    $include_images = $args['include_images'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $content_block_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $subheading_id = snn_generate_element_id();
    $text_id = snn_generate_element_id();
    $button_group_id = snn_generate_element_id();
    $button_id = snn_generate_element_id();
    $button2_id = snn_generate_element_id();

    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_height' => '100vh',
                '_minHeight' => '700',
                '_height:mobile_landscape' => 'auto',
                '_minHeight:mobile_landscape' => '600',
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                    'image' => array(
                        'url' => 'https://images.unsplash.com/photo-1557683316-973673baf926?w=1920',
                        'size' => 'cover',
                        'position' => 'center center',
                    ),
                ),
                '_gradient' => array(
                    'applyTo' => 'overlay',
                    'colors' => array(
                        array( 'color' => array( 'hex' => $bg_color ), 'stop' => '0', 'alpha' => '0.85' ),
                        array( 'color' => array( 'hex' => $accent_color ), 'stop' => '100', 'alpha' => '0.75' ),
                    ),
                    'angle' => '135',
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
                '_justifyContent' => 'center',
                '_alignItems' => 'center',
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $content_block_id ),
            'settings' => array(
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
            ),
        ),
        array(
            'id' => $content_block_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array( $subheading_id, $heading_id, $text_id, $button_group_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_alignItems' => 'center',
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
                '_maxWidth' => '1100px',
                '_margin' => array(
                    'left' => 'auto',
                    'right' => 'auto',
                ),
                '_animation' => array(
                    'type' => 'fadeInUp',
                    'duration' => '1000',
                ),
            ),
        ),
        array(
            'id' => $subheading_id,
            'name' => 'text-basic',
            'parent' => $content_block_id,
            'children' => array(),
            'settings' => array(
                'text' => '✨ Welcome to Excellence',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '14px',
                    'font-weight' => '600',
                    'letter-spacing' => '2px',
                    'text-transform' => 'uppercase',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $accent_color ),
                ),
                '_backgroundColor' => array( 'hex' => '#ffffff', 'alpha' => '0.1' ),
                '_padding' => array( 'top' => '8', 'right' => '20', 'bottom' => '8', 'left' => '20' ),
                '_border' => array(
                    'radius' => array( 'top' => '50', 'right' => '50', 'bottom' => '50', 'left' => '50' ),
                ),
                '_backdropFilter' => 'blur(10px)',
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $content_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Transform Your Vision Into Reality',
                'tag' => 'h1',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '80px',
                    'font-size:tablet' => '60px',
                    'font-size:mobile_landscape' => '42px',
                    'font-weight' => '800',
                    'line-height' => '1.1',
                    'letter-spacing' => '-2px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_textShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '4',
                    'blur' => '20',
                    'color' => array( 'hex' => '#000000', 'alpha' => '0.3' ),
                ),
            ),
        ),
        array(
            'id' => $text_id,
            'name' => 'text-basic',
            'parent' => $content_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Empowering businesses with cutting-edge solutions that drive growth, innovation, and sustainable success in the digital age.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '22px',
                    'font-size:mobile_landscape' => '18px',
                    'line-height' => '1.7',
                    'text-align' => 'center',
                    'color' => array( 'hex' => '#ffffff', 'alpha' => '0.95' ),
                ),
                '_maxWidth' => '800px',
            ),
        ),
        array(
            'id' => $button_group_id,
            'name' => 'block',
            'parent' => $content_block_id,
            'children' => array( $button_id, $button2_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'row',
                '_flexDirection:mobile_landscape' => 'column',
                '_columnGap' => '16',
                '_rowGap' => '16',
                '_justifyContent' => 'center',
                '_margin' => array( 'top' => '20' ),
            ),
        ),
        array(
            'id' => $button_id,
            'name' => 'button',
            'parent' => $button_group_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Get Started →',
                'link' => array( 'url' => '#' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '17px',
                    'font-weight' => '600',
                    'letter-spacing' => '0.5px',
                    'color' => array( 'hex' => '#000000' ),
                ),
                '_backgroundColor' => array( 'hex' => '#ffffff' ),
                '_padding' => array(
                    'top' => '18',
                    'right' => '36',
                    'bottom' => '18',
                    'left' => '36',
                ),
                '_border' => array(
                    'radius' => array( 'top' => '50', 'right' => '50', 'bottom' => '50', 'left' => '50' ),
                ),
                '_boxShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '8',
                    'blur' => '24',
                    'color' => array( 'hex' => $accent_color, 'alpha' => '0.4' ),
                ),
            ),
        ),
        array(
            'id' => $button2_id,
            'name' => 'button',
            'parent' => $button_group_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Learn More',
                'link' => array( 'url' => '#' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '17px',
                    'font-weight' => '600',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_backgroundColor' => array( 'hex' => '#ffffff', 'alpha' => '0' ),
                '_padding' => array(
                    'top' => '18',
                    'right' => '36',
                    'bottom' => '18',
                    'left' => '36',
                ),
                '_border' => array(
                    'width' => array( 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2' ),
                    'style' => 'solid',
                    'color' => array( 'hex' => '#ffffff', 'alpha' => '0.5' ),
                    'radius' => array( 'top' => '50', 'right' => '50', 'bottom' => '50', 'left' => '50' ),
                ),
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * Generate about section
 */
function snn_generate_bricks_about( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $include_images = $args['include_images'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $grid_id = snn_generate_element_id();
    $col1_id = snn_generate_element_id();
    $col2_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $text_id = snn_generate_element_id();
    $list_id = snn_generate_element_id();
    $image_id = snn_generate_element_id();

    $col1_children = array( $heading_id, $text_id, $list_id );
    $col2_children = $include_images ? array( $image_id ) : array();

    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $grid_id ),
            'settings' => array(),
        ),
        array(
            'id' => $grid_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array( $col1_id, $col2_id ),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => '1fr 1fr',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => $spacing['gap'],
                '_alignItems' => 'center',
            ),
        ),
        array(
            'id' => $col1_id,
            'name' => 'block',
            'parent' => $grid_id,
            'children' => $col1_children,
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => $spacing['element'],
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $col1_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Building Excellence, One Project at a Time',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '52px',
                    'font-size:mobile_landscape' => '36px',
                    'font-weight' => '800',
                    'line-height' => '1.2',
                    'letter-spacing' => '-1px',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
        array(
            'id' => $text_id,
            'name' => 'text-basic',
            'parent' => $col1_id,
            'children' => array(),
            'settings' => array(
                'text' => 'We are a forward-thinking team dedicated to crafting exceptional digital experiences. With a passion for innovation and a commitment to excellence, we transform ideas into reality through strategic design and cutting-edge technology.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '18px',
                    'line-height' => '1.8',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.85' ),
                ),
                '_margin' => array( 'top' => '20', 'bottom' => '20' ),
            ),
        ),
        array(
            'id' => $list_id,
            'name' => 'list',
            'parent' => $col1_id,
            'children' => array(),
            'settings' => array(
                'items' => array(
                    array( 'text' => '✓ Award-winning quality standards' ),
                    array( 'text' => '✓ 50+ industry-certified experts' ),
                    array( 'text' => '✓ 99.8% client satisfaction rate' ),
                    array( 'text' => '✓ 24/7 dedicated support team' ),
                ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '17px',
                    'line-height' => '2',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.9' ),
                ),
            ),
        ),
        array(
            'id' => $col2_id,
            'name' => 'block',
            'parent' => $grid_id,
            'children' => $col2_children,
            'settings' => array(),
        ),
    );

    if ( $include_images ) {
        $content[] = array(
            'id' => $image_id,
            'name' => 'image',
            'parent' => $col2_id,
            'children' => array(),
            'settings' => array(
                'image' => array(
                    'url' => 'https://placehold.co/600x400',
                    'id' => 0,
                    'size' => 'full',
                ),
                '_objectFit' => 'cover',
                '_width' => '100%',
                '_border' => array(
                    'radius' => array(
                        'top' => '12',
                        'right' => '12',
                        'bottom' => '12',
                        'left' => '12',
                    ),
                ),
            ),
        );
    }

    return array( 'content' => $content );
}

/**
 * Generate services section
 */
function snn_generate_bricks_services( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $columns = $args['columns'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $grid_id = snn_generate_element_id();

    $services = array(
        array( 'title' => '🚀 Digital Transformation', 'desc' => 'Revolutionize your business with cutting-edge digital solutions that drive growth and innovation in the modern marketplace.' ),
        array( 'title' => '✨ Creative Design', 'desc' => 'Stunning visual experiences that captivate your audience and elevate your brand to new heights of excellence.' ),
        array( 'title' => '⚡ Performance Optimization', 'desc' => 'Lightning-fast, scalable solutions engineered for peak performance and exceptional user experiences.' ),
        array( 'title' => '🛡️ Security & Compliance', 'desc' => 'Enterprise-grade security measures that protect your data and ensure complete regulatory compliance.' ),
    );

    $grid_children = array();
    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $heading_id, $grid_id ),
            'settings' => array(
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Premium Services Tailored for You',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '54px',
                    'font-size:mobile_landscape' => '38px',
                    'font-weight' => '800',
                    'line-height' => '1.15',
                    'letter-spacing' => '-1px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_margin' => array(
                    'bottom' => '60',
                ),
            ),
        ),
        array(
            'id' => $grid_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => 'repeat(' . $columns . ', 1fr)',
                '_gridTemplateColumns:tablet' => $columns > 2 ? 'repeat(2, 1fr)' : '1fr',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => $spacing['gap'],
            ),
        ),
    );

    foreach ( array_slice( $services, 0, $columns ) as $service ) {
        $card_id = snn_generate_element_id();
        $card_heading_id = snn_generate_element_id();
        $card_text_id = snn_generate_element_id();

        $grid_children[] = $card_id;

        $content[] = array(
            'id' => $card_id,
            'name' => 'block',
            'parent' => $grid_id,
            'children' => array( $card_heading_id, $card_text_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '20',
                '_padding' => array(
                    'top' => '40',
                    'right' => '32',
                    'bottom' => '40',
                    'left' => '32',
                ),
                '_backgroundColor' => array( 'hex' => '#ffffff' ),
                '_border' => array(
                    'width' => array( 'top' => '0', 'right' => '0', 'bottom' => '4', 'left' => '0' ),
                    'style' => 'solid',
                    'color' => array( 'hex' => $accent_color ),
                    'radius' => array( 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16' ),
                ),
                '_boxShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '8',
                    'blur' => '30',
                    'spread' => '0',
                    'color' => array( 'hex' => '#000000', 'alpha' => '0.08' ),
                ),
                '_transition' => array(
                    'property' => 'all',
                    'duration' => '300',
                ),
                '_cursor' => 'pointer',
            ),
        );

        $content[] = array(
            'id' => $card_heading_id,
            'name' => 'heading',
            'parent' => $card_id,
            'children' => array(),
            'settings' => array(
                'text' => $service['title'],
                'tag' => 'h3',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '26px',
                    'font-weight' => '700',
                    'line-height' => '1.3',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        );

        $content[] = array(
            'id' => $card_text_id,
            'name' => 'text-basic',
            'parent' => $card_id,
            'children' => array(),
            'settings' => array(
                'text' => $service['desc'],
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '16px',
                    'line-height' => '1.7',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.8' ),
                ),
            ),
        );
    }

    // Update grid children
    foreach ( $content as &$element ) {
        if ( $element['id'] === $grid_id ) {
            $element['children'] = $grid_children;
            break;
        }
    }

    return array( 'content' => $content );
}

/**
 * Generate CTA section
 */
function snn_generate_bricks_cta( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $content_block_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $text_id = snn_generate_element_id();
    $button_id = snn_generate_element_id();

    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $content_block_id ),
            'settings' => array(),
        ),
        array(
            'id' => $content_block_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array( $heading_id, $text_id, $button_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_alignItems' => 'center',
                '_rowGap' => $spacing['gap'],
                '_maxWidth' => '800px',
                '_margin' => array(
                    'left' => 'auto',
                    'right' => 'auto',
                ),
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $content_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Ready to Transform Your Business?',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '64px',
                    'font-size:tablet' => '48px',
                    'font-size:mobile_landscape' => '38px',
                    'font-weight' => '800',
                    'line-height' => '1.15',
                    'letter-spacing' => '-1px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_textShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '2',
                    'blur' => '15',
                    'color' => array( 'hex' => '#000000', 'alpha' => '0.3' ),
                ),
            ),
        ),
        array(
            'id' => $text_id,
            'name' => 'text-basic',
            'parent' => $content_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Join 10,000+ companies already scaling with our cutting-edge platform. Start your journey to success today.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '21px',
                    'font-size:mobile_landscape' => '18px',
                    'line-height' => '1.7',
                    'text-align' => 'center',
                    'color' => array( 'hex' => '#ffffff', 'alpha' => '0.95' ),
                ),
            ),
        ),
        array(
            'id' => $button_id,
            'name' => 'button',
            'parent' => $content_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Start Free Trial →',
                'link' => array( 'url' => '#' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '18px',
                    'font-weight' => '700',
                    'letter-spacing' => '0.5px',
                    'color' => array( 'hex' => $accent_color ),
                ),
                '_backgroundColor' => array( 'hex' => '#ffffff' ),
                '_padding' => array(
                    'top' => '20',
                    'right' => '48',
                    'bottom' => '20',
                    'left' => '48',
                ),
                '_border' => array(
                    'radius' => array( 'top' => '50', 'right' => '50', 'bottom' => '50', 'left' => '50' ),
                ),
                '_boxShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '12',
                    'blur' => '40',
                    'spread' => '0',
                    'color' => array( 'hex' => '#000000', 'alpha' => '0.4' ),
                ),
                '_margin' => array( 'top' => '12' ),
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * Generate stats section
 */
function snn_generate_bricks_stats( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $grid_id = snn_generate_element_id();

    $stats = array(
        array( 'number' => '2.5K+', 'label' => 'Projects Delivered Successfully' ),
        array( 'number' => '120+', 'label' => 'Expert Team Members' ),
        array( 'number' => '99.8%', 'label' => 'Client Satisfaction Score' ),
    );

    $grid_children = array();
    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $grid_id ),
            'settings' => array(),
        ),
        array(
            'id' => $grid_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => 'repeat(3, 1fr)',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => $spacing['gap'],
            ),
        ),
    );

    foreach ( $stats as $stat ) {
        $stat_block_id = snn_generate_element_id();
        $stat_number_id = snn_generate_element_id();
        $stat_label_id = snn_generate_element_id();

        $grid_children[] = $stat_block_id;

        $content[] = array(
            'id' => $stat_block_id,
            'name' => 'block',
            'parent' => $grid_id,
            'children' => array( $stat_number_id, $stat_label_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_alignItems' => 'center',
                '_rowGap' => $spacing['element'],
            ),
        );

        $content[] = array(
            'id' => $stat_number_id,
            'name' => 'heading',
            'parent' => $stat_block_id,
            'children' => array(),
            'settings' => array(
                'text' => $stat['number'],
                'tag' => 'div',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '80px',
                    'font-size:mobile_landscape' => '56px',
                    'font-weight' => '900',
                    'line-height' => '1',
                    'letter-spacing' => '-2px',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_textShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '2',
                    'blur' => '10',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.1' ),
                ),
            ),
        );

        $content[] = array(
            'id' => $stat_label_id,
            'name' => 'text-basic',
            'parent' => $stat_block_id,
            'children' => array(),
            'settings' => array(
                'text' => $stat['label'],
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '17px',
                    'font-weight' => '500',
                    'text-align' => 'center',
                    'line-height' => '1.4',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.7' ),
                ),
            ),
        );
    }

    // Update grid children
    foreach ( $content as &$element ) {
        if ( $element['id'] === $grid_id ) {
            $element['children'] = $grid_children;
            break;
        }
    }

    return array( 'content' => $content );
}

/**
 * Generate testimonials section
 */
function snn_generate_bricks_testimonials( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $secondary_color = $args['secondary_color'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $grid_id = snn_generate_element_id();

    $testimonials = array(
        array(
            'quote' => 'Working with this team has been an absolute game-changer for our business. Their innovative approach and attention to detail exceeded all expectations. The results speak for themselves - 300% growth in just 6 months!',
            'author' => 'Sarah Johnson, CEO at TechVision Inc.',
        ),
        array(
            'quote' => 'Exceptional quality and professionalism from start to finish. They didn\'t just meet our requirements - they anticipated our needs and delivered solutions we didn\'t even know were possible. Truly outstanding partnership.',
            'author' => 'Michael Chen, Director of Innovation at Global Solutions',
        ),
    );

    $grid_children = array();
    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $heading_id, $grid_id ),
            'settings' => array(
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Loved by Clients Worldwide',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '54px',
                    'font-size:mobile_landscape' => '38px',
                    'font-weight' => '800',
                    'line-height' => '1.15',
                    'letter-spacing' => '-1px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_margin' => array(
                    'bottom' => '60',
                ),
            ),
        ),
        array(
            'id' => $grid_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => 'repeat(2, 1fr)',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => $spacing['gap'],
            ),
        ),
    );

    foreach ( $testimonials as $testimonial ) {
        $card_id = snn_generate_element_id();
        $quote_id = snn_generate_element_id();
        $author_id = snn_generate_element_id();

        $grid_children[] = $card_id;

        $content[] = array(
            'id' => $card_id,
            'name' => 'block',
            'parent' => $grid_id,
            'children' => array( $quote_id, $author_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '24',
                '_padding' => array(
                    'top' => '48',
                    'right' => '40',
                    'bottom' => '48',
                    'left' => '40',
                ),
                '_backgroundColor' => array( 'hex' => '#ffffff' ),
                '_border' => array(
                    'width' => array( 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1' ),
                    'style' => 'solid',
                    'color' => array( 'hex' => '#e5e7eb' ),
                    'radius' => array( 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20' ),
                ),
                '_boxShadow' => array(
                    'horizontal' => '0',
                    'vertical' => '10',
                    'blur' => '40',
                    'spread' => '-5',
                    'color' => array( 'hex' => '#000000', 'alpha' => '0.1' ),
                ),
                '_transition' => array(
                    'property' => 'all',
                    'duration' => '300',
                ),
                '_position' => 'relative',
            ),
        );

        $content[] = array(
            'id' => $quote_id,
            'name' => 'text-basic',
            'parent' => $card_id,
            'children' => array(),
            'settings' => array(
                'text' => '"' . $testimonial['quote'] . '"',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '18px',
                    'line-height' => '1.75',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.9' ),
                ),
            ),
        );

        $content[] = array(
            'id' => $author_id,
            'name' => 'text-basic',
            'parent' => $card_id,
            'children' => array(),
            'settings' => array(
                'text' => '— ' . $testimonial['author'],
                '_typography' => array(
                    'font-size' => '14px',
                    'font-weight' => '600',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        );
    }

    // Update grid children
    foreach ( $content as &$element ) {
        if ( $element['id'] === $grid_id ) {
            $element['children'] = $grid_children;
            break;
        }
    }

    return array( 'content' => $content );
}

/**
 * Generate team section
 */
function snn_generate_bricks_team( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $columns = $args['columns'];
    $include_images = $args['include_images'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $grid_id = snn_generate_element_id();

    $grid_children = array();
    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $heading_id, $grid_id ),
            'settings' => array(
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Meet the Visionaries',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '54px',
                    'font-size:mobile_landscape' => '38px',
                    'font-weight' => '800',
                    'line-height' => '1.15',
                    'letter-spacing' => '-1px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_margin' => array(
                    'bottom' => '60',
                ),
            ),
        ),
        array(
            'id' => $grid_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => 'repeat(' . $columns . ', 1fr)',
                '_gridTemplateColumns:tablet' => $columns > 2 ? 'repeat(2, 1fr)' : '1fr',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => $spacing['gap'],
            ),
        ),
    );

    for ( $i = 1; $i <= $columns; $i++ ) {
        $member_block_id = snn_generate_element_id();
        $member_image_id = snn_generate_element_id();
        $member_name_id = snn_generate_element_id();
        $member_title_id = snn_generate_element_id();

        $member_children = array();
        if ( $include_images ) {
            $member_children[] = $member_image_id;
        }
        $member_children[] = $member_name_id;
        $member_children[] = $member_title_id;

        $grid_children[] = $member_block_id;

        $content[] = array(
            'id' => $member_block_id,
            'name' => 'block',
            'parent' => $grid_id,
            'children' => $member_children,
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_alignItems' => 'center',
                '_rowGap' => $spacing['element'],
            ),
        );

        if ( $include_images ) {
            $content[] = array(
                'id' => $member_image_id,
                'name' => 'image',
                'parent' => $member_block_id,
                'children' => array(),
                'settings' => array(
                    'image' => array(
                        'url' => 'https://placehold.co/300x300',
                        'id' => 0,
                        'size' => 'full',
                    ),
                    '_objectFit' => 'cover',
                    '_width' => '200px',
                    '_height' => '200px',
                    '_border' => array(
                        'radius' => array( 'top' => '100', 'right' => '100', 'bottom' => '100', 'left' => '100' ),
                    ),
                ),
            );
        }

        $content[] = array(
            'id' => $member_name_id,
            'name' => 'heading',
            'parent' => $member_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Team Member ' . $i,
                'tag' => 'h3',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '26px',
                    'font-weight' => '700',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        );

        $content[] = array(
            'id' => $member_title_id,
            'name' => 'text-basic',
            'parent' => $member_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Position Title',
                '_typography' => array(
                    'font-size' => '14px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        );
    }

    // Update grid children
    foreach ( $content as &$element ) {
        if ( $element['id'] === $grid_id ) {
            $element['children'] = $grid_children;
            break;
        }
    }

    return array( 'content' => $content );
}

/**
 * Generate FAQ section
 */
function snn_generate_bricks_faq( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $accordion_id = snn_generate_element_id();

    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $heading_id, $accordion_id ),
            'settings' => array(
                '_maxWidth' => '900px',
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Got Questions? We\'ve Got Answers',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '54px',
                    'font-size:mobile_landscape' => '38px',
                    'font-weight' => '800',
                    'line-height' => '1.15',
                    'letter-spacing' => '-1px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_margin' => array(
                    'bottom' => '60',
                ),
            ),
        ),
        array(
            'id' => $accordion_id,
            'name' => 'accordion',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'items' => array(
                    array(
                        'title' => 'What makes your services unique?',
                        'content' => 'We combine cutting-edge technology with deep industry expertise to deliver solutions that are not just functional, but transformative. Our holistic approach ensures every project aligns perfectly with your business goals and drives measurable results.',
                    ),
                    array(
                        'title' => 'How quickly can we get started?',
                        'content' => 'Most projects begin within 1-2 weeks of initial consultation. We pride ourselves on rapid deployment without compromising quality. Our streamlined onboarding process gets you up and running quickly while ensuring every detail is perfect.',
                    ),
                    array(
                        'title' => 'What kind of support do you provide?',
                        'content' => 'We offer comprehensive 24/7 support with dedicated account managers, priority response times, and proactive monitoring. Our team doesn\'t just fix issues - we anticipate them and provide ongoing optimization to ensure continued success.',
                    ),
                    array(
                        'title' => 'Can you scale with our growing needs?',
                        'content' => 'Absolutely. Our solutions are built with scalability at their core. Whether you\'re doubling in size or expanding globally, our infrastructure and team grow with you seamlessly, ensuring consistent performance at any scale.',
                    ),
                ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * Generate generic section
 */
function snn_generate_bricks_generic( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $heading_id = snn_generate_element_id();
    $text_id = snn_generate_element_id();

    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_background' => array(
                    'color' => array( 'hex' => $bg_color ),
                ),
                '_padding' => array(
                    'top' => $spacing['section'],
                    'bottom' => $spacing['section'],
                ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $heading_id, $text_id ),
            'settings' => array(
                '_columnGap' => $spacing['gap'],
                '_rowGap' => $spacing['gap'],
            ),
        ),
        array(
            'id' => $heading_id,
            'name' => 'heading',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Section Heading',
                'tag' => 'h2',
                '_typography' => array(
                    'font-size' => '48px',
                    'font-weight' => '700',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
        array(
            'id' => $text_id,
            'name' => 'text-basic',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Your content goes here. This is a flexible section that can be customized to fit your needs.',
                '_typography' => array(
                    'font-size' => '16px',
                    'line-height' => '1.6',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
    );

    return array( 'content' => $content );
}
