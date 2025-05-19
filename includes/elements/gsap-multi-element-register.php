<?php

add_action( 'init', function () {
    $targets = [ 'section', 'container', 'block', 'div' ];
    foreach ( $targets as $name ) {
        add_filter( "bricks/elements/{$name}/controls", function ( $controls ) {
            $controls['custom_data_animate_dynamic_elements'] = [
                'tab'     => 'content',
                'label'   => esc_html__( 'Select Animation', 'snn' ),
                'type'    => 'select',
                'options'     => [
                    // Fading
                    'style_start-opacity:0, style_end-opacity:1' => esc_html__('Fade In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0' => esc_html__('Fade Out', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(-1000px), style_end-transform:translateY(0px)' => esc_html__('Fade In Down', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px), style_end-transform:translateY(1000px)' => esc_html__('Fade Out Down', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px), style_end-transform:translateX(0px)' => esc_html__('Fade In Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px), style_end-transform:translateX(-1000px)' => esc_html__('Fade Out Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px), style_end-transform:translateX(0px)' => esc_html__('Fade In Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px), style_end-transform:translateX(1000px)' => esc_html__('Fade Out Right', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(1000px), style_end-transform:translateY(0px)' => esc_html__('Fade In Up', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px), style_end-transform:translateY(-1000px)' => esc_html__('Fade Out Up', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px) translateY(-1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Top Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(-1000px) translateY(-1000px)' => esc_html__('Fade Out Top Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px) translateY(-1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Top Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(1000px) translateY(-1000px)' => esc_html__('Fade Out Top Right', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px) translateY(1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Bottom Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(-1000px) translateY(1000px)' => esc_html__('Fade Out Bottom Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px) translateY(1000px), style_end-transform:translateX(0px) translateY(0px)' => esc_html__('Fade In Bottom Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) translateY(0px), style_end-transform:translateX(1000px) translateY(1000px)' => esc_html__('Fade Out Bottom Right', 'snn'),

                    // Zooming
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8), style_end-transform:scale(1)' => esc_html__('Zoom In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1), style_end-transform:scale(0.8)' => esc_html__('Zoom Out', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateY(-1000px), style_end-transform:scale(1) translateY(0px)' => esc_html__('Zoom In Down', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateY(0px), style_end-transform:scale(0.8) translateY(1000px)' => esc_html__('Zoom Out Down', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateX(-1000px), style_end-transform:scale(1) translateX(0px)' => esc_html__('Zoom In Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateX(0px), style_end-transform:scale(0.8) translateX(-1000px)' => esc_html__('Zoom Out Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateX(1000px), style_end-transform:scale(1) translateX(0px)' => esc_html__('Zoom In Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateX(0px), style_end-transform:scale(0.8) translateX(1000px)' => esc_html__('Zoom Out Right', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) translateY(1000px), style_end-transform:scale(1) translateY(0px)' => esc_html__('Zoom In Up', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) translateY(0px), style_end-transform:scale(0.8) translateY(-1000px)' => esc_html__('Zoom Out Up', 'snn'),


                    // Sliding (sequence improved: now stacks with ";")
                    'style_start-transform:translateY(-1000px); style_end-transform:translateY(0px)' => esc_html__('Slide In Down (Seq)', 'snn'),
                    'style_start-transform:translateY(0px); style_end-transform:translateY(1000px)' => esc_html__('Slide Out Down (Seq)', 'snn'),
                    'style_start-transform:translateX(-1000px); style_end-transform:translateX(0px)' => esc_html__('Slide In Left (Seq)', 'snn'),
                    'style_start-transform:translateX(0px); style_end-transform:translateX(-1000px)' => esc_html__('Slide Out Left (Seq)', 'snn'),
                    'style_start-transform:translateX(1000px); style_end-transform:translateX(0px)' => esc_html__('Slide In Right (Seq)', 'snn'),
                    'style_start-transform:translateX(0px); style_end-transform:translateX(1000px)' => esc_html__('Slide Out Right (Seq)', 'snn'),
                    'style_start-transform:translateY(1000px); style_end-transform:translateY(0px)' => esc_html__('Slide In Up (Seq)', 'snn'),
                    'style_start-transform:translateY(0px); style_end-transform:translateY(-1000px)' => esc_html__('Slide Out Up (Seq)', 'snn'),

                    // Blob/clipPath Morphing (now uses clipPath for morph, not just skew/transform)
                    'style_start-clipPath:ellipse(80% 50% at 50% 50%), style_end-clipPath:ellipse(100% 100% at 50% 50%)' => esc_html__('Blob Morph In', 'snn'),
                    'style_start-clipPath:ellipse(100% 100% at 50% 50%), style_end-clipPath:ellipse(80% 50% at 50% 50%)' => esc_html__('Blob Morph Out', 'snn'),

                    // All other animations kept exactly the same as you had
                    // Rotating
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(-200deg) scale(0.8), style_end-transform:rotate(0deg) scale(1)' => esc_html__('Rotate In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) scale(1), style_end-transform:rotate(200deg) scale(0.8)' => esc_html__('Rotate Out', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(-90deg) translateY(-1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Down Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(90deg) translateY(1000px)' => esc_html__('Rotate Out Down Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(90deg) translateY(-1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Down Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(-90deg) translateY(1000px)' => esc_html__('Rotate Out Down Right', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(-90deg) translateY(1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Up Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(90deg) translateY(-1000px)' => esc_html__('Rotate Out Up Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate(90deg) translateY(1000px), style_end-transform:rotate(0deg) translateY(0px)' => esc_html__('Rotate In Up Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate(0deg) translateY(0px), style_end-transform:rotate(-90deg) translateY(-1000px)' => esc_html__('Rotate Out Up Right', 'snn'),

                    // Flipping & 3D
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateX(90deg), style_end-transform:rotateX(0deg)' => esc_html__('Flip In X', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateX(0deg), style_end-transform:rotateX(90deg)' => esc_html__('Flip Out X', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateY(90deg), style_end-transform:rotateY(0deg)' => esc_html__('Flip In Y', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateY(0deg), style_end-transform:rotateY(90deg)' => esc_html__('Flip Out Y', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotate3d(1,1,0,90deg), style_end-transform:rotate3d(1,1,0,0deg)' => esc_html__('Flip In 3D', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotate3d(1,1,0,0deg), style_end-transform:rotate3d(1,1,0,90deg)' => esc_html__('Flip Out 3D', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateY(90deg) scale(0.8), style_end-transform:rotateY(0deg) scale(1)' => esc_html__('Cube Rotate In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateY(0deg) scale(1), style_end-transform:rotateY(90deg) scale(0.8)' => esc_html__('Cube Rotate Out', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:rotateY(180deg), style_end-transform:rotateY(0deg)' => esc_html__('Card Flip In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:rotateY(0deg), style_end-transform:rotateY(180deg)' => esc_html__('Card Flip Out', 'snn'),

                    // Blurring & Focusing
                    'style_start-opacity:0, style_end-opacity:1, style_start-filter:blur(10px), style_end-filter:blur(0px)' => esc_html__('Blur In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-filter:blur(0px), style_end-filter:blur(10px)' => esc_html__('Blur Out', 'snn'),
                    'style_start-filter:blur(10px), style_end-filter:blur(0px)' => esc_html__('Focus In', 'snn'),
                    'style_start-filter:blur(0px), style_end-filter:blur(10px)' => esc_html__('Focus Out', 'snn'),

                    // Revealing & Masking
                    'style_start-clipPath:inset(100% 0 0 0), style_end-clipPath:inset(0 0 0 0)' => esc_html__('Mask Reveal In', 'snn'),
                    'style_start-clipPath:inset(0 0 0 0), style_end-clipPath:inset(100% 0 0 0)' => esc_html__('Mask Reveal Out', 'snn'),
                    'style_start-backgroundColor:transparent, style_end-backgroundColor:linear-gradient(90deg,#fff 0%,#000 100%)' => esc_html__('Gradient Reveal In', 'snn'),
                    'style_start-backgroundColor:linear-gradient(90deg,#fff 0%,#000 100%), style_end-backgroundColor:transparent' => esc_html__('Gradient Reveal Out', 'snn'),
                    'style_start-clipPath:circle(0% at 50% 50%), style_end-clipPath:circle(100% at 50% 50%)' => esc_html__('Clip Reveal In', 'snn'),
                    'style_start-clipPath:circle(100% at 50% 50%), style_end-clipPath:circle(0% at 50% 50%)' => esc_html__('Clip Reveal Out', 'snn'),

                    // Morphing
                    'style_start-borderRadius:50%, style_end-borderRadius:0%' => esc_html__('Morph In', 'snn'),
                    'style_start-borderRadius:0%, style_end-borderRadius:50%' => esc_html__('Morph Out', 'snn'),
                    'style_start-transform:scale(1) skewX(30deg), style_end-transform:scale(1) skewX(0deg)' => esc_html__('Blob Morph In (Legacy)', 'snn'),
                    'style_start-transform:scale(1) skewX(0deg), style_end-transform:scale(1) skewX(30deg)' => esc_html__('Blob Morph Out (Legacy)', 'snn'),



                    // Light Speed
                    'style_start-transform:translateX(100%) skewX(-30deg), style_end-transform:translateX(0%) skewX(0deg)' => esc_html__('Light Speed In Right', 'snn'),
                    'style_start-transform:translateX(0%) skewX(0deg), style_end-transform:translateX(100%) skewX(30deg)' => esc_html__('Light Speed Out Right', 'snn'),
                    'style_start-transform:translateX(-100%) skewX(30deg), style_end-transform:translateX(0%) skewX(0deg)' => esc_html__('Light Speed In Left', 'snn'),
                    'style_start-transform:translateX(0%) skewX(0deg), style_end-transform:translateX(-100%) skewX(-30deg)' => esc_html__('Light Speed Out Left', 'snn'),

                    // Specials (unchanged)
                    'style_start-transform:translateX(-100%) rotate(-120deg), style_end-transform:translateX(0%) rotate(0deg)' => esc_html__('Roll In', 'snn'),
                    'style_start-transform:translateX(0%) rotate(0deg), style_end-transform:translateX(100%) rotate(120deg)' => esc_html__('Roll Out', 'snn'),
                    'style_start-transform:rotate(0deg), style_end-transform:rotate(80deg), style_end-opacity:0' => esc_html__('Hinge', 'snn'),
                    'style_start-transform:scale(0.1) rotate(30deg), style_end-transform:scale(1) rotate(0deg), style_start-opacity:0, style_end-opacity:1' => esc_html__('Jack In The Box', 'snn'),
                    'style_start-transform:translateY(-200px), style_end-transform:translateY(0px), style_start-opacity:0, style_end-opacity:1' => esc_html__('Drop In', 'snn'),
                    'style_start-transform:translateY(0px), style_end-transform:translateY(200px), style_start-opacity:1, style_end-opacity:0' => esc_html__('Drop Out', 'snn'),
                    'style_start-transform:scale(0), style_end-transform:scale(1), style_start-opacity:0, style_end-opacity:1' => esc_html__('Explode In', 'snn'),
                    'style_start-transform:scale(1), style_end-transform:scale(2), style_start-opacity:1, style_end-opacity:0' => esc_html__('Explode Out', 'snn'),
                    'style_start-transform:scale(0.2), style_end-transform:scale(1), style_start-opacity:0, style_end-opacity:1' => esc_html__('Puff In', 'snn'),
                    'style_start-transform:scale(1), style_end-transform:scale(2), style_start-opacity:1, style_end-opacity:0' => esc_html__('Puff Out', 'snn'),
                    'style_start-transform:translateY(1000px) scale(0), style_end-transform:translateY(0px) scale(1), style_start-opacity:0, style_end-opacity:1' => esc_html__('Smoke In', 'snn'),
                    'style_start-transform:translateY(0px) scale(1), style_end-transform:translateY(-1000px) scale(2), style_start-opacity:1, style_end-opacity:0' => esc_html__('Smoke Out', 'snn'),
                    'style_start-transform:scale(0.5), style_end-transform:scale(1), style_start-opacity:0, style_end-opacity:1' => esc_html__('Firework In', 'snn'),
                    'style_start-transform:scale(1), style_end-transform:scale(0.5), style_start-opacity:1, style_end-opacity:0' => esc_html__('Firework Out', 'snn'),

                    // Parallax & Depth
                    'style_start-transform:translateY(80px) scale(1.1), style_end-transform:translateY(0px) scale(1)' => esc_html__('Parallax In', 'snn'),
                    'style_start-transform:translateY(0px) scale(1), style_end-transform:translateY(-80px) scale(1.1)' => esc_html__('Parallax Out', 'snn'),
                    'style_start-transform:perspective(400px) translateZ(-50px), style_end-transform:perspective(400px) translateZ(0px)' => esc_html__('Depth In', 'snn'),
                    'style_start-transform:perspective(400px) translateZ(0px), style_end-transform:perspective(400px) translateZ(-50px)' => esc_html__('Depth Out', 'snn'),

                    // Combo & Complex
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(1000px), style_end-transform:translateY(0px)' => esc_html__('Fade In Up', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px), style_end-transform:translateY(1000px)' => esc_html__('Fade Out Down', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.8) rotate(-30deg), style_end-transform:scale(1) rotate(0deg)' => esc_html__('Zoom In Rotate', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1) rotate(0deg), style_end-transform:scale(0.8) rotate(30deg)' => esc_html__('Zoom Out Rotate', 'snn'),
                    'style_start-transform:rotateY(90deg), style_end-transform:rotateY(0deg), style_start-opacity:0, style_end-opacity:1' => esc_html__('Flip In Fade', 'snn'),
                    'style_start-transform:rotateY(0deg), style_end-transform:rotateY(90deg), style_start-opacity:1, style_end-opacity:0' => esc_html__('Flip Out Fade', 'snn'),

                    // Micro-Interactions
                    'style_start-transform:scale(1), style_end-transform:scale(0.95)' => esc_html__('Button Press', 'snn'),
                    'style_start-transform:scale(0.95), style_end-transform:scale(1)' => esc_html__('Button Release', 'snn'),
                    'style_start-transform:scale(1), style_end-transform:scale(1.08)' => esc_html__('Hover Pop', 'snn'),
                    'style_start-boxShadow:0px 0px 0px 0px #fff, style_end-boxShadow:0px 0px 16px 0px #f39c12' => esc_html__('Hover Glow', 'snn'),
                    'style_start-boxShadow:0px 0px 0px 0px #33c3f0, style_end-boxShadow:0px 0px 16px 0px #33c3f0' => esc_html__('Input Focus Glow', 'snn'),
                    'style_start-transform:scale(0.7), style_end-transform:scale(1.2)' => esc_html__('Tooltip Pop', 'snn'),
                    'style_start-transform:rotate(0deg), style_end-transform:rotate(360deg)' => esc_html__('Icon Spin', 'snn'),
                    'style_start-transform:scale(0.8), style_end-transform:scale(1.3)' => esc_html__('Star Burst', 'snn'),





                    // Attention Seekers (improved yoyo: loop:true, scroll:false)
                    'style_start-transform:translateY(0px), style_end-transform:translateY(-30px), yoyo:true, repeat:5, loop:true, scroll:false' => esc_html__('Bounce', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, yoyo:true, repeat:3, loop:true, scroll:false' => esc_html__('Flash', 'snn'),
                    'style_start-transform:scale(1), style_end-transform:scale(1.1), yoyo:true, repeat:3, loop:true, scroll:false' => esc_html__('Pulse', 'snn'),
                    'style_start-transform:scaleX(1.25) scaleY(0.75), style_end-transform:scaleX(0.75) scaleY(1.25), yoyo:true, repeat:2, loop:true, scroll:false' => esc_html__('Rubber Band', 'snn'),
                    'style_start-transform:translateX(-10px), style_end-transform:translateX(10px), yoyo:true, repeat:6, loop:true, scroll:false' => esc_html__('Shake', 'snn'),
                    'style_start-transform:translateX(-20px), style_end-transform:translateX(20px), yoyo:true, repeat:8, loop:true, scroll:false' => esc_html__('Shake X', 'snn'),
                    'style_start-transform:translateY(-20px), style_end-transform:translateY(20px), yoyo:true, repeat:8, loop:true, scroll:false' => esc_html__('Shake Y', 'snn'),
                    'style_start-transform:translateX(0px) rotateY(0deg), style_end-transform:translateX(-20px) rotateY(-20deg), yoyo:true, repeat:2, loop:true, scroll:false' => esc_html__('Head Shake', 'snn'),
                    'style_start-transform:rotate(0deg), style_end-transform:rotate(15deg), yoyo:true, repeat:3, loop:true, scroll:false' => esc_html__('Swing', 'snn'),
                    'style_start-transform:scale(0.9) rotate(-3deg), style_end-transform:scale(1.1) rotate(3deg), yoyo:true, repeat:4, loop:true, scroll:false' => esc_html__('Tada', 'snn'),
                    'style_start-transform:translateX(-10px) skewX(-5deg), style_end-transform:translateX(10px) skewX(5deg), yoyo:true, repeat:5, loop:true, scroll:false' => esc_html__('Wobble', 'snn'),
                    'style_start-transform:skewX(0deg), style_end-transform:skewX(25deg), yoyo:true, repeat:3, loop:true, scroll:false' => esc_html__('Jello', 'snn'),
                    'style_start-transform:scale(1), style_end-transform:scale(1.2), yoyo:true, repeat:6, loop:true, scroll:false' => esc_html__('Heart Beat', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, yoyo:true, repeat:6, loop:true, scroll:false' => esc_html__('Blink', 'snn'),
                    'style_start-transform:rotate(-8deg), style_end-transform:rotate(8deg), yoyo:true, repeat:8, loop:true, scroll:false' => esc_html__('Wiggle', 'snn'),
                    'style_start-opacity:0.8, style_end-opacity:1, yoyo:true, repeat:8, loop:true, scroll:false' => esc_html__('Flicker', 'snn'),



                    // Bouncing (improved for yoyo to actually work: added scroll:false, loop:true for meaningful yoyo)
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.3), style_end-transform:scale(1), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce In', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1), style_end-transform:scale(0.3), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce Out', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(-1000px) scale(0.3), style_end-transform:translateY(0px) scale(1), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce In Down', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px) scale(1), style_end-transform:translateY(1000px) scale(0.3), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce Out Down', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px) scale(0.3), style_end-transform:translateX(0px) scale(1), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce In Left', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) scale(1), style_end-transform:translateX(-1000px) scale(0.3), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce Out Left', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px) scale(0.3), style_end-transform:translateX(0px) scale(1), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce In Right', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) scale(1), style_end-transform:translateX(1000px) scale(0.3), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce Out Right', 'snn'),
                    'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(1000px) scale(0.3), style_end-transform:translateY(0px) scale(1), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce In Up', 'snn'),
                    'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px) scale(1), style_end-transform:translateY(-1000px) scale(0.3), yoyo:true, loop:true, scroll:false' => esc_html__('Bounce Out Up', 'snn'),




                    // Scroll markers/options
                    'start:top 80%, end:bottom 30%' => esc_html__('Top 80% to Bottom 30%', 'snn'),
                    'start:top 60%, end:bottom 40%' => esc_html__('Top 60% to Bottom 40%', 'snn'),
                    'start:top 50%, end:bottom 50%' => esc_html__('Top 50% to Bottom 50%', 'snn'),
                    'start:top 40%, end:bottom 60%' => esc_html__('Top 40% to Bottom 60%', 'snn'),
                    'start:top top, end:bottom bottom' => esc_html__('Top of viewport to Bottom of viewport', 'snn'),
                    'start:center center, end:center center' => esc_html__('Center of viewport to Center of viewport', 'snn'),
                    'start:top 1000px, end:bottom 1000px' => esc_html__('Top 1000px to Bottom 1000px', 'snn'),
                    'start:top 0%, end:bottom 100%' => esc_html__('Top 0% to Bottom 100%', 'snn'),
                    'start:top 75%, end:bottom 25%' => esc_html__('Top 75% to Bottom 25%', 'snn'),
                    'start:top+=100, end:bottom+=50' => esc_html__('Top +1000px to Bottom +50px', 'snn'),
                    'start:top center, end:bottom center' => esc_html__('Top Center to Bottom Center', 'snn'),
                    'start:bottom 80%, end:bottom 10%' => esc_html__('Bottom 80% to Bottom 10%', 'snn'),

                    'markers:true' => esc_html__('Markers True', 'snn'),
                    'scroll:false' => esc_html__('Scroll False', 'snn'),
                    'loop:true' => esc_html__('Loop True', 'snn'),
                    'pin:true' => esc_html__('Pin True', 'snn'),
                    'stagger:1' => esc_html__('Stagger 1', 'snn'),
                    'stagger:0.5' => esc_html__('Stagger 0.5', 'snn'),
                    'stagger:0.1' => esc_html__('Stagger 0.1', 'snn'),

                ],

                'default' => '',
                'multiple' => true,
                'searchable' => true,
                'clearable' => true,
                'description' => '<br><br><br><br><br><br><br><br>',
            ];
            return $controls;
        } );
    }
} );


add_filter( 'bricks/element/render_attributes', function( $attributes, $key, $element ) {
    $targets = [ 'section', 'container', 'block', 'div' ];
    $custom  = $element->settings['custom_data_animate_dynamic_elements'] ?? '';

    if ( ! empty( $custom ) && in_array( $element->name, $targets, true ) ) {
        // If $custom is an array, convert it to a comma-separated string
        if ( is_array( $custom ) ) {
            $custom = implode( ',', $custom );
        }

        // Add the data-animate attribute to the root element
        $attributes[$key]['data-animate'] = esc_attr( $custom );
    }

    return $attributes;
}, 999, 3 );
