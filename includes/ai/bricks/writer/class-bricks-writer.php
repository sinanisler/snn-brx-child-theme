<?php
/**
 * Persists compiler output to a Bricks page.
 *
 * Element trees are saved through Bricks' own Save_Pipeline when it exists
 * (Bricks 2.4+): normalization, KSES/security checks, validation, code
 * signing, a revision snapshot, a digest compare-and-swap against concurrent
 * edits, and CSS regeneration — the exact path Bricks' MCP abilities use.
 * Older Bricks falls back to a revision + direct post meta write.
 *
 * Modes for a build: append, prepend, replace, insert-after / insert-before (a
 * root element), replace-section (rebuild one top-level section in place).
 * remove_root_ids drops earlier top-level elements in the same save, so a
 * rebuilt import pass replaces its old sections in one revision.
 *
 * Every build (and dry run) returns `feedback` (SNN_Bricks_Feedback).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Writer {

    const MODES = array( 'append', 'prepend', 'replace', 'insert-after', 'insert-before', 'replace-section' );

    private static function debug( $stage, array $data = array(), $level = 'info' ) {
        if ( function_exists( 'snn_mcp_debug' ) ) {
            snn_mcp_debug( 'stage', array_merge( array( 'stage' => $stage ), $data ), $level );
        }
    }

    /** Is Bricks active and usable for this post? */
    public static function check_target( $post_id ) {
        if ( ! class_exists( '\Bricks\Database' ) ) {
            return new WP_Error( 'snn_bricks_inactive', 'The Bricks theme is not active, so there is no Bricks page to write to.', array( 'status' => 400 ) );
        }
        $post = get_post( $post_id );
        if ( ! $post || ! snn_ability_post_type( $post->post_type ) && $post->post_type !== 'bricks_template' ) {
            return new WP_Error( 'snn_invalid_post', 'Post not found.', array( 'status' => 404 ) );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return new WP_Error( 'snn_forbidden', 'You cannot edit this post.', array( 'status' => 403 ) );
        }
        if ( class_exists( '\Bricks\Capabilities' ) && method_exists( '\Bricks\Capabilities', 'current_user_can_use_builder' )
            && ! \Bricks\Capabilities::current_user_can_use_builder( $post_id ) ) {
            return new WP_Error( 'snn_no_builder_access', 'Your role has no Bricks builder access for this post.', array( 'status' => 403 ) );
        }
        return true;
    }

    /**
     * Save a complete element tree.
     * @return array|WP_Error { revisionId, documentDigest, changed, elements }
     */
    public static function save( $post_id, $area, array $elements, $expected_digest = '' ) {
        $elements = array_values( $elements );

        if ( class_exists( '\Bricks\Abilities\Save_Pipeline' ) ) {
            if ( class_exists( '\Bricks\Abilities\Manager' ) && method_exists( '\Bricks\Abilities\Manager', 'flush_post_cache' ) ) {
                \Bricks\Abilities\Manager::flush_post_cache( $post_id );
            }
            $result = \Bricks\Abilities\Save_Pipeline::execute( $post_id, $elements, $area, array(), (string) $expected_digest );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            return array(
                'revisionId'     => $result['revisionId'] ?? null,
                'documentDigest' => $result['documentDigest'] ?? SNN_Bricks_Write_Guard::digest( $result['elements'] ),
                'changed'        => ! empty( $result['changed'] ),
                'elements'       => $result['elements'],
            );
        }

        // Fallback for Bricks < 2.4.
        $meta_key = SNN_Bricks_Write_Guard::meta_key( $area );
        $current  = SNN_Bricks_Write_Guard::read_elements( $post_id, $area );
        if ( $expected_digest !== '' && ! hash_equals( (string) $expected_digest, SNN_Bricks_Write_Guard::digest( $current ) ) ) {
            return new WP_Error( 'snn_document_digest_mismatch', 'The page changed during this write.', array( 'status' => 409 ) );
        }
        $revision_id = null;
        if ( $current ) {
            add_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
            $revision_id = wp_save_post_revision( $post_id );
            remove_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
            if ( $revision_id && ! is_wp_error( $revision_id ) ) {
                update_metadata( 'post', $revision_id, $meta_key, wp_slash( $current ) );
            }
        }
        update_post_meta( $post_id, $meta_key, wp_slash( $elements ) );
        if ( defined( 'BRICKS_DB_EDITOR_MODE' ) ) {
            update_post_meta( $post_id, BRICKS_DB_EDITOR_MODE, 'bricks' );
        }
        wp_update_post( array( 'ID' => $post_id, 'post_modified' => current_time( 'mysql' ) ) );
        return array(
            'revisionId'     => is_wp_error( $revision_id ) ? null : $revision_id,
            'documentDigest' => SNN_Bricks_Write_Guard::digest( $elements ),
            'changed'        => true,
            'elements'       => $elements,
        );
    }

    /** All ids in a subtree (root included), following parent links. */
    public static function subtree_ids( array $elements, $root_id ) {
        $ids     = array( $root_id => true );
        $changed = true;
        while ( $changed ) {
            $changed = false;
            foreach ( $elements as $el ) {
                $p = (string) ( $el['parent'] ?? '0' );
                if ( isset( $ids[ $p ] ) && ! isset( $ids[ $el['id'] ] ) ) {
                    $ids[ $el['id'] ] = true;
                    $changed          = true;
                }
            }
        }
        return $ids;
    }

    /** Ids already used on the page, so new ids never collide. */
    public static function page_ids( array $elements ) {
        $out = array();
        foreach ( $elements as $el ) {
            if ( ! empty( $el['id'] ) ) {
                $out[] = $el['id'];
            }
        }
        return $out;
    }

    /**
     * Compile + persist a full HTML build.
     *
     * @param int   $post_id
     * @param array $args html, css, mode, anchor_id, section_id, expected_digest, force, dry_run,
     *                    upload_svgs, prompt, source, remove_root_ids, verify
     * @return array|WP_Error
     */
    public static function build_and_write( $post_id, array $args ) {
        $target = self::check_target( $post_id );
        if ( is_wp_error( $target ) ) {
            return $target;
        }
        $mode = $args['mode'] ?? 'append';
        if ( ! in_array( $mode, self::MODES, true ) ) {
            return new WP_Error( 'snn_bad_mode', 'mode must be one of: ' . implode( ', ', self::MODES ), array( 'status' => 400 ) );
        }

        $area     = SNN_Bricks_Write_Guard::area( $post_id );
        $dry_run  = ! empty( $args['dry_run'] );
        if ( ! $dry_run ) {
            $guard = SNN_Bricks_Write_Guard::check( $post_id, $area, (string) ( $args['expected_digest'] ?? '' ), ! empty( $args['force'] ) );
            if ( is_wp_error( $guard ) ) {
                return $guard;
            }
        }

        $existing         = SNN_Bricks_Write_Guard::read_elements( $post_id, $area );
        $original         = $existing;
        $digest_before    = SNN_Bricks_Write_Guard::digest( $existing );
        $existing_classes = SNN_Bricks_Global_Classes_Store::read_classes();
        self::debug( 'build.start', array( 'postId' => (int) $post_id, 'mode' => $mode, 'dryRun' => $dry_run, 'existingElements' => count( $existing ), 'htmlChars' => strlen( (string) ( $args['html'] ?? '' ) ), 'cssChars' => strlen( (string) ( $args['css'] ?? '' ) ) ) );

        // Earlier top-level elements to drop in this same save; the first one's
        // slot is where an anchor among them points.
        $removed_ids = array();
        $slot        = null;
        $remove      = array_values( array_filter( array_map( 'strval', (array) ( $args['remove_root_ids'] ?? array() ) ) ) );
        if ( $remove && $mode !== 'replace-section' ) {
            $doomed = array();
            foreach ( $remove as $r ) {
                $doomed += self::subtree_ids( $existing, $r );
            }
            $kept = array();
            foreach ( $existing as $el ) {
                if ( isset( $doomed[ $el['id'] ?? '' ] ) ) {
                    if ( $slot === null && in_array( (string) $el['id'], $remove, true ) ) {
                        $slot = count( $kept );
                    }
                    $removed_ids[] = (string) $el['id'];
                    continue;
                }
                $kept[] = $el;
            }
            $existing = $kept;
        }

        // The HTML carries its CSS in <style>; a separate css argument is prepended.
        $html = (string) ( $args['html'] ?? '' );
        if ( ! empty( $args['css'] ) ) {
            $html = '<style>' . $args['css'] . '</style>' . $html;
        }
        // Export image paths the user already uploaded (SNN_Bricks_Asset_Drop) become real URLs.
        $html = SNN_Bricks_Asset_Drop::rewrite_html( $post_id, $html );

        // SVGs → media library first (Bricks renders SVG natively only from an attachment).
        $svg = array( 'assets' => array(), 'uploaded' => 0, 'reused' => 0, 'skipped' => array(), 'notAllowed' => 0 );
        if ( ! $dry_run && ( $args['upload_svgs'] ?? true ) ) {
            $svg = SNN_Bricks_Assets::prepare_svgs( $html );
        }

        if ( $mode === 'replace-section' ) {
            return self::replace_section( $post_id, $area, $existing, $existing_classes, $html, $svg, $args, $digest_before );
        }

        $t0    = microtime( true );
        $build = SNN_Bricks_Html_To_Bricks::build( $html, array(
            'existing_classes' => $existing_classes,
            'taken_ids'        => self::page_ids( $original ),
            'svg_assets'       => $svg['assets'],
        ) );
        self::debug( 'build.compiled', array(
            'ms'       => (int) round( ( microtime( true ) - $t0 ) * 1000 ),
            'sections' => count( $build['sections'] ),
            'classes'  => count( $build['classesToCreate'] ),
            'warnings' => $build['warnings'],
            'svgs'     => array_diff_key( $svg, array( 'assets' => 1 ) ),
        ), $build['sections'] ? 'info' : 'warn' );
        if ( ! $build['sections'] ) {
            return new WP_Error( 'snn_nothing_compiled', 'The HTML produced no Bricks elements. Check that it has top-level <section> elements with data-bricks hints (snn-bricks-start-here).', array( 'status' => 422, 'warnings' => $build['warnings'] ) );
        }

        $new_nodes = array();
        if ( $build['pageCssElement'] ) {
            $new_nodes[] = $build['pageCssElement'];
        }
        foreach ( $build['sections'] as $s ) {
            foreach ( $s['content'] as $el ) {
                $new_nodes[] = $el;
            }
        }

        switch ( $mode ) {
            case 'replace':
                $tree = $new_nodes;
                break;
            case 'prepend':
                $tree = array_merge( $new_nodes, $existing );
                break;
            case 'insert-after':
            case 'insert-before':
                $anchor = (string) ( $args['anchor_id'] ?? '' );
                $tree   = $existing;
                if ( $slot !== null && in_array( $anchor, $remove, true ) ) {
                    array_splice( $tree, $slot, 0, $new_nodes ); // the removed elements' place
                    break;
                }
                $at = null;
                foreach ( $existing as $i => $el ) {
                    if ( ( $el['id'] ?? '' ) === $anchor ) {
                        $at = $i;
                    }
                }
                if ( $at === null || ( $existing[ $at ]['parent'] ?? 0 ) !== 0 && ( $existing[ $at ]['parent'] ?? 0 ) !== '0' ) {
                    return new WP_Error( 'snn_bad_anchor', 'anchor_id must be the id of a top-level element on the page.', array( 'status' => 400 ) );
                }
                array_splice( $tree, $mode === 'insert-after' ? $at + 1 : $at, 0, $new_nodes );
                break;
            default:
                $tree = array_merge( $existing, $new_nodes );
        }

        $root_ids = array_values( array_filter( array_column( $build['sections'], 'rootId' ) ) );
        $summary  = array(
            'postId'          => (int) $post_id,
            'mode'            => $mode,
            'dryRun'          => $dry_run,
            'sections'        => array_map( function ( $s ) {
                return array( 'label' => $s['label'], 'rootId' => $s['rootId'], 'elementCount' => count( $s['content'] ) );
            }, $build['sections'] ),
            'elementCount'    => count( $new_nodes ),
            'classesToCreate' => array_column( $build['classesToCreate'], 'name' ),
            'classesReused'   => $build['classesReused'],
            'renamedClasses'  => $build['renames'],
            'pageCss'         => (bool) $build['pageCssElement'],
            'pageCssId'       => $build['pageCssElement'] ? $build['pageCssElement']['id'] : null,
            'removedRootIds'  => array_values( array_intersect( $removed_ids, $remove ) ),
            'svgs'            => array_diff_key( $svg, array( 'assets' => 1 ) ),
            'warnings'        => $build['warnings'],
            'documentDigestBefore' => $digest_before,
        );
        if ( $dry_run ) {
            $summary['elements']      = $new_nodes;
            $summary['globalClasses'] = $build['classesToCreate'];
            // The planned classes are not stored yet; the outline shows their names anyway.
            $summary['feedback'] = SNN_Bricks_Feedback::report( $post_id, $original, $tree, array(
                'focus' => $root_ids, 'verify' => false, 'saved' => false, 'warnings' => $build['warnings'],
            ) );
            return $summary;
        }

        // Classes first: elements reference them. Rolled back if the element save fails.
        $classes_before = SNN_Bricks_Global_Classes_Store::read_classes();
        $classes        = SNN_Bricks_Global_Classes_Store::apply( $build['classesToCreate'] );
        if ( is_wp_error( $classes ) ) {
            return $classes;
        }

        $saved = self::save( $post_id, $area, $tree, $digest_before );
        if ( is_wp_error( $saved ) ) {
            if ( $classes['created'] ) {
                update_option( SNN_Bricks_Global_Classes_Store::classes_option(), $classes_before );
            }
            self::debug( 'build.save_failed', array( 'error' => $saved ), 'error' );
            return $saved;
        }
        self::debug( 'build.saved', array( 'revisionId' => $saved['revisionId'], 'elements' => count( $saved['elements'] ), 'classesCreated' => count( $classes['created'] ) ) );

        $colors = SNN_Bricks_Global_Classes_Store::add_palette_colors( $build['colorVars'], $build['ids'] );
        $vars   = SNN_Bricks_Global_Classes_Store::add_variables( $build['sizeVars'], $build['ids'] );

        $rows = array();
        foreach ( $build['sections'] as $s ) {
            if ( $s['rootId'] ) {
                $rows[] = array(
                    'rootId' => $s['rootId'], 'label' => $s['label'], 'html' => $s['html'],
                    'prompt' => (string) ( $args['prompt'] ?? '' ), 'classes' => $s['classes'],
                    'source' => $args['source'] ?? 'mcp',
                );
            }
        }
        SNN_Bricks_Section_Manifest::record( $post_id, $rows, $saved['elements'], $mode === 'replace' ? self::page_ids( $existing ) : $removed_ids );

        $summary['classesCreated']   = $classes['created'];
        $summary['colorsAdded']      = is_wp_error( $colors ) ? 0 : $colors;
        $summary['variablesAdded']   = is_wp_error( $vars ) ? 0 : $vars;
        $summary['revisionId']       = $saved['revisionId'];
        $summary['documentDigest']   = $saved['documentDigest'];
        $summary['changed']          = $saved['changed'];
        $summary['permalink']        = get_permalink( $post_id );
        $summary['builderUrl']       = add_query_arg( 'bricks', 'run', get_permalink( $post_id ) );
        unset( $summary['classesToCreate'] );
        $summary['feedback'] = SNN_Bricks_Feedback::report( $post_id, $original, $saved['elements'], array(
            'focus'    => $root_ids,
            'verify'   => $args['verify'] ?? true,
            'warnings' => $build['warnings'],
        ) );
        return $summary;
    }

    /** Recompute next steps once the caller knows the import progress after this build. */
    public static function attach_import_progress( array $summary, array $progress ) {
        if ( isset( $summary['feedback'] ) ) {
            $summary['feedback']['next'] = array_values( array_filter( $summary['feedback']['next'], function ( $line ) {
                return strpos( $line, 'Import' ) !== 0;
            } ) );
            if ( $progress['nextChunk'] !== null ) {
                array_unshift( $summary['feedback']['next'], 'Import: ' . $progress['built'] . ' of ' . $progress['total'] . ' passes built. Next: snn-bricks-import-chunk with import_id "' . $progress['importId'] . '" and chunk "' . $progress['nextChunk'] . '".' );
            } else {
                array_unshift( $summary['feedback']['next'], 'Import complete (' . $progress['total'] . ' passes). Compare pageSections with the design order, then open the permalink at desktop and 390px width, or call snn-bricks-render-summary.' );
            }
        }
        $summary['import'] = $progress;
        return $summary;
    }

    /** Rebuild one top-level section in place from new HTML. */
    private static function replace_section( $post_id, $area, array $existing, array $existing_classes, $html, array $svg, array $args, $digest_before ) {
        $section_id = (string) ( $args['section_id'] ?? '' );
        $at         = null;
        foreach ( $existing as $i => $el ) {
            if ( ( $el['id'] ?? '' ) === $section_id ) {
                $at = $i;
            }
        }
        if ( $at === null ) {
            return new WP_Error( 'snn_section_not_found', 'No element [' . $section_id . '] on this page. Use snn-bricks-get-page-tree to find section ids.', array( 'status' => 404 ) );
        }
        $doomed = self::subtree_ids( $existing, $section_id );
        $build  = SNN_Bricks_Html_To_Bricks::compile_section_replacement( $html, $existing, $doomed, $existing_classes, $svg['assets'] );
        if ( ! $build ) {
            return new WP_Error( 'snn_nothing_compiled', 'The HTML produced no Bricks elements.', array( 'status' => 422 ) );
        }
        // The replacement takes the old section's place among its siblings.
        foreach ( $build['content'] as &$el ) {
            if ( $el['parent'] === 0 ) {
                $el['parent'] = $existing[ $at ]['parent'] ?? 0;
            }
        }
        unset( $el );

        $tree = array();
        foreach ( $existing as $i => $el ) {
            if ( $i === $at ) {
                foreach ( $build['content'] as $new ) {
                    $tree[] = $new;
                }
            }
            if ( ! isset( $doomed[ $el['id'] ?? '' ] ) ) {
                $tree[] = $el;
            }
        }
        $parent_id = $existing[ $at ]['parent'] ?? 0;
        if ( $parent_id !== 0 && $parent_id !== '0' ) {
            foreach ( $tree as &$el ) {
                if ( ( $el['id'] ?? '' ) === $parent_id && isset( $el['children'] ) && is_array( $el['children'] ) ) {
                    $pos = array_search( $section_id, $el['children'], true );
                    if ( $pos !== false ) {
                        $el['children'][ $pos ] = $build['rootId'];
                    }
                }
            }
            unset( $el );
        }

        $summary = array(
            'postId'          => (int) $post_id,
            'mode'            => 'replace-section',
            'dryRun'          => ! empty( $args['dry_run'] ),
            'replacedRootId'  => $section_id,
            'newRootId'       => $build['rootId'],
            'elementCount'    => count( $build['content'] ),
            'classesToCreate' => array_column( $build['classesToCreate'], 'name' ),
            'classesUpdated'  => array_column( $build['classesToUpdate'], 'id' ),
            'svgs'            => array_diff_key( $svg, array( 'assets' => 1 ) ),
            'warnings'        => $build['warnings'],
        );
        if ( ! empty( $args['dry_run'] ) ) {
            $summary['elements'] = $build['content'];
            $summary['feedback'] = SNN_Bricks_Feedback::report( $post_id, $existing, $tree, array(
                'focus' => array( $build['rootId'] ), 'verify' => false, 'saved' => false, 'warnings' => $build['warnings'],
            ) );
            return $summary;
        }

        $classes_before = SNN_Bricks_Global_Classes_Store::read_classes();
        $classes        = SNN_Bricks_Global_Classes_Store::apply( $build['classesToCreate'], $build['classesToUpdate'] );
        if ( is_wp_error( $classes ) ) {
            return $classes;
        }
        $saved = self::save( $post_id, $area, $tree, $digest_before );
        if ( is_wp_error( $saved ) ) {
            update_option( SNN_Bricks_Global_Classes_Store::classes_option(), $classes_before );
            return $saved;
        }

        $old = SNN_Bricks_Section_Manifest::find( $post_id, $section_id );
        SNN_Bricks_Section_Manifest::record( $post_id, array( array(
            'rootId'  => $build['rootId'],
            'label'   => $old['label'] ?? 'Section',
            'html'    => $html,
            'prompt'  => (string) ( $args['prompt'] ?? '' ),
            'theme'   => $old['theme'] ?? null,
            'classes' => $build['classNames'],
            'source'  => $args['source'] ?? 'mcp',
        ) ), $saved['elements'], array( $section_id ) );

        $summary['classesCreated'] = $classes['created'];
        $summary['revisionId']     = $saved['revisionId'];
        $summary['documentDigest'] = $saved['documentDigest'];
        $summary['changed']        = $saved['changed'];
        $summary['permalink']      = get_permalink( $post_id );
        unset( $summary['classesToCreate'] );
        $summary['feedback'] = SNN_Bricks_Feedback::report( $post_id, $existing, $saved['elements'], array(
            'focus'    => array( $build['rootId'] ),
            'verify'   => $args['verify'] ?? true,
            'warnings' => $build['warnings'],
        ) );
        return $summary;
    }
}
