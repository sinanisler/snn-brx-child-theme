<?php
/**
 * MCP settings: master switch + Read / Write toggle per ability group.
 *
 * Stored as one option, snn_mcp_settings:
 *   [ 'enabled' => bool, 'groups' => [ group => [ 'read' => bool, 'write' => bool ] ],
 *     'debug' => bool, 'debug_payloads' => bool ]
 *
 * Reads default on, writes default off: an MCP client acts as a real user, and
 * a write means it changes the site without anyone in the admin watching.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function snn_mcp_get_settings() {
    static $cache = null;
    if ( $cache !== null ) {
        return $cache;
    }
    $saved    = get_option( 'snn_mcp_settings', array() );
    $saved    = is_array( $saved ) ? $saved : array();
    $settings = array(
        'enabled'        => array_key_exists( 'enabled', $saved ) ? (bool) $saved['enabled'] : true,
        'groups'         => array(),
        'debug'          => ! empty( $saved['debug'] ),
        'debug_payloads' => ! empty( $saved['debug_payloads'] ),
    );
    foreach ( array_keys( snn_mcp_ability_groups() ) as $group ) {
        $row = isset( $saved['groups'][ $group ] ) && is_array( $saved['groups'][ $group ] ) ? $saved['groups'][ $group ] : array();
        $settings['groups'][ $group ] = array(
            'read'  => array_key_exists( 'read', $row ) ? (bool) $row['read'] : true,
            'write' => array_key_exists( 'write', $row ) ? (bool) $row['write'] : false,
        );
    }
    $cache = $settings;
    return $cache;
}

function snn_mcp_is_enabled() {
    return (bool) snn_mcp_get_settings()['enabled'];
}

/** @param string $kind 'read' or 'write'. A write also needs read: a client must be able to look first. */
function snn_mcp_group_allows( $group, $kind ) {
    $row = snn_mcp_get_settings()['groups'][ $group ] ?? null;
    if ( ! $row || empty( $row['read'] ) ) {
        return false;
    }
    return $kind === 'read' ? true : ! empty( $row['write'] );
}

function snn_mcp_sanitize_settings( $input ) {
    $input = is_array( $input ) ? $input : array();
    $out   = array(
        'enabled'        => ! empty( $input['enabled'] ),
        'groups'         => array(),
        'debug'          => ! empty( $input['debug'] ),
        'debug_payloads' => ! empty( $input['debug_payloads'] ),
    );
    foreach ( array_keys( snn_mcp_ability_groups() ) as $group ) {
        $out['groups'][ $group ] = array(
            'read'  => ! empty( $input['groups'][ $group ]['read'] ),
            'write' => ! empty( $input['groups'][ $group ]['write'] ),
        );
    }
    return $out;
}

add_action( 'admin_init', 'snn_mcp_register_settings' );
function snn_mcp_register_settings() {
    register_setting( 'snn_mcp_settings_group', 'snn_mcp_settings', array(
        'type'              => 'array',
        'sanitize_callback' => 'snn_mcp_sanitize_settings',
        'default'           => array(),
    ) );
}

add_action( 'admin_menu', 'snn_mcp_add_settings_submenu', 20 );
function snn_mcp_add_settings_submenu() {
    add_submenu_page(
        'snn-settings',
        __( 'MCP', 'snn' ),
        __( 'MCP', 'snn' ),
        'manage_options',
        'snn-mcp-settings',
        'snn_mcp_render_settings'
    );
}

