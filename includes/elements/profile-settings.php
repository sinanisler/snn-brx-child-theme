<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Bricks\Element;

/**
 * Profile Settings Element
 * Allows users to edit their own profile with multi-layer security checks
 */
class SNN_Element_Profile_Settings extends Element {
	public $category     = 'snn';
	public $name         = 'profile-settings';
	public $icon         = 'ti-user';
	public $css_selector = '.snn-profile-settings';
	public $nestable     = false;

	public function get_label() {
		return esc_html__( 'Profile Settings', 'snn' );
	}

	public function set_controls() {

		/* ---------- CONTENT TAB - Profile Fields (Repeater for ordering) ------------------------------------------------ */

		$this->controls['profile_fields'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Profile Fields (Drag to Reorder)', 'snn' ),
			'type'          => 'repeater',
			'titleProperty' => 'field_type',
			'fields'        => [
				'field_type' => [
					'label'   => esc_html__( 'Field Type', 'snn' ),
					'type'    => 'select',
					'options' => [
						'avatar'      => esc_html__( 'Avatar Upload', 'snn' ),
						'first_name'  => esc_html__( 'First Name', 'snn' ),
						'last_name'   => esc_html__( 'Last Name', 'snn' ),
						'email'       => esc_html__( 'Email', 'snn' ),
						'description' => esc_html__( 'Description/Bio', 'snn' ),
						'password'    => esc_html__( 'Password Change', 'snn' ),
						'website'     => esc_html__( 'Website URL', 'snn' ),
					],
					'default' => 'avatar',
					'inline'  => true,
				],
				'enabled' => [
					'label'   => esc_html__( 'Enable', 'snn' ),
					'type'    => 'checkbox',
					'default' => true,
					'inline'  => true,
				],
			],
			'default' => [
				[ 'field_type' => 'avatar', 'enabled' => true ],
				[ 'field_type' => 'first_name', 'enabled' => true ],
				[ 'field_type' => 'last_name', 'enabled' => true ],
				[ 'field_type' => 'email', 'enabled' => true ],
				[ 'field_type' => 'description', 'enabled' => true ],
				[ 'field_type' => 'password', 'enabled' => true ],
				[ 'field_type' => 'website', 'enabled' => false ],
			],
		];

		/* ---------- Custom Meta Fields Repeater ------------------------------------------------ */

		$this->controls['custom_fields'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Custom Meta Fields', 'snn' ),
			'type'          => 'repeater',
			'titleProperty' => 'field_label',
			'fields'        => [
				'field_label' => [
					'label'       => esc_html__( 'Field Label', 'snn' ),
					'type'        => 'text',
					'default'     => esc_html__( 'Custom Field', 'snn' ),
					'inline'      => true,
				],
				'field_key' => [
					'label'       => esc_html__( 'Meta Key', 'snn' ),
					'type'        => 'text',
					'placeholder' => 'custom_field_key',
					'description' => esc_html__( 'Use unique key (e.g., twitter_handle, phone_number)', 'snn' ),
					'inline'      => true,
				],
				'field_type' => [
					'label'   => esc_html__( 'Field Type', 'snn' ),
					'type'    => 'select',
					'options' => [
						'text'     => esc_html__( 'Text', 'snn' ),
						'textarea' => esc_html__( 'Textarea', 'snn' ),
						'url'      => esc_html__( 'URL', 'snn' ),
						'email'    => esc_html__( 'Email', 'snn' ),
						'number'   => esc_html__( 'Number', 'snn' ),
						'checkbox' => esc_html__( 'Checkbox (Boolean)', 'snn' ),
					],
					'default' => 'text',
					'inline'  => true,
				],
				'field_placeholder' => [
					'label'  => esc_html__( 'Placeholder', 'snn' ),
					'type'   => 'text',
					'inline' => true,
				],
			],
		];

		/* ---------- STYLE TAB - Labels & Buttons ------------------------------------------------ */

		$this->controls['submit_button_text'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Submit Button Text', 'snn' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Update Profile', 'snn' ),
			'placeholder' => esc_html__( 'Update Profile', 'snn' ),
			'inline'      => true,
		];

