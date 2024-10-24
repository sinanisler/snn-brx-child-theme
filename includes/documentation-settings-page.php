<?php

// Add the Documentation Submenu
function snn_add_documentation_submenu() {
add_submenu_page(
'snn-settings', // Parent slug (main menu slug)
'Documentation', // Page title
'Documentation', // Submenu title
'manage_options', // Capability
'snn-documentation', // Submenu slug
'snn_documentation_page_callback', // Function to display the page
6
);
}
add_action('admin_menu', 'snn_add_documentation_submenu' , 99);

// Callback function for the Documentation page content
function snn_documentation_page_callback() {
?>



<div class="wrap" style="max-width:800px; line-height:30px; font-size:18px">


<h2>Site Dynamic Data Tags</h2>
<p>This tags are used usualy outside of the custom loops.</p>

<b>{current_author_id}</b>
<p>This is used in the author.php archive page. I added this tag for using in the conditions. With this we can create advanced current author loops or current author related profile editing page. Without current author id check we can not create profile editing for current author so we need this tag.</p>

<b>{post_count:post_type_name}</b><br>
<b>{post_count:post_type_name:taxonomy_name:term_slug}</b>
<p>With this tag we can get post count for specific post types or specific post types with taxonomies.</p>

<b>{current_user_first_name}</b>
<p>Get current user first_name or get user_login name as default Adds a new tag 'current_user_first_name' to the Bricks Builder dynamic tags list.</p>


<b>{current_user_fields:name} </b><br>
<b>{current_user_fields:firstname} </b><br>
<b>{current_user_fields:lastname} </b><br>
<b>{current_user_fields:email} </b><br>
<b>{current_user_fields:customfieldname} </b>
<p>It fetches and displays various fields of the current user like name, first name, last name, email, and custom fields.</p>




<br>


<h2>Loop or Post Data Tags</h2>

<b>{taxonomy_term_slug:category}</b>
<p>Use {taxonomy_term_slug:category} to insert slugs of categories assigned to the post. Adds a new dynamic tag 'taxonomy_term_slug' to the Bricks Builder tags list.</p>

<b>{taxonomy_color_tag:category}</b>
<p>Taxonomy "color" custom field The tag can be used with any taxonomy, e.g., {taxonomy_color_tag:category} or {taxonomy_color_tag:custom_taxonomy_name}, to fetch the color. Adds the new tag 'taxonomy_color_tag' to the Bricks Builder dynamic tags list with dynamic term support.</p>

<b>{estimated_post_read_time}</b>
<p>Adds a new dynamic tag 'estimated_post_read_time' to Bricks Builder for displaying estimated post read time as estimated minutes.</p>




<br>


<h2>SNN GSAP data-animate Features</h2>



<pre style="font-size:15px">
x: Horizontal position (e.g., x: 100).
y: Vertical position (e.g., y: -50).
o: Opacity (e.g., o: 0.5).
r: Rotation angle (e.g., r: 45). Supports rotation on specified axis (use axis to define the axis).
s: Scale (e.g., s: 0.8).
start: Scroll trigger start position (e.g., start: top 20%).
end: Scroll trigger end position (e.g., end: bottom 80%).
scrub: Scrubbing behavior for smoother animations (e.g., scrub: true).
pin: Pin element during scroll (e.g., pin: true).
markers: Display scroll trigger markers for debugging (e.g., markers: true).
toggleClass: Toggle a CSS class when the animation is triggered (e.g., toggleClass: active).
pinSpacing: Adjust spacing behavior when an element is pinned (e.g., pinSpacing: margin).
splittext: Split text into individual characters for staggered animations (e.g., splittext: true).
stagger: Delay between animations for individual characters when splittext is enabled (e.g., stagger: 0.05).
axis: Rotation axis when rotating elements (e.g., axis: X). Default is Z.
invalidateOnRefresh: Ensures scroll positions are recalculated on page reload or resizing (always enabled).
immediateRender: Prevents animation from rendering before the user scrolls (always set to false).
100ms Load Delay: Adds a short delay (100ms) before initializing animations to ensure proper layout rendering on page load.

You can use the style_start and style_end properties to animate various CSS properties
</pre>

<br>
<p>This example will animate the element by fading it in from the left. The element will start with an x-offset of -50 pixels and an opacity of 0.</p>
<textarea class="tt1" style="width:100%"><h1 data-animate="x:-50, o:0, start:top 80%, end:bottom 20%">Welcome to my website!</h1></textarea>


<br><br>
<p>In this example, the div element will scale up from 0.5 to 1 and rotate by 180 degrees. The animation will start when the element is 60% from the top of the viewport and end when it reaches 40% from the bottom.</p>
<textarea class="tt1" style="width:100%"><div data-animate="s:0.5, r:180, start:top 60%, end:bottom 40%, scrub:true">Lorem ipsum dolor sit amet.</div></textarea>



<br><br>
<p>style_start and style_end</p>
<textarea class="tt1" style="width:100%"><div data-animate="style_start-color:red, style_end-color:blue"></div></textarea><br>
<textarea class="tt1" style="width:100%"><div data-animate="style_start-padding:10px, style_end-padding:50px"></div></textarea><br>
<textarea class="tt1" style="width:100%"><div data-animate="style_start-background-color:white, style_end-background-color:black"></div></textarea><br>
<textarea class="tt1" style="width:100%"><div data-animate="style_start-scale:0.5, style_end-scale:1"></div></textarea><br>
<textarea class="tt1" style="width:100%"><div data-animate="style_start-rotateY:180deg, style_end-rotateY:0deg"></div></textarea>



<br><br>
<p>Full example for each available feature.</p>

<textarea class="tt1" style="width:100%"><div data-animate="x: 100,y: -50, o: 0.5, r: 45, s: 0.8, axis: X, start: top 20%, end: bottom 80%, scrub: true, pin: true, markers: true, toggleClass: active, pinSpacing: margin, splittext: true, stagger: 0.05"></div></textarea>






</div>











<?php
}















