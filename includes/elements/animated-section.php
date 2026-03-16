<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Bricks\Frontend;

class Snn_Animated_Section extends \Bricks\Element {
	public $category     = 'snn';
	public $name         = 'snn-animated-section';
	public $icon         = 'ti-layout-grid2';
	public $css_selector = '.snn-animated-section-wrap';
	public $scripts      = [];
	public $nestable     = true;

	public function get_label() {
		return esc_html__( 'Animated Section', 'snn' );
	}

	public function set_control_groups() {
		$this->control_groups['shader'] = [
			'title' => esc_html__( 'Shader Settings', 'snn' ),
			'tab'   => 'content',
		];
		if ( get_option( 'snn_ai_enabled', 'no' ) === 'yes' ) {
			$this->control_groups['ai_shader'] = [
				'title' => esc_html__( 'AI Shader Generator', 'snn' ),
				'tab'   => 'content',
			];
		}
	}

	public function set_controls() {

		// ─── Preset ───────────────────────────────────────────────────────────
		$this->controls['preset'] = [
			'tab'     => 'content',
			'group'   => 'shader',
			'label'   => esc_html__( 'Animation Preset', 'snn' ),
			'type'    => 'select',
			'options' => [
				'waves'        => esc_html__( 'Waves', 'snn' ),
				'aurora'       => esc_html__( 'Aurora', 'snn' ),
				'plasma'       => esc_html__( 'Plasma', 'snn' ),
				'gradientflow' => esc_html__( 'Gradient Flow', 'snn' ),
				'noise'        => esc_html__( 'Noise Field', 'snn' ),
				'ripple'       => esc_html__( 'Ripple', 'snn' ),
				'stars'        => esc_html__( 'Stars', 'snn' ),
				'vortex'       => esc_html__( 'Vortex', 'snn' ),
				'fire'         => esc_html__( 'Fire', 'snn' ),
				'gridpulse'    => esc_html__( 'Grid Pulse', 'snn' ),
				'custom'       => esc_html__( 'Custom Shader', 'snn' ),
			],
			'default' => 'waves',
		];

		// ─── Speed ────────────────────────────────────────────────────────────
		$this->controls['speed'] = [
			'tab'     => 'content',
			'group'   => 'shader',
			'label'   => esc_html__( 'Speed', 'snn' ),
			'type'    => 'number',
			'min'     => 0.1,
			'max'     => 10.0,
			'step'    => 0.1,
			'default' => 1.0,
		];

		// ─── Primary Color ────────────────────────────────────────────────────
		$this->controls['color1'] = [
			'tab'     => 'content',
			'group'   => 'shader',
			'label'   => esc_html__( 'Primary Color', 'snn' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#1a6cf5', 'rgb' => 'rgba(26,108,245,1)' ],
		];

		// ─── Secondary Color ──────────────────────────────────────────────────
		$this->controls['color2'] = [
			'tab'     => 'content',
			'group'   => 'shader',
			'label'   => esc_html__( 'Secondary Color', 'snn' ),
			'type'    => 'color',
			'default' => [ 'hex' => '#030a1a', 'rgb' => 'rgba(3,10,26,1)' ],
		];

		// ─── Opacity ──────────────────────────────────────────────────────────
		$this->controls['opacity'] = [
			'tab'     => 'content',
			'group'   => 'shader',
			'label'   => esc_html__( 'Shader Opacity', 'snn' ),
			'type'    => 'number',
			'min'     => 0,
			'max'     => 1,
			'step'    => 0.01,
			'default' => 1.0,
		];

		// ─── Blend Mode ───────────────────────────────────────────────────────
		$this->controls['blend_mode'] = [
			'tab'     => 'content',
			'group'   => 'shader',
			'label'   => esc_html__( 'Blend Mode', 'snn' ),
			'type'    => 'select',
			'options' => [
				'normal'     => esc_html__( 'Normal', 'snn' ),
				'screen'     => esc_html__( 'Screen', 'snn' ),
				'overlay'    => esc_html__( 'Overlay', 'snn' ),
				'multiply'   => esc_html__( 'Multiply', 'snn' ),
				'soft-light' => esc_html__( 'Soft Light', 'snn' ),
			],
			'default' => 'normal',
		];

		// ─── Custom GLSL Shader ───────────────────────────────────────────────
		$default_glsl = 'precision mediump float;
uniform float u_time;
uniform vec2 u_resolution;
uniform vec3 u_color1;
uniform vec3 u_color2;
uniform float u_speed;
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float f = sin(uv.x * 10.0 + u_time * u_speed) * 0.5 + 0.5;
    f += sin(uv.y * 8.0 - u_time * u_speed * 0.7) * 0.3;
    f = clamp(f, 0.0, 1.0);
    gl_FragColor = vec4(mix(u_color1, u_color2, f), 1.0);
}';

		$this->controls['custom_shader'] = [
			'tab'         => 'content',
			'group'       => 'shader',
			'label'       => esc_html__( 'GLSL Fragment Shader', 'snn' ),
			'type'        => 'code',
			'mode'        => 'c',
			'default'     => $default_glsl,
			'required'    => [ [ 'preset', '=', 'custom' ] ],
			'description' => esc_html__( 'Available uniforms: u_time (float), u_resolution (vec2), u_color1 (vec3), u_color2 (vec3), u_speed (float). Must write gl_FragColor.', 'snn' ),
		];

		// ─── AI Shader Generator ─ only registered when AI is enabled ─────────
		if ( get_option( 'snn_ai_enabled', 'no' ) === 'yes' ) {
			$this->controls['ai_shader_prompt'] = [
				'tab'         => 'content',
				'group'       => 'ai_shader',
				'label'       => esc_html__( 'Shader Description', 'snn' ),
				'type'        => 'textarea',
				'rows'        => 3,
				'placeholder' => 'e.g. Animated northern lights with green and purple tones, soft flowing curtains...',
				'required'    => [ [ 'preset', '=', 'custom' ] ],
				'description' => '<button class="snn-animated-section-ai-gen-btn" type="button" style="margin-top:8px;padding:6px 14px;background:#6366f1;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;display:block;width:100%;text-align:center;">&#10022; Generate Shader with AI</button><span class="snn-animated-section-ai-status" style="display:block;margin-top:4px;font-size:11px;min-height:16px;opacity:0.75;"></span>',
			];
		}
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	/**
	 * Parse a Bricks color control value to a normalised [r, g, b] array (0–1).
	 */
	private function get_color_vec3( $key, $default = [ 1.0, 1.0, 1.0 ] ) {
		if ( empty( $this->settings[ $key ] ) ) return $default;
		$color = $this->settings[ $key ];

		if ( ! empty( $color['rgb'] ) ) {
			preg_match( '/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $color['rgb'], $m );
			if ( count( $m ) >= 4 ) {
				return [ round( $m[1] / 255, 5 ), round( $m[2] / 255, 5 ), round( $m[3] / 255, 5 ) ];
			}
		}
		if ( ! empty( $color['hex'] ) ) {
			$hex = ltrim( $color['hex'], '#' );
			if ( strlen( $hex ) === 6 ) {
				return [
					round( hexdec( substr( $hex, 0, 2 ) ) / 255, 5 ),
					round( hexdec( substr( $hex, 2, 2 ) ) / 255, 5 ),
					round( hexdec( substr( $hex, 4, 2 ) ) / 255, 5 ),
				];
			}
		}
		return $default;
	}

	/**
	 * Returns the built-in GLSL fragment shader for a preset key.
	 * Every shader exposes the same uniform interface:
	 *   u_time (float), u_resolution (vec2), u_color1 (vec3), u_color2 (vec3), u_speed (float)
	 */
	private function get_preset_shader( $preset ) {
		$shaders = [

			// 1 ─ Waves
			'waves' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed;
    float w = sin(uv.x * 8.0 + t)          * 0.12
            + sin(uv.x * 5.0 - t*0.9+1.5) * 0.08
            + sin(uv.x * 13.0 + t*1.4+0.7)* 0.05;
    float f = smoothstep(0.4, 0.6, uv.y + w);
    gl_FragColor = vec4(mix(u_color1, u_color2, f), 1.0);
}',

			// 2 ─ Aurora
			'aurora' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed * 0.25;
    float a = sin(uv.x * 3.0 + t)             * 0.5 + 0.5;
    float b = sin(uv.x * 5.5 - t*1.2 + 1.2)  * 0.5 + 0.5;
    float c = sin(uv.x * 2.0 + t*0.7  + 2.4) * 0.5 + 0.5;
    float band = (a + b + c) / 3.0;
    float curtain = exp(-abs(uv.y - (0.5 + band * 0.3)) * 9.0);
    curtain *= smoothstep(0.0, 0.3, 1.0 - uv.y);
    vec3 col = mix(u_color1, u_color2, band) * curtain * 2.5;
    col += mix(u_color2, u_color1, uv.y) * 0.12;
    gl_FragColor = vec4(clamp(col, 0.0, 1.0), 1.0);
}',

			// 3 ─ Plasma
			'plasma' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed;
    float v  = sin(uv.x * 10.0 + t);
    v += sin(uv.y * 10.0 + t * 0.9);
    v += sin((uv.x + uv.y) * 8.0 + t * 1.1);
    float cx = uv.x - 0.5 + sin(t * 0.4) * 0.3;
    float cy = uv.y - 0.5 + cos(t * 0.5) * 0.3;
    v += sin(sqrt(cx*cx + cy*cy) * 12.0 - t);
    float f = (sin(v * 1.5707963) + 1.0) * 0.5;
    gl_FragColor = vec4(mix(u_color1, u_color2, f), 1.0);
}',

			// 4 ─ Gradient Flow
			'gradientflow' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed * 0.18;
    float f = uv.x * 0.5 + uv.y * 0.5
            + sin(t) * 0.25
            + sin(uv.x * 3.14159 + t * 1.3) * 0.12
            + sin(uv.y * 3.14159 - t * 0.8) * 0.10;
    f = clamp(f, 0.0, 1.0);
    gl_FragColor = vec4(mix(u_color1, u_color2, f), 1.0);
}',

