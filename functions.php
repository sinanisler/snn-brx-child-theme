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
      <h1>SNN Settings</h1>
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
      'General Settings',
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
      'snn_disable_xmlrpc',
      'Disable XML-RPC',
      'snn_disable_xmlrpc_callback',
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
      'snn_login_error_message',
      'Login Error Message',
      'snn_login_error_message_callback',
      'snn-settings',
      'snn_general_section'
  );
}
add_action('admin_init', 'snn_register_settings');

// Callback functions for settings sections and fields
function snn_general_section_callback() {
  echo '<b>SNN</b> Bricks Builder Child Theme Settings';
}

function snn_remove_wp_version_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[remove_wp_version]" value="1" <?php checked(isset($options['remove_wp_version']), 1); ?>>
  <?php
}

function snn_disable_xmlrpc_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[disable_xmlrpc]" value="1" <?php checked(isset($options['disable_xmlrpc']), 1); ?>>
  <?php
}

function snn_remove_rss_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="checkbox" name="snn_settings[remove_rss]" value="1" <?php checked(isset($options['remove_rss']), 1); ?>>
  <?php
}

function snn_login_error_message_callback() {
  $options = get_option('snn_settings');
  ?>
  <input type="text" name="snn_settings[login_error_message]" value="<?php echo esc_attr($options['login_error_message'] ?? ''); ?>">
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








// enqueue GSAP script in WordPress
// wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
function theme_gsap_script(){
    // The core GSAP library
    wp_enqueue_script( 'gsap-js', 'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js', array(), false, true );
    // ScrollTrigger - with gsap.js passed as a dependency
    wp_enqueue_script( 'gsap-st', 'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js', array('gsap-js'), false, true );
    // Your animation code file - with gsap.js passed as a dependency
    wp_enqueue_script( 'gsap-js2', get_template_directory_uri() . 'js/app.js', array('gsap-js'), false, true );
}

add_action( 'wp_enqueue_scripts', 'theme_gsap_script' );
































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

