<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!session_id()) {
    session_start();
}

function add_login_math_captcha() {
    $_SESSION['captcha_number1'] = rand(1, 6);
    $_SESSION['captcha_number2'] = rand(1, 6);
    $sum = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

    $encodedNumber1 = base64_encode($_SESSION['captcha_number1']);
    $encodedNumber2 = base64_encode($_SESSION['captcha_number2']);
    $encodedSum     = base64_encode($sum);

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

function validate_login_captcha($user, $password) {
    if (!session_id()) {
        session_start();
    }
    
    if (!isset($_POST['js_enabled']) || $_POST['js_enabled'] !== 'yes') {
        if (empty($_POST['math_captcha'])) {
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
