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
			'titleProperty' => 'cursor_selector',
			'default'       => [],
			'fields'        => [
				'cursor_selector' => [
					'label'       => esc_html__( 'Cursor Selector', 'snn' ),
					'type'        => 'text',
					'default'     => '.my-cursor',
					'description' => esc_html__( 'CSS selector for the DOM element that will BE the cursor (e.g., .project-hover, .karriere-hover)', 'snn' ),
				],
				'target_hover_selector' => [
					'label'       => esc_html__( 'Target Hover Selector', 'snn' ),
					'type'        => 'text',
					'default'     => '.my-target',
					'description' => esc_html__( 'CSS selector for elements that when hovered will show this cursor (e.g., .project, #brxe-vzibxz)', 'snn' ),
				],
				'cursor_speed' => [
					'label'   => esc_html__( 'Cursor Speed', 'snn' ),
					'type'    => 'number',
					'default' => 0.125,
					'min'     => 0.01,
					'max'     => 1,
					'step'    => 0.01,
				],
				'cursor_x_position' => [
					'label'       => esc_html__( 'Cursor X Position', 'snn' ),
					'type'        => 'number',
					'default'     => 0,
					'description' => esc_html__( 'Horizontal offset from center in pixels', 'snn' ),
				],
				'cursor_y_position' => [
					'label'       => esc_html__( 'Cursor Y Position', 'snn' ),
					'type'        => 'number',
					'default'     => 0,
					'description' => esc_html__( 'Vertical offset from center in pixels', 'snn' ),
				],
			],
		];
	}

	public function render() {
		// Get settings
		$default_size     = intval( $this->settings['default_cursor_size'] ?? 10 );
		$cursor_color     = $this->ensure_string( $this->settings['default_cursor_color'] ?? '#000000' );
		$default_speed    = floatval( $this->settings['cursor_speed'] ?? 0.125 );
		$z_index          = intval( $this->settings['cursor_z_index'] ?? 9999 );
		$hide_builder     = isset( $this->settings['hide_in_builder'] ) ? $this->settings['hide_in_builder'] : true;
		$hover_cursors    = isset( $this->settings['hover_cursors'] ) && is_array( $this->settings['hover_cursors'] )
			? $this->settings['hover_cursors']
			: [];

		// Generate unique ID
		$unique_id = 'snn-cursor-' . uniqid();

		$this->set_attribute( '_root', 'class', 'snn-custom-cursor-wrapper' );
		$this->set_attribute( '_root', 'id', $unique_id );

		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		// Output default cursor
		echo '<div id="' . esc_attr( $unique_id ) . '-default" class="snn-cursor-default"></div>';

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
				speed: ' . esc_js( $default_speed ) . '
			});
			';

		// Initialize hover cursors using existing DOM elements
		if ( ! empty( $hover_cursors ) ) {
			echo 'const cursorConfig = [';

			foreach ( $hover_cursors as $index => $cursor ) {
				$cursor_selector       = $cursor['cursor_selector'] ?? '';
				$target_hover_selector = $cursor['target_hover_selector'] ?? '';
				$cursor_speed          = floatval( $cursor['cursor_speed'] ?? 0.125 );
				$cursor_x_position     = floatval( $cursor['cursor_x_position'] ?? 0 );
				$cursor_y_position     = floatval( $cursor['cursor_y_position'] ?? 0 );

				if ( empty( $cursor_selector ) || empty( $target_hover_selector ) ) {
					continue;
				}

				echo '{
					cursorSelector: "' . esc_js( $cursor_selector ) . '",
					targetSelector: "' . esc_js( $target_hover_selector ) . '",
					speed: ' . esc_js( $cursor_speed ) . ',
					offsetX: ' . esc_js( $cursor_x_position ) . ',
					offsetY: ' . esc_js( $cursor_y_position ) . '
				},';
			}

			echo '];';

			echo 'cursorConfig.forEach(function(config) {
				const cursorElement = document.querySelector(config.cursorSelector);

				if (!cursorElement) {
					console.warn("Cursor element not found or could not be selected. Move this element as latest on footer !: " + config.cursorSelector);
					return;
				}

				// Fix positioning for Cotton.js - must match default cursor positioning
				cursorElement.style.position = "fixed";
				cursorElement.style.top = "0";
				cursorElement.style.left = "0";
				cursorElement.style.pointerEvents = "none";
				cursorElement.style.zIndex = "' . esc_js( $z_index ) . '";

				// Set initial hidden state for hover cursors
				cursorElement.style.opacity = "0";
				cursorElement.style.visibility = "hidden";

				// Store original transform for offset calculation
				const originalTransform = window.getComputedStyle(cursorElement).transform;

				// Initialize Cotton for the cursor element with centerMouse enabled
				new Cotton(cursorElement, {
					speed: config.speed,
					centerMouse: true,
					on: {
						cottonMove: function(element) {
							// Apply offset if specified
							if (config.offsetX !== 0 || config.offsetY !== 0) {
								const currentTransform = element.style.transform;
								// Extract the translate values and add offset
								const match = currentTransform.match(/translate\(calc\(-50% \+ (.+?)px\), calc\(-50% \+ (.+?)px\)\)/);
								if (match) {
									const x = parseFloat(match[1]) + config.offsetX;
									const y = parseFloat(match[2]) + config.offsetY;
									element.style.transform = "translate(calc(-50% + " + x + "px), calc(-50% + " + y + "px))";
								}
							}
						}
					}
				});

				// Setup hover interactions
				new Cotton(defaultCursor, {
					models: config.targetSelector,
					speed: config.speed,
					on: {
						enterModel: function(cursorEl) {
							cursorElement.style.opacity = "1";
							cursorElement.style.visibility = "visible";
							cursorEl.style.opacity = "0";
						},
						leaveModel: function(cursorEl) {
							cursorElement.style.opacity = "0";
							cursorElement.style.visibility = "hidden";
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
