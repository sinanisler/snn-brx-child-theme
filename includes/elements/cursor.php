<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Bricks\Element;

class SNN_Custom_Cursor_Element extends Element {
	public $category       = 'snn';
	public $name           = 'snn-custom-cursor';
	public $icon           = 'ti-cursor';
	public $css_selector   = '.snn-custom-cursor-wrapper';
	public $scripts        = [];
	public $nestable       = false;

	public function get_label() {
		return esc_html__( 'Custom Cursor', 'snn' );
	}

	public function set_controls() {
		// Default Cursor Settings
		$this->controls['default_cursor_size'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Default Cursor Size', 'snn' ),
			'type'    => 'number',
			'default' => 10,
			'min'     => 1,
			'max'     => 50,
			'step'    => 1,
			'unit'    => 'px',
		];

		$this->controls['default_cursor_color'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Default Cursor Color', 'snn' ),
			'type'    => 'color',
			'default' => [
				'hex' => '#000000',
			],
		];

		$this->controls['cursor_speed'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Cursor Speed', 'snn' ),
			'type'    => 'number',
			'default' => 0.125,
			'min'     => 0.01,
			'max'     => 1,
			'step'    => 0.01,
		];

		$this->controls['cursor_z_index'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Cursor Z-Index', 'snn' ),
			'type'    => 'number',
			'default' => 9999,
			'min'     => 1,
			'max'     => 99999,
			'step'    => 1,
		];

		$this->controls['hide_in_builder'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Hide in Bricks Builder', 'snn' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		// Repeater for Hover States
		$this->controls['hover_cursors'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Hover Cursors', 'snn' ),
			'type'          => 'repeater',
			'titleProperty' => 'cursor_name',
			'default'       => [],
			'fields'        => [
				'cursor_name' => [
					'label'   => esc_html__( 'Cursor Name', 'snn' ),
					'type'    => 'text',
					'default' => 'My Cursor',
				],
				'cursor_type' => [
					'label'   => esc_html__( 'Cursor Type', 'snn' ),
					'type'    => 'select',
					'options' => [
						'circle'       => esc_html__( 'Circle with Text', 'snn' ),
						'circle_arrow' => esc_html__( 'Circle with Arrow', 'snn' ),
						'custom'       => esc_html__( 'Custom', 'snn' ),
					],
					'default' => 'circle',
				],
				'circle_size' => [
					'label'    => esc_html__( 'Circle Size', 'snn' ),
					'type'     => 'number',
					'default'  => 140,
					'min'      => 20,
					'max'      => 500,
					'step'     => 1,
					'unit'     => 'px',
					'required' => [ 'cursor_type', '!=', 'custom' ],
				],
				'circle_bg_image' => [
					'label'    => esc_html__( 'Circle Background Image', 'snn' ),
					'type'     => 'image',
					'default'  => '',
					'required' => [ 'cursor_type', '=', 'circle' ],
				],
				'inner_icon_image' => [
					'label'    => esc_html__( 'Inner Icon/Arrow Image', 'snn' ),
					'type'     => 'image',
					'default'  => '',
					'required' => [ 'cursor_type', '=', 'circle_arrow' ],
				],
				'inner_icon_size' => [
					'label'    => esc_html__( 'Inner Icon Size', 'snn' ),
					'type'     => 'number',
					'default'  => 44,
					'min'      => 10,
					'max'      => 200,
					'step'     => 1,
					'unit'     => 'px',
					'required' => [ 'cursor_type', '=', 'circle_arrow' ],
				],
				'rotation_speed' => [
					'label'    => esc_html__( 'Rotation Speed (seconds)', 'snn' ),
					'type'     => 'number',
					'default'  => 20,
					'min'      => 1,
					'max'      => 60,
					'step'     => 1,
					'unit'     => 's',
					'required' => [ 'cursor_type', '!=', 'custom' ],
				],
				'target_selector' => [
					'label'       => esc_html__( 'Target CSS Selector', 'snn' ),
					'type'        => 'text',
					'default'     => '.my-target',
					'description' => esc_html__( 'CSS selector for elements that trigger this cursor (e.g., .project, #brxe-vzibxz)', 'snn' ),
				],
				'custom_html' => [
					'label'       => esc_html__( 'Custom HTML', 'snn' ),
					'type'        => 'editor',
					'default'     => '<div class="custom-cursor-content">Custom</div>',
					'description' => esc_html__( 'Custom HTML for cursor (only used when Cursor Type is "Custom")', 'snn' ),
					'required'    => [ 'cursor_type', '=', 'custom' ],
				],
				'custom_css' => [
					'label'       => esc_html__( 'Custom CSS', 'snn' ),
					'type'        => 'textarea',
					'default'     => '',
					'description' => esc_html__( 'Additional CSS styles for this cursor', 'snn' ),
				],
			],
		];
	}

	public function render() {
		// Get settings
		$default_size  = intval( $this->settings['default_cursor_size'] ?? 10 );
		$cursor_color  = $this->ensure_string( $this->settings['default_cursor_color'] ?? '#000000' );
		$cursor_speed  = floatval( $this->settings['cursor_speed'] ?? 0.125 );
		$z_index       = intval( $this->settings['cursor_z_index'] ?? 9999 );
		$hide_builder  = isset( $this->settings['hide_in_builder'] ) ? $this->settings['hide_in_builder'] : true;
		$hover_cursors = isset( $this->settings['hover_cursors'] ) && is_array( $this->settings['hover_cursors'] )
			? $this->settings['hover_cursors']
			: [];

		// Generate unique ID
		$unique_id = 'snn-cursor-' . uniqid();

		$this->set_attribute( '_root', 'class', 'snn-custom-cursor-wrapper' );
		$this->set_attribute( '_root', 'id', $unique_id );

		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		// Output default cursor
		echo '<div id="' . esc_attr( $unique_id ) . '-default" class="snn-cursor-default"></div>';

		// Output hover cursors
		if ( ! empty( $hover_cursors ) ) {
			foreach ( $hover_cursors as $index => $cursor ) {
				$cursor_id   = $unique_id . '-hover-' . $index;
				$cursor_type = $cursor['cursor_type'] ?? 'circle';
				$cursor_name = $cursor['cursor_name'] ?? 'Cursor ' . ( $index + 1 );

				echo '<div id="' . esc_attr( $cursor_id ) . '" class="snn-cursor-hover snn-cursor-hover-' . esc_attr( $index ) . '" data-cursor-name="' . esc_attr( $cursor_name ) . '">';

				if ( $cursor_type === 'circle' || $cursor_type === 'circle_arrow' ) {
					$circle_size     = intval( $cursor['circle_size'] ?? 140 );
					$circle_bg_image = $cursor['circle_bg_image']['url'] ?? '';
					$inner_icon      = $cursor['inner_icon_image']['url'] ?? '';
					$inner_size      = intval( $cursor['inner_icon_size'] ?? 44 );

					echo '<div class="snn-cursor-circle">';
					if ( $cursor_type === 'circle_arrow' && $inner_icon ) {
						echo '<div class="snn-cursor-inner-icon"></div>';
					}
					echo '</div>';

				} elseif ( $cursor_type === 'custom' ) {
					$custom_html = $cursor['custom_html'] ?? '<div>Custom</div>';
					echo wp_kses_post( $custom_html );
				}

				echo '</div>';
			}
		}

		// CSS Styles
		echo '<style>';

		// Selection color
		echo '::selection { background: gray; color: black; }';

		// Hide in builder template editor
		if ( $hide_builder ) {
			echo '.bricks_template-template-default #' . esc_attr( $unique_id ) . ' { display: none !important; }';
		}

		// Default cursor styles
		echo '#' . esc_attr( $unique_id ) . '-default {
			position: fixed;
			top: 0;
			left: 0;
			z-index: ' . esc_attr( $z_index ) . ';
			width: ' . esc_attr( $default_size ) . 'px;
			height: ' . esc_attr( $default_size ) . 'px;
			border-radius: 50%;
			pointer-events: none;
			background-color: ' . esc_attr( $cursor_color ) . ';
			transform: translate(-200px, -200px);
			transition: opacity 0.3s ease;
		}';

		// Hover cursor base styles
		echo '.snn-cursor-hover {
			position: fixed;
			top: 0;
			left: 0;
			z-index: ' . esc_attr( $z_index ) . ';
			pointer-events: none;
			transform: translate(-200px, -200px);
			opacity: 0;
			visibility: hidden;
			transition: opacity 0.3s ease, visibility 0.3s ease;
		}';

