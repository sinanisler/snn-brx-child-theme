<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Bricks\Element;

class Multi_Step_Form_Element extends Element {
    public $category     = 'general';
    public $name         = 'multi-step-form';
    public $icon         = 'ti-list-ol';
    public $css_selector = '.multi-step-form-wrapper';
    public $scripts      = [];
    public $nestable     = false;

    public function get_label() {
        return esc_html__( 'Multi-Step Form', 'snn' );
    }

    public function set_controls() {
        // Selector input (for targeting the form)
        $this->controls['form_selector'] = [
            'tab'       => 'content',
            'label'     => esc_html__( 'Form Selector', 'snn' ),
            'type'      => 'text',
            'default'   => 'form',
            'placeholder' => '#your-form-id',
            'description'=> '
<pre data-control="info" style="line-height:2">
Enter the CSS selector of your form.
</pre>',
            'inline' => true,
        ];
        // Button background color
        $this->controls['button_bg_color'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Button Background Color', 'snn' ),
            'type'    => 'color',
            'default' => [ 'hex' => '#cccccc' ],
            'css'     => [
                [
                    'property' => 'background-color',
                    'selector' => '.multi-step-form-wrapper .button-progress',
                ]
            ],
        ];
        // Button typography
        $this->controls['button_typography'] = [
            'tab'   => 'content',
            'label' => esc_html__( 'Button Typography', 'snn' ),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'typography',
                    'selector' => '.multi-step-form-wrapper .button-progress',
                ]
            ],
        ];
        // Progress bar color
        $this->controls['progress_bg_color'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Progress Bar Color', 'snn' ),
            'type'    => 'color',
            'default' => [ 'hex' => '#cccccc' ],
            'css'     => [
                [
                    'property' => 'background-color',
                    'selector' => '.multi-step-form-wrapper .progress-bar',
                ]
            ],
        ];
        // Progress indicator color
        $this->controls['progress_indicator_color'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Progress Indicator Color', 'snn' ),
            'type'    => 'color',
            'default' => [ 'hex' => '#333333' ],
            'css'     => [
                [
                    'property' => 'background-color',
                    'selector' => '.multi-step-form-wrapper .progress-bar span',
                ]
            ],
        ];

        // Back button text control
        $this->controls['back_button_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Back Button Text', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'Back', 'snn' ),
            'placeholder' => esc_html__( 'Back', 'snn' ),
            'inline' => true,
        ];

        // Next button text control
        $this->controls['next_button_text'] = [
            'tab'     => 'content',
            'label'   => esc_html__( 'Next Button Text', 'snn' ),
            'type'    => 'text',
            'default' => esc_html__( 'Next', 'snn' ),
            'placeholder' => esc_html__( 'Next', 'snn' ),
            'inline' => true,
            'description' => '            
<pre data-control="info" style="line-height:2">
Add <img src="data:image/webp;base64,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
" /> to HTML Fields for split each form field groups.
</pre>           
            ',
        ];



    }

    public function render() {
        // Get settings
        $form_selector = $this->settings['form_selector'] ?? '#brxe-pojxsi';
        $button_bg_color = $this->settings['button_bg_color']['hex'] ?? '#bedc00';
        $progress_bg_color = $this->settings['progress_bg_color']['hex'] ?? '#e0e0e0';
        $progress_indicator_color = $this->settings['progress_indicator_color']['hex'] ?? '#bedc00';
        $back_button_text = $this->settings['back_button_text'] ?? 'Back';
        $next_button_text = $this->settings['next_button_text'] ?? 'Next';

        $this->set_attribute( '_root', 'class', 'multi-step-form-wrapper' );
        $unique_id = 'multi-step-form-' . uniqid();

        // Output wrapper and inline CSS for frontend + builder
        echo '<div ' . $this->render_attributes('_root') . ' id="' . esc_attr( $unique_id ) . '">';
        ?>
        <style>
            <?php echo esc_js($form_selector); ?> .progress-bar {
                width: 100%;
                height: 10px;
                background: <?php echo esc_attr($progress_bg_color); ?>;
                position: relative;
                margin-bottom: 20px;
                overflow: hidden;
                border-radius: 4px;
            }
            <?php echo esc_js($form_selector); ?> .progress-bar span {
                display: block;
                height: 100%;
                width: 0%;
                background: <?php echo esc_attr($progress_indicator_color); ?>;
                transition: 1s ;
            }
            <?php echo esc_js($form_selector); ?> .button-progress {
                border: none;
                cursor: pointer;
                background-color: <?php echo esc_attr($button_bg_color); ?>;
                line-height: 1;
                border-radius: 4px;
                font-weight: 500;
                margin: 0;
                transition: opacity 0.2s;
            }
            <?php echo esc_js($form_selector); ?> .button-progress:hover {
                opacity: 0.8;
            }
            <?php echo esc_js($form_selector); ?> .nav-container {
                width: 100%;
                justify-content: space-between;
                margin-top: 30px;
                gap: 10px;
            }
            form .button-progress{
                padding:10px 20px;
            }
            form .back{
                float:left
            }
            form .next{
                float:right
            }
        </style>
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.querySelector('<?php echo esc_js($form_selector); ?>');
                if (!form) return;

                // Avoid double-inserting if already present
                if (form.closest('.multi-step-form-wrapper')) return;

                // Create progress bar container and insert it as the first child *inside* the form.
                var progressBar = document.createElement('div');
                progressBar.classList.add('progress-bar');
                var progressSpan = document.createElement('span');
                progressBar.appendChild(progressSpan);
                form.insertBefore(progressBar, form.firstChild);

                // Get all form groups and separate out the submit button group.
                var allGroups = Array.from(form.querySelectorAll('.form-group'));
                var submitGroup = form.querySelector('.submit-button-wrapper');
                if (submitGroup) {
                  allGroups = allGroups.filter(el => !el.classList.contains('submit-button-wrapper'));
                  submitGroup.style.display = 'none';
                }

                // Split the groups into steps based on the data-split markers.
                var steps = [];
                var currentStepGroups = [];
                allGroups.forEach(function (group) {
                  if (group.querySelector('[data-split]') !== null) {
                    if (currentStepGroups.length > 0) {
                      steps.push(currentStepGroups);
                      currentStepGroups = [];
                    }
                  } else {
                    currentStepGroups.push(group);
                  }
                });
                if (currentStepGroups.length > 0) {
                  steps.push(currentStepGroups);
                }
                if (submitGroup && steps.length > 0) {
                  steps[steps.length - 1].push(submitGroup);
                }

                // Hide all groups in all steps initially.
                steps.forEach(stepGroups => stepGroups.forEach(group => group.style.display = 'none'));

                var currentStepIndex = 0;

                // Function to update the progress bar.
                function updateProgress() {
                  if (steps.length > 1) {
                    var progress = (currentStepIndex / (steps.length - 1)) * 100;
                    progressSpan.style.width = progress + '%';
                  } else {
                    progressSpan.style.width = '100%';
                  }
                }

                // Function to display a given step and hide others.
                function showStep(index) {
                  steps.forEach((stepGroups, i) => stepGroups.forEach(group => {
                    group.style.display = (i === index) ? '' : 'none';
                  }));
                  updateNav();
                  updateProgress();
                }

                // Create navigation container and append it to the form.
                var navContainer = document.createElement('div');
                navContainer.classList.add('nav-container');
                form.appendChild(navContainer);

                // Create Prev button.
                var prevButton = document.createElement('button');
                prevButton.type = 'button';
                prevButton.textContent = <?php echo json_encode($back_button_text); ?>;
                prevButton.classList.add('button-progress', 'prev');
                prevButton.addEventListener('click', function () {
                  if (currentStepIndex > 0) {
                    currentStepIndex--;
                    showStep(currentStepIndex);
                  }
                });

                // Create Next button.
                var nextButton = document.createElement('button');
                nextButton.type = 'button';
                nextButton.textContent = <?php echo json_encode($next_button_text); ?>;
                nextButton.classList.add('button-progress', 'next');
                nextButton.addEventListener('click', function () {
                  // Collect inputs from the current step groups.
                  var currentStepElements = steps[currentStepIndex];
                  var inputs = [];
                  currentStepElements.forEach(function (group) {
                    inputs = inputs.concat(Array.from(group.querySelectorAll('input, textarea, select')));
                  });

                  // Validate each input. For radio groups, validate only once per group.
                  var valid = true;
                  var processedRadioGroups = new Set();
                  for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    if (input.type === 'radio') {
                      if (processedRadioGroups.has(input.name)) continue;
                      processedRadioGroups.add(input.name);
                      var radios = form.querySelectorAll('input[type="radio"][name="' + input.name + '"]');
                      var checked = Array.from(radios).some(function(radio) { return radio.checked; });
                      if (!checked) {
                        radios[0].reportValidity();
                        valid = false;
                        break;
                      }
                    } else {
                      if (!input.checkValidity()) {
                        input.reportValidity();
                        valid = false;
                        break;
                      }
                    }
                  }

                  if (valid && currentStepIndex < steps.length - 1) {
                    currentStepIndex++;
                    showStep(currentStepIndex);
                  }
                });

                // Update the navigation container based on the current step.
                function updateNav() {
                  navContainer.innerHTML = '';
                  if (currentStepIndex > 0) navContainer.appendChild(prevButton);
                  if (currentStepIndex < steps.length - 1) navContainer.appendChild(nextButton);
                }

                // Display the first step.
                showStep(currentStepIndex);
            });
        })();
        </script>
        <?php
        echo '</div>';
    }
}
