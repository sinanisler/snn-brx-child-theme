<?php
/**
 * Images a hosted AI chat cannot send.
 *
 * A chat like claude.ai can read a design-export zip in its own sandbox but
 * cannot pass the file to a tool: a tool call only carries text the model
 * types. So the page is built from the HTML/CSS right away, with the export's
 * image paths kept as they are (images/hero.png), and the images follow in
 * one step:
 *
 *  1. Every build's feedback finds those relative image paths (missing()) and
 *     includes a one-click upload link (link()) for the user.
 *  2. The user opens it (logged in) and drops the zip, or the images.
 *  3. Each missing path is matched to a file by path, then by file name, the
 *     file goes to the media library, and the page elements and global
 *     classes are rewritten to the new URLs (place()).
 *  4. The path → URL map stays on the post, so passes built later get the
 *     real URLs straight away (rewrite_html()).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Asset_Drop {

    const META_TOKEN = '_snn_bricks_asset_drop';
    const META_MAP   = '_snn_bricks_asset_map';
    const TTL        = 172800; // link valid for 2 days
    const MAX_FILES  = 300;

    const IMAGE_EXT = 'png|jpe?g|gif|webp|avif|svg';

    /** A relative image path in a string (not a URL, not a data: URI). */
    private static function ref_regex() {
        return '#(?<![\w:/.%-])(?:\.{1,2}/|/)?(?:[\w%.@ -]+/)*[\w%.@ -]+\.(?:' . self::IMAGE_EXT . ')(?![\w.])#i';
    }

    private static function is_local_wp_path( $ref ) {
        return stripos( $ref, 'wp-content/' ) !== false || stripos( $ref, 'wp-includes/' ) !== false;
    }

    /** Relative image paths still used by the page's elements and classes. */
    public static function missing( array $elements, array $classes ) {
        $found = array();
        $scan  = function ( $value ) use ( &$scan, &$found ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $k => $v ) {
                    // Bricks keeps the library file's bare name next to its URL.
                    if ( ! in_array( (string) $k, array( 'filename', 'alt', 'title', 'caption', 'size' ), true ) ) {
                        $scan( $v );
                    }
                }
                return;
            }
            if ( ! is_string( $value ) || $value === '' || ! preg_match_all( self::ref_regex(), $value, $m ) ) {
                return;
            }
            foreach ( $m[0] as $ref ) {
                $ref = trim( $ref );
                if ( $ref !== '' && ! self::is_local_wp_path( $ref ) ) {
                    $found[ $ref ] = true;
                }
            }
        };
        $used = array();
        foreach ( $elements as $el ) {
            $s = (array) ( $el['settings'] ?? array() );
            foreach ( (array) ( $s['_cssGlobalClasses'] ?? array() ) as $cid ) {
                $used[ $cid ] = true;
            }
            // Text settings can mention "logo.png" in prose; only asset-bearing ones count.
            foreach ( array( 'image', 'items', 'images', 'video', '_background', 'content', '_cssCustom', 'icon', 'gallery' ) as $k ) {
                if ( isset( $s[ $k ] ) ) {
                    $scan( $s[ $k ] );
                }
            }
            foreach ( $s as $k => $v ) {
                if ( strpos( (string) $k, '_background' ) === 0 ) {
                    $scan( $v );
                }
            }
        }
        foreach ( $classes as $gc ) {
            if ( isset( $used[ $gc['id'] ?? '' ] ) ) {
                $scan( $gc['settings']['_cssCustom'] ?? '' );
            }
        }
        return array_keys( $found );
    }

    /** The upload link for a post (reused while valid). */
    public static function link( $post_id ) {
        $row = get_post_meta( $post_id, self::META_TOKEN, true );
        if ( ! is_array( $row ) || empty( $row['token'] ) || ( $row['expires'] ?? 0 ) < time() + 3600 ) {
            $row = array( 'token' => wp_generate_password( 32, false ), 'expires' => time() + self::TTL, 'user' => get_current_user_id() );
            update_post_meta( $post_id, self::META_TOKEN, $row );
        }
        return add_query_arg( array( 'action' => 'snn_bricks_drop', 'post' => (int) $post_id, 'token' => $row['token'] ), admin_url( 'admin-post.php' ) );
    }

    public static function valid_token( $post_id, $token ) {
        $row = get_post_meta( $post_id, self::META_TOKEN, true );
        return is_array( $row ) && ! empty( $row['token'] ) && hash_equals( $row['token'], (string) $token ) && $row['expires'] > time();
    }

    /** Stored path → URL map for a post. */
    public static function map( $post_id ) {
        $map = get_post_meta( $post_id, self::META_MAP, true );
        return is_array( $map ) ? $map : array();
    }

    /** Swap known paths in HTML/CSS before a build, so later passes use real URLs. */
    public static function rewrite_html( $post_id, $text ) {
        $map = self::map( $post_id );
        if ( ! $map || $text === '' ) {
            return $text;
        }
        return preg_replace_callback( self::ref_regex(), function ( $m ) use ( $map ) {
            $url = self::lookup( $map, $m[0] );
            return $url !== null ? $url : $m[0];
        }, $text );
    }

    private static function norm( $ref ) {
        $ref = strtolower( rawurldecode( trim( (string) $ref ) ) );
        return ltrim( preg_replace( '#^(\./|\.\./)+#', '', $ref ), '/' );
    }

    private static function lookup( array $map, $ref ) {
        $n = self::norm( $ref );
        if ( isset( $map[ $n ] ) ) {
            return $map[ $n ];
        }
        $parts = explode( '/', $n );
        $base  = end( $parts );
        return $map[ 'name:' . $base ] ?? null;
    }

    /**
     * Upload the files the page needs and point the page at them.
     *
     * @param int   $post_id
     * @param array $files   [ ['name' => 'images/a.png', 'bytes' => '...'] ] — zip entries or single images
     * @return array|WP_Error placed, notFound, failed, unused
     */
    public static function place( $post_id, array $files ) {
        $area     = SNN_Bricks_Write_Guard::area( $post_id );
        $elements = SNN_Bricks_Write_Guard::read_elements( $post_id, $area );
        $classes  = SNN_Bricks_Global_Classes_Store::read_classes();
        $missing  = self::missing( $elements, $classes );

        $by_path = array();
        $by_name = array();
        foreach ( $files as $i => $f ) {
            $n = self::norm( $f['name'] );
            $by_path[ $n ] = $i;
            $parts = explode( '/', $n );
            $base  = end( $parts );
            if ( ! isset( $by_name[ $base ] ) ) {
                $by_name[ $base ] = $i;
            }
        }

        $map      = self::map( $post_id );
        $placed   = array();
        $notfound = array();
        $failed   = array();
        $used     = array();
        foreach ( $missing as $ref ) {
            if ( self::lookup( $map, $ref ) !== null ) {
                continue;
            }
            $n     = self::norm( $ref );
            $parts = explode( '/', $n );
            $base  = end( $parts );
            $i     = $by_path[ $n ] ?? ( $by_name[ $base ] ?? null );
            // Longest path suffix match: "../img/a.png" vs "export/img/a.png".
            if ( $i === null ) {
                foreach ( $by_path as $p => $idx ) {
                    if ( substr( $p, -strlen( $n ) ) === $n ) {
                        $i = $idx;
                        break;
                    }
                }
            }
            if ( $i === null ) {
                $notfound[] = $ref;
                continue;
            }
            if ( ! isset( $used[ $i ] ) ) {
                $name  = basename( $files[ $i ]['name'] );
                $asset = preg_match( '/\.svg$/i', $name )
                    ? SNN_Bricks_Assets::store_svg( $files[ $i ]['bytes'], $name )
                    : SNN_Bricks_Assets::store_raster( $files[ $i ]['bytes'], $name );
                if ( is_wp_error( $asset ) ) {
                    $failed[] = $ref . ' (' . $asset->get_error_message() . ')';
                    continue;
                }
                $used[ $i ] = $asset['url'];
            }
            $map[ $n ]               = $used[ $i ];
            $map[ 'name:' . $base ]  = $used[ $i ];
            $placed[ $ref ]          = $used[ $i ];
        }
        update_post_meta( $post_id, self::META_MAP, $map );

        $rewritten = 0;
        if ( $placed ) {
            $swap = function ( $value ) use ( &$swap, $map, &$rewritten ) {
                if ( is_array( $value ) ) {
                    foreach ( $value as $k => $v ) {
                        $value[ $k ] = $swap( $v );
                    }
                    return $value;
                }
                if ( ! is_string( $value ) ) {
                    return $value;
                }
                return preg_replace_callback( self::ref_regex(), function ( $m ) use ( $map, &$rewritten ) {
                    $url = self::lookup( $map, $m[0] );
                    if ( $url === null ) {
                        return $m[0];
                    }
                    $rewritten++;
                    return $url;
                }, $value );
            };
            $digest = SNN_Bricks_Write_Guard::digest( $elements );
            $new    = $elements;
            foreach ( $new as &$el ) {
                if ( isset( $el['settings'] ) && is_array( $el['settings'] ) ) {
                    $el['settings'] = $swap( $el['settings'] );
                    // An image element pointing at a library file should carry its id too.
                    if ( isset( $el['settings']['image']['url'] ) && empty( $el['settings']['image']['id'] ) ) {
                        $id = attachment_url_to_postid( $el['settings']['image']['url'] );
                        if ( $id ) {
                            $el['settings']['image']['id'] = $id;
                        }
                    }
                }
            }
            unset( $el );
            if ( $new !== $elements ) {
                $saved = SNN_Bricks_Writer::save( $post_id, $area, $new, $digest );
                if ( is_wp_error( $saved ) ) {
                    return $saved;
                }
            }
            foreach ( $classes as $gc ) {
                $css = (string) ( $gc['settings']['_cssCustom'] ?? '' );
                if ( $css === '' || ! preg_match( self::ref_regex(), $css ) ) {
                    continue;
                }
                $out = $swap( $css );
                if ( $out !== $css ) {
                    SNN_Bricks_Global_Classes_Store::set_class_css( $gc['name'], $out );
                }
            }
        }

        $unused = array();
        foreach ( $files as $i => $f ) {
            if ( ! isset( $used[ $i ] ) && preg_match( '/\.(' . self::IMAGE_EXT . ')$/i', $f['name'] ) ) {
                $unused[] = $f['name'];
            }
        }
        return array(
            'placed'     => count( $placed ),
            'rewritten'  => $rewritten,
            'notFound'   => $notfound,
            'failed'     => $failed,
            'unusedFiles'=> count( $unused ),
            'stillMissing' => self::missing( SNN_Bricks_Write_Guard::read_elements( $post_id, $area ), SNN_Bricks_Global_Classes_Store::read_classes() ),
        );
    }

    /** Image files from an uploaded zip. */
    public static function files_from_zip( $path ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return new WP_Error( 'snn_zip_unavailable', 'The PHP zip extension is not installed on this server.' );
        }
        $zip = new ZipArchive();
        if ( $zip->open( $path ) !== true ) {
            return new WP_Error( 'snn_zip_unreadable', 'Could not read that .zip.' );
        }
        $files = array();
        for ( $i = 0; $i < $zip->numFiles && count( $files ) < self::MAX_FILES; $i++ ) {
            $stat = $zip->statIndex( $i );
            $name = (string) $stat['name'];
            if ( preg_match( '#(^|/)(__MACOSX|\.)#', $name ) || ! preg_match( '/\.(' . self::IMAGE_EXT . ')$/i', $name ) ) {
                continue;
            }
            if ( (int) $stat['size'] > SNN_Bricks_Assets::MAX_IMAGE_BYTES ) {
                continue;
            }
            $files[] = array( 'name' => $name, 'bytes' => (string) $zip->getFromIndex( $i ) );
        }
        $zip->close();
        return $files;
    }
}