		// Individual hover cursor styles
		if ( ! empty( $hover_cursors ) ) {
			foreach ( $hover_cursors as $index => $cursor ) {
				$cursor_id       = $unique_id . '-hover-' . $index;
				$cursor_type     = $cursor['cursor_type'] ?? 'circle';
				$circle_size     = intval( $cursor['circle_size'] ?? 140 );
				$circle_bg_image = $cursor['circle_bg_image']['url'] ?? '';
				$inner_icon      = $cursor['inner_icon_image']['url'] ?? '';
				$inner_size      = intval( $cursor['inner_icon_size'] ?? 44 );
				$rotation_speed  = intval( $cursor['rotation_speed'] ?? 20 );
				$custom_css      = $cursor['custom_css'] ?? '';

				echo '#' . esc_attr( $cursor_id ) . ' {
					width: ' . esc_attr( $circle_size ) . 'px;
					height: ' . esc_attr( $circle_size ) . 'px;
				}';

				if ( $cursor_type === 'circle' || $cursor_type === 'circle_arrow' ) {
					echo '#' . esc_attr( $cursor_id ) . ' .snn-cursor-circle {
						width: ' . esc_attr( $circle_size ) . 'px;
						height: ' . esc_attr( $circle_size ) . 'px;
						display: flex;
						justify-content: center;
						align-items: center;
					}';

					if ( $circle_bg_image ) {
						echo '#' . esc_attr( $cursor_id ) . ' .snn-cursor-circle {
							background: url(' . esc_url( $circle_bg_image ) . ') no-repeat center center;
							background-size: contain;
							animation: rotate-cursor-' . esc_attr( $index ) . ' ' . esc_attr( $rotation_speed ) . 's infinite linear;
						}';

						// Rotation animation
						echo '@keyframes rotate-cursor-' . esc_attr( $index ) . ' {
							from { transform: rotate(0deg); }
							to { transform: rotate(-360deg); }
						}';
					}

					if ( $cursor_type === 'circle_arrow' && $inner_icon ) {
						echo '#' . esc_attr( $cursor_id ) . ' .snn-cursor-inner-icon {
							width: ' . esc_attr( $inner_size ) . 'px;
							height: ' . esc_attr( $inner_size ) . 'px;
							display: block;
							background: url(' . esc_url( $inner_icon ) . ') no-repeat center center;
							background-size: contain;
							animation: rotate-cursor-reverse-' . esc_attr( $index ) . ' ' . esc_attr( $rotation_speed ) . 's infinite linear;
						}';

						// Reverse rotation animation
						echo '@keyframes rotate-cursor-reverse-' . esc_attr( $index ) . ' {
							from { transform: rotate(0deg); }
							to { transform: rotate(360deg); }
						}';
					}
				}

				// Custom CSS
				if ( ! empty( $custom_css ) ) {
					echo wp_kses_post( $custom_css );
				}
			}
		}

		echo '</style>';

		// JavaScript - Cotton library + initialization
		echo '<script>';

		// Cotton.js library (minified)
		echo '!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e="undefined"!=typeof globalThis?globalThis:e||self).Cotton=t()}(this,(function(){"use strict";function e(t){return(e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(t)}function t(e,t){for(var n=0;n<t.length;n++){var a=t[n];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}function n(e){return function(e){if(Array.isArray(e))return a(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(!e)return;if("string"==typeof e)return a(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);"Object"===n&&e.constructor&&(n=e.constructor.name);if("Map"===n||"Set"===n)return Array.from(e);if("Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n))return a(e,t)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function a(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,a=new Array(t);n<t;n++)a[n]=e[n];return a}function o(e){console.error("[Cotton warn]: ".concat(e))}function r(e){var t=e.getBoundingClientRect(),n=document.body.getBoundingClientRect();return{width:t.width,height:t.height,centerX:t.left-n.left+t.width/2-s(e),centerY:t.top-n.top+t.height/2-i(e)}}function s(e){var t=getComputedStyle(e).transform,n=t.match(/^matrix3d((.+))$/);return n?parseFloat(n[1].split(", ")[12]):(n=t.match(/^matrix((.+))$/))?parseFloat(n[1].split(", ")[4]):0}function i(e){var t=getComputedStyle(e).transform,n=t.match(/^matrix3d((.+))$/);return n?parseFloat(n[1].split(", ")[13]):(n=t.match(/^matrix((.+))$/))?parseFloat(n[1].split(", ")[5]):0}function l(e){var t=e.element,n=e.params,a=n.data;a.x&&a.y?(a.dx=(a.mouseX-a.x)*n.speed,a.dy=(a.mouseY-a.y)*n.speed,Math.abs(a.dx)+Math.abs(a.dy)<.1?(a.x=a.mouseX,a.y=a.mouseY):(a.x+=a.dx,a.y+=a.dy)):(a.x=a.mouseX,a.y=a.mouseY),a.animationFrame=requestAnimationFrame((function(){l(e)})),n.centerMouse?t.style.transform="translate(calc(-50% + ".concat(a.x,"px), calc(-50% + ").concat(a.y,"px))"):t.style.transform="translate(".concat(a.x,"px, ").concat(a.y,"px)")}function c(e){var t=e.element,n=e.params,a=n.data,o=n.airMode;a.distanceX&&a.distanceY?(a.dx=(a.distanceX-a.x)*n.speed,a.dy=(a.distanceY-a.y)*n.speed,Math.abs(a.dx)+Math.abs(a.dy)<.1?(a.x=a.distanceX,a.y=a.distanceY):(a.x+=a.dx,a.y+=a.dy)):(a.x=a.distanceX,a.y=a.distanceY),a.animationFrame=requestAnimationFrame((function(){c(e)}));var r=o.reverse?-a.x:a.x,s=o.reverse?-a.y:a.y,i="number"==typeof a.transformX?a.transformX+"px":a.transformX,l="number"==typeof a.transformY?a.transformY+"px":a.transformY,d=a.transformX?"calc(".concat(i," + ").concat(Math.floor(r/o.resistance),"px)"):"".concat(Math.floor(r/o.resistance),"px"),m=a.transformY?"calc(".concat(l," + ").concat(Math.floor(s/o.resistance),"px)"):"".concat(Math.floor(s/o.resistance),"px");t.style.transform="translate(".concat(d,", ").concat(m,")")}function d(e,t){if(0!==e.models.length){var n=e.models;if(t)n.forEach((function(t){t.addEventListener("mouseenter",e.enterModelHandler),t.addEventListener("mouseleave",e.leaveModelHandler)}));else{n.forEach((function(t){t.removeEventListener("mouseenter",e.enterModelHandler),t.removeEventListener("mouseleave",e.leaveModelHandler)}));var a=document.querySelectorAll(e.params.models);a.forEach((function(t){t.addEventListener("mouseenter",e.enterModelHandler),t.addEventListener("mouseleave",e.leaveModelHandler)})),e.models=a}}}return function(){function a(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,a);var r,s={data:{mouseX:null,mouseY:null,distanceX:null,distanceY:null,x:null,y:null,dx:null,dy:null,animationFrame:void 0},scene:"body",conttonInitClass:"cotton-init",cottonMovingClass:"cotton-moving",cottonActiveClass:"cotton-active",models:".cotton-model",modelsActiveClass:"model-active",centerMouse:!0,speed:.125,airMode:!1,on:{enterModel:null,leaveModel:null,enterScene:null,leaveScene:null,cottonMove:null}};if(this.element=t instanceof Element?t:document.querySelector(t),this.params=Object.assign({},s,n),this.scene=this.params.scene instanceof Element?this.params.scene:document.querySelector(this.params.scene),this.models=NodeList.prototype.isPrototypeOf(this.params.models)?this.params.models:document.querySelectorAll(this.params.models),this.enterModelHandler=this.enterModelHandler.bind(this),this.leaveModelHandler=this.leaveModelHandler.bind(this),!this.element)return o("Cannot define a cotton element which is not exist");if(!this.scene)return o("Cannot define a scene which is not exist");if((this.params.speed>1||this.params.speed<=0)&&(this.params.speed=.125),this.params.airMode){var i=this.params.airMode,l={resistance:15,reverse:!1,alive:!1};"object"!==e(i)||Array.isArray(i)?this.params.airMode=l:this.params.airMode=Object.assign(l,i),(i.resistance<1||i.resistance>100)&&(i.resistance=15)}(r=navigator.userAgent).indexOf("Android")>-1||r.indexOf("Adr"),r.indexOf("Mac")>-1&&"ontouchend"in document||a.init(this)}var m,u,f;return m=a,f=[{key:"getMouseData",value:function(e){var t=e.element,a=e.scene,o=e.params,l=o.data,c=o.airMode;a.addEventListener("mousemove",(function(a){l.mouseX=c?a.pageX:a.clientX,l.mouseY=c?a.pageY:a.clientY,n(t.classList).indexOf(o.conttonInitClass)>-1&&t.classList.add(o.cottonMovingClass),o.on.cottonMove&&"function"==typeof o.on.cottonMove&&o.on.cottonMove.call(e,t,a)})),c&&(c.alive||(l.rect=r(t),l.transformX=s(t),l.transformY=i(t),window.addEventListener("resize",(function(){l.rect=r(t),l.transformX=s(t),l.transformY=i(t)}))),a.addEventListener("mousemove",(function(){c.alive&&(l.rect=r(t));var e=window.innerWidth+l.rect.width/2,n=window.innerHeight+l.rect.height/2,a=l.mouseX-l.rect.centerX,o=l.mouseY-l.rect.centerY;l.distanceX=Math.min(Math.max(parseInt(a),-e),e),l.distanceY=Math.min(Math.max(parseInt(o),-n),n)})))}},{key:"init",value:function(e){var t=e.element,n=e.params,o=e.scene;o.addEventListener("mouseenter",(function(a){n.on.enterScene&&"function"==typeof n.on.enterScene&&n.on.enterScene.call(e,t,o,a)})),o.addEventListener("mouseleave",(function(a){t.classList.remove(n.cottonMovingClass),n.on.leaveScene&&"function"==typeof n.on.leaveScene&&n.on.leaveScene.call(e,t,o,a)})),a.getMouseData(e,!0),e.move(),d(e,!0)}}],(u=[{key:"enterModelHandler",value:function(e){var t=this.element,n=this.params;n.on.enterModel&&"function"==typeof n.on.enterModel&&n.on.enterModel.call(this,t,e.target,e),t.classList.add(n.cottonActiveClass),e.target.classList.add(n.modelsActiveClass)}},{key:"leaveModelHandler",value:function(e){var t=this.element,n=this.params;n.on.leaveModel&&"function"==typeof n.on.leaveModel&&n.on.leaveModel.call(this,t,e.target,e),t.classList.remove(n.cottonActiveClass),e.target.classList.remove(n.modelsActiveClass)}},{key:"move",value:function(){var e=this.params.data,t=this.params.airMode;this.element.classList.add(this.params.conttonInitClass),e.animationFrame||(t?c(this):l(this))}},{key:"stop",value:function(){var e=this.params.data;this.element.classList.remove(this.params.conttonInitClass),this.element.classList.remove(this.params.cottonMovingClass),cancelAnimationFrame(e.animationFrame),e.animationFrame=void 0}},{key:"updateModels",value:function(){d(this,!1)}}])&&t(m.prototype,u),f&&t(m,f),a}()}));';

		// Configuration and initialization
		echo '(function() {
			const defaultCursor = document.querySelector("#' . esc_js( $unique_id ) . '-default");

			if (!defaultCursor) return;

			// Initialize default cursor
			new Cotton(defaultCursor, {
				speed: ' . esc_js( $cursor_speed ) . '
			});
			';

		// Initialize hover cursors
		if ( ! empty( $hover_cursors ) ) {
			echo 'const cursorConfig = {';

			foreach ( $hover_cursors as $index => $cursor ) {
				$cursor_id       = $unique_id . '-hover-' . $index;
				$target_selector = $cursor['target_selector'] ?? '.target-' . $index;

				echo 'cursor_' . esc_js( $index ) . ': {
					element: document.querySelector("#' . esc_js( $cursor_id ) . '"),
					targets: "' . esc_js( $target_selector ) . '"
				},';
			}

			echo '};';

			echo 'Object.values(cursorConfig).forEach(function(config) {
				if (!config.element) return;

				// Initialize Cotton for each hover cursor
				new Cotton(config.element, {
					speed: ' . esc_js( $cursor_speed ) . '
				});

				// Setup hover interactions
				new Cotton(defaultCursor, {
					models: config.targets,
					speed: ' . esc_js( $cursor_speed ) . ',
					on: {
						enterModel: function(cursorEl) {
							config.element.style.opacity = "1";
							config.element.style.visibility = "visible";
							cursorEl.style.opacity = "0";
						},
						leaveModel: function(cursorEl) {
							config.element.style.opacity = "0";
							config.element.style.visibility = "hidden";
							cursorEl.style.opacity = "1";
						}
					}
				});
			});';
		}

		echo '})();';
		echo '</script>';

		echo '</div>';
	}

	/**
	 * Helper method to ensure color value is a string
	 */
	private function ensure_string( $value ) {
		if ( is_array( $value ) ) {
			if ( isset( $value['hex'] ) ) {
				return $value['hex'];
			}
			if ( isset( $value['raw'] ) ) {
				return $value['raw'];
			}
			return implode( ' ', $value );
		} elseif ( is_scalar( $value ) ) {
			return (string) $value;
		}
		return '';
	}
}