			// 5 ─ Noise Field
			'noise' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
float h(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7))) * 43758.5453); }
float n(vec2 p){
    vec2 i = floor(p); vec2 f = fract(p);
    f = f * f * (3.0 - 2.0*f);
    return mix(mix(h(i),h(i+vec2(1,0)),f.x), mix(h(i+vec2(0,1)),h(i+vec2(1,1)),f.x), f.y);
}
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed * 0.12;
    float v  = n(uv * 3.5 + t);
    v += n(uv * 7.0  - t*1.3 + 4.0) * 0.5;
    v += n(uv * 14.0 + t*0.6 + 8.0) * 0.25;
    v /= 1.75;
    gl_FragColor = vec4(mix(u_color1, u_color2, v), 1.0);
}',

			// 6 ─ Ripple
			'ripple' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = (gl_FragCoord.xy / u_resolution) * 2.0 - 1.0;
    uv.x *= u_resolution.x / u_resolution.y;
    float t = u_time * u_speed;
    float d = length(uv);
    float r = sin(d * 14.0 - t * 3.5) * 0.5 + 0.5;
    float falloff = 1.0 / (d * 2.5 + 0.6);
    float f = clamp(r * falloff, 0.0, 1.0);
    gl_FragColor = vec4(mix(u_color1, u_color2, f), 1.0);
}',

			// 7 ─ Stars
			'stars' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
