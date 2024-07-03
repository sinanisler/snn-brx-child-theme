<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Custom_Title extends \Bricks\Element {
  /** 
   * How to create custom elements in Bricks
   * 
   * https://academy.bricksbuilder.io/article/create-your-own-elements
   */
  public $category     = 'custom';
  public $name         = 'custom-title';
  public $icon         = 'fas fa-anchor'; // FontAwesome 5 icon in builder (https://fontawesome.com/icons)
  public $css_selector = '.custom-title-wrapper'; // Default CSS selector for all controls with 'css' properties
  // public $scripts      = []; // Enqueue registered scripts by their handle

  public function get_label() {
    return esc_html__( 'Title', 'bricks' );
  }

  public function set_control_groups() {
    $this->control_groups['typography'] = [
      'title' => esc_html__( 'Typography', 'bricks' ),
      'tab'   => 'content', // Accepts: 'content' or 'style'
    ];
  }

  public function set_controls() {
    $this->controls['title'] = [
      'tab'            => 'content',
      'label'          => esc_html__( 'Title', 'bricks' ),
      'type'           => 'text',
			'hasDynamicData' => 'text',
      'default'        => esc_html__( 'I am a custom element', 'bricks' ),
      'placeholder'    => esc_html__( 'Title goes here ..', 'bricks' ),
    ];

    $this->controls['subtitle'] = [
      'tab'            => 'content',
      'label'          => esc_html__( 'Subtitle', 'bricks' ),
      'type'           => 'text',
			'hasDynamicData' => 'text',
      'default'        => esc_html__( 'Just a subtitle. Click to edit me!', 'bricks' ),
      'placeholder'    => esc_html__( 'Subtitle goes here ..', 'bricks' ),
    ];

    $this->controls['titleTypography'] = [
      'tab'     => 'content',
      'group'   => 'typography',
      'label'   => esc_html__( 'Title typography', 'bricks' ),
      'type'    => 'typography',
      'default' => [
        'color' => [
          'hex' => '#f44336',
        ],
      ],
			'css'     => [
        [
          'property' => 'typography',
          'selector' => '.title',
        ],
      ],
    ];

    $this->controls['subtitleTypography'] = [
      'tab'     => 'content',
      'group'   => 'typography',
      'label'   => esc_html__( 'Subtitle typography', 'bricks' ),
      'type'    => 'typography',
      'default' => [
        'font-size' => '18px',
      ],
			'css'     => [
        [
          'property' => 'typography',
          'selector' => '.subtitle',
        ],
      ],
    ];
  }

  /** 
   * Render element HTML on frontend
   * 
   * If no 'render_builder' function is defined then this code is used to render element HTML in builder, too.
   */
  public function render() {
    $settings = $this->settings;
    $title    = ! empty( $settings['title'] ) ? $settings['title'] : false;
    $subtitle = ! empty( $settings['subtitle'] ) ? $settings['subtitle'] : false;

    // Return element placeholder
    if ( ! $title && ! $subtitle ) {
      return $this->render_element_placeholder( [
        'icon-class'  => 'ti-paragraph',
        'title'       => esc_html__( 'Please add a title/subtitle.', 'bricks' ),
				'description' => esc_html__( 'Here goes the element placeholder description (optional).', 'bricks' ),
      ] );
    }

		/**
		 * '_root' attribute contains element ID, classes, etc. 
		 * 
		 * @since 1.4
		 */
    $output = "<div {$this->render_attributes( '_root' )}>";

    if ( $title ) {
      $this->set_attribute( 'title', 'class', 'title' );
    
      $output .= "<h4 {$this->render_attributes( 'title' )}>$title</h4>";
    }

    if ( $subtitle ) {
      $this->set_attribute( 'subtitle', 'class', 'subtitle' );
    
      $output .= "<div {$this->render_attributes( 'subtitle' )}>$subtitle</div>";
    }

    $output .= '</div>';

		// Output final element HTML
		echo $output;
  }

  /**
   * Render element HTML in builder (optional)
   * 
   * Adds element render scripts to wp_footer via x-template.
   * Better performance than PHP 'render' function, which requires AJAX requests for every HTML re-render. 
   * Works only with static, non-database data.
   */
  public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-custom-title">
			<component 
				:is="tag"
				class="custom-title-wrapper">
				<contenteditable
					v-if="settings.title" 
					tag="h4"
					:name="name"
					:settings="settings"
					controlKey="title"
					class="title"/>

				<contenteditable
					v-if="settings.subtitle" 
					:name="name"
					:settings="settings"
					controlKey="subtitle"
					class="subtitle"/>
			</component>
		</script>
	<?php
	}
}