// ── the upload page behind link() ─────────────────────────────────────

add_action( 'admin_post_nopriv_snn_bricks_drop', function () {
    $back = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
    wp_safe_redirect( wp_login_url( home_url( $back ) ) );
    exit;
} );

add_action( 'admin_post_snn_bricks_drop', 'snn_bricks_asset_drop_page' );
function snn_bricks_asset_drop_page() {
    $post_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
    $token   = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    if ( ! $post_id || ! SNN_Bricks_Asset_Drop::valid_token( $post_id, $token ) || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'upload_files' ) ) {
        wp_die( esc_html__( 'This upload link is invalid or has expired. Ask the AI for a new one.', 'snn' ), 403 );
    }

    $result = null;
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        check_admin_referer( 'snn_bricks_drop_' . $post_id );
        $files = array();
        $error = '';
        $up    = $_FILES['snn_files'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $count = $up && is_array( $up['name'] ) ? count( $up['name'] ) : 0;
        for ( $i = 0; $i < $count; $i++ ) {
            if ( ! empty( $up['error'][ $i ] ) || ! is_uploaded_file( $up['tmp_name'][ $i ] ) ) {
                $error = __( 'A file did not arrive. The server upload limit is ', 'snn' ) . ini_get( 'upload_max_filesize' ) . '.';
                continue;
            }
            $name = (string) $up['name'][ $i ];
            if ( preg_match( '/\.zip$/i', $name ) ) {
                $more = SNN_Bricks_Asset_Drop::files_from_zip( $up['tmp_name'][ $i ] );
                if ( is_wp_error( $more ) ) {
                    $error = $more->get_error_message();
                    continue;
                }
                $files = array_merge( $files, $more );
            } elseif ( preg_match( '/\.(png|jpe?g|gif|webp|avif|svg)$/i', $name ) ) {
                $files[] = array( 'name' => $name, 'bytes' => (string) file_get_contents( $up['tmp_name'][ $i ] ) );
            }
        }
        $result = $files ? SNN_Bricks_Asset_Drop::place( $post_id, $files ) : new WP_Error( 'snn_no_files', $error ?: __( 'No zip or image files were uploaded.', 'snn' ) );
        if ( function_exists( 'snn_log_user_activity' ) ) {
            snn_log_user_activity( 'Design images uploaded for Bricks page ' . $post_id, 'mcp', $post_id, 'mcp_ability' );
        }
    }

    $elements = SNN_Bricks_Write_Guard::read_elements( $post_id, SNN_Bricks_Write_Guard::area( $post_id ) );
    $missing  = SNN_Bricks_Asset_Drop::missing( $elements, SNN_Bricks_Global_Classes_Store::read_classes() );
    $title    = get_the_title( $post_id );
    nocache_headers();
    ?><!DOCTYPE html>
