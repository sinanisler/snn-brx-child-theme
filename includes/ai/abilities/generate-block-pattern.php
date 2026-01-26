<?php 
/**
 * Generate Block Pattern Ability
 *
 * This ability generates rich, detailed block patterns using WordPress core blocks.
 * It can create complete sections like heroes, about sections, services, CTAs, and more.
 *
 * The generated patterns use proper block markup and follow WordPress pattern best practices.
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_generate_block_pattern_ability' );
function snn_register_generate_block_pattern_ability() {
    wp_register_ability(
        'snn/generate-block-pattern',
        array(
            'label'       => __( 'Generate Block Pattern', 'snn' ),
            'description' => __( 'Generates rich, detailed block patterns using WordPress core blocks. Creates complete sections from scratch with professional styling. Use for rapid prototyping and structured layouts.

PATTERN TYPES (pattern_type):
- hero: Hero banners with headings, descriptions, CTAs (large text, centered, buttons) [ALIGN: full] [CLASS: hero-section]
- about: About sections with text + imagery (2-column, lists, professional) [ALIGN: wide] [CLASS: about-section]
- services: Service grids (1-4 columns, titles, descriptions) [ALIGN: wide] [CLASS: services-section]
- cta: Call-to-action sections (centered, prominent buttons, conversion-focused) [ALIGN: full] [CLASS: cta-section]
- testimonials: Customer reviews (quotes in columns, attribution) [ALIGN: wide] [CLASS: testimonials-section]
- team: Team member grids (images, names, positions, configurable columns) [ALIGN: wide] [CLASS: team-section]
- stats: Statistics showcase (large numbers, labels, multi-column) [ALIGN: wide] [CLASS: stats-section]
- faq: FAQ sections (accordion/details blocks, expandable Q&A) [ALIGN: wide] [CLASS: faq-section]
- custom: Generic flexible pattern [ALIGN: none] [CLASS: custom-section]

ALIGNMENT BEHAVIOR:
Patterns automatically apply appropriate alignment based on type:
- "full" (alignfull): Full-width edge-to-edge sections for maximum impact (hero, cta)
- "wide" (alignwide): Wide but contained sections for most content (services, about, team, testimonials, stats, faq)
- "none": Default constrained width for generic content

STYLE OPTIONS (style_preference):
modern (clean/contemporary), minimal (essential only), bold (attention-grabbing), elegant (refined), playful (creative/fun), professional (corporate), creative (artistic/unique)

KEY PARAMETERS:
- content_description: BE SPECIFIC about headings, text, items, layout. Good: "Hero with heading \'Transform Business\', subtext about digital services, \'Get Started\' button". Bad: "Make a hero"
- layout_columns: 1-4 columns for grids (services, team, testimonials, stats)
- color_scheme: {background: "#fff", text: "#000", accent: "#0066cc"}
- spacing: compact (40/16/8px), normal (80/24/12px), spacious (120/40/20px)
- action_type: replace (all content), append (add to end), prepend (add to start)
- semantic_class: Optional custom semantic class name. If not provided, defaults to pattern-type-based class (hero-section, services-section, etc.)

USAGE EXAMPLES:
1. Hero: {pattern_type: "hero", content_description: "Heading \'Welcome to Our Agency\', description about creative services, \'Start Project\' button", style_preference: "modern", color_scheme: {background: "#000", text: "#fff", accent: "#00ff88"}, semantic_class: "agency-hero"}
2. Services: {pattern_type: "services", content_description: "3 services: Web Dev (custom sites), Mobile Apps (iOS/Android), Cloud (scalable)", layout_columns: 3, semantic_class: "our-services"}
3. Stats: {pattern_type: "stats", content_description: "500+ projects, 50+ team, 98% satisfaction", spacing: "spacious", semantic_class: "company-stats"}
4. Team: {pattern_type: "team", content_description: "4 leadership members", layout_columns: 4, include_images: true, semantic_class: "leadership-team"}

AVAILABLE CORE BLOCKS:
Layout: group, columns, column, cover, media-text, spacer, separator | Content: paragraph, heading, list, list-item, quote, pullquote, table | Media: image, gallery, video, audio | Interactive: button, buttons, accordion, details, social-links

CRITICAL WORDPRESS BLOCK RULES (prevent broken blocks):

1. HTML STRUCTURE:
   - Close ALL tags: <p>Text</p> NOT <p>Text
   - Proper nesting: <p><strong>Bold</strong></p> NOT <strong><p>Bold</p></strong>
   - Lists need containers: <ul><li>Item 1</li><li>Item 2</li></ul>
   - No loose text outside block elements
   - Escape % symbols in inline styles: 50%% NOT 50%

2. BLOCK COMMENT SYNTAX:
   - Opening: <!-- wp:block-name {JSON_attributes} -->
   - Closing: <!-- /wp:block-name -->
   - Self-closing NOT allowed - always use opening/closing pair
   - JSON must be valid: {"key":"value"} with proper quotes
   - No trailing commas in JSON objects

3. BLOCK ATTRIBUTES (JSON structure):
   - metadata: {"categories":["category"],"patternName":"name","name":"Display Name"}
   - style: {"spacing":{},"color":{},"typography":{},"border":{},"elements":{}}
   - layout: {"type":"constrained|flex","contentSize":"1180px","justifyContent":"left|center|right"}
   - Spacing: {"padding":{"top":"80px","right":"26px","bottom":"80px","left":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"24px"}
   - Border: Individual sides {"top":{"color":"#e0e0e0","width":"1px","style":"solid"},"radius":"24px"}

4. CSS CLASSES (WordPress conventions):
   - Color classes: has-text-color, has-background, has-link-color, has-{slug}-color
   - Typography: has-custom-font-size (required when using inline font-size styles)
   - Border: has-border-color
   - Alignment: has-text-align-center, aligncenter, alignleft, alignright
   - Always include semantic classes when using colors/backgrounds/typography

5. SEMANTIC CLASS NAMES:
   - ALWAYS include semantic className in root group: "className":"pattern-section"
   - Pattern type defaults: hero-section, about-section, services-section, cta-section, etc.
   - Use descriptive, kebab-case names: agency-hero, our-services, company-stats
   - Class appears in HTML: class="wp-block-group alignfull hero-section has-text-color..."
   - Allows for easy CSS targeting and styling customization

6. INLINE STYLES (format strictly):
   - Format: style="property:value;property:value"
   - Colors: color:#000000;background-color:#ffffff
   - Spacing: margin-top:24px;padding-top:80px (include units)
   - Typography: font-size:48px;line-height:1.2;font-weight:500
   - Borders: border-radius:24px;border-color:#e0e0e0;border-width:1px
   - No spaces around colons/semicolons

7. COLUMN LAYOUTS:
   - Parent: <!-- wp:columns {"style":{}} -->
   - Child: <!-- wp:column {"width":"50%%"} --> (escape % as %%)
   - Use flex-basis in inline style: style="flex-basis:50%%"
   - Equal columns: omit width attribute
   - Column gaps: blockGap:{"top":"34px","left":"24px"}

8. COLOR INHERITANCE:
   - Elements for link colors: "elements":{"link":{"color":{"text":"#000"}}}
   - Apply to parent groups to cascade to children
   - Use both class and inline style for reliability

9. IMAGE BLOCKS:
   - Structure: <!-- wp:image {"sizeSlug":"large","align":"center"} -->
   - Wrap in <figure>: <figure class="wp-block-image"><img src="" alt=""/></figure>
   - Always include alt text (can be empty: alt="")
   - Include size classes: size-large, aligncenter
   - For sized images: {"width":"161px","height":"auto"}

10. BUTTON BLOCKS:
   - Always wrap button in buttons: <!-- wp:buttons --> with <!-- wp:button -->
   - Structure: <div class="wp-block-button"><a class="wp-block-button__link">Text</a></div>
   - Include wp-element-button class on link
   - CRITICAL: When using custom font size, add has-custom-font-size class
   - Example: class="wp-block-button__link has-text-color has-background has-custom-font-size wp-element-button"
   - Colors: {"style":{"color":{"background":"#000","text":"#fff"}}}

11. SPACING CONSISTENCY:
    - Section padding: 80px (normal), 40px (compact), 120px (spacious)
    - Block gaps: 24px (normal), 16px (compact), 40px (spacious)
    - Element margins: 12px (normal), 8px (compact), 20px (spacious)
    - Always use margin:{top:"0",bottom:"0"} on containers to prevent double spacing

12. METADATA REQUIREMENTS:
    - Root group must have: {"metadata":{"categories":["type"],"patternName":"unique-slug","name":"Display Name"}}
    - Categories match pattern type: hero, about, services, cta, testimonials, team, stats, faq
    - patternName should be unique and descriptive

13. VALIDATION CHECKLIST:
    - Every opening block comment has matching closing comment
    - All HTML tags are properly closed and nested
    - JSON attributes are valid (no trailing commas, proper quotes)
    - All % symbols in styles are escaped as %%
    - Color values are valid hex codes (#000000 not #000)
    - Spacing values include units (24px not 24)
    - Classes match WordPress conventions
    - Semantic className is included in root group and appears in HTML class attribute

BEST PRACTICES:
- Always include semantic class names (hero-section, services-section, custom-hero, etc.) for easy CSS targeting
- Be descriptive in content_description (specific headings, exact text, structure details)
- Match colors to brand (use color_scheme with hex codes, always 6 digits: #000000 not #000)
- Choose appropriate spacing (compact for dense info, spacious for emphasis)
- Layer patterns with action_type: "append" to build complete pages
- Use layout_columns based on content: 1 (detailed), 2 (balanced), 3 (standard grid), 4 (dense grid)
- Maintain consistent contentSize: 1180px for full width, 900px for centered content, 800px for narrow (CTA/FAQ)
- Apply margin:{top:"0",bottom:"0"} to container groups to prevent unwanted spacing
- Use semantic heading levels: h2 for section titles, h3 for subsection titles
- Include both CSS classes AND inline styles for maximum compatibility
- Test color contrast: ensure text is readable against backgrounds (WCAG AA minimum)
- Use blockGap instead of individual margins where possible for consistency
- Group related elements in nested groups for better structure and maintainability', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'pattern_type', 'content_description' ),
                'properties' => array(
                    'pattern_type' => array(
                        'type'        => 'string',
                        'enum'        => array( 'hero', 'about', 'services', 'cta', 'testimonials', 'team', 'portfolio', 'pricing', 'faq', 'contact', 'footer', 'stats', 'custom' ),
                        'description' => 'Type of pattern to generate. Choose the category that best fits the desired section.',
                    ),
                    'content_description' => array(
                        'type'        => 'string',
                        'description' => 'Detailed description of what the pattern should contain. Include: main message/heading, key points, number of items, desired layout (columns, rows), style preferences (modern, minimal, bold), colors if specific, any special features needed.',
                        'minLength'   => 10,
                    ),
                    'style_preference' => array(
                        'type'        => 'string',
                        'enum'        => array( 'modern', 'minimal', 'bold', 'elegant', 'playful', 'professional', 'creative' ),
                        'description' => 'Overall style/mood for the pattern design.',
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
                        'description' => 'Whether to include image blocks with placeholder URLs.',
                        'default'     => true,
                    ),
                    'color_scheme' => array(
                        'type'        => 'object',
                        'description' => 'Optional color scheme. Provide background, text, and accent colors.',
                        'properties'  => array(
                            'background' => array( 'type' => 'string', 'description' => 'Background color (hex, rgb, or color name)' ),
                            'text'       => array( 'type' => 'string', 'description' => 'Text color (hex, rgb, or color name)' ),
                            'accent'     => array( 'type' => 'string', 'description' => 'Accent color for buttons, highlights (hex, rgb, or color name)' ),
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
                        'description' => 'How to insert the pattern: replace (replace all content), append (add to end), prepend (add to start)',
                        'default'     => 'append',
                    ),
                    'semantic_class' => array(
                        'type'        => 'string',
                        'description' => 'Optional semantic CSS class name for the pattern (e.g., "hero-section", "our-services"). Defaults to pattern type + "-section" if not provided.',
                        'pattern'     => '^[a-z0-9-]+$',
                    ),
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'Optional: Post ID to insert the pattern into.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success'  => array(
                        'type'        => 'boolean',
                        'description' => 'Whether pattern generation was successful',
                    ),
                    'message' => array(
                        'type'        => 'string',
                        'description' => 'Result message',
                    ),
                    'pattern_markup' => array(
                        'type'        => 'string',
                        'description' => 'Generated block pattern markup',
                    ),
                    'pattern_info' => array(
                        'type'        => 'object',
                        'description' => 'Information about the generated pattern',
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
                $pattern_type = $input['pattern_type'];
                $content_description = $input['content_description'];
                $style_preference = $input['style_preference'] ?? 'modern';
                $layout_columns = $input['layout_columns'] ?? 3;
                $include_images = $input['include_images'] ?? true;
                $color_scheme = $input['color_scheme'] ?? array();
                $spacing = $input['spacing'] ?? 'normal';
                $action_type = $input['action_type'] ?? 'append';
                $post_id = $input['post_id'] ?? null;
                $semantic_class = $input['semantic_class'] ?? $pattern_type . '-section';

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
                    'compact'  => array( 'section' => '40px', 'block' => '16px', 'element' => '8px' ),
                    'normal'   => array( 'section' => '80px', 'block' => '24px', 'element' => '12px' ),
                    'spacious' => array( 'section' => '120px', 'block' => '40px', 'element' => '20px' ),
                );
                $spacing_values = $spacing_map[ $spacing ];

                // Set default colors if not provided
                $bg_color = $color_scheme['background'] ?? '#ffffff';
                $text_color = $color_scheme['text'] ?? '#000000';
                $accent_color = $color_scheme['accent'] ?? '#000000';

                // Generate pattern based on type
                $pattern_markup = snn_generate_pattern_markup( array(
                    'type'              => $pattern_type,
                    'description'       => $content_description,
                    'style'             => $style_preference,
                    'columns'           => $layout_columns,
                    'include_images'    => $include_images,
                    'bg_color'          => $bg_color,
                    'text_color'        => $text_color,
                    'accent_color'      => $accent_color,
                    'spacing'           => $spacing_values,
                    'semantic_class'    => $semantic_class,
                ) );

                // Calculate word count for feedback
                $word_count = str_word_count( wp_strip_all_tags( $pattern_markup ) );

                // Build client command
                $client_command = array(
                    'type'             => 'update_editor_content',
                    'content'          => $pattern_markup,
                    'action'           => $action_type,
                    'post_id'          => $post_id,
                    'save_immediately' => false,
                    'word_count'       => $word_count,
                );

                // Return instruction for JavaScript to execute
                return array(
                    'success'                   => true,
                    'message'                   => sprintf(
                        __( 'Generated %s pattern (%d words). Ready to insert into editor.', 'snn' ),
                        $pattern_type,
                        $word_count
                    ),
                    'pattern_markup'            => $pattern_markup,
                    'pattern_info'              => array(
                        'type'        => $pattern_type,
                        'style'       => $style_preference,
                        'blocks_used' => snn_count_blocks_in_pattern( $pattern_markup ),
                        'word_count'  => $word_count,
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
 * Generate pattern markup based on parameters
 */
