# SNN-BRX Child Theme - AI Coding Instructions

## Theme Architecture Overview

This is a **WordPress child theme for Bricks Builder** designed as a comprehensive replacement for 25+ plugins. The theme follows a modular, feature-based architecture where each capability is a separate, toggleable module.

### Core Structure Pattern

- **`functions.php`**: Main entry point that requires all modules using `SNN_PATH` constants
- **`includes/`**: All feature modules organized by function (security, AI, elements, etc.)
- **`includes/settings-page.php`**: Central dashboard with grid navigation to all feature settings
- **Feature Toggle Pattern**: Most features check `get_option('snn_*_settings')` arrays for enable/disable state

### Key Constants
```php
SNN_PATH          // Theme directory path
SNN_PATH_ASSETS   // Assets directory path  
SNN_URL           // Theme URL
SNN_URL_ASSETS    // Assets URL
```

## Bricks Builder Integration Patterns

### Custom Element Registration
Elements are registered in `functions.php` using conditional loading:
```php
// Standard elements (always loaded)
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/custom-maps.php');

// GSAP elements (conditionally loaded)
$options = get_option('snn_other_settings');
if (!empty($options['enqueue_gsap'])) {
    \Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gsap-animations.php');
}
```

### Custom Element Class Structure
All custom elements extend `\Bricks\Element` with this pattern:
- `$category = 'snn'` (groups elements in Bricks panel)
- `$name` = unique element identifier
- `$css_selector` = main wrapper class
- `$nestable = true` for container elements
- `$scripts = []` for script dependencies
- `get_label()` = display name in Bricks
- `set_controls()` = element settings configuration

## Custom Element Development Deep Dive

### Element File Structure Patterns

#### Basic Element Template
```php
<?php
if (!defined('ABSPATH')) exit;

class Custom_Element_Name extends \Bricks\Element {
    public $category = 'snn';
    public $name = 'element-name';
    public $icon = 'fas fa-icon';
    public $css_selector = '.custom-element-wrapper';
    public $nestable = false; // or true for containers
    public $scripts = []; // External dependencies

    public function get_label() {
        return esc_html__('Element Display Name', 'snn');
    }
}
```

#### Nestable Container Elements
For elements that can contain other elements (like `flip-box.php`):
```php
public $nestable = true;
public $nestable_areas = ['front', 'back']; // Named areas for content
```

### Control Types and Patterns

#### Standard Control Types
- **Text Input**: `'type' => 'text'`
- **Number**: `'type' => 'number'` with `min`, `max`, `step`, `unit`
- **Checkbox**: `'type' => 'checkbox'` with `'inline' => true`
- **Select Dropdown**: `'type' => 'select'` with `'options' => []`
- **Color Picker**: `'type' => 'color'` with CSS targeting
- **Image Upload**: `'type' => 'image'`
- **Icon Picker**: `'type' => 'icon'`
- **Typography**: `'type' => 'typography'`
- **Code Editor**: `'type' => 'code'` with `'mode' => 'php|css|js'`

#### Advanced Control Patterns

**Repeater Fields** (from `custom-maps.php`):
```php
$this->controls['markers'] = [
    'tab' => 'content',
    'label' => 'Location',
    'type' => 'repeater',
    'titleProperty' => 'Location',
    'fields' => [
        'lat' => [
            'label' => __('Latitude', 'snn'),
            'type' => 'number',
            'step' => 0.0001,
        ],
        // More fields...
    ],
];
```

**Conditional Loading Controls** (from `lottie-animation.php`):
```php
$options = get_option('snn_other_settings');
if (isset($options['enqueue_gsap']) && $options['enqueue_gsap']) {
    // Only show this element when GSAP is enabled
}
```

**Multi-Select with Search** (from `frontend-post-form.php`):
```php
$this->controls['allowed_user_roles'] = [
    'type' => 'select',
    'options' => $role_options,
    'multiple' => true,
    'searchable' => true,
    'clearable' => true,
];
```

