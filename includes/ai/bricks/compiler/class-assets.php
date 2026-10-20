<?php
/**
 * Media-library assets for server-side builds.
 *
 * Same rules as the builder's AJAX handlers (snn_upload_design_asset,
 * snn_upload_design_svg, snn_save_image_to_library) without the nonce/$_FILES
 * plumbing, so abilities and the zip importer can call them directly:
 *  - rasters: png/jpg/gif/webp/avif only, 10 MB, real bytes must be an image;
 *  - SVGs: Bricks' SVG upload permission + Bricks' sanitizer + our sanitizer,
 *    64 KB, identical markup reuses its attachment;
 *  - URLs: http(s) only, fetched with the core "safe" HTTP API, which rejects
 *    private, loopback and link-local hosts (same policy as bricks/upload-media).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Assets {

    const MAX_IMAGE_BYTES = 10485760; // 10 MB
    const MAX_SVG_BYTES   = 65536;    // 64 KB

    const RASTER_MIMES = array(
        'png'      => 'image/png',
        'jpg|jpeg' => 'image/jpeg',
        'gif'      => 'image/gif',
        'webp'     => 'image/webp',
        'avif'     => 'image/avif',
    );

    private static function load_admin_includes() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    private static function payload( $attachment_id ) {
        return array(
            'id'       => (int) $attachment_id,
            'url'      => wp_get_attachment_url( $attachment_id ),
            'filename' => basename( (string) get_attached_file( $attachment_id ) ),
        );
    }

    public static function can_upload_svg() {
        return current_user_can( 'upload_files' )
            && class_exists( '\Bricks\Capabilities' )
            && \Bricks\Capabilities::current_user_can_upload_svg();
    }

    /** Store raster image bytes as an attachment. @return array|WP_Error {id,url,filename} */
    public static function store_raster( $bytes, $filename, $alt = '' ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'snn_upload_forbidden', 'You are not allowed to upload files.' );
        }
        $bytes = (string) $bytes;
        if ( $bytes === '' ) {
            return new WP_Error( 'snn_empty_file', 'The file is empty.' );
        }
        if ( strlen( $bytes ) > self::MAX_IMAGE_BYTES ) {
            return new WP_Error( 'snn_file_too_large', 'Image is larger than 10 MB.' );
        }
        self::load_admin_includes();

        $filename = sanitize_file_name( $filename ? $filename : 'image.png' );
        $tmp      = wp_tempnam( $filename );
        if ( ! $tmp || false === file_put_contents( $tmp, $bytes ) ) {
            return new WP_Error( 'snn_tmp_failed', 'Could not write a temporary file.' );
        }
        if ( ! @getimagesize( $tmp ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_not_image', 'File is not a readable image.' );
        }

        $file = array( 'name' => $filename, 'tmp_name' => $tmp, 'error' => 0, 'size' => filesize( $tmp ) );
        $upload = wp_handle_sideload( $file, array( 'test_form' => false, 'mimes' => self::RASTER_MIMES ) );
        if ( ! is_array( $upload ) || ! empty( $upload['error'] ) ) {
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_upload_rejected', is_array( $upload ) && ! empty( $upload['error'] ) ? $upload['error'] : 'Upload rejected.' );
        }
        if ( strpos( (string) $upload['type'], 'image/' ) !== 0 ) {
            @unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_not_image', 'File is not an image.' );
        }

        $attachment_id = wp_insert_attachment( array(
            'post_mime_type' => $upload['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $upload['file'] ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ), $upload['file'] );
        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            @unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_attachment_failed', 'Could not create the attachment.' );
        }
        wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
        if ( $alt !== '' ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }
        return self::payload( $attachment_id );
    }

    /** Store SVG markup as an attachment (Bricks permission + both sanitizers). */
    public static function store_svg( $markup, $name = 'icon' ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'snn_upload_forbidden', 'You are not allowed to upload files.' );
        }
        if ( ! self::can_upload_svg() ) {
            return new WP_Error( 'svg_not_allowed', 'SVG uploads are not enabled for your role in Bricks settings.' );
        }
        $markup = SNN_Bricks_Svg_Sanitizer::sanitize( $markup );
        if ( $markup === '' ) {
            return new WP_Error( 'snn_bad_svg', 'Not a readable SVG document.' );
        }
        if ( strlen( $markup ) > self::MAX_SVG_BYTES ) {
            return new WP_Error( 'snn_svg_too_large', 'SVG is larger than 64 KB.' );
        }

        $hash     = md5( $markup );
        $existing = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_snn_design_svg_hash', // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value'     => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery
        ) );
        if ( $existing ) {
            $path = get_attached_file( $existing[0] );
            if ( $path && file_exists( $path ) ) {
                return self::payload( $existing[0] );
            }
        }

        self::load_admin_includes();
        $base = preg_replace( '/\.svg$/i', '', sanitize_file_name( (string) $name ) );
        if ( $base === '' ) {
            $base = 'icon';
        }
        $tmp = wp_tempnam( $base . '.svg' );
        if ( ! $tmp || false === file_put_contents( $tmp, $markup ) ) {
            return new WP_Error( 'snn_tmp_failed', 'Could not write a temporary file.' );
        }
        $file = array( 'name' => $base . '.svg', 'type' => 'image/svg+xml', 'tmp_name' => $tmp, 'error' => 0, 'size' => filesize( $tmp ) );

        // A sideload fires the sideload prefilter, not the upload prefilter Bricks sanitizes on.
        $file = apply_filters( 'wp_handle_upload_prefilter', $file );
        if ( ! empty( $file['error'] ) ) {
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_svg_rejected', is_string( $file['error'] ) ? $file['error'] : 'SVG rejected by the sanitizer.' );
        }
        $clean = (string) file_get_contents( $tmp );
        if ( stripos( $clean, '<svg' ) === false || preg_match( '/<script|\son[a-z]+\s*=|javascript:/i', $clean ) ) {
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_svg_unsafe', 'SVG failed sanitization.' );
        }

        $upload = wp_handle_sideload( $file, array( 'test_form' => false, 'mimes' => array( 'svg' => 'image/svg+xml' ) ) );
        if ( ! is_array( $upload ) || ! empty( $upload['error'] ) ) {
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_upload_rejected', is_array( $upload ) && ! empty( $upload['error'] ) ? $upload['error'] : 'Upload rejected.' );
        }
        $attachment_id = wp_insert_attachment( array(
            'post_mime_type' => 'image/svg+xml',
            'post_title'     => $base,
            'post_content'   => '',
            'post_status'    => 'inherit',
        ), $upload['file'] );
        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            @unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new WP_Error( 'snn_attachment_failed', 'Could not create the attachment.' );
        }
        update_post_meta( $attachment_id, '_snn_design_svg_hash', $hash );
        return self::payload( $attachment_id );
    }

    /** Fetch a public http(s) URL and store it (raster or SVG). */
    public static function store_from_url( $url, $alt = '' ) {
        $url = esc_url_raw( trim( (string) $url ) );
        $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
        if ( ! $url || ! in_array( $scheme, array( 'http', 'https' ), true ) || ! wp_http_validate_url( $url ) ) {
            return new WP_Error( 'snn_bad_url', 'Only public http(s) URLs are allowed.' );
        }
        $response = wp_safe_remote_get( $url, array(
            'timeout'             => 20,
            'redirection'         => 3,
            'limit_response_size' => self::MAX_IMAGE_BYTES + 1,
        ) );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        if ( (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return new WP_Error( 'snn_fetch_failed', 'The URL returned HTTP ' . (int) wp_remote_retrieve_response_code( $response ) . '.' );
        }
        $bytes = (string) wp_remote_retrieve_body( $response );
        $name  = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
        $type  = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
        if ( strpos( $type, 'svg' ) !== false || preg_match( '/\.svg$/i', $name ) || preg_match( '/^\s*(<\?xml[^>]*>\s*)?<svg[\s>]/i', $bytes ) ) {
            return self::store_svg( $bytes, $name ? $name : 'image.svg' );
        }
        if ( ! preg_match( '/\.(png|jpe?g|gif|webp|avif)$/i', $name ) ) {
            $ext  = array( 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/avif' => 'avif' );
            $mime = trim( explode( ';', $type )[0] );
            $name = ( $name ? preg_replace( '/\.[^.]*$/', '', $name ) : 'image' ) . '.' . ( $ext[ $mime ] ?? 'png' );
        }
        return self::store_raster( $bytes, $name, $alt );
    }

    /** A readable SVG file name: aria-label, <title> or first class, else "icon". */
    private static function svg_file_name( $svg ) {
        $label = SNN_Bricks_Dom::attr( $svg, 'aria-label' );
        if ( ! $label ) {
            $titles = SNN_Bricks_Dom::find_tags( $svg, array( 'title' ) );
            $label  = $titles ? SNN_Bricks_Dom::text( $titles[0] ) : '';
        }
        if ( ! $label ) {
            $label = preg_split( '/\s+/', trim( (string) SNN_Bricks_Dom::attr( $svg, 'class' ) ) )[0];
        }
        $slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $label ) ), '-' );
        return ( substr( $slug, 0, 40 ) ?: 'icon' ) . '.svg';
    }

    /**
     * SvgAssets.prepare(): upload every liftable inline SVG in the HTML.
     * @return array{assets: array, uploaded: int, reused: int, skipped: string[], notAllowed: int}
     */
    public static function prepare_svgs( $html ) {
        $result = array( 'assets' => array(), 'uploaded' => 0, 'reused' => 0, 'skipped' => array(), 'notAllowed' => 0 );
        $parsed = SNN_Bricks_Dom::parse( $html );
        $svgs   = SNN_Bricks_Features::svg_upload_candidates( $parsed['body'] );
        if ( ! $svgs ) {
            return $result;
        }
        if ( ! self::can_upload_svg() ) {
            $result['notAllowed'] = count( $svgs );
            return $result;
        }
        foreach ( $svgs as $svg ) {
            $key = SNN_Bricks_Features::svg_key( $svg );
            if ( isset( $result['assets'][ $key ] ) ) {
                $result['reused']++;
                continue;
            }
            $asset = self::store_svg( $key, self::svg_file_name( $svg ) );
            if ( is_wp_error( $asset ) ) {
                $result['skipped'][] = $asset->get_error_message();
                continue;
            }
            $result['assets'][ $key ] = $asset;
            $result['uploaded']++;
        }
        return $result;
    }
}
