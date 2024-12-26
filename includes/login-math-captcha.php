<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure session_start() is called at the beginning of the script
if (!session_id()) {
    session_start();
}

/**
 * Add math captcha to the login form
 */
function add_login_math_captcha() {
    // Generate random numbers and store in session
    $_SESSION['captcha_number1'] = rand(1, 6);
    $_SESSION['captcha_number2'] = rand(1, 6);
    $sum = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

    // Encode them in Base64 (to pass to JS without revealing raw numbers)
    $encodedNumber1 = base64_encode($_SESSION['captcha_number1']);
    $encodedNumber2 = base64_encode($_SESSION['captcha_number2']);
    $encodedSum     = base64_encode($sum);

    // Check if captcha is enabled in settings
    $options = get_option('snn_security_options');
    if (isset($options['enable_math_captcha']) && $options['enable_math_captcha']) {
        ?>
        <p id="math_captcha_container" style="display: none;">
            <label id="captcha_label" for="math_captcha"></label>
            <input type="text" name="math_captcha" id="math_captcha" class="input" value="" size="20" autocomplete="off" required>
            <input type="hidden" name="js_enabled" value="no" id="js_enabled">
        </p>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var captchaContainer = document.getElementById('math_captcha_container');
                captchaContainer.style.display = 'block'; 
                document.getElementById('js_enabled').value = 'yes';

                var captchaLabel = document.getElementById('captcha_label');
                var submitButton = document.getElementById('wp-submit');
                var captchaInput = document.getElementById('math_captcha');

                submitButton.disabled = true;

                // Retrieve our Base64-encoded numbers
                var b64Number1 = "<?php echo esc_js($encodedNumber1); ?>";
                var b64Number2 = "<?php echo esc_js($encodedNumber2); ?>";
                var b64Sum     = "<?php echo esc_js($encodedSum); ?>";

                // Decode the numbers
                var number1 = parseInt(window.atob(b64Number1), 10);
                var number2 = parseInt(window.atob(b64Number2), 10);
                var correctSum = parseInt(window.atob(b64Sum), 10);

                // Instead of textContent = "X + Y = ?", we draw it on a canvas
                // so the numbers are not directly parsable as text in the DOM.
                captchaLabel.innerHTML = "<canvas id='captchaCanvas' width='150' height='40'></canvas>";
                var canvas = document.getElementById('captchaCanvas');
                var ctx    = canvas.getContext('2d');
                
                // Some basic styling
                ctx.font = "24px Arial";
                ctx.fillStyle = "#333";
                ctx.fillText(number1 + ' + ' + number2 + ' = ?', 10, 28);

                // Function to validate user input
                function validateCaptcha() {
                    var userCaptcha = parseInt(captchaInput.value.trim(), 10);
                    if (isNaN(userCaptcha) || userCaptcha !== correctSum) {
                        submitButton.disabled = true;
                    } else {
                        submitButton.disabled = false;
                    }
                }

                captchaInput.addEventListener('input', validateCaptcha);
            });
        </script>
        <?php
    }
}
add_action('login_form', 'add_login_math_captcha');

/**
 * Validate the math captcha during authentication
 */
function validate_login_captcha($user, $password) {
    // Ensure session is started
    if (!session_id()) {
        session_start();
    }
    
    // Check if JavaScript was disabled
    if (!isset($_POST['js_enabled']) || $_POST['js_enabled'] !== 'yes') {
        if (empty($_POST['math_captcha'])) {
            // If JS is disabled, user must still fill out the math captcha
            return new WP_Error('authentication_failed', __('No JavaScript detected and math captcha is empty.', 'snn'));
        }
    }
    
    if (isset($_POST['math_captcha'], $_SESSION['captcha_number1'], $_SESSION['captcha_number2'])) {
        $user_captcha_response = trim($_POST['math_captcha']);
        $correct_answer        = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

        if (empty($user_captcha_response) || (int)$user_captcha_response !== $correct_answer) {
            return new WP_Error('captcha_error', __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn"));
        }
    }
    return $user;
}
add_filter('authenticate', 'validate_login_captcha', 10, 3);
?>
