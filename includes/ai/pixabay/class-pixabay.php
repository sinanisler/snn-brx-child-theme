<?php
/**
 * Pixabay: the search API, the cache and the media-library import, in one place.
 *
 * Used by the snn/pixabay-image ability and by the Bricks builder chat (the
 * snn_pixabay_image proxy and the save-to-library handler), so the request rules
 * live once.
 *
 * Pixabay's API rules this follows: API requests are cached for 24 hours, result
 * images may only be shown briefly, and an image is saved to our own media library
 * before the site serves it. Imports here always keep a local copy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Pixabay {

    const API_URL         = 'https://pixabay.com/api/';
    const CACHE_TTL       = DAY_IN_SECONDS;
    const MAX_PER_PAGE    = 20;
    const MAX_IMAGE_BYTES = 10485760; // 10 MB

    const META_ID           = '_snn_pixabay_id';
    const META_PHOTOGRAPHER = '_snn_pixabay_photographer';
    const META_PAGE_URL     = '_snn_pixabay_page_url';

    /**
     * Search Pixabay. Hits come back in our own shape and are cached per request
     * and per image ID.
     *
     * @param array $args query, per_page (3 to 20), orientation, order, min_width.
     * @return array|WP_Error Hits. Each keeps large_url, which only the import uses.
     */
    public static function search( array $args ) {
        $key = self::api_key();
        if ( '' === $key ) {
            return self::no_key_error();
        }

        // Built without the key, so the cache key never holds it.
        $params = array(
            'q'           => mb_substr( (string) ( $args['query'] ?? '' ), 0, 100 ),
            'image_type'  => 'photo',
            'safesearch'  => 'true',
            'lang'        => 'en',
            'per_page'    => min( self::MAX_PER_PAGE, max( 3, (int) ( $args['per_page'] ?? 10 ) ) ),
            'order'       => in_array( $args['order'] ?? '', array( 'popular', 'latest' ), true ) ? $args['order'] : 'popular',
            'orientation' => in_array( $args['orientation'] ?? '', array( 'horizontal', 'vertical' ), true ) ? $args['orientation'] : 'all',
        );
        if ( ! empty( $args['min_width'] ) ) {
            $params['min_width'] = (int) $args['min_width'];
        }

        $cache_key = 'snn_pixabay_s_' . md5( wp_json_encode( $params ) );
        $hits      = get_transient( $cache_key );
        if ( false === $hits ) {
            $data = self::request( $params + array( 'key' => $key ) );
            if ( is_wp_error( $data ) ) {
                return $data;
            }

            $hits = array();
            foreach ( (array) ( $data['hits'] ?? array() ) as $raw ) {
                $hit = self::normalize( $raw );
                if ( $hit ) {
                    $hits[] = $hit;
                    set_transient( self::hit_key( $hit['pixabay_id'] ), $hit, self::CACHE_TTL );
                }
            }
            set_transient( $cache_key, $hits, self::CACHE_TTL );
        }

        return $hits;
    }

    /**
     * One hit by Pixabay ID. Served from the cache when a search has already
     * returned it.
     *
     * @return array|WP_Error
     */
    public static function find( $id ) {
        $id     = (int) $id;
        $cached = get_transient( self::hit_key( $id ) );
        if ( is_array( $cached ) && ! empty( $cached['large_url'] ) ) {
            return $cached;
        }

        $key = self::api_key();
        if ( '' === $key ) {
            return self::no_key_error();
        }

        $data = self::request( array( 'key' => $key, 'id' => $id ) );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $hits = is_array( $data['hits'] ?? null ) ? $data['hits'] : array();
        $hit  = $hits ? self::normalize( reset( $hits ) ) : null;
        if ( ! $hit ) {
            return new WP_Error( 'snn_pixabay_not_found', 'Pixabay has no image with that ID.' );
        }

        set_transient( self::hit_key( $id ), $hit, self::CACHE_TTL );
        return $hit;
    }

    /**
     * Save a Pixabay image into the media library. An ID that was imported before
     * returns the existing attachment, so repeating a call does not add a copy.
     *
     * @return array|WP_Error {id, url, filename, pixabay_id, photographer, page_url}
     */
    public static function import( $id, $alt = '' ) {
        $id = (int) $id;

        $existing = self::attachment_for( $id );
        if ( $existing ) {
            return self::payload( $existing );
        }

        // Not loaded outside wp-admin (REST and MCP calls), and the download and sideload need them.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $hit = self::find( $id );
        if ( is_wp_error( $hit ) ) {
            return $hit;
        }

        $tmp = download_url( $hit['large_url'] );
        if ( is_wp_error( $tmp ) ) {
            // A cached link can expire, so look the image up again once.
            delete_transient( self::hit_key( $id ) );
            $hit = self::find( $id );
            if ( is_wp_error( $hit ) ) {
                return $hit;
            }
            $tmp = download_url( $hit['large_url'] );
            if ( is_wp_error( $tmp ) ) {
                return new WP_Error( 'snn_pixabay_download_failed', 'Pixabay did not return the image file.' );
            }
        }

        if ( (int) filesize( $tmp ) > self::MAX_IMAGE_BYTES ) {
            wp_delete_file( $tmp );
            return new WP_Error( 'snn_pixabay_too_large', 'Image is larger than 10 MB.' );
        }

        $ext = strtolower( pathinfo( (string) wp_parse_url( $hit['large_url'], PHP_URL_PATH ), PATHINFO_EXTENSION ) );
        $ext = in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ), true ) ? $ext : 'jpg';

        $title         = trim( (string) strtok( $hit['tags'], ',' ) );
        $attachment_id = media_handle_sideload(
            array( 'name' => 'pixabay-' . $id . '.' . $ext, 'tmp_name' => $tmp ),
            0,
            null,
            array( 'post_title' => '' !== $title ? $title : 'Pixabay ' . $id )
        );
        if ( is_wp_error( $attachment_id ) ) {
            if ( file_exists( $tmp ) ) {
                wp_delete_file( $tmp );
            }
            return $attachment_id;
        }

        update_post_meta( $attachment_id, self::META_ID, $id );
        update_post_meta( $attachment_id, self::META_PHOTOGRAPHER, sanitize_text_field( $hit['photographer'] ) );
        update_post_meta( $attachment_id, self::META_PAGE_URL, esc_url_raw( $hit['page_url'] ) );
        if ( '' !== $alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }

        return self::payload( $attachment_id );
    }

    /** A hit as the ability returns it: the download link stays server side. */
    public static function public_view( $hit ) {
        unset( $hit['large_url'] );
        return $hit;
    }

    private static function api_key() {
        return (string) get_option( 'snn_pixabay_api_key', '' );
    }

    private static function no_key_error() {
        return new WP_Error( 'snn_pixabay_no_key', 'No Pixabay API key is set. Add it under AI Agent & Chat Overlay Settings.' );
    }

    private static function hit_key( $id ) {
        return 'snn_pixabay_h_' . (int) $id;
    }

    /** One GET to the API. Maps rate limits to a 429 error and keeps the key out of messages. */
    private static function request( array $params ) {
        $response = wp_remote_get( add_query_arg( $params, self::API_URL ), array( 'timeout' => 10 ) );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'snn_pixabay_unreachable', 'Could not reach Pixabay.' );
        }

        $code      = (int) wp_remote_retrieve_response_code( $response );
        $remaining = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
        if ( 429 === $code || ( '' !== $remaining && (int) $remaining <= 0 ) ) {
            $reset = (int) wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );
            if ( $reset > 0 ) {
                set_transient( 'snn_pixabay_rate_reset', time() + $reset, $reset );
            }
            return new WP_Error( 'snn_pixabay_rate_limited', 'Pixabay rate limit reached. Try again in a minute.', array( 'status' => 429 ) );
        }

        $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        if ( 200 !== $code || ! is_array( $data ) ) {
            $message = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : 'HTTP ' . $code;
            return new WP_Error( 'snn_pixabay_http', 'Pixabay returned an error: ' . $message, array( 'status' => $code ? $code : 502 ) );
        }

        return $data;
    }

    /** One raw API hit in our shape, or null when it lacks an ID or a Pixabay image link. */
    private static function normalize( $hit ) {
        if ( ! is_array( $hit ) || empty( $hit['id'] ) ) {
            return null;
        }

        $large = (string) ( $hit['largeImageURL'] ?? '' );
        if ( '' === $large ) {
            $large = (string) ( $hit['webformatURL'] ?? '' );
        }
        if ( ! self::is_pixabay_url( $large ) ) {
            return null;
        }

        $preview = (string) ( $hit['webformatURL'] ?? '' );

        return array(
            'pixabay_id'   => (int) $hit['id'],
            'preview_url'  => self::is_pixabay_url( $preview ) ? $preview : '',
            'large_url'    => $large,
            'width'        => (int) ( $hit['imageWidth'] ?? 0 ),
            'height'       => (int) ( $hit['imageHeight'] ?? 0 ),
            'tags'         => (string) ( $hit['tags'] ?? '' ),
            'views'        => (int) ( $hit['views'] ?? 0 ),
            'downloads'    => (int) ( $hit['downloads'] ?? 0 ),
            'photographer' => (string) ( $hit['user'] ?? '' ),
            'page_url'     => (string) ( $hit['pageURL'] ?? '' ),
        );
    }

    /** Only https links on pixabay.com are downloaded, so the URL in a response cannot send the import elsewhere. */
    private static function is_pixabay_url( $url ) {
        $host = (string) wp_parse_url( (string) $url, PHP_URL_HOST );
        return 'https' === wp_parse_url( (string) $url, PHP_URL_SCHEME ) && (bool) preg_match( '/(^|\.)pixabay\.com$/i', $host );
    }

    /** Attachment ID already imported for this Pixabay image, or 0. */
    private static function attachment_for( $id ) {
        $found = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value'     => $id, // phpcs:ignore WordPress.DB.SlowDBQuery
        ) );

        if ( $found && file_exists( (string) get_attached_file( $found[0] ) ) ) {
            return (int) $found[0];
        }
        return 0;
    }

    private static function payload( $attachment_id ) {
        return array(
            'id'           => (int) $attachment_id,
            'url'          => wp_get_attachment_url( $attachment_id ),
            'filename'     => basename( (string) get_attached_file( $attachment_id ) ),
            'pixabay_id'   => (int) get_post_meta( $attachment_id, self::META_ID, true ),
            'photographer' => (string) get_post_meta( $attachment_id, self::META_PHOTOGRAPHER, true ),
            'page_url'     => (string) get_post_meta( $attachment_id, self::META_PAGE_URL, true ),
        );
    }
}
