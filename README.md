# SNN BRX - Bricks Builder Child Theme



## Theme Settings

SNN BRX theme adds theme settings and lots of features with it. 


### Security Features & Settings

- Remove WP Version	
- Disable XML-RPC	
- Remove RSS	
- Change Login Error Message
- Disable JSON API for Guests
- Enabling Auto Update Bricks Theme	


### Enable GSAP	

Enabling this setting will enqueue the GSAP library and its associated scripts on your website.

example data-animate usage ``` <h1 data-animate="x:-50, o:0, start:top 80%, end:bottom 20%">Welcome to my website!</h1>```






## Custom Elements

### Custom Code HTML

Custom Code HTML Element. With this you can add simple html css script codes to the page easily. It only allows html tags so it is more less secure.

I added this element because bricks builder added signature for every code for every change and it locks on the domain so when you move from staging to live it resets its signatures and your codes stop working. 

With this element I solved that problem.

Just write or paste your html code and forget about it.



## Loop Dynamic Data Tags

### {taxonomy_term_slug:category}

Use {taxonomy_term_slug:category} to insert slugs of categories assigned to the post.
Adds a new dynamic tag 'taxonomy_term_slug' to the Bricks Builder tags list.


### {taxonomy_color_tag:category}
Taxonomy "color" custom field
The tag can be used with any taxonomy, e.g., {taxonomy_color_tag:category} or  {taxonomy_color_tag:custom_taxonomy_name}, to fetch the color.
Adds the new tag 'taxonomy_color_tag' to the Bricks Builder dynamic tags list with dynamic term support.

### {estimated_post_read_time}
Adds a new dynamic tag 'estimated_post_read_time' to Bricks Builder for displaying estimated post read time as estimated minutes.


## Site Dynamic Data Tags

This tags are used usualy outside of the custom loops.

### {current_author_id}

This is used in the author.php archive page. I added this tag for using in the conditions.
With this we can create  advanced current author loops or current author related profile editing page.
Without current author id check we can not create profile editing for current author so we need this tag.


### {post_count:post_type_name}
### {post_count:post_type_name:taxonomy_name:term_slug}

With this tag we can get post count for specific post types or specific post types with taxonomies.


### {current_user_first_name}
Get current user first_name or get user_login name as default
Adds a new tag 'current_user_first_name' to the Bricks Builder dynamic tags list.






