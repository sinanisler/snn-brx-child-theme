<?php


// Settings Page
require_once get_stylesheet_directory() . '/includes/settings-page.php';

// Features & Settings (each with its own settings)
require_once get_stylesheet_directory() . '/includes/remove-wp-version.php';
require_once get_stylesheet_directory() . '/includes/disable-xmlrpc.php';
require_once get_stylesheet_directory() . '/includes/disable-file-editing.php';
require_once get_stylesheet_directory() . '/includes/remove-rss.php';
require_once get_stylesheet_directory() . '/includes/login-error-message.php';
require_once get_stylesheet_directory() . '/includes/disable-wp-json-if-not-logged-in.php';
require_once get_stylesheet_directory() . '/includes/auto-update-bricks.php';
require_once get_stylesheet_directory() . '/includes/enqueue-gsap.php';
require_once get_stylesheet_directory() . '/includes/move-bricks-menu.php';
require_once get_stylesheet_directory() . '/includes/snn-custom-css-setting.php';
require_once get_stylesheet_directory() . '/includes/snn-custom-css-js-cdn.php';
require_once get_stylesheet_directory() . '/includes/wp-head-css-frontend.php';


// Include script enqueuing
require_once get_stylesheet_directory() . '/includes/enqueue-scripts.php';

// Custom Dynamic Data Tags
// https://academy.bricksbuilder.io/article/dynamic-data/
require_once get_stylesheet_directory() . '/dynamic_data_tags/custom_dynamic_data_tags.php';



// Register Custom Elements
// https://academy.bricksbuilder.io/article/create-your-own-elements/

add_action( 'init', function() {
    $element_files = [
      __DIR__ . '/custom_elements/custom-html-css-script.php', 
    ];
  
    foreach ( $element_files as $file ) {
      if (file_exists($file)) {
        require_once $file;
        $element_class = 'Custom_HTML_CSS_Script'; 
        \Bricks\Elements::register_element($file, 'custom-html-css-script', $element_class); 
      }
    }
  }, 11 );
  







// Automatic theme updates from the GitHub repository
add_filter('pre_set_site_transient_update_themes', 'automatic_GitHub_updates', 100, 1);
function automatic_GitHub_updates($data) {
  // Theme information
  $theme   = get_stylesheet(); // Folder name of the current theme
  $current = wp_get_theme()->get('Version'); // Get the version of the current theme
  // GitHub information
  $user = 'sinanisler'; // The GitHub username hosting the repository
  $repo = 'snn-brx-child-theme'; // Repository name as it appears in the URL
  // Get the latest release tag from the repository. The User-Agent header must be sent, as per
  // GitHub's API documentation: https://developer.github.com/v3/#user-agent-required
  $file = @json_decode(@file_get_contents('https://api.github.com/repos/'.$user.'/'.$repo.'/releases/latest', false,
      stream_context_create(['http' => ['header' => "User-Agent: ".$user."\r\n"]])
  ));
  if($file) {
	$update = filter_var($file->tag_name, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    // Only return a response if the new version number is higher than the current version
    if($update > $current) {
  	  $data->response[$theme] = array(
	      'theme'       => $theme,
	      // Strip the version number of any non-alpha characters (excluding the period)
	      // This way you can still use tags like v1.1 or ver1.1 if desired
	      'new_version' => $update,
	      'url'         => 'https://github.com/'.$user.'/'.$repo,
	      'package'     => $file->assets[0]->browser_download_url,
      );
    }
  }
  return $data;
}

