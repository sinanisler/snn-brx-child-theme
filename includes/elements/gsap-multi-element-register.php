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


'style_start-transform:translateY(-1000px), style_end-transform:translateY(0px)' => esc_html__('Slide In Down ', 'snn'),
'style_start-transform:translateY(0px), style_end-transform:translateY(1000px)' => esc_html__('Slide Out Down ', 'snn'),
'style_start-transform:translateX(-1000px), style_end-transform:translateX(0px)' => esc_html__('Slide In Left ', 'snn'),
'style_start-transform:translateX(0px), style_end-transform:translateX(-1000px)' => esc_html__('Slide Out Left ', 'snn'),
'style_start-transform:translateX(1000px), style_end-transform:translateX(0px)' => esc_html__('Slide In Right ', 'snn'),
'style_start-transform:translateX(0px), style_end-transform:translateX(1000px)' => esc_html__('Slide Out Right ', 'snn'),
'style_start-transform:translateY(1000px), style_end-transform:translateY(0px)' => esc_html__('Slide In Up ', 'snn'),
'style_start-transform:translateY(0px), style_end-transform:translateY(-1000px)' => esc_html__('Slide Out Up ', 'snn'),


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



// Attention Seekers (cleaned)
'style_start-transform:translateY(0px), style_end-transform:translateY(-30px)' => esc_html__('Bounce', 'snn'),
'style_start-opacity:1, style_end-opacity:0' => esc_html__('Flash', 'snn'),
'style_start-transform:scale(1), style_end-transform:scale(1.1)' => esc_html__('Pulse', 'snn'),
'style_start-transform:scaleX(1.25) scaleY(0.75), style_end-transform:scaleX(0.75) scaleY(1.25)' => esc_html__('Rubber Band', 'snn'),
'style_start-transform:translateX(-10px), style_end-transform:translateX(10px)' => esc_html__('Shake', 'snn'),
'style_start-transform:translateX(-20px), style_end-transform:translateX(20px)' => esc_html__('Shake X', 'snn'),
'style_start-transform:translateY(-20px), style_end-transform:translateY(20px)' => esc_html__('Shake Y', 'snn'),
'style_start-transform:translateX(0px) rotateY(0deg), style_end-transform:translateX(-20px) rotateY(-20deg)' => esc_html__('Head Shake', 'snn'),
'style_start-transform:rotate(0deg), style_end-transform:rotate(15deg)' => esc_html__('Swing', 'snn'),
'style_start-transform:scale(0.9) rotate(-3deg), style_end-transform:scale(1.1) rotate(3deg)' => esc_html__('Tada', 'snn'),
'style_start-transform:translateX(-10px) skewX(-5deg), style_end-transform:translateX(10px) skewX(5deg)' => esc_html__('Wobble', 'snn'),
'style_start-transform:skewX(0deg), style_end-transform:skewX(25deg)' => esc_html__('Jello', 'snn'),
'style_start-transform:scale(1), style_end-transform:scale(1.2)' => esc_html__('Heart Beat', 'snn'),
'style_start-opacity:1, style_end-opacity:0' => esc_html__('Blink', 'snn'),
'style_start-transform:rotate(-8deg), style_end-transform:rotate(8deg)' => esc_html__('Wiggle', 'snn'),
'style_start-opacity:0.8, style_end-opacity:1' => esc_html__('Flicker', 'snn'),

