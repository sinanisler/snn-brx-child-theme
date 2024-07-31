# SNN BRX - Bricks Builder Child Theme

> [!NOTE]
> With version 0.46 site editor support dropped. It was creating problem with WooCommerce sites. I will come back to this later.





[![youtube](https://img.youtube.com/vi/kwKpiAVWBn8/0.jpg)](https://www.youtube.com/watch?v=kwKpiAVWBn8)


## Theme Settings

SNN BRX theme adds theme settings and lots of features with it. 


### SNN Settings & Features

- Remove/Hide WP Version	
- Disable XML-RPC	
- Disable Theme/Plugin File Editing 
- Disable Remove RSS	
- Login Error Message
- Login Math Captcha Validation
- Disable JSON API for Guests
- Enable Auto Update Theme
- Custom CSS for Bricks and Block Editor	
- Custom CSS CDN URL Loader
- Custom JavaScript CDN URL Loader


### GSAP Animation Library

Enabling this setting will enqueue the GSAP library and its associated scripts on your website.

After you enable the GSAP feature you can use this animation library with data-animate attribues with any bricks dom and any bricks element.

example data-animate usage: 

``` <h1 data-animate="x:-50, o:0, start:top 80%, end:bottom 20%">Welcome to my website!</h1>```

``` <div data-animate="s:0.5, r:180, start:top 60%, end:bottom 40%, scrub:true">Lorem ipsum dolor sit amet.</div> ```

<ul>
    <li><b>x</b>: Horizontal position (e.g., <b>x: 100</b>).</li>
    <li><b>y</b>: Vertical position (e.g., <b>y: -50</b>).</li>
    <li><b>o</b>: Opacity (e.g., <b>o: 0.5</b>).</li>
    <li><b>r</b>: Rotation angle (e.g., <b>r: 45</b>).</li>
    <li><b>s</b>: Scale (e.g., <b>s: 0.8</b>).</li>
    <li><b>start</b>: Scroll trigger start position (e.g., <b>start: top 20%</b>).</li>
    <li><b>end</b>: Scroll trigger end position (e.g., <b>end: bottom 80%</b>).</li>
    <li><b>scrub</b>: Scrubbing behavior (e.g., <b>scrub: true</b>).</li>
    <li><b>pin</b>: Pin element during scroll (e.g., <b>pin: true</b>).</li>
    <li><b>markers</b>: Display scroll trigger markers (e.g., <b>markers: true</b>).</li>
    <li><b>toggleClass</b>: Toggle CSS class (e.g., <b>toggleClass: active</b>).</li>
    <li><b>pinSpacing</b>: Spacing behavior when pinning (e.g., <b>pinSpacing: margin</b>).</li>
    <li><b>splittext</b>: Split text into characters (e.g., <b>splittext: true</b>).</li>
    <li><b>stagger</b>: Stagger delay between characters (e.g., <b>stagger: 0.05</b>).</li>
  </ul>


Tutorial:


[![youtube](https://img.youtube.com/vi/plJpgqtFpg0/0.jpg)](https://www.youtube.com/watch?v=plJpgqtFpg0)


### Move Bricks Menu to End	

Enabling this setting will move the Bricks menu item to the end of the WordPress admin menu.



### Custom CSS for Bricks and Block Editor	

Enter custom CSS for the block editor and front-end. This CSS will be applied site-wide.

Some Native CSS Libraries https://github.com/uhub/awesome-css stay away from .js required ones.

[![youtube](https://img.youtube.com/vi/kwKpiAVWBn8/0.jpg)](https://www.youtube.com/watch?v=kwKpiAVWBn8)




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






