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
- hero: High-end hero banners with creative variations. Styles: "bold" (Split Design), "modern" (Centered Minimal), "creative" (Grid Showcase), "elegant" (Offset Layout).
- about: About sections with text + imagery (2-column, lists, professional) [CONTAINED]
- services: Service grids (1-4 columns, titles, descriptions, hover effects) [CONTAINED]
- cta: Call-to-action sections (centered, prominent buttons, conversion-focused) [FULL-WIDTH]
- testimonials: Customer reviews (quotes in columns, attribution) [CONTAINED]
- team: Team member grids (images, names, positions, configurable columns) [CONTAINED]
- stats: Statistics showcase (large numbers, labels, multi-column) [CONTAINED]
- faq: FAQ sections (accordion/expandable Q&A) [CONTAINED]
- sticky-content: Sticky sidebar with scrolling content (product showcases, feature lists) [SPLIT]
- custom: Generic flexible section with custom HTML/CSS/JS support [CONTAINED]

STYLE OPTIONS (style_preference):
modern, minimal, bold, elegant, playful, professional, creative

INTERACTIVE FEATURES:
- Hover states: Use _background:hover, _typography:hover, _cssTransition for smooth transitions
- JavaScript: Agent can generate custom-html-css-script elements for interactive features when needed
- Animations: _animation property supports fadeIn, fadeInUp, slideIn, etc.
- Links: Button links use {"url":"#","type":"external"} format

ADVANCED LAYOUT PROPERTIES:
GRID:
- _justifyItemsGrid, _alignItemsGrid: Align items within grid cells
- _justifyContentGrid, _alignContentGrid: Align entire grid
- _gridTemplateColumns:mobile_portrait: Additional mobile breakpoint
- _order: Visual reordering of elements

FLEX:
- _flexWrap: "nowrap" | "wrap" | "wrap-reverse"
- _alignSelf: Override alignment for individual items
- _flexGrow, _flexShrink: Flexible sizing

POSITIONING:
- _position: "sticky" with _top for sticky elements
- _top, _right, _bottom, _left: Positioning offsets
- _overflow: "hidden" | "visible" | "scroll" | "auto"
- _visibility: "visible" | "hidden"

SIZING:
- _widthMin, _widthMax, _heightMin, _heightMax: Size constraints
- _aspectRatio: Maintain aspect ratio (e.g., "16/9", "1/1")

COLOR SCHEME (Default: Monochrome):
By default, ALL sections use a sophisticated monochrome palette (black, white, and grays) with optimal contrast.
- Default colors: background "#ffffff" (white), text "#000000" (black), accent "#1a1a1a" (very dark gray), secondary "#f5f5f5" (light gray)
- Monochrome ensures professional appearance and WCAG AA contrast compliance
- Users can override defaults by providing color_scheme parameter with custom hex colors
- Only use custom colors when user explicitly specifies them

KEY PARAMETERS:
- content_description: BE SPECIFIC about headings, text, items.
- layout_columns: 1-4 columns for grids.
- color_scheme: Optional. Defaults to monochrome. Example: {background: "#ffffff", text: "#000000", accent: "#ff6600", secondary: "#f0f0f0"}
- spacing: compact, normal, spacious
- action_type: replace, append, prepend

