<?php
/**
 * Generate Bricks Builder Content Ability - CREATIVE MODE
 *
 * ============================================================================
 * THE TRANSLATOR PATTERN: Unlimited Creative Freedom
 * ============================================================================
 * 
 * This ability uses a revolutionary "Translator Pattern" that enables the AI agent
 * to have UNLIMITED CREATIVE FREEDOM when generating Bricks Builder content.
 * 
 * === HOW IT WORKS ===
 * 
 * OLD WAY (Template System):
 * - Agent picks from predefined templates: hero_style_1, hero_style_2, etc.
 * - Rigid structure, limited creativity
 * - Can't deviate from templates
 * 
 * NEW WAY (Translator Pattern):
 * - Agent designs layouts freely using simple, readable JSON
 * - Describes structure: "container with 2 columns, left has heading + text, right has image"
 * - PHP "translates" this simple blueprint into complex Bricks format
 * - Agent controls EVERYTHING: hierarchy, content, styling, nesting
 * 
 * === THE THREE COMPONENTS ===
 * 
 * 1. SIMPLE INPUT (Agent writes this):
 *    {
 *      "type": "section",
 *      "styles": {"background": "#000", "padding": "80px"},
 *      "children": [
 *        {"type": "heading", "content": "Hello World", "styles": {"color": "#fff"}}
 *      ]
 *    }
 * 
 * 2. RECURSIVE BUILDER (snn_recursive_builder):
 *    - Walks through the structure tree
 *    - Generates unique IDs automatically
 *    - Handles parent-child relationships
 *    - Recursively processes all nested children
 * 
 * 3. STYLE MAPPER (snn_map_styles_to_bricks):
 *    - Translates simple keys ("color", "gap") to Bricks format ("_typography", "_gridGap")
 *    - Handles responsive properties (fontSize:mobile_landscape)
 *    - Manages hover states (backgroundHover)
 *    - Prevents errors by formatting everything correctly
 * 
 * === WHY THIS IS POWERFUL ===
 * 
 * ✓ Agent can create ANY structure imaginable (3 columns? 5 columns? Nested grids? Yes!)
 * ✓ Agent controls exact content (no more hardcoded "Lorem Ipsum")
 * ✓ Agent decides hierarchy (which elements are children of which)
 * ✓ Simple, readable syntax (no complex Bricks knowledge needed)
 * ✓ Safe - can't break Bricks format (translator handles all complexity)
 * ✓ Responsive - easy to add mobile/tablet variations
 * ✓ Interactive - supports hover states and custom code
 * 
 * === EXAMPLE USE CASES ===
 * 
 * "Create a dark hero with the heading on the left and image on the right"
 * "Make a 3-column service grid with icons and hover effects"
 * "Build a testimonial slider with centered text and gradient background"
 * "Design a sticky sidebar layout with scrolling content on the right"
 * 
 * The agent can now handle ALL of these with creative freedom, designing the
 * exact structure, content, and styling that fits the user's request.
 * 
 * ============================================================================
 */

