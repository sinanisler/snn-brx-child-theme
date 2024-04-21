<?php 












// Add a new menu item under the Settings page
function snn_add_submenu_page() {
  add_submenu_page(
      'options-general.php',
      'SNN Settings',
      'SNN Settings',
      'manage_options',
      'snn-settings',
      'snn_settings_page_callback'
  );
}
add_action('admin_menu', 'snn_add_submenu_page');

// Callback function for the SNN Settings page
function snn_settings_page_callback() {
  ?>
  <div class="wrap">
      <h1>SNN Bricks Builder Child Theme Settings</h1>
      <form method="post" action="options.php">
          <?php
          settings_fields('snn_settings_group');
          do_settings_sections('snn-settings');
          submit_button();
          ?>
      </form>
  </div>
  <?php
}

// Register settings and add settings sections and fields
function snn_register_settings() {
  register_setting('snn_settings_group', 'snn_settings');

  add_settings_section(
      'snn_general_section',
      'Enable or Disable Settings Depending on Your Project Needs and Requirements',
      'snn_general_section_callback',
      'snn-settings'
  );

  add_settings_field(
      'snn_remove_wp_version',
      'Remove WP Version',
      'snn_remove_wp_version_callback',
      'snn-settings',
      'snn_general_section'
  );
  add_settings_field(
      'snn_remove_wp_version_description',
      '',
      'snn_remove_wp_version_description_callback',
      'snn-settings',
      'snn_general_section'
  );

  add_settings_field(
      'snn_disable_xmlrpc',
      'Disable XML-RPC',
      'snn_disable_xmlrpc_callback',
      'snn-settings',
      'snn_general_section'
  );
  add_settings_field(
      'snn_disable_xmlrpc_description',
      '',
      'snn_disable_xmlrpc_description_callback',
      'snn-settings',
      'snn_general_section'
  );

  add_settings_field(
      'snn_remove_rss',
      'Remove RSS',
      'snn_remove_rss_callback',
      'snn-settings',
      'snn_general_section'
  );
  add_settings_field(
      'snn_remove_rss_description',
      '',
      'snn_remove_rss_description_callback',
      'snn-settings',
      'snn_general_section'
  );

  add_settings_field(
      'snn_login_error_message',
      'Login Error Message',
      'snn_login_error_message_callback',
      'snn-settings',
      'snn_general_section'
  );
  add_settings_field(
      'snn_login_error_message_description',
      '',
      'snn_login_error_message_description_callback',
      'snn-settings',
      'snn_general_section'
  );

  add_settings_field(
      'snn_auto_update_bricks',
      'Auto Update Bricks Theme',
      'snn_auto_update_bricks_callback',
      'snn-settings',
      'snn_general_section'
  );
  add_settings_field(
      'snn_auto_update_bricks_description',
      '',
      'snn_auto_update_bricks_description_callback',
      'snn-settings',
      'snn_general_section'
  );

  add_settings_field(
      'snn_enqueue_gsap',
      'Enable GSAP',
      'snn_enqueue_gsap_callback',
      'snn-settings',
      'snn_general_section'
  );
  add_settings_field(
      'snn_enqueue_gsap_description',
      '',
      'snn_enqueue_gsap_description_callback',
      'snn-settings',
      'snn_general_section'
  );
}
add_action('admin_init', 'snn_register_settings');

// Callback functions for settings sections and fields
function snn_general_section_callback() {
  echo ' <br><br>';
}

function snn_remove_wp_version_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[remove_wp_version]" value="1" <?php checked(isset($options['remove_wp_version']), 1); ?>>
  <?php
}
function snn_remove_wp_version_description_callback() {
  ?>
  <p>Enabling this setting will remove the WordPress version number from your website's HTML source code. This can help improve security by making it more difficult for potential attackers to determine which version of WordPress your site is running.</p>


<style>
  /*
  CUSTOM STYLES for PAGE
  */ 
.form-table td{padding:0; }
.form-table th{padding:0; }
p{padding-bottom:20px;}

.wrap{
  max-width:1000px; 
}

</style>



  <?php
}

function snn_disable_xmlrpc_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[disable_xmlrpc]" value="1" <?php checked(isset($options['disable_xmlrpc']), 1); ?>>
  <?php
}
function snn_disable_xmlrpc_description_callback() {
  ?>
  <p>Enabling this setting will disable the XML-RPC functionality in WordPress. XML-RPC is a remote procedure call protocol that allows external services to interact with your WordPress site. Disabling it can help improve security by preventing unauthorized access.</p>
  <?php
}

function snn_remove_rss_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[remove_rss]" value="1" <?php checked(isset($options['remove_rss']), 1); ?>>
  <?php
}
function snn_remove_rss_description_callback() {
  ?>
  <p>Enabling this setting will remove the RSS feed links from your website's HTML source code. If you don't plan to use RSS feeds on your site, removing these links can help reduce clutter and improve performance.</p>
  <?php
}

