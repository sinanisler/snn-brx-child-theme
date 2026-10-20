<?php
/**
 * Design-export .zip import (Figma / Penpot "export to HTML" and friends).
 *
 * Server-side port of ZipImport in ai-agent-and-chat-bricks.php: unpack with
 * the same limits, pick the primary HTML page, inline local stylesheets and
 * one level of @import, upload raster assets to the media library, inline
 * SVG assets, and rewrite every reference. The result is the HTML + CSS an
 * MCP client (or the compiler directly) works from.
 *
 * Not ported: sorting sections by rendered position. That needs a layout
 * engine; the result reports it so the client can order sections itself.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Zip_Import {

    const LIMITS = array(
        'zipBytes'       => 26214400,  // 25 MB (decoded) — base64 in a tool call
        'entries'        => 800,
        'unpackedBytes'  => 262144000, // 250 MB zip-bomb guard
        'images'         => 80,
        'imageBytes'     => 10485760,
        'inlineSvgBytes' => 49152,
        'textBytes'      => 4194304,
    );

    const RASTER = array( 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif' );

    private $zip;
    private $entries = array(); // path => ['index' => int, 'size' => int]
    private $by_path = array();
    private $by_name = array();

    private static function ext( $p ) {
        $parts = explode( '.', $p );
        return strtolower( (string) end( $parts ) );
    }

    private static function dir( $p ) {
        $at = strrpos( $p, '/' );
        return $at === false ? '' : substr( $p, 0, $at + 1 );
    }

    private static function normalize( $p ) {
        $out = array();
        foreach ( explode( '/', str_replace( '\\', '/', $p ) ) as $seg ) {
            if ( $seg === '' || $seg === '.' ) {
                continue;
            }
            if ( $seg === '..' ) {
                array_pop( $out );
                continue;
            }
            $out[] = $seg;
        }
        return implode( '/', $out );
    }

    private static function resolve_ref( $base_dir, $ref ) {
        $r = trim( preg_replace( '/[?#].*$/', '', (string) $ref ) );
        if ( $r === '' ) {
            return '';
        }
        $r = rawurldecode( $r );
        if ( preg_match( '/^(https?:|data:|blob:|mailto:|tel:|#)/i', $r ) || strpos( $r, '//' ) === 0 ) {
            return '';
        }
        return self::normalize( strpos( $r, '/' ) === 0 ? substr( $r, 1 ) : $base_dir . $r );
    }

    private function lookup( $resolved ) {
        if ( $resolved === '' ) {
            return null;
        }
        $lower = strtolower( $resolved );
        if ( isset( $this->by_path[ $lower ] ) ) {
            return $this->by_path[ $lower ];
        }
        $parts = explode( '/', $lower );
        return $this->by_name[ end( $parts ) ] ?? null;
    }

    private function read( $path ) {
        return (string) $this->zip->getFromIndex( $this->entries[ $path ]['index'] );
    }

    /**
     * @param string $bytes      Raw zip bytes.
     * @param array  $options    html_path (pick a page), upload_assets (default true)
     * @return array|WP_Error {htmlPath, candidates, html, css, report}
     */
    public static function extract( $bytes, array $options = array() ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return new WP_Error( 'snn_zip_unavailable', 'The PHP zip extension is not installed on this server.' );
        }
        if ( strlen( $bytes ) > self::LIMITS['zipBytes'] ) {
            return new WP_Error( 'snn_zip_too_large', 'The .zip is larger than 25 MB. Upload images separately with snn-bricks-upload-asset and send HTML/CSS instead.' );
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp = wp_tempnam( 'snn-design.zip' );
        file_put_contents( $tmp, $bytes );
        $self = new self();
        try {
            return $self->run( $tmp, $options );
        } finally {
            if ( $self->zip ) {
                $self->zip->close();
            }
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
    }

    private function run( $file, array $options ) {
        $this->zip = new ZipArchive();
        if ( $this->zip->open( $file ) !== true ) {
            $this->zip = null;
            return new WP_Error( 'snn_zip_unreadable', 'Could not read that .zip.' );
        }

        $unpacked = 0;
        for ( $i = 0; $i < $this->zip->numFiles; $i++ ) {
            $stat = $this->zip->statIndex( $i );
            $name = (string) $stat['name'];
            if ( substr( $name, -1 ) === '/' || preg_match( '/(^|\/)(__MACOSX|\.DS_Store)/i', $name ) || preg_match( '/(^|\/)\./', $name ) ) {
                continue;
            }
            $path = self::normalize( $name );
            if ( $path === '' ) {
                continue;
            }
            $this->entries[ $path ] = array( 'index' => $i, 'size' => (int) $stat['size'] );
            $unpacked += (int) $stat['size'];
            $lower = strtolower( $path );
            $this->by_path[ $lower ] = $path;
            $parts = explode( '/', $lower );
            $base  = end( $parts );
            if ( ! isset( $this->by_name[ $base ] ) ) {
                $this->by_name[ $base ] = $path;
            }
        }

        if ( ! $this->entries ) {
            return new WP_Error( 'snn_zip_empty', 'The archive is empty.' );
        }
        if ( count( $this->entries ) > self::LIMITS['entries'] ) {
            return new WP_Error( 'snn_zip_entries', 'The archive holds ' . count( $this->entries ) . ' files (limit ' . self::LIMITS['entries'] . ').' );
        }
        if ( $unpacked > self::LIMITS['unpackedBytes'] ) {
            return new WP_Error( 'snn_zip_bomb', 'The archive unpacks to more than 250 MB.' );
        }

        $html_paths = array_values( array_filter( array_keys( $this->entries ), function ( $p ) {
            return in_array( self::ext( $p ), array( 'html', 'htm' ), true );
        } ) );
        if ( ! $html_paths ) {
            return new WP_Error( 'snn_zip_no_html', 'No .html file inside. This importer needs an HTML/CSS export.' );
        }

        $primary = null;
        if ( ! empty( $options['html_path'] ) && in_array( $options['html_path'], $html_paths, true ) ) {
            $primary = $options['html_path'];
        } else {
            $primary = $this->pick_primary( $html_paths );
        }
        if ( $this->entries[ $primary ]['size'] > self::LIMITS['textBytes'] ) {
            return new WP_Error( 'snn_zip_html_large', 'The HTML file is larger than 4 MB.' );
        }

        $base_dir = self::dir( $primary );
        $html     = $this->read( $primary );

        // Remote stylesheets (Google Fonts ...) live in <head>, which is not part of
        // the returned body markup: carry them over as @import lines in the CSS.
        $remote = array();
        if ( preg_match_all( '#<link\b[^>]*>#i', $html, $links ) ) {
            foreach ( $links[0] as $tag ) {
                if ( preg_match( '/\brel\s*=\s*["\']?[^"\'>]*stylesheet/i', $tag ) && preg_match( '/\bhref\s*=\s*["\']((?:https?:)?\/\/[^"\']+)["\']/i', $tag, $h ) ) {
                    $remote[] = '@import url("' . html_entity_decode( $h[1], ENT_QUOTES ) . '");';
                }
            }
        }

        // Scripts and embeds out; local stylesheets inlined.
        $html = preg_replace( '#<(script|noscript|iframe|object|embed)\b[^>]*>.*?</\1\s*>#is', '', $html );
        $html = preg_replace( '#<(script|iframe|object|embed)\b[^>]*/?>#is', '', $html );
        $html = preg_replace_callback( '#<link\b[^>]*>#i', function ( $m ) use ( $base_dir ) {
            $tag = $m[0];
            if ( ! preg_match( '/\brel\s*=\s*["\']?[^"\'>]*stylesheet/i', $tag ) || ! preg_match( '/\bhref\s*=\s*["\']([^"\']+)["\']/i', $tag, $h ) ) {
                return $tag;
            }
            $href = $h[1];
            if ( preg_match( '#^(https?:)?//#i', $href ) ) {
                return $tag; // remote sheets (Google Fonts) keep working as links
            }
            $hit = $this->lookup( self::resolve_ref( $base_dir, $href ) );
            if ( ! $hit || $this->entries[ $hit ]['size'] > self::LIMITS['textBytes'] ) {
                return '';
            }
            $css = $this->inline_imports( $this->read( $hit ), self::dir( $hit ) );
            $css = $this->rebase_css_urls( $css, self::dir( $hit ), $base_dir );
            return '<style data-snn-zip-css="' . esc_attr( $hit ) . '">' . $css . '</style>';
        }, $html );

        $report = array( 'uploaded' => 0, 'inlinedSvg' => 0, 'skipped' => array(), 'visualOrder' => 'not-checked' );
        $html   = $this->process_assets( $html, $base_dir, $report, $options['upload_assets'] ?? true );

        // Split into stylesheet text and body markup.
        $css = '';
        if ( preg_match_all( '#<style\b[^>]*>(.*?)</style\s*>#is', $html, $sm ) ) {
            $css = implode( "\n", $sm[1] );
        }
        if ( $remote ) {
            $css = implode( "\n", array_unique( $remote ) ) . "\n" . $css;
        }
        $parsed = SNN_Bricks_Dom::parse( $html );
        foreach ( SNN_Bricks_Dom::find_tags( $parsed['body'], array( 'style' ) ) as $st ) {
            SNN_Bricks_Dom::remove( $st );
        }
        $body_html = trim( SNN_Bricks_Dom::inner_html( $parsed['body'] ) );
        if ( $body_html === '' ) {
            return new WP_Error( 'snn_zip_empty_body', 'The HTML file has an empty <body>.' );
        }

        return array(
            'htmlPath'   => $primary,
            'candidates' => $html_paths,
            'html'       => $body_html,
            'css'        => $css,
            'report'     => $report,
        );
    }

    /** index.html at the shallowest depth wins; otherwise the largest file. */
    private function pick_primary( array $paths ) {
        $score = function ( $p ) {
            $parts = explode( '/', strtolower( $p ) );
            $base  = end( $parts );
            return preg_match( '/^index\.html?$/', $base ) ? 0 : ( preg_match( '/^(home|main|page)\.html?$/', $base ) ? 1 : 2 );
        };
        usort( $paths, function ( $a, $b ) use ( $score ) {
            return ( $score( $a ) - $score( $b ) )
                ?: ( count( explode( '/', $a ) ) - count( explode( '/', $b ) ) )
                ?: ( $this->entries[ $b ]['size'] - $this->entries[ $a ]['size'] );
        } );
        return $paths[0];
    }

    /** Splice in local @import'ed stylesheets (one level); remote ones stay. */
    private function inline_imports( $css, $base_dir ) {
        return preg_replace_callback( '/@import\s+(?:url\(\s*)?[\'"]?([^\'")\s]+)[\'"]?\s*\)?\s*;/i', function ( $m ) use ( $base_dir ) {
            if ( preg_match( '#^(https?:)?//#i', $m[1] ) ) {
                return $m[0];
            }
            $hit = $this->lookup( self::resolve_ref( $base_dir, $m[1] ) );
            if ( ! $hit || $this->entries[ $hit ]['size'] > self::LIMITS['textBytes'] ) {
                return $m[0];
            }
            return $this->rebase_css_urls( $this->read( $hit ), self::dir( $hit ), $base_dir );
        }, (string) $css );
    }

    /** url() in a stylesheet resolves against the sheet; once inlined it must resolve against the page. */
    private function rebase_css_urls( $css, $sheet_dir, $page_dir ) {
        if ( $sheet_dir === $page_dir ) {
            return $css;
        }
        return preg_replace_callback( '/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', function ( $m ) use ( $sheet_dir ) {
            $resolved = self::resolve_ref( $sheet_dir, $m[2] );
            return $resolved === '' ? $m[0] : 'url("/' . $resolved . '")';
        }, $css );
    }

    private function process_assets( $html, $base_dir, array &$report, $upload ) {
        // 1. Collect every local reference: markup attributes and CSS url().
        $refs = array();
        $add  = function ( $ref ) use ( &$refs, $base_dir ) {
            $ref = html_entity_decode( trim( (string) $ref ), ENT_QUOTES );
            if ( $ref === '' || isset( $refs[ $ref ] ) ) {
                return;
            }
            $hit = $this->lookup( self::resolve_ref( $base_dir, $ref ) );
            if ( $hit ) {
                $refs[ $ref ] = $hit;
            }
        };
        if ( preg_match_all( '/\b(?:src|poster)\s*=\s*["\']([^"\']+)["\']/i', $html, $m ) ) {
            array_map( $add, $m[1] );
        }
        if ( preg_match_all( '/\bsrcset\s*=\s*["\']([^"\']+)["\']/i', $html, $m ) ) {
            foreach ( $m[1] as $set ) {
                foreach ( explode( ',', $set ) as $part ) {
                    $add( preg_split( '/\s+/', trim( $part ) )[0] );
                }
            }
        }
        if ( preg_match_all( '/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $html, $m ) ) {
            array_map( $add, $m[1] );
        }

        // 2. Resolve each reference to a replacement URL.
        $replacement = array();
        $done        = array();
        $uploaded    = 0;
        foreach ( $refs as $ref => $path ) {
            $name = basename( $path );
            if ( isset( $done[ $path ] ) ) {
                $replacement[ $ref ] = $done[ $path ];
                continue;
            }
            $ext  = self::ext( $path );
            $size = $this->entries[ $path ]['size'];

            if ( $ext === 'svg' ) {
                if ( $size > self::LIMITS['inlineSvgBytes'] ) {
                    $report['skipped'][] = $name . ' (SVG over 48 KB)';
                    continue;
                }
                $svg = SNN_Bricks_Svg_Sanitizer::sanitize( $this->read( $path ) );
                if ( $svg === '' ) {
                    $report['skipped'][] = $name . ' (unreadable SVG)';
                    continue;
                }
                $url = 'data:image/svg+xml;charset=utf-8,' . rawurlencode( $svg );
                $done[ $path ]       = $url;
                $replacement[ $ref ] = $url;
                $report['inlinedSvg']++;
                continue;
            }
            if ( ! in_array( $ext, self::RASTER, true ) ) {
                $report['skipped'][] = $name . ' (unsupported type .' . $ext . ')';
                continue;
            }
            if ( ! $upload ) {
                $report['skipped'][] = $name . ' (asset uploads disabled)';
                continue;
            }
            if ( $size > self::LIMITS['imageBytes'] ) {
                $report['skipped'][] = $name . ' (over 10 MB)';
                continue;
            }
            if ( $uploaded >= self::LIMITS['images'] ) {
                $report['skipped'][] = $name . ' (image limit ' . self::LIMITS['images'] . ' reached)';
                continue;
            }
            $asset = SNN_Bricks_Assets::store_raster( $this->read( $path ), $name );
            if ( is_wp_error( $asset ) ) {
                $report['skipped'][] = $name . ' (upload failed: ' . $asset->get_error_message() . ')';
                continue;
            }
            $done[ $path ]       = $asset['url'];
            $replacement[ $ref ] = $asset['url'];
            $uploaded++;
        }
        $report['uploaded'] = $uploaded;

        // 3 + 4. Rewrite markup attributes and CSS text.
        foreach ( $replacement as $ref => $url ) {
            $q   = preg_quote( $ref, '/' );
            $qe  = preg_quote( esc_attr( $ref ), '/' );
            $html = preg_replace_callback( '/(\b(?:src|poster)\s*=\s*["\'])(?:' . $q . '|' . $qe . ')(["\'])/i', function ( $m ) use ( $url ) {
                return $m[1] . esc_attr( $url ) . $m[2];
            }, $html );
            $html = preg_replace_callback( '/(\bsrcset\s*=\s*["\'][^"\']*?)(?<![^\s,"\'])(?:' . $q . ')(?=[\s,"\'])/i', function ( $m ) use ( $url ) {
                return $m[1] . esc_attr( $url );
            }, $html );
            $html = str_replace(
                array( 'url(' . $ref . ')', "url('" . $ref . "')", 'url("' . $ref . '")', 'url(&quot;' . $ref . '&quot;)' ),
                'url("' . $url . '")',
                $html
            );
        }

        // 5. An <img> pointing at an inlined SVG becomes real inline SVG.
        $html = preg_replace_callback( '#<img\b([^>]*)\bsrc\s*=\s*["\']data:image/svg\+xml;charset=utf-8,([^"\']+)["\']([^>]*)>#i', function ( $m ) {
            $svg = rawurldecode( html_entity_decode( $m[2], ENT_QUOTES ) );
            if ( ! preg_match( '/^\s*<svg[\s>]/i', $svg ) ) {
                return $m[0];
            }
            if ( preg_match( '/\bclass\s*=\s*["\']([^"\']*)["\']/i', $m[1] . $m[3], $cls ) ) {
                $svg = preg_replace( '/^\s*<svg\b/i', '<svg class="' . esc_attr( $cls[1] ) . '"', $svg, 1 );
            }
            return $svg;
        }, $html );

        return $html;
    }
}
