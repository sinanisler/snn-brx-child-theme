<?php
/**
 * Element edit operations — PHP port of EditOps (+ NativeLayoutSync) from
 * ai-agent-and-chat-bricks.php, run against the saved tree instead of the
 * builder's live state.
 *
 * Ops: set_text, set_image, set_icon, set_settings, update_class, delete,
 * move, duplicate, insert, wrap, unwrap. Targets: "id", {id}, {text}, {class},
 * {type}, {within}, {index}. Ambiguity is reported with candidates, never
 * resolved by guessing. All ops apply in memory first; the page is saved once,
 * so a failing op leaves the page untouched.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Edit_Ops {

    const OPS        = array( 'set_text', 'set_image', 'set_icon', 'set_settings', 'update_class', 'delete', 'move', 'duplicate', 'insert', 'wrap', 'unwrap' );
    const STRUCTURAL = array( 'delete', 'move', 'insert', 'wrap', 'unwrap', 'duplicate' );

    const LAYOUT_PROP = '/^(display|flex|flex-direction|flex-wrap|flex-flow|align-items|align-content|justify-items|justify-content|gap|row-gap|column-gap|grid-gap|grid-template|grid-template-columns|grid-template-rows|grid-auto-columns|grid-auto-rows|grid-auto-flow|width|max-width)$/i';

    /** @var array flat elements */
    private $content;
    /** @var array name → class row */
    private $classes;
    /** @var array class edits to persist: name → css */
    private $class_writes = array();
    /** @var SNN_Bricks_Id_Pool */
    private $ids;
    private $post_id;
    private $content_changed = false;

    public function __construct( $post_id, array $content, array $classes ) {
        $this->post_id = $post_id;
        $this->content = array_values( $content );
        $this->classes = array();
        $taken = SNN_Bricks_Writer::page_ids( $content );
        foreach ( $classes as $gc ) {
            if ( ! empty( $gc['name'] ) ) {
                $this->classes[ $gc['name'] ] = $gc;
                $taken[] = $gc['id'];
            }
        }
        $this->ids = new SNN_Bricks_Id_Pool( $taken );
    }

    // ── index helpers (PageContext) ────────────────────────────────────────

    private function index() {
        $by_id = array();
        foreach ( $this->content as $i => $el ) {
            if ( ! empty( $el['id'] ) ) {
                $by_id[ $el['id'] ] = $i;
            }
        }
        return $by_id;
    }

    private function &el( $id ) {
        $idx = $this->index();
        $i   = $idx[ $id ];
        return $this->content[ $i ];
    }

    private function exists( $id ) {
        return isset( $this->index()[ $id ] );
    }

    private static function is_root( $parent ) {
        return $parent === 0 || $parent === '0' || $parent === null || $parent === '';
    }

    public function children_of( $id ) {
        $el = $this->content[ $this->index()[ $id ] ];
        $by = $this->index();
        if ( ! empty( $el['children'] ) && is_array( $el['children'] ) ) {
            $known = array_values( array_filter( $el['children'], function ( $c ) use ( $by ) {
                return isset( $by[ $c ] );
            } ) );
            if ( $known ) {
                return $known;
            }
        }
        $out = array();
        foreach ( $this->content as $e ) {
            if ( (string) ( $e['parent'] ?? '' ) === (string) $id ) {
                $out[] = $e['id'];
            }
        }
        return $out;
    }

    public function descendants_of( $id ) {
        $out  = array();
        $walk = function ( $cur ) use ( &$walk, &$out ) {
            foreach ( $this->children_of( $cur ) as $cid ) {
                $out[] = $cid;
                $walk( $cid );
            }
        };
        $walk( $id );
        return $out;
    }

    public function class_names_of( array $el ) {
        $names = array();
        $by_id = array();
        foreach ( $this->classes as $gc ) {
            $by_id[ $gc['id'] ] = $gc['name'];
        }
        foreach ( (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ) as $cid ) {
            if ( isset( $by_id[ $cid ] ) ) {
                $names[] = $by_id[ $cid ];
            }
        }
        return $names;
    }

    public static function text_of( array $el, $max = 70 ) {
        $s   = $el['settings'] ?? array();
        $raw = $s['text'] ?? ( $s['content'] ?? ( $s['title'] ?? '' ) );
        if ( ! is_string( $raw ) ) {
            return '';
        }
        $txt = trim( preg_replace( '/\s+/', ' ', preg_replace( '/<[^>]*>/', ' ', $raw ) ) );
        return mb_strlen( $txt ) > $max ? mb_substr( $txt, 0, $max ) . '…' : $txt;
    }

    /** Resolve a target spec to one id: ['ok' => true, 'id'] or ['ok' => false, 'error', 'candidates']. */
    public function resolve( $spec ) {
        if ( ! $spec ) {
            return array( 'ok' => false, 'error' => 'No target given' );
        }
        $direct = is_string( $spec ) ? $spec : ( $spec['id'] ?? null );
        if ( $direct ) {
            return $this->exists( $direct )
                ? array( 'ok' => true, 'id' => $direct )
                : array( 'ok' => false, 'error' => 'No element with id [' . $direct . '] exists on this page' );
        }
        $matches = $this->content;
        if ( ! empty( $spec['text'] ) ) {
            $needle  = mb_strtolower( (string) $spec['text'] );
            $matches = array_filter( $matches, function ( $el ) use ( $needle ) {
                return mb_strpos( mb_strtolower( self::text_of( $el, 400 ) ), $needle ) !== false;
            } );
        }
        if ( ! empty( $spec['class'] ) ) {
            $want    = ltrim( (string) $spec['class'], '.' );
            $matches = array_filter( $matches, function ( $el ) use ( $want ) {
                return in_array( $want, $this->class_names_of( $el ), true );
            } );
        }
        if ( ! empty( $spec['type'] ) ) {
            $type    = (string) $spec['type'];
            $matches = array_filter( $matches, function ( $el ) use ( $type ) {
                return ( $el['name'] ?? '' ) === $type;
            } );
        }
        if ( ! empty( $spec['within'] ) ) {
            $parent = $this->resolve( $spec['within'] );
            if ( $parent['ok'] ) {
                $allowed = array_flip( $this->descendants_of( $parent['id'] ) );
                $matches = array_filter( $matches, function ( $el ) use ( $allowed ) {
                    return isset( $allowed[ $el['id'] ] );
                } );
            }
        }
        $matches = array_values( $matches );
        if ( ! $matches ) {
            return array( 'ok' => false, 'error' => 'No element matched ' . wp_json_encode( $spec ) );
        }
        if ( isset( $spec['index'] ) && is_numeric( $spec['index'] ) ) {
            $i = (int) $spec['index'];
            return isset( $matches[ $i ] )
                ? array( 'ok' => true, 'id' => $matches[ $i ]['id'] )
                : array( 'ok' => false, 'error' => 'Only ' . count( $matches ) . ' element(s) matched; index ' . $i . ' is out of range' );
        }
        if ( count( $matches ) > 1 ) {
            return array(
                'ok'         => false,
                'error'      => count( $matches ) . ' elements matched — specify an id or an index',
                'candidates' => array_map( function ( $el ) {
                    return array( 'id' => $el['id'], 'name' => $el['name'], 'text' => self::text_of( $el, 45 ), 'classes' => $this->class_names_of( $el ) );
                }, array_slice( $matches, 0, 8 ) ),
            );
        }
        return array( 'ok' => true, 'id' => $matches[0]['id'] );
    }

    private static function fail( $r ) {
        return array( 'success' => false, 'error' => $r['error'], 'candidates' => $r['candidates'] ?? null );
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /** Deep merge; null or '' deletes a key (Bricks' control semantics). */
    public static function merge_settings( array $target, array $patch ) {
        foreach ( $patch as $k => $v ) {
            if ( $v === null || $v === '' ) {
                unset( $target[ $k ] );
            } elseif ( is_array( $v ) && ! wp_is_numeric_array( $v ) && isset( $target[ $k ] ) && is_array( $target[ $k ] ) && ! wp_is_numeric_array( $target[ $k ] ) ) {
                $target[ $k ] = self::merge_settings( $target[ $k ], $v );
            } else {
                $target[ $k ] = $v;
            }
        }
        return $target;
    }

    /** Merge declarations into a class's base rule; :hover / @media blocks are preserved. */
    public static function merge_class_css( $existing, $class_name, array $decls ) {
        $sel     = '.' . $class_name;
        $escaped = preg_quote( $class_name, '/' );
        $re      = '/(^|\})\s*\.' . $escaped . '\s*\{([^}]*)\}/';
        $map     = array();
        $found   = preg_match( $re, $existing, $m );
        if ( $found ) {
            foreach ( explode( ';', $m[2] ) as $d ) {
                $i = strpos( $d, ':' );
                if ( $i > 0 ) {
                    $map[ trim( substr( $d, 0, $i ) ) ] = trim( substr( $d, $i + 1 ) );
                }
            }
        }
        foreach ( $decls as $prop => $val ) {
            if ( $val === null || $val === '' ) {
                unset( $map[ $prop ] );
            } else {
                $map[ $prop ] = (string) $val;
            }
        }
        $body = implode( "\n", array_map( function ( $p ) use ( $map ) {
            return '  ' . $p . ': ' . $map[ $p ] . ';';
        }, array_keys( $map ) ) );
        $rule = $sel . " {\n" . $body . "\n}";
        if ( $found ) {
            return preg_replace_callback( $re, function ( $mm ) use ( $rule ) {
                return $mm[1] . "\n" . $rule;
            }, $existing, 1 );
        }
        return ( $existing !== '' ? rtrim( $existing ) . "\n\n" : '' ) . $rule;
    }

    /** '<i class="fas fa-arrow-right"></i> Go' → { text: 'Go', icon, position: 'left' }. */
    public static function split_icon_from_label( $html ) {
        $parsed = SNN_Bricks_Dom::parse( '<div>' . $html . '</div>' );
        $root   = SNN_Bricks_Dom::children( $parsed['body'] )[0] ?? null;
        if ( ! $root ) {
            return array( 'text' => $html, 'icon' => null, 'position' => null );
        }
        $node = null;
        foreach ( SNN_Bricks_Dom::find_tags( $root, array( 'i', 'span' ) ) as $n ) {
            if ( trim( SNN_Bricks_Dom::text( $n ) ) === '' && ! SNN_Bricks_Dom::children( $n ) && SNN_Bricks_Features::parse_fa_icon( (string) SNN_Bricks_Dom::attr( $n, 'class' ) ) ) {
                $node = $n;
                break;
            }
        }
        if ( ! $node ) {
            return array( 'text' => $html, 'icon' => null, 'position' => null );
        }
        $before = SNN_Bricks_Dom::text_around( $root, $node )['before'];
        $icon   = SNN_Bricks_Features::parse_fa_icon( (string) SNN_Bricks_Dom::attr( $node, 'class' ) );
        SNN_Bricks_Dom::remove( $node );
        return array(
            'text'     => trim( preg_replace( '/\s+/', ' ', SNN_Bricks_Dom::inner_html( $root ) ) ),
            'icon'     => $icon,
            'position' => trim( $before ) !== '' ? 'right' : 'left',
        );
    }

    /** Class names → ids, creating empty classes on the fly. */
    private function class_ids( $names ) {
        if ( ! is_array( $names ) || ! $names ) {
            return array();
        }
        $out = array();
        foreach ( $names as $raw ) {
            $name = ltrim( (string) $raw, '.' );
            if ( $name === '' ) {
                continue;
            }
            if ( ! isset( $this->classes[ $name ] ) ) {
                $this->classes[ $name ] = array(
                    'id' => $this->ids->next(), 'name' => $name, 'new' => true,
                    'settings' => array( '_cssCustom' => '.' . $name . " {\n}" ),
                );
            }
            $out[] = $this->classes[ $name ]['id'];
        }
        return $out;
    }

    // ── operations ─────────────────────────────────────────────────────────

    private function op_set_text( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $el   = &$this->el( $r['id'] );
        $text = (string) ( $op['text'] ?? '' );
        if ( in_array( $el['name'], array( 'button', 'text-link' ), true ) ) {
            $split = self::split_icon_from_label( $text );
            $text  = $split['text'];
            if ( $split['icon'] ) {
                $el['settings']['icon']         = $split['icon'];
                $el['settings']['iconPosition'] = $split['position'];
            }
        }
        if ( isset( $el['settings']['content'] ) && ! isset( $el['settings']['text'] ) ) {
            $el['settings']['content'] = $text;
        } else {
            $el['settings']['text'] = $text;
        }
        return array( 'success' => true, 'message' => 'Text updated on [' . $r['id'] . ']' );
    }

    private function op_set_icon( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $el = &$this->el( $r['id'] );
        if ( ! in_array( $el['name'], array( 'button', 'text-link', 'icon' ), true ) ) {
            return array( 'success' => false, 'error' => 'set_icon works on button, text-link and icon elements; [' . $r['id'] . '] is a ' . $el['name'] );
        }
        if ( array_key_exists( 'icon', $op ) && ( $op['icon'] === null || $op['icon'] === '' ) || ! empty( $op['remove'] ) ) {
            if ( $el['name'] === 'icon' ) {
                return array( 'success' => false, 'error' => 'An icon element needs an icon — delete the element instead' );
            }
            unset( $el['settings']['icon'], $el['settings']['iconPosition'] );
            return array( 'success' => true, 'message' => 'Icon removed from [' . $r['id'] . ']' );
        }
        if ( isset( $op['icon'] ) ) {
            $icon = SNN_Bricks_Features::parse_fa_icon( (string) $op['icon'] );
            if ( ! $icon ) {
                return array( 'success' => false, 'error' => 'icon must be FontAwesome classes such as "fas fa-arrow-right" or "fab fa-github"' );
            }
            $el['settings']['icon'] = $icon;
        }
        if ( $el['name'] !== 'icon' && ! empty( $op['position'] ) ) {
            $el['settings']['iconPosition'] = $op['position'] === 'left' ? 'left' : 'right';
        }
        if ( ! empty( $op['size'] ) ) {
            if ( $el['name'] === 'button' ) {
                $el['settings']['iconTypography'] = array_merge( (array) ( $el['settings']['iconTypography'] ?? array() ), array( 'font-size' => (string) $op['size'] ) );
            } else {
                $el['settings']['iconSize'] = (string) $op['size'];
            }
        }
        if ( ! empty( $op['color'] ) ) {
            $color = SNN_Bricks_Features::to_bricks_color( $op['color'] );
            if ( $el['name'] === 'button' ) {
                $el['settings']['iconTypography'] = array_merge( (array) ( $el['settings']['iconTypography'] ?? array() ), array( 'color' => $color ) );
            } else {
                $el['settings']['iconColor'] = $color;
            }
        }
        if ( isset( $op['gap'] ) && $el['name'] !== 'icon' ) {
            $el['settings'][ $el['name'] === 'button' ? 'iconGap' : 'gap' ] = (string) $op['gap'];
        }
        return array( 'success' => true, 'message' => 'Icon updated on [' . $r['id'] . ']' );
    }

    private function op_set_image( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $url = esc_url_raw( (string) ( $op['url'] ?? '' ) );
        if ( ! $url ) {
            return array( 'success' => false, 'error' => 'set_image needs a url' );
        }
        $el = &$this->el( $r['id'] );
        if ( ! empty( $op['background'] ) ) {
            $el['settings']['_background']['image'] = array_merge( (array) ( $el['settings']['_background']['image'] ?? array() ), array( 'url' => $url ) );
        } else {
            $el['settings']['image'] = SNN_Bricks_Html_To_Bricks::image_setting( $url );
        }
        return array( 'success' => true, 'message' => 'Image updated on [' . $r['id'] . ']' );
    }

    private function op_set_settings( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $el             = &$this->el( $r['id'] );
        $el['settings'] = self::merge_settings( (array) ( $el['settings'] ?? array() ), (array) ( $op['settings'] ?? array() ) );
        return array( 'success' => true, 'message' => 'Settings updated on [' . $r['id'] . ']' );
    }

    private function op_update_class( array $op ) {
        $name = ltrim( (string) ( $op['class'] ?? ( $op['name'] ?? '' ) ), '.' );
        if ( ! isset( $this->classes[ $name ] ) ) {
            $near = array_slice( array_values( array_filter( array_keys( $this->classes ), function ( $n ) use ( $name ) {
                return strpos( $n, explode( '__', $name )[0] ) !== false;
            } ) ), 0, 8 );
            return array( 'success' => false, 'error' => 'No global class ".' . $name . '" exists' . ( $near ? '. Similar: ' . implode( ', ', $near ) : '' ) );
        }
        $current = (string) ( $this->classes[ $name ]['settings']['_cssCustom'] ?? '' );
        $css     = ! empty( $op['css'] ) ? (string) $op['css'] : self::merge_class_css( $current, $name, (array) ( $op['declarations'] ?? array() ) );
        $this->classes[ $name ]['settings']['_cssCustom'] = $css;
        $this->class_writes[ $name ]                      = $css;

        $synced = 0;
        if ( $this->touches_layout( $op ) ) {
            $synced = $this->refresh_layout( $this->classes[ $name ]['id'] );
        }
        return array( 'success' => true, 'message' => 'Class .' . $name . ' updated' . ( $synced ? ' (layout re-synced on ' . $synced . ' element(s))' : '' ) );
    }

    private function op_delete( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $doomed        = array_flip( array_merge( array( $r['id'] ), $this->descendants_of( $r['id'] ) ) );
        $this->content = array_values( array_filter( $this->content, function ( $el ) use ( $doomed ) {
            return ! isset( $doomed[ $el['id'] ] );
        } ) );
        foreach ( $this->content as &$el ) {
            if ( ! empty( $el['children'] ) ) {
                $el['children'] = array_values( array_filter( $el['children'], function ( $c ) use ( $doomed ) {
                    return ! isset( $doomed[ $c ] );
                } ) );
            }
        }
        unset( $el );
        return array( 'success' => true, 'message' => 'Deleted [' . $r['id'] . '] and ' . ( count( $doomed ) - 1 ) . ' descendant(s)' );
    }

    private function op_move( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $id         = $r['id'];
        $el         = $this->content[ $this->index()[ $id ] ];
        $new_parent = $el['parent'] ?? 0;

        if ( array_key_exists( 'parent', $op ) ) {
            if ( self::is_root( $op['parent'] ) ) {
                $new_parent = 0;
            } else {
                $pr = $this->resolve( $op['parent'] );
                if ( ! $pr['ok'] ) {
                    return array( 'success' => false, 'error' => 'Target parent: ' . $pr['error'], 'candidates' => $pr['candidates'] ?? null );
                }
                if ( $pr['id'] === $id || in_array( $pr['id'], $this->descendants_of( $id ), true ) ) {
                    return array( 'success' => false, 'error' => 'Cannot move an element inside itself' );
                }
                $new_parent = $pr['id'];
            }
        }

        $position = isset( $op['index'] ) && is_numeric( $op['index'] ) ? (int) $op['index'] : null;
        if ( ! empty( $op['before'] ) || ! empty( $op['after'] ) ) {
            $ref = $this->resolve( ! empty( $op['before'] ) ? $op['before'] : $op['after'] );
            if ( ! $ref['ok'] ) {
                return array( 'success' => false, 'error' => 'Reference element: ' . $ref['error'], 'candidates' => $ref['candidates'] ?? null );
            }
            $new_parent = $this->content[ $this->index()[ $ref['id'] ] ]['parent'] ?? 0;
            $sibs = self::is_root( $new_parent ) ? $this->root_ids() : $this->children_of( $new_parent );
            $sibs = array_values( array_diff( $sibs, array( $id ) ) );
            $at   = array_search( $ref['id'], $sibs, true );
            $position = ! empty( $op['before'] ) ? $at : $at + 1;
        }

        // Detach from the old parent.
        $old_parent = $el['parent'] ?? 0;
        if ( ! self::is_root( $old_parent ) && $this->exists( $old_parent ) ) {
            $op_el             = &$this->el( $old_parent );
            $op_el['children'] = array_values( array_diff( (array) ( $op_el['children'] ?? array() ), array( $id ) ) );
            unset( $op_el );
        }
        $self           = &$this->el( $id );
        $self['parent'] = self::is_root( $new_parent ) ? 0 : $new_parent;
        $moved          = $self;
        unset( $self );

        if ( self::is_root( $new_parent ) ) {
            $without = array_values( array_filter( $this->content, function ( $e ) use ( $id ) {
                return $e['id'] !== $id;
            } ) );
            $roots  = array_values( array_filter( $without, function ( $e ) {
                return self::is_root( $e['parent'] ?? 0 );
            } ) );
            $anchor = ( $position === null || $position >= count( $roots ) ) ? null : $roots[ $position ];
            $at     = count( $without );
            if ( $anchor ) {
                foreach ( $without as $i => $e ) {
                    if ( $e['id'] === $anchor['id'] ) {
                        $at = $i;
                        break;
                    }
                }
            }
            array_splice( $without, $at, 0, array( $moved ) );
            $this->content = $without;
        } else {
            $np             = &$this->el( $new_parent );
            $kids           = array_values( array_diff( (array) ( $np['children'] ?? array() ), array( $id ) ) );
            if ( $position === null || $position > count( $kids ) ) {
                $kids[] = $id;
            } else {
                array_splice( $kids, $position, 0, array( $id ) );
            }
            $np['children'] = $kids;
            unset( $np );
        }
        return array( 'success' => true, 'message' => 'Moved [' . $id . ']' );
    }

    private function root_ids() {
        $out = array();
        foreach ( $this->content as $e ) {
            if ( self::is_root( $e['parent'] ?? 0 ) ) {
                $out[] = $e['id'];
            }
        }
        return $out;
    }

    private function op_duplicate( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $subtree = array_merge( array( $r['id'] ), $this->descendants_of( $r['id'] ) );
        $map     = array();
        foreach ( $subtree as $old ) {
            $map[ $old ] = $this->ids->next();
        }
        $idx    = $this->index();
        $clones = array();
        foreach ( $subtree as $old ) {
            $src             = $this->content[ $idx[ $old ] ];
            $clone           = $src;
            $clone['id']     = $map[ $old ];
            $clone['parent'] = $old === $r['id'] ? ( $src['parent'] ?? 0 ) : $map[ $src['parent'] ];
            $clone['children'] = array_values( array_map( function ( $c ) use ( $map ) {
                return $map[ $c ];
            }, array_filter( (array) ( $src['children'] ?? array() ), function ( $c ) use ( $map ) {
                return isset( $map[ $c ] );
            } ) ) );
            $clones[] = $clone;
        }
        $last = max( array_map( function ( $id ) use ( $idx ) {
            return $idx[ $id ];
        }, $subtree ) );
        array_splice( $this->content, $last + 1, 0, $clones );

        $root_clone = $clones[0];
        if ( ! self::is_root( $root_clone['parent'] ) && $this->exists( $root_clone['parent'] ) ) {
            $p    = &$this->el( $root_clone['parent'] );
            $kids = (array) ( $p['children'] ?? array() );
            $at   = array_search( $r['id'], $kids, true );
            if ( $at !== false ) {
                array_splice( $kids, $at + 1, 0, array( $root_clone['id'] ) );
            } else {
                $kids[] = $root_clone['id'];
            }
            $p['children'] = $kids;
            unset( $p );
        }
        return array( 'success' => true, 'message' => 'Duplicated [' . $r['id'] . '] as [' . $root_clone['id'] . ']', 'newId' => $root_clone['id'] );
    }

    private function op_insert( array $op ) {
        $parent_id = 0;
        if ( isset( $op['parent'] ) && ! self::is_root( $op['parent'] ) ) {
            $pr = $this->resolve( $op['parent'] );
            if ( ! $pr['ok'] ) {
                return array( 'success' => false, 'error' => 'Parent: ' . $pr['error'], 'candidates' => $pr['candidates'] ?? null );
            }
            $parent_id = $pr['id'];
        }
        $name     = sanitize_key( (string) ( $op['name'] ?? 'block' ) );
        $settings = (array) ( $op['settings'] ?? array() );
        if ( isset( $op['text'] ) ) {
            $settings['text'] = (string) $op['text'];
        }
        if ( in_array( $name, array( 'button', 'text-link' ), true ) && isset( $settings['text'] ) && is_string( $settings['text'] ) ) {
            $split            = self::split_icon_from_label( $settings['text'] );
            $settings['text'] = $split['text'];
            if ( $split['icon'] ) {
                $settings['icon']         = $split['icon'];
                $settings['iconPosition'] = $split['position'];
            }
        }
        if ( ! empty( $op['icon'] ) && in_array( $name, array( 'button', 'text-link', 'icon' ), true ) ) {
            $icon = SNN_Bricks_Features::parse_fa_icon( (string) $op['icon'] );
            if ( $icon ) {
                $settings['icon'] = $icon;
                if ( $name !== 'icon' ) {
                    $settings['iconPosition'] = ( $op['iconPosition'] ?? '' ) === 'left' ? 'left' : 'right';
                }
            }
        }
        $cids = $this->class_ids( $op['classes'] ?? array() );
        if ( $cids ) {
            $settings['_cssGlobalClasses'] = $cids;
        }
        $id = $this->ids->next();
        $el = array( 'id' => $id, 'name' => $name, 'parent' => $parent_id, 'children' => array(), 'settings' => $settings, 'themeStyles' => array() );

        if ( $parent_id === 0 ) {
            $roots = $this->root_ids();
            if ( isset( $op['index'] ) && is_numeric( $op['index'] ) && (int) $op['index'] < count( $roots ) ) {
                $anchor = $roots[ (int) $op['index'] ];
                array_splice( $this->content, $this->index()[ $anchor ], 0, array( $el ) );
            } else {
                $this->content[] = $el;
            }
        } else {
            $p    = &$this->el( $parent_id );
            $kids = (array) ( $p['children'] ?? array() );
            if ( isset( $op['index'] ) && is_numeric( $op['index'] ) && (int) $op['index'] <= count( $kids ) ) {
                array_splice( $kids, (int) $op['index'], 0, array( $id ) );
            } else {
                $kids[] = $id;
            }
            $p['children'] = $kids;
            unset( $p );
            array_splice( $this->content, $this->index()[ $parent_id ] + 1, 0, array( $el ) );
        }
        return array( 'success' => true, 'message' => 'Inserted ' . $name . ' [' . $id . ']', 'newId' => $id );
    }

    private function op_wrap( array $op ) {
        $specs = ! empty( $op['targets'] ) && is_array( $op['targets'] ) ? $op['targets'] : array( $op['target'] ?? null );
        $ids   = array();
        foreach ( $specs as $sp ) {
            $r = $this->resolve( $sp );
            if ( ! $r['ok'] ) {
                return self::fail( $r );
            }
            $ids[] = $r['id'];
        }
        if ( ! $ids ) {
            return array( 'success' => false, 'error' => 'Nothing to wrap' );
        }
        $idx       = $this->index();
        $parent_id = $this->content[ $idx[ $ids[0] ] ]['parent'] ?? 0;
        foreach ( $ids as $id ) {
            if ( (string) ( $this->content[ $idx[ $id ] ]['parent'] ?? 0 ) !== (string) $parent_id ) {
                return array( 'success' => false, 'error' => 'All wrapped elements must share the same parent' );
            }
        }
        $wrapper_id = $this->ids->next();
        $cids       = $this->class_ids( $op['classes'] ?? array() );
        $wrapper    = array(
            'id' => $wrapper_id, 'name' => sanitize_key( (string) ( $op['name'] ?? 'block' ) ), 'parent' => $parent_id,
            'children' => $ids, 'settings' => $cids ? array( '_cssGlobalClasses' => $cids ) : array(), 'themeStyles' => array(),
        );
        if ( ! self::is_root( $parent_id ) && $this->exists( $parent_id ) ) {
            $p    = &$this->el( $parent_id );
            $kids = (array) ( $p['children'] ?? array() );
            $at   = array_search( $ids[0], $kids, true );
            $kids = array_values( array_diff( $kids, $ids ) );
            array_splice( $kids, $at === false ? count( $kids ) : $at, 0, array( $wrapper_id ) );
            $p['children'] = $kids;
            unset( $p );
        }
        foreach ( $ids as $id ) {
            $e           = &$this->el( $id );
            $e['parent'] = $wrapper_id;
            unset( $e );
        }
        array_splice( $this->content, $this->index()[ $ids[0] ], 0, array( $wrapper ) );
        return array( 'success' => true, 'message' => 'Wrapped ' . count( $ids ) . ' element(s) in [' . $wrapper_id . ']', 'newId' => $wrapper_id );
    }

    private function op_unwrap( array $op ) {
        $r = $this->resolve( $op['target'] ?? null );
        if ( ! $r['ok'] ) {
            return self::fail( $r );
        }
        $kids = $this->children_of( $r['id'] );
        if ( ! $kids ) {
            return array( 'success' => false, 'error' => 'Element [' . $r['id'] . '] has no children to unwrap' );
        }
        $parent_id = $this->content[ $this->index()[ $r['id'] ] ]['parent'] ?? 0;
        foreach ( $kids as $cid ) {
            $e           = &$this->el( $cid );
            $e['parent'] = $parent_id;
            unset( $e );
        }
        if ( ! self::is_root( $parent_id ) && $this->exists( $parent_id ) ) {
            $p    = &$this->el( $parent_id );
            $list = (array) ( $p['children'] ?? array() );
            $at   = array_search( $r['id'], $list, true );
            if ( $at === false ) {
                $list = array_merge( $list, $kids );
            } else {
                array_splice( $list, $at, 1, $kids );
            }
            $p['children'] = $list;
            unset( $p );
        } else {
            // Root children keep the wrapper's place in the flat order.
            $at = $this->index()[ $r['id'] ];
            $moved = array();
            foreach ( $kids as $cid ) {
                $moved[] = $this->content[ $this->index()[ $cid ] ];
            }
            $this->content = array_values( array_filter( $this->content, function ( $e ) use ( $kids ) {
                return ! in_array( $e['id'], $kids, true );
            } ) );
            $at = $this->index()[ $r['id'] ];
            array_splice( $this->content, $at, 0, $moved );
        }
        $wid           = $r['id'];
        $this->content = array_values( array_filter( $this->content, function ( $e ) use ( $wid ) {
            return $e['id'] !== $wid;
        } ) );
        return array( 'success' => true, 'message' => 'Unwrapped [' . $r['id'] . '], promoted ' . count( $kids ) . ' child element(s)' );
    }

    /** Structural integrity check after every op batch. */
    private function verify() {
        $idx = $this->index();
        $bad = array();
        foreach ( $this->content as $el ) {
            if ( empty( $el['id'] ) ) {
                $bad[] = 'element with no id';
            }
            if ( ! self::is_root( $el['parent'] ?? 0 ) && ! isset( $idx[ $el['parent'] ] ) ) {
                $bad[] = '[' . $el['id'] . '] points at missing parent ' . $el['parent'];
            }
            foreach ( (array) ( $el['children'] ?? array() ) as $c ) {
                if ( ! isset( $idx[ $c ] ) ) {
                    $bad[] = '[' . $el['id'] . '] lists missing child ' . $c;
                }
            }
        }
        return array_slice( $bad, 0, 10 );
    }

    // ── NativeLayoutSync ───────────────────────────────────────────────────

    private function touches_layout( array $op ) {
        if ( ! empty( $op['css'] ) ) {
            $css = (string) $op['css'];
            if ( preg_match( '/@media/i', $css ) ) {
                return true;
            }
            foreach ( array_keys( SNN_Bricks_Css::parse_declarations( str_replace( array( '{', '}' ), ';', $css ) ) ) as $p ) {
                if ( preg_match( self::LAYOUT_PROP, $p ) ) {
                    return true;
                }
            }
            return false;
        }
        foreach ( array_keys( (array) ( $op['declarations'] ?? array() ) ) as $p ) {
            if ( preg_match( self::LAYOUT_PROP, trim( (string) $p ) ) ) {
                return true;
            }
        }
        return false;
    }

    private static function tag_for( array $el ) {
        $st     = $el['settings'] ?? array();
        $custom = ( $st['tag'] ?? '' ) === 'custom' && ! empty( $st['customTag'] ) ? preg_split( '/\s+/', (string) $st['customTag'] )[0] : '';
        $pick   = function ( $t, $fallback ) {
            return preg_match( '/^[a-z][a-z0-9-]*$/i', (string) $t ) ? $t : $fallback;
        };
        switch ( $el['name'] ?? '' ) {
            case 'section':
                return $pick( $custom ?: ( $st['tag'] ?? '' ), 'section' );
            case 'container':
            case 'block':
            case 'div':
                return $pick( $custom ?: ( $st['tag'] ?? '' ), 'div' );
            case 'heading':
                return $pick( $custom ?: ( $st['tag'] ?? '' ), 'h3' );
            case 'text-basic':
                return $pick( $custom ?: ( $st['tag'] ?? '' ), 'div' );
            case 'button':
                return ! empty( $st['link'] ) ? 'a' : $pick( $st['tag'] ?? '', 'span' );
            case 'text-link':
                return ! empty( $st['link'] ) ? 'a' : 'span';
            case 'image':
                return 'img';
            case 'icon':
                return 'i';
            case 'svg':
                return 'svg';
        }
        return 'div';
    }

    /** Re-derive native layout for builder/MCP-built sections that use a class. */
    private function refresh_layout( $class_id ) {
        $manifest = SNN_Bricks_Section_Manifest::get( $this->post_id );
        $scopes   = array();
        foreach ( $manifest as $row ) {
            if ( ! $this->exists( $row['rootId'] ?? '' ) ) {
                continue;
            }
            $ids = array_merge( array( $row['rootId'] ), $this->descendants_of( $row['rootId'] ) );
            foreach ( $ids as $id ) {
                $el = $this->content[ $this->index()[ $id ] ];
                if ( in_array( $class_id, (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ), true ) ) {
                    $scopes[] = $ids;
                    break;
                }
            }
        }
        if ( ! $scopes ) {
            return 0;
        }

        // A detached DOM mirroring the Bricks tree: real tags, class names, leaf markup.
        $names = array();
        foreach ( $this->classes as $gc ) {
            $names[ $gc['id'] ] = $gc['name'];
        }
        $html = '';
        $make = function ( $id ) use ( &$make, $names ) {
            $el      = $this->content[ $this->index()[ $id ] ];
            $tag     = self::tag_for( $el );
            $classes = array();
            foreach ( (array) ( $el['settings']['_cssGlobalClasses'] ?? array() ) as $cid ) {
                if ( isset( $names[ $cid ] ) ) {
                    $classes[] = $names[ $cid ];
                }
            }
            $out  = '<' . $tag . ' data-snn-id="' . esc_attr( $id ) . '"' . ( $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '' ) . '>';
            $kids = $this->children_of( $id );
            if ( $kids ) {
                foreach ( $kids as $k ) {
                    $out .= $make( $k );
                }
            } elseif ( in_array( $el['name'], array( 'heading', 'text-basic', 'text' ), true ) && is_string( $el['settings']['text'] ?? null ) ) {
                $out .= $el['settings']['text'];
            }
            return in_array( $tag, array( 'img' ), true ) ? $out : $out . '</' . $tag . '>';
        };
        foreach ( $this->root_ids() as $r ) {
            $html .= $make( $r );
        }
        $parsed = SNN_Bricks_Dom::parse( $html );
        $node_for = array();
        foreach ( SNN_Bricks_Dom::descendants( $parsed['body'] ) as $n ) {
            $sid = SNN_Bricks_Dom::attr( $n, 'data-snn-id' );
            if ( $sid ) {
                $node_for[ $sid ] = $n;
            }
        }

        $css = array( SNN_Bricks_Html_To_Bricks::page_style_css( $this->content ) );
        foreach ( $this->classes as $gc ) {
            $css[] = SNN_Bricks_Html_To_Bricks::global_class_css( $gc );
        }
        $ctx = new SNN_Bricks_Compile_Context( SNN_Bricks_Cascade::build_rule_set( implode( "\n", $css ) ), SNN_Bricks_Native_Layout::breakpoint_plan() );

        $touched = 0;
        foreach ( $scopes as $ids ) {
            foreach ( $ids as $id ) {
                $el = &$this->el( $id );
                if ( empty( $node_for[ $id ] ) || in_array( $el['name'], array( 'custom-html-css-script', 'divider', 'svg' ), true ) ) {
                    unset( $el );
                    continue;
                }
                $settings = (array) ( $el['settings'] ?? array() );
                SNN_Bricks_Native_Layout::clear( $settings );
                foreach ( SNN_Bricks_Native_Layout::compute( $node_for[ $id ], $el['name'], $ctx ) as $k => $v ) {
                    $settings[ $k ] = $v;
                }
                $el['settings'] = $settings;
                unset( $el );
                $touched++;
            }
        }
        if ( $touched ) {
            $this->content_changed = true;
        }
        return $touched;
    }

    // ── run ────────────────────────────────────────────────────────────────

    /**
     * Apply all ops in memory. Nothing is saved here.
     * @return array { success, applied: string[], error?, failedOp?, candidates?, contentChanged }
     */
    public function apply( array $ops ) {
        if ( ! $ops ) {
            return array( 'success' => false, 'error' => 'No operations given' );
        }
        $applied = array();
        $new_ids = array();
        foreach ( $ops as $i => $op ) {
            if ( ! is_array( $op ) ) {
                return array( 'success' => false, 'error' => 'Operation ' . $i . ' is not an object' );
            }
            $kind = $op['op'] ?? ( $op['type'] ?? '' );
            if ( ! in_array( $kind, self::OPS, true ) ) {
                return array( 'success' => false, 'error' => 'Unknown operation "' . $kind . '". Available: ' . implode( ', ', self::OPS ), 'failedOp' => $i );
            }
            $res = $this->{ 'op_' . $kind }( $op );
            if ( empty( $res['success'] ) ) {
                return array( 'success' => false, 'error' => $res['error'], 'candidates' => $res['candidates'] ?? null, 'failedOp' => $i, 'failedKind' => $kind );
            }
            if ( $kind !== 'update_class' ) {
                $this->content_changed = true;
            }
            if ( ! empty( $res['newId'] ) ) {
                $new_ids[] = $res['newId'];
            }
            $applied[] = $res['message'];
        }
        $bad = $this->verify();
        if ( $bad ) {
            return array( 'success' => false, 'error' => 'Edit produced an inconsistent tree: ' . implode( '; ', $bad ) );
        }
        return array( 'success' => true, 'applied' => $applied, 'newIds' => $new_ids, 'contentChanged' => $this->content_changed );
    }

    /**
     * The edited tree, flat array in depth-first document order. Bricks'
     * Save_Pipeline rebuilds every children list from flat order on a
     * structural write, so a move only survives when the flat order agrees.
     */
    public function content() {
        $idx  = $this->index();
        $out  = array();
        $seen = array();
        $walk = function ( $id ) use ( &$walk, &$out, &$seen, $idx ) {
            if ( isset( $seen[ $id ] ) || ! isset( $idx[ $id ] ) ) {
                return;
            }
            $seen[ $id ] = true;
            $out[]       = $this->content[ $idx[ $id ] ];
            foreach ( $this->children_of( $id ) as $cid ) {
                $walk( $cid );
            }
        };
        foreach ( $this->root_ids() as $r ) {
            $walk( $r );
        }
        // Anything unreachable (should not happen after verify()) keeps its place at the end.
        foreach ( $this->content as $el ) {
            if ( ! isset( $seen[ $el['id'] ] ) ) {
                $out[] = $el;
            }
        }
        return $out;
    }

    /** New classes created by insert/wrap, as store rows. */
    public function new_classes() {
        $out = array();
        foreach ( $this->classes as $gc ) {
            if ( ! empty( $gc['new'] ) ) {
                $out[] = array( 'id' => $gc['id'], 'name' => $gc['name'], 'settings' => $gc['settings'] );
            }
        }
        return $out;
    }

    /** Class CSS edits on existing classes: name → css. */
    public function class_writes() {
        $out = array();
        foreach ( $this->class_writes as $name => $css ) {
            if ( empty( $this->classes[ $name ]['new'] ) ) {
                $out[ $name ] = $css;
            }
        }
        return $out;
    }
}
