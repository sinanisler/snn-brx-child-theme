<?php
/**
 * Custom Cursor Element for Bricks Builder
 * 
 * This element creates customizable cursor effects using the Cotton.js library
 * with support for multiple cursor states via repeater fields.
 * 
 * @package Bricks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Custom_Cursor_Element extends \Bricks\Element {
	
	// Element properties
	public $category     = 'general';
	public $name         = 'custom-cursor';
	public $icon         = 'ti-mouse';
	public $css_selector = '.custom-cursor-wrapper';
	public $scripts      = ['customCursorScript'];
	
	/**
	 * Return localized element label
	 */
	public function get_label() {
		return esc_html__( 'Custom Cursor', 'bricks' );
	}
	
	/**
	 * Return element keywords for search
	 */
	public function get_keywords() {
		return [ 'cursor', 'mouse', 'pointer', 'custom', 'follow', 'hover', 'interactive' ];
	}
	
	/**
	 * Set builder controls
	 */
	public function set_controls() {

		// ========== MAIN CURSOR ==========

		$this->controls['mainCursorEnable'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Enable Main Cursor', 'bricks' ),
			'type'    => 'checkbox',
			'inline'  => true,
			'default' => true,
		];

		$this->controls['mainCursorShape'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Shape', 'bricks' ),
			'type'     => 'select',
			'options'  => [
				'circle' => esc_html__( 'Circle', 'bricks' ),
				'square' => esc_html__( 'Square', 'bricks' ),
			],
			'default'  => 'circle',
			'inline'   => true,
			'required' => [ 'mainCursorEnable', '=', true ],
		];

		$this->controls['mainCursorSize'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Size (px)', 'bricks' ),
			'type'     => 'number',
			'unit'     => 'px',
			'default'  => 10,
			'min'      => 5,
			'max'      => 100,
			'required' => [ 'mainCursorEnable', '=', true ],
		];

		$this->controls['mainCursorColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Background Color', 'bricks' ),
			'type'     => 'color',
			'inline'   => true,
			'default'  => [
				'hex' => '#000000',
			],
			'required' => [ 'mainCursorEnable', '=', true ],
		];

		// ========== HOVER STATES REPEATER ==========

		$this->controls['cursorHovers'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Hover Cursors', 'bricks' ),
			'type'          => 'repeater',
			'titleProperty' => 'hoverName',
			'placeholder'   => esc_html__( 'Hover State', 'bricks' ),
			'fields'        => [
				'hoverName' => [
					'label'       => esc_html__( 'Name', 'bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'e.g., Project Hover', 'bricks' ),
				],
				
				'hoverTargets' => [
					'label'       => esc_html__( 'Target Selectors', 'bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( '.project, #element-id', 'bricks' ),
					'description' => esc_html__( 'CSS selectors (comma-separated) that will trigger this cursor', 'bricks' ),
				],
				
				'hoverShape' => [
					'label'   => esc_html__( 'Shape', 'bricks' ),
					'type'    => 'select',
					'options' => [
						'circle' => esc_html__( 'Circle', 'bricks' ),
						'square' => esc_html__( 'Square', 'bricks' ),
					],
					'default' => 'circle',
					'inline'  => true,
				],
				
				'hoverSize' => [
					'label'   => esc_html__( 'Size (px)', 'bricks' ),
					'type'    => 'number',
					'unit'    => 'px',
					'default' => 140,
					'min'     => 50,
					'max'     => 300,
				],
				
				'hoverType' => [
					'label'   => esc_html__( 'Background Type', 'bricks' ),
					'type'    => 'select',
					'options' => [
						'color' => esc_html__( 'Color', 'bricks' ),
						'image' => esc_html__( 'Image', 'bricks' ),
					],
					'default' => 'color',
					'inline'  => true,
				],
				
				'hoverColor' => [
					'label'    => esc_html__( 'Background Color', 'bricks' ),
					'type'     => 'color',
					'inline'   => true,
					'default'  => [ 'hex' => '#000000' ],
					'required' => [ 'hoverType', '=', 'color' ],
				],
				
				'hoverImageUrl' => [
					'label'    => esc_html__( 'Background Image URL', 'bricks' ),
					'type'     => 'image',
					'required' => [ 'hoverType', '=', 'image' ],
				],
				
				'hoverShowArrow' => [
					'label'   => esc_html__( 'Show Center Arrow', 'bricks' ),
					'type'    => 'checkbox',
					'inline'  => true,
					'default' => true,
				],
				
				'hoverArrowImage' => [
					'label'    => esc_html__( 'Arrow Image', 'bricks' ),
					'type'     => 'image',
					'required' => [ 'hoverShowArrow', '=', true ],
				],
				
				'hoverArrowSize' => [
					'label'    => esc_html__( 'Arrow Size (px)', 'bricks' ),
					'type'     => 'number',
					'unit'     => 'px',
					'default'  => 44,
					'min'      => 20,
					'max'      => 100,
					'required' => [ 'hoverShowArrow', '=', true ],
				],
				
				'hoverRotateSpeed' => [
					'label'       => esc_html__( 'Rotation Speed (seconds)', 'bricks' ),
					'type'        => 'number',
					'default'     => 20,
					'min'         => 5,
					'max'         => 60,
					'description' => esc_html__( 'Animation duration in seconds', 'bricks' ),
				],
				
				'hoverReverseRotation' => [
					'label'   => esc_html__( 'Reverse Arrow Rotation', 'bricks' ),
					'type'    => 'checkbox',
					'inline'  => true,
					'default' => true,
				],
			],
		];
		
		// ========== SETTINGS ==========

		$this->controls['cursorSpeed'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Follow Speed', 'bricks' ),
			'type'        => 'number',
			'default'     => 0.125,
			'min'         => 0.01,
			'max'         => 1,
			'step'        => 0.01,
			'description' => esc_html__( 'How fast the cursor follows the mouse (0.01 - 1)', 'bricks' ),
		];

		$this->controls['centerMouse'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Center on Mouse', 'bricks' ),
			'type'        => 'checkbox',
			'inline'      => true,
			'default'     => true,
			'description' => esc_html__( 'Center the cursor on mouse position', 'bricks' ),
		];

		$this->controls['hideOnBuilder'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Hide in Builder', 'bricks' ),
			'type'        => 'checkbox',
			'inline'      => true,
			'default'     => true,
			'description' => esc_html__( 'Hide cursors when editing in Bricks Builder', 'bricks' ),
		];

		$this->controls['cursorZIndex'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Z-Index', 'bricks' ),
			'type'    => 'number',
			'default' => 9999,
			'min'     => 0,
			'max'     => 999999,
		];
	}
	
	/**
	 * Enqueue element styles and scripts
	 */
	public function enqueue_scripts() {
		// Cotton.js library is inline in the element for simplicity
		// You can also enqueue it separately if preferred
	}
	
	/**
	 * Render element HTML on frontend
	 */
	public function render() {
		$settings = $this->settings;
		
		// Get settings with defaults
		$main_cursor_enable = isset( $settings['mainCursorEnable'] ) ? true : false;
		$main_cursor_shape  = isset( $settings['mainCursorShape'] ) ? $settings['mainCursorShape'] : 'circle';
		$main_cursor_size   = isset( $settings['mainCursorSize'] ) ? $settings['mainCursorSize'] : 10;
		$main_cursor_color  = isset( $settings['mainCursorColor']['hex'] ) ? $settings['mainCursorColor']['hex'] : '#000000';
		$cursor_hovers      = isset( $settings['cursorHovers'] ) ? $settings['cursorHovers'] : [];
		$cursor_speed       = isset( $settings['cursorSpeed'] ) ? $settings['cursorSpeed'] : 0.125;
		$center_mouse       = isset( $settings['centerMouse'] ) ? true : false;
		$hide_on_builder    = isset( $settings['hideOnBuilder'] ) ? true : false;
		$cursor_z_index     = isset( $settings['cursorZIndex'] ) ? $settings['cursorZIndex'] : 9999;
		
		// Generate unique ID for this element instance
		$element_id = 'custom-cursor-' . $this->id;
		
		?>
		<div <?php echo $this->render_attributes( '_root' ); ?> id="<?php echo esc_attr( $element_id ); ?>">
			
			<?php if ( $main_cursor_enable ) : ?>
				<!-- Main Cursor -->
				<div class="snn-cursor" data-cursor="main" style="
					position: fixed;
					top: 0;
					left: 0;
					z-index: <?php echo esc_attr( $cursor_z_index ); ?>;
					width: <?php echo esc_attr( $main_cursor_size ); ?>px;
					height: <?php echo esc_attr( $main_cursor_size ); ?>px;
					border-radius: <?php echo $main_cursor_shape === 'circle' ? '50%' : '0'; ?>;
					pointer-events: none;
					background-color: <?php echo esc_attr( $main_cursor_color ); ?>;
					transform: translate(-200px, -200px);
				"></div>
			<?php endif; ?>
			
			<?php 
			// Render hover cursors
			if ( ! empty( $cursor_hovers ) && is_array( $cursor_hovers ) ) :
				foreach ( $cursor_hovers as $index => $hover ) :
					$hover_name           = isset( $hover['hoverName'] ) ? $hover['hoverName'] : 'Hover ' . ( $index + 1 );
					$hover_targets        = isset( $hover['hoverTargets'] ) ? $hover['hoverTargets'] : '';
					$hover_shape          = isset( $hover['hoverShape'] ) ? $hover['hoverShape'] : 'circle';
					$hover_size           = isset( $hover['hoverSize'] ) ? $hover['hoverSize'] : 140;
					$hover_type           = isset( $hover['hoverType'] ) ? $hover['hoverType'] : 'color';
					$hover_color          = isset( $hover['hoverColor']['hex'] ) ? $hover['hoverColor']['hex'] : '#000000';
					$hover_image_id       = isset( $hover['hoverImageUrl']['id'] ) ? $hover['hoverImageUrl']['id'] : 0;
					$hover_image_url      = $hover_image_id ? wp_get_attachment_image_url( $hover_image_id, 'full' ) : '';
					$hover_show_arrow     = isset( $hover['hoverShowArrow'] ) ? true : false;
					$hover_arrow_image_id = isset( $hover['hoverArrowImage']['id'] ) ? $hover['hoverArrowImage']['id'] : 0;
					$hover_arrow_url      = $hover_arrow_image_id ? wp_get_attachment_image_url( $hover_arrow_image_id, 'full' ) : '';
					$hover_arrow_size     = isset( $hover['hoverArrowSize'] ) ? $hover['hoverArrowSize'] : 44;
					$hover_rotate_speed   = isset( $hover['hoverRotateSpeed'] ) ? $hover['hoverRotateSpeed'] : 20;
					$hover_reverse_rotate = isset( $hover['hoverReverseRotation'] ) ? true : false;
					
					if ( empty( $hover_targets ) ) continue;
					
					$hover_class = 'hover-cursor-' . sanitize_title( $hover_name ) . '-' . $index;
					
					// Determine background style
					$bg_style = '';
					if ( $hover_type === 'image' && ! empty( $hover_image_url ) ) {
						$bg_style = "background: url('" . esc_url( $hover_image_url ) . "') no-repeat center center; background-size: cover;";
					} else {
						$bg_style = "background-color: " . esc_attr( $hover_color ) . ";";
					}
					?>
					
					<!-- Hover Cursor: <?php echo esc_html( $hover_name ); ?> -->
					<div class="<?php echo esc_attr( $hover_class ); ?>" data-cursor="hover" data-targets="<?php echo esc_attr( $hover_targets ); ?>" style="
						position: fixed;
						top: 0;
						left: 0;
						z-index: <?php echo esc_attr( $cursor_z_index ); ?>;
						width: <?php echo esc_attr( $hover_size ); ?>px;
						height: <?php echo esc_attr( $hover_size ); ?>px;
						pointer-events: none;
						transform: translate(-200px, -200px);
						opacity: 0;
						visibility: hidden;
						transition: opacity 0.3s ease, visibility 0.3s ease;
					">
						<div class="<?php echo esc_attr( $hover_class ); ?>-circle" style="
							width: <?php echo esc_attr( $hover_size ); ?>px;
							height: <?php echo esc_attr( $hover_size ); ?>px;
							display: flex;
							<?php echo $bg_style; ?>
							border-radius: <?php echo $hover_shape === 'circle' ? '50%' : '0'; ?>;
							animation: rotate-<?php echo esc_attr( $hover_class ); ?> <?php echo esc_attr( $hover_rotate_speed ); ?>s infinite linear;
							justify-content: center;
							align-items: center;
						">
							<?php if ( $hover_show_arrow && ! empty( $hover_arrow_url ) ) : ?>
								<div class="<?php echo esc_attr( $hover_class ); ?>-arrow" style="
									width: <?php echo esc_attr( $hover_arrow_size ); ?>px;
									height: <?php echo esc_attr( $hover_arrow_size ); ?>px;
									display: block;
									background: url('<?php echo esc_url( $hover_arrow_url ); ?>') no-repeat center center;
									background-size: contain;
									<?php if ( $hover_reverse_rotate ) : ?>
									animation: rotate-<?php echo esc_attr( $hover_class ); ?>-reverse <?php echo esc_attr( $hover_rotate_speed ); ?>s infinite linear;
									<?php endif; ?>
								"></div>
							<?php endif; ?>
						</div>
					</div>
					
					<style>
						@keyframes rotate-<?php echo esc_attr( $hover_class ); ?> {
							from { transform: rotate(0deg); }
							to { transform: rotate(-360deg); }
						}
						<?php if ( $hover_reverse_rotate ) : ?>
						@keyframes rotate-<?php echo esc_attr( $hover_class ); ?>-reverse {
							from { transform: rotate(0deg); }
							to { transform: rotate(360deg); }
						}
						<?php endif; ?>
					</style>
					
				<?php endforeach; ?>
			<?php endif; ?>
			
			<!-- Cotton.js Library -->
			<script>
			<?php echo $this->get_cotton_js(); ?>
			</script>
			
			<!-- Custom Cursor Initialization -->
			<script>
			(function() {
				'use strict';
				
				// Configuration
				const cursorConfig = {
					speed: <?php echo esc_js( $cursor_speed ); ?>,
					centerMouse: <?php echo $center_mouse ? 'true' : 'false'; ?>,
					hovers: []
				};
				
				<?php if ( $main_cursor_enable ) : ?>
				// Initialize main cursor
				const mainCursor = document.querySelector('#<?php echo esc_js( $element_id ); ?> .snn-cursor[data-cursor="main"]');
				if (mainCursor) {
					new Cotton(mainCursor, {
						speed: cursorConfig.speed,
						centerMouse: cursorConfig.centerMouse
					});
				}
				<?php endif; ?>
				
				// Initialize hover cursors
				const hoverCursors = document.querySelectorAll('#<?php echo esc_js( $element_id ); ?> [data-cursor="hover"]');
				hoverCursors.forEach(function(hoverCursor) {
					const targets = hoverCursor.getAttribute('data-targets');
					if (!targets) return;
					
					// Set initial hidden state
					hoverCursor.style.opacity = '0';
					hoverCursor.style.visibility = 'hidden';
					
					// Make hover cursor follow mouse
					new Cotton(hoverCursor, {
						speed: cursorConfig.speed,
						centerMouse: cursorConfig.centerMouse
					});
					
					// Setup interactions
					<?php if ( $main_cursor_enable ) : ?>
					new Cotton(mainCursor, {
						models: targets,
						on: {
							enterModel: function(cursorEl) {
								hoverCursor.style.opacity = '1';
								hoverCursor.style.visibility = 'visible';
								cursorEl.style.opacity = '0';
							},
							leaveModel: function(cursorEl) {
								hoverCursor.style.opacity = '0';
								hoverCursor.style.visibility = 'hidden';
								cursorEl.style.opacity = '1';
							}
						}
					});
					<?php else : ?>
					// Without main cursor, just show/hide hover cursor
					const targetElements = document.querySelectorAll(targets);
					targetElements.forEach(function(target) {
						target.addEventListener('mouseenter', function() {
							hoverCursor.style.opacity = '1';
							hoverCursor.style.visibility = 'visible';
						});
						target.addEventListener('mouseleave', function() {
							hoverCursor.style.opacity = '0';
							hoverCursor.style.visibility = 'hidden';
						});
					});
					
					new Cotton(hoverCursor, {
						speed: cursorConfig.speed,
						centerMouse: cursorConfig.centerMouse
					});
					<?php endif; ?>
				});
				
				<?php if ( $hide_on_builder ) : ?>
				// Hide cursors in Bricks builder
				if (document.body.classList.contains('bricks-is-frontend-builder')) {
					const allCursors = document.querySelectorAll('#<?php echo esc_js( $element_id ); ?> [data-cursor]');
					allCursors.forEach(function(cursor) {
						cursor.style.display = 'none';
					});
				}
				<?php endif; ?>
			})();
			</script>
			
		</div>
		<?php
	}
	
	/**
	 * Get Cotton.js library inline
	 * 
	 * @return string Cotton.js library code
	 */
	private function get_cotton_js() {
		// Cotton.js library (minified version)
		return <<<'COTTONJS'
!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e="undefined"!=typeof globalThis?globalThis:e||self).Cotton=t()}(this,(function(){"use strict";function e(t){return(e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(t)}function t(e,t){for(var n=0;n<t.length;n++){var a=t[n];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}function n(e){return function(e){if(Array.isArray(e))return a(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(!e)return;if("string"==typeof e)return a(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);"Object"===n&&e.constructor&&(n=e.constructor.name);if("Map"===n||"Set"===n)return Array.from(e);if("Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n))return a(e,t)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function a(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,a=new Array(t);n<t;n++)a[n]=e[n];return a}function o(e){console.error("[Cotton warn]: ".concat(e))}function r(e){var t=e.getBoundingClientRect(),n=document.body.getBoundingClientRect();return{width:t.width,height:t.height,centerX:t.left-n.left+t.width/2-s(e),centerY:t.top-n.top+t.height/2-i(e)}}function s(e){var t=getComputedStyle(e).transform,n=t.match(/^matrix3d\((.+)\)$/);return n?parseFloat(n[1].split(", ")[12]):(n=t.match(/^matrix\((.+)\)$/))?parseFloat(n[1].split(", ")[4]):0}function i(e){var t=getComputedStyle(e).transform,n=t.match(/^matrix3d\((.+)\)$/);return n?parseFloat(n[1].split(", ")[13]):(n=t.match(/^matrix\((.+)\)$/))?parseFloat(n[1].split(", ")[5]):0}function l(e){var t=e.element,n=e.params,a=n.data;a.x&&a.y?(a.dx=(a.mouseX-a.x)*n.speed,a.dy=(a.mouseY-a.y)*n.speed,Math.abs(a.dx)+Math.abs(a.dy)<.1?(a.x=a.mouseX,a.y=a.mouseY):(a.x+=a.dx,a.y+=a.dy)):(a.x=a.mouseX,a.y=a.mouseY),a.animationFrame=requestAnimationFrame((function(){l(e)})),n.centerMouse?t.style.transform="translate(calc(-50% + ".concat(a.x,"px), calc(-50% + ").concat(a.y,"px))"):t.style.transform="translate(".concat(a.x,"px, ").concat(a.y,"px)")}function c(e){var t=e.element,n=e.params,a=n.data,o=n.airMode;a.distanceX&&a.distanceY?(a.dx=(a.distanceX-a.x)*n.speed,a.dy=(a.distanceY-a.y)*n.speed,Math.abs(a.dx)+Math.abs(a.dy)<.1?(a.x=a.distanceX,a.y=a.distanceY):(a.x+=a.dx,a.y+=a.dy)):(a.x=a.distanceX,a.y=a.distanceY),a.animationFrame=requestAnimationFrame((function(){c(e)}));var r=o.reverse?-a.x:a.x,s=o.reverse?-a.y:a.y,i="number"==typeof a.transformX?a.transformX+"px":a.transformX,l="number"==typeof a.transformY?a.transformY+"px":a.transformY,d=a.transformX?"calc(".concat(i," + ").concat(Math.floor(r/o.resistance),"px)"):"".concat(Math.floor(r/o.resistance),"px"),m=a.transformY?"calc(".concat(l," + ").concat(Math.floor(s/o.resistance),"px)"):"".concat(Math.floor(s/o.resistance),"px");t.style.transform="translate(".concat(d,", ").concat(m,")")}function d(e,t){if(0!==e.models.length){var n=e.models;if(t)n.forEach((function(t){t.addEventListener("mouseenter",e.enterModelHandler),t.addEventListener("mouseleave",e.leaveModelHandler)}));else{n.forEach((function(t){t.removeEventListener("mouseenter",e.enterModelHandler),t.removeEventListener("mouseleave",e.leaveModelHandler)}));var a=document.querySelectorAll(e.params.models);a.forEach((function(t){t.addEventListener("mouseenter",e.enterModelHandler),t.addEventListener("mouseleave",e.leaveModelHandler)})),e.models=a}}}return function(){function a(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,a);var r,s={data:{mouseX:null,mouseY:null,distanceX:null,distanceY:null,x:null,y:null,dx:null,dy:null,animationFrame:void 0},scene:"body",conttonInitClass:"cotton-init",cottonMovingClass:"cotton-moving",cottonActiveClass:"cotton-active",models:".cotton-model",modelsActiveClass:"model-active",centerMouse:!0,speed:.125,airMode:!1,on:{enterModel:null,leaveModel:null,enterScene:null,leaveScene:null,cottonMove:null}};if(this.element=t instanceof Element?t:document.querySelector(t),this.params=Object.assign({},s,n),this.scene=this.params.scene instanceof Element?this.params.scene:document.querySelector(this.params.scene),this.models=NodeList.prototype.isPrototypeOf(this.params.models)?this.params.models:document.querySelectorAll(this.params.models),this.enterModelHandler=this.enterModelHandler.bind(this),this.leaveModelHandler=this.leaveModelHandler.bind(this),!this.element)return o("Cannot define a cotton element which is not exist");if(!this.scene)return o("Cannot define a scene which is not exist");if((this.params.speed>1||this.params.speed<=0)&&(this.params.speed=.125),this.params.airMode){var i=this.params.airMode,l={resistance:15,reverse:!1,alive:!1};"object"!==e(i)||Array.isArray(i)?this.params.airMode=l:this.params.airMode=Object.assign(l,i),(i.resistance<1||i.resistance>100)&&(i.resistance=15)}(r=navigator.userAgent).indexOf("Android")>-1||r.indexOf("Adr"),r.indexOf("Mac")>-1&&"ontouchend"in document||a.init(this)}var m,u,f;return m=a,f=[{key:"getMouseData",value:function(e){var t=e.element,a=e.scene,o=e.params,l=o.data,c=o.airMode;a.addEventListener("mousemove",(function(a){l.mouseX=c?a.pageX:a.clientX,l.mouseY=c?a.pageY:a.clientY,n(t.classList).indexOf(o.conttonInitClass)>-1&&t.classList.add(o.cottonMovingClass),o.on.cottonMove&&"function"==typeof o.on.cottonMove&&o.on.cottonMove.call(e,t,a)})),c&&(c.alive||(l.rect=r(t),l.transformX=s(t),l.transformY=i(t),window.addEventListener("resize",(function(){l.rect=r(t),l.transformX=s(t),l.transformY=i(t)}))),a.addEventListener("mousemove",(function(){c.alive&&(l.rect=r(t));var e=window.innerWidth+l.rect.width/2,n=window.innerHeight+l.rect.height/2,a=l.mouseX-l.rect.centerX,o=l.mouseY-l.rect.centerY;l.distanceX=Math.min(Math.max(parseInt(a),-e),e),l.distanceY=Math.min(Math.max(parseInt(o),-n),n)})))}},{key:"init",value:function(e){var t=e.element,n=e.params,o=e.scene;o.addEventListener("mouseenter",(function(a){n.on.enterScene&&"function"==typeof n.on.enterScene&&n.on.enterScene.call(e,t,o,a)})),o.addEventListener("mouseleave",(function(a){t.classList.remove(n.cottonMovingClass),n.on.leaveScene&&"function"==typeof n.on.leaveScene&&n.on.leaveScene.call(e,t,o,a)})),a.getMouseData(e,!0),e.move(),d(e,!0)}}],(u=[{key:"enterModelHandler",value:function(e){var t=this.element,n=this.params;n.on.enterModel&&"function"==typeof n.on.enterModel&&n.on.enterModel.call(this,t,e.target,e),t.classList.add(n.cottonActiveClass),e.target.classList.add(n.modelsActiveClass)}},{key:"leaveModelHandler",value:function(e){var t=this.element,n=this.params;n.on.leaveModel&&"function"==typeof n.on.leaveModel&&n.on.leaveModel.call(this,t,e.target,e),t.classList.remove(n.cottonActiveClass),e.target.classList.remove(n.modelsActiveClass)}},{key:"move",value:function(){var e=this.params.data,t=this.params.airMode;this.element.classList.add(this.params.conttonInitClass),e.animationFrame||(t?c(this):l(this))}},{key:"stop",value:function(){var e=this.params.data;this.element.classList.remove(this.params.conttonInitClass),this.element.classList.remove(this.params.cottonMovingClass),cancelAnimationFrame(e.animationFrame),e.animationFrame=void 0}},{key:"updateModels",value:function(){d(this,!1)}}])&&t(m.prototype,u),f&&t(m,f),a}()}));
COTTONJS;
	}
}