		$this->controls['success_message'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Success Message', 'snn' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Profile updated successfully!', 'snn' ),
			'inline'      => true,
		];

		$this->controls['label_typography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Label Typography', 'snn' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'label',
				],
			],
		];

		$this->controls['input_background'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Input Background', 'snn' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'input[type="text"], input[type="email"], input[type="password"], input[type="url"], input[type="number"], textarea',
				],
			],
		];

		$this->controls['input_border'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Input Border', 'snn' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'input[type="text"], input[type="email"], input[type="password"], input[type="url"], input[type="number"], textarea',
				],
			],
		];

		$this->controls['button_typography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Button Typography', 'snn' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.snn-profile-submit',
				],
			],
		];

		$this->controls['button_background'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Button Background', 'snn' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.snn-profile-submit',
				],
			],
		];
	}

	public function render() {

		/* === MULTI-LAYER SECURITY CHECK #1: User must be logged in === */
		/* Output absolutely nothing for non-logged-in users */
		if ( ! is_user_logged_in() ) {
			return;
		}

		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;

		/* === MULTI-LAYER SECURITY CHECK #2: Verify current user ID is valid === */
		/* Output absolutely nothing for invalid user sessions */
		if ( ! $current_user_id || $current_user_id < 1 ) {
			return;
		}

		/* === Get Settings === */
		$profile_fields     = isset( $this->settings['profile_fields'] ) ? $this->settings['profile_fields'] : [];
		$custom_fields      = isset( $this->settings['custom_fields'] ) ? $this->settings['custom_fields'] : [];
		$submit_text        = $this->settings['submit_button_text'] ?? esc_html__( 'Update Profile', 'snn' );
		$success_message    = $this->settings['success_message'] ?? esc_html__( 'Profile updated successfully!', 'snn' );

		/* === Create field type lookup for easy checking === */
		$enabled_fields = [];
		foreach ( $profile_fields as $field ) {
			if ( ! empty( $field['enabled'] ) && ! empty( $field['field_type'] ) ) {
				$enabled_fields[] = $field['field_type'];
			}
		}

		/* === Get current user data === */
		$user_data = get_userdata( $current_user_id );
		$first_name = get_user_meta( $current_user_id, 'first_name', true );
		$last_name = get_user_meta( $current_user_id, 'last_name', true );
		$description = get_user_meta( $current_user_id, 'description', true );
		$user_email = $user_data->user_email;
		$user_website = $user_data->user_url;

		/* === Create unique nonce for this user === */
		$nonce_action = 'snn_profile_update_' . $current_user_id;
		$nonce_field = wp_nonce_field( $nonce_action, 'snn_profile_nonce', true, false );

		/* === Set wrapper attributes === */
		$this->set_attribute( '_root', 'class', 'snn-profile-settings-wrapper' );

		echo '<div ' . $this->render_attributes( '_root' ) . '>';
		?>
		<style>
		/* Base form styles */
		.snn-profile-settings-wrapper{width:100%; margin:0 auto}
		.snn-profile-form{display:flex;flex-direction:column;gap:10px}
		.snn-profile-field{display:flex;flex-direction:column;}
		.snn-profile-field label{font-weight:600;font-size:14px; line-height:1}
		.snn-profile-field input[type="text"],
		.snn-profile-field input[type="email"],
		.snn-profile-field input[type="password"],
		.snn-profile-field input[type="url"],
		.snn-profile-field input[type="number"],
		.snn-profile-field textarea{
			padding:10px 15px;
			border:1px solid #ddd;
			border-radius:4px;
			font-size:14px;
			width:100%;
			box-sizing:border-box;
		}
		.snn-profile-field textarea{min-height:120px;resize:vertical}
		.snn-profile-field input:focus,
		.snn-profile-field textarea:focus{outline:2px solid #0073aa;outline-offset:2px}
		.snn-profile-submit{
			padding:10px 30px;
			background:#0073aa;
			color:#fff;
			border:none;
			border-radius:4px;
			font-size:16px;
			font-weight:600;
			cursor:pointer;
			transition:background 0.2s;
			align-self:flex-start;
		}
		.snn-profile-submit:hover{background:#005a87}
		.snn-profile-submit:disabled{background:#ccc;cursor:not-allowed}
		.snn-profile-message{padding:15px;margin-top:20px;border-radius:4px}
		.snn-profile-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
		.snn-profile-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
		.snn-profile-avatar-preview{margin-top:10px}
		.snn-profile-avatar-preview img{max-width:150px;height:auto;border-radius:50%;border:3px solid #ddd}
		.snn-profile-password-hint{color:#666;margin-top:5px}
		.snn-profile-avatar-hint{color:#666;font-size:12px;margin-top:5px}
		.snn-profile-field-group{background:#f9f9f9;padding:15px;border-radius:4px;margin-top:10px; gap: 10px;display: flex;flex-direction: column;}
		.snn-profile-field-group-title{font-weight:600;font-size:16px;margin-bottom:15px;color:#333}
		.snn-profile-field input[type="checkbox"]{width:auto;margin-right:8px;cursor:pointer}
		.snn-profile-field label:has(input[type="checkbox"]){flex-direction:row;align-items:center;cursor:pointer}
		</style>

		<form class="snn-profile-form" id="snn-profile-form" method="post" enctype="multipart/form-data">
			
			<?php echo $nonce_field; ?>
			<input type="hidden" name="snn_profile_action" value="update_profile">
			<input type="hidden" name="snn_current_user_id" value="<?php echo esc_attr( $current_user_id ); ?>">

			<?php
			/* === Render fields in the order specified by repeater === */
			foreach ( $profile_fields as $field ) :
				if ( empty( $field['enabled'] ) || empty( $field['field_type'] ) ) continue;
				
				$field_type = $field['field_type'];
				
				switch ( $field_type ) :
					case 'avatar': ?>
						<div class="snn-profile-field">
							<label for="snn-avatar"><?php esc_html_e( 'Profile Avatar', 'snn' ); ?></label>
							<input type="file" id="snn-avatar" name="snn_avatar" accept="image/*">
							<div class="snn-profile-avatar-hint"><?php esc_html_e( 'Max file size: 5MB. Allowed formats: JPG, PNG, GIF, WebP.', 'snn' ); ?></div>
							<div class="snn-profile-avatar-preview">
								<?php echo get_avatar( $current_user_id, 150 ); ?>
							</div>
						</div>
						<?php break;
					
					case 'first_name': ?>
						<div class="snn-profile-field">
							<label for="snn-first-name"><?php esc_html_e( 'First Name', 'snn' ); ?></label>
							<input type="text" id="snn-first-name" name="snn_first_name" value="<?php echo esc_attr( $first_name ); ?>">
						</div>
						<?php break;
					
					case 'last_name': ?>
						<div class="snn-profile-field">
							<label for="snn-last-name"><?php esc_html_e( 'Last Name', 'snn' ); ?></label>
							<input type="text" id="snn-last-name" name="snn_last_name" value="<?php echo esc_attr( $last_name ); ?>">
						</div>
						<?php break;
					
					case 'email': ?>
						<div class="snn-profile-field">
							<label for="snn-email"><?php esc_html_e( 'Email Address', 'snn' ); ?></label>
							<input type="email" id="snn-email" name="snn_email" value="<?php echo esc_attr( $user_email ); ?>" required>
						</div>
						<?php break;
					
					case 'website': ?>
						<div class="snn-profile-field">
							<label for="snn-website"><?php esc_html_e( 'Website URL', 'snn' ); ?></label>
							<input type="url" id="snn-website" name="snn_website" value="<?php echo esc_url( $user_website ); ?>" placeholder="https://example.com">
						</div>
						<?php break;
					
					case 'description': ?>
						<div class="snn-profile-field">
							<label for="snn-description"><?php esc_html_e( 'Bio / Description', 'snn' ); ?></label>
							<textarea id="snn-description" name="snn_description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
						</div>
						<?php break;
					
					case 'password': ?>
						<div class="snn-profile-field-group">
							<div class="snn-profile-field-group-title"><?php esc_html_e( 'Change Password', 'snn' ); ?></div>
							
							<div class="snn-profile-field">
								<label for="snn-password-new"><?php esc_html_e( 'New Password', 'snn' ); ?></label>
								<input type="password" id="snn-password-new" name="snn_password_new" autocomplete="new-password">
								<div class="snn-profile-password-hint"><?php esc_html_e( 'Leave blank to keep current password', 'snn' ); ?></div>
							</div>

							<div class="snn-profile-field">
								<label for="snn-password-confirm"><?php esc_html_e( 'Confirm New Password', 'snn' ); ?></label>
								<input type="password" id="snn-password-confirm" name="snn_password_confirm" autocomplete="new-password">
							</div>
						</div>
						<?php break;
					
				endswitch;
			endforeach;
			?>

			<?php if ( ! empty( $custom_fields ) ) : ?>
			<div class="snn-profile-field-group">
				<div class="snn-profile-field-group-title"><?php esc_html_e( 'Additional Information', 'snn' ); ?></div>
				
				<?php foreach ( $custom_fields as $field ) :
					if ( empty( $field['field_key'] ) ) continue;
					
					$field_key = sanitize_key( $field['field_key'] );
					$field_label = isset( $field['field_label'] ) ? esc_html( $field['field_label'] ) : $field_key;
					$field_type = isset( $field['field_type'] ) ? $field['field_type'] : 'text';
					$field_placeholder = isset( $field['field_placeholder'] ) ? esc_attr( $field['field_placeholder'] ) : '';
					$field_value = get_user_meta( $current_user_id, $field_key, true );
				?>
				<div class="snn-profile-field">
					<?php if ( $field_type === 'checkbox' ) : ?>
						<input type="hidden" name="snn_checkbox_fields[]" value="<?php echo esc_attr( $field_key ); ?>">
						<label for="snn-custom-<?php echo esc_attr( $field_key ); ?>">
							<input
								type="checkbox"
								id="snn-custom-<?php echo esc_attr( $field_key ); ?>"
								name="snn_custom_fields[<?php echo esc_attr( $field_key ); ?>]"
								value="1"
								<?php checked( $field_value, '1' ); ?>
							>
							<?php echo $field_label; ?>
						</label>
					<?php else : ?>
						<label for="snn-custom-<?php echo esc_attr( $field_key ); ?>"><?php echo $field_label; ?></label>
						<?php if ( $field_type === 'textarea' ) : ?>
							<textarea
								id="snn-custom-<?php echo esc_attr( $field_key ); ?>"
								name="snn_custom_fields[<?php echo esc_attr( $field_key ); ?>]"
								placeholder="<?php echo $field_placeholder; ?>"
								rows="4"
							><?php echo esc_textarea( $field_value ); ?></textarea>
						<?php else : ?>
							<input
								type="<?php echo esc_attr( $field_type ); ?>"
								id="snn-custom-<?php echo esc_attr( $field_key ); ?>"
								name="snn_custom_fields[<?php echo esc_attr( $field_key ); ?>]"
								value="<?php echo esc_attr( $field_value ); ?>"
								placeholder="<?php echo $field_placeholder; ?>"
							>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<button type="submit" class="snn-profile-submit"><?php echo esc_html( $submit_text ); ?></button>

			<!-- Message container -->
			<div id="snn-profile-message" style="display:none;"></div>
		</form>

		<script>
		(function() {
			const form = document.getElementById('snn-profile-form');
			const messageDiv = document.getElementById('snn-profile-message');
			const submitBtn = form.querySelector('.snn-profile-submit');

			form.addEventListener('submit', function(e) {
				e.preventDefault();
				
				// Validate passwords if changing
				const newPass = document.getElementById('snn-password-new');
				const confirmPass = document.getElementById('snn-password-confirm');
				
				if (newPass && confirmPass) {
					if (newPass.value && newPass.value !== confirmPass.value) {
						showMessage('<?php esc_html_e( 'Passwords do not match!', 'snn' ); ?>', 'error');
						return;
					}
				}

				// Disable submit button
				submitBtn.disabled = true;
				submitBtn.textContent = '<?php esc_html_e( 'Updating...', 'snn' ); ?>';

				// Prepare form data
				const formData = new FormData(form);
				formData.append('action', 'snn_update_profile');

				// Send AJAX request
				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				})
				.then(response => response.json())
				.then(data => {
					submitBtn.disabled = false;
					submitBtn.textContent = '<?php echo esc_js( $submit_text ); ?>';

					if (data.success) {
						showMessage(data.data.message || '<?php echo esc_js( $success_message ); ?>', 'success');
						
						// Update avatar preview if changed
						if (data.data.avatar_url) {
							const avatarPreview = document.querySelector('.snn-profile-avatar-preview img');
							if (avatarPreview) {
								avatarPreview.src = data.data.avatar_url;
							}
						}

						// Clear password fields
						if (newPass) newPass.value = '';
						if (confirmPass) confirmPass.value = '';
					} else {
						showMessage(data.data.message || '<?php esc_html_e( 'An error occurred. Please try again.', 'snn' ); ?>', 'error');
					}
				})
				.catch(error => {
					submitBtn.disabled = false;
					submitBtn.textContent = '<?php echo esc_js( $submit_text ); ?>';
					showMessage('<?php esc_html_e( 'Network error. Please try again.', 'snn' ); ?>', 'error');
				});
			});

			function showMessage(message, type) {
				messageDiv.textContent = message;
				messageDiv.className = 'snn-profile-message snn-profile-' + type;
				messageDiv.style.display = 'block';
				messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

				// Auto-hide success messages after 5 seconds
				if (type === 'success') {
					setTimeout(() => {
						messageDiv.style.display = 'none';
					}, 5000);
				}
			}
		})();
		</script>

		<?php
		echo '</div>';
	}
}

/* ========================================================================
   AJAX HANDLER - Update Profile with Multi-Layer Security
   ======================================================================== */

if ( ! function_exists( 'snn_ajax_update_profile' ) ) {
	function snn_ajax_update_profile() {
		
		/* === SECURITY CHECK #1: User must be logged in === */
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message' => esc_html__( 'You must be logged in to update your profile.', 'snn' )
			] );
		}

		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;

		/* === SECURITY CHECK #2: Validate user ID from form matches current user === */
		$submitted_user_id = isset( $_POST['snn_current_user_id'] ) ? intval( $_POST['snn_current_user_id'] ) : 0;
		if ( $submitted_user_id !== $current_user_id ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Security check failed: User ID mismatch.', 'snn' )
			] );
		}

		/* === SECURITY CHECK #3: Verify nonce === */
		$nonce_action = 'snn_profile_update_' . $current_user_id;
		if ( ! isset( $_POST['snn_profile_nonce'] ) || ! wp_verify_nonce( $_POST['snn_profile_nonce'], $nonce_action ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Security check failed: Invalid nonce.', 'snn' )
			] );
		}

		/* === SECURITY CHECK #4: Verify action === */
		if ( ! isset( $_POST['snn_profile_action'] ) || $_POST['snn_profile_action'] !== 'update_profile' ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Invalid action.', 'snn' )
			] );
		}

		/* === SECURITY CHECK #5: Rate limiting (prevent spam) === */
		$rate_limit_key = 'snn_profile_update_' . $current_user_id;
		$last_update = get_transient( $rate_limit_key );
		if ( $last_update ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Please wait a moment before updating again.', 'snn' )
			] );
		}
		// Set transient for 10 seconds
		set_transient( $rate_limit_key, time(), 5 );

		$response_data = [];

		/* === Update First Name === */
		if ( isset( $_POST['snn_first_name'] ) ) {
			$first_name = sanitize_text_field( $_POST['snn_first_name'] );
			update_user_meta( $current_user_id, 'first_name', $first_name );
		}

		/* === Update Last Name === */
		if ( isset( $_POST['snn_last_name'] ) ) {
			$last_name = sanitize_text_field( $_POST['snn_last_name'] );
			update_user_meta( $current_user_id, 'last_name', $last_name );
		}

		/* === Update Email === */
		if ( isset( $_POST['snn_email'] ) ) {
			$new_email = sanitize_email( $_POST['snn_email'] );
			
			// Validate email
			if ( ! is_email( $new_email ) ) {
				wp_send_json_error( [
					'message' => esc_html__( 'Invalid email address.', 'snn' )
				] );
			}

			// Check if email is already in use by another user
			if ( email_exists( $new_email ) && email_exists( $new_email ) !== $current_user_id ) {
				wp_send_json_error( [
					'message' => esc_html__( 'This email is already in use.', 'snn' )
				] );
			}

			// Update email
			wp_update_user( [
				'ID'         => $current_user_id,
				'user_email' => $new_email
			] );
		}

		/* === Update Website URL === */
		if ( isset( $_POST['snn_website'] ) ) {
			$website = esc_url_raw( $_POST['snn_website'] );
			wp_update_user( [
				'ID'       => $current_user_id,
				'user_url' => $website
			] );
		}

		/* === Update Description === */
		if ( isset( $_POST['snn_description'] ) ) {
			$description = sanitize_textarea_field( $_POST['snn_description'] );
			update_user_meta( $current_user_id, 'description', $description );
		}

		/* === Update Password === */
		if ( isset( $_POST['snn_password_new'] ) && ! empty( $_POST['snn_password_new'] ) ) {
			$new_password = $_POST['snn_password_new'];
			$confirm_password = isset( $_POST['snn_password_confirm'] ) ? $_POST['snn_password_confirm'] : '';

			// Validate passwords match
			if ( $new_password !== $confirm_password ) {
				wp_send_json_error( [
					'message' => esc_html__( 'Passwords do not match.', 'snn' )
				] );
			}

			// Validate password strength (minimum 8 characters)
			if ( strlen( $new_password ) < 8 ) {
				wp_send_json_error( [
					'message' => esc_html__( 'Password must be at least 8 characters long.', 'snn' )
				] );
			}

			// Update password
			wp_set_password( $new_password, $current_user_id );
		}

		/* === Handle Avatar Upload === */
		if ( isset( $_FILES['snn_avatar'] ) && ! empty( $_FILES['snn_avatar']['name'] ) ) {
			
			// Validate file upload
			if ( $_FILES['snn_avatar']['error'] !== UPLOAD_ERR_OK ) {
				wp_send_json_error( [
					'message' => esc_html__( 'Avatar upload failed.', 'snn' )
				] );
			}

			// Validate file type
			$allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
			$file_type = wp_check_filetype( $_FILES['snn_avatar']['name'] );
			
			if ( ! in_array( $_FILES['snn_avatar']['type'], $allowed_types ) ) {
				wp_send_json_error( [
					'message' => esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'snn' )
				] );
			}

			// Validate file size (max 5MB)
			if ( $_FILES['snn_avatar']['size'] > 5242880 ) {
				wp_send_json_error( [
					'message' => esc_html__( 'File size too large. Maximum 5MB allowed.', 'snn' )
				] );
			}

			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Upload file
			$attachment_id = media_handle_upload( 'snn_avatar', 0 );

			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( [
					'message' => esc_html__( 'Avatar upload failed: ', 'snn' ) . $attachment_id->get_error_message()
				] );
			}

			// Save avatar as user meta
			update_user_meta( $current_user_id, 'snn_profile_avatar', $attachment_id );
			
			// Get avatar URL for response
			$response_data['avatar_url'] = wp_get_attachment_url( $attachment_id );
		}

		/* === Update Custom Meta Fields === */
		if ( isset( $_POST['snn_custom_fields'] ) && is_array( $_POST['snn_custom_fields'] ) ) {
			foreach ( $_POST['snn_custom_fields'] as $key => $value ) {
				$sanitized_key = sanitize_key( $key );

				// Sanitize based on expected type
				if ( $value === '1' ) {
					// Checkbox field (boolean true)
					$sanitized_value = '1';
				} elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
					$sanitized_value = esc_url_raw( $value );
				} elseif ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
					$sanitized_value = sanitize_email( $value );
				} elseif ( is_numeric( $value ) ) {
					$sanitized_value = floatval( $value );
				} else {
					$sanitized_value = sanitize_text_field( $value );
				}

				update_user_meta( $current_user_id, $sanitized_key, $sanitized_value );
			}
		}

		/* === Handle unchecked checkboxes (they don't get submitted in form) === */
		if ( isset( $_POST['snn_checkbox_fields'] ) && is_array( $_POST['snn_checkbox_fields'] ) ) {
			foreach ( $_POST['snn_checkbox_fields'] as $checkbox_key ) {
				$sanitized_key = sanitize_key( $checkbox_key );
				// If checkbox wasn't in snn_custom_fields, it means it was unchecked
				if ( ! isset( $_POST['snn_custom_fields'][ $checkbox_key ] ) ) {
					update_user_meta( $current_user_id, $sanitized_key, '0' );
				}
			}
		}

		/* === Success Response === */
		$response_data['message'] = esc_html__( 'Profile updated successfully!', 'snn' );
		wp_send_json_success( $response_data );
	}

	add_action( 'wp_ajax_snn_update_profile', 'snn_ajax_update_profile' );
}

/* ========================================================================
   Custom Avatar Display (use uploaded avatar if exists)
   ======================================================================== */

if ( ! function_exists( 'snn_custom_avatar' ) ) {
	function snn_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		$user_id = false;

		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_object( $id_or_email ) ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				$user_id = (int) $id_or_email->user_id;
			}
		} else {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		if ( $user_id ) {
			$custom_avatar_id = get_user_meta( $user_id, 'snn_profile_avatar', true );
			if ( $custom_avatar_id ) {
				$custom_avatar = wp_get_attachment_image_src( $custom_avatar_id, [ $size, $size ] );
				if ( $custom_avatar ) {
					$avatar = '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $custom_avatar[0] ) . '" class="avatar avatar-' . esc_attr( $size ) . ' photo" height="' . esc_attr( $size ) . '" width="' . esc_attr( $size ) . '" />';
				}
			}
		}

		return $avatar;
	}
	add_filter( 'get_avatar', 'snn_custom_avatar', 10, 5 );
}
