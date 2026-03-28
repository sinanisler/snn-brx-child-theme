<?php 







// WP-Admin Backend Custom JS and CSS in <head>
function snn_custom_css_utils() { ?>
    <style>
    #toplevel_page_snn-settings li a{
        line-height:1.1 !important;
    }
    #toplevel_page_snn-settings li a:hover{
        font-weight:700
    }


    </style>


    <?php if (isset($_GET['bricks'])) { ?>
        <style>
        [data-control-key="position_start_horizontal"],
        [data-control-key="position_start_vertical"] {
            float:left !important;
            padding:10px;
        }
        </style>
    <?php }?>

<?php }
add_action('admin_head', 'snn_custom_css_utils');





// Add custom CSS to the footer based on query parameter for BRICKS EDITOR
function snn_custom_css_utils_wp_footer() {
    if (isset($_GET['bricks'])) { ?>
    <style>
        [data-controlkey="gsap_animations"] .repeater-item-inner,
        [data-controlkey="gsap_animations"] .repeater-item-inner {
            float: left !important;
            width: 50%;
        }
        [data-controlkey="gsap_animations"] .control-inline{
            gap:2px !important
        }
        [data-controlkey="gsap_animations"] .bricks-sortable-item .control{
            margin-left:8px !important;
            margin-right:8px !important;
        }
        [data-controlkey="gsap_animations"] .sortable-title{
            padding-left:8px !important;
        }

		[data-controlkey="snn_data_animate_enable_desktop"],
		[data-controlkey="snn_data_animate_enable_tablet"],
		[data-controlkey="snn_data_animate_enable_mobile"]
		{
			width:33%;
			float:left;
		}

        [data-control=color] .bricks-control-popup .color-palette{ grid-template-columns: repeat(auto-fit,minmax(36px,36px)) ; }
        .bricks-control-popup .color-palette-grid{ grid-template-columns: repeat(auto-fit, minmax(36px, calc(10% - 4px))) !important; }
        [data-control=color] .bricks-control-popup .color-palette-grid li.active div:before {
            left: 0px !important;    transform: none !important;    width: 100% !important;    right: auto !important;}
    </style>
    <?php
    }
}

// Hook the function to the WordPress 'wp_footer' action
add_action('wp_footer', 'snn_custom_css_utils_wp_footer');














// Enable JSON lottie file uploads in the Media Library
function allow_json_upload($mimes) {
    // Add .json to the list of allowed mime types
    $mimes['json'] = 'application/json';
    return $mimes;
}
add_filter('upload_mimes', 'allow_json_upload');

// Fix MIME type check for JSON uploads (for security and compatibility)
function check_json_filetype($data, $file, $filename, $mimes) {
    // Get the file extension
    $filetype = wp_check_filetype($filename, $mimes);
 
    // If the extension is JSON, update the type and ext
    if ($filetype['ext'] === 'json') {
        $data['ext'] = 'json';
        $data['type'] = 'application/json';
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'check_json_filetype', 10, 4);

// Allow JSON files to bypass the upload restriction in WordPress
function allow_unfiltered_json_upload($file) {
    // Check for JSON file type
    if ($file['type'] === 'application/json') {
        // No error for JSON files
        $file['error'] = false;
    }
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'allow_unfiltered_json_upload');

// Display JSON files properly in the Media Library (optional)
function enable_json_preview_in_media_library($response, $attachment, $meta) {
    // Ensure the file is a JSON file
    if ($response['mime'] === 'application/json') {
        // Provide a basic preview message for JSON files
        $response['uploaded_filename'] = basename(get_attached_file($attachment->ID));
        $response['url'] = wp_get_attachment_url($attachment->ID);
    }
    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'enable_json_preview_in_media_library', 10, 3);














/* ---------------------------------------------------------------------
 * MULTI-ROLE CHECKBOXES ON USER PROFILE SCREENS
 * Put the whole block in mu-plugin or your theme’s functions.php
 * ------------------------------------------------------------------ */

/**
 * Add multi-role check-boxes to the profile form.
 */
function myroles_show_role_checkboxes( $user ) {
	// Only admins/super-admins may touch roles.
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}

	$all_roles  = get_editable_roles();
	$user_roles = (array) $user->roles;

	// Nonce for save handler
	wp_nonce_field( 'myroles_save_roles', 'myroles_nonce' );   ?>
	<h2><?php esc_html_e( 'User Roles (multiple allowed)', 'snn' ); ?></h2>

	<table class="form-table" role="presentation">
		<tr>
			<th><label for="user_roles[]"><?php esc_html_e( 'Assign Roles', 'snn' ); ?></label></th>
			<td>
				<?php foreach ( $all_roles as $slug => $details ) : ?>
					<label style="display:inline-block;margin-right:15px;">
						<input type="checkbox"
							   name="user_roles[]"
							   value="<?php echo esc_attr( $slug ); ?>"
							   <?php checked( in_array( $slug, $user_roles, true ) ); ?> />
						<?php echo esc_html( $details['name'] ); ?>
					</label>
				<?php endforeach; ?>

				<p class="description" style="margin-top:8px;color:#c00;font-weight:600;">
					<?php esc_html_e(
						'⚠ WARNING – You can assign multiple roles, but overlapping capabilities can clash. Grant the minimum required and test thoroughly.',
						'snn'
					); ?>
				</p>
			</td>
		</tr>
	</table>
<?php }
add_action( 'show_user_profile', 'myroles_show_role_checkboxes', 20 );
add_action( 'edit_user_profile', 'myroles_show_role_checkboxes', 20 );