### Rendering Patterns

#### Basic Render Structure
```php
public function render() {
    // Set root attributes
    $this->set_attribute('_root', 'class', 'custom-element-wrapper');
    
    // Get settings
    $setting_value = $this->settings['setting_key'] ?? 'default';
    
    // Render output
    echo '<div ' . $this->render_attributes('_root') . '>';
    // Element content
    echo '</div>';
}
```

#### Inline Styles and Scripts
Many elements include inline CSS/JS (from `flip-box.php`):
```php
echo '<style>
    #' . esc_attr($root_id) . ' {
        /* Scoped styles using unique ID */
    }
</style>';
```

#### Dynamic Asset Loading
From `custom-maps.php` - conditionally enqueue Leaflet:
```php
wp_enqueue_style('leaflet-css', SNN_URL_ASSETS . 'css/leaflet.css');
wp_enqueue_script('leaflet-js', SNN_URL_ASSETS . 'js/leaflet.js');
```

### GSAP Integration Patterns

#### Animation Presets System
GSAP elements use preset-based animations (from `gsap-text-animations.php`):
```php
$this->controls['presets'] = [
    'type' => 'select',
    'options' => [
        'style_start-opacity:0, style_end-opacity:1' => 'Fade In',
        'style_start-transform:translateY(-100px), style_end-transform:translateY(0px)' => 'Slide Down',
    ],
];
```

#### Multi-Element Animation Registration
The `gsap-multi-element-register.php` adds animation controls to existing Bricks elements:
```php
$targets = ['section', 'container', 'block', 'div', 'heading', 'text-basic'];
foreach ($targets as $name) {
    add_filter("bricks/elements/{$name}/controls", function($controls) {
        // Add animation controls to existing elements
        return $controls;
    });
}
```

### Frontend Interaction Elements

#### Form Handling Pattern (from `frontend-post-form.php`)
- User permission checks
- Post type and taxonomy integration
- File upload handling
- Custom validation and sanitization

#### Interactive JavaScript Elements
Elements like `compare-image.php` include:
- Mouse event handlers
- Dynamic DOM manipulation
- State management with CSS classes

### Asset Management Patterns

#### Conditional Script Loading
From `enqueue-scripts.php`:
```php
add_action('wp_enqueue_scripts', function () {
    if (!bricks_is_builder_main()) {
        wp_enqueue_style('bricks-child', get_stylesheet_uri(), ['bricks-frontend']);
    }
});
```

#### Editor-Specific Enhancements
Special handling when `?bricks=run` is present for builder interface improvements.

### Element Registration Patterns

Elements are registered in two ways:
1. **Direct registration** in `functions.php`
2. **Class registration** at end of element file:
```php
add_action('bricks/element_classes', function($element_classes) {
    $element_classes['custom-html-css-script'] = 'Custom_HTML_CSS_Script';
    return $element_classes;
});
```

### Utility Functions

#### Value Parsing
Common utility from GSAP elements:
```php
private function parse_unit_value($value) {
    if (empty($value)) return '';
    if (preg_match('/[a-zA-Z%()]/', $value)) return $value;
    return is_numeric($value) ? $value . 'px' : $value;
}
```

## Settings Architecture

### Module Pattern
Each feature follows this structure:
1. **Settings Registration**: `add_settings_field()` in admin_init hook
2. **Options Storage**: Uses `snn_*_options` option names  
3. **Conditional Execution**: Features check their options before running
4. **Dashboard Integration**: Menu items defined in `settings-page.php` array

### Security Module Example
Files like `disable-wp-json-if-not-logged-in.php` demonstrate the pattern:
- Settings field registration function
- Callback function for form rendering  
- Feature implementation using `get_option()` checks
- Direct WordPress hook integration

## AI Integration System

The AI features use a 3-file architecture:

1. **`ai-settings.php`**: Admin UI for API keys and provider selection
2. **`ai-api.php`**: Configuration helper with `snn_get_ai_api_config()` function
3. **`ai-overlay.php`**: Frontend interface injected into Bricks Builder