function snn_generate_pattern_markup( $args ) {
    $type = $args['type'];
    $description = $args['description'];
    $style = $args['style'];
    $columns = $args['columns'];
    $include_images = $args['include_images'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];
    $spacing = $args['spacing'];

    // Generate appropriate pattern based on type
    switch ( $type ) {
        case 'hero':
            return snn_generate_hero_pattern( $args );
        case 'about':
            return snn_generate_about_pattern( $args );
        case 'services':
            return snn_generate_services_pattern( $args );
        case 'cta':
            return snn_generate_cta_pattern( $args );
        case 'testimonials':
            return snn_generate_testimonials_pattern( $args );
        case 'team':
            return snn_generate_team_pattern( $args );
        case 'stats':
            return snn_generate_stats_pattern( $args );
        case 'faq':
            return snn_generate_faq_pattern( $args );
        default:
            return snn_generate_generic_pattern( $args );
    }
}

/**
 * Generate hero pattern
 */
function snn_generate_hero_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];
    $semantic_class = $args['semantic_class'] ?? 'hero-section';

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["hero"],"patternName":"generated-hero"},"align":"full","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"},"color":{"background":"%s","text":"%s"},"elements":{"link":{"color":{"text":"%s"}}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull %s has-text-color has-background has-link-color" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:group {"style":{"spacing":{"blockGap":"%s"}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"72px","lineHeight":"1.1"},"color":{"text":"%s"},"elements":{"link":{"color":{"text":"%s"}}}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color has-link-color" style="color:%s;font-size:72px;line-height:1.1">%s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"%s"}}}} -->
<p class="has-text-align-center" style="margin-top:%s;font-size:20px">%s</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"%s"}}}} -->
<div class="wp-block-buttons" style="margin-top:%s">
<!-- wp:button {"style":{"color":{"background":"%s","text":"#ffffff"},"elements":{"link":{"color":{"text":"#ffffff"}}},"typography":{"fontSize":"18px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-link-color has-custom-font-size wp-element-button" style="color:#ffffff;background-color:%s;font-size:18px">Get Started</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $bg_color, $text_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'],
        $text_color, $text_color, $text_color,
        'Your Compelling Hero Heading Goes Here',
        $spacing['block'], $spacing['block'],
        'This is a brief description that captures attention and explains your value proposition.',
        $spacing['block'], $spacing['block'],
        $accent_color, $accent_color
    );
}

/**
 * Generate about pattern
 */
function snn_generate_about_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $semantic_class = $args['semantic_class'] ?? 'about-section';

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["about"],"patternName":"generated-about"},"align":"wide","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignwide %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"%s","left":"%s"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"width":"50%%"} -->
<div class="wp-block-column" style="flex-basis:50%%">
<!-- wp:heading {"style":{"typography":{"fontSize":"48px","lineHeight":"1.2"}}} -->
<h2 class="wp-block-heading" style="font-size:48px;line-height:1.2">About Our Company</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p style="margin-top:%s">We are dedicated to providing exceptional service and innovative solutions. Our team brings years of experience and expertise to every project.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"spacing":{"margin":{"top":"%s"}}}} -->
<ul style="margin-top:%s">
<li>Quality-focused approach</li>
<li>Expert team members</li>
<li>Client satisfaction guaranteed</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%%"} -->
<div class="wp-block-column" style="flex-basis:50%%">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="" alt="About us image"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'], $spacing['block'],
        $spacing['element'], $spacing['element'],
        $spacing['element'], $spacing['element']
    );
}