<html <?php language_attributes(); ?>><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( sprintf( __( 'Images for %s', 'snn' ), $title ) ); ?></title>
<style>
body{font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f3f4f6;color:#111;margin:0;padding:32px 16px}
.box{max-width:620px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
h1{font-size:20px;margin:0 0 6px}p{margin:8px 0}.muted{color:#6b7280;font-size:13px}
.drop{display:block;border:2px dashed #cbd5e1;border-radius:10px;padding:34px 16px;text-align:center;cursor:pointer;margin:18px 0}
.drop.over{border-color:#2563eb;background:#eff6ff}input[type=file]{display:none}
button{background:#2563eb;color:#fff;border:0;border-radius:8px;padding:11px 18px;font-size:15px;cursor:pointer;width:100%}
button:disabled{opacity:.5}.ok{background:#ecfdf5;border-radius:8px;padding:12px 14px}.err{background:#fef2f2;border-radius:8px;padding:12px 14px}
ul{margin:6px 0;padding-left:18px;max-height:180px;overflow:auto;font-size:13px}a{color:#2563eb}
</style></head><body><div class="box">
<h1><?php echo esc_html( sprintf( __( 'Images for "%s"', 'snn' ), $title ) ); ?></h1>
<?php if ( is_wp_error( $result ) ) : ?>
    <p class="err"><?php echo esc_html( $result->get_error_message() ); ?></p>
<?php elseif ( is_array( $result ) ) : ?>
    <div class="ok">
        <strong><?php echo esc_html( sprintf( _n( '%d image placed on the page.', '%d images placed on the page.', $result['placed'], 'snn' ), $result['placed'] ) ); ?></strong>
        <?php if ( $result['failed'] ) : ?><p><?php esc_html_e( 'Could not upload:', 'snn' ); ?></p><ul><?php foreach ( $result['failed'] as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ( ! $missing ) : ?><p><?php esc_html_e( 'All images are in place. You can close this tab and tell the AI you are done.', 'snn' ); ?></p><?php endif; ?>
    </div>
<?php endif; ?>
<?php if ( $missing ) : ?>
    <p><?php echo esc_html( sprintf( _n( 'The page uses %d image that is not on the site yet. Drop the design .zip (or the images) here; files are matched by name.', 'The page uses %d images that are not on the site yet. Drop the design .zip (or the images) here; files are matched by name.', count( $missing ), 'snn' ), count( $missing ) ) ); ?></p>
    <ul><?php foreach ( array_slice( $missing, 0, 60 ) as $m ) : ?><li><?php echo esc_html( $m ); ?></li><?php endforeach; ?></ul>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="snn_bricks_drop"><input type="hidden" name="post" value="<?php echo (int) $post_id; ?>"><input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
        <?php wp_nonce_field( 'snn_bricks_drop_' . $post_id ); ?>
        <label class="drop" id="drop"><span id="dropText"><?php esc_html_e( 'Drop the .zip or images here, or click to choose', 'snn' ); ?></span>
            <input type="file" name="snn_files[]" id="files" multiple accept=".zip,image/*"></label>
        <button type="submit" id="go" disabled><?php esc_html_e( 'Upload and place images', 'snn' ); ?></button>
    </form>
    <p class="muted"><?php echo esc_html( sprintf( __( 'Server upload limit: %s per file.', 'snn' ), ini_get( 'upload_max_filesize' ) ) ); ?></p>
<?php elseif ( ! is_array( $result ) ) : ?>
    <p><?php esc_html_e( 'Every image on this page is already in the media library. Nothing to upload.', 'snn' ); ?></p>
<?php endif; ?>
<p class="muted"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank"><?php esc_html_e( 'View the page', 'snn' ); ?></a></p>
</div>
<script>
(function(){var d=document.getElementById('drop'),f=document.getElementById('files'),g=document.getElementById('go'),t=document.getElementById('dropText');if(!d)return;
function show(){var n=f.files.length;g.disabled=!n;t.textContent=n?(n===1?f.files[0].name:n+' files'):t.textContent;}
f.addEventListener('change',show);
['dragenter','dragover'].forEach(function(e){d.addEventListener(e,function(ev){ev.preventDefault();d.classList.add('over');});});
['dragleave','drop'].forEach(function(e){d.addEventListener(e,function(ev){ev.preventDefault();d.classList.remove('over');});});
d.addEventListener('drop',function(ev){f.files=ev.dataTransfer.files;show();});
g&&g.form.addEventListener('submit',function(){g.disabled=true;g.textContent='<?php echo esc_js( __( 'Uploading…', 'snn' ) ); ?>';});})();
</script>
</body></html><?php
    exit;
}