### AI Providers Supported
- OpenAI (gpt-4o-mini default)
- OpenRouter (multiple models)
- Custom API endpoints

## GSAP Animation System

GSAP features are conditionally loaded and include:
- **Scroll Trigger animations** 
- **Text animations** with SplitText
- **Multi-element animations** via `gsap-multi-element-register.php`
- **Responsive controls** for different breakpoints

### Animation Element Pattern
GSAP elements use repeater controls for multiple animations on single elements with properties like:
- Animation type (fade, slide, rotate, etc.)
- Trigger settings (scroll, load, hover)
- Duration and delay controls
- Device-specific visibility

## Dynamic Data Tags

Custom dynamic data tags in `includes/dynamic-data-tags/` extend Bricks' data capabilities:
- `estimated-post-read-time.php` - Calculates reading time
- `get-contextual-id.php` - Context-aware ID retrieval
- `custom-field-repeater-first-item.php` - First repeater item access

## Development Workflows

### Adding New Features
1. Create module file in appropriate `includes/` subdirectory
2. Add `require_once` to `functions.php`
3. Add settings field registration if configurable
4. Add menu item to `$menu_items` array in `settings-page.php`
5. Follow the options pattern: `get_option('snn_feature_options')`

### Custom Elements Development Workflow
1. **Create Element File**: Place in `includes/elements/`
2. **Extend Base Class**: `class MyElement extends \Bricks\Element`
3. **Set Required Properties**:
   ```php
   public $category = 'snn';
   public $name = 'my-element';
   public $icon = 'fas fa-icon';
   public $css_selector = '.my-element-wrapper';
   ```
4. **Define Controls**: Use `set_controls()` method with proper control types
5. **Implement Render**: Handle settings retrieval and output generation
6. **Register Element**: Add to `functions.php` or use class registration
7. **Handle Assets**: Enqueue scripts/styles in render method if needed

**Element Registration Example:**
After creating your element (e.g., `gallery-box.php`), register it in `functions.php` like this:
```php
\Bricks\Elements::register_element(SNN_PATH . 'includes/elements/gallery-box.php');
```

### Element Development Best Practices
- **Unique IDs**: $root_id = $this->attributes['_root']['id']; or if attr need to be added to dom $this->set_attribute( '_root', 'class', 'brxe-flipbox flip-container' );
- **Settings Validation**: Always provide defaults and validate user input
- **Responsive Considerations**: Include device-specific controls where appropriate
- **Inline Styles**: Use scoped CSS with unique selectors for complex styling
- **Asset Loading**: Only enqueue external assets when element is used
- **Accessibility**: Include proper ARIA attributes and semantic HTML

### GSAP Element Patterns
- **Conditional Loading**: Check `snn_other_settings['enqueue_gsap']`
- **Preset System**: Use preset strings for common animations
- **Multi-Element Support**: Extend existing Bricks elements via filters
- **Performance**: Load GSAP assets only when animations are enabled

### Security Feature Pattern
Each security feature follows:
- Settings field in admin
- Option check before execution  
- Direct WordPress hook implementation
- Clear enable/disable toggle in UI

### Custom Field Integration
- Register fields via `custom-field-settings.php`
- Support repeater fields and multiple field types
- Integrate with Bricks dynamic data system
- Provide admin UI for field management

## File Organization Principles

- **Feature isolation**: Each capability in separate file
- **Conditional loading**: Resource-heavy features (GSAP, AI) only load when enabled
- **Settings centralization**: All admin settings accessible from main dashboard
- **Asset organization**: CSS/JS in `assets/` with descriptive names

## Key Integration Points

- **WordPress hooks**: Features integrate via standard WP hooks (init, admin_init, etc.)
- **Bricks Builder**: Custom elements, editor enhancements, and builder integration
- **Database options**: Extensive use of WordPress options API for settings storage
- **Asset management**: Conditional script/style enqueuing based on feature status