/**
 * Generate services pattern
 */
function snn_generate_services_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $columns = $args['columns'];
    $semantic_class = $args['semantic_class'] ?? 'services-section';

    $column_blocks = '';
    $services = array(
        array( 'title' => 'Service One', 'desc' => 'Description of our first service offering and its benefits.' ),
        array( 'title' => 'Service Two', 'desc' => 'Description of our second service offering and its benefits.' ),
        array( 'title' => 'Service Three', 'desc' => 'Description of our third service offering and its benefits.' ),
    );

    foreach ( array_slice( $services, 0, $columns ) as $service ) {
        $column_blocks .= sprintf(
            '<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"28px"}}} -->
<h3 class="wp-block-heading" style="font-size:28px">%s</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p style="margin-top:%s">%s</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

',
            $service['title'],
            $spacing['element'], $spacing['element'],
            $service['desc']
        );
    }

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["services"],"patternName":"generated-services"},"align":"wide","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignwide %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:48px">Our Services</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"%s"},"blockGap":{"top":"%s","left":"%s"}}}} -->
<div class="wp-block-columns" style="margin-top:%s">
%s
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $column_blocks
    );
}

/**
 * Generate CTA pattern
 */
function snn_generate_cta_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $accent_color = $args['accent_color'];
    $semantic_class = $args['semantic_class'] ?? 'cta-section';

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["cta"],"patternName":"generated-cta"},"align":"full","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group alignfull %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px","lineHeight":"1.2"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:48px;line-height:1.2">Ready to Get Started?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p class="has-text-align-center" style="margin-top:%s">Join thousands of satisfied customers who have transformed their business with our solutions.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"%s"}}}} -->
<div class="wp-block-buttons" style="margin-top:%s">
<!-- wp:button {"style":{"color":{"background":"%s","text":"#ffffff"},"typography":{"fontSize":"18px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-custom-font-size wp-element-button" style="color:#ffffff;background-color:%s;font-size:18px">Start Now</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['element'], $spacing['element'],
        $spacing['block'], $spacing['block'],
        $accent_color, $accent_color
    );
}

/**
 * Generate testimonials pattern
 */
function snn_generate_testimonials_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $semantic_class = $args['semantic_class'] ?? 'testimonials-section';

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["testimonials"],"patternName":"generated-testimonials"},"align":"wide","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignwide %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:48px">What Our Clients Say</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"%s"},"blockGap":{"top":"%s","left":"%s"}}}} -->
<div class="wp-block-columns" style="margin-top:%s">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote {"style":{"spacing":{"padding":{"top":"%s","right":"%s","bottom":"%s","left":"%s"}},"border":{"width":"1px","style":"solid","color":"#e0e0e0"}}} -->
<blockquote class="wp-block-quote has-border-color" style="border-color:#e0e0e0;border-width:1px;border-style:solid;padding-top:%s;padding-right:%s;padding-bottom:%s;padding-left:%s">
<p>This service exceeded our expectations. Highly recommend to anyone looking for quality and professionalism.</p>
<cite>— John Doe, CEO</cite>
</blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote {"style":{"spacing":{"padding":{"top":"%s","right":"%s","bottom":"%s","left":"%s"}},"border":{"width":"1px","style":"solid","color":"#e0e0e0"}}} -->
<blockquote class="wp-block-quote has-border-color" style="border-color:#e0e0e0;border-width:1px;border-style:solid;padding-top:%s;padding-right:%s;padding-bottom:%s;padding-left:%s">
<p>Outstanding results and excellent customer support. They truly care about their clients success.</p>
<cite>— Jane Smith, Marketing Director</cite>
</blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block']
    );
}

