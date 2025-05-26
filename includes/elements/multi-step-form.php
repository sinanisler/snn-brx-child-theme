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
<pre data-control="info" style="line-height:2; padding:10px">
Add data-split span code to to <b>HTML Fields</b> for split each form field groups.
</pre>       
<textarea style="min-height:33px"><span data-split></span></textarea>    
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
