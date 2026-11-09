<?php
/**
 * Readable file names for images the AI saves to the media library.
 *
 * Before this, an imported image was named after its source ("pixabay-1234.jpg") or
 * after the CDN file. Now the name comes from the section the image sits in and what
 * it shows: hero-mountain-lake.jpg. WordPress adds -1, -2 ... when a name is taken.
 *
 * Used by the Pixabay import (includes/ai/pixabay/class-pixabay.php) and by the
 * Bricks chat's save-to-library handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_AI_Media {

    const MAX_BYTES = 10485760; // 10 MB

    /** Image types we store, keyed by the MIME type found in the downloaded bytes. */
    const EXTENSIONS = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    );

    /**
     * Slug for a file name from the first phrase that has any letters or digits.
     * Phrases go most specific first, e.g. ("hero mountain lake", "Mountain lake at dawn").
     * Accents are folded to ASCII (Turkish ş, ğ, ı included); a phrase in another script
     * yields nothing and the next one is tried.
     *
     * @return string Slug of at most 60 characters, never empty.
     */
    public static function slug_from( array $phrases ) {
        foreach ( $phrases as $phrase ) {
            $slug = strtolower( remove_accents( (string) $phrase ) );
            $slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $slug ), '-' );
            if ( '' === $slug ) {
                continue;
            }

            if ( strlen( $slug ) > 60 ) {
                $slug = substr( $slug, 0, 60 );
                $cut  = strrpos( $slug, '-' );
                if ( false !== $cut && $cut >= 20 ) {
                    $slug = substr( $slug, 0, $cut );
                }
                $slug = rtrim( $slug, '-' );
            }
            return $slug;
        }

        return 'image';
    }

    /**
     * Download a public image URL and save it under a readable name.
     *
     * @param string $url     Direct image URL (http or https).
     * @param array  $phrases Name candidates for slug_from(). The URL's own file name is not used.
     * @param string $alt     Alt text, stored on the attachment when not empty.
     * @return int|WP_Error Attachment ID.
     */
    public static function sideload_url( $url, array $phrases, $alt = '' ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $tmp = download_url( $url, 30 );
        if ( is_wp_error( $tmp ) ) {
            return $tmp;
        }

        return self::sideload( $tmp, $phrases, $alt );
    }

    /**
     * Save a file that is already on disk into the media library under a readable name.
     * The extension comes from the file's bytes, not from the URL, so a mislabelled file
     * is still stored as the type it really is. The temporary file is removed on failure.
     *
     * @param string $tmp     Path to the downloaded file.
     * @param array  $phrases Name candidates for slug_from().
     * @param string $alt     Alt text, stored on the attachment when not empty.
     * @return int|WP_Error Attachment ID.
     */
    public static function sideload( $tmp, array $phrases, $alt = '' ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        if ( (int) filesize( $tmp ) > self::MAX_BYTES ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'snn_image_too_large', 'Image is larger than 10 MB.' );
        }

        $info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $ext  = ( is_array( $info ) && isset( self::EXTENSIONS[ $info['mime'] ] ) ) ? self::EXTENSIONS[ $info['mime'] ] : '';
        if ( '' === $ext ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'snn_image_unsupported', 'The download is not a JPEG, PNG, GIF or WebP image.' );
        }

        $base          = self::slug_from( $phrases );
        $attachment_id = media_handle_sideload(
            array( 'name' => $base . '.' . $ext, 'tmp_name' => $tmp ),
            0,
            null,
            array( 'post_title' => ucfirst( str_replace( '-', ' ', $base ) ) )
        );
        if ( is_wp_error( $attachment_id ) ) {
            if ( file_exists( $tmp ) ) {
                wp_delete_file( $tmp );
            }
            return $attachment_id;
        }

        if ( '' !== $alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }

        return (int) $attachment_id;
    }
}