// Register ability
add_action( 'wp_abilities_api_init', 'snn_register_generate_bricks_content_ability' );
function snn_register_generate_bricks_content_ability() {
    wp_register_ability(
        'snn/generate-bricks-content',
        array(
            'label'       => __( 'Generate Bricks Content (Creative Mode)', 'snn' ),
            'description' => __( 'Generates Bricks Builder content with UNLIMITED CREATIVE FREEDOM. You define the structure, hierarchy, content, and styling - the system translates it into proper Bricks format.

⚠️ CRITICAL: FOR FULL PAGES, CALL THIS ABILITY MULTIPLE TIMES!
When user asks for a "homepage" or "full page", you MUST create multiple sections separately:
- First call: Hero section
- Second call: Features/Services section
- Third call: Testimonials/About section
- Fourth call: CTA/Footer section
DO NOT try to cram everything into one giant structure. Each section should be its own separate call.

🎨 CREATIVE MODE: Define ANY layout structure you can imagine!

HOW IT WORKS:
Instead of choosing from templates, you define a hierarchical structure object. Think of it like describing a visual design in JSON:
- Define containers, sections, blocks, grids
- Nest children freely to create any hierarchy
- Add content (headings, text, images, buttons)
- Apply styles using simple, readable properties
- The system handles all complex Bricks formatting automatically

AVAILABLE ELEMENT TYPES:

Layout & Structure:
- section: Full-width page section
- container: Constrained content container (max-width)
- block: Flexbox container (div with display:flex defaults)
- div: Clean div element (no default flexbox, better for raw layouts and precise control)
⚠️ IMPORTANT: Use 'div' when you need a simple wrapper without flex defaults. Use 'block' when you want flexbox behavior.

Content & Typography:
- heading: Text heading (h1-h6)
- text-basic: Simple paragraph text
- text: Rich text with HTML formatting
- text-link: Text element with link

Media & Visuals:
- image: Image element
- icon: Icon from icon libraries (FontAwesome, Ionicons, etc.)
- svg: SVG file element

Interactive Elements:
- button: Interactive button with link
- form: Form with customizable fields
- social-icons: Social media icon row

Nestable/Complex Elements:
- slider-nested: Slider/carousel container (children = slides)
- accordion-nested: Accordion container (children = accordion items)
- tabs-nested: Tabs container (children = tab menu + tab content)

Special Elements:
- counter: Animated number counter
- countdown: Countdown timer
- list: Styled list with items
- shortcode: WordPress shortcode element
- custom-html-css-script: Custom code element for advanced interactions

STYLE PROPERTIES (Simple & Readable):

⚠️ VALUE FORMATTING RULES:
- Numeric values: Use plain numbers WITHOUT units: "80" NOT "80px"
- Percentages: Include % sign: "50%"
- Auto: Use string "auto" for margin auto
- Colors: Always use hex format: "#ffffff" NOT "white"
- URLs: Full https:// URLs for images

LAYOUT:
- display: "flex" | "grid" | "block"
- flexDirection: "row" | "column"
- justifyContent: "center" | "flex-start" | "flex-end" | "space-between"
- alignItems: "center" | "flex-start" | "flex-end" | "stretch"
- gridTemplateColumns: "1fr 1fr" | "repeat(3, 1fr)" | "300px 1fr"
- gap: "20" | "40" (NO px suffix - just the number)

SPACING:
- padding: "80" (all sides, NO px) or {top: "80", right: "40", bottom: "80", left: "40"}
- margin: "auto" | "20" or {top: "0", bottom: "40"}

🎨 DESIGN QUALITY RULES:
1. ALWAYS add proper gap between elements (minimum "24" for vertical, "30" for grid)
2. Use consistent padding (sections: "80"-"100", cards: "40", buttons: "15"-"20")
3. Ensure sufficient contrast (dark bg needs light text, light bg needs dark text)
4. Add responsive breakpoints for mobile (gridTemplateColumns:mobile_landscape)
5. Use proper typography hierarchy (h1: 56-72px, h2: 42-56px, h3: 28-36px, body: 16-18px)
6. Add hover effects to interactive elements (buttons, cards)
7. Use border radius for modern look (buttons: "6"-"8", cards: "12"-"20", images: "16"-"20")

SIZING:
- width: "100%" | "500px" | "50vw"
- height: "100vh" | "400px"
- minHeight: "600px"
- maxWidth: "1200px"
- aspectRatio: "16/9" | "1/1"

COLORS:
- background: "#121212" | "#ffffff"
- color: "#ffffff" | "#000000"
- accentColor: "#00ff00" (for buttons, highlights)

TYPOGRAPHY:
- fontSize: "60px" | "18px"
- fontWeight: "700" | "900"
- lineHeight: "1.2" | "1.7"
- letterSpacing: "-2px" | "3px"
- textAlign: "center" | "left" | "right"

VISUAL EFFECTS:
- borderRadius: "20px" | "50%"
- opacity: "0.8"
- boxShadow: "0 10px 30px rgba(0,0,0,0.1)"

POSITIONING:
- position: "sticky" | "relative" | "absolute"
- top: "100px"
- zIndex: "10"

RESPONSIVE:
- Add ":mobile_landscape" or ":tablet" suffix to any property for responsive values
- Example: fontSize:mobile_landscape: "32px"

ADVANCED: You can also use native Bricks properties (prefixed with _):
- _background, _typography, _padding, _border, etc.
- Use these for fine-grained control when simple properties aren\'t enough

CONTENT PROPERTY:
For text/image elements, use "content" to define what they display:
- heading: content: "Your Heading Text Here"
- text-basic: content: "Your paragraph text"
- button: content: "Click Me", link: "#contact"
- image: content: "https://example.com/image.jpg"

EXAMPLE 1 - Simple Hero:
{
  "structure": {
    "type": "section",
    "styles": {"background": "#000000", "minHeight": "100vh", "padding": "80px"},
    "children": [{
      "type": "container",
      "styles": {"display": "flex", "flexDirection": "column", "alignItems": "center", "gap": "30px"},
      "children": [
        {"type": "heading", "content": "Future of Design", "styles": {"fontSize": "72px", "color": "#ffffff"}},
        {"type": "text-basic", "content": "Where creativity meets technology", "styles": {"fontSize": "20px", "color": "#cccccc"}},
        {"type": "button", "content": "Get Started", "link": "#", "styles": {"background": "#00ff00", "color": "#000000"}}
      ]
    }]
  }
}

EXAMPLE 2 - Complex Grid Layout:
{
  "structure": {
    "type": "section",
    "styles": {"background": "#ffffff", "padding": "100px"},
    "children": [{
      "type": "container",
      "children": [{
        "type": "block",
        "styles": {"display": "grid", "gridTemplateColumns": "1fr 1fr 1fr", "gap": "40px"},
        "children": [
          {
            "type": "block",
            "styles": {"padding": "40px", "background": "#f5f5f5", "borderRadius": "20px"},
            "children": [
              {"type": "heading", "content": "Service 1", "styles": {"fontSize": "32px"}},
              {"type": "text-basic", "content": "Description of service", "styles": {"fontSize": "16px"}}
            ]
          },
          // Repeat for more cards...
        ]
      }]
    }]
  }
}

EXAMPLE 3 - Icon with Text:
{
  "structure": {
    "type": "section",
    "children": [{
      "type": "container",
      "children": [{
        "type": "block",
        "styles": {"display": "flex", "alignItems": "center", "gap": "20"},
        "children": [
          {"type": "icon", "iconData": {"library": "fontawesome", "icon": "fa fa-star"}, "styles": {"fontSize": "40px", "color": "#FFD700"}},
          {"type": "heading", "content": "Featured Item", "styles": {"fontSize": "24px"}}
        ]
      }]
    }]
  }
}

EXAMPLE 4 - Counter:
{
  "structure": {
    "type": "section",
    "children": [{
      "type": "container",
      "children": [
        {"type": "counter", "countTo": "1000", "styles": {"fontSize": "72px", "fontWeight": "900", "color": "#000000"}}
      ]
    }]
  }
}

EXAMPLE 5 - Countdown Timer:
{
  "structure": {
    "type": "section",
    "children": [{
      "type": "container",
      "children": [
        {
          "type": "countdown", 
          "date": "2026-12-31 23:59",
          "fields": [
            {"format": "%D days"},
            {"format": "%H hours"},
            {"format": "%M minutes"},
            {"format": "%S seconds"}
          ],
          "styles": {"fontSize": "48px", "textAlign": "center"}
        }
      ]
    }]
  }
}

EXAMPLE 6 - Social Icons:
{
  "structure": {
    "type": "section",
    "children": [{
      "type": "container",
      "children": [{
        "type": "social-icons",
        "icons": [
          {"label": "Facebook", "icon": {"library": "fontawesomeBrands", "icon": "fab fa-facebook"}, "background": {"hex": "#3b5998"}, "id": "fb001"},
          {"label": "Twitter", "icon": {"library": "fontawesomeBrands", "icon": "fab fa-twitter"}, "background": {"hex": "#1DA1F2"}, "id": "tw001"}
        ],
        "direction": "row",
        "styles": {"gap": "16", "justifyContent": "center"}
      }]
    }]
  }
}

EXAMPLE 7 - List:
{
  "structure": {
    "type": "section",
    "children": [{
      "type": "container",
      "children": [{
        "type": "list",
        "items": [
          {"title": "Feature One", "meta": "Available", "id": "item1"},
          {"title": "Feature Two", "meta": "Coming Soon", "id": "item2"}
        ],
        "styles": {"fontSize": "18px", "gap": "16"}
      }]
    }]
  }
}

EXAMPLE 8 - Form:
{
  "structure": {
    "type": "section",
    "children": [{
      "type": "container",
      "children": [{
        "type": "form",
        "actions": ["email"],
        "emailTo": "admin@example.com",
        "emailSubject": "Contact Form Submission",
        "successMessage": "Thank you for your message!",
        "fields": [
          {"type": "text", "label": "Name", "placeholder": "Your Name", "id": "name001"},
          {"type": "email", "label": "Email", "placeholder": "your@email.com", "id": "email001"},
          {"type": "textarea", "label": "Message", "placeholder": "Your message", "id": "msg001"}
        ]
      }]
    }]
  }
}

ADVANCED USAGE TIPS:

🎨 USING GLOBAL VARIABLES & COLOR PALETTE:
Instead of hardcoded values, you can reference theme variables:
- Font sizes: Use "var(--brxw-text-xl)" or "var(--size-48)" for responsive fluid typography
- Colors: Use "var(--c1)" or "var(--c2)" for theme colors (these map to the site\'s color palette)
- Spacing: Use "var(--brxw-space-m)" for consistent spacing

Example with variables:
{
  "structure": {
    "type": "section",
    "styles": {"background": "var(--c1)", "padding": "var(--brxw-space-3xl)"},
    "children": [{
      "type": "heading",
      "content": "Heading",
      "styles": {"fontSize": "var(--brxw-text-5xl)", "color": "var(--c3)"}
    }]
  }
}

💡 GLOBAL VARIABLES AVAILABLE:
Access via bricksState.globalVariables:
- Text sizes: brxw-text-xs, brxw-text-s, brxw-text-m, brxw-text-l, brxw-text-xl, brxw-text-2xl, brxw-text-3xl, brxw-text-4xl, brxw-text-5xl
- Space sizes: brxw-space-3xs, brxw-space-2xs, brxw-space-xs, brxw-space-s, brxw-space-m, brxw-space-l, brxw-space-xl, brxw-space-2xl, brxw-space-3xl
- Custom sizes: size-18, size-24, size-32, size-48, size-64, size-82, size-96 (if defined)

🎨 COLOR PALETTE:
Access via bricksState.colorPalette:
- Prefer: "var(--c1)", "var(--c2)", etc. for theme colors
- Fallback: Static hex colors like "#ffffff" if variables aren\'t defined
- Color palette IDs from user\'s theme should be used when available

KEY ADVANTAGES:
✅ Unlimited creativity - no template restrictions
✅ Define any hierarchy and nesting
✅ Control every aspect of layout and styling
✅ Simple, readable property names
✅ Automatic Bricks format translation
✅ Safe - can\'t break Bricks JSON syntax
✅ 20+ element types including sliders, accordions, forms, icons

🏗️ NESTABLE ELEMENTS (Advanced Structures):

SLIDER (slider-nested):
Create carousels and sliders. Direct children become slides.
{
  "type": "slider-nested",
  "styles": {"height": "500px", "perPage": "3", "gap": "20", "pagination": true, "arrows": true},
  "children": [
    {"type": "block", "label": "Slide 1", "children": [{"type": "heading", "content": "Slide 1"}]},
    {"type": "block", "label": "Slide 2", "children": [{"type": "heading", "content": "Slide 2"}]}
  ]
}

ACCORDION (accordion-nested):
Create expandable accordion sections. Structure: accordion > items > (title block + content block)
{
  "type": "accordion-nested",
  "styles": {"titleHeight": "50px", "contentPadding": {"top": "15", "right": "0", "bottom": "15", "left": "0"}},
  "children": [
    {
      "type": "block",
      "label": "Item",
      "children": [
        {
          "type": "block",
          "label": "Title",
          "styles": {"_hidden": {"_cssClasses": "accordion-title-wrapper"}},
          "children": [
            {"type": "heading", "content": "Accordion Title", "tag": "h3"},
            {"type": "icon", "iconData": {"library": "ionicons", "icon": "ion-ios-arrow-forward"}, "styles": {"isAccordionIcon": true}}
          ]
        },
        {
          "type": "block",
          "label": "Content",
          "styles": {"_hidden": {"_cssClasses": "accordion-content-wrapper"}},
          "children": [
            {"type": "text", "content": "<p>Accordion content goes here...</p>"}
          ]
        }
      ]
    }
  ]
}

TABS (tabs-nested):
Create tabbed content. Structure: tabs > (tab-menu block + tab-content block)
{
  "type": "tabs-nested",
  "styles": {"titlePadding": {"top": "20", "right": "20", "bottom": "20", "left": "20"}},
  "children": [
    {
      "type": "block",
      "label": "Tab menu",
      "styles": {"_direction": "row", "_hidden": {"_cssClasses": "tab-menu"}},
      "children": [
        {"type": "div", "styles": {"_hidden": {"_cssClasses": "tab-title"}}, "children": [{"type": "text-basic", "content": "Tab 1"}]},
        {"type": "div", "styles": {"_hidden": {"_cssClasses": "tab-title"}}, "children": [{"type": "text-basic", "content": "Tab 2"}]}
      ]
    },
    {
      "type": "block",
      "label": "Tab content",
      "styles": {"_hidden": {"_cssClasses": "tab-content"}},
      "children": [
        {"type": "block", "styles": {"_hidden": {"_cssClasses": "tab-pane"}}, "children": [{"type": "text", "content": "Content 1"}]},
        {"type": "block", "styles": {"_hidden": {"_cssClasses": "tab-pane"}}, "children": [{"type": "text", "content": "Content 2"}]}
      ]
    }
  ]
}

USAGE:
Think of the user\'s request, design the structure visually in your mind, then describe it in JSON format.', 'snn' ),
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'structure' ),
                'properties' => array(
                    'structure' => array(
                        'type'        => 'object',
                        'description' => 'The hierarchical structure definition. Must contain type, and can contain styles, content, children, and link properties.',
                        'required'    => array( 'type' ),
                        'properties'  => array(
                            'type' => array(
                                'type'        => 'string',
                                'enum'        => array( 
                                    'section', 'container', 'block', 'div', 
                                    'heading', 'text-basic', 'text', 'text-link',
                                    'button', 'image', 'icon', 'svg',
                                    'slider-nested', 'accordion-nested', 'tabs-nested',
                                    'form', 'counter', 'countdown', 'list', 'shortcode', 'social-icons',
                                    'custom-html-css-script' 
                                ),
                                'description' => 'Element type to create',
                            ),
                            'content' => array(
                                'type'        => 'string',
                                'description' => 'Content for text elements (heading text, paragraph text, button text, image URL)',
                            ),
                            'link' => array(
                                'type'        => 'string',
                                'description' => 'Link URL for buttons',
                            ),
                            'tag' => array(
                                'type'        => 'string',
                                'description' => 'HTML tag for headings (h1-h6)',
                            ),
                            'styles' => array(
                                'type'        => 'object',
                                'description' => 'Styling properties (simple or native Bricks format)',
                            ),
                            'children' => array(
                                'type'        => 'array',
                                'description' => 'Child elements (recursive structure)',
                            ),
                        ),
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
                $structure = $input['structure'];
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

                // Use the new creative builder system
                $result = snn_recursive_builder( $structure );
                $content_json = array( 'content' => $result['elements'] );
                $element_count = count( $result['elements'] );

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

                $structure_type = $structure['type'] ?? 'custom';
                return array(
                    'success'                   => true,
                    'message'                   => sprintf( __( 'Generated creative %s structure (%d elements). Ready to insert.', 'snn' ), $structure_type, $element_count ),
                    'content_json'              => $content_json,
                    'content_info'              => array( 'type' => $structure_type, 'element_count' => $element_count, 'root_id' => $result['root_id'] ),
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
 * ==============================================================================
 * THE RECURSIVE BUILDER ENGINE (The Heart of Creative Mode)
 * ==============================================================================
 * 
 * This function takes the Agent's simplified "blueprint" structure and converts it
 * into the strict Bricks Array Format. It handles all the complexity of IDs, parent-child
 * relationships, and Bricks-specific formatting.
 * 
 * @param array $node The structure node from the agent (contains type, content, styles, children)
 * @param int|string $parent_id The parent element ID (0 for root elements)
 * @return array Array with 'root_id' (the ID of this element) and 'elements' (flat array of all elements)
 */
function snn_recursive_builder( $node, $parent_id = 0 ) {
    // Generate unique ID for this element
    $element_id = snn_generate_element_id();
    
    // Extract node properties with defaults
    $type = $node['type'] ?? 'block';
    $content = $node['content'] ?? '';
    $link = $node['link'] ?? '';
    $tag = $node['tag'] ?? '';
    $label = $node['label'] ?? ''; // Optional label for element
    $styles = $node['styles'] ?? array();
    $children_nodes = $node['children'] ?? array();
    
    // 1. TRANSLATE SIMPLE STYLES TO BRICKS FORMAT
    // This is where the magic happens - convert readable keys to Bricks keys
    $bricks_settings = snn_map_styles_to_bricks( $styles, $type );
    
    // Add label if provided (helps with organization in Bricks builder)
    if ( $label ) {
        $bricks_settings['label'] = $label;
    }
    
    // Handle any additional special properties from node
    // These are passed directly to settings if they don't conflict
    $special_properties = array(
        'iconData', 'fileData', 'countTo', 'date', 'fields', 'items', 'icons', 
        'direction', 'actions', 'emailTo', 'emailSubject', 'successMessage', 
        'emailErrorMessage', 'fromName', 'htmlEmail',
        // Slider properties
        'pagination', 'arrows', 'perPage', 'autoplay', 'loop',
        // Accordion properties  
        'titleHeight', 'contentPadding', 'titleBackgroundColor', 'titleBorder', 'contentBorder',
        // Tabs properties
        'titlePadding', 'titleActiveBackgroundColor', 'contentBorder',
        // Form properties
        'submitButtonStyle', 'submitButtonText',
        // Other nestable properties
        'isAccordionIcon'
    );
    
    foreach ( $special_properties as $prop ) {
        if ( isset( $node[ $prop ] ) && ! isset( $bricks_settings[ $prop ] ) ) {
            $bricks_settings[ $prop ] = $node[ $prop ];
        }
    }
    
    // 2. HANDLE CONTENT BASED ON ELEMENT TYPE
    switch ( $type ) {
        case 'heading':
            $bricks_settings['text'] = $content;
            if ( $tag ) {
                $bricks_settings['tag'] = $tag;
            } elseif ( ! isset( $bricks_settings['tag'] ) ) {
                $bricks_settings['tag'] = 'h2'; // Default heading tag
            }
            break;
            
        case 'text-basic':
            $bricks_settings['text'] = $content;
            break;
            
        case 'text':
            // Rich text with HTML
            $bricks_settings['text'] = $content;
            break;
            
        case 'text-link':
            // Text with link
            $bricks_settings['text'] = $content;
            if ( $link ) {
                $bricks_settings['link'] = array(
                    'url' => $link,
                    'type' => 'external'
                );
            }
            break;
            
        case 'button':
            $bricks_settings['text'] = $content;
            if ( $link ) {
                $bricks_settings['link'] = array(
                    'url' => $link,
                    'type' => 'external'
                );
            }
            break;
            
        case 'image':
            if ( $content ) {
                $bricks_settings['image'] = array(
                    'url' => $content,
                    'size' => 'full'
                );
            }
            break;
            
        case 'icon':
            // Icon element - expects icon data in styles or as iconData property
            // Format: {"library": "fontawesome", "icon": "fa fa-star"}
            if ( isset( $node['iconData'] ) ) {
                $bricks_settings['icon'] = $node['iconData'];
            } elseif ( $content ) {
                // If content is provided as JSON string, parse it
                $icon_data = json_decode( $content, true );
                if ( $icon_data ) {
                    $bricks_settings['icon'] = $icon_data;
                } else {
                    // Fallback: assume simple icon class
                    $bricks_settings['icon'] = array(
                        'library' => 'fontawesome',
                        'icon' => $content
                    );
                }
            }
            break;
            
        case 'svg':
            // SVG file element - expects file data
            if ( isset( $node['fileData'] ) ) {
                $bricks_settings['file'] = $node['fileData'];
            } elseif ( $content ) {
                // Content is URL
                $bricks_settings['file'] = array(
                    'url' => $content
                );
            }
            break;
            
        case 'counter':
            // Animated counter
            if ( $content ) {
                $bricks_settings['countTo'] = $content;
            } elseif ( isset( $node['countTo'] ) ) {
                $bricks_settings['countTo'] = $node['countTo'];
            }
            break;
            
        case 'countdown':
            // Countdown timer
            if ( $content ) {
                $bricks_settings['date'] = $content;
            } elseif ( isset( $node['date'] ) ) {
                $bricks_settings['date'] = $node['date'];
            }
            // Fields for countdown display
            if ( isset( $node['fields'] ) ) {
                $bricks_settings['fields'] = $node['fields'];
            } else {
                // Default fields
                $bricks_settings['fields'] = array(
                    array( 'format' => '%D days' ),
                    array( 'format' => '%H hours' ),
                    array( 'format' => '%M minutes' ),
                    array( 'format' => '%S seconds' )
                );
            }
            break;
            
        case 'shortcode':
            // WordPress shortcode
            if ( $content ) {
                $bricks_settings['shortcode'] = $content;
            }
            break;
            
        case 'list':
            // List element with items
            if ( isset( $node['items'] ) ) {
                $bricks_settings['items'] = $node['items'];
            }
            break;
            
        case 'social-icons':
            // Social media icons
            if ( isset( $node['icons'] ) ) {
                $bricks_settings['icons'] = $node['icons'];
            }
            // Direction
            if ( isset( $node['direction'] ) ) {
                $bricks_settings['direction'] = $node['direction'];
            }
            break;
            
        case 'form':
            // Form element
            if ( isset( $node['fields'] ) ) {
                $bricks_settings['fields'] = $node['fields'];
            }
            // Form actions
            if ( isset( $node['actions'] ) ) {
                $bricks_settings['actions'] = $node['actions'];
            }
            // Email settings
            if ( isset( $node['emailTo'] ) ) {
                $bricks_settings['emailTo'] = $node['emailTo'];
            }
            if ( isset( $node['emailSubject'] ) ) {
                $bricks_settings['emailSubject'] = $node['emailSubject'];
            }
            // Success/Error messages
            if ( isset( $node['successMessage'] ) ) {
                $bricks_settings['successMessage'] = $node['successMessage'];
            }
            if ( isset( $node['emailErrorMessage'] ) ) {
                $bricks_settings['emailErrorMessage'] = $node['emailErrorMessage'];
            }
            break;
            
        case 'slider-nested':
        case 'accordion-nested':
        case 'tabs-nested':
            // Nestable elements - children define the structure
            // Additional settings can be passed through styles
            break;
            
        case 'custom-html-css-script':
            // Content should contain the HTML/CSS/JS
            $bricks_settings['content'] = $content;
            break;
    }
    
    // 3. RECURSIVELY PROCESS CHILDREN
    $children_ids = array();
    $generated_children_elements = array();
    
    foreach ( $children_nodes as $child ) {
        // Recursively build each child, passing this element as parent
        $child_result = snn_recursive_builder( $child, $element_id );
        
        // Collect child ID for this element's children array
        $children_ids[] = $child_result['root_id'];
        
        // Merge all child elements into our flat elements array
        $generated_children_elements = array_merge( 
            $generated_children_elements, 
            $child_result['elements'] 
        );
    }
    
    // 4. CREATE THE CURRENT ELEMENT IN BRICKS FORMAT
    $current_element = array(
        'id'       => $element_id,
        'name'     => $type,
        'parent'   => $parent_id,
        'children' => $children_ids,
        'settings' => $bricks_settings
    );
    
    // 5. RETURN THE TREE
    // We return both this element's ID (so parent knows about us)
    // And a flat list of ALL elements (this one + all descendants)
    return array(
        'root_id'  => $element_id,
        'elements' => array_merge( array( $current_element ), $generated_children_elements )
    );
}

/**
 * ==============================================================================
 * THE STYLE MAPPER (Translator from Simple to Bricks Format)
 * ==============================================================================
 * 
 * Allows the agent to use simple, readable keys like "color" or "gap" without
 * knowing the complex Bricks syntax. This function translates them.
 * 
 * @param array $simple_styles The simple style properties from the agent
 * @param string $element_type The type of element being styled (for context-aware mapping)
 * @return array Bricks-formatted settings array
 */
function snn_map_styles_to_bricks( $simple_styles, $element_type = '' ) {
    $settings = array();
    
    // DIRECT PASS-THROUGH: If agent already knows Bricks syntax (keys starting with _)
    foreach ( $simple_styles as $key => $value ) {
        if ( strpos( $key, '_' ) === 0 ) {
            // This is already a Bricks property, use it directly
            $settings[ $key ] = $value;
        }
    }
    
    // SMART MAPPING: Translate simple keys to Bricks format
    
    // === LAYOUT PROPERTIES ===
    if ( isset( $simple_styles['display'] ) ) {
        $settings['_display'] = $simple_styles['display'];
    }
    if ( isset( $simple_styles['flexDirection'] ) ) {
        $settings['_flexDirection'] = $simple_styles['flexDirection'];
    }
    if ( isset( $simple_styles['justifyContent'] ) ) {
        $settings['_justifyContent'] = $simple_styles['justifyContent'];
    }
    if ( isset( $simple_styles['alignItems'] ) ) {
        $settings['_alignItems'] = $simple_styles['alignItems'];
    }
    if ( isset( $simple_styles['flexWrap'] ) ) {
        $settings['_flexWrap'] = $simple_styles['flexWrap'];
    }
    if ( isset( $simple_styles['gridTemplateColumns'] ) ) {
        $settings['_gridTemplateColumns'] = $simple_styles['gridTemplateColumns'];
    }
    if ( isset( $simple_styles['gridTemplateRows'] ) ) {
        $settings['_gridTemplateRows'] = $simple_styles['gridTemplateRows'];
    }
    
    // === GAP PROPERTIES ===
    if ( isset( $simple_styles['gap'] ) ) {
        $gap_value = snn_sanitize_bricks_value( $simple_styles['gap'], 'gap' );
        $settings['_columnGap'] = $gap_value;
        $settings['_rowGap'] = $gap_value;
        $settings['_gridGap'] = $gap_value;
    }
    if ( isset( $simple_styles['columnGap'] ) ) {
        $settings['_columnGap'] = snn_sanitize_bricks_value( $simple_styles['columnGap'], 'columnGap' );
    }
    if ( isset( $simple_styles['rowGap'] ) ) {
        $settings['_rowGap'] = snn_sanitize_bricks_value( $simple_styles['rowGap'], 'rowGap' );
    }
    
    // === SPACING PROPERTIES ===
    if ( isset( $simple_styles['padding'] ) ) {
        $padding = $simple_styles['padding'];
        if ( is_string( $padding ) ) {
            $p = snn_sanitize_bricks_value( $padding, 'padding' );
            $settings['_padding'] = array(
                'top'    => $p,
                'right'  => $p,
                'bottom' => $p,
                'left'   => $p
            );
        } elseif ( is_array( $padding ) ) {
            // Sanitize each value in the array
            $settings['_padding'] = array(
                'top'    => snn_sanitize_bricks_value( $padding['top'] ?? '0', 'padding.top' ),
                'right'  => snn_sanitize_bricks_value( $padding['right'] ?? '0', 'padding.right' ),
                'bottom' => snn_sanitize_bricks_value( $padding['bottom'] ?? '0', 'padding.bottom' ),
                'left'   => snn_sanitize_bricks_value( $padding['left'] ?? '0', 'padding.left' )
            );
        }
    }
    if ( isset( $simple_styles['margin'] ) ) {
        $margin = $simple_styles['margin'];
        if ( is_string( $margin ) ) {
            if ( $margin === 'auto' ) {
                $settings['_margin'] = array(
                    'left'  => 'auto',
                    'right' => 'auto'
                );
            } else {
                $m = snn_sanitize_bricks_value( $margin, 'margin' );
                $settings['_margin'] = array(
                    'top'    => $m,
                    'right'  => $m,
                    'bottom' => $m,
                    'left'   => $m
                );
            }
        } elseif ( is_array( $margin ) ) {
            // Sanitize each value in the array
            $settings['_margin'] = array(
                'top'    => snn_sanitize_bricks_value( $margin['top'] ?? '0', 'margin.top' ),
                'right'  => snn_sanitize_bricks_value( $margin['right'] ?? '0', 'margin.right' ),
                'bottom' => snn_sanitize_bricks_value( $margin['bottom'] ?? '0', 'margin.bottom' ),
                'left'   => snn_sanitize_bricks_value( $margin['left'] ?? '0', 'margin.left' )
            );
        }
    }
    
    // === SIZING PROPERTIES ===
    if ( isset( $simple_styles['width'] ) ) {
        $settings['_width'] = $simple_styles['width'];
    }
    if ( isset( $simple_styles['height'] ) ) {
        $settings['_height'] = $simple_styles['height'];
    }
    if ( isset( $simple_styles['minWidth'] ) ) {
        $settings['_widthMin'] = $simple_styles['minWidth'];
    }
    if ( isset( $simple_styles['minHeight'] ) ) {
        $settings['_minHeight'] = $simple_styles['minHeight'];
    }
    if ( isset( $simple_styles['maxWidth'] ) ) {
        $settings['_maxWidth'] = $simple_styles['maxWidth'];
    }
    if ( isset( $simple_styles['maxHeight'] ) ) {
        $settings['_maxHeight'] = $simple_styles['maxHeight'];
    }
    if ( isset( $simple_styles['aspectRatio'] ) ) {
        $settings['_aspectRatio'] = $simple_styles['aspectRatio'];
    }
    
    // === COLOR PROPERTIES ===
    if ( isset( $simple_styles['background'] ) ) {
        $settings['_background'] = array(
            'color' => array( 'hex' => $simple_styles['background'] )
        );
    }
    if ( isset( $simple_styles['backgroundColor'] ) ) {
        $settings['_background'] = array(
            'color' => array( 'hex' => $simple_styles['backgroundColor'] )
        );
    }
    
    // Typography color - needs to go in _typography array
    $typography = array();
    if ( isset( $simple_styles['color'] ) ) {
        $typography['color'] = array( 'hex' => $simple_styles['color'] );
    }
    if ( isset( $simple_styles['fontSize'] ) ) {
        $typography['font-size'] = $simple_styles['fontSize'];
    }
    if ( isset( $simple_styles['fontWeight'] ) ) {
        $typography['font-weight'] = $simple_styles['fontWeight'];
    }
    if ( isset( $simple_styles['lineHeight'] ) ) {
        $typography['line-height'] = $simple_styles['lineHeight'];
    }
    if ( isset( $simple_styles['letterSpacing'] ) ) {
        $typography['letter-spacing'] = $simple_styles['letterSpacing'];
    }
    if ( isset( $simple_styles['textAlign'] ) ) {
        $typography['text-align'] = $simple_styles['textAlign'];
    }
    if ( isset( $simple_styles['fontFamily'] ) ) {
        $typography['font-family'] = $simple_styles['fontFamily'];
    }
    if ( isset( $simple_styles['textTransform'] ) ) {
        $typography['text-transform'] = $simple_styles['textTransform'];
    }
    
    // Merge typography if we have any typography properties
    if ( ! empty( $typography ) ) {
        // If _typography already exists from direct pass-through, merge with it
        if ( isset( $settings['_typography'] ) ) {
            $settings['_typography'] = array_merge( $settings['_typography'], $typography );
        } else {
            $settings['_typography'] = $typography;
        }
    }
    
    // === VISUAL EFFECTS ===
    if ( isset( $simple_styles['borderRadius'] ) ) {
        $radius = snn_sanitize_bricks_value( $simple_styles['borderRadius'], 'borderRadius' );
        $settings['_border'] = array(
            'radius' => array(
                'top'    => $radius,
                'right'  => $radius,
                'bottom' => $radius,
                'left'   => $radius
            )
        );
    }
    if ( isset( $simple_styles['opacity'] ) ) {
        $settings['_opacity'] = $simple_styles['opacity'];
    }
    if ( isset( $simple_styles['boxShadow'] ) ) {
        $settings['_boxShadow'] = $simple_styles['boxShadow'];
    }
    
    // === POSITIONING ===
    if ( isset( $simple_styles['position'] ) ) {
        $settings['_position'] = $simple_styles['position'];
    }
    if ( isset( $simple_styles['top'] ) ) {
        $settings['_top'] = snn_sanitize_bricks_value( $simple_styles['top'], 'top' );
    }
    if ( isset( $simple_styles['right'] ) ) {
        $settings['_right'] = snn_sanitize_bricks_value( $simple_styles['right'], 'right' );
    }
    if ( isset( $simple_styles['bottom'] ) ) {
        $settings['_bottom'] = snn_sanitize_bricks_value( $simple_styles['bottom'], 'bottom' );
    }
    if ( isset( $simple_styles['left'] ) ) {
        $settings['_left'] = snn_sanitize_bricks_value( $simple_styles['left'], 'left' );
    }
    if ( isset( $simple_styles['zIndex'] ) ) {
        $settings['_zIndex'] = snn_sanitize_bricks_value( $simple_styles['zIndex'], 'zIndex' );
    }
    
    // === MISC ===
    if ( isset( $simple_styles['overflow'] ) ) {
        $settings['_overflow'] = $simple_styles['overflow'];
    }
    if ( isset( $simple_styles['objectFit'] ) ) {
        $settings['_objectFit'] = $simple_styles['objectFit'];
    }
    
    // === RESPONSIVE PROPERTIES ===
    // Handle properties with :mobile_landscape or :tablet suffixes
    foreach ( $simple_styles as $key => $value ) {
        if ( strpos( $key, ':' ) !== false ) {
            // This is a responsive property like "fontSize:mobile_landscape"
            list( $base_key, $breakpoint ) = explode( ':', $key, 2 );
            
            // Map the base key to Bricks format
            $bricks_key = snn_map_single_style_key( $base_key );
            if ( $bricks_key ) {
                $responsive_key = $bricks_key . ':' . $breakpoint;
                
                // Handle typography properties specially
                if ( in_array( $base_key, array( 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing', 'color' ) ) ) {
                    if ( ! isset( $settings['_typography'] ) ) {
                        $settings['_typography'] = array();
                    }
                    $typo_key = str_replace( array( 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing' ), 
                                           array( 'font-size', 'font-weight', 'line-height', 'letter-spacing' ), 
                                           $base_key );
                    $settings['_typography'][ $typo_key . ':' . $breakpoint ] = $value;
                } else {
                    $settings[ $responsive_key ] = $value;
                }
            }
        }
    }
    
    // === HOVER STATES ===
    // Handle properties with :hover suffix (for buttons, etc.)
    foreach ( $simple_styles as $key => $value ) {
        if ( strpos( $key, 'Hover' ) !== false ) {
            // backgroundHover, colorHover, etc.
            $base_key = str_replace( 'Hover', '', $key );
            $bricks_key = snn_map_single_style_key( $base_key );
            if ( $bricks_key ) {
                $settings[ $bricks_key . ':hover' ] = $value;
            }
        }
    }
    
    // Add transition for smooth hover effects if hover states exist
    $has_hover = false;
    foreach ( $settings as $key => $value ) {
        if ( strpos( $key, ':hover' ) !== false ) {
            $has_hover = true;
            break;
        }
    }
    if ( $has_hover && ! isset( $settings['_cssTransition'] ) ) {
        $settings['_cssTransition'] = '0.3s';
    }
    
    return $settings;
}

/**
 * Sanitize a style value to ensure it's properly formatted for Bricks
 * Removes units, cleans up malformed values, ensures proper format
 * 
 * @param mixed $value The value to sanitize
 * @param string $property The property name (for context-aware sanitization)
 * @return mixed The sanitized value
 */
function snn_sanitize_bricks_value( $value, $property = '' ) {
    // Handle arrays (like padding objects) recursively
    if ( is_array( $value ) ) {
        $sanitized = array();
        foreach ( $value as $key => $val ) {
            $sanitized[ $key ] = snn_sanitize_bricks_value( $val, $property . '.' . $key );
        }
        return $sanitized;
    }
    
    // Handle null/empty
    if ( empty( $value ) && $value !== '0' && $value !== 0 ) {
        return $value;
    }
    
    // Convert to string for processing
    $value = (string) $value;
    
    // Don't sanitize certain special values
    $no_sanitize = array( 'auto', 'none', 'inherit', 'initial', 'unset', 'normal' );
    if ( in_array( strtolower( $value ), $no_sanitize ) ) {
        return $value;
    }
    
    // Don't sanitize URLs
    if ( strpos( $value, 'http://' ) === 0 || strpos( $value, 'https://' ) === 0 ) {
        return $value;
    }
    
    // Don't sanitize grid template values or calc() expressions
    if ( strpos( $value, 'fr' ) !== false || 
         strpos( $value, 'repeat' ) !== false || 
         strpos( $value, 'calc' ) !== false ||
         strpos( $value, 'minmax' ) !== false ) {
        return $value;
    }
    
    // Handle percentages - keep the %
    if ( strpos( $value, '%' ) !== false ) {
        return preg_replace( '/[^0-9.%\-]/', '', $value );
    }
    
    // Handle viewport units (vh, vw, vmin, vmax) - these are allowed
    if ( preg_match( '/^-?[0-9.]+\s*(vh|vw|vmin|vmax)$/i', $value ) ) {
        return $value;
    }
    
    // Handle em/rem units - these are allowed
    if ( preg_match( '/^-?[0-9.]+\s*(em|rem)$/i', $value ) ) {
        return $value;
    }
    
    // CRITICAL FIX: Remove malformed spacing like "15px 40" or "100px 20"
    // This should be just the first number
    if ( preg_match( '/^([0-9.]+)(?:px)?\s+([0-9.]+)/', $value, $matches ) ) {
        // Take the first number only
        return $matches[1];
    }
    
    // For numeric values, strip all units (px, pt, etc.) - Bricks uses plain numbers
    if ( preg_match( '/^-?[0-9.]+/', $value, $matches ) ) {
        $number = $matches[0];
        
        // Preserve decimals where appropriate
        if ( strpos( $number, '.' ) !== false ) {
            return rtrim( rtrim( $number, '0' ), '.' );
        }
        
        return $number;
    }
    
    // Return as-is if we can't determine how to sanitize
    return $value;
}

/**
 * Helper function to map a single style key to its Bricks equivalent
 * 
 * @param string $key The simple style key
 * @return string|false The Bricks key, or false if not mappable
 */
function snn_map_single_style_key( $key ) {
    $map = array(
        'width'           => '_width',
        'height'          => '_height',
        'minWidth'        => '_widthMin',
        'minHeight'       => '_minHeight',
        'maxWidth'        => '_maxWidth',
        'maxHeight'       => '_maxHeight',
        'display'         => '_display',
        'flexDirection'   => '_flexDirection',
        'justifyContent'  => '_justifyContent',
        'alignItems'      => '_alignItems',
        'gridTemplateColumns' => '_gridTemplateColumns',
        'gap'             => '_gridGap',
        'columnGap'       => '_columnGap',
        'rowGap'          => '_rowGap',
        'background'      => '_background',
        'padding'         => '_padding',
        'margin'          => '_margin',
    );
    
    return $map[ $key ] ?? false;
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