USAGE EXAMPLES:
1. Hero (monochrome): {section_type: "hero", content_description: "Heading \'Future of Tech\', subtext about AI", style_preference: "bold"} -> Generates Industrial Dark with grayscale
2. Services (custom colors): {section_type: "services", content_description: "3 service cards", color_scheme: {accent: "#0066cc"}} -> Uses blue accent', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'section_type', 'content_description' ),
                'properties' => array(
                    'section_type' => array(
                        'type'        => 'string',
                        'enum'        => array( 'hero', 'about', 'services', 'cta', 'testimonials', 'team', 'stats', 'faq', 'sticky-content', 'custom' ),
                        'description' => 'Type of section to generate.',
                    ),
                    'content_description' => array(
                        'type'        => 'string',
                        'description' => 'Detailed description of content.',
                        'minLength'   => 10,
                    ),
                    'style_preference' => array(
                        'type'        => 'string',
                        'enum'        => array( 'modern', 'minimal', 'bold', 'elegant', 'playful', 'professional', 'creative' ),
                        'description' => 'Style preference. "Bold/Professional" triggers Industrial layout. Others trigger Layered layout.',
                        'default'     => 'modern',
                    ),
                    'layout_columns' => array(
                        'type'        => 'integer',
                        'description' => 'Number of columns (1-4). Default: 3',
                        'minimum'     => 1,
                        'maximum'     => 4,
                        'default'     => 3,
                    ),
                    'include_images' => array(
                        'type'        => 'boolean',
                        'description' => 'Whether to include image elements.',
                        'default'     => true,
                    ),
                    'color_scheme' => array(
                        'type'        => 'object',
                        'description' => 'Optional color scheme. Defaults to monochrome (black/white/grays). Only provide if user explicitly specifies custom colors.',
                        'properties'  => array(
                            'background' => array( 'type' => 'string', 'description' => 'Background color (hex). Default: #ffffff' ),
                            'text'       => array( 'type' => 'string', 'description' => 'Text color (hex). Default: #000000' ),
                            'accent'     => array( 'type' => 'string', 'description' => 'Accent color (hex). Default: #1a1a1a' ),
                            'secondary'  => array( 'type' => 'string', 'description' => 'Secondary color (hex). Default: #f5f5f5' ),
                        ),
                    ),
                    'spacing' => array(
                        'type'        => 'string',
                        'enum'        => array( 'compact', 'normal', 'spacious' ),
                        'default'     => 'normal',
                    ),
                    'action_type' => array(
                        'type'        => 'string',
                        'enum'        => array( 'replace', 'append', 'prepend' ),
                        'default'     => 'append',
                    ),
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Optional Post ID.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'message' => array( 'type' => 'string' ),
                    'content_json' => array( 'type' => 'object' ),
                    'content_info' => array( 'type' => 'object' ),
                    'requires_client_update' => array( 'type' => 'boolean' ),
                    'client_command' => array( 'type' => 'object' ),
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

                if ( ! current_user_can( 'edit_posts' ) ) {
                    return new WP_Error( 'permission_denied', __( 'You do not have permission to edit posts.', 'snn' ) );
                }

                if ( $post_id ) {
                    $post = get_post( $post_id );
                    if ( ! $post ) {
                        return new WP_Error( 'invalid_post', __( 'Post not found.', 'snn' ) );
                    }
                    if ( ! current_user_can( 'edit_post', $post_id ) ) {
                        return new WP_Error( 'permission_denied', __( 'You do not have permission to edit this post.', 'snn' ) );
                    }
                }

                $spacing_map = array(
                    'compact'  => array( 'section' => '40', 'gap' => '16', 'element' => '8' ),
                    'normal'   => array( 'section' => '80', 'gap' => '24', 'element' => '12' ),
                    'spacious' => array( 'section' => '120', 'gap' => '40', 'element' => '20' ),
                );
                $spacing_values = $spacing_map[ $spacing ];

                $bg_color = $color_scheme['background'] ?? '#ffffff';
                $text_color = $color_scheme['text'] ?? '#000000';
                $accent_color = $color_scheme['accent'] ?? '#1a1a1a';
                $secondary_color = $color_scheme['secondary'] ?? '#f5f5f5';

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

                $element_count = count( $content_json['content'] );

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

                return array(
                    'success'                   => true,
                    'message'                   => sprintf( __( 'Generated %s section (%d elements). Ready to insert.', 'snn' ), $section_type, $element_count ),
                    'content_json'              => $content_json,
                    'content_info'              => array( 'type' => $section_type, 'style' => $style_preference, 'element_count' => $element_count ),
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
        case 'sticky-content':
            return snn_generate_bricks_sticky_content( $args );
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
 * Calculate relative luminance of a color (WCAG formula)
 * Used to determine if text will be readable on a background
 */
function snn_get_luminance( $hex ) {
    $hex = ltrim( $hex, '#' );
    $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
    $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
    $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

    $r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
    $g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
    $b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Check if text color has sufficient contrast against background
 * Returns true if contrast ratio >= 4.5:1 (WCAG AA standard)
 */
function snn_has_good_contrast( $text_color, $bg_color ) {
    $l1 = snn_get_luminance( $text_color );
    $l2 = snn_get_luminance( $bg_color );

    $lighter = max( $l1, $l2 );
    $darker = min( $l1, $l2 );

    $contrast = ( $lighter + 0.05 ) / ( $darker + 0.05 );

    return $contrast >= 4.5;
}

/**
 * Get contrasting text color (black or white) for a given background
 */
function snn_get_contrast_text_color( $bg_color ) {
    $luminance = snn_get_luminance( $bg_color );
    // If background is dark (luminance < 0.5), use white text. Otherwise use black.
    return $luminance < 0.5 ? '#ffffff' : '#000000';
}

/**
 * ==============================================================================
 * HERO GENERATION - Multiple Creative Variations
 * ==============================================================================
 */

/**
 * Main Hero Switcher - Routes to different creative hero layouts
 */
function snn_generate_bricks_hero( $args ) {
    $style = $args['style'] ?? 'modern';

    // Switch between creative hero styles
    switch ( $style ) {
        case 'bold':
        case 'professional':
            return snn_hero_split_design( $args );
        case 'creative':
            return snn_hero_grid_showcase( $args );
        case 'elegant':
        case 'minimal':
            return snn_hero_offset_layout( $args );
        default:
            return snn_hero_centered_minimal( $args );
    }
}

/**
 * HERO STYLE 1: "Split Design"
 * Features: Half image, half content, bold typography, clean split layout
 */
function snn_hero_split_design( $args ) {
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];

    $ids = array(
        'section' => snn_generate_element_id(),
        'container' => snn_generate_element_id(),
        'grid' => snn_generate_element_id(),
        'left_col' => snn_generate_element_id(),
        'heading' => snn_generate_element_id(),
        'text' => snn_generate_element_id(),
        'button' => snn_generate_element_id(),
        'right_col' => snn_generate_element_id(),
        'image' => snn_generate_element_id(),
    );

    $content = array(
        array(
            'id' => $ids['section'],
            'name' => 'section',
            'parent' => 0,
            'children' => array( $ids['container'] ),
            'settings' => array(
                '_minHeight' => '100vh',
                '_padding' => array( 'top' => '0', 'bottom' => '0' ),
                '_background' => array( 'color' => array( 'hex' => $bg_color ) ),
            ),
        ),
        array(
            'id' => $ids['container'],
            'name' => 'container',
            'parent' => $ids['section'],
            'children' => array( $ids['grid'] ),
            'settings' => array(
                '_width' => '100%',
                '_maxWidth' => '100%',
                '_padding' => array( 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0' ),
            ),
        ),
        array(
            'id' => $ids['grid'],
            'name' => 'block',
            'parent' => $ids['container'],
            'children' => array( $ids['left_col'], $ids['right_col'] ),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => '1fr 1fr',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_minHeight' => '100vh',
            ),
        ),
        array(
            'id' => $ids['left_col'],
            'name' => 'block',
            'parent' => $ids['grid'],
            'children' => array( $ids['heading'], $ids['text'], $ids['button'] ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_justifyContent' => 'center',
                '_padding' => array( 'top' => 80, 'right' => 80, 'bottom' => 80, 'left' => 80 ),
                '_padding:mobile_landscape' => array( 'top' => 60, 'right' => 30, 'bottom' => 60, 'left' => 30 ),
                '_rowGap' => '30',
            ),
        ),
        array(
            'id' => $ids['heading'],
            'name' => 'heading',
            'parent' => $ids['left_col'],
            'children' => array(),
            'settings' => array(
                'text' => 'Build Amazing Digital Experiences',
                'tag' => 'h1',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '72px',
                    'font-size:tablet' => '56px',
                    'font-size:mobile_landscape' => '42px',
                    'font-weight' => '900',
                    'line-height' => '1.1',
                    'letter-spacing' => '-2px',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'duration' => 800 ),
            ),
        ),
        array(
            'id' => $ids['text'],
            'name' => 'text-basic',
            'parent' => $ids['left_col'],
            'children' => array(),
            'settings' => array(
                'text' => 'Transform your vision into reality with cutting-edge design and development solutions that drive results.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '20px',
                    'line-height' => '1.7',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.8' ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 200 ),
            ),
        ),
        array(
            'id' => $ids['button'],
            'name' => 'button',
            'parent' => $ids['left_col'],
            'children' => array(),
            'settings' => array(
                'text' => 'Get Started',
                'link' => array( 'url' => '#', 'type' => 'external' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-weight' => '700',
                    'font-size' => '18px',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_typography:hover' => array(
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_background' => array( 'color' => array( 'hex' => $accent_color ) ),
                '_background:hover' => array( 'color' => array( 'hex' => $text_color ) ),
                '_padding' => array( 'top' => 18, 'right' => 40, 'bottom' => 18, 'left' => 40 ),
                '_border' => array( 'radius' => array( 'top' => 6, 'right' => 6, 'bottom' => 6, 'left' => 6 ) ),
                '_cssTransition' => '0.3s',
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 400 ),
            ),
        ),
        array(
            'id' => $ids['right_col'],
            'name' => 'block',
            'parent' => $ids['grid'],
            'children' => array( $ids['image'] ),
            'settings' => array(
                '_background' => array( 'color' => array( 'hex' => $accent_color, 'alpha' => '0.05' ) ),
                '_display' => 'flex',
                '_alignItems' => 'center',
                '_justifyContent' => 'center',
            ),
        ),
        array(
            'id' => $ids['image'],
            'name' => 'image',
            'parent' => $ids['right_col'],
            'children' => array(),
            'settings' => array(
                'image' => array(
                    'url' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=1200',
                    'size' => 'full',
                ),
                '_objectFit' => 'cover',
                '_width' => '100%',
                '_height' => '100%',
                '_animation' => array( 'type' => 'fadeIn', 'duration' => 1200 ),
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * HERO STYLE 2: "Centered Minimal"
 * Features: Clean centered text, simple CTA, modern and minimal
 */
function snn_hero_centered_minimal( $args ) {
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];

    // Ensure subtext has good contrast with background
    $subtext_color = snn_has_good_contrast( $accent_color, $bg_color ) ? $accent_color : $text_color;

    $ids = array(
        'section' => snn_generate_element_id(),
        'container' => snn_generate_element_id(),
        'content_block' => snn_generate_element_id(),
        'subtext' => snn_generate_element_id(),
        'heading' => snn_generate_element_id(),
        'description' => snn_generate_element_id(),
        'button' => snn_generate_element_id(),
    );

    $content = array(
        array(
            'id' => $ids['section'],
            'name' => 'section',
            'parent' => 0,
            'children' => array( $ids['container'] ),
            'settings' => array(
                '_minHeight' => '90vh',
                '_background' => array( 'color' => array( 'hex' => $bg_color ) ),
                '_padding' => array( 'top' => '80', 'bottom' => '80' ),
            ),
        ),
        array(
            'id' => $ids['container'],
            'name' => 'container',
            'parent' => $ids['section'],
            'children' => array( $ids['content_block'] ),
            'settings' => array(),
        ),
        array(
            'id' => $ids['content_block'],
            'name' => 'block',
            'parent' => $ids['container'],
            'children' => array( $ids['subtext'], $ids['heading'], $ids['description'], $ids['button'] ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_alignItems' => 'center',
                '_justifyContent' => 'center',
                '_rowGap' => '24',
                '_maxWidth' => '900px',
                '_margin' => array( 'left' => 'auto', 'right' => 'auto' ),
                '_minHeight' => '70vh',
            ),
        ),
        array(
            'id' => $ids['subtext'],
            'name' => 'text-basic',
            'parent' => $ids['content_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'WELCOME TO THE FUTURE',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '14px',
                    'font-weight' => '700',
                    'letter-spacing' => '3px',
                    'text-transform' => 'uppercase',
                    'color' => array( 'hex' => $subtext_color ),
                ),
                '_animation' => array( 'type' => 'fadeIn', 'delay' => 100 ),
            ),
        ),
        array(
            'id' => $ids['heading'],
            'name' => 'heading',
            'parent' => $ids['content_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'Innovation Meets Excellence',
                'tag' => 'h1',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '86px',
                    'font-size:tablet' => '64px',
                    'font-size:mobile_landscape' => '44px',
                    'font-weight' => '800',
                    'line-height' => '1.1',
                    'letter-spacing' => '-3px',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 200 ),
            ),
        ),
        array(
            'id' => $ids['description'],
            'name' => 'text-basic',
            'parent' => $ids['content_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'We create extraordinary digital experiences that push boundaries and inspire change. Join thousands of innovators already transforming their industry.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '20px',
                    'line-height' => '1.7',
                    'text-align' => 'center',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.75' ),
                ),
                '_maxWidth' => '700px',
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 400 ),
            ),
        ),
        array(
            'id' => $ids['button'],
            'name' => 'button',
            'parent' => $ids['content_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'Start Your Journey',
                'link' => array( 'url' => '#', 'type' => 'external' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-weight' => '700',
                    'font-size' => '18px',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_typography:hover' => array(
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_background' => array( 'color' => array( 'hex' => $accent_color ) ),
                '_background:hover' => array( 'color' => array( 'hex' => $text_color ) ),
                '_padding' => array( 'top' => 20, 'right' => 48, 'bottom' => 20, 'left' => 48 ),
                '_border' => array( 'radius' => array( 'top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50 ) ),
                '_cssTransition' => '0.3s',
                '_margin' => array( 'top' => '16' ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 600 ),
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * HERO STYLE 3: "Grid Showcase"
 * Features: Grid layout with multiple content blocks, dynamic and creative
 */
function snn_hero_grid_showcase( $args ) {
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];

    $ids = array(
        'section' => snn_generate_element_id(),
        'container' => snn_generate_element_id(),
        'grid' => snn_generate_element_id(),
        'heading_block' => snn_generate_element_id(),
        'heading' => snn_generate_element_id(),
        'feature1' => snn_generate_element_id(),
        'feature1_icon' => snn_generate_element_id(),
        'feature1_text' => snn_generate_element_id(),
        'feature2' => snn_generate_element_id(),
        'feature2_icon' => snn_generate_element_id(),
        'feature2_text' => snn_generate_element_id(),
        'cta_block' => snn_generate_element_id(),
        'cta_text' => snn_generate_element_id(),
        'cta_button' => snn_generate_element_id(),
    );

    $content = array(
        array(
            'id' => $ids['section'],
            'name' => 'section',
            'parent' => 0,
            'children' => array( $ids['container'] ),
            'settings' => array(
                '_minHeight' => '100vh',
                '_background' => array( 'color' => array( 'hex' => $bg_color ) ),
                '_padding' => array( 'top' => '80', 'bottom' => '80' ),
            ),
        ),
        array(
            'id' => $ids['container'],
            'name' => 'container',
            'parent' => $ids['section'],
            'children' => array( $ids['grid'] ),
            'settings' => array(),
        ),
        array(
            'id' => $ids['grid'],
            'name' => 'block',
            'parent' => $ids['container'],
            'children' => array( $ids['heading_block'], $ids['feature1'], $ids['feature2'], $ids['cta_block'] ),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => 'repeat(2, 1fr)',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => '32',
                '_minHeight' => '80vh',
            ),
        ),
        array(
            'id' => $ids['heading_block'],
            'name' => 'block',
            'parent' => $ids['grid'],
            'children' => array( $ids['heading'] ),
            'settings' => array(
                '_display' => 'flex',
                '_alignItems' => 'center',
                '_padding' => array( 'top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40 ),
                '_background' => array( 'color' => array( 'hex' => $accent_color ) ),
                '_border' => array( 'radius' => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ) ),
                '_animation' => array( 'type' => 'fadeIn', 'duration' => 800 ),
            ),
        ),
        array(
            'id' => $ids['heading'],
            'name' => 'heading',
            'parent' => $ids['heading_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'Creative Solutions<br>That Work',
                'tag' => 'h1',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '64px',
                    'font-size:tablet' => '48px',
                    'font-size:mobile_landscape' => '38px',
                    'font-weight' => '900',
                    'line-height' => '1.1',
                    'letter-spacing' => '-2px',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
            ),
        ),
        array(
            'id' => $ids['feature1'],
            'name' => 'block',
            'parent' => $ids['grid'],
            'children' => array( $ids['feature1_icon'], $ids['feature1_text'] ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '20',
                '_padding' => array( 'top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40 ),
                '_background' => array( 'color' => array( 'hex' => '#ffffff' ) ),
                '_border' => array(
                    'width' => array( 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1 ),
                    'style' => 'solid',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.1' ),
                    'radius' => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 200 ),
            ),
        ),
        array(
            'id' => $ids['feature1_icon'],
            'name' => 'icon',
            'parent' => $ids['feature1'],
            'children' => array(),
            'settings' => array(
                'icon' => array( 'library' => 'ionicons', 'icon' => 'ion-ios-rocket' ),
                '_color' => array( 'hex' => $accent_color ),
                '_typography' => array( 'font-size' => '48px' ),
            ),
        ),
        array(
            'id' => $ids['feature1_text'],
            'name' => 'text-basic',
            'parent' => $ids['feature1'],
            'children' => array(),
            'settings' => array(
                'text' => '<strong>Lightning Fast</strong><br>Optimized for speed and performance',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '18px',
                    'line-height' => '1.6',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
        array(
            'id' => $ids['feature2'],
            'name' => 'block',
            'parent' => $ids['grid'],
            'children' => array( $ids['feature2_icon'], $ids['feature2_text'] ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '20',
                '_padding' => array( 'top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40 ),
                '_background' => array( 'color' => array( 'hex' => '#ffffff' ) ),
                '_border' => array(
                    'width' => array( 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1 ),
                    'style' => 'solid',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.1' ),
                    'radius' => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 400 ),
            ),
        ),
        array(
            'id' => $ids['feature2_icon'],
            'name' => 'icon',
            'parent' => $ids['feature2'],
            'children' => array(),
            'settings' => array(
                'icon' => array( 'library' => 'ionicons', 'icon' => 'ion-ios-shield' ),
                '_color' => array( 'hex' => $accent_color ),
                '_typography' => array( 'font-size' => '48px' ),
            ),
        ),
        array(
            'id' => $ids['feature2_text'],
            'name' => 'text-basic',
            'parent' => $ids['feature2'],
            'children' => array(),
            'settings' => array(
                'text' => '<strong>Secure & Reliable</strong><br>Built with security at the core',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '18px',
                    'line-height' => '1.6',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
        array(
            'id' => $ids['cta_block'],
            'name' => 'block',
            'parent' => $ids['grid'],
            'children' => array( $ids['cta_text'], $ids['cta_button'] ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_justifyContent' => 'center',
                '_rowGap' => '24',
                '_padding' => array( 'top' => 40, 'right' => 40, 'bottom' => 40, 'left' => 40 ),
                '_background' => array( 'color' => array( 'hex' => $text_color, 'alpha' => '0.02' ) ),
                '_border' => array( 'radius' => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ) ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 600 ),
            ),
        ),
        array(
            'id' => $ids['cta_text'],
            'name' => 'text-basic',
            'parent' => $ids['cta_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'Ready to get started? Join us today and transform your digital presence.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '22px',
                    'font-weight' => '600',
                    'line-height' => '1.5',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
        array(
            'id' => $ids['cta_button'],
            'name' => 'button',
            'parent' => $ids['cta_block'],
            'children' => array(),
            'settings' => array(
                'text' => 'Get Started Now',
                'link' => array( 'url' => '#', 'type' => 'external' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-weight' => '700',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_typography:hover' => array(
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_background' => array( 'color' => array( 'hex' => $accent_color ) ),
                '_background:hover' => array( 'color' => array( 'hex' => $text_color ) ),
                '_padding' => array( 'top' => 18, 'right' => 40, 'bottom' => 18, 'left' => 40 ),
                '_border' => array( 'radius' => array( 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8 ) ),
                '_cssTransition' => '0.3s',
                '_alignSelf' => 'flex-start',
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * HERO STYLE 4: "Offset Layout"
 * Features: Elegant offset text with image background, sophisticated design
 */
function snn_hero_offset_layout( $args ) {
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];

    // This hero has a dark overlay, so ensure eyebrow text is bright/light
    // If accent is too dark for dark background, use white with slight opacity
    $eyebrow_color = snn_has_good_contrast( $accent_color, '#000000' ) ? $accent_color : '#ffffff';

    $ids = array(
        'section' => snn_generate_element_id(),
        'container' => snn_generate_element_id(),
        'content_wrapper' => snn_generate_element_id(),
        'eyebrow' => snn_generate_element_id(),
        'heading' => snn_generate_element_id(),
        'description' => snn_generate_element_id(),
        'buttons_wrapper' => snn_generate_element_id(),
        'primary_btn' => snn_generate_element_id(),
        'secondary_btn' => snn_generate_element_id(),
    );

    $content = array(
        array(
            'id' => $ids['section'],
            'name' => 'section',
            'parent' => 0,
            'children' => array( $ids['container'] ),
            'settings' => array(
                '_minHeight' => '100vh',
                '_background' => array(
                    'image' => array(
                        'url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2000',
                        'size' => 'cover',
                        'position' => 'center center',
                    ),
                    'color' => array( 'hex' => '#000000' ),
                ),
                '_gradient' => array(
                    'applyTo' => 'overlay',
                    'colors' => array(
                        array( 'color' => array( 'hex' => '#000000', 'alpha' => '0.6' ), 'stop' => '0' ),
                        array( 'color' => array( 'hex' => '#000000', 'alpha' => '0.3' ), 'stop' => '100' ),
                    ),
                ),
                '_padding' => array( 'top' => '80', 'bottom' => '80' ),
            ),
        ),
        array(
            'id' => $ids['container'],
            'name' => 'container',
            'parent' => $ids['section'],
            'children' => array( $ids['content_wrapper'] ),
            'settings' => array(),
        ),
        array(
            'id' => $ids['content_wrapper'],
            'name' => 'block',
            'parent' => $ids['container'],
            'children' => array( $ids['eyebrow'], $ids['heading'], $ids['description'], $ids['buttons_wrapper'] ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '24',
                '_maxWidth' => '700px',
                '_minHeight' => '80vh',
                '_justifyContent' => 'center',
            ),
        ),
        array(
            'id' => $ids['eyebrow'],
            'name' => 'text-basic',
            'parent' => $ids['content_wrapper'],
            'children' => array(),
            'settings' => array(
                'text' => 'SINCE 2024',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '13px',
                    'font-weight' => '700',
                    'letter-spacing' => '4px',
                    'text-transform' => 'uppercase',
                    'color' => array( 'hex' => $eyebrow_color ),
                ),
                '_animation' => array( 'type' => 'fadeIn', 'delay' => 100 ),
            ),
        ),
        array(
            'id' => $ids['heading'],
            'name' => 'heading',
            'parent' => $ids['content_wrapper'],
            'children' => array(),
            'settings' => array(
                'text' => 'Crafting Digital<br>Masterpieces',
                'tag' => 'h1',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '78px',
                    'font-size:tablet' => '58px',
                    'font-size:mobile_landscape' => '42px',
                    'font-weight' => '300',
                    'line-height' => '1.1',
                    'letter-spacing' => '-2px',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 200 ),
            ),
        ),
        array(
            'id' => $ids['description'],
            'name' => 'text-basic',
            'parent' => $ids['content_wrapper'],
            'children' => array(),
            'settings' => array(
                'text' => 'Experience design excellence with our award-winning team. We blend creativity with technical expertise to deliver remarkable digital solutions.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '19px',
                    'line-height' => '1.7',
                    'color' => array( 'hex' => '#ffffff', 'alpha' => '0.9' ),
                ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 400 ),
            ),
        ),
        array(
            'id' => $ids['buttons_wrapper'],
            'name' => 'block',
            'parent' => $ids['content_wrapper'],
            'children' => array( $ids['primary_btn'], $ids['secondary_btn'] ),
            'settings' => array(
                '_display' => 'flex',
                '_columnGap' => '16',
                '_rowGap' => '16',
                '_flexWrap' => 'wrap',
                '_margin' => array( 'top' => '16' ),
                '_animation' => array( 'type' => 'fadeInUp', 'delay' => 600 ),
            ),
        ),
        array(
            'id' => $ids['primary_btn'],
            'name' => 'button',
            'parent' => $ids['buttons_wrapper'],
            'children' => array(),
            'settings' => array(
                'text' => 'View Our Work',
                'link' => array( 'url' => '#', 'type' => 'external' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-weight' => '600',
                    'color' => array( 'hex' => $text_color ),
                ),
                '_typography:hover' => array(
                    'color' => array( 'hex' => $text_color ),
                ),
                '_background' => array( 'color' => array( 'hex' => '#ffffff' ) ),
                '_background:hover' => array( 'color' => array( 'hex' => $accent_color ) ),
                '_padding' => array( 'top' => 18, 'right' => 40, 'bottom' => 18, 'left' => 40 ),
                '_border' => array( 'radius' => array( 'top' => 6, 'right' => 6, 'bottom' => 6, 'left' => 6 ) ),
                '_cssTransition' => '0.3s',
            ),
        ),
        array(
            'id' => $ids['secondary_btn'],
            'name' => 'button',
            'parent' => $ids['buttons_wrapper'],
            'children' => array(),
            'settings' => array(
                'text' => 'Contact Us',
                'link' => array( 'url' => '#', 'type' => 'external' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-weight' => '600',
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_typography:hover' => array(
                    'color' => array( 'hex' => '#ffffff' ),
                ),
                '_background' => array( 'color' => array( 'hex' => 'transparent' ) ),
                '_background:hover' => array( 'color' => array( 'hex' => '#ffffff', 'alpha' => '0.1' ) ),
                '_padding' => array( 'top' => 18, 'right' => 40, 'bottom' => 18, 'left' => 40 ),
                '_border' => array(
                    'width' => array( 'top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2 ),
                    'style' => 'solid',
                    'color' => array( 'hex' => '#ffffff' ),
                    'radius' => array( 'top' => 6, 'right' => 6, 'bottom' => 6, 'left' => 6 ),
                ),
                '_cssTransition' => '0.3s',
            ),
        ),
    );

    return array( 'content' => $content );
}

/**
 * ==============================================================================
 * STANDARD SECTIONS (Original Code Preserved Below)
 * ==============================================================================
 */

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
    $accent_color = $args['accent_color']; // Added for border
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

    // CTA uses inverted colors for impact - dark bg, light text
    $cta_bg = $accent_color; // Dark background
    $cta_text = snn_get_contrast_text_color( $cta_bg ); // Ensure readable text on CTA background

    // Button should contrast with CTA background
    // Normal state: Light button with dark text
    // Hover state: Dark button with light text
    $cta_btn_bg = $cta_text === '#ffffff' ? '#ffffff' : '#000000'; // Opposite of background
    $cta_btn_text = snn_get_contrast_text_color( $cta_btn_bg ); // Text contrasts with button
    $cta_btn_bg_hover = $cta_bg; // Hover uses CTA background color
    $cta_btn_text_hover = $cta_text; // Hover text uses CTA text color

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
                    'color' => array( 'hex' => $cta_bg ),
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
                    'color' => array( 'hex' => $cta_text ),
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
                    'color' => array( 'hex' => $cta_text, 'alpha' => '0.95' ),
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
                'link' => array( 'url' => '#', 'type' => 'external' ),
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '18px',
                    'font-weight' => '700',
                    'letter-spacing' => '0.5px',
                    'color' => array( 'hex' => $cta_btn_text ),
                ),
                '_typography:hover' => array(
                    'color' => array( 'hex' => $cta_btn_text_hover ),
                ),
                '_backgroundColor' => array( 'hex' => $cta_btn_bg ),
                '_backgroundColor:hover' => array( 'hex' => $cta_btn_bg_hover ),
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
                '_cssTransition' => '0.3s',
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
    $secondary_color = $args['secondary_color']; // Not used in original code logic, but kept in signature

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
 * Generate sticky content section
 * Features: Sticky sidebar with scrolling content (great for product showcases, long-form content)
 */
function snn_generate_bricks_sticky_content( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];

    $section_id = snn_generate_element_id();
    $container_id = snn_generate_element_id();
    $sticky_col_id = snn_generate_element_id();
    $sticky_block_id = snn_generate_element_id();
    $sticky_image_id = snn_generate_element_id();
    $sticky_heading_id = snn_generate_element_id();
    $content_col_id = snn_generate_element_id();

    $content = array(
        array(
            'id' => $section_id,
            'name' => 'section',
            'parent' => 0,
            'children' => array( $container_id ),
            'settings' => array(
                '_height' => '100vh',
                '_minHeight' => '800px',
                '_background' => array( 'color' => array( 'hex' => $bg_color ) ),
                '_padding' => array( 'top' => $spacing['section'], 'bottom' => $spacing['section'] ),
            ),
        ),
        array(
            'id' => $container_id,
            'name' => 'container',
            'parent' => $section_id,
            'children' => array( $sticky_col_id, $content_col_id ),
            'settings' => array(
                '_display' => 'grid',
                '_gridTemplateColumns' => '1fr 1fr',
                '_gridTemplateColumns:mobile_landscape' => '1fr',
                '_gridGap' => '40',
                '_justifyItemsGrid' => 'start',
                '_alignItemsGrid' => 'start',
            ),
        ),
        // Sticky Column
        array(
            'id' => $sticky_col_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array( $sticky_block_id ),
            'settings' => array(),
        ),
        array(
            'id' => $sticky_block_id,
            'name' => 'block',
            'parent' => $sticky_col_id,
            'children' => array( $sticky_image_id, $sticky_heading_id ),
            'settings' => array(
                '_position' => 'sticky',
                '_top' => '100',
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '24',
                'label' => 'Sticky Content Block',
            ),
        ),
        array(
            'id' => $sticky_image_id,
            'name' => 'image',
            'parent' => $sticky_block_id,
            'children' => array(),
            'settings' => array(
                'image' => array(
                    'url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=800',
                    'size' => 'full',
                ),
                '_width' => '100%',
                '_aspectRatio' => '1/1',
                '_objectFit' => 'cover',
                '_border' => array(
                    'radius' => array( 'top' => 16, 'right' => 16, 'bottom' => 16, 'left' => 16 ),
                ),
            ),
        ),
        array(
            'id' => $sticky_heading_id,
            'name' => 'heading',
            'parent' => $sticky_block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Discover Our Innovation',
                'tag' => 'h2',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '42px',
                    'font-size:mobile_landscape' => '32px',
                    'font-weight' => '800',
                    'line-height' => '1.2',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        ),
        // Scrolling Content Column
        array(
            'id' => $content_col_id,
            'name' => 'block',
            'parent' => $container_id,
            'children' => array(),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '40',
            ),
        ),
    );

    // Add 4 content blocks in the scrolling column
    for ( $i = 1; $i <= 4; $i++ ) {
        $block_id = snn_generate_element_id();
        $block_heading_id = snn_generate_element_id();
        $block_text_id = snn_generate_element_id();

        // Add block ID to parent's children
        foreach ( $content as &$element ) {
            if ( $element['id'] === $content_col_id ) {
                $element['children'][] = $block_id;
                break;
            }
        }

        $content[] = array(
            'id' => $block_id,
            'name' => 'block',
            'parent' => $content_col_id,
            'children' => array( $block_heading_id, $block_text_id ),
            'settings' => array(
                '_display' => 'flex',
                '_flexDirection' => 'column',
                '_rowGap' => '16',
                '_padding' => array( 'top' => 32, 'right' => 32, 'bottom' => 32, 'left' => 32 ),
                '_background' => array( 'color' => array( 'hex' => '#ffffff' ) ),
                '_border' => array(
                    'width' => array( 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1 ),
                    'style' => 'solid',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.1' ),
                    'radius' => array( 'top' => 12, 'right' => 12, 'bottom' => 12, 'left' => 12 ),
                ),
                '_order' => (string) $i,
            ),
        );

        $content[] = array(
            'id' => $block_heading_id,
            'name' => 'heading',
            'parent' => $block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Feature ' . $i,
                'tag' => 'h3',
                '_typography' => array(
                    'font-family' => 'Poppins',
                    'font-size' => '28px',
                    'font-weight' => '700',
                    'color' => array( 'hex' => $text_color ),
                ),
            ),
        );

        $content[] = array(
            'id' => $block_text_id,
            'name' => 'text-basic',
            'parent' => $block_id,
            'children' => array(),
            'settings' => array(
                'text' => 'Detailed explanation of this feature and how it benefits your workflow. Our innovative approach ensures maximum efficiency and results.',
                '_typography' => array(
                    'font-family' => 'Inter',
                    'font-size' => '17px',
                    'line-height' => '1.7',
                    'color' => array( 'hex' => $text_color, 'alpha' => '0.8' ),
                ),
            ),
        );
    }

    return array( 'content' => $content );
}

/**
 * Generate generic section
 * This can include custom HTML/CSS/JS for interactive features
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

/**
 * ==============================================================================
 * HELPER: Generate custom HTML/CSS/JS element
 * ==============================================================================
 *
 * Use this when you need interactive features or custom code
 *
 * Example usage in a section:
 *
 * $custom_element = snn_create_custom_code_element(
 *     parent: $container_id,
 *     html: '<div id="counter">0</div>',
 *     css: '#counter { font-size: 48px; font-weight: bold; }',
 *     js: 'let count = 0; setInterval(() => { document.getElementById("counter").textContent = ++count; }, 1000);'
 * );
 *
 * @param string $parent Parent element ID
 * @param string $html HTML content
 * @param string $css CSS styles
 * @param string $js JavaScript code
 * @return array Bricks element array
 */
function snn_create_custom_code_element( $parent, $html = '', $css = '', $js = '' ) {
    $id = snn_generate_element_id();

    $content_parts = array();
    if ( ! empty( $html ) ) {
        $content_parts[] = $html;
    }
    if ( ! empty( $css ) ) {
        $content_parts[] = "<style>\n" . $css . "\n</style>";
    }
    if ( ! empty( $js ) ) {
        $content_parts[] = "<script>\n" . $js . "\n</script>";
    }

    return array(
        'id' => $id,
        'name' => 'custom-html-css-script',
        'parent' => $parent,
        'children' => array(),
        'settings' => array(
            'content' => implode( "\n", $content_parts ),
        ),
    );
}