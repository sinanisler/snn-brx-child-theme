<?php

// Add the Documentation Submenu
function snn_add_documentation_submenu() {
    add_submenu_page(
        'snn-settings', // Parent slug (main menu slug)
        'Documentation', // Page title
        'Documentation', // Submenu title
        'manage_options', // Capability
        'snn-documentation', // Submenu slug
        'snn_documentation_page_callback' // Function to display the page
    );
}
add_action('admin_menu', 'snn_add_documentation_submenu');

// Callback function for the Documentation page content
function snn_documentation_page_callback() {
    ?>



<div class="wrap" style="max-width:800px; line-height:30px; font-size:18px">
 <h1>SNN Bricks Builder Documentation</h1>
    

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













</div>











    <?php
}















