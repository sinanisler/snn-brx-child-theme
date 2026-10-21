<?php
/**
 * The feedback a client gets after every Bricks write (and every dry run).
 *
 * In the builder the user sees the page change; an MCP client sees nothing,
 * so each write answers the questions the builder would answer visually:
 *
 *  - changes         which elements were added, removed or changed (and which settings)
 *  - outline         the affected sections as the page tree prints them, with ids
 *                    the next edit can target
 *  - pageSections    the top-level order of the whole page, so misplaced
 *                    sections are obvious
 *  - checks          static problems: heading outline, empty text, images
 *                    without a source, buttons without a link, canvas-export
 *                    leftovers (absolute positioning, fixed canvas widths)
 *  - render          Bricks' own server render of the result (Bricks 2.4+):
 *                    heading outline, landmarks, responsive CSS, CSS errors
 *  - next            what to do next, in plain words
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Feedback {

    const MAX_LIST    = 40;
    const MAX_OUTLINE = 160;

    /**
     * @param int   $post_id
     * @param array $before  Elements before the write.
     * @param array $after   Elements after the write (or the planned tree on a dry run).
     * @param array $opts    focus (root ids to outline), verify (bool, render check),
     *                       saved (bool, false on a dry run), warnings (compiler warnings)
     */
    public static function report( $post_id, array $before, array $after, array $opts = array() ) {
        $classes = SNN_Bricks_Global_Classes_Store::read_classes();
        $ops     = new SNN_Bricks_Edit_Ops( $post_id, $after, $classes );
        $changes = self::diff( $before, $after );
        $saved   = $opts['saved'] ?? true;

        // Sections to show: the requested roots, else every section a change touched.
        $focus = array_values( array_filter( (array) ( $opts['focus'] ?? array() ) ) );
        if ( ! $focus ) {
            $touched = array_merge( $changes['added'], array_column( $changes['changed'], 'id' ) );
            foreach ( $touched as $id ) {
                $root = self::section_of( $after, $id );
                if ( $root !== null && ! in_array( $root, $focus, true ) ) {
                    $focus[] = $root;
                }
            }
        }
        list( $lines, $truncated ) = self::outline( $after, $ops, $focus, self::MAX_OUTLINE );
        if ( $truncated ) {
            $lines[] = '(truncated at ' . self::MAX_OUTLINE . ' elements — snn-bricks-get-page-tree with root_id shows the rest)';
        }

        $out = array(
            'changes'      => self::cap_changes( $changes ),
            'outline'      => $lines ? implode( "\n", $lines ) : '(no affected sections)',
            'pageSections' => self::page_sections( $after, $focus ),
            'checks'       => self::checks( $after, $ops, $classes, self::subtree_set( $after, $focus ) ),
        );

        if ( ! empty( $opts['verify'] ) ) {
            $out['render'] = self::render( $post_id, $saved ? null : $after );
        }
        // Export image paths with no file on the site yet: the user uploads them in one step.
        $missing = SNN_Bricks_Asset_Drop::missing( $after, $classes );
        if ( $missing ) {
            $out['assets'] = array(
                'missing'   => array_slice( $missing, 0, self::MAX_LIST ),
                'count'     => count( $missing ),
                'uploadUrl' => $saved ? SNN_Bricks_Asset_Drop::link( $post_id ) : null,
            );
        }
        $out['next'] = self::next_steps( $out, $opts, $focus );
        if ( function_exists( 'snn_mcp_debug' ) ) {
            snn_mcp_debug( 'stage', array(
                'stage'   => 'feedback',
                'added'   => count( $changes['added'] ),
                'removed' => count( $changes['removed'] ),
                'changed' => count( $changes['changed'] ),
                'checks'  => $out['checks'],
            ) );
        }
        return $out;
    }

    /** Added / removed / changed element ids, with the changed setting keys. */
    public static function diff( array $before, array $after ) {
        $old = array();
        foreach ( $before as $el ) {
            $old[ (string) ( $el['id'] ?? '' ) ] = $el;
        }
        $new = array();
        foreach ( $after as $el ) {
            $new[ (string) ( $el['id'] ?? '' ) ] = $el;
        }
        $added   = array_values( array_map( 'strval', array_keys( array_diff_key( $new, $old ) ) ) );
        $removed = array_values( array_map( 'strval', array_keys( array_diff_key( $old, $new ) ) ) );
        $changed = array();
        foreach ( array_intersect_key( $new, $old ) as $id => $el ) {
            $was = $old[ $id ];
            if ( wp_json_encode( $was ) === wp_json_encode( $el ) ) {
                continue;
            }
            $fields = array();
            foreach ( array( 'name', 'parent', 'children', 'label' ) as $k ) {
                if ( wp_json_encode( $was[ $k ] ?? null ) !== wp_json_encode( $el[ $k ] ?? null ) ) {
                    $fields[] = $k;
                }
            }
            $s_old = (array) ( $was['settings'] ?? array() );
            $s_new = (array) ( $el['settings'] ?? array() );
            foreach ( array_unique( array_merge( array_keys( $s_old ), array_keys( $s_new ) ) ) as $k ) {
                if ( wp_json_encode( $s_old[ $k ] ?? null ) !== wp_json_encode( $s_new[ $k ] ?? null ) ) {
                    $fields[] = 'settings.' . $k;
                }
            }
            $changed[] = array( 'id' => (string) $id, 'name' => $el['name'] ?? '', 'fields' => $fields );
        }
        return array( 'added' => $added, 'removed' => $removed, 'changed' => $changed );
    }

    private static function cap_changes( array $c ) {
        $out = array(
            'addedCount'   => count( $c['added'] ),
            'removedCount' => count( $c['removed'] ),
            'changedCount' => count( $c['changed'] ),
            'removed'      => array_slice( $c['removed'], 0, self::MAX_LIST ),
            // Children-list updates on parents are noise next to real changes.
            'changed'      => array_slice( array_values( array_filter( $c['changed'], function ( $row ) {
                return array_diff( $row['fields'], array( 'children' ) ) !== array();
            } ) ), 0, self::MAX_LIST ),
        );
        if ( count( $c['added'] ) <= self::MAX_LIST ) {
            $out['added'] = $c['added'];
        }
        return $out;
    }

    /** Top-level ancestor of an element (the section it belongs to). */
    public static function section_of( array $elements, $id ) {
        $by = array();
        foreach ( $elements as $el ) {
            $by[ (string) $el['id'] ] = $el;
        }
        $id   = (string) $id;
        $seen = 0;
        while ( isset( $by[ $id ] ) && $seen++ < 200 ) {
            $p = (string) ( $by[ $id ]['parent'] ?? '0' );
            if ( $p === '0' || $p === '' || ! isset( $by[ $p ] ) ) {
                return $id;
            }
            $id = $p;
        }
        return null;
    }

    private static function subtree_set( array $elements, array $roots ) {
        $set = array();
        foreach ( $roots as $r ) {
            $set += SNN_Bricks_Writer::subtree_ids( $elements, $r );
        }
        return $set;
    }

    /**
     * The page-tree outline ([id] type .classes "text") for these roots.
     * @return array [ string[] lines, bool truncated ]
     */
    public static function outline( array $elements, SNN_Bricks_Edit_Ops $ops, array $roots, $max ) {
        $by_id = array();
        foreach ( $elements as $el ) {
            $by_id[ $el['id'] ] = $el;
        }
        $lines     = array();
        $emitted   = 0;
        $truncated = false;
        $walk = function ( $id, $depth ) use ( &$walk, &$lines, &$emitted, &$truncated, $max, $by_id, $ops ) {
            if ( ! isset( $by_id[ $id ] ) ) {
                return;
            }
            if ( $emitted >= $max ) {
                $truncated = true;
                return;
            }
            $emitted++;
            $el      = $by_id[ $id ];
            $classes = implode( ' ', array_map( function ( $c ) {
                return '.' . $c;
            }, $ops->class_names_of( $el ) ) );
            $line = str_repeat( '  ', $depth ) . '[' . $el['id'] . '] ' . ( $el['name'] ?? 'block' );
            if ( $classes !== '' ) {
                $line .= '  ' . $classes;
            }
            $text = SNN_Bricks_Edit_Ops::text_of( $el );
            if ( $text !== '' && ( $el['name'] ?? '' ) !== 'custom-html-css-script' ) {
                $line .= '  "' . $text . '"';
            }
            $icon = $el['settings']['icon'] ?? null;
            if ( is_array( $icon ) ) {
                $line .= '  icon=' . ( $icon['icon'] ?? ( $icon['library'] ?? 'svg' ) );
            }
            $lines[] = $line;
            foreach ( $ops->children_of( $id ) as $cid ) {
                $walk( $cid, $depth + 1 );
            }
        };
        foreach ( $roots as $r ) {
            $walk( $r, 0 );
        }
        return array( $lines, $truncated );
    }

    /** Top-level order of the page; the affected sections are flagged. */
    public static function page_sections( array $elements, array $focus = array() ) {
        $ids = array();
        foreach ( $elements as $el ) {
            $ids[ (string) $el['id'] ] = true;
        }
        $out = array();
        foreach ( $elements as $el ) {
            $p = (string) ( $el['parent'] ?? '0' );
            if ( $p !== '0' && $p !== '' && isset( $ids[ $p ] ) ) {
                continue;
            }
            $label = SNN_Bricks_Edit_Ops::text_of( $el, 40 ) ?: ( $el['label'] ?? $el['name'] );
            if ( $el['name'] === 'custom-html-css-script' && preg_match( '/^\s*<style\b/i', (string) ( $el['settings']['content'] ?? '' ) ) ) {
                $label = 'Page CSS';
            }
            $row = array( 'position' => count( $out ), 'id' => (string) $el['id'], 'name' => $el['name'], 'label' => $label );
            if ( in_array( (string) $el['id'], $focus, true ) ) {
                $row['affected'] = true;
            }
            $out[] = $row;
        }
        return $out;
    }

    /** Problems a browser preview would show the builder user at a glance. */
    public static function checks( array $elements, SNN_Bricks_Edit_Ops $ops, array $classes, array $scope ) {
        $issues   = array();
        $headings = array();
        $h1       = 0;
        $class_by_id = array();
        foreach ( $classes as $gc ) {
            $class_by_id[ $gc['id'] ?? '' ] = $gc;
        }
        $used = array();

        foreach ( $elements as $el ) {
            $name = $el['name'] ?? '';
            $s    = (array) ( $el['settings'] ?? array() );
            if ( $name === 'heading' ) {
                $level = (int) substr( (string) ( $s['tag'] ?? 'h3' ), 1 );
                $level = $level ?: 3;
                $h1   += $level === 1 ? 1 : 0;
                $headings[] = array( 'id' => $el['id'], 'level' => $level, 'text' => SNN_Bricks_Edit_Ops::text_of( $el, 50 ) );
            }
            if ( ! isset( $scope[ $el['id'] ] ) ) {
                continue;
            }
            foreach ( (array) ( $s['_cssGlobalClasses'] ?? array() ) as $cid ) {
                $used[ $cid ] = true;
            }
            if ( in_array( $name, array( 'heading', 'text-basic', 'text', 'button', 'text-link' ), true ) && SNN_Bricks_Edit_Ops::text_of( $el ) === '' && empty( $s['icon'] ) ) {
                $issues[] = '[' . $el['id'] . '] ' . $name . ' has no text';
            }
            if ( $name === 'image' && empty( $s['image']['url'] ) && empty( $s['image']['id'] ) && empty( $s['image']['useDynamicData'] ) ) {
                $issues[] = '[' . $el['id'] . '] image has no source';
            }
            if ( $name === 'button' && empty( $s['link'] ) ) {
                $issues[] = '[' . $el['id'] . '] button has no link';
            }
        }

        // Heading outline: one h1, no skipped levels.
        $prev = 0;
        foreach ( $headings as $h ) {
            if ( $prev && $h['level'] > $prev + 1 ) {
                $issues[] = '[' . $h['id'] . '] heading jumps from h' . $prev . ' to h' . $h['level'] . ' ("' . $h['text'] . '")';
            }
            $prev = $h['level'];
        }
        if ( $headings && $h1 === 0 ) {
            $issues[] = 'the page has no h1';
        } elseif ( $h1 > 1 ) {
            $issues[] = 'the page has ' . $h1 . ' h1 headings';
        }

        // Canvas-export leftovers in the classes the affected sections use.
        $canvas = array();
        foreach ( array_keys( $used ) as $cid ) {
            $css = (string) ( $class_by_id[ $cid ]['settings']['_cssCustom'] ?? '' );
            if ( $css === '' ) {
                continue;
            }
            $why = array();
            if ( preg_match( '/position\s*:\s*absolute/i', $css ) ) {
                $why[] = 'position:absolute';
            }
            if ( preg_match_all( '/(?<![-\w])(min-)?(width|height)\s*:\s*(\d{4,})px/i', $css, $m, PREG_SET_ORDER ) ) {
                foreach ( $m as $hit ) {
                    $why[] = $hit[2] . ':' . $hit[3] . 'px';
                }
            }
            if ( $why ) {
                $canvas[] = '.' . ( $class_by_id[ $cid ]['name'] ?? $cid ) . ' (' . implode( ', ', array_unique( $why ) ) . ')';
            }
        }
        if ( $canvas ) {
            $issues[] = 'possible canvas leftovers, check desktop and mobile: ' . implode( '; ', array_slice( $canvas, 0, 12 ) );
        }

        return array(
            'ok'             => ! $issues,
            'issues'         => array_slice( $issues, 0, self::MAX_LIST ),
            'headingOutline' => array_map( function ( $h ) {
                return 'h' . $h['level'] . ' [' . $h['id'] . '] ' . $h['text'];
            }, array_slice( $headings, 0, 40 ) ),
        );
    }

    /** Bricks' server render of the saved page, or of a planned tree on a dry run. */
    public static function render( $post_id, $elements = null ) {
        if ( ! class_exists( '\Bricks\Abilities\Conversion' ) ) {
            return array( 'available' => false, 'reason' => 'needs Bricks 2.4+' );
        }
        try {
            $input = array( 'postId' => (int) $post_id, 'responseFormat' => 'summary' );
            if ( $elements !== null ) {
                $input['elements'] = array_values( $elements );
            }
            $r = \Bricks\Abilities\Conversion::render_elements( $input );
            if ( is_wp_error( $r ) ) {
                return array( 'available' => false, 'reason' => $r->get_error_message() );
            }
            $s = $r['summary'] ?? array();
            return array(
                'available'        => true,
                'emptyHtml'        => $s['emptyHtml'] ?? null,
                'landmarks'        => $s['landmarks'] ?? null,
                'hasResponsiveCss' => $s['hasResponsiveCss'] ?? null,
                'cssSyntaxErrors'  => $s['cssSyntaxErrors'] ?? array(),
                'cssSpecificity'   => array_slice( (array) ( $s['cssSpecificityRisks'] ?? array() ), 0, 10 ),
                'htmlKb'           => isset( $s['htmlBytes'] ) ? round( $s['htmlBytes'] / 1024, 1 ) : null,
            );
        } catch ( \Throwable $e ) {
            return array( 'available' => false, 'reason' => $e->getMessage() );
        }
    }

    private static function next_steps( array $out, array $opts, array $focus ) {
        $next = array();
        if ( ! empty( $out['assets']['uploadUrl'] ) ) {
            $next[] = $out['assets']['count'] . ' image path(s) from the export have no file on the site yet. Keep building; when the page is done, give the user this link to drop the design .zip (or the images) — every image is placed automatically: ' . $out['assets']['uploadUrl'];
        }
        if ( empty( $opts['saved'] ) && array_key_exists( 'saved', $opts ) ) {
            $next[] = 'Dry run: nothing was saved. Send the same call without dry_run to write it.';
        }
        if ( ! empty( $opts['warnings'] ) ) {
            $next[] = 'Read the compiler warnings above; fix the HTML and rebuild the section if one matters.';
        }
        if ( ! $out['checks']['ok'] ) {
            $next[] = 'Fix the issues in checks: content with snn-bricks-edit-elements (ids are in the outline), styling with update_class.';
        }
        if ( isset( $out['render'] ) && ! empty( $out['render']['available'] ) ) {
            if ( ! empty( $out['render']['cssSyntaxErrors'] ) ) {
                $next[] = 'The rendered CSS has syntax errors: check the class CSS you sent.';
            }
            if ( $out['render']['hasResponsiveCss'] === false ) {
                $next[] = 'The page has no responsive CSS yet: add @media rules with this site\'s breakpoints.';
            }
        }
        if ( $focus ) {
            $next[] = 'Small change: snn-bricks-edit-elements with an id from the outline. Redo a whole section: snn-bricks-html-to-bricks mode "replace-section" with its section id.';
        }
        $next[] = 'Pass the returned documentDigest as expected_digest on the next write.';
        return $next;
    }
}
