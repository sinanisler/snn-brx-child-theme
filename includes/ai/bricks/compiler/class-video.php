<?php
/**
 * Video: YouTube and Vimeo embeds and file videos as Bricks' native video element.
 *
 *   <iframe src="https://www.youtube.com/embed/abc123?autoplay=1" title="Demo"></iframe>  -> video (youtube)
 *   <iframe src="https://player.vimeo.com/video/98765"></iframe>                          -> video (vimeo)
 *   <video src="/clip.mp4"></video>  or  <video><source src="/clip.mp4"></video>          -> video (file)
 *
 * Any other iframe stays raw HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_Bricks_Video {

    /**
     * Native video settings for an iframe or video element, or null when it is not a video
     * this converter knows.
     */
    public static function settings_for( $el ) {
        $tag = SNN_Bricks_Dom::tag( $el );
        if ( $tag === 'iframe' ) {
            $src = (string) SNN_Bricks_Dom::attr( $el, 'src' );
            if ( $src === '' ) {
                return null;
            }
            $title = (string) SNN_Bricks_Dom::attr( $el, 'title' );
            $out   = array();
            if ( $title !== '' ) {
                $out['iframeTitle'] = $title;
            }
            $youtube = self::youtube_id( $src );
            if ( $youtube !== null ) {
                $out['videoType']  = 'youtube';
                $out['youTubeId']  = $youtube;
                if ( preg_match( '/[?&]autoplay=1\b/', $src ) ) {
                    $out['youtubeAutoplay'] = true;
                }
                if ( preg_match( '/[?&]loop=1\b/', $src ) ) {
                    $out['youtubeLoop'] = true;
                }
                return $out;
            }
            $vimeo = self::vimeo_id( $src );
            if ( $vimeo !== null ) {
                $out['videoType'] = 'vimeo';
                $out['vimeoId']   = $vimeo;
                return $out;
            }
            return null;
        }
        if ( $tag === 'video' ) {
            $url = (string) SNN_Bricks_Dom::attr( $el, 'src' );
            if ( $url === '' ) {
                foreach ( SNN_Bricks_Dom::children( $el ) as $kid ) {
                    if ( SNN_Bricks_Dom::tag( $kid ) === 'source' && SNN_Bricks_Dom::attr( $kid, 'src' ) ) {
                        $url = (string) SNN_Bricks_Dom::attr( $kid, 'src' );
                        break;
                    }
                }
            }
            if ( $url === '' ) {
                return null;
            }
            return array( 'videoType' => 'file', 'fileUrl' => $url );
        }
        return null;
    }

    /** Is this iframe a YouTube or Vimeo embed? */
    public static function is_embed( $el ) {
        return SNN_Bricks_Dom::tag( $el ) === 'iframe' && self::settings_for( $el ) !== null;
    }

    public static function youtube_id( $src ) {
        if ( preg_match( '#youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,20})#', $src, $m ) ) {
            return $m[1];
        }
        if ( preg_match( '#youtu\.be/([A-Za-z0-9_-]{6,20})#', $src, $m ) ) {
            return $m[1];
        }
        if ( preg_match( '#youtube\.com/watch\?(?:[^"]*&)?v=([A-Za-z0-9_-]{6,20})#', $src, $m ) ) {
            return $m[1];
        }
        return null;
    }

    public static function vimeo_id( $src ) {
        if ( preg_match( '#vimeo\.com/(?:video/)?(\d{5,12})#', $src, $m ) ) {
            return $m[1];
        }
        return null;
    }
}