function snn_login_error_message_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="text" name="snn_settings[login_error_message]" value="<?php echo esc_attr($options['login_error_message'] ?? ''); ?>">
  <?php
}
function snn_login_error_message_description_callback() {
  ?>
  <p>This setting allows you to customize the error message displayed when a user enters incorrect login credentials. By default, WordPress provides a generic error message. Customizing this message can help improve security by making it more difficult for potential attackers to determine valid usernames.</p>
  <?php
}

function snn_auto_update_bricks_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[auto_update_bricks]" value="1" <?php checked(isset($options['auto_update_bricks']), 1); ?>>
  <?php
}
function snn_auto_update_bricks_description_callback() {
  ?>
  <p>Enabling this setting will automatically update the Bricks theme whenever a new version is available. Keeping your theme up to date is important for security and functionality. However, it's recommended to test updates on a staging site before applying them to your live site.</p>
  <?php
}

function snn_enqueue_gsap_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[enqueue_gsap]" value="1" <?php checked(isset($options['enqueue_gsap']), 1); ?>>
  <?php
}
function snn_enqueue_gsap_description_callback() {
  ?>
  <h2>GSAP</h2>
  <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website. GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.</p>
  <ul>
    <li><code>gsap.min.js</code>: The core GSAP library.</li>
    <li><code>ScrollTrigger.min.js</code>: A GSAP plugin that enables scroll-based animations.</li>
    <li><code>gsap-data-animate.js</code>: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.</li>
  </ul>

  <style>
  .tt1{width:880px; height:60px}
  </style>


  <br><br>
  <p>This example will animate the  element by fading it in from the left. The element will start with an x-offset of -50 pixels and an opacity of 0.</p>
  <textarea class="tt1"><h1 data-animate="x:-50, o:0, start:top 80%, end:bottom 20%">Welcome to my website!</h1></textarea>


  <br><br>
  <p>In this example, the div element will scale up from 0.5 to 1 and rotate by 180 degrees. The animation will start when the element is 60% from the top of the viewport and end when it reaches 40% from the bottom.</p>
  <textarea class="tt1"><div data-animate="s:0.5, r:180, start:top 60%, end:bottom 40%, scrub:true">Lorem ipsum dolor sit amet.</div></textarea>


  <br><br>
  <p>This example will pin the section element in place while scrolling. The animation will start when the top of the element reaches the top of the viewport and end when the bottom of the element reaches the top of the viewport. </p>
  <textarea class="tt1"> <section data-animate="pin:true, toggleClass:active, start:top top, end:bottom top, pinSpacing:false">
  <h2>About Us</h2>
  <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
</section> </textarea>



<br><br>

<p>This example will animate the element by fading it in from the left. The element will start with an x-offset of -100 pixels and an opacity of 0.</p> <textarea class="tt1"><div data-animate="x:-100, o:0, start:top 80%, end:bottom 20%">Fade in from left</div></textarea> <p>This example will animate the element by fading it in from the bottom. The element will start with a y-offset of 50 pixels and an opacity of 0.</p> <textarea class="tt1"><div data-animate="y:50, o:0, start:top 70%, end:bottom 30%">Fade in from bottom</div></textarea> <p>This example will animate the element by rotating it 360 degrees.</p> <textarea class="tt1"><div data-animate="r:360, start:top 60%, end:bottom 40%">Rotate 360 degrees</div></textarea> <p>This example will animate the element by scaling it from 0.5 to 1.</p> <textarea class="tt1"><div data-animate="s:0.5, start:top 75%, end:bottom 25%">Scale from 0.5 to 1</div></textarea> <p>This example demonstrates using custom start and end positions for the animation. The element will fade in from the left with an x-offset of -50 pixels and an opacity of 0.</p> <textarea class="tt1"><div data-animate="x:-50, o:0, start:top 90%, end:bottom 10%">Custom start and end</div></textarea> <p>This example showcases a scrubbing animation. The element will animate based on the scroll position, starting with a y-offset of 100 pixels and an opacity of 0.</p> <textarea class="tt1"><div data-animate="y:100, o:0, scrub:true, start:top 80%, end:bottom 20%">Scrub animation</div></textarea> <p>This example demonstrates pinning an element during the animation. The section will be pinned in place while scrolling.</p> <textarea class="tt1"> <section data-animate="pin:true, start:top top, end:bottom top"> <h2>Pinned Section</h2> <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p> </section> </textarea> <p>This example shows how to add markers for debugging purposes. The markers will be visible to help visualize the start and end positions of the animation.</p> <textarea class="tt1"><div data-animate="x:50, o:0, markers:true, start:top 70%, end:bottom 30%">Markers for debugging</div></textarea> <p>This example demonstrates toggling a class during the animation. The element will animate from a y-offset of -50 pixels and an opacity of 0, and the "active" class will be toggled.</p> <textarea class="tt1"><div data-animate="y:-50, o:0, toggleClass:active, start:top 60%, end:bottom 40%">Toggle class</div></textarea> <p>This example showcases custom pin spacing. The section will be pinned without any additional spacing.</p> <textarea class="tt1"> <section data-animate="pin:true, pinSpacing:false, start:top top, end:bottom top"> <h2>Custom Pin Spacing</h2> <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p> </section> </textarea> <p>This example demonstrates a staggered letter animation. Each letter of the text will animate individually with a staggered effect, starting with a y-offset of 50 pixels and an opacity of 0.</p> <textarea class="tt1"><h1 data-animate="y:50, o:0, start:top 80%, end:bottom 20%, stagger:0.1">Staggered Text Animation</h1></textarea>









<br><br>

x and y for x-offset and y-offset animations<br>
o for opacity animation<br>
r for rotation animation<br>
s for scale animation<br>
start and end for defining custom start and end positions<br>
scrub for enabling scrubbing animation based on scroll position<br>
pin for pinning the element during animation<br>
markers for adding markers for debugging purposes<br>
toggleClass for toggling a class during animation<br>
pinSpacing for customizing the pin spacing behavior<br>
stagger for creating staggered animations on child elements<br>




  <?php
}