/**
 * Save the chosen roles.
 */
function myroles_save_role_checkboxes( $user_id ) {

	// Capability & nonce checks.
	if ( ! current_user_can( 'administrator' ) ||
	     ! isset( $_POST['myroles_nonce'] ) ||
	     ! wp_verify_nonce( $_POST['myroles_nonce'], 'myroles_save_roles' ) ) {
		return;
	}

	$posted_roles = isset( $_POST['user_roles'] )
		? array_map( 'sanitize_key', (array) $_POST['user_roles'] )
		: array();

	if ( empty( $posted_roles ) ) {
		$posted_roles[] = 'subscriber';         // Always leave the user with at least one role.
	}

	$user = get_userdata( $user_id );

	// Wipe existing roles, then add the selected ones.
	foreach ( (array) $user->roles as $role ) {
		$user->remove_role( $role );
	}
	foreach ( $posted_roles as $role ) {
		$user->add_role( $role );
	}

	/*
	 * Optional: keep WP core’s hidden “Primary role” <select>
	 * consistent by forcing its value to the first chosen role.
	 */
	if ( ! empty( $posted_roles ) && ! isset( $_POST['role'] ) ) {
		$_POST['role'] = reset( $posted_roles );
	}
}
// Runs LAST, after core finishes updating the user.
add_action( 'profile_update',  'myroles_save_role_checkboxes', 99, 2 );
// Handle brand-new users (Users › Add New).
add_action( 'user_register',   'myroles_save_role_checkboxes', 99, 1 );

/**
 * Hide the default single-role selector so admins see only check-boxes.
 */
function myroles_hide_default_role_dropdown() {
	if ( isset( $_GET['user_id'] ) || isset( $_GET['profile'] ) ) : ?>
		<style>
			select#role, tr.user-role-wrap th, tr.user-role-wrap td { display:none !important; }
		</style>
	<?php endif;
}
add_action( 'admin_head', 'myroles_hide_default_role_dropdown' );













add_action('admin_enqueue_scripts', function() {
    $theme_uri = get_stylesheet_directory_uri();
    wp_enqueue_style(
        'snn-rich-text-editor',
        $theme_uri . '/assets/css/snn-rich-text-editor.css',
        [],
        '1.0'
    );
    wp_enqueue_script(
        'snn-rich-text-editor',
        $theme_uri . '/assets/js/snn-rich-text-editor.js',
        [],
        '1.0',
        true
    );
    // Pass AJAX URL and nonce as array!
    wp_localize_script(
        'snn-rich-text-editor',
        'snnRichTextEditorVars',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('snn_comment_media_upload')
        ]
    );
});