/**
 * Generate team pattern
 */
function snn_generate_team_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $columns = $args['columns'];
    $semantic_class = $args['semantic_class'] ?? 'team-section';

    $column_blocks = '';
    for ( $i = 1; $i <= $columns; $i++ ) {
        $column_blocks .= sprintf(
            '<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"sizeSlug":"large","align":"center"} -->
<figure class="wp-block-image aligncenter size-large"><img src="" alt="Team member %d"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"textAlign":"center","style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"top":"%s"}}}} -->
<h3 class="wp-block-heading has-text-align-center" style="margin-top:%s;font-size:24px">Team Member %d</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p class="has-text-align-center" style="margin-top:%s">Position Title</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

',
            $i,
            $spacing['element'], $spacing['element'],
            $i,
            $spacing['element'], $spacing['element']
        );
    }

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["team"],"patternName":"generated-team"},"align":"wide","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignwide %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:48px">Meet Our Team</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"%s"},"blockGap":{"top":"%s","left":"%s"}}}} -->
<div class="wp-block-columns" style="margin-top:%s">
%s
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $column_blocks
    );
}

/**
 * Generate stats pattern
 */
function snn_generate_stats_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $semantic_class = $args['semantic_class'] ?? 'stats-section';

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["stats"],"patternName":"generated-stats"},"align":"wide","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignwide %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"%s","left":"%s"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"64px","lineHeight":"1"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:64px;line-height:1">500+</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p class="has-text-align-center" style="margin-top:%s">Projects Completed</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"64px","lineHeight":"1"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:64px;line-height:1">50+</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p class="has-text-align-center" style="margin-top:%s">Team Members</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"64px","lineHeight":"1"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:64px;line-height:1">98%%</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p class="has-text-align-center" style="margin-top:%s">Client Satisfaction</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'], $spacing['block'],
        $spacing['element'], $spacing['element'],
        $spacing['element'], $spacing['element'],
        $spacing['element'], $spacing['element']
    );
}

