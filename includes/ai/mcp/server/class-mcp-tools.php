<?php
/**
 * Tools of the built-in MCP server and how a tools/call runs.
 *
 * Two kinds of tools:
 *  - Named tools: one per public Bricks ability. snn/bricks-foo is listed as
 *    snn-bricks-foo with the ability's own input schema, annotations and output.
 *    Plugins can add abilities as named tools through mcp_adapter_default_server_config
 *    (Bricks lists its high-frequency abilities that way).
 *  - Meta tools: discover, inspect and run any public ability by its full name.
 *    Their names are the ones the MCP Adapter used, so connectors and saved
 *    instructions that call them keep working.
 *
 * An ability is public to MCP when its meta.mcp.public is true (see mcp-loader.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SNN_MCP_Tools {

    const DISCOVER = 'mcp-adapter-discover-abilities';
    const GET_INFO = 'mcp-adapter-get-ability-info';
    const EXECUTE  = 'mcp-adapter-execute-ability';

    /** Abilities with this prefix are also listed as named tools. */
    const NAMED_PREFIX = 'snn/bricks-';

    const MAX_TOOL_NAME_LENGTH = 128;

    public static function list_tools() {
        $tools = self::meta_tools();

        foreach ( self::named_abilities() as $tool_name => $ability ) {
            $tools[] = self::ability_tool( $ability, $tool_name );
        }

        return $tools;
    }

    /**
     * @param array $params tools/call params: name and arguments.
     * @return array CallToolResult.
     */
    public static function call_tool( array $params ) {
        $name = is_string( $params['name'] ?? null ) ? $params['name'] : '';
        if ( '' === $name ) {
            throw new SNN_MCP_Rpc_Exception( 'Tool name is required.', SNN_MCP_Protocol::INVALID_PARAMS );
        }

        $arguments = is_array( $params['arguments'] ?? null ) ? $params['arguments'] : array();

        if ( self::is_meta_tool( $name ) ) {
            $result = self::run_meta_tool( $name, $arguments );
        } else {
            $ability = self::named_abilities()[ $name ] ?? null;
            if ( null === $ability ) {
                throw new SNN_MCP_Rpc_Exception( 'Tool not found: ' . $name, SNN_MCP_Protocol::INVALID_PARAMS );
            }
            $result = self::run_named_tool( $ability, $arguments );
        }

        return self::tool_result( $result );
    }

    /* ----------------------------------------------------------------------
     * Listing
     * ------------------------------------------------------------------- */

    private static function meta_tools() {
        $empty_input = array(
            'type'       => 'object',
            'properties' => new stdClass(),
        );

        return array(
            array(
                'name'         => self::DISCOVER,
                'title'        => __( 'Discover Abilities', 'snn' ),
                'description'  => 'Discover all available WordPress abilities in the system. Returns a list of all registered abilities with their basic information.',
                'inputSchema'  => $empty_input,
                'outputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'abilities' => array(
                            'type'  => 'array',
                            'items' => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'name'        => array( 'type' => 'string' ),
                                    'label'       => array( 'type' => 'string' ),
                                    'description' => array( 'type' => 'string' ),
                                ),
                                'required'   => array( 'name', 'label', 'description' ),
                            ),
                        ),
                    ),
                    'required'   => array( 'abilities' ),
                ),
                'annotations'  => self::hints( true, false, true, __( 'Discover Abilities', 'snn' ) ),
            ),
            array(
                'name'         => self::GET_INFO,
                'title'        => __( 'Get Ability Info', 'snn' ),
                'description'  => 'Get detailed information about a specific WordPress ability including its input/output schema, description, and usage examples.',
                'inputSchema'  => array(
                    'type'       => 'object',
                    'properties' => array(
                        'ability_name' => array(
                            'type'        => 'string',
                            'description' => 'The full name of the ability to get information about',
                        ),
                    ),
                    'required'   => array( 'ability_name' ),
                ),
                'outputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'name'          => array( 'type' => 'string' ),
                        'label'         => array( 'type' => 'string' ),
                        'description'   => array( 'type' => 'string' ),
                        'input_schema'  => array(
                            'type'        => 'object',
                            'description' => 'JSON Schema for the ability input parameters',
                        ),
                        'output_schema' => array(
                            'type'        => 'object',
                            'description' => 'JSON Schema for the ability output structure',
                        ),
                        'meta'          => array(
                            'type'        => 'object',
                            'description' => 'Additional metadata about the ability',
                        ),
                    ),
                    'required'   => array( 'name', 'label', 'description', 'input_schema' ),
                ),
                'annotations'  => self::hints( true, false, true, __( 'Get Ability Info', 'snn' ) ),
            ),
            array(
                'name'         => self::EXECUTE,
                'title'        => __( 'Execute Ability', 'snn' ),
                'description'  => 'Execute a WordPress ability with the provided parameters. This is the primary execution layer that can run any registered ability.',
                'inputSchema'  => array(
                    'type'       => 'object',
                    'properties' => array(
                        'ability_name' => array(
                            'type'        => 'string',
                            'description' => 'The full name of the ability to execute',
                        ),
                        'parameters'   => array(
                            'type'        => 'object',
                            'description' => 'Parameters to pass to the ability',
                        ),
                    ),
                    'required'   => array( 'ability_name', 'parameters' ),
                ),
                'outputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'success' => array( 'type' => 'boolean' ),
                        'data'    => array(
                            'type'        => array( 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ),
                            'description' => 'The result data from the ability execution',
                        ),
                        'error'   => array(
                            'type'        => 'string',
                            'description' => 'Error message if execution failed',
                        ),
                    ),
                    'required'   => array( 'success' ),
                ),
                'annotations'  => self::hints( false, true, false, __( 'Execute Ability', 'snn' ) ),
            ),
        );
    }

    /** Named tools: public Bricks abilities whose tool name is valid, keyed by tool name. */
    private static function named_abilities() {
        if ( ! function_exists( 'wp_get_abilities' ) ) {
            return array();
        }

        $by_name = array();
        foreach ( wp_get_abilities() as $ability ) {
            $name = $ability->get_name();
            if ( 0 === strpos( $name, self::NAMED_PREFIX ) && self::is_public( $ability ) && 'tool' === self::mcp_type( $ability ) ) {
                $by_name[ $name ] = $ability;
            }
        }

        // Plugins add their own named tools through the filter below (Bricks does this
        // for its high-frequency abilities). Like the adapter did, these need no public flag.
        foreach ( self::extra_named_ability_names() as $name ) {
            if ( ! isset( $by_name[ $name ] ) && wp_has_ability( $name ) ) {
                $by_name[ $name ] = wp_get_ability( $name );
            }
        }

        $named = array();
        foreach ( $by_name as $name => $ability ) {
            $tool_name = self::tool_name( $name );
            if ( null !== $tool_name ) {
                $named[ $tool_name ] = $ability;
            }
        }

        return $named;
    }

    /**
     * Ability names that plugins list as named tools through `mcp_adapter_default_server_config`.
     * The filter name is kept, not the adapter: Bricks and others already hook it, so
     * their tools keep appearing without any change on their side.
     */
    private static function extra_named_ability_names() {
        $config = apply_filters( 'mcp_adapter_default_server_config', array( 'tools' => array() ) );
        $names  = is_array( $config ) && is_array( $config['tools'] ?? null ) ? $config['tools'] : array();

        return array_filter( $names, 'is_string' );
    }

    /** Ability name to MCP tool name: slashes become hyphens. Null when the result is not a valid tool name. */
    private static function tool_name( $ability_name ) {
        $name = str_replace( '/', '-', $ability_name );
        if ( strlen( $name ) > self::MAX_TOOL_NAME_LENGTH || ! preg_match( '/^[a-zA-Z0-9_.-]+$/', $name ) ) {
            return null;
        }
        return $name;
    }

    private static function ability_tool( $ability, $tool_name ) {
        $input = self::object_schema( $ability->get_input_schema(), 'input' );
        $label = $ability->get_label();

        $tool = array(
            'name'        => $tool_name,
            'title'       => $label,
            'description' => $ability->get_description(),
            'inputSchema' => $input['schema'] + array( 'properties' => new stdClass() ),
        );

        $output = self::object_schema( $ability->get_output_schema(), 'result' );
        if ( ! empty( $ability->get_output_schema() ) ) {
            $tool['outputSchema'] = $output['schema'] + array( 'properties' => new stdClass() );
        }

        $meta = $ability->get_meta();
        $tool['annotations'] = self::annotations( $meta, $label );

        $mcp = self::mcp_meta( $ability );
        if ( isset( $mcp['icons'] ) ) {
            $tool['icons'] = $mcp['icons'];
        }
        if ( isset( $mcp['_meta'] ) ) {
            $tool['_meta'] = $mcp['_meta'];
        }

        return $tool;
    }

    /**
     * Ability annotations as MCP tool hints. The ability's own names (readonly,
     * destructive, idempotent) win over the MCP names (readOnlyHint, ...).
     */
    private static function annotations( array $meta, $title ) {
        $source = is_array( $meta['annotations'] ?? null ) ? $meta['annotations'] : array();

        $hints = array();
        $map   = array(
            'readOnlyHint'    => array( 'readonly', 'readOnlyHint' ),
            'destructiveHint' => array( 'destructive', 'destructiveHint' ),
            'idempotentHint'  => array( 'idempotent', 'idempotentHint' ),
            'openWorldHint'   => array( 'openWorldHint' ),
        );
        foreach ( $map as $hint => $keys ) {
            foreach ( $keys as $key ) {
                if ( isset( $source[ $key ] ) ) {
                    $hints[ $hint ] = (bool) $source[ $key ];
                    break;
                }
            }
        }

        $hints['title'] = $title;
        return $hints;
    }

    private static function hints( $read_only, $destructive, $idempotent, $title ) {
        return array(
            'readOnlyHint'    => $read_only,
            'destructiveHint' => $destructive,
            'idempotentHint'  => $idempotent,
            'title'           => $title,
        );
    }

    /**
     * Wrap a schema that is not an object in an object, so every tool takes an
     * object of arguments. The wrapper key says where the value sits.
     *
     * @return array{schema: array, wrapped: bool}
     */
    private static function object_schema( $schema, $wrapper ) {
        $schema = is_array( $schema ) ? $schema : array();

        if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) && empty( $schema['properties'] ) ) {
            unset( $schema['properties'] );
        }

        if ( empty( $schema ) ) {
            return array(
                'schema'  => array( 'type' => 'object' ),
                'wrapped' => false,
            );
        }

        if ( ! isset( $schema['type'] ) ) {
            $schema['type'] = 'object';
        }

        if ( 'object' === $schema['type'] ) {
            return array(
                'schema'  => $schema,
                'wrapped' => false,
            );
        }

        return array(
            'schema'  => array(
                'type'       => 'object',
                'properties' => array( $wrapper => $schema ),
                'required'   => array( $wrapper ),
            ),
            'wrapped' => true,
        );
    }

    /* ----------------------------------------------------------------------
     * Calling
     * ------------------------------------------------------------------- */

    private static function is_meta_tool( $name ) {
        return in_array( $name, array( self::DISCOVER, self::GET_INFO, self::EXECUTE ), true );
    }

    private static function run_meta_tool( $name, array $arguments ) {
        switch ( $name ) {
            case self::DISCOVER:
                return self::discover();
            case self::GET_INFO:
                return self::get_info( $arguments );
            case self::EXECUTE:
                return self::execute( $arguments );
        }
        return new WP_Error( 'snn_mcp_unknown_tool', 'Tool not found: ' . $name );
    }

    private static function run_named_tool( $ability, array $arguments ) {
        $input = self::object_schema( $ability->get_input_schema(), 'input' );
        $args  = $input['wrapped'] ? ( $arguments['input'] ?? null ) : $arguments;
        $args  = self::normalize_arguments( $ability, $args );

        $permission = $ability->check_permissions( $args );
        if ( true !== $permission ) {
            return is_wp_error( $permission ) ? $permission : new WP_Error( 'snn_mcp_permission_denied', __( 'Permission denied', 'snn' ) );
        }

        $result = $ability->execute( $args );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $output = self::object_schema( $ability->get_output_schema(), 'result' );
        return $output['wrapped'] ? array( 'result' => $result ) : $result;
    }

    private static function discover() {
        $access = self::check_user_access();
        if ( is_wp_error( $access ) ) {
            return $access;
        }

        $abilities = array();
        foreach ( wp_get_abilities() as $ability ) {
            if ( ! self::is_public( $ability ) || 'tool' !== self::mcp_type( $ability ) ) {
                continue;
            }
            $abilities[] = array(
                'name'        => $ability->get_name(),
                'label'       => $ability->get_label(),
                'description' => $ability->get_description(),
            );
        }

        return array( 'abilities' => $abilities );
    }

    private static function get_info( array $arguments ) {
        $ability = self::exposed_ability_argument( $arguments );
        if ( is_wp_error( $ability ) ) {
            return $ability;
        }

        $info = array(
            'name'         => $ability->get_name(),
            'label'        => $ability->get_label(),
            'description'  => $ability->get_description(),
            'input_schema' => $ability->get_input_schema() ?: new stdClass(),
        );

        $output_schema = $ability->get_output_schema();
        if ( ! empty( $output_schema ) ) {
            $info['output_schema'] = $output_schema;
        }

        $meta = $ability->get_meta();
        if ( ! empty( $meta ) ) {
            $info['meta'] = $meta;
        }

        return $info;
    }

    private static function execute( array $arguments ) {
        $ability = self::exposed_ability_argument( $arguments );
        if ( is_wp_error( $ability ) ) {
            return $ability;
        }

        $args       = self::normalize_arguments( $ability, $arguments['parameters'] ?? null );
        $permission = $ability->check_permissions( $args );
        if ( true !== $permission ) {
            return is_wp_error( $permission ) ? $permission : new WP_Error( 'snn_mcp_permission_denied', __( 'Permission denied', 'snn' ) );
        }

        try {
            $result = $ability->execute( $args );
        } catch ( Throwable $exception ) {
            return array(
                'success' => false,
                'error'   => $exception->getMessage(),
            );
        }

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'error'   => $result->get_error_message(),
            );
        }

        return array(
            'success' => true,
            'data'    => $result,
        );
    }

    /**
     * The ability named in ability_name, after the access checks the meta tools
     * share: a logged-in user with the capability, and an ability exposed to MCP.
     *
     * @return WP_Ability|WP_Error
     */
    private static function exposed_ability_argument( array $arguments ) {
        $name = $arguments['ability_name'] ?? '';
        if ( ! is_string( $name ) || '' === $name ) {
            return new WP_Error( 'missing_ability_name', __( 'Ability name is required', 'snn' ) );
        }

        $access = self::check_user_access();
        if ( is_wp_error( $access ) ) {
            return $access;
        }

        $ability = wp_has_ability( $name ) ? wp_get_ability( $name ) : null;
        if ( ! $ability || ! self::is_public( $ability ) ) {
            $suggestions = self::suggest( $name );
            return new WP_Error( 'ability_not_found', self::not_found_message( $name, $suggestions ), array( 'suggestions' => $suggestions ) );
        }

        return $ability;
    }

    private static function check_user_access() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'authentication_required', __( 'User must be authenticated to access this ability', 'snn' ) );
        }

        $capability = apply_filters( 'snn_mcp_ability_capability', 'read' );
        if ( ! current_user_can( $capability ) ) {
            return new WP_Error( 'insufficient_capability', sprintf( 'User lacks required capability: %s', $capability ) );
        }

        return true;
    }

    /**
     * Ability input as the ability expects it. Empty input becomes null when the
     * schema has a default (so the default applies) and an empty array otherwise.
     */
    private static function normalize_arguments( $ability, $parameters ) {
        $schema = $ability->get_input_schema();

        if ( empty( $schema ) ) {
            return is_array( $parameters ) && empty( $parameters ) ? null : $parameters;
        }

        if ( null === $parameters || array() === $parameters ) {
            if ( array_key_exists( 'default', $schema ) ) {
                return null;
            }
            $type = $schema['type'] ?? null;
            $nullable = is_array( $type ) ? in_array( 'null', $type, true ) : 'null' === $type;
            return null === $parameters && $nullable ? null : array();
        }

        return $parameters;
    }

    /** Result of a call in the MCP CallToolResult shape. */
    private static function tool_result( $result ) {
        if ( is_wp_error( $result ) ) {
            return self::error_result( $result->get_error_message() );
        }

        if ( is_object( $result ) ) {
            $result = get_object_vars( $result );
        }
        if ( ! is_array( $result ) ) {
            $result = array( 'result' => $result );
        }

        // An ability that reports { success: false, error } is a failed call.
        if ( array_key_exists( 'success', $result ) && false === $result['success'] && isset( $result['error'] ) && is_string( $result['error'] ) && '' !== trim( $result['error'] ) ) {
            return self::error_result( $result['error'] );
        }

        $json = wp_json_encode( $result );
        if ( false === $json ) {
            throw new RuntimeException( 'Tool result cannot be JSON encoded.' );
        }

        return array(
            'content'           => array(
                array(
                    'type' => 'text',
                    'text' => $json,
                ),
            ),
            'isError'           => false,
            'structuredContent' => json_decode( $json ),
        );
    }

    private static function error_result( $message ) {
        return array(
            'content' => array(
                array(
                    'type' => 'text',
                    'text' => $message,
                ),
            ),
            'isError' => true,
        );
    }

    /* ----------------------------------------------------------------------
     * Ability helpers
     * ------------------------------------------------------------------- */

    /** Public to MCP: meta.mcp.public when set, otherwise meta.public === true. */
    private static function is_public( $ability ) {
        $meta = $ability->get_meta();
        $mcp  = self::mcp_meta( $ability );

        if ( isset( $mcp['public'] ) ) {
            return (bool) $mcp['public'];
        }
        return true === ( $meta['public'] ?? false );
    }

    private static function mcp_meta( $ability ) {
        $meta = $ability->get_meta();
        return is_array( $meta['mcp'] ?? null ) ? $meta['mcp'] : array();
    }

    private static function mcp_type( $ability ) {
        $type = self::mcp_meta( $ability )['type'] ?? 'tool';
        return in_array( $type, array( 'tool', 'resource', 'prompt' ), true ) ? $type : 'tool';
    }

    /** Public tool abilities whose names are closest to the requested one. */
    private static function suggest( $name ) {
        $scored = array();
        foreach ( wp_get_abilities() as $ability ) {
            if ( ! self::is_public( $ability ) || 'tool' !== self::mcp_type( $ability ) ) {
                continue;
            }
            similar_text( strtolower( $name ), strtolower( $ability->get_name() ), $percent );
            if ( $percent >= 50 ) {
                $scored[ $ability->get_name() ] = $percent;
            }
        }

        arsort( $scored );
        return array_slice( array_keys( $scored ), 0, 5 );
    }

    private static function not_found_message( $name, array $suggestions ) {
        if ( array() !== $suggestions ) {
            return sprintf(
                "Ability '%1\$s' not found on this WordPress site. Closest abilities you can call: %2\$s. Use one of these if it does what you need; guessing other spellings will not find '%1\$s'.",
                $name,
                implode( ', ', $suggestions )
            );
        }

        return sprintf(
            "Ability '%s' not found on this WordPress site, and no ability you can call has a similar name. Call %s to see what this site offers, or tell the user this site cannot do it.",
            $name,
            self::DISCOVER
        );
    }
}