// Bouncing (cleaned)
'style_start-opacity:0, style_end-opacity:1, style_start-transform:scale(0.3), style_end-transform:scale(1)' => esc_html__('Bounce In', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:scale(1), style_end-transform:scale(0.3)' => esc_html__('Bounce Out', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(-1000px) scale(0.3), style_end-transform:translateY(0px) scale(1)' => esc_html__('Bounce In Down', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px) scale(1), style_end-transform:translateY(1000px) scale(0.3)' => esc_html__('Bounce Out Down', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(-1000px) scale(0.3), style_end-transform:translateX(0px) scale(1)' => esc_html__('Bounce In Left', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) scale(1), style_end-transform:translateX(-1000px) scale(0.3)' => esc_html__('Bounce Out Left', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateX(1000px) scale(0.3), style_end-transform:translateX(0px) scale(1)' => esc_html__('Bounce In Right', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateX(0px) scale(1), style_end-transform:translateX(1000px) scale(0.3)' => esc_html__('Bounce Out Right', 'snn'),
'style_start-opacity:0, style_end-opacity:1, style_start-transform:translateY(1000px) scale(0.3), style_end-transform:translateY(0px) scale(1)' => esc_html__('Bounce In Up', 'snn'),
'style_start-opacity:1, style_end-opacity:0, style_start-transform:translateY(0px) scale(1), style_end-transform:translateY(-1000px) scale(0.3)' => esc_html__('Bounce Out Up', 'snn'),





// Scaling (very common utility)
'style_start-transform:scale(0), style_end-transform:scale(1)' => esc_html__('Scale 0 to 1', 'snn'),
'style_start-transform:scale(1), style_end-transform:scale(0)' => esc_html__('Scale 1 to 0', 'snn'),

// Width and Height Transitions
'style_start-width:0%, style_end-width:100%' => esc_html__('Width 0% to 100%', 'snn'),
'style_start-width:100%, style_end-width:0%' => esc_html__('Width 100% to 0%', 'snn'),
'style_start-height:0%, style_end-height:100%' => esc_html__('Height 0% to 100%', 'snn'),
'style_start-height:100%, style_end-height:0%' => esc_html__('Height 100% to 0%', 'snn'),

// Opacity Utility (great for fading/combos)
'style_start-opacity:0, style_end-opacity:1' => esc_html__('Opacity 0 to 1', 'snn'),
'style_start-opacity:1, style_end-opacity:0' => esc_html__('Opacity 1 to 0', 'snn'),

// Rotate
'style_start-transform:rotate(0deg), style_end-transform:rotate(180deg)' => esc_html__('Rotate 0 to 180', 'snn'),
'style_start-transform:rotate(180deg), style_end-transform:rotate(0deg)' => esc_html__('Rotate 180 to 0', 'snn'),


// Blob/clipPath Morphing (now uses clipPath for morph, not just skew/transform)
'style_start-clipPath:ellipse(80% 50% at 50% 50%), style_end-clipPath:ellipse(100% 100% at 50% 50%)' => esc_html__('Clip Blob Morph In', 'snn'),
'style_start-clipPath:ellipse(100% 100% at 50% 50%), style_end-clipPath:ellipse(80% 50% at 50% 50%)' => esc_html__('Clip Blob Morph Out', 'snn'),


// Revealing & Masking
'style_start-clipPath:inset(100% 0 0 0), style_end-clipPath:inset(0 0 0 0)' => esc_html__('Clip Mask Reveal In', 'snn'),
'style_start-clipPath:inset(0 0 0 0), style_end-clipPath:inset(100% 0 0 0)' => esc_html__('Clip Mask Reveal Out', 'snn'),
'style_start-backgroundColor:transparent, style_end-backgroundColor:linear-gradient(90deg,#fff 0%,#000 100%)' => esc_html__('Gradient Reveal In', 'snn'),
'style_start-backgroundColor:linear-gradient(90deg,#fff 0%,#000 100%), style_end-backgroundColor:transparent' => esc_html__('Gradient Reveal Out', 'snn'),
'style_start-clipPath:circle(0% at 50% 50%), style_end-clipPath:circle(100% at 50% 50%)' => esc_html__('Clip Reveal In', 'snn'),
'style_start-clipPath:circle(100% at 50% 50%), style_end-clipPath:circle(0% at 50% 50%)' => esc_html__('Clip Reveal Out', 'snn'),


// Horizontal Reveal (Left to Right)
'style_start-clipPath:inset(0% 100% 0% 0%), style_end-clipPath:inset(0% 0% 0% 0%)' => esc_html__('Clip Reveal Left to Right', 'snn'),

// Horizontal Hide (Right to Left)
'style_start-clipPath:inset(0% 0% 0% 0%), style_end-clipPath:inset(0% 100% 0% 0%)' => esc_html__('Clip Hide Right to Left', 'snn'),

// Vertical Reveal (Top to Bottom)
'style_start-clipPath:inset(100% 0% 0% 0%), style_end-clipPath:inset(0% 0% 0% 0%)' => esc_html__('Clip Reveal Top to Bottom', 'snn'),

// Vertical Hide (Bottom to Top)
'style_start-clipPath:inset(0% 0% 0% 0%), style_end-clipPath:inset(100% 0% 0% 0%)' => esc_html__('Clip Hide Bottom to Top', 'snn'),

// Diagonal Reveal (Top Left to Bottom Right)
'style_start-clipPath:polygon(0% 0%, 0% 0%, 0% 0%, 0% 0%), style_end-clipPath:polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)' => esc_html__('Clip Reveal Diagonal Top Left', 'snn'),

// Diagonal Hide (Bottom Right to Top Left)
'style_start-clipPath:polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%), style_end-clipPath:polygon(100% 100%, 100% 100%, 100% 100%, 100% 100%)' => esc_html__('Clip Hide Diagonal Bottom Right', 'snn'),

// Circle Reveal (Center Out)
'style_start-clipPath:circle(0% at 50% 50%), style_end-clipPath:circle(150% at 50% 50%)' => esc_html__('Clip Circle Center Out', 'snn'),

// Circle Hide (Out to Center)
'style_start-clipPath:circle(150% at 50% 50%), style_end-clipPath:circle(0% at 50% 50%)' => esc_html__('Clip Circle Out to Center', 'snn'),

// Ellipse Reveal (Horizontal)
'style_start-clipPath:ellipse(0% 50% at 50% 50%), style_end-clipPath:ellipse(100% 50% at 50% 50%)' => esc_html__('Clip Ellipse Horizontal Reveal', 'snn'),

// Ellipse Reveal (Vertical)
'style_start-clipPath:ellipse(50% 0% at 50% 50%), style_end-clipPath:ellipse(50% 100% at 50% 50%)' => esc_html__('Clip Ellipse Vertical Reveal', 'snn'),

// Triangle Reveal (Left)
'style_start-clipPath:polygon(0% 0%, 0% 0%, 0% 100%), style_end-clipPath:polygon(0% 0%, 100% 50%, 0% 100%)' => esc_html__('Clip Triangle Left Reveal', 'snn'),

// Triangle Reveal (Right)
'style_start-clipPath:polygon(100% 0%, 100% 0%, 100% 100%), style_end-clipPath:polygon(100% 0%, 0% 50%, 100% 100%)' => esc_html__('Clip Triangle Right Reveal', 'snn'),

// Diamond Reveal (Center Out)
'style_start-clipPath:polygon(50% 50%, 50% 50%, 50% 50%, 50% 50%), style_end-clipPath:polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)' => esc_html__('Clip Diamond Center Out', 'snn'),

// Diamond Hide (To Center)
'style_start-clipPath:polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%), style_end-clipPath:polygon(50% 50%, 50% 50%, 50% 50%, 50% 50%)' => esc_html__('Clip Diamond In to Center', 'snn'),

// Hexagon Reveal (Center Out)
'style_start-clipPath:polygon(50% 50%, 50% 50%, 50% 50%, 50% 50%, 50% 50%, 50% 50%), style_end-clipPath:polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)' => esc_html__('Clip Hexagon Center Out', 'snn'),







// Scroll markers/options
'start:top 80%, end:bottom 30%' => esc_html__('Start Top 80% to Bottom 30%', 'snn'),
'start:top 60%, end:bottom 40%' => esc_html__('Start Top 60% to Bottom 40%', 'snn'),
'start:top 50%, end:bottom 50%' => esc_html__('Start Top 50% to Bottom 50%', 'snn'),
'start:top 40%, end:bottom 60%' => esc_html__('Start Top 40% to Bottom 60%', 'snn'),
'start:top top, end:bottom bottom' => esc_html__('Start Top of viewport to Bottom of viewport', 'snn'),
'start:center center, end:center center' => esc_html__('Start Center of viewport to Center of viewport', 'snn'),
'start:top 1000px, end:bottom 1000px' => esc_html__('Start Top 1000px to Bottom 1000px', 'snn'),
'start:top 0%, end:bottom 100%' => esc_html__('Start Top 0% to Bottom 100%', 'snn'),
'start:top 75%, end:bottom 25%' => esc_html__('Start Top 75% to Bottom 25%', 'snn'),
'start:top+=100, end:bottom+=50' => esc_html__('Start Top +1000px to Bottom +50px', 'snn'),
'start:top center, end:bottom center' => esc_html__('Start Top Center to Bottom Center', 'snn'),
'start:bottom 80%, end:bottom 10%' => esc_html__('Start Bottom 80% to Bottom 10%', 'snn'),









'markers:true' => esc_html__('Markers True', 'snn'),
'scroll:false' => esc_html__('Scroll False', 'snn'),
'loop:true' => esc_html__('Loop True', 'snn'),
'pin:true' => esc_html__('Pin True', 'snn'),
'stagger:1' => esc_html__('Stagger 1', 'snn'),
'stagger:0.5' => esc_html__('Stagger 0.5', 'snn'),
'stagger:0.1' => esc_html__('Stagger 0.1', 'snn'),
'scrub:false' => esc_html__('Scrub False', 'snn'),
'yoyo:true' => esc_html__('Yoyo True', 'snn'),








// Ease linear
'ease:linear' => esc_html__('Easing: Linear', 'snn'),

// Ease In
'ease:power1.in' => esc_html__('Easing: Ease In (power1)', 'snn'),
'ease:power2.in' => esc_html__('Easing: Ease In (power2)', 'snn'),
'ease:power3.in' => esc_html__('Easing: Ease In (power3)', 'snn'),
'ease:power4.in' => esc_html__('Easing: Ease In (power4)', 'snn'),

// Ease Out
'ease:power1.out' => esc_html__('Easing: Ease Out (power1)', 'snn'),
'ease:power2.out' => esc_html__('Easing: Ease Out (power2)', 'snn'),
'ease:power3.out' => esc_html__('Easing: Ease Out (power3)', 'snn'),
'ease:power4.out' => esc_html__('Easing: Ease Out (power4)', 'snn'),

// Ease In Out
'ease:power1.inOut' => esc_html__('Easing: InOut (power1)', 'snn'),
'ease:power2.inOut' => esc_html__('Easing: InOut (power2)', 'snn'),
'ease:power3.inOut' => esc_html__('Easing: InOut (power3)', 'snn'),
'ease:power4.inOut' => esc_html__('Easing: InOut (power4)', 'snn'),

// Sine
'ease:sine.in' => esc_html__('Easing: Sine In', 'snn'),
'ease:sine.out' => esc_html__('Easing: Sine Out', 'snn'),
'ease:sine.inOut' => esc_html__('Easing: Sine InOut', 'snn'),

// Expo
'ease:expo.in' => esc_html__('Easing: Expo In', 'snn'),
'ease:expo.out' => esc_html__('Easing: Expo Out', 'snn'),
'ease:expo.inOut' => esc_html__('Easing: Expo InOut', 'snn'),

// Circ
'ease:circ.in' => esc_html__('Easing: Circ In', 'snn'),
'ease:circ.out' => esc_html__('Easing: Circ Out', 'snn'),
'ease:circ.inOut' => esc_html__('Easing: Circ InOut', 'snn'),

// Back (overshoots, elastic)
'ease:back.in(1.7)' => esc_html__('Easing: Back In', 'snn'),
'ease:back.out(1.7)' => esc_html__('Easing: Back Out', 'snn'),
'ease:back.inOut(1.7)' => esc_html__('Easing: Back InOut', 'snn'),

// Elastic (springy bounce)
'ease:elastic.out(1, 0.3)' => esc_html__('Easing: Elastic Out', 'snn'),
'ease:elastic.in(1, 0.3)' => esc_html__('Easing: Elastic In', 'snn'),
'ease:elastic.inOut(1, 0.3)' => esc_html__('Easing: Elastic InOut', 'snn'),

// Bounce (cartoon bounce)
'ease:bounce.in' => esc_html__('Easing: Bounce In', 'snn'),
'ease:bounce.out' => esc_html__('Easing: Bounce Out', 'snn'),
'ease:bounce.inOut' => esc_html__('Easing: Bounce InOut', 'snn'),







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
