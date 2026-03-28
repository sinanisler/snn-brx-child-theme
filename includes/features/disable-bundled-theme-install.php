<?php 

function snn_disable_bundled_theme_install() {
$options = get_option('snn_security_options'); 
if (isset($options['disable_bundled_theme_install'])) { 
define('CORE_UPGRADE_SKIP_NEW_BUNDLED', true); 
} 
} 
add_action('init', 'snn_disable_bundled_theme_install');

function snn_disable_bundled_theme_install_setting_field() {
add_settings_field( 
'disable_bundled_theme_install', 
__('Disable Bundled Theme Install', 'snn'), 
'snn_disable_bundled_theme_install_callback', 
'snn-security', 
'snn_security_main_section' 
); 
} 
add_action('admin_init', 'snn_disable_bundled_theme_install_setting_field');

function snn_disable_bundled_theme_install_callback() {
$options = get_option('snn_security_options'); 
?> 
<input type="checkbox" name="snn_security_options[disable_bundled_theme_install]" value="1" <?php checked(isset($options['disable_bundled_theme_install']), 1); ?>>
<p><?php esc_html_e('Enabling this setting will disable bundled theme install when upgrading WordPress.', 'snn'); ?></p>
<?php 
} 
