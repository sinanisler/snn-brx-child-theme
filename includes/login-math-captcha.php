<?php

// Add math captcha to login form with JavaScript validation
function add_login_math_captcha() {
    if (!session_id()) {
        session_start();
    }
    $_SESSION['captcha_number1'] = rand(1, 6);
    $_SESSION['captcha_number2'] = rand(1, 6);
    $sum = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

    // Check if captcha is enabled in the settings
    $options = get_option('snn_settings');
    if (isset($options['enable_math_captcha']) && $options['enable_math_captcha']) {
        ?>
        <p>
            <label for="math_captcha"><?php echo $_SESSION['captcha_number1'] . " + " . $_SESSION['captcha_number2']; ?> = ?</label>
            <input type="text" name="math_captcha" id="math_captcha" class="input" value="" size="20" autocomplete="off" required />
            <input type="hidden" name="js_enabled" value="no" id="js_enabled">
        </p>
        <script type="text/javascript">
            document.getElementById('js_enabled').value = 'yes'; // Only executes if JavaScript is enabled
            document.addEventListener('DOMContentLoaded', function () {
                var submitButton = document.getElementById('wp-submit');
                var captchaInput = document.getElementById('math_captcha');
                submitButton.disabled = true; // Disable submit button initially

                function validateCaptcha() {
                    var userCaptcha = parseInt(captchaInput.value.trim());
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

function validate_login_captcha($user, $password) {
    // Check if 'js_enabled' is set and has the value 'yes'
    if (!isset($_POST['js_enabled']) || $_POST['js_enabled'] !== 'yes') {
        // If 'js_enabled' field is not set to 'yes', block the login attempt silently
        return new WP_Error('authentication_failed', __('Authentication failed.', 'my_textdomain'));
    }

    // Continue with the captcha check if JavaScript is enabled
    if (isset($_POST['math_captcha'], $_SESSION['captcha_number1'], $_SESSION['captcha_number2'])) {
        $user_captcha_response = trim($_POST['math_captcha']);
        $correct_answer = $_SESSION['captcha_number1'] + $_SESSION['captcha_number2'];

        if (empty($user_captcha_response) || (int)$user_captcha_response !== $correct_answer) {
            return new WP_Error('captcha_error', __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "my_textdomain"));
        }
    }
    return $user;
}
add_filter('authenticate', 'validate_login_captcha', 10, 3);

// Settings for enabling the math captcha
function snn_math_captcha_setting_field() {
    add_settings_field(
        'enable_math_captcha',
        'Enable Math Captcha for Login',
        'snn_math_captcha_callback',
        'snn-settings',
        'snn_general_section'
    );
}

add_action('admin_init', 'snn_math_captcha_setting_field');

function snn_math_captcha_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="checkbox" name="snn_settings[enable_math_captcha]" value="1" <?php checked(isset($options['enable_math_captcha']), 1); ?>>
    <p>Enable this setting to add a math captcha challenge on the login page to improve security.</p>
    <?php
}
?>