/**
 * Generate FAQ pattern
 */
function snn_generate_faq_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $semantic_class = $args['semantic_class'] ?? 'faq-section';

    return sprintf(
        '<!-- wp:group {"metadata":{"categories":["faq"],"patternName":"generated-faq"},"align":"wide","className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group alignwide %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:48px">Frequently Asked Questions</h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"%s"},"blockGap":"%s"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:%s">
<!-- wp:details {"style":{"spacing":{"padding":{"top":"%s","right":"%s","bottom":"%s","left":"%s"}},"border":{"width":"1px","style":"solid","color":"#e0e0e0"}}} -->
<details class="wp-block-details has-border-color" style="border-color:#e0e0e0;border-width:1px;border-style:solid;padding-top:%s;padding-right:%s;padding-bottom:%s;padding-left:%s">
<summary>What services do you offer?</summary>
<!-- wp:paragraph -->
<p>We offer a comprehensive range of services including design, development, and consultation to meet all your business needs.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details {"style":{"spacing":{"padding":{"top":"%s","right":"%s","bottom":"%s","left":"%s"}},"border":{"width":"1px","style":"solid","color":"#e0e0e0"}}} -->
<details class="wp-block-details has-border-color" style="border-color:#e0e0e0;border-width:1px;border-style:solid;padding-top:%s;padding-right:%s;padding-bottom:%s;padding-left:%s">
<summary>How long does a typical project take?</summary>
<!-- wp:paragraph -->
<p>Project timelines vary based on scope and complexity, but most projects are completed within 4-8 weeks.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details {"style":{"spacing":{"padding":{"top":"%s","right":"%s","bottom":"%s","left":"%s"}},"border":{"width":"1px","style":"solid","color":"#e0e0e0"}}} -->
<details class="wp-block-details has-border-color" style="border-color:#e0e0e0;border-width:1px;border-style:solid;padding-top:%s;padding-right:%s;padding-bottom:%s;padding-left:%s">
<summary>Do you offer support after project completion?</summary>
<!-- wp:paragraph -->
<p>Yes, we provide ongoing support and maintenance packages to ensure your continued success.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['block'], $spacing['element'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block'],
        $spacing['block'], $spacing['block'], $spacing['block'], $spacing['block']
    );
}

