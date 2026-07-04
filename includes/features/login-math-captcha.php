<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snn_add_math_captcha() {
    $options      = get_option( 'snn_security_options' );
    $captcha_type = $options['captcha_type'] ?? 'none';

    // Dispatch to Turnstile if that type is selected.
    if ( $captcha_type === 'turnstile' ) {
        snn_add_turnstile_captcha();
        return;
    }

    if ( $captcha_type !== 'math' ) {
        return;
    }

    $number1 = rand( 1, 9 );
        $number2 = rand( 1, 9 );
        $sum     = $number1 + $number2;

        $encodedNumber1 = base64_encode( $number1 );
        $encodedNumber2 = base64_encode( $number2 );
        $encodedSum     = base64_encode( $sum );

        // Generate a unique ID so that if more than one form is on the same page, each captcha instance remains unique.
        $unique = uniqid( 'captcha_' );
        ?>
        <div id="captcha_container_<?php echo esc_attr( $unique ); ?>" class="snn-captcha-wrapper" style="display: none;">
            <style>
                .snn-captcha-wrapper { margin: 15px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .snn-captcha-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #646970; margin-bottom: 8px; display: block; font-weight: 600; }
                #captchaCanvas_<?php echo esc_attr( $unique ); ?> { background: #f9f9f9; border-radius: 4px; display: block; margin-bottom: 10px; width: 100%; height: auto; cursor: pointer; }
                #math_captcha_<?php echo esc_attr( $unique ); ?> { font-size: 18px; width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; box-sizing: border-box; text-align: center; transition: border-color 0.3s ease, background 0.3s ease; }
                #math_captcha_<?php echo esc_attr( $unique ); ?>.snn-correct { border-color: #27ae60; background: #e8f5e9; }
                .snn-captcha-success { color: #27ae60; font-size: 13px; font-weight: bold; margin-top: 6px; text-align: center; display: none; }
                .snn-captcha-hint { font-size: 11px; color: #787c82; text-align: center; margin-top: 4px; }
            </style>

            <span class="snn-captcha-label"><?php _e('Verify you are human:', 'snn'); ?></span>
            <canvas id="captchaCanvas_<?php echo esc_attr( $unique ); ?>" width="280" height="100" title="<?php esc_attr_e('Click to refresh', 'snn'); ?>"></canvas>
            <input type="text" name="math_captcha" id="math_captcha_<?php echo esc_attr( $unique ); ?>" placeholder="?" autocomplete="off" class="input">
            <div class="snn-captcha-success" id="captchaSuccess_<?php echo esc_attr( $unique ); ?>">&#10003; <?php _e('Verified!', 'snn'); ?></div>
            <div class="snn-captcha-hint"><?php _e('Click the image for a new equation', 'snn'); ?></div>

            <input type="hidden" name="captcha_solution" id="captcha_solution_<?php echo esc_attr( $unique ); ?>" value="">
            <input type="hidden" name="js_enabled" value="no" id="js_enabled_<?php echo esc_attr( $unique ); ?>">
        </div>

        <script type="text/javascript">
            (function () {
                const uniqueId = '<?php echo esc_js( $unique ); ?>';
                const container = document.getElementById('captcha_container_' + uniqueId);
                if (!container) return;

                container.style.display = 'block';
                document.getElementById('js_enabled_' + uniqueId).value = 'yes';

                const canvas = document.getElementById('captchaCanvas_' + uniqueId);
                const ctx = canvas.getContext('2d');
                const mathInput = document.getElementById('math_captcha_' + uniqueId);
                const successMsg = document.getElementById('captchaSuccess_' + uniqueId);
                const solutionInput = document.getElementById('captcha_solution_' + uniqueId);

                const n1 = parseInt(window.atob("<?php echo esc_js( $encodedNumber1 ); ?>"), 10);
                const n2 = parseInt(window.atob("<?php echo esc_js( $encodedNumber2 ); ?>"), 10);
                const correctSum = n1 + n2;
                solutionInput.value = correctSum;

                function drawNoise() {
                    for (let i = 0; i < 8; i++) {
                        ctx.strokeStyle = `rgba(0,0,0,${Math.random() * 0.1})`;
                        ctx.lineWidth = Math.random() * 2;
                        ctx.beginPath();
                        ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
                        ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
                        ctx.stroke();
                    }
                }

                function generateCaptcha() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    drawNoise();
                    const items = [];
                    const minDistance = 60;

                    function isColliding(x, y) {
                        return items.some(item => Math.sqrt((item.x - x)**2 + (item.y - y)**2) < minDistance);
                    }

                    // Real problem
                    let rx, ry;
                    let attempts1 = 0;
                    do { 
                        rx = Math.random() * (canvas.width - 100) + 20; 
                        ry = Math.random() * (canvas.height - 40) + 30; 
                        attempts1++;
                    } while (isColliding(rx, ry) && attempts1 < 50);
                    items.push({ text: `${n1} + ${n2} = ?`, x: rx, y: ry, color: '#000000', size: 28, bold: true });

                    // Decoys
                    const colors = ['#e74c3c', '#3498db', '#9b59b6', '#f1c40f', '#e67e22'];
                    for (let i = 0; i < 4; i++) {
                        let dx, dy;
                        let attempts2 = 0;
                        do { 
                            dx = Math.random() * (canvas.width - 80) + 10; 
                            dy = Math.random() * (canvas.height - 30) + 25; 
                            attempts2++;
                        } while (isColliding(dx, dy) && attempts2 < 50);
                        items.push({ text: `${Math.floor(Math.random()*9)+1} + ${Math.floor(Math.random()*9)+1} = ?`, x: dx, y: dy, color: colors[i], size: Math.floor(Math.random()*4)+14, bold: false });
                    }

                    items.forEach(item => {
                        ctx.fillStyle = item.color;
                        ctx.font = `${item.bold ? 'bold' : 'italic'} ${item.size}px Arial`;
                        ctx.save();
                        ctx.translate(item.x, item.y);
                        ctx.rotate((Math.random() - 0.5) * 0.1);
                        ctx.fillText(item.text, 0, 0);
                        ctx.restore();
                    });

                    // Reset state on canvas refresh
                    mathInput.value = '';
                    mathInput.classList.remove('snn-correct');
                    successMsg.style.display = 'none';
                }

                mathInput.addEventListener('input', function() {
                    if (parseInt(this.value.trim(), 10) === correctSum) {
                        this.classList.add('snn-correct');
                        successMsg.style.display = 'block';
                    } else {
                        this.classList.remove('snn-correct');
                        successMsg.style.display = 'none';
                    }
                });

                canvas.addEventListener('click', generateCaptcha);
                
                window.requestAnimationFrame(() => {
                    generateCaptcha();
                });
            })();
        </script>
        <?php
}

// Add captcha to various WP forms.
add_action( 'login_form', 'snn_add_math_captcha' );
add_action( 'register_form', 'snn_add_math_captcha' );
add_action( 'lostpassword_form', 'snn_add_math_captcha' );

add_action( 'woocommerce_login_form', 'snn_add_math_captcha' );
add_action( 'woocommerce_register_form', 'snn_add_math_captcha' );
add_action( 'woocommerce_lostpassword_form', 'snn_add_math_captcha' );

function snn_check_captcha() {
    $options      = get_option( 'snn_security_options' );
    $captcha_type = $options['captcha_type'] ?? 'none';

    // Dispatch to Turnstile validation if that type is selected.
    if ( $captcha_type === 'turnstile' ) {
        return snn_check_turnstile();
    }

    if ( $captcha_type !== 'math' ) {
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
    $options     = get_option( 'snn_security_options' );
    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type === 'math' ) {
        // Only validate captcha during actual form submissions (POST requests)
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $current_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

            if ( $current_action === 'login' || ! isset( $_REQUEST['action'] ) ) {
                if ( ! snn_check_captcha() ) {
                    $result = new WP_Error(
                        'captcha_error',
                        __("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math equation.", "snn"), "snn")
                    );
                }
            }
        }
    }
    return $result;
}
add_filter( 'authenticate', 'snn_validate_math_captcha', 30, 3 );