## Bricks Builder Control Types Reference

Below is a reference of all available Bricks Builder element control types, their options, and example usage. Use this as a cheat sheet when building custom elements.

### Universal Control Arguments
- `tab`: 'content' or 'style'
- `group`: string (optional grouping)
- `label`: localized string
- `type`: control type (see below)
- `inline`: bool (label/input on same line)
- `small`: bool (input width 60px)
- `css`: array of CSS rules (`property`, `selector`)
- `default`: string/array
- `pasteStyles`: bool (exclude from paste styles)
- `description`: string
- `required`: array (conditional display)

### Control Types & Examples

#### Text
```php
$this->controls['exampleText'] = [
  'type' => 'text',
  'spellcheck' => true,
  'trigger' => 'keyup',
  'inlineEditing' => true,
  'default' => 'Your text here',
];
```

#### Border
```php
$this->controls['titleBorder'] = [
  'type' => 'border',
  'css' => [ ['property' => 'border', 'selector' => '.title'] ],
  'default' => [ 'width' => ['top' => 1], 'color' => '#000' ],
];
```

#### Background
```php
$this->controls['exampleBackground'] = [
  'type' => 'background',
  'css' => [ ['property' => 'background', 'selector' => '.wrapper'] ],
  'exclude' => ['color'],
];
```

#### Icon
```php
$this->controls['exampleIcon'] = [
  'type' => 'icon',
  'default' => [ 'library' => 'themify', 'icon' => 'ti-star' ],
];
```

#### Checkbox
```php
$this->controls['exampleCheckbox'] = [
  'type' => 'checkbox',
  'inline' => true,
  'default' => true,
];
```

#### Info
```php
$this->controls['info'] = [
  'type' => 'info',
  'label' => 'Helper text',
];
```

#### Editor
```php
$this->controls['exampleEditor'] = [
  'type' => 'editor',
  'inlineEditing' => [ 'selector' => '.editor', 'toolbar' => true ],
];
```

#### Dimensions
```php
$this->controls['exampleDimensions'] = [
  'type' => 'dimensions',
  'css' => [ ['property' => 'padding', 'selector' => '.title'] ],
  'default' => [ 'top' => '30px' ],
];
```

#### Code
```php
$this->controls['exampleHtml'] = [
  'type' => 'code',
  'mode' => 'php',
  'default' => '<h4>Example</h4>',
];
```

#### Link
```php
$this->controls['exampleLink'] = [
  'type' => 'link',
  'placeholder' => 'http://yoursite.com',
];
```

#### Number
```php
$this->controls['exampleNumber'] = [
  'type' => 'number',
  'min' => 0,
  'step' => 0.1,
  'default' => 123,
];
```

#### Textarea
```php
$this->controls['exampleTextarea'] = [
  'type' => 'textarea',
  'rows' => 10,
  'spellcheck' => true,
  'inlineEditing' => true,
];
```

#### Audio
```php
$this->controls['file'] = [ 'type' => 'audio' ];
```

#### Box Shadow
```php
$this->controls['exampleBoxShadow'] = [
  'type' => 'box-shadow',
  'css' => [ ['property' => 'box-shadow', 'selector' => '.wrapper'] ],
];
```

#### Image
```php
$this->controls['exampleImage'] = [ 'type' => 'image' ];
```

#### Datepicker
```php
$this->controls['date'] = [
  'type' => 'datepicker',
  'options' => [ 'enableTime' => true, 'altInput' => true ],
];
```

#### Image Gallery
```php
$this->controls['exampleImageGallery'] = [ 'type' => 'image-gallery' ];
```

#### Color
```php
$this->controls['exampleColor'] = [
  'type' => 'color',
  'css' => [ ['property' => 'color', 'selector' => '.title'] ],
  'default' => [ 'hex' => '#3ce77b' ],
];
```

