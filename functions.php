<?php 






// Add a new menu item under the Settings page
function snn_add_submenu_page() {
    add_submenu_page(
        'themes.php',
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
        <p>Add your SNN settings content here.</p>
    </div>
    <?php
}














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