function snn_validate_registration_captcha( $errors, $sanitized_user_login, $user_email ) {
    $options     = get_option( 'snn_security_options' );
    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type === 'math' ) {
        if ( ! snn_check_captcha() ) {
            $errors->add(
                'captcha_error',
                __("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math equation.", "snn"), "snn")
            );
        }
    }
    return $errors;
}
add_filter( 'registration_errors', 'snn_validate_registration_captcha', 10, 3 );

function snn_validate_lostpassword_captcha( $errors, $user_login ) {
    $options     = get_option( 'snn_security_options' );
    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type === 'math' ) {
        if ( ! snn_check_captcha() ) {
            $errors->add(
                'captcha_error',
                __("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math equation.", "snn"), "snn")
            );
        }
    }
    return $errors;
}
add_filter( 'lostpassword_post_errors', 'snn_validate_lostpassword_captcha', 10, 2 );

function snn_add_math_captcha_to_comment_textarea( $comment_field ) {
    if ( ! is_user_logged_in() ) {
        ob_start();
        snn_add_math_captcha();
        $captcha = ob_get_clean();
        return $comment_field . $captcha;
    }
    return $comment_field;
}
add_filter( 'comment_form_field_comment', 'snn_add_math_captcha_to_comment_textarea' );

function snn_validate_comment_captcha( $commentdata ) {
    $options = get_option( 'snn_security_options' );

    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type === 'math' && ! is_user_logged_in() ) {
        if ( ! snn_check_captcha() ) {
            wp_die(__("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math equation.", "snn"), "snn"));
        }
    }
    return $commentdata;
}
add_filter( 'preprocess_comment', 'snn_validate_comment_captcha' );
?>