#### Typography
```php
$this->controls['exampleTypography'] = [
  'type' => 'typography',
  'css' => [ ['property' => 'typography', 'selector' => '.text'] ],
  'inline' => true,
];
```

#### Repeater
```php
$this->controls['exampleRepeater'] = [
  'type' => 'repeater',
  'titleProperty' => 'title',
  'fields' => [ 'title' => [ 'type' => 'text' ] ],
];
```

#### Filters
```php
$this->controls['exampleFilters'] = [
  'type' => 'filters',
  'css' => [ ['property' => 'filter', 'selector' => '.filter'] ],
];
```

#### SVG
```php
$this->controls['exampleSvg'] = [ 'type' => 'svg' ];
```

#### Text Shadow
```php
$this->controls['exampleTextShadow'] = [
  'type' => 'text-shadow',
  'css' => [ ['property' => 'text-shadow', 'selector' => '.text'] ],
];
```

#### Slider
```php
$this->controls['exampleSliderFontSize'] = [
  'type' => 'slider',
  'css' => [ ['property' => 'font-size'] ],
  'units' => [ 'px' => ['min' => 1, 'max' => 50, 'step' => 1] ],
];
```

#### Select
```php
$this->controls['exampleSelectTitleTag'] = [
  'type' => 'select',
  'options' => [ 'h1' => 'H1', 'h2' => 'H2' ],
];
```

#### Query
```php
$this->controls['exampleQueryArgs'] = [
  'type' => 'query',
  'default' => [ 'post_type' => 'post' ],
];
```

#### Gradient
```php
$this->controls['exampleGradient'] = [
  'type' => 'gradient',
  'css' => [ ['property' => 'background-image'] ],
];
```

#### Apply
```php
$this->controls['apply'] = [
  'type' => 'apply',
  'reload' => true,
  'label' => 'Apply preview',
];
```

#### Flexbox Controls
- `align-items`, `justify-content`, `direction`
```php
$this->controls['alignItems'] = [ 'type' => 'align-items', 'css' => [ ['property' => 'align-items', 'selector' => '.flexbox'] ] ];
$this->controls['justifyContent'] = [ 'type' => 'justify-content', 'css' => [ ['property' => 'justify-content', 'selector' => '.flexbox'] ] ];
$this->controls['direction'] = [ 'type' => 'direction', 'css' => [ ['property' => 'flex-direction', 'selector' => '.flexbox'] ] ];
```

#### Text Align / Decoration / Transform
```php
$this->controls['textAlign'] = [ 'type' => 'text-align', 'css' => [ ['property' => 'text-align', 'selector' => '.text'] ] ];
$this->controls['textDecoration'] = [ 'type' => 'text-decoration', 'css' => [ ['property' => 'text-decoration', 'selector' => '.text'] ] ];
$this->controls['textTransform'] = [ 'type' => 'text-transform', 'css' => [ ['property' => 'text-transform', 'selector' => '.text'] ] ];
```

---

For more details and advanced usage, see the [Bricks Builder Codex](https://sinanisler.com/codex/bricks-builder-docs-custom-elements-php-bricks-controls-php/).

## Element Rendering & Unique Root IDs

- Always generate a unique root ID for each element instance (e.g., `$root_id = 'element-' . uniqid();`).
- Use this root ID for targeting with JS and CSS, ensuring styles/scripts only affect the intended element.
- Output inline CSS and JS using `echo` inside the `render()` method. This guarantees the element looks and behaves the same in both the builder/editor and on the frontend.
- Example:
```php
$root_id = 'my-element-' . uniqid();
$this->set_attribute('_root', 'id', $root_id);
echo '<div ' . $this->render_attributes('_root') . '>';
echo '<style>#' . esc_attr($root_id) . ' { /* styles */ }</style>';
echo '<script>document.getElementById("' . esc_js($root_id) . '") /* ... */</script>';
echo '</div>';
```
- Avoid relying on static selectors or global JS/CSS for element-specific logic—always scope to the unique root ID.