function snn_mcp_render_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $settings = snn_mcp_get_settings();
    $groups   = snn_mcp_ability_groups();
    $endpoint = snn_mcp_endpoint_url();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'MCP (Model Context Protocol)', 'snn' ); ?></h1>

        <?php if ( snn_mcp_adapter_active() && $settings['enabled'] ) : ?>
            <div class="notice notice-info inline"><p>
                <?php esc_html_e( 'The MCP Adapter plugin is also active. This built-in server takes over its default endpoint, so these abilities do not need the plugin. Deactivate it unless another plugin uses it.', 'snn' ); ?>
            </p></div>
        <?php endif; ?>

        <p><?php esc_html_e( 'Create one token per AI assistant below. Its link carries the token, so the assistant needs nothing else. Every ability still runs its own permission checks for the user who created the token.', 'snn' ); ?></p>
        <?php if ( $settings['enabled'] ) : ?>
            <p><strong><?php esc_html_e( 'Endpoint:', 'snn' ); ?></strong> <code><?php echo esc_html( $endpoint ); ?></code></p>
            <p class="description"><?php echo esc_html( sprintf( __( 'Connectors set up with the older URL %s keep working.', 'snn' ), snn_mcp_legacy_endpoint_url() ) ); ?></p>
        <?php else : ?>
            <p><?php esc_html_e( 'The built-in MCP server is off, so no endpoint is served. Turn it on below to connect clients.', 'snn' ); ?></p>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'snn_mcp_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Built-in MCP server', 'snn' ); ?></th>
                    <td><label>
                        <input type="checkbox" name="snn_mcp_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
                        <?php esc_html_e( 'Run the MCP server. The groups below choose which abilities it exposes. Off means no endpoint at all.', 'snn' ); ?>
                    </label></td>
                </tr>
            </table>

            <table class="widefat striped" style="max-width:820px">
                <thead><tr>
                    <th><?php esc_html_e( 'Group', 'snn' ); ?></th>
                    <th style="width:90px"><?php esc_html_e( 'Read', 'snn' ); ?></th>
                    <th style="width:90px"><?php esc_html_e( 'Write', 'snn' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $groups as $key => $group ) : ?>
                    <tr>
                        <td><?php echo esc_html( $group['label'] ); ?></td>
                        <td><input type="checkbox" name="snn_mcp_settings[groups][<?php echo esc_attr( $key ); ?>][read]" value="1" <?php checked( $settings['groups'][ $key ]['read'] ); ?> /></td>
                        <td><input type="checkbox" name="snn_mcp_settings[groups][<?php echo esc_attr( $key ); ?>][write]" value="1" <?php checked( $settings['groups'][ $key ]['write'] ); ?> /></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e( 'Write needs Read. Writes are off by default. Every MCP call is recorded in the Activity Log (type "MCP ability") when the Activity Log is on.', 'snn' ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Debug log', 'snn' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="snn_mcp_settings[debug]" value="1" <?php checked( $settings['debug'] ); ?> />
                            <?php esc_html_e( 'Log every MCP request, tool call, step, timing, error and PHP fatal error', 'snn' ); ?></label><br>
                        <label><input type="checkbox" name="snn_mcp_settings[debug_payloads]" value="1" <?php checked( $settings['debug_payloads'] ); ?> />
                            <?php esc_html_e( 'Log full payloads (HTML/CSS the client sent and received, up to 200 KB per value)', 'snn' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Passwords, tokens and base64 file data are never logged. Turn this off when you are done: payloads can contain page content.', 'snn' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <?php snn_mcp_render_tokens(); ?>

        <?php snn_mcp_render_zip_upload(); ?>

        <?php
        if ( $settings['debug'] ) {
            snn_mcp_debug_render_panel();
        }
        ?>
    </div>
    <?php
}

/**
 * Tokens: one row per AI assistant, a form to add one, and the links for the
 * token just created. The token is shown once, from a short-lived transient.
 */
function snn_mcp_render_tokens() {
    $notice_key = snn_mcp_token_notice_key();
    $new        = get_transient( $notice_key );
    if ( $new ) {
        delete_transient( $notice_key );
    }
    $tokens   = snn_mcp_tokens_get();
    $endpoint = snn_mcp_endpoint_url();
    ?>
    <h2><?php esc_html_e( 'AI assistants', 'snn' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Give each assistant its own link, so you can see and cut off each one separately. Revoking a token stops that assistant at once.', 'snn' ); ?></p>

    <?php if ( ! empty( $_GET['snn_mcp_revoked'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
        <div class="notice notice-success inline"><p><?php esc_html_e( 'Token revoked.', 'snn' ); ?></p></div>
    <?php endif; ?>

    <?php if ( $new ) : ?>
        <?php $url = snn_mcp_token_url( $new['token'] ); ?>
        <div class="notice notice-success inline" style="padding-bottom:12px">
            <p><strong><?php echo esc_html( sprintf( __( 'Token for %s created. Copy its link now: the token is not shown again.', 'snn' ), $new['name'] ) ); ?></strong></p>
            <?php snn_mcp_render_copy_box( __( 'claude.ai, Claude desktop app, Cowork: Settings > Connectors > Add custom connector, paste this address', 'snn' ), $url ); ?>
            <?php snn_mcp_render_copy_box( __( 'Claude Code: run this in a terminal', 'snn' ), 'claude mcp add --transport http ' . ( sanitize_title( $new['name'] ) ?: 'snn-site' ) . ' "' . $url . '"' ); ?>
            <?php snn_mcp_render_copy_box( __( 'Any other MCP client: server address', 'snn' ), $endpoint ); ?>
            <?php snn_mcp_render_copy_box( __( 'Any other MCP client: header', 'snn' ), 'Authorization: Bearer ' . $new['token'] ); ?>
        </div>
    <?php endif; ?>

    <table class="widefat striped" style="max-width:820px">
        <thead><tr>
            <th><?php esc_html_e( 'Name', 'snn' ); ?></th>
            <th><?php esc_html_e( 'Access', 'snn' ); ?></th>
            <th><?php esc_html_e( 'Last used', 'snn' ); ?></th>
            <th><?php esc_html_e( 'Created', 'snn' ); ?></th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php if ( empty( $tokens ) ) : ?>
            <tr><td colspan="5"><?php esc_html_e( 'No tokens yet. Add one for each AI below.', 'snn' ); ?></td></tr>
        <?php endif; ?>
        <?php foreach ( $tokens as $id => $record ) : ?>
            <tr>
                <td><strong><?php echo esc_html( $record['name'] ); ?></strong></td>
                <td><?php echo 'write' === $record['access'] ? esc_html__( 'Read and write', 'snn' ) : esc_html__( 'Read only', 'snn' ); ?></td>
                <td><?php echo $record['last_used'] ? esc_html( sprintf( __( '%s ago', 'snn' ), human_time_diff( (int) $record['last_used'] ) ) ) : esc_html__( 'Never', 'snn' ); ?></td>
                <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $record['created'] ) ); ?></td>
                <td><a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=snn_mcp_revoke_token&id=' . rawurlencode( (string) $id ) ), 'snn_mcp_revoke_token_' . $id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Revoke this token? That assistant loses access at once.', 'snn' ) ); ?>');"><?php esc_html_e( 'Revoke', 'snn' ); ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3><?php esc_html_e( 'New token', 'snn' ); ?></h3>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="snn_mcp_create_token" />
        <?php wp_nonce_field( 'snn_mcp_create_token' ); ?>
        <label><?php esc_html_e( 'Name', 'snn' ); ?>
            <input type="text" name="name" maxlength="60" required placeholder="<?php esc_attr_e( 'e.g. Claude, Sinan', 'snn' ); ?>" />
        </label>
        <label><?php esc_html_e( 'Access', 'snn' ); ?>
            <select name="access">
                <option value="read"><?php esc_html_e( 'Read only: look, scan, report', 'snn' ); ?></option>
                <option value="write"><?php esc_html_e( 'Read and write', 'snn' ); ?></option>
            </select>
        </label>
        <?php submit_button( __( 'Create token', 'snn' ), 'secondary', 'submit', false ); ?>
    </form>
    <p class="description"><?php esc_html_e( 'Read and write still respects the groups above: a group whose Write is off stays read only for every token.', 'snn' ); ?></p>
    <p class="description"><?php esc_html_e( 'Start each assistant with the tool snn-bricks-start-here: it returns the HTML rules and the recommended order of calls for building Bricks pages.', 'snn' ); ?></p>
    <?php
}

/** A labelled, read-only text field with a Copy button. */
function snn_mcp_render_copy_box( $label, $text ) {
    ?>
    <p style="margin:12px 0 4px"><strong><?php echo esc_html( $label ); ?></strong></p>
    <div style="display:flex;gap:8px;max-width:820px">
        <input type="text" readonly class="large-text code" style="flex:1" value="<?php echo esc_attr( $text ); ?>" onclick="this.select()" />
        <button type="button" class="button" onclick="this.previousElementSibling.select(); navigator.clipboard && navigator.clipboard.writeText(this.previousElementSibling.value);"><?php esc_html_e( 'Copy', 'snn' ); ?></button>
    </div>
    <?php
}

/**
 * Design-zip upload for MCP clients that cannot send files (claude.ai and
 * other hosted chats): the zip goes to the media library from the browser,
 * and the client gets only its attachment ID.
 */
function snn_mcp_render_zip_upload() {
    $uploaded = isset( $_GET['snn_zip'] ) ? absint( $_GET['snn_zip'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
    $error    = isset( $_GET['snn_zip_error'] ) ? sanitize_text_field( wp_unslash( $_GET['snn_zip_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    ?>
    <h2><?php esc_html_e( 'Design zip for MCP', 'snn' ); ?></h2>
    <p><?php esc_html_e( 'Hosted AI chats cannot send a file to your site. Upload the design-export .zip here (max 50 MB), then give the AI the attachment ID. The tool snn-bricks-import-zip reads it on the server.', 'snn' ); ?></p>
    <?php if ( $uploaded && get_post_type( $uploaded ) === 'attachment' ) : ?>
        <div class="notice notice-success inline"><p>
            <?php echo esc_html( sprintf( __( 'Uploaded %1$s as attachment %2$d. Tell the AI:', 'snn' ), basename( (string) get_attached_file( $uploaded ) ), $uploaded ) ); ?>
            <code><?php echo esc_html( sprintf( 'Import the design zip attachment_id %d into page <ID or title> with snn-bricks-import-zip.', $uploaded ) ); ?></code>
        </p></div>
    <?php elseif ( $error ) : ?>
        <div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="snn_mcp_upload_zip" />
        <?php wp_nonce_field( 'snn_mcp_upload_zip' ); ?>
        <input type="file" name="snn_design_zip" accept=".zip,application/zip" required />
        <?php submit_button( __( 'Upload zip', 'snn' ), 'secondary', 'submit', false ); ?>
    </form>
    <?php
}

add_action( 'admin_post_snn_mcp_upload_zip', 'snn_mcp_handle_zip_upload' );
function snn_mcp_handle_zip_upload() {
    if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Not allowed.', 'snn' ) );
    }
    check_admin_referer( 'snn_mcp_upload_zip' );
    $back = admin_url( 'admin.php?page=snn-mcp-settings' );
    $file = $_FILES['snn_design_zip'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
    if ( ! $file || ! empty( $file['error'] ) || ! preg_match( '/\.zip$/i', (string) $file['name'] ) ) {
        wp_safe_redirect( add_query_arg( 'snn_zip_error', rawurlencode( __( 'Choose a .zip file. If it is large, the server upload limit may be lower than the file: ', 'snn' ) . ini_get( 'upload_max_filesize' ) ), $back ) );
        exit;
    }
    if ( (int) $file['size'] > 52428800 ) { // SNN_Bricks_Zip_Import::LIMITS['zipBytes']
        wp_safe_redirect( add_query_arg( 'snn_zip_error', rawurlencode( __( 'The zip is larger than 50 MB.', 'snn' ) ), $back ) );
        exit;
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $id = media_handle_upload( 'snn_design_zip', 0, array(), array(
        'test_form' => false,
        'mimes'     => array( 'zip' => 'application/zip' ),
    ) );
    if ( is_wp_error( $id ) ) {
        wp_safe_redirect( add_query_arg( 'snn_zip_error', rawurlencode( $id->get_error_message() ), $back ) );
        exit;
    }
    wp_safe_redirect( add_query_arg( 'snn_zip', (int) $id, $back ) );
    exit;
}
