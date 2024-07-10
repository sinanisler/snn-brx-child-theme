<?php 

// Function to limit the number of post revisions
function custom_revisions_limit($num, $post) {
    return 100;
  }
  add_filter('wp_revisions_to_keep', 'custom_revisions_limit', 10, 2);
  
  // Function to add the revisions limit setting field
  function snn_revisions_limit_setting_field() {
    add_settings_field(
      'snn_revisions_limit',
      'Limit Post Revisions',
      'snn_revisions_limit_callback',
      'snn-settings',
      'snn_general_section'
    );
  }
  add_action('admin_init', 'snn_revisions_limit_setting_field');
  
  // Callback function for the revisions limit setting field
  function snn_revisions_limit_callback() {
    $options = get_option('snn_settings');
    ?>
    <input type="number" name="snn_settings[revisions_limit]" value="<?php echo isset($options['revisions_limit']) ? esc_attr($options['revisions_limit']) : 100; ?>" min="0">
    <p>Set the maximum number of revisions to keep for each post. Default is 100.</p>
    <?php
  }
  
  // Function to save the revisions limit setting
  function snn_save_revisions_limit($num, $post) {
    $options = get_option('snn_settings');
    if (isset($options['revisions_limit'])) {
      return intval($options['revisions_limit']);
    }
    return $num;
  }
  add_filter('wp_revisions_to_keep', 'snn_save_revisions_limit', 10, 2);
  