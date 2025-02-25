<?php
// element-halftone.php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Prefix_Element_Halftone extends \Bricks\Element {
	// Element properties
	public $category     = 'snn';
	public $name         = 'prefix-halftone';
	public $icon         = 'ti-image';
	public $css_selector = '.prefix-halftone-wrapper';
	public $scripts      = []; // Inline JS is used in the render() method

	// Return localized element label.
	public function get_label() {
		return esc_html__( 'Polkadot Halftone Image', 'bricks' );
	}

	// Set builder controls – all in the content tab.
	public function set_controls() {
		$this->controls['exampleImage'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Image', 'bricks' ),
			'type'  => 'image',
		];
		$this->controls['grid_size'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Grid Size', 'bricks' ),
			'type'    => 'slider',
			'default' => 20,
			'units'   => [
				'px' => [
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				],
			],
		];
		$this->controls['brightness'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Brightness', 'bricks' ),
			'type'    => 'slider',
			'default' => -100,
			'units'   => [
				'px' => [
					'min'  => -200,
					'max'  => 200,
					'step' => 1,
				],
			],
		];
		$this->controls['contrast'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Contrast', 'bricks' ),
			'type'    => 'slider',
			'default' => 0,
			'units'   => [
				'px' => [
					'min'  => -100,
					'max'  => 100,
					'step' => 1,
				],
			],
		];
		$this->controls['gamma'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Gamma', 'bricks' ),
			'type'    => 'slider',
			'default' => 0.8,
			'units'   => [
				'' => [
					'min'  => 0.1,
					'max'  => 3.0,
					'step' => 0.1,
				],
			],
		];
		$this->controls['smoothing'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Smoothing', 'bricks' ),
			'type'    => 'slider',
			'default' => 0,
			'units'   => [
				'' => [
					'min'  => 0,
					'max'  => 10,
					'step' => 1,
				],
			],
		];
		$this->controls['dither_type'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Dither Type', 'bricks' ),
			'type'    => 'select',
			'options' => [
				'None'           => esc_html__( 'None', 'bricks' ),
				'FloydSteinberg' => esc_html__( 'Floyd-Steinberg', 'bricks' ),
				'Ordered'        => esc_html__( 'Ordered', 'bricks' ),
				'Noise'          => esc_html__( 'Noise', 'bricks' ),
			],
			'default' => 'None',
            'description' => "<br>
                <p data-control='info'>
                    Resizing and preview a bit limited but cool effect. Crop the image before using.
                </p>
            ",
		];
		// Removed canvas_width and canvas_height controls as they were not working.
	}

	// Render element HTML and inline JS.
	public function render() {
		// Set up CSS classes for the root wrapper.
		$root_classes = ['prefix-halftone-wrapper'];
		$this->set_attribute('_root', 'class', $root_classes);

		// Generate or retrieve a unique ID for the root element.
		if ( isset( $this->attributes['_root']['id'] ) && ! empty( $this->attributes['_root']['id'] ) ) {
			$unique_id = $this->attributes['_root']['id'];
		} else {
			$unique_id = 'prefix-halftone-' . uniqid();
			$this->set_attribute('_root', 'id', $unique_id);
		}

		// In the editor, output a fallback static image so the element renders correctly.
		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			if ( ! empty( $this->settings['exampleImage']['id'] ) ) {
				echo wp_get_attachment_image( $this->settings['exampleImage']['id'], 'full', false, [ 'class' => 'prefix-halftone-fallback' ] );
			} else if ( ! empty( $this->settings['exampleImage']['url'] ) ) {
				echo '<img class="prefix-halftone-fallback" src="' . esc_url( $this->settings['exampleImage']['url'] ) . '" alt="' . esc_attr__( 'Halftone Image', 'bricks' ) . '" />';
			} else {
				esc_html_e( 'No image selected.', 'bricks' );
			}
			return;
		}

		// Retrieve the example image/video URL from the image control array.
		$exampleImage = ! empty( $this->settings['exampleImage']['url'] )
			? esc_url( $this->settings['exampleImage']['url'] )
			: '';

		?>
		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<canvas id="<?php echo $unique_id; ?>-halftoneCanvas"></canvas>
		</div>
		<script>
		(function(){
			var container = document.getElementById("<?php echo $unique_id; ?>");
			if (!container) return;

			var halftoneCanvas = container.querySelector("#<?php echo $unique_id; ?>-halftoneCanvas");

			// Settings from Bricks Builder
			var settings = {
				gridSize: <?php echo esc_js( $this->settings['grid_size'] ?? 20 ); ?>,
				brightness: <?php echo esc_js( $this->settings['brightness'] ?? -100 ); ?>,
				contrast: <?php echo esc_js( $this->settings['contrast'] ?? 0 ); ?>,
				gamma: <?php echo esc_js( $this->settings['gamma'] ?? 0.8 ); ?>,
				smoothing: <?php echo esc_js( $this->settings['smoothing'] ?? 0 ); ?>,
				ditherType: "<?php echo esc_js( $this->settings['dither_type'] ?? 'None' ); ?>"
			};

			// Set up canvas dimensions based on the loaded image.
			function setupCanvasDimensions(width, height) {
				halftoneCanvas.width = width;
				halftoneCanvas.height = height;
			}

			// Process the frame by generating the halftone effect.
			function processFrame() {
				if (!imageElement) return;
				generateHalftone(halftoneCanvas, 1);
			}

			// Generate halftone: compute grayscale per grid cell.
			function generateHalftone(targetCanvas, scaleFactor) {
				var previewWidth = targetCanvas.width;
				var previewHeight = targetCanvas.height;
				var targetWidth = previewWidth * scaleFactor;
				var targetHeight = previewHeight * scaleFactor;

				targetCanvas.width = targetWidth;
				targetCanvas.height = targetHeight;

				// Draw the full-res image onto a temporary canvas.
				var tempCanvas = document.createElement('canvas');
				tempCanvas.width = targetWidth;
				tempCanvas.height = targetHeight;
				var tempCtx = tempCanvas.getContext('2d');

				tempCtx.drawImage(imageElement, 0, 0, targetWidth, targetHeight);

				var imgData = tempCtx.getImageData(0, 0, targetWidth, targetHeight);
				var data = imgData.data;

				var brightnessAdj = parseInt(settings.brightness, 10);
				var contrastAdj = parseInt(settings.contrast, 10);
				var gammaValNum = parseFloat(settings.gamma);
				var contrastFactor = (259 * (contrastAdj + 255)) / (255 * (259 - contrastAdj));

				var grayData = new Float32Array(targetWidth * targetHeight);
				for (var i = 0; i < data.length; i += 4) {
					var r = data[i], g = data[i+1], b = data[i+2];
					var gray = 0.299 * r + 0.587 * g + 0.114 * b;
					gray = contrastFactor * (gray - 128) + 128 + brightnessAdj;
					gray = Math.max(0, Math.min(255, gray));
					gray = 255 * Math.pow(gray / 255, 1 / gammaValNum);
					grayData[i / 4] = gray;
				}

				var grid = parseInt(settings.gridSize, 10) * scaleFactor;
				var numCols = Math.ceil(targetWidth / grid);
				var numRows = Math.ceil(targetHeight / grid);
				var cellValues = new Float32Array(numRows * numCols);

				for (var row = 0; row < numRows; row++) {
					for (var col = 0; col < numCols; col++) {
						var sum = 0, count = 0;
						var startY = row * grid;
						var startX = col * grid;
						var endY = Math.min(startY + grid, targetHeight);
						var endX = Math.min(startX + grid, targetWidth);
						for (var y = startY; y < endY; y++) {
							for (var x = startX; x < endX; x++) {
								sum += grayData[y * targetWidth + x];
								count++;
							}
						}
						cellValues[row * numCols + col] = sum / count;
					}
				}

				var smoothingStrength = parseFloat(settings.smoothing);
				if (smoothingStrength > 0) {
					cellValues = applyBoxBlur(cellValues, numRows, numCols, smoothingStrength);
				}

				var selectedDither = settings.ditherType;
				if (selectedDither === "FloydSteinberg") {
					applyFloydSteinbergDithering(cellValues, numRows, numCols);
				} else if (selectedDither === "Ordered") {
					applyOrderedDithering(cellValues, numRows, numCols);
				} else if (selectedDither === "Noise") {
					applyNoiseDithering(cellValues, numRows, numCols);
				}

				var ctx = targetCanvas.getContext('2d');
				ctx.fillStyle = 'white';
				ctx.fillRect(0, 0, targetCanvas.width, targetCanvas.height);

				for (var row = 0; row < numRows; row++) {
					for (var col = 0; col < numCols; col++) {
						var brightnessValue = cellValues[row * numCols + col];
						var norm = brightnessValue / 255;
						var maxRadius = grid / 2;
						var radius = maxRadius * (1 - norm);
						if (radius > 0.5) {
							ctx.beginPath();
							var centerX = col * grid + grid / 2;
							var centerY = row * grid + grid / 2;
							ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
							ctx.fillStyle = 'black';
							ctx.fill();
						}
					}
				}
			}

			// Box Blur for smoothing grid cell values.
			function applyBoxBlur(cellValues, numRows, numCols, strength) {
				var result = new Float32Array(cellValues);
				var passes = Math.floor(strength);
				for (var p = 0; p < passes; p++) {
					var temp = new Float32Array(result.length);
					for (var row = 0; row < numRows; row++) {
						for (var col = 0; col < numCols; col++) {
							var sum = 0, count = 0;
							for (var dy = -1; dy <= 1; dy++) {
								for (var dx = -1; dx <= 1; dx++) {
									var r = row + dy, c = col + dx;
									if (r >= 0 && r < numRows && c >= 0 && c < numCols) {
										sum += result[r * numCols + c];
										count++;
									}
								}
							}
							temp[row * numCols + col] = sum / count;
						}
					}
					result = temp;
				}
				var frac = strength - Math.floor(strength);
				if (frac > 0) {
					for (var i = 0; i < result.length; i++) {
						result[i] = cellValues[i] * (1 - frac) + result[i] * frac;
					}
				}
				return result;
			}

			function applyFloydSteinbergDithering(cellValues, numRows, numCols) {
				var threshold = 128;
				for (var row = 0; row < numRows; row++) {
					for (var col = 0; col < numCols; col++) {
						var index = row * numCols + col;
						var oldVal = cellValues[index];
						var newVal = oldVal < threshold ? 0 : 255;
						var error = oldVal - newVal;
						cellValues[index] = newVal;
						if (col + 1 < numCols) {
							cellValues[row * numCols + (col + 1)] += error * (7 / 16);
						}
						if (row + 1 < numRows) {
							if (col - 1 >= 0) {
								cellValues[(row + 1) * numCols + (col - 1)] += error * (3 / 16);
							}
							cellValues[(row + 1) * numCols + col] += error * (5 / 16);
							if (col + 1 < numCols) {
								cellValues[(row + 1) * numCols + (col + 1)] += error * (1 / 16);
							}
						}
					}
				}
			}

			function applyOrderedDithering(cellValues, numRows, numCols) {
				var bayerMatrix = [[0,2],[3,1]];
				var matrixSize = 2;
				for (var row = 0; row < numRows; row++) {
					for (var col = 0; col < numCols; col++) {
						var index = row * numCols + col;
						var threshold = ((bayerMatrix[row % matrixSize][col % matrixSize] + 0.5) * (255 / (matrixSize * matrixSize)));
						cellValues[index] = cellValues[index] < threshold ? 0 : 255;
					}
				}
			}

			function applyNoiseDithering(cellValues, numRows, numCols) {
				var threshold = 128;
				for (var row = 0; row < numRows; row++) {
					for (var col = 0; col < numCols; col++) {
						var index = row * numCols + col;
						var noise = (Math.random() - 0.5) * 50;
						var adjustedVal = cellValues[index] + noise;
						cellValues[index] = adjustedVal < threshold ? 0 : 255;
					}
				}
			}

			// Initialize image element from the exampleImage URL provided by Bricks Builder.
			var imageElement = new Image();
			imageElement.crossOrigin = "anonymous";
			imageElement.src = "<?php echo $exampleImage; ?>";
			imageElement.addEventListener('load', function() {
				setupCanvasDimensions(imageElement.width, imageElement.height);
				processFrame();
			});

			// Fill the canvas with white initially.
			(function(){
				var ctx = halftoneCanvas.getContext('2d');
				ctx.fillStyle = 'white';
				ctx.fillRect(0, 0, halftoneCanvas.width, halftoneCanvas.height);
			})();
		})();
		</script>
		<?php
	}
}
?>