float h(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7))) * 43758.5453); }
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed * 0.04;
    vec2 p = uv + vec2(0.0, t);
    vec2 g = floor(p * 55.0);
    vec2 f = fract(p * 55.0) - 0.5;
    float rnd = h(g);
    float twinkle = sin(u_time * u_speed * (rnd * 3.5 + 0.8)) * 0.35 + 0.65;
    float d = length(f);
    float bright = smoothstep(0.28 * rnd, 0.0, d) * twinkle * rnd;
    vec3 col = mix(u_color1, u_color2, rnd) * bright;
    col += u_color2 * 0.07;
    gl_FragColor = vec4(clamp(col, 0.0, 1.0), 1.0);
}',

			// 8 ─ Vortex
			'vortex' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = (gl_FragCoord.xy / u_resolution) * 2.0 - 1.0;
    uv.x *= u_resolution.x / u_resolution.y;
    float t = u_time * u_speed * 0.28;
    float angle = atan(uv.y, uv.x) + t;
    float dist  = length(uv);
    float spiral = sin(angle * 5.0 + dist * 9.0 - t * 2.2) * 0.5 + 0.5;
    float falloff = exp(-dist * 1.2);
    float f = spiral * falloff + (1.0 - falloff) * 0.15;
    gl_FragColor = vec4(mix(u_color1, u_color2, f), 1.0);
}',

			// 9 ─ Fire
			'fire' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
float h(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7))) * 43758.5453); }
float n(vec2 p){
    vec2 i = floor(p); vec2 f = fract(p);
    f = f * f * (3.0 - 2.0*f);
    return mix(mix(h(i),h(i+vec2(1,0)),f.x), mix(h(i+vec2(0,1)),h(i+vec2(1,1)),f.x), f.y);
}
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed * 0.38;
    vec2 q = vec2(n(uv*3.0 + vec2(0.0,-t)), n(uv*3.0 + vec2(1.7,-t*0.95)));
    float f = n(uv * 4.5 + q * 1.2 + vec2(-t*0.4, 0.0));
    f = f * (1.05 - uv.y * 1.1);
    f = smoothstep(0.0, 0.75, f);
    gl_FragColor = vec4(mix(u_color2, u_color1, f), 1.0);
}',

			// 10 ─ Grid Pulse
			'gridpulse' => 'precision mediump float;
uniform float u_time; uniform vec2 u_resolution;
uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed;
void main() {
    vec2 uv = gl_FragCoord.xy / u_resolution;
    float t = u_time * u_speed;
    vec2 grid = uv * 18.0;
    vec2 g = floor(grid);
    vec2 f = fract(grid);
    float dist  = length(g - vec2(9.0));
    float pulse = sin(dist * 1.4 - t * 2.0) * 0.5 + 0.5;
    float line  = max(step(0.92, f.x), step(0.92, f.y));
    float dot_  = 1.0 - smoothstep(0.0, 0.38, length(f - 0.5));
    float mask  = max(line * 0.6, dot_ * pulse);
    vec3 col = mix(u_color2, u_color1 * (pulse * 0.7 + 0.3), mask);
    gl_FragColor = vec4(col, 1.0);
}',

		];

		return isset( $shaders[ $preset ] ) ? $shaders[ $preset ] : $shaders['waves'];
	}

	// ── Render ───────────────────────────────────────────────────────────────

	public function render() {
		static $ai_builder_script_printed = false;

		$settings   = $this->settings;
		$preset     = $settings['preset']     ?? 'waves';
		$speed      = isset( $settings['speed'] )   ? floatval( $settings['speed'] )   : 1.0;
		$opacity    = isset( $settings['opacity'] )  ? floatval( $settings['opacity'] )  : 1.0;
		$blend_mode = sanitize_key( $settings['blend_mode'] ?? 'normal' );

		$color1 = $this->get_color_vec3( 'color1', [ 0.102, 0.424, 0.961 ] );
		$color2 = $this->get_color_vec3( 'color2', [ 0.012, 0.039, 0.102 ] );

		// Choose fragment shader source
		if ( $preset === 'custom' && ! empty( $settings['custom_shader'] ) ) {
			$frag_src = $settings['custom_shader'];
		} else {
			$frag_src = $this->get_preset_shader( $preset );
		}

		$unique_id = 'snn-as-' . uniqid();
		$this->set_attribute( '_root', 'class', [ 'snn-animated-section-wrap' ] );
		$this->set_attribute( '_root', 'id', $unique_id );

		$c1_js = implode( ', ', $color1 );
		$c2_js = implode( ', ', $color2 );
		?>
		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<canvas class="snn-as-canvas"
				id="<?php echo esc_attr( $unique_id ); ?>-canvas"
				aria-hidden="true"></canvas>
			<div class="snn-as-content">
				<?php echo Frontend::render_children( $this ); ?>
			</div>
		</div>
		<style>
		#<?php echo $unique_id; ?> {
			position: relative;
			overflow: hidden;
		}
		#<?php echo $unique_id; ?> .snn-as-canvas {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			display: block;
			z-index: 0;
			opacity: <?php echo esc_attr( $opacity ); ?>;
			mix-blend-mode: <?php echo esc_attr( $blend_mode ); ?>;
			pointer-events: none;
		}
		#<?php echo $unique_id; ?> .snn-as-content {
			position: relative;
			z-index: 1;
		}
		</style>
		<script>
		(function() {
			var VS = 'attribute vec2 a_position; void main(){gl_Position=vec4(a_position,0.0,1.0);}';
			var FS    = <?php echo wp_json_encode( $frag_src ); ?>;
			var speed  = <?php echo (float) $speed; ?>;
			var color1 = [<?php echo esc_js( $c1_js ); ?>];
			var color2 = [<?php echo esc_js( $c2_js ); ?>];

			function initWebGL() {
				var container = document.getElementById(<?php echo wp_json_encode( $unique_id ); ?>);
				if (!container) { requestAnimationFrame(initWebGL); return; }
				var canvas = document.getElementById(<?php echo wp_json_encode( $unique_id . '-canvas' ); ?>);
				if (!canvas) return;

				var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
				if (!gl) { canvas.style.display = 'none'; return; }

				function compileShader(type, src) {
					var s = gl.createShader(type);
					gl.shaderSource(s, src);
					gl.compileShader(s);
					if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
						console.warn('[AnimatedSection] Shader error:', gl.getShaderInfoLog(s));
						return null;
					}
					return s;
				}

				var vs = compileShader(gl.VERTEX_SHADER,   VS);
				var fs = compileShader(gl.FRAGMENT_SHADER, FS);
				if (!vs || !fs) return;

				var prog = gl.createProgram();
				gl.attachShader(prog, vs);
				gl.attachShader(prog, fs);
				gl.linkProgram(prog);
				if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
					console.warn('[AnimatedSection] Link error:', gl.getProgramInfoLog(prog));
					return;
				}
				gl.useProgram(prog);

				var buf = gl.createBuffer();
				gl.bindBuffer(gl.ARRAY_BUFFER, buf);
				gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, 1,1]), gl.STATIC_DRAW);

				var posLoc = gl.getAttribLocation(prog, 'a_position');
				gl.enableVertexAttribArray(posLoc);
				gl.vertexAttribPointer(posLoc, 2, gl.FLOAT, false, 0, 0);

				var uTime = gl.getUniformLocation(prog, 'u_time');
				var uRes  = gl.getUniformLocation(prog, 'u_resolution');
				var uC1   = gl.getUniformLocation(prog, 'u_color1');
				var uC2   = gl.getUniformLocation(prog, 'u_color2');
				var uSpd  = gl.getUniformLocation(prog, 'u_speed');

				var t0 = performance.now();

				function resize() {
					var w = container.clientWidth  || 1;
					var h = container.clientHeight || 1;
					if (canvas.width !== w || canvas.height !== h) {
						canvas.width  = w;
						canvas.height = h;
						gl.viewport(0, 0, w, h);
					}
				}

				function draw() {
					resize();
					var t = (performance.now() - t0) / 1000.0;
					if (uTime) gl.uniform1f(uTime, t);
					if (uRes)  gl.uniform2f(uRes, canvas.width, canvas.height);
					if (uC1)   gl.uniform3fv(uC1, color1);
					if (uC2)   gl.uniform3fv(uC2, color2);
					if (uSpd)  gl.uniform1f(uSpd, speed);
					gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
					requestAnimationFrame(draw);
				}

				resize();
				draw();

				if (window.ResizeObserver) {
					new ResizeObserver(resize).observe(container);
				}
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initWebGL);
			} else {
				initWebGL();
			}
		})();
		</script>
		<?php

		// ── Builder-only AI shader generation script ──────────────────────────
		// Only injected once per page, only when Bricks builder is active,
		// and only when "Enable AI Features" is turned on in SNN AI Settings.
		if (
			function_exists( 'bricks_is_builder' ) && bricks_is_builder() &&
			get_option( 'snn_ai_enabled', 'no' ) === 'yes' &&
			! $ai_builder_script_printed
		) {
			$ai_builder_script_printed = true;
			$config = function_exists( 'snn_get_ai_api_config' ) ? snn_get_ai_api_config() : [];

			if ( ! empty( $config['apiKey'] ) && ! empty( $config['apiEndpoint'] ) ) {
				$cfg_json = wp_json_encode( [
					'apiKey'      => $config['apiKey'],
					'apiEndpoint' => $config['apiEndpoint'],
					'model'       => $config['model'] ?? '',
				] );
				?>
				<script>
				(function() {
					if (window.__snnAnimatedSectionAI) return;
					window.__snnAnimatedSectionAI = true;

					var cfg = <?php echo $cfg_json; // phpcs:ignore ?>;

					document.addEventListener('click', function(e) {
						var btn = e.target.closest
							? e.target.closest('.snn-animated-section-ai-gen-btn')
							: (e.target.classList.contains('snn-animated-section-ai-gen-btn') ? e.target : null);
						if (!btn) return;

						var controlRow = btn.closest('[data-controlkey="ai_shader_prompt"]');
						var statusEl   = btn.parentElement
							? btn.parentElement.querySelector('.snn-animated-section-ai-status')
							: null;
						var prompt = '';
						if (controlRow) {
							var ta = controlRow.querySelector('textarea');
							if (ta) prompt = ta.value.trim();
						}

						if (!prompt) {
							if (statusEl) statusEl.textContent = 'Please enter a shader description first.';
							return;
						}

						btn.disabled = true;
						btn.textContent = '⏳ Generating…';
						if (statusEl) statusEl.textContent = 'Calling AI, please wait…';

						fetch(cfg.apiEndpoint, {
							method: 'POST',
							headers: {
								'Content-Type':  'application/json',
								'Authorization': 'Bearer ' + cfg.apiKey
							},
							body: JSON.stringify({
								model: cfg.model,
								messages: [
									{
										role: 'system',
										content: 'You are a WebGL GLSL expert. Write a WebGL 1.0 GLSL ' +
											'fragment shader. Output ONLY the raw GLSL code — no markdown, ' +
											'no backticks, no explanation. These uniforms are already ' +
											'declared: uniform float u_time; uniform vec2 u_resolution; ' +
											'uniform vec3 u_color1; uniform vec3 u_color2; uniform float u_speed; ' +
											'Start with: precision mediump float; ' +
											'End by assigning gl_FragColor.'
									},
									{
										role: 'user',
										content: 'Create a GLSL fragment shader: ' + prompt
									}
								]
							})
						})
						.then(function(r) { return r.json(); })
						.then(function(data) {
							var code = (data.choices && data.choices[0] && data.choices[0].message)
								? data.choices[0].message.content
								: '';
							// Strip markdown code fences if the model returns them
							code = code.replace(/^```[a-zA-Z]*\s*/m, '').replace(/```\s*$/m, '').trim();

							// Apply to the custom_shader CodeMirror control
							var applied = false;
							var cmEls = document.querySelectorAll('[data-controlkey="custom_shader"] .CodeMirror');
							cmEls.forEach(function(cmEl) {
								if (cmEl.CodeMirror) {
									cmEl.CodeMirror.setValue(code);
									cmEl.CodeMirror.refresh();
									var innerTa = cmEl.CodeMirror.getTextArea();
									if (innerTa) innerTa.dispatchEvent(new Event('input', { bubbles: true }));
									applied = true;
								}
							});
							// Fallback: plain textarea
							if (!applied) {
								document.querySelectorAll('[data-controlkey="custom_shader"] textarea').forEach(function(ta) {
									ta.value = code;
									ta.dispatchEvent(new Event('input', { bubbles: true }));
									applied = true;
								});
							}

							if (statusEl) {
								statusEl.textContent = applied
									? '✓ Shader generated and applied above.'
									: '⚠ Generated but could not auto-apply — see console.';
							}
							if (!applied) console.log('[AnimatedSection] Generated shader:', code);

							btn.disabled = false;
							btn.textContent = '✦ Generate Shader with AI';
						})
						.catch(function(err) {
							if (statusEl) statusEl.textContent = '✗ Error: ' + err.message;
							btn.disabled = false;
							btn.textContent = '✦ Generate Shader with AI';
						});
					});
				})();
				</script>
				<?php
			}
		}
	}

	// ── Builder Vue template ──────────────────────────────────────────────────

	public static function render_builder() {
		?>
		<script type="text/x-template" id="tmpl-bricks-element-snn-animated-section">
			<component :is="tag">
				<bricks-element-children :element="element"/>
			</component>
		</script>
		<?php
	}
}
