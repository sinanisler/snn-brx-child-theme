<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snn_add_math_captcha() {
    $options = get_option( 'snn_security_options' );

    if ( isset( $options['enable_math_captcha'] ) && $options['enable_math_captcha'] ) {
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
                .verification-container { background: #fff;  border-radius: 8px;   position: relative; min-height: 160px;  }
                .step-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #646970; margin-bottom: 10px; display: block; font-weight: 600; }
                #math_step_<?php echo esc_attr( $unique ); ?> { transition: opacity 0.3s ease; }
                #captchaCanvas_<?php echo esc_attr( $unique ); ?> { background: #f9f9f9; border-radius: 4px; display: block; margin-bottom: 10px;   width: 100%; height: auto;  }
                #math_captcha_<?php echo esc_attr( $unique ); ?> { font-size: 18px; width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; box-sizing: border-box; text-align: center; }
                .drag-area { width: 100%; height: 80px; background: #f0f0f1; border: 2px dashed #c3c4c7; border-radius: 8px; position: relative; overflow: hidden; margin-top: 5px; }
                .drop-target { position: absolute; right: 10px; top: 10px; width: 56px; height: 56px; border: 2px solid #dcdcde; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #8c8f94; text-align: center; }
                .drag-item { position: absolute; left: 10px; top: 10px; width: 56px; height: 56px; background: #2271b1; border-radius: 50%; cursor: grab; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.15); z-index: 10; touch-action: none; }
                .success-msg { color: #27ae60; font-size: 13px; font-weight: bold; margin-top: 10px; text-align: center; }
                .step-label b{color:green}
            </style>

            <div class="verification-container">
                <!-- STEP 1: MATH -->
                <div id="math_step_<?php echo esc_attr( $unique ); ?>">
                    <span class="step-label"><?php _e('Step 1: Solve <b>Math</b> Equation', 'snn'); ?></span>
                    <canvas id="captchaCanvas_<?php echo esc_attr( $unique ); ?>" width="280" height="100"></canvas>
                    <input type="text" name="math_captcha" id="math_captcha_<?php echo esc_attr( $unique ); ?>" placeholder="?" autocomplete="off" class="input">
                </div>

                <!-- STEP 2: DRAG DROP -->
                <div id="drag_step_<?php echo esc_attr( $unique ); ?>" style="display: none; flex-direction: column; align-items: center; justify-content: center;">
                    <span class="step-label"><?php _e('Step 2: Verify Identity', 'snn'); ?></span>
                    <div class="drag-area" id="dragArea_<?php echo esc_attr( $unique ); ?>">
                        <div class="drop-target" id="dropTarget_<?php echo esc_attr( $unique ); ?>"><?php _e('Target', 'snn'); ?></div>
                        <div class="drag-item" id="dragItem_<?php echo esc_attr( $unique ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 19-3 3-3-3M19 15l3-3-3-3M5 9l-3 3 3 3M9 5l3-3 3 3M12 2v20M2 12h20"/></svg>
                        </div>
                    </div>
                    <div class="success-msg" id="successMsg_<?php echo esc_attr( $unique ); ?>" style="display: none;"><?php _e('Identity Verified!', 'snn'); ?></div>
                </div>
            </div>

            <input type="hidden" name="captcha_solution" id="captcha_solution_<?php echo esc_attr( $unique ); ?>" value="">
            <input type="hidden" name="drag_verified" id="drag_verified_<?php echo esc_attr( $unique ); ?>" value="no">
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
                const mathStep = document.getElementById('math_step_' + uniqueId);
                const dragStep = document.getElementById('drag_step_' + uniqueId);
                const dragItem = document.getElementById('dragItem_' + uniqueId);
                const dragArea = document.getElementById('dragArea_' + uniqueId);
                const dropTarget = document.getElementById('dropTarget_' + uniqueId);
                const successMsg = document.getElementById('successMsg_' + uniqueId);
                const dragVerifiedInput = document.getElementById('drag_verified_' + uniqueId);
                const solutionInput = document.getElementById('captcha_solution_' + uniqueId);

                // Fix: Find and disable the submit button even if it's parsed after this script
                let submitButton = null;
                const setupSubmitButton = () => {
                    const form = container.closest('form');
                    if (form && !submitButton) {
                        submitButton = form.querySelector('input[type="submit"], button[type="submit"]');
                        if (submitButton && dragVerifiedInput.value !== 'yes') {
                            submitButton.disabled = true;
                        }
                    }
                };

                setupSubmitButton();
                document.addEventListener('DOMContentLoaded', setupSubmitButton);

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
                    items.push({ text: `${n1} + ${n2} = ?`, x: rx, y: ry, color: '#27ae60', size: 24, bold: true });

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
                        items.push({ text: `${Math.floor(Math.random()*9)+1} + ${Math.floor(Math.random()*9)+1} = ?`, x: dx, y: dy, color: colors[i], size: Math.floor(Math.random()*4)+16, bold: false });
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
                }

                mathInput.addEventListener('input', function() {
                    if (parseInt(this.value.trim(), 10) === correctSum) {
                        mathStep.style.opacity = '0';
                        setTimeout(() => {
                            mathStep.style.display = 'none';
                            dragStep.style.display = 'flex';
                        }, 300);
                    }
                });

                // Drag Logic
                let isDragging = false, startX, startY, initialX, initialY;
                const startDrag = (e) => {
                    isDragging = true;
                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
                    startX = clientX; startY = clientY;
                    initialX = dragItem.offsetLeft; initialY = dragItem.offsetTop;
                    dragItem.style.transition = 'none';
                };

                const moveDrag = (e) => {
                    if (!isDragging) return;
                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
                    let nx = initialX + (clientX - startX), ny = initialY + (clientY - startY);
                    nx = Math.max(0, Math.min(nx, dragArea.offsetWidth - dragItem.offsetWidth));
                    ny = Math.max(0, Math.min(ny, dragArea.offsetHeight - dragItem.offsetHeight));
                    dragItem.style.left = nx + 'px'; dragItem.style.top = ny + 'px';

                    const tr = dropTarget.getBoundingClientRect(), ir = dragItem.getBoundingClientRect();
                    const dist = Math.sqrt(((ir.left + ir.width/2) - (tr.left + tr.width/2))**2 + ((ir.top + ir.height/2) - (tr.top + tr.height/2))**2);
                    dropTarget.style.background = dist < 20 ? '#e8f5e9' : '#fff';
                };

                const endDrag = () => {
                    if (!isDragging) return;
                    isDragging = false;
                    const tr = dropTarget.getBoundingClientRect(), ir = dragItem.getBoundingClientRect();
                    const dist = Math.sqrt(((ir.left + ir.width/2) - (tr.left + tr.width/2))**2 + ((ir.top + ir.height/2) - (tr.top + tr.height/2))**2);

                    if (dist < 20) {
                        dragItem.style.left = dropTarget.offsetLeft + 'px';
                        dragItem.style.top = dropTarget.offsetTop + 'px';
                        dragItem.style.background = '#27ae60';
                        dropTarget.style.display = 'none';
                        successMsg.style.display = 'block';
                        dragVerifiedInput.value = 'yes';
                        if (submitButton) submitButton.disabled = false;
                    } else {
                        dragItem.style.transition = 'all 0.3s ease';
                        dragItem.style.left = '10px'; dragItem.style.top = '10px';
                    }
                };

                dragItem.addEventListener('mousedown', startDrag);
                window.addEventListener('mousemove', moveDrag);
                window.addEventListener('mouseup', endDrag);
                dragItem.addEventListener('touchstart', startDrag);
                window.addEventListener('touchmove', moveDrag);
                window.addEventListener('touchend', endDrag);

                canvas.addEventListener('click', generateCaptcha);
                
                window.requestAnimationFrame(() => {
                    generateCaptcha();
                });
            })();
        </script>
        <?php
    }
}

// Add captcha to various WP forms.
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
    $drag_verified = ( isset( $_POST['drag_verified'] ) && $_POST['drag_verified'] === 'yes' );

    if ( ! $js_enabled || ! $drag_verified ) {
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
        // Only validate captcha during actual form submissions (POST requests)
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $current_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

            if ( $current_action === 'login' || ! isset( $_REQUEST['action'] ) ) {
                if ( ! snn_check_captcha() ) {
                    $result = new WP_Error(
                        'captcha_error',
                        __("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math and drag the circle.", "snn"), "snn")
                    );
                }
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
                __("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math and drag the circle.", "snn"), "snn")
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
                __("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math and drag the circle.", "snn"), "snn")
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

    if ( ! empty( $options['enable_math_captcha'] ) && ! is_user_logged_in() ) {
        if ( ! snn_check_captcha() ) {
            wp_die(__("<strong>ERROR</strong>: " . __("Verification failed. Please solve the math and drag the circle.", "snn"), "snn"));
        }
    }
    return $commentdata;
}
add_filter( 'preprocess_comment', 'snn_validate_comment_captcha' );
?>