// Functions to handle the settings
function remove_wp_version() {
  $options = get_option('snn_settings');
  if (isset($options['remove_wp_version'])) {
      return '';
  }
}
add_filter('the_generator', 'remove_wp_version');

function disable_xmlrpc($enabled) {
  $options = get_option('snn_settings');
  if (isset($options['disable_xmlrpc'])) {
      return false;
  }
  return $enabled;
}
add_filter('xmlrpc_enabled', 'disable_xmlrpc');

function remove_rss() {
  $options = get_option('snn_settings');
  if (isset($options['remove_rss'])) {
      remove_action('wp_head', 'rsd_link');
      remove_action('wp_head', 'feed_links', 2);
      remove_action('wp_head', 'feed_links_extra', 3);
      remove_action('wp_head', 'wlwmanifest_link');
  }
}
add_action('init', 'remove_rss');

function failed_login_message() {
  $options = get_option('snn_settings');
  $message = $options['login_error_message'] ?? 'Incorrect, please try again';
  return $message;
}
add_filter('login_errors', 'failed_login_message');

// Auto Update Bricks Theme
function auto_update_bricks_theme($update, $item) {
  $options = get_option('snn_settings');
  if (isset($options['auto_update_bricks']) && $item->theme == 'bricks') {
      return true;
  }
  return $update;
}
add_filter('auto_update_theme', 'auto_update_bricks_theme', 10, 2);

// Enqueue GSAP scripts
function enqueue_gsap_scripts() {
  $options = get_option('snn_settings');
  if (isset($options['enqueue_gsap'])) {
      wp_enqueue_script('gsap-js', get_stylesheet_directory_uri() . '/js/gsap.min.js', array(), false, true);
      wp_enqueue_script('gsap-st-js', get_stylesheet_directory_uri() . '/js/ScrollTrigger.min.js', array('gsap-js'), false, true);
      wp_enqueue_script('gsap-data-js', get_stylesheet_directory_uri() . '/js/gsap-data-animate.js?v0.01', array(), false, true);
  }
}
add_action('wp_enqueue_scripts', 'enqueue_gsap_scripts');




















// Move Bricks Menu to End of the wp-admin menu list
function custom_menu_order($menu_ord) {
  if (!$menu_ord) return true;
  // Identify the index of 'bricks'
  foreach ($menu_ord as $index => $item) {
      if ($item == 'bricks') {
          $bricks_menu = $item;
          unset($menu_ord[$index]);
          break;
      }
  }
  // Append 'bricks' to the end
  if (isset($bricks_menu)) {
      $menu_ord[] = $bricks_menu;
  }
  return $menu_ord;
}
add_filter('menu_order', 'custom_menu_order');
add_filter('custom_menu_order', function(){ return true; }); // Activate custom_menu_order









/**
 * Register/enqueue custom scripts and styles
 */
add_action( 'wp_enqueue_scripts', function() {
	// Enqueue your files on the canvas & frontend, not the builder panel. Otherwise custom CSS might affect builder)
	if ( ! bricks_is_builder_main() ) {
		wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime( get_stylesheet_directory() . '/style.css' ) );
	}
} );

/**
 * Register custom elements
 */
add_action( 'init', function() {
  $element_files = [
    __DIR__ . '/elements/title.php',
  ];

  foreach ( $element_files as $file ) {
    \Bricks\Elements::register_element( $file );
  }
}, 11 );

/**
 * Add text strings to builder
 */
add_filter( 'bricks/builder/i18n', function( $i18n ) {
  // For element category 'custom'
  $i18n['custom'] = esc_html__( 'Custom', 'bricks' );

  return $i18n;
} );

