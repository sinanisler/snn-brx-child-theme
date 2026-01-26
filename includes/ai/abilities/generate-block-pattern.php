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
            'description' => __( '🎨 ABSTRACT LAYOUT ENGINE - Design sections using structural primitives instead of predefined templates.

🏗️ LAYOUT MODES (layout_mode):
Instead of picking "hero" or "services", you choose HOW it should be structured:

- stack: Vertical content flow (headings, paragraphs, buttons stacked vertically). Perfect for: CTA sections, simple content blocks, focused messages.
- grid: Multi-column layout (1-4 columns). Perfect for: Services, features, team members, pricing tables, statistics.
- banner: Full-width cover with background image and overlay text. Perfect for: Hero sections, announcements, large CTAs.
- media-text: Split-screen image + text side-by-side. Perfect for: About sections, product showcases, image-heavy content.

📐 KEY PARAMETERS:

1. layout_mode (REQUIRED): Choose structural framework (stack/grid/banner/media-text)

2. content_slots (REQUIRED): Array of content objects. Number of objects = number of columns/items.
   Each slot can contain:
   - heading: Main title text
   - subheading: Subtitle (optional)
   - text: Body content
   - button_text: CTA button label
   - image_url: Image URL (leave empty for placeholder)

3. column_count: Number of columns for grid layouts (1-4). Ignored for banner/media-text.

4. style_config: Appearance settings
   - background_color: Section background (hex: #ffffff)
   - text_color: Text color (hex: #000000)
   - is_dark_mode: Boolean for dark mode
   - alignment: "full" (edge-to-edge) or "wide" (contained)

5. action_type: How to insert - "replace" (all content), "append" (add to end), "prepend" (add to start)

6. post_id: Target post ID (CRITICAL for "Add more" functionality)

💡 USAGE EXAMPLES:

1. Hero Banner:
{
  layout_mode: "banner",
  content_slots: [{
    heading: "Transform Your Business",
    text: "Professional solutions for modern challenges",
    button_text: "Get Started"
  }],
  style_config: {
    background_color: "#000000",
    text_color: "#ffffff",
    alignment: "full"
  }
}

2. 3-Column Services Grid:
{
  layout_mode: "grid",
  column_count: 3,
  content_slots: [
    {heading: "Web Development", text: "Custom websites built with modern tech"},
    {heading: "Mobile Apps", text: "iOS and Android applications"},
    {heading: "Cloud Solutions", text: "Scalable infrastructure"}
  ]
}

3. About Section (Media + Text):
{
  layout_mode: "media-text",
  content_slots: [{
    heading: "Our Story",
    text: "Founded in 2020, we help businesses grow through technology",
    button_text: "Learn More"
  }]
}

4. Statistics Stack:
{
  layout_mode: "stack",
  content_slots: [{
    heading: "500+",
    text: "Projects Completed",
  }, {
    heading: "50+",
    text: "Team Members"
  }]
}

🎯 WHY THIS IS BETTER:
- AI decides WHAT to build, not just which template to pick
- One system replaces 9+ hardcoded functions
- Want 4 pricing columns? Just grid + 4 slots
- Want team section? Just grid + slots with images
- Want footer? Stack with multiple text blocks
- Infinite flexibility without code changes

🚀 AVAILABLE CORE BLOCKS:
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
                'required'   => array( 'layout_mode', 'content_slots' ),
                'properties' => array(
                    'post_id' => array(
                        'type'        => 'integer',
                        'description' => 'CRITICAL: ID of the current post. Must pass this to append correctly.',
                    ),
                    'layout_mode' => array(
                        'type'        => 'string',
                        'enum'        => array( 'stack', 'grid', 'banner', 'media-text' ),
                        'description' => 'The structural framework. "stack" = vertical centered content. "grid" = multi-column. "banner" = background image with overlay text. "media-text" = split screen image/text.',
                    ),
                    'column_count' => array(
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'maximum'     => 4,
                        'description' => 'Number of horizontal slots. Automatically ignored if layout_mode is banner.',
                    ),
                    'content_slots' => array(
                        'type'        => 'array',
                        'description' => 'Array of content objects. If 3 columns, provide 3 objects. If hero, provide 1.',
                        'items'       => array(
                            'type'       => 'object',
                            'properties' => array(
                                'heading'     => array( 'type' => 'string' ),
                                'subheading'  => array( 'type' => 'string' ),
                                'text'        => array( 'type' => 'string' ),
                                'button_text' => array( 'type' => 'string' ),
                                'image_url'   => array( 'type' => 'string', 'description' => 'Leave empty for placeholder' ),
                            ),
                        ),
                    ),
                    'style_config' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'background_color' => array( 'type' => 'string', 'default' => '#ffffff' ),
                            'text_color'       => array( 'type' => 'string', 'default' => '#000000' ),
                            'is_dark_mode'     => array( 'type' => 'boolean' ),
                            'alignment'        => array( 'type' => 'string', 'enum' => array( 'full', 'wide' ) ),
                        ),
                    ),
                    'action_type' => array(
                        'type'        => 'string',
                        'enum'        => array( 'replace', 'append', 'prepend' ),
                        'description' => 'How to insert the pattern: replace (replace all content), append (add to end), prepend (add to start)',
                        'default'     => 'append',
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
                $layout_mode = $input['layout_mode'];
                $content_slots = $input['content_slots'];
                $column_count = $input['column_count'] ?? 1;
                $style_config = $input['style_config'] ?? array();
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

                // Generate pattern using abstract layout engine
                $pattern_markup = snn_generate_pattern_markup( array(
                    'layout_mode'   => $layout_mode,
                    'content_slots' => $content_slots,
                    'column_count'  => $column_count,
                    'style_config'  => $style_config,
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
                        __( 'Generated %s layout (%d words). Ready to insert into editor.', 'snn' ),
                        $layout_mode,
                        $word_count
                    ),
                    'pattern_markup'            => $pattern_markup,
                    'pattern_info'              => array(
                        'layout_mode' => $layout_mode,
                        'slots_count' => count( $content_slots ),
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
 * The Universal Layout Engine
 */
function snn_generate_pattern_markup( $args ) {
    // 1. Unpack Arguments
    $layout_mode = $args['layout_mode']; // stack, grid, banner, media-text
    $slots = $args['content_slots'];
    $cols = $args['column_count'] ?? 1;
    $style = $args['style_config'] ?? array();
    
    // Defaults
    $bg = $style['background_color'] ?? '#ffffff';
    $text = $style['text_color'] ?? '#000000';
    $align = $style['alignment'] ?? 'wide';
    $is_dark = $style['is_dark_mode'] ?? false;
    
    // Base Classes
    $semantic_class = 'dynamic-section-' . $layout_mode;
    $text_class = $is_dark ? 'has-text-color has-background' : 'has-text-color has-background';
    
    // 2. Generate Inner Content (The Slots)
    $generated_slots = array();
    foreach ( $slots as $index => $slot ) {
        $generated_slots[] = snn_render_slot_content( $slot, $layout_mode, $text );
    }

    // 3. Wrap based on Layout Mode
    $inner_html = '';

    switch ( $layout_mode ) {
        case 'grid':
            // WP Columns Block
            $col_markup = '';
            foreach ( $generated_slots as $slot_html ) {
                $col_markup .= sprintf(
                    '<div class="wp-block-column">%s</div>' . "\n",
                    $slot_html
                );
            }
            $inner_html = sprintf(
                '<div class="wp-block-columns alignwide" style="gap:40px">%s</div>' . "\n",
                $col_markup
            );
            break;

        case 'banner':
            // WP Cover Block (Hero/CTA)
            $content = implode( '', $generated_slots ); // Usually just 1 slot for banner
            $inner_html = sprintf(
                '<div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim"></span><img class="wp-block-cover__image-background" src="" alt=""/><div class="wp-block-cover__inner-container">' . "\n" . '%s' . "\n" . '</div></div>' . "\n",
                $content
            );
            $align = 'full'; // Force full for banners
            $bg = 'transparent'; // Reset outer bg since cover handles it
            break;

        case 'media-text':
            // WP Media & Text Block
            // We assume Slot 0 is text, Image is auto-handled by block
            $content = implode( '', $generated_slots );
            $inner_html = sprintf(
                '<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="" alt=""/></figure><div class="wp-block-media-text__content">' . "\n" . '%s' . "\n" . '</div></div>' . "\n",
                $content
            );
            $align = 'full';
            break;

        case 'stack':
        default:
            // Standard Group (Vertical Stack)
            $inner_html = implode( "\n\n", $generated_slots );
            break;
    }

    // 4. Return the Final Section Wrapper
    return sprintf(
        '<div class="wp-block-group align%s %s %s" style="background-color:%s;color:%s;padding-top:80px;padding-bottom:80px">' . "\n" . '%s' . "\n" . '</div>' . "\n",
        $align, $semantic_class, $text_class, $bg, $text,
        $inner_html
    );
}

/**
 * Helper: Renders the internals of a single slot (Heading, Text, Button)
 */
function snn_render_slot_content( $slot, $layout_mode, $text_color ) {
    $html = '';
    
    // Image (If grid/stack and requested)
    if ( ! empty( $slot['image_url'] ) || ( isset( $slot['has_image'] ) && $slot['has_image'] ) ) {
        $html .= '<figure class="wp-block-image size-large"><img src="" alt=""/></figure>';
    }

    // Heading
    if ( ! empty( $slot['heading'] ) ) {
        $align = ( $layout_mode === 'banner' || $layout_mode === 'stack' ) ? 'has-text-align-center' : '';
        $font_size = ( $layout_mode === 'banner' ) ? '64px' : '32px';
        
        $html .= sprintf(
            '<h2 class="wp-block-heading %s" style="font-size:%s">%s</h2>',
            $align,
            $font_size,
            esc_html( $slot['heading'] )
        );
    }

    // Subheading / Text
    if ( ! empty( $slot['text'] ) ) {
        $align = ( $layout_mode === 'banner' || $layout_mode === 'stack' ) ? 'has-text-align-center' : '';
        $html .= sprintf(
            '<p class="%s">%s</p>',
            $align,
            esc_html( $slot['text'] )
        );
    }

    // Button
    if ( ! empty( $slot['button_text'] ) ) {
        $justify = ( $layout_mode === 'banner' || $layout_mode === 'stack' ) ? 'center' : 'left';
        $html .= sprintf(
            '<div class="wp-block-buttons" style="justify-content:%s"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">%s</a></div></div>',
            $justify,
            esc_html( $slot['button_text'] )
        );
    }

    return $html;
}

/**
 * Count blocks in pattern markup
 */
function snn_count_blocks_in_pattern( $markup ) {
    preg_match_all( '/<!-- wp:([a-z-\/]+)/', $markup, $matches );
    return count( $matches[0] );
}
