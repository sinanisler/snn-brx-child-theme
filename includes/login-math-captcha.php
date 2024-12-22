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
    // Check if session is already started
    if (!session_id()) {
        session_start();
    }
    
    $_SESSION['captcha_number1'] = rand(1, 6);
    $_SESSION['captcha_number2'] = rand(1, 6);
    $sum = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

    // Check if captcha is enabled in the settings
    $options = get_option('snn_security_options');
    if (isset($options['enable_math_captcha']) && $options['enable_math_captcha']) {
        ?>
        <p id="math_captcha_container" style="display: none;">
            <label for="math_captcha"><?php echo esc_html($_SESSION['captcha_number1'] . " + " . $_SESSION['captcha_number2']); ?> = ?</label>
            <input type="text" name="math_captcha" id="math_captcha" class="input" value="" size="20" autocomplete="off" required>
            <input type="hidden" name="js_enabled" value="no" id="js_enabled">
        </p>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var captchaContainer = document.getElementById('math_captcha_container');
                captchaContainer.style.display = 'block'; // Always display captcha but only validate if JS is enabled
                document.getElementById('js_enabled').value = 'yes';
                
                var submitButton = document.getElementById('wp-submit');
                var captchaInput = document.getElementById('math_captcha');
                submitButton.disabled = true; // Disable submit button initially
                
                function validateCaptcha() {
                    var userCaptcha = parseInt(captchaInput.value.trim(), 10);
                    var correctCaptcha = <?php echo json_encode($sum); ?>;
                    submitButton.disabled = isNaN(userCaptcha) || userCaptcha !== correctCaptcha;
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
    
    if (!isset($_POST['js_enabled']) || $_POST['js_enabled'] !== 'yes') {
        if (empty($_POST['math_captcha'])) {
            // Block the login attempt if math captcha is empty when JS is disabled
            return new WP_Error('authentication_failed', __('No JavaScript detected and math captcha is empty.', 'snn'));
        }
    }
    
    if (isset($_POST['math_captcha'], $_SESSION['captcha_number1'], $_SESSION['captcha_number2'])) {
        $user_captcha_response = trim($_POST['math_captcha']);
        $correct_answer = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

        if (empty($user_captcha_response) || (int)$user_captcha_response !== $correct_answer) {
            return new WP_Error('captcha_error', __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn"));
        }
    }
    return $user;
}
add_filter('authenticate', 'validate_login_captcha', 10, 3);
?>
