<?php
/**
 * MCP settings: master switch + Read / Write toggle per ability group.
 *
 * Stored as one option, snn_mcp_settings:
 *   [ 'enabled' => bool, 'groups' => [ group => [ 'read' => bool, 'write' => bool ] ] ]
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
        'enabled' => array_key_exists( 'enabled', $saved ) ? (bool) $saved['enabled'] : true,
        'groups'  => array(),
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
        'enabled' => ! empty( $input['enabled'] ),
        'groups'  => array(),
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

        <?php if ( ! snn_mcp_adapter_active() ) : ?>
            <div class="notice notice-warning"><p>
                <?php esc_html_e( 'The MCP Adapter plugin is not active. Install and activate "MCP Adapter" (WordPress/mcp-adapter) to expose these abilities to MCP clients.', 'snn' ); ?>
            </p></div>
        <?php endif; ?>

        <p><?php esc_html_e( 'MCP clients (Claude, Cursor, ChatGPT and others) connect to the endpoint below as a real WordPress user, using an Application Password (Users > Profile > Application Passwords). Every ability still runs its own permission checks for that user.', 'snn' ); ?></p>
        <p><strong><?php esc_html_e( 'Endpoint:', 'snn' ); ?></strong> <code><?php echo esc_html( $endpoint ); ?></code></p>

        <form method="post" action="options.php">
            <?php settings_fields( 'snn_mcp_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Expose theme abilities', 'snn' ); ?></th>
                    <td><label>
                        <input type="checkbox" name="snn_mcp_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
                        <?php esc_html_e( 'Make the enabled groups below available through MCP', 'snn' ); ?>
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

            <?php submit_button(); ?>
        </form>

        <h2><?php esc_html_e( 'Connect a client', 'snn' ); ?></h2>
        <p><?php esc_html_e( 'Claude Code (HTTP transport):', 'snn' ); ?></p>
        <pre style="background:#fff;padding:12px;border:1px solid #ccd0d4;max-width:820px;white-space:pre-wrap">claude mcp add --transport http snn-site <?php echo esc_html( $endpoint ); ?> --header "Authorization: Basic BASE64(username:application-password)"</pre>
        <p><?php esc_html_e( 'Start with the tool snn-bricks-start-here: it returns the HTML rules and the recommended order of calls for building Bricks pages.', 'snn' ); ?></p>
    </div>
    <?php
}
