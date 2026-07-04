<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cloudflare Turnstile Captcha Integration
 *
 * Provides an alternative captcha method using Cloudflare's privacy-focused,
 * invisible CAPTCHA alternative. Requires a Cloudflare account and
 * site key + secret key from the Turnstile dashboard.
 *
 * @see https://developers.cloudflare.com/turnstile/
 */

/**
 * Check if Turnstile is properly configured (both keys set).
 */
function snn_turnstile_is_configured() {
    $options = get_option( 'snn_security_options', array() );
    return ! empty( $options['turnstile_site_key'] ) && ! empty( $options['turnstile_secret_key'] );
}

/**
 * Render the Cloudflare Turnstile widget on a form.
 */
function snn_add_turnstile_captcha() {
    $options = get_option( 'snn_security_options', array() );

    if ( ! snn_turnstile_is_configured() ) {
        return;
    }

    $site_key      = $options['turnstile_site_key'];
    $theme         = $options['turnstile_theme'] ?? 'auto';
    $size          = $options['turnstile_size'] ?? 'normal';
    $unique        = uniqid( 'ts_' );
    ?>
    <style>
        .snn-turnstile-wrapper {
            max-width: 100%;
            overflow: hidden;
            margin: 15px 0;
            box-sizing: border-box;
        }
        .snn-turnstile-wrapper iframe {
            max-width: 100% !important;
        }
    </style>
    <div id="turnstile_container_<?php echo esc_attr( $unique ); ?>" class="snn-turnstile-wrapper">
        <div
            class="cf-turnstile"
            data-sitekey="<?php echo esc_attr( $site_key ); ?>"
            data-theme="<?php echo esc_attr( $theme ); ?>"
            data-size="<?php echo esc_attr( $size ); ?>"
        ></div>
    </div>
    <?php
}

/**
 * Enqueue the Turnstile API script on pages where the captcha is needed.
 * Hooked to login_enqueue_scripts for WP login/register/lostpassword pages.
 */
function snn_enqueue_turnstile_script() {
    $options     = get_option( 'snn_security_options', array() );
    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type !== 'turnstile' || ! snn_turnstile_is_configured() ) {
        return;
    }

    wp_enqueue_script(
        'cf-turnstile',
        'https://challenges.cloudflare.com/turnstile/v0/api.js',
        array(),
        null,
        array( 'strategy' => 'defer' )
    );
}
add_action( 'login_enqueue_scripts', 'snn_enqueue_turnstile_script' );

/**
 * Enqueue Turnstile script on non-login pages (comments, WooCommerce, etc.).
 */
function snn_enqueue_turnstile_script_public() {
    $options     = get_option( 'snn_security_options', array() );
    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type !== 'turnstile' || ! snn_turnstile_is_configured() ) {
        return;
    }

    // Only enqueue on pages that might have captcha-protected forms.
    if ( is_singular() || is_page() || ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
        wp_enqueue_script(
            'cf-turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            array(),
            null,
            array( 'strategy' => 'defer' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'snn_enqueue_turnstile_script_public' );

/**
 * Validate a Turnstile token via the Cloudflare Siteverify API.
 *
 * @param string $token The cf-turnstile-response token from the form.
 * @return bool True if valid, false otherwise.
 */
function snn_validate_turnstile_token( $token ) {
    $options = get_option( 'snn_security_options', array() );
    $secret  = $options['turnstile_secret_key'] ?? '';

    if ( empty( $secret ) || empty( $token ) ) {
        return false;
    }

    $response = wp_remote_post(
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        array(
            'timeout' => 10,
            'body'    => array(
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        error_log( 'Turnstile validation error: ' . $response->get_error_message() );
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['success'] ) ) {
        $error_codes = isset( $body['error-codes'] ) ? implode( ', ', $body['error-codes'] ) : 'unknown';
        error_log( 'Turnstile validation failed: ' . $error_codes );
        return false;
    }

    return true;
}

/**
 * Check if the Turnstile captcha passed validation.
 * Mirrors snn_check_captcha() for the math captcha.
 *
 * @return bool True if valid or captcha not of turnstile type.
 */
function snn_check_turnstile() {
    $options     = get_option( 'snn_security_options', array() );
    $captcha_type = $options['captcha_type'] ?? 'none';

    if ( $captcha_type !== 'turnstile' ) {
        return true;
    }

    if ( ! snn_turnstile_is_configured() ) {
        // Turnstile is selected but not configured — allow through to avoid lockout
        // but log a warning.
        error_log( 'Turnstile captcha is selected but site/secret keys are missing. No protection is active.' );
        return true;
    }

    $token = $_POST['cf-turnstile-response'] ?? '';

    if ( empty( $token ) ) {
        return false;
    }

    return snn_validate_turnstile_token( $token );
}