/**
 * Generate generic pattern
 */
function snn_generate_generic_pattern( $args ) {
    $spacing = $args['spacing'];
    $bg_color = $args['bg_color'];
    $text_color = $args['text_color'];
    $semantic_class = $args['semantic_class'] ?? 'custom-section';

    return sprintf(
        '<!-- wp:group {"className":"%s","style":{"spacing":{"padding":{"top":"%s","bottom":"%s","left":"26px","right":"26px"},"margin":{"top":"0","bottom":"0"},"blockGap":"%s"},"color":{"background":"%s","text":"%s"}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group %s has-text-color has-background" style="color:%s;background-color:%s;margin-top:0;margin-bottom:0;padding-top:%s;padding-right:26px;padding-bottom:%s;padding-left:26px">
<!-- wp:heading {"style":{"typography":{"fontSize":"48px"}}} -->
<h2 class="wp-block-heading" style="font-size:48px">Section Heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"%s"}}}} -->
<p style="margin-top:%s">Your content goes here. This is a flexible pattern that can be customized to fit your needs.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
        $semantic_class,
        $spacing['section'], $spacing['section'], $spacing['block'], $bg_color, $text_color,
        $semantic_class, $text_color, $bg_color, $spacing['section'], $spacing['section'],
        $spacing['element'], $spacing['element']
    );
}

/**
 * Count blocks in pattern markup
 */
function snn_count_blocks_in_pattern( $markup ) {
    preg_match_all( '/<!-- wp:([a-z-\/]+)/', $markup, $matches );
    return count( $matches[0] );
}
