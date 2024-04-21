<?php












// Add a new menu item under the Settings page
function snn_add_submenu_page()
{
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
function snn_settings_page_callback()
{
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
function snn_register_settings()
{
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
function snn_general_section_callback()
{
  echo ' <br><br>';
}

function snn_remove_wp_version_callback()
{
  $options = get_option('snn_settings');
?>
  <input type="checkbox" name="snn_settings[remove_wp_version]" value="1" <?php checked(isset($options['remove_wp_version']), 1); ?>>
<?php
}
function snn_remove_wp_version_description_callback()
{
?>
  <p>Enabling this setting will remove the WordPress version number from your website's HTML source code. This can help improve security by making it more difficult for potential attackers to determine which version of WordPress your site is running.</p>


  <style>
    /*
  CUSTOM STYLES for PAGE
  */
    .form-table td {
      padding: 0;
    }

    .form-table th {
      padding: 0;
    }

    p {
      padding-bottom: 20px;
    }

    .wrap {
      max-width: 1000px;
    }
  </style>



<?php
}

function snn_disable_xmlrpc_callback()
{
  $options = get_option('snn_settings');
?>
  <input type="checkbox" name="snn_settings[disable_xmlrpc]" value="1" <?php checked(isset($options['disable_xmlrpc']), 1); ?>>
<?php
}
function snn_disable_xmlrpc_description_callback()
{
?>
  <p>Enabling this setting will disable the XML-RPC functionality in WordPress. XML-RPC is a remote procedure call protocol that allows external services to interact with your WordPress site. Disabling it can help improve security by preventing unauthorized access.</p>
<?php
}

function snn_remove_rss_callback()
{
  $options = get_option('snn_settings');
?>
  <input type="checkbox" name="snn_settings[remove_rss]" value="1" <?php checked(isset($options['remove_rss']), 1); ?>>
<?php
}
function snn_remove_rss_description_callback()
{
?>
  <p>Enabling this setting will remove the RSS feed links from your website's HTML source code. If you don't plan to use RSS feeds on your site, removing these links can help reduce clutter and improve performance.</p>
<?php
}

function snn_login_error_message_callback()
{
  $options = get_option('snn_settings');
?>
  <input type="text" name="snn_settings[login_error_message]" value="<?php echo esc_attr($options['login_error_message'] ?? ''); ?>">
<?php
}
function snn_login_error_message_description_callback()
{
?>
  <p>This setting allows you to customize the error message displayed when a user enters incorrect login credentials. By default, WordPress provides a generic error message. Customizing this message can help improve security by making it more difficult for potential attackers to determine valid usernames.</p>
<?php
}

function snn_auto_update_bricks_callback()
{
  $options = get_option('snn_settings');
?>
  <input type="checkbox" name="snn_settings[auto_update_bricks]" value="1" <?php checked(isset($options['auto_update_bricks']), 1); ?>>
<?php
}
function snn_auto_update_bricks_description_callback()
{
?>
  <p>Enabling this setting will automatically update the Bricks theme whenever a new version is available. Keeping your theme up to date is important for security and functionality. However, it's recommended to test updates on a staging site before applying them to your live site.</p>
<?php
}

function snn_enqueue_gsap_callback()
{
  $options = get_option('snn_settings');
?>
  <input type="checkbox" name="snn_settings[enqueue_gsap]" value="1" <?php checked(isset($options['enqueue_gsap']), 1); ?>>
<?php
}
function snn_enqueue_gsap_description_callback()
{
?>
  <h2>GSAP</h2>
  <p>Enabling this setting will enqueue the GSAP library and its associated scripts on your website. GSAP is a powerful JavaScript animation library that allows you to create complex and interactive animations.</p>
  <ul>
    <li><code>gsap.min.js</code>: The core GSAP library.</li>
    <li><code>ScrollTrigger.min.js</code>: A GSAP plugin that enables scroll-based animations.</li>
    <li><code>gsap-data-animate.js</code>: A custom script that utilizes GSAP and ScrollTrigger for animating elements based on data attributes.</li>
  </ul>

  <style>
    .tt1 {
      width: 880px;
      height: 60px
    }
  </style>


  <br><br>
  <p>This example will animate the element by fading it in from the left. The element will start with an x-offset of -50 pixels and an opacity of 0.</p>
  <textarea class="tt1"><h1 data-animate="x:-50, o:0, start:top 80%, end:bottom 20%">Welcome to my website!</h1></textarea>


  <br><br>
  <p>In this example, the div element will scale up from 0.5 to 1 and rotate by 180 degrees. The animation will start when the element is 60% from the top of the viewport and end when it reaches 40% from the bottom.</p>
  <textarea class="tt1"><div data-animate="s:0.5, r:180, start:top 60%, end:bottom 40%, scrub:true">Lorem ipsum dolor sit amet.</div></textarea>





  <br><br>



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






  <br><br>


<?php
}

// Functions to handle the settings
function remove_wp_version()
{
  $options = get_option('snn_settings');
  if (isset($options['remove_wp_version'])) {
    return '';
  }
}
add_filter('the_generator', 'remove_wp_version');

function disable_xmlrpc($enabled)
{
  $options = get_option('snn_settings');
  if (isset($options['disable_xmlrpc'])) {
    return false;
  }
  return $enabled;
}
add_filter('xmlrpc_enabled', 'disable_xmlrpc');

function remove_rss()
{
  $options = get_option('snn_settings');
  if (isset($options['remove_rss'])) {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'wlwmanifest_link');
  }
}
add_action('init', 'remove_rss');

function failed_login_message()
{
  $options = get_option('snn_settings');
  $message = $options['login_error_message'] ?? 'Incorrect, please try again';
  return $message;
}
add_filter('login_errors', 'failed_login_message');

// Auto Update Bricks Theme
function auto_update_bricks_theme($update, $item)
{
  $options = get_option('snn_settings');
  if (isset($options['auto_update_bricks']) && $item->theme == 'bricks') {
    return true;
  }
  return $update;
}
add_filter('auto_update_theme', 'auto_update_bricks_theme', 10, 2);

// Enqueue GSAP scripts
function enqueue_gsap_scripts()
{
  $options = get_option('snn_settings');
  if (isset($options['enqueue_gsap'])) {
    wp_enqueue_script('gsap-js', get_stylesheet_directory_uri() . '/js/gsap.min.js', array(), false, true);
    wp_enqueue_script('gsap-st-js', get_stylesheet_directory_uri() . '/js/ScrollTrigger.min.js', array('gsap-js'), false, true);
    wp_enqueue_script('gsap-data-js', get_stylesheet_directory_uri() . '/js/gsap-data-animate.js?v0.01', array(), false, true);
  }
}
add_action('wp_enqueue_scripts', 'enqueue_gsap_scripts');




















// Move Bricks Menu to End of the wp-admin menu list
function custom_menu_order($menu_ord)
{
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
add_filter('custom_menu_order', function () {
  return true;
}); // Activate custom_menu_order









/**
 * Register/enqueue custom scripts and styles
 */
add_action('wp_enqueue_scripts', function () {
  // Enqueue your files on the canvas & frontend, not the builder panel. Otherwise custom CSS might affect builder)
  if (!bricks_is_builder_main()) {
    wp_enqueue_style('bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime(get_stylesheet_directory() . '/style.css'));
  }
});

/**
 * Register custom elements
 */
add_action('init', function () {
  $element_files = [
    __DIR__ . '/elements/title.php',
  ];

  foreach ($element_files as $file) {
    \Bricks\Elements::register_element($file);
  }
}, 11);

/**
 * Add text strings to builder
 */
add_filter('bricks/builder/i18n', function ($i18n) {
  // For element category 'custom'
  $i18n['custom'] = esc_html__('Custom', 'bricks');

  return $i18n;
});
