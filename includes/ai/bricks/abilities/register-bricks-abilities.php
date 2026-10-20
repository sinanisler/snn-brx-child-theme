<?php
/**
 * Bricks abilities (snn/bricks-*) — loader, category and shared helpers.
 *
 * These are ordinary WordPress abilities: the theme's agents can call them over
 * REST, and the MCP Adapter exposes them as MCP tools when the "Bricks" group
 * is enabled under SNN Settings > MCP (includes/ai/mcp/).
 *
 * Loaded from functions.php after the Bricks theme is available.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$snn_bricks_dir = dirname( __DIR__ );

require_once $snn_bricks_dir . '/compiler/class-dom.php';
require_once $snn_bricks_dir . '/compiler/class-css-tokenizer.php';
require_once $snn_bricks_dir . '/compiler/class-media-matcher.php';
require_once $snn_bricks_dir . '/compiler/class-selector-matcher.php';
require_once $snn_bricks_dir . '/compiler/class-cascade-resolver.php';
require_once $snn_bricks_dir . '/compiler/class-native-layout.php';
require_once $snn_bricks_dir . '/compiler/class-element-features.php';
require_once $snn_bricks_dir . '/compiler/class-html-to-bricks.php';
require_once $snn_bricks_dir . '/compiler/class-svg-sanitizer.php';
require_once $snn_bricks_dir . '/compiler/class-assets.php';
require_once $snn_bricks_dir . '/compiler/class-zip-import.php';

require_once $snn_bricks_dir . '/writer/class-write-guard.php';
require_once $snn_bricks_dir . '/writer/class-global-classes-store.php';
require_once $snn_bricks_dir . '/writer/class-section-manifest.php';
require_once $snn_bricks_dir . '/writer/class-bricks-writer.php';
require_once $snn_bricks_dir . '/writer/class-edit-ops.php';

require_once __DIR__ . '/bricks-start-here.php';
require_once __DIR__ . '/bricks-get-page-tree.php';
require_once __DIR__ . '/bricks-find-elements.php';
require_once __DIR__ . '/bricks-get-global-classes.php';
require_once __DIR__ . '/bricks-render-summary.php';
require_once __DIR__ . '/bricks-upload-asset.php';
require_once __DIR__ . '/bricks-import-zip.php';
require_once __DIR__ . '/bricks-html-to-bricks.php';
require_once __DIR__ . '/bricks-edit-elements.php';

unset( $snn_bricks_dir );

add_action( 'wp_abilities_api_categories_init', 'snn_register_bricks_ability_category' );
function snn_register_bricks_ability_category() {
    if ( ! wp_has_ability_category( 'snn-bricks' ) ) {
        wp_register_ability_category( 'snn-bricks', array(
            'label'       => __( 'Bricks Builder (SNN)', 'snn' ),
            'description' => __( 'Read Bricks pages, build them from HTML/CSS or design-export zips, and edit their elements.', 'snn' ),
        ) );
    }
}

/** post_id input schema shared by every page ability. */
function snn_bricks_post_id_schema() {
    return array(
        'type'        => 'integer',
        'minimum'     => 1,
        'description' => 'ID of the page, post or Bricks template.',
    );
}

/** Permission: the caller may read this post's Bricks data. */
function snn_bricks_read_permission( $input ) {
    $post_id = (int) ( $input['post_id'] ?? 0 );
    if ( ! $post_id ) {
        return current_user_can( 'edit_posts' );
    }
    $post = get_post( $post_id );
    if ( ! $post || ( ! snn_ability_post_type( $post->post_type ) && $post->post_type !== 'bricks_template' ) ) {
        return new WP_Error( 'snn_invalid_post', 'Post not found.' );
    }
    return current_user_can( 'edit_post', $post_id );
}

/** Permission: the caller may edit this post with Bricks. */
function snn_bricks_write_permission( $input ) {
    $read = snn_bricks_read_permission( $input );
    if ( $read !== true ) {
        return $read;
    }
    $post_id = (int) ( $input['post_id'] ?? 0 );
    if ( $post_id && class_exists( '\Bricks\Capabilities' ) && method_exists( '\Bricks\Capabilities', 'current_user_can_use_builder' )
        && ! \Bricks\Capabilities::current_user_can_use_builder( $post_id ) ) {
        return new WP_Error( 'snn_no_builder_access', 'Your role has no Bricks builder access for this post.' );
    }
    return true;
}

/** Saved Bricks elements + digest + area for a post. */
function snn_bricks_page_state( $post_id ) {
    $area     = SNN_Bricks_Write_Guard::area( $post_id );
    $elements = SNN_Bricks_Write_Guard::read_elements( $post_id, $area );
    return array(
        'area'           => $area,
        'elements'       => $elements,
        'documentDigest' => SNN_Bricks_Write_Guard::digest( $elements ),
    );
}

/** Wrap an ability's execute so the shared idempotency / audit rules apply once. */
function snn_bricks_with_idempotency( array $input, callable $run ) {
    $key    = isset( $input['idempotencyKey'] ) ? (string) $input['idempotencyKey'] : '';
    $digest = SNN_Bricks_Write_Guard::request_digest( $input );
    $replay = SNN_Bricks_Write_Guard::idempotent_replay( $key, $digest );
    if ( $replay !== null ) {
        return $replay;
    }
    $result = $run();
    SNN_Bricks_Write_Guard::remember( $key, $digest, $result );
    return $result;
}

/** Common write inputs. */
function snn_bricks_write_input_properties() {
    return array(
        'expected_digest' => array(
            'type'        => 'string',
            'description' => 'documentDigest from snn-bricks-get-page-tree. The write is refused when the page changed since that read. Strongly recommended.',
        ),
        'force'           => array(
            'type'        => 'boolean',
            'default'     => false,
            'description' => 'Write even though the page is open (locked) in the Bricks builder. Only when the user confirmed the builder tab will be closed or reloaded without saving.',
        ),
        'idempotencyKey'  => array(
            'type'        => 'string',
            'maxLength'   => 128,
            'description' => 'Stable key for this exact intended write. Retrying with the same key returns the original result instead of writing twice.',
        ),
    );
}
