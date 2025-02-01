<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snn_add_math_captcha() {
    $options = get_option( 'snn_security_options' );

    if ( isset( $options['enable_math_captcha'] ) && $options['enable_math_captcha'] ) {
        $number1 = rand( 1, 6 );
        $number2 = rand( 1, 6 );
        $sum     = $number1 + $number2;

        $encodedNumber1 = base64_encode( $number1 );
        $encodedNumber2 = base64_encode( $number2 );
        $encodedSum     = base64_encode( $sum );

        // Generate a unique ID so that if more than one form is on the same page, each captcha instance remains unique.
        $unique = uniqid( 'captcha_' );
        ?>
        <p id="math_captcha_container_<?php echo esc_attr( $unique ); ?>" style="display: none; margin-top: 1em;">
            <label id="captcha_label_<?php echo esc_attr( $unique ); ?>" for="math_captcha_<?php echo esc_attr( $unique ); ?>"></label>
            <input type="text" name="math_captcha" id="math_captcha_<?php echo esc_attr( $unique ); ?>" class="input" value="" size="20" autocomplete="off" required>
            <input type="hidden" name="captcha_solution" id="captcha_solution_<?php echo esc_attr( $unique ); ?>" value="">
            <input type="hidden" name="js_enabled" value="no" id="js_enabled_<?php echo esc_attr( $unique ); ?>">
        </p>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const captchaContainer = document.getElementById('math_captcha_container_<?php echo esc_js( $unique ); ?>');
                captchaContainer.style.display = 'block';
                document.getElementById('js_enabled_<?php echo esc_js( $unique ); ?>').value = 'yes';

                const captchaLabel = document.getElementById('captcha_label_<?php echo esc_js( $unique ); ?>');
                // Find the closest form for proper scoping.
                const form = captchaContainer.closest('form');
                // Supports both input[type="submit"] and button[type="submit"].
                const submitButton = form ? form.querySelector('input[type="submit"], button[type="submit"]') : null;
                const captchaInput = document.getElementById('math_captcha_<?php echo esc_js( $unique ); ?>');
                const captchaSolutionInput = document.getElementById('captcha_solution_<?php echo esc_js( $unique ); ?>');

                if ( submitButton ) {
                    submitButton.disabled = true;
                }

                const b64Number1 = "<?php echo esc_js( $encodedNumber1 ); ?>";
                const b64Number2 = "<?php echo esc_js( $encodedNumber2 ); ?>";
                const b64Sum     = "<?php echo esc_js( $encodedSum ); ?>";
                const number1    = parseInt( window.atob(b64Number1), 10 );
                const number2    = parseInt( window.atob(b64Number2), 10 );
                const correctSum = parseInt( window.atob(b64Sum), 10 );

                // Display the math question in a canvas element within the label.
                captchaLabel.innerHTML = `<canvas id='captchaCanvas_<?php echo esc_js( $unique ); ?>' width='150' height='40'></canvas>`;

                const canvas = document.getElementById('captchaCanvas_<?php echo esc_js( $unique ); ?>');
                const ctx    = canvas.getContext('2d');
                ctx.font     = "24px Arial";
                ctx.fillStyle= "#333";
                ctx.fillText(`${number1} + ${number2} = ?`, 10, 28);

                captchaSolutionInput.value = correctSum;

                function validateCaptcha() {
                    const userCaptcha = parseInt( captchaInput.value.trim(), 10 );
                    if ( isNaN(userCaptcha) || userCaptcha !== correctSum ) {
                        if ( submitButton ) {
                            submitButton.disabled = true;
                        }
                    } else {
                        if ( submitButton ) {
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

add_action( 'login_form', 'snn_add_math_captcha' );
add_action( 'register_form', 'snn_add_math_captcha' );
add_action( 'lostpassword_form', 'snn_add_math_captcha' );

add_action( 'woocommerce_login_form', 'snn_add_math_captcha' );
add_action( 'woocommerce_register_form', 'snn_add_math_captcha' );
add_action( 'woocommerce_lostpassword_form', 'snn_add_math_captcha' );

function snn_check_captcha() {
    $options = get_option( 'snn_security_options' );

    if ( empty( $options['enable_math_captcha'] ) ) {
        return true;
    }

    $js_enabled = ( isset( $_POST['js_enabled'] ) && $_POST['js_enabled'] === 'yes' );

    if ( ! $js_enabled ) {
        return false;
    }

    if ( ! isset( $_POST['math_captcha'], $_POST['captcha_solution'] ) ) {
        return false;
    }

    $user_captcha_response = trim( $_POST['math_captcha'] );
    $correct_answer        = trim( $_POST['captcha_solution'] );

    if ( empty( $user_captcha_response ) || (int) $user_captcha_response !== (int) $correct_answer ) {
        return false;
    }

    return true;
}

function snn_validate_math_captcha( $result, $username, $password ) {
    $options = get_option( 'snn_security_options' );

    if ( ! empty( $options['enable_math_captcha'] ) ) {
        $current_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

        if ( $current_action === 'login' || ! isset( $_REQUEST['action'] ) ) {
            if ( ! snn_check_captcha() ) {
                $result = new WP_Error(
                    'captcha_error',
                    __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn")
                );
            }
        }
    }
    return $result;
}
add_filter( 'authenticate', 'snn_validate_math_captcha', 30, 3 );

function snn_validate_registration_captcha( $errors, $sanitized_user_login, $user_email ) {
    $options = get_option( 'snn_security_options' );

    if ( ! empty( $options['enable_math_captcha'] ) ) {
        if ( ! snn_check_captcha() ) {
            $errors->add(
                'captcha_error',
                __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn")
            );
        }
    }
    return $errors;
}
add_filter( 'registration_errors', 'snn_validate_registration_captcha', 10, 3 );

function snn_validate_lostpassword_captcha( $errors, $user_login ) {
    $options = get_option( 'snn_security_options' );

    if ( ! empty( $options['enable_math_captcha'] ) ) {
        if ( ! snn_check_captcha() ) {
            $errors->add(
                'captcha_error',
                __("<strong>ERROR</strong>: Incorrect or empty math captcha.", "snn")
            );
        }
    }
    return $errors;
}
add_filter( 'lostpassword_post_errors', 'snn_validate_lostpassword_captcha', 10, 2 );
?>
