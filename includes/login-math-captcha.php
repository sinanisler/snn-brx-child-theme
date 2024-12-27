<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!session_id()) {
    session_start();
}

/**
 * Adds a math captcha to Login, Register, and Lost Password forms.
 */
function snn_add_math_captcha() {
    $options = get_option('snn_security_options');
    if (isset($options['enable_math_captcha']) && $options['enable_math_captcha']) {
        // Generate two random numbers for the captcha
        $_SESSION['captcha_number1'] = rand(1, 6);
        $_SESSION['captcha_number2'] = rand(1, 6);
        $sum = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

        // Encode numbers using base64
        $encodedNumber1 = base64_encode($_SESSION['captcha_number1']);
        $encodedNumber2 = base64_encode($_SESSION['captcha_number2']);
        $encodedSum     = base64_encode($sum);
        
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
                var submitButton = document.querySelector('input[type="submit"]');
                var captchaInput = document.getElementById('math_captcha');
                if (submitButton) {
                    submitButton.disabled = true;
                }
                var b64Number1 = "<?php echo esc_js($encodedNumber1); ?>";
                var b64Number2 = "<?php echo esc_js($encodedNumber2); ?>";
                var b64Sum     = "<?php echo esc_js($encodedSum); ?>";
                var number1 = parseInt(window.atob(b64Number1), 10);
                var number2 = parseInt(window.atob(b64Number2), 10);
                var correctSum = parseInt(window.atob(b64Sum), 10);
                captchaLabel.innerHTML = "<canvas id='captchaCanvas' width='150' height='40'></canvas>";
                var canvas = document.getElementById('captchaCanvas');
                var ctx    = canvas.getContext('2d');
                ctx.font = "24px Arial";
                ctx.fillStyle = "#333";
                ctx.fillText(number1 + ' + ' + number2 + ' = ?', 10, 28);
                function validateCaptcha() {
                    var userCaptcha = parseInt(captchaInput.value.trim(), 10);
                    if (isNaN(userCaptcha) || userCaptcha !== correctSum) {
                        if (submitButton) {
                            submitButton.disabled = true;
                        }
                    } else {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    }
                }

                captchaInput.addEventListener('input', validateCaptcha);
            });
        </script>
        <?php
    }
}

// Hook the captcha to the Login form
add_action('login_form', 'snn_add_math_captcha');

// Hook the captcha to the Register form
add_action('register_form', 'snn_add_math_captcha');

// Hook the captcha to the Lost Password form
add_action('lostpassword_form', 'snn_add_math_captcha');

/**
 * Validates the math captcha for Login, Register, and Lost Password forms.
 *
 * @param WP_Error|bool $result The result of the authentication.
 * @param string $username The username.
 * @param string $password The password.
 * @return WP_Error|bool The result after validation.
 */
function snn_validate_math_captcha($result, $username, $password) {
    // Determine the current form
    $current_action = '';
    if (isset($_REQUEST['action'])) {
        $current_action = $_REQUEST['action'];
    }

    // For Login form
    if ($current_action === 'login' || !isset($_REQUEST['action'])) {
        // Proceed with login captcha validation
        if (!snn_check_captcha()) {
            $result = new WP_Error('captcha_error', __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn"));
        }
    }

    return $result;
}
add_filter('authenticate', 'snn_validate_math_captcha', 30, 3);

/**
 * Validates the math captcha for the Register form.
 *
 * @param WP_Error $errors Validation errors.
 * @param string $sanitized_user_login The sanitized username.
 * @param string $user_email The user email.
 * @return WP_Error The validation errors.
 */
function snn_validate_registration_captcha($errors, $sanitized_user_login, $user_email) {
    if (!snn_check_captcha()) {
        $errors->add('captcha_error', __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn"));
    }
    return $errors;
}
add_filter('registration_errors', 'snn_validate_registration_captcha', 10, 3);

/**
 * Validates the math captcha for the Lost Password form.
 *
 * @param WP_Error $errors Validation errors.
 * @param string $user_login The user login.
 * @return WP_Error The validation errors.
 */
function snn_validate_lostpassword_captcha($errors, $user_login) {
    if (!snn_check_captcha()) {
        $errors->add('captcha_error', __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn"));
    }
    return $errors;
}
add_filter('lostpassword_post_errors', 'snn_validate_lostpassword_captcha', 10, 2);

/**
 * Helper function to check the captcha.
 *
 * @return bool True if captcha is valid, false otherwise.
 */
function snn_check_captcha() {
    if (!session_id()) {
        session_start();
    }

    // Check if JavaScript is enabled
    $js_enabled = isset($_POST['js_enabled']) && $_POST['js_enabled'] === 'yes';

    if ($js_enabled) {
        // When JavaScript is enabled, captcha should be present
        if (!isset($_POST['math_captcha'])) {
            return false;
        }
    } else {
        // When JavaScript is not enabled, captcha must be filled
        if (empty($_POST['math_captcha'])) {
            return false;
        }
    }

    if (isset($_POST['math_captcha'], $_SESSION['captcha_number1'], $_SESSION['captcha_number2'])) {
        $user_captcha_response = trim($_POST['math_captcha']);
        $correct_answer        = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

        if (empty($user_captcha_response) || (int)$user_captcha_response !== $correct_answer) {
            return false;
        }
    } else {
        return false;
    }

    return true;
}
?>
