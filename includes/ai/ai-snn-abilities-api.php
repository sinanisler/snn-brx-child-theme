<?php
/**
 * SNN Abilities API
 * 
 * A simplified implementation of the WordPress Abilities API concept.
 * Provides a standardized way to register, discover, and execute capabilities
 * for AI agent integrations.
 * 
 * @package SNN_Abilities_API
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * =============================================================================
 * ABILITY CLASS
 * =============================================================================
 */

/**
 * Represents a single ability with its configuration and execution logic.
 * Stores all metadata and provides the execute() method for invoking the ability.
 */
class SNN_Ability {

    protected $name;
    protected $label;
    protected $description;
    protected $category;
    protected $input_schema;
    protected $output_schema;
    protected $execute_callback;
    protected $permission_callback;
    protected $meta;

    /**
     * Constructor - initializes ability with provided arguments.
     * Sets sensible defaults for optional parameters.
     */
    public function __construct( string $name, array $args ) {
        $defaults = array(
            'label'               => $name,
            'description'         => '',
            'category'            => 'general',
            'input_schema'        => array(),
            'output_schema'       => array(),
            'execute_callback'    => null,
            'permission_callback' => '__return_false',
            'meta'                => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => true,
            ),
        );

        $args = wp_parse_args( $args, $defaults );

        $this->name                = $name;
        $this->label               = $args['label'];
        $this->description         = $args['description'];
        $this->category            = $args['category'];
        $this->input_schema        = $args['input_schema'];
        $this->output_schema       = $args['output_schema'];
        $this->execute_callback    = $args['execute_callback'];
        $this->permission_callback = $args['permission_callback'];
        $this->meta                = $args['meta'];
    }

    /**
     * Get ability name/identifier.
     * Used as unique key in registry.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get human-readable label.
     * Useful for UI display and AI agent descriptions.
     */
    public function get_label(): string {
        return $this->label;
    }

    /**
     * Get ability description.
     * Helps AI agents understand what this ability does.
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Get category for grouping abilities.
     * Enables organized discovery of related abilities.
     */
    public function get_category(): string {
        return $this->category;
    }

    /**
     * Get input schema defining expected parameters.
     * Used for validation and AI agent parameter hints.
     */
    public function get_input_schema(): array {
        return $this->input_schema;
    }

    /**
     * Get output schema defining return structure.
     * Helps AI agents understand response format.
     */
    public function get_output_schema(): array {
        return $this->output_schema;
    }

    /**
     * Get metadata array.
     * Contains REST visibility and behavior flags.
     */
    public function get_meta(): array {
        return $this->meta;
    }

    /**
     * Check if current user has permission to execute.
     * Calls the registered permission callback.
     */
    public function check_permission(): bool {
        if ( is_callable( $this->permission_callback ) ) {
            return (bool) call_user_func( $this->permission_callback );
        }
        return false;
    }

    /**
     * Validate input against schema.
     * Returns WP_Error on validation failure, true on success.
     */
    public function validate_input( array $input ): bool|WP_Error {
        if ( empty( $this->input_schema ) || empty( $this->input_schema['properties'] ) ) {
            return true;
        }

        $properties = $this->input_schema['properties'];
        $required   = $this->input_schema['required'] ?? array();

        // Check required fields
        foreach ( $required as $field ) {
            if ( ! isset( $input[ $field ] ) ) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf( 'Required field "%s" is missing.', $field ),
                    array( 'status' => 400 )
                );
            }
        }

        // Validate types
        foreach ( $input as $key => $value ) {
            if ( isset( $properties[ $key ]['type'] ) ) {
                $expected_type = $properties[ $key ]['type'];
                $valid         = $this->validate_type( $value, $expected_type );
                
                if ( ! $valid ) {
                    return new WP_Error(
                        'invalid_type',
                        sprintf( 'Field "%s" must be of type "%s".', $key, $expected_type ),
                        array( 'status' => 400 )
                    );
                }
            }
        }

        return true;
    }

    /**
     * Check if value matches expected type.
     * Supports string, integer, number, boolean, array, object.
     */
    protected function validate_type( $value, string $type ): bool {
        switch ( $type ) {
            case 'string':
                return is_string( $value );
            case 'integer':
                return is_int( $value ) || ( is_numeric( $value ) && (int) $value == $value );
            case 'number':
                return is_numeric( $value );
            case 'boolean':
                return is_bool( $value ) || $value === 'true' || $value === 'false' || $value === 1 || $value === 0;
            case 'array':
                return is_array( $value );
            case 'object':
                return is_array( $value ) || is_object( $value );
            default:
                return true;
        }
    }

    /**
     * Execute the ability with given input.
     * Validates input, checks permissions, then calls the callback.
     */
    public function execute( array $input = array() ): mixed {
        // Check permission
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'permission_denied',
                'You do not have permission to execute this ability.',
                array( 'status' => 403 )
            );
        }

        // Validate input
        $validation = $this->validate_input( $input );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        // Allow filtering before execution
        $input = apply_filters( 'snn_before_ability_execute', $input, $this->name, $this );

        // Execute callback
        if ( ! is_callable( $this->execute_callback ) ) {
            return new WP_Error(
                'invalid_callback',
                'The execute callback is not callable.',
                array( 'status' => 500 )
            );
        }

        $result = call_user_func( $this->execute_callback, $input );

        // Allow filtering after execution
        $result = apply_filters( 'snn_after_ability_execute', $result, $this->name, $input, $this );

        return $result;
    }

    /**
     * Convert ability to array for JSON serialization.
     * Used by REST API responses.
     */
    public function to_array(): array {
        return array(
            'name'          => $this->name,
            'label'         => $this->label,
            'description'   => $this->description,
            'category'      => $this->category,
            'input_schema'  => $this->input_schema,
            'output_schema' => $this->output_schema,
            'meta'          => $this->meta,
        );
    }
}

/**
 * =============================================================================
 * ABILITIES REGISTRY (SINGLETON)
 * =============================================================================
 */

/**
 * Central registry for all abilities.
 * Singleton pattern ensures single source of truth across WordPress.
 */
class SNN_Abilities_Registry {

    private static $instance = null;
    private $abilities = array();
    private $categories = array();
    private $initialized = false;

    /**
     * Private constructor prevents direct instantiation.
     * Use get_instance() to access the registry.
     */
    private function __construct() {
        // Register default categories
        $this->categories = array(
            'general'  => __( 'General', 'snn-abilities' ),
            'content'  => __( 'Content', 'snn-abilities' ),
            'media'    => __( 'Media', 'snn-abilities' ),
            'users'    => __( 'Users', 'snn-abilities' ),
            'settings' => __( 'Settings', 'snn-abilities' ),
            'site'     => __( 'Site', 'snn-abilities' ),
        );
    }

    /**
     * Get singleton instance.
     * Creates instance on first call.
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the registry and fire init hook.
     * Should be called once during WordPress init.
     */
    public function init(): void {
        if ( $this->initialized ) {
            return;
        }

        $this->initialized = true;

        // Fire action for plugins/themes to register abilities
        do_action( 'snn_abilities_api_init' );
    }

    /**
     * Register a new ability.
     * Returns true on success, WP_Error on failure.
     */
    public function register( string $name, array $args ): bool|WP_Error {
        // Validate name format (namespace/ability recommended)
        if ( empty( $name ) ) {
            return new WP_Error(
                'invalid_ability_name',
                'Ability name cannot be empty.'
            );
        }

        // Check for duplicates
        if ( isset( $this->abilities[ $name ] ) ) {
            return new WP_Error(
                'ability_exists',
                sprintf( 'Ability "%s" is already registered.', $name )
            );
        }

        // Validate required args
        if ( empty( $args['execute_callback'] ) || ! is_callable( $args['execute_callback'] ) ) {
            return new WP_Error(
                'invalid_callback',
                'A valid execute_callback is required.'
            );
        }

        // Create and store ability
        $ability = new SNN_Ability( $name, $args );
        $this->abilities[ $name ] = $ability;

        // Fire action after registration
        do_action( 'snn_ability_registered', $name, $ability );

        return true;
    }

    /**
     * Unregister an ability.
     * Returns true on success, false if not found.
     */
    public function unregister( string $name ): bool {
        if ( ! isset( $this->abilities[ $name ] ) ) {
            return false;
        }

        unset( $this->abilities[ $name ] );
        do_action( 'snn_ability_unregistered', $name );

        return true;
    }

    /**
     * Get single ability by name.
     * Returns null if not found.
     */
    public function get( string $name ): ?SNN_Ability {
        return $this->abilities[ $name ] ?? null;
    }

    /**
     * Get all registered abilities.
     * Optionally filter by category.
     */
    public function get_all( ?string $category = null ): array {
        if ( null === $category ) {
            return $this->abilities;
        }

        return array_filter(
            $this->abilities,
            fn( $ability ) => $ability->get_category() === $category
        );
    }

    /**
     * Check if ability exists.
     * Quick existence check without fetching full object.
     */
    public function has( string $name ): bool {
        return isset( $this->abilities[ $name ] );
    }

    /**
     * Register a custom category.
     * Categories help organize abilities for discovery.
     */
    public function register_category( string $slug, string $label ): void {
        $this->categories[ $slug ] = $label;
    }

    /**
     * Get all registered categories.
     */
    public function get_categories(): array {
        return $this->categories;
    }

    /**
     * Get abilities formatted for AI agents.
     * Returns simplified schema useful for LLM function calling.
     */
    public function get_for_ai_agent(): array {
        $abilities = array();

        foreach ( $this->abilities as $ability ) {
            $abilities[] = array(
                'name'        => $ability->get_name(),
                'description' => $ability->get_description(),
                'parameters'  => $ability->get_input_schema(),
            );
        }

        return $abilities;
    }

    /**
     * Prevent cloning of singleton.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton.
     */
    public function __wakeup() {
        throw new Exception( 'Cannot unserialize singleton' );
    }
}

/**
 * =============================================================================
 * HELPER FUNCTIONS
 * =============================================================================
 */

/**
 * Register a new ability.
 * Main function for plugins/themes to register their abilities.
 * 
 * @param string $name Unique identifier (recommend namespace/ability format)
 * @param array  $args Ability configuration array
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function snn_register_ability( string $name, array $args ): bool|WP_Error {
    return SNN_Abilities_Registry::get_instance()->register( $name, $args );
}

/**
 * Unregister an ability.
 * Removes ability from registry.
 * 
 * @param string $name Ability identifier
 * @return bool True if removed, false if not found
 */
function snn_unregister_ability( string $name ): bool {
    return SNN_Abilities_Registry::get_instance()->unregister( $name );
}

/**
 * Get single ability by name.
 * Returns the ability object for inspection or execution.
 * 
 * @param string $name Ability identifier
 * @return SNN_Ability|null Ability object or null
 */
function snn_get_ability( string $name ): ?SNN_Ability {
    return SNN_Abilities_Registry::get_instance()->get( $name );
}

/**
 * Get all registered abilities.
 * Optionally filter by category.
 * 
 * @param string|null $category Optional category filter
 * @return array Array of SNN_Ability objects
 */
function snn_get_abilities( ?string $category = null ): array {
    return SNN_Abilities_Registry::get_instance()->get_all( $category );
}

/**
 * Check if ability exists.
 * Quick check without fetching full object.
 * 
 * @param string $name Ability identifier
 * @return bool True if exists
 */
function snn_has_ability( string $name ): bool {
    return SNN_Abilities_Registry::get_instance()->has( $name );
}

/**
 * Execute ability by name with input.
 * Convenience function that fetches and executes in one call.
 * 
 * @param string $name  Ability identifier
 * @param array  $input Input parameters
 * @return mixed Result of execution or WP_Error
 */
function snn_execute_ability( string $name, array $input = array() ): mixed {
    $ability = snn_get_ability( $name );

    if ( null === $ability ) {
        return new WP_Error(
            'ability_not_found',
            sprintf( 'Ability "%s" not found.', $name ),
            array( 'status' => 404 )
        );
    }

    return $ability->execute( $input );
}

/**
 * Register a custom ability category.
 * Categories help organize abilities for discovery.
 * 
 * @param string $slug  Category slug
 * @param string $label Human-readable label
 */
function snn_register_ability_category( string $slug, string $label ): void {
    SNN_Abilities_Registry::get_instance()->register_category( $slug, $label );
}

/**
 * Get abilities formatted for AI agents.
 * Returns simplified schema compatible with LLM function calling.
 * 
 * @return array Array of ability schemas
 */
function snn_get_abilities_for_ai(): array {
    return SNN_Abilities_Registry::get_instance()->get_for_ai_agent();
}

/**
 * =============================================================================
 * REST API CONTROLLER
 * =============================================================================
 */

/**
 * REST API controller for abilities endpoints.
 * Handles listing, fetching, and executing abilities via REST.
 */
class SNN_Abilities_REST_Controller {

    protected $namespace = 'snn-abilities/v1';

    /**
     * Register REST API routes.
     * Called during rest_api_init action.
     */
    public function register_routes(): void {
        // GET /abilities - List all abilities
        register_rest_route(
            $this->namespace,
            '/abilities',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_abilities' ),
                'permission_callback' => array( $this, 'get_abilities_permission' ),
                'args'                => array(
                    'category' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // GET /abilities/{id} - Get single ability
        register_rest_route(
            $this->namespace,
            '/abilities/(?P<id>[a-zA-Z0-9_\-\/]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ability' ),
                'permission_callback' => array( $this, 'get_abilities_permission' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // POST /abilities/{id}/run - Execute ability
        register_rest_route(
            $this->namespace,
            '/abilities/(?P<id>[a-zA-Z0-9_\-\/]+)/run',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'execute_ability' ),
                'permission_callback' => array( $this, 'execute_ability_permission' ),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // GET /categories - List all categories
        register_rest_route(
            $this->namespace,
            '/categories',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_categories' ),
                'permission_callback' => array( $this, 'get_abilities_permission' ),
            )
        );

        // GET /schema - Get all abilities as AI agent schema
        register_rest_route(
            $this->namespace,
            '/schema',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_schema' ),
                'permission_callback' => array( $this, 'get_abilities_permission' ),
            )
        );
    }

    /**
     * Permission check for listing abilities.
     * Requires authenticated user by default.
     */
    public function get_abilities_permission( WP_REST_Request $request ): bool {
        return is_user_logged_in();
    }

    /**
     * Permission check for executing abilities.
     * Requires authentication; per-ability permissions checked during execution.
     */
    public function execute_ability_permission( WP_REST_Request $request ): bool {
        return is_user_logged_in();
    }

    /**
     * Handle GET /abilities request.
     * Returns list of all abilities, optionally filtered by category.
     */
    public function get_abilities( WP_REST_Request $request ): WP_REST_Response {
        $category  = $request->get_param( 'category' );
        $abilities = snn_get_abilities( $category );

        $data = array();
        foreach ( $abilities as $ability ) {
            if ( $ability->get_meta()['show_in_rest'] ?? true ) {
                $data[] = $ability->to_array();
            }
        }

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Handle GET /abilities/{id} request.
     * Returns single ability details.
     */
    public function get_ability( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id      = $request->get_param( 'id' );
        $ability = snn_get_ability( $id );

        if ( null === $ability ) {
            return new WP_Error(
                'ability_not_found',
                sprintf( 'Ability "%s" not found.', $id ),
                array( 'status' => 404 )
            );
        }

        if ( ! ( $ability->get_meta()['show_in_rest'] ?? true ) ) {
            return new WP_Error(
                'ability_not_available',
                'This ability is not available via REST.',
                array( 'status' => 403 )
            );
        }

        return new WP_REST_Response( $ability->to_array(), 200 );
    }

    /**
     * Handle POST /abilities/{id}/run request.
     * Executes ability with JSON body as input.
     */
    public function execute_ability( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id    = $request->get_param( 'id' );
        $input = $request->get_json_params();

        if ( ! is_array( $input ) ) {
            $input = array();
        }

        $result = snn_execute_ability( $id, $input );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data'    => $result,
            ),
            200
        );
    }

    /**
     * Handle GET /categories request.
     * Returns all registered categories.
     */
    public function get_categories( WP_REST_Request $request ): WP_REST_Response {
        $categories = SNN_Abilities_Registry::get_instance()->get_categories();
        return new WP_REST_Response( $categories, 200 );
    }

    /**
     * Handle GET /schema request.
     * Returns abilities in AI agent compatible format.
     */
    public function get_ai_schema( WP_REST_Request $request ): WP_REST_Response {
        $schema = snn_get_abilities_for_ai();
        return new WP_REST_Response( $schema, 200 );
    }
}

/**
 * =============================================================================
 * INITIALIZATION
 * =============================================================================
 */

/**
 * Initialize the SNN Abilities API.
 * Hooks into WordPress init and rest_api_init.
 */
function snn_abilities_api_bootstrap(): void {
    // Initialize registry on init (priority 5 to run early)
    add_action( 'init', function() {
        SNN_Abilities_Registry::get_instance()->init();
    }, 5 );

    // Register REST routes
    add_action( 'rest_api_init', function() {
        $controller = new SNN_Abilities_REST_Controller();
        $controller->register_routes();
    } );
}

// Bootstrap the API
snn_abilities_api_bootstrap();

/**
 * =============================================================================
 * EXAMPLE ABILITIES (OPTIONAL - REMOVE IN PRODUCTION)
 * =============================================================================
 * 
 * Below are example abilities demonstrating different use cases.
 * Remove or modify these for your production environment.
 */

add_action( 'snn_abilities_api_init', 'snn_register_example_abilities' );

/**
 * Register example abilities for demonstration.
 * Shows different patterns and use cases.
 */
function snn_register_example_abilities(): void {

    // Example 1: Simple site info ability
    snn_register_ability(
        'snn/site-info',
        array(
            'label'       => 'Get Site Info',
            'description' => 'Retrieves basic information about the WordPress site.',
            'category'    => 'site',
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'name'        => array( 'type' => 'string' ),
                    'description' => array( 'type' => 'string' ),
                    'url'         => array( 'type' => 'string' ),
                    'admin_email' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => function( $input ) {
                return array(
                    'name'        => get_bloginfo( 'name' ),
                    'description' => get_bloginfo( 'description' ),
                    'url'         => get_bloginfo( 'url' ),
                    'admin_email' => get_bloginfo( 'admin_email' ),
                );
            },
            'permission_callback' => '__return_true', // Public
        )
    );

    // Example 2: Get posts with parameters
    snn_register_ability(
        'snn/get-posts',
        array(
            'label'       => 'Get Posts',
            'description' => 'Retrieves a list of posts with optional filtering.',
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type to retrieve (post, page, or custom).',
                    ),
                    'posts_per_page' => array(
                        'type'        => 'integer',
                        'description' => 'Number of posts to retrieve. Use -1 for all.',
                    ),
                    'category' => array(
                        'type'        => 'string',
                        'description' => 'Category slug to filter by.',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'      => array( 'type' => 'integer' ),
                        'title'   => array( 'type' => 'string' ),
                        'url'     => array( 'type' => 'string' ),
                        'excerpt' => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    'post_type'      => $input['post_type'] ?? 'post',
                    'posts_per_page' => $input['posts_per_page'] ?? 10,
                    'post_status'    => 'publish',
                );

                if ( ! empty( $input['category'] ) ) {
                    $args['category_name'] = $input['category'];
                }

                $posts  = get_posts( $args );
                $result = array();

                foreach ( $posts as $post ) {
                    $result[] = array(
                        'id'      => $post->ID,
                        'title'   => $post->post_title,
                        'url'     => get_permalink( $post ),
                        'excerpt' => wp_trim_words( $post->post_content, 30 ),
                    );
                }

                return $result;
            },
            'permission_callback' => '__return_true',
        )
    );

    // Example 3: Create post (requires authentication)
    snn_register_ability(
        'snn/create-post',
        array(
            'label'       => 'Create Post',
            'description' => 'Creates a new post with the provided title and content.',
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'title', 'content' ),
                'properties' => array(
                    'title' => array(
                        'type'        => 'string',
                        'description' => 'Post title.',
                    ),
                    'content' => array(
                        'type'        => 'string',
                        'description' => 'Post content (HTML allowed).',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'description' => 'Post status (draft, publish, pending).',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Post type (post, page).',
                    ),
                ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'  => array( 'type' => 'integer' ),
                    'url' => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $post_data = array(
                    'post_title'   => sanitize_text_field( $input['title'] ),
                    'post_content' => wp_kses_post( $input['content'] ),
                    'post_status'  => $input['status'] ?? 'draft',
                    'post_type'    => $input['post_type'] ?? 'post',
                    'post_author'  => get_current_user_id(),
                );

                $post_id = wp_insert_post( $post_data, true );

                if ( is_wp_error( $post_id ) ) {
                    return $post_id;
                }

                return array(
                    'id'  => $post_id,
                    'url' => get_permalink( $post_id ),
                );
            },
            'permission_callback' => function() {
                return current_user_can( 'publish_posts' );
            },
            'meta' => array(
                'show_in_rest' => true,
                'readonly'     => false,
                'destructive'  => false,
                'idempotent'   => false,
            ),
        )
    );

    // Example 4: Search content
    snn_register_ability(
        'snn/search',
        array(
            'label'       => 'Search Content',
            'description' => 'Searches posts, pages, and custom post types.',
            'category'    => 'content',
            'input_schema' => array(
                'type'       => 'object',
                'required'   => array( 'query' ),
                'properties' => array(
                    'query' => array(
                        'type'        => 'string',
                        'description' => 'Search query string.',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'description' => 'Limit search to specific post type.',
                    ),
                    'limit' => array(
                        'type'        => 'integer',
                        'description' => 'Maximum results to return.',
                    ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $args = array(
                    's'              => sanitize_text_field( $input['query'] ),
                    'post_type'      => $input['post_type'] ?? 'any',
                    'posts_per_page' => $input['limit'] ?? 10,
                    'post_status'    => 'publish',
                );

                $query   = new WP_Query( $args );
                $results = array();

                foreach ( $query->posts as $post ) {
                    $results[] = array(
                        'id'        => $post->ID,
                        'title'     => $post->post_title,
                        'type'      => $post->post_type,
                        'url'       => get_permalink( $post ),
                        'excerpt'   => wp_trim_words( $post->post_content, 20 ),
                        'relevance' => $post->relevance_score ?? null,
                    );
                }

                return array(
                    'total'   => $query->found_posts,
                    'results' => $results,
                );
            },
            'permission_callback' => '__return_true',
        )
    );

    // Example 5: Get current user info
    snn_register_ability(
        'snn/current-user',
        array(
            'label'       => 'Get Current User',
            'description' => 'Retrieves information about the currently logged-in user.',
            'category'    => 'users',
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'id'           => array( 'type' => 'integer' ),
                    'username'     => array( 'type' => 'string' ),
                    'email'        => array( 'type' => 'string' ),
                    'display_name' => array( 'type' => 'string' ),
                    'roles'        => array( 'type' => 'array' ),
                ),
            ),
            'execute_callback' => function( $input ) {
                $user = wp_get_current_user();

                if ( 0 === $user->ID ) {
                    return new WP_Error(
                        'not_logged_in',
                        'No user is currently logged in.',
                        array( 'status' => 401 )
                    );
                }

                return array(
                    'id'           => $user->ID,
                    'username'     => $user->user_login,
                    'email'        => $user->user_email,
                    'display_name' => $user->display_name,
                    'roles'        => $user->roles,
                );
            },
            'permission_callback' => 'is_user_logged_in',
        )
    );
}






/**
 * =============================================================================
 * USAGE EXAMPLES (DOCUMENTATION)
 * =============================================================================
 * 
 * Below are examples of how to use the SNN Abilities API in your plugins/themes.
 * These are wrapped in a false condition so they don't execute.
 */

if ( false ) {

    /**
     * EXAMPLE 1: Register a simple ability
     * 
     * Use snn_abilities_api_init hook to register your abilities.
     */
    add_action( 'snn_abilities_api_init', function() {
        snn_register_ability(
            'my-plugin/hello-world',
            array(
                'label'            => 'Say Hello',
                'description'      => 'Returns a greeting message.',
                'category'         => 'general',
                'execute_callback' => function( $input ) {
                    $name = $input['name'] ?? 'World';
                    return "Hello, {$name}!";
                },
                'permission_callback' => '__return_true',
            )
        );
    } );

    /**
     * EXAMPLE 2: Register ability with input validation
     */
    add_action( 'snn_abilities_api_init', function() {
        snn_register_ability(
            'my-plugin/calculate',
            array(
                'label'       => 'Calculate Sum',
                'description' => 'Adds two numbers together.',
                'category'    => 'general',
                'input_schema' => array(
                    'type'       => 'object',
                    'required'   => array( 'a', 'b' ),
                    'properties' => array(
                        'a' => array(
                            'type'        => 'number',
                            'description' => 'First number',
                        ),
                        'b' => array(
                            'type'        => 'number',
                            'description' => 'Second number',
                        ),
                    ),
                ),
                'output_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'result' => array( 'type' => 'number' ),
                    ),
                ),
                'execute_callback' => function( $input ) {
                    return array( 'result' => $input['a'] + $input['b'] );
                },
                'permission_callback' => '__return_true',
            )
        );
    } );

    /**
     * EXAMPLE 3: Execute ability in PHP
     */
    // Method 1: Using helper function
    $result = snn_execute_ability( 'my-plugin/calculate', array( 'a' => 5, 'b' => 3 ) );
    // Result: array( 'result' => 8 )

    // Method 2: Get ability and execute
    $ability = snn_get_ability( 'my-plugin/calculate' );
    if ( $ability ) {
        $result = $ability->execute( array( 'a' => 10, 'b' => 20 ) );
    }

    /**
     * EXAMPLE 4: Check if ability exists before using
     */
    if ( snn_has_ability( 'my-plugin/hello-world' ) ) {
        $greeting = snn_execute_ability( 'my-plugin/hello-world', array( 'name' => 'Developer' ) );
        echo $greeting; // "Hello, Developer!"
    }

    /**
     * EXAMPLE 5: List all abilities
     */
    $all_abilities = snn_get_abilities();
    foreach ( $all_abilities as $ability ) {
        echo $ability->get_name() . ': ' . $ability->get_description() . "\n";
    }

    /**
     * EXAMPLE 6: Filter abilities by category
     */
    $content_abilities = snn_get_abilities( 'content' );

    /**
     * EXAMPLE 7: Get abilities for AI agent (function calling schema)
     */
    $ai_schema = snn_get_abilities_for_ai();
    // Returns array formatted for OpenAI/Claude function calling

    /**
     * EXAMPLE 8: REST API Usage (JavaScript/External)
     * 
     * List abilities:
     * GET /wp-json/snn-abilities/v1/abilities
     * 
     * Get single ability:
     * GET /wp-json/snn-abilities/v1/abilities/my-plugin/hello-world
     * 
     * Execute ability:
     * POST /wp-json/snn-abilities/v1/abilities/my-plugin/calculate/run
     * Body: {"a": 5, "b": 3}
     * 
     * Get AI schema:
     * GET /wp-json/snn-abilities/v1/schema
     */

    /**
     * EXAMPLE 9: Register custom category
     */
    snn_register_ability_category( 'my-custom', 'My Custom Category' );

    /**
     * EXAMPLE 10: Ability with role-based permissions
     */
    add_action( 'snn_abilities_api_init', function() {
        snn_register_ability(
            'my-plugin/admin-only',
            array(
                'label'       => 'Admin Only Action',
                'description' => 'Only administrators can execute this.',
                'execute_callback' => function( $input ) {
                    return 'Secret admin data!';
                },
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
            )
        );
    } );

    /**
     * EXAMPLE 11: Filter ability execution
     */
    // Modify input before execution
    add_filter( 'snn_before_ability_execute', function( $input, $name, $ability ) {
        if ( $name === 'my-plugin/hello-world' ) {
            $input['name'] = strtoupper( $input['name'] ?? 'World' );
        }
        return $input;
    }, 10, 3 );

    // Modify result after execution
    add_filter( 'snn_after_ability_execute', function( $result, $name, $input, $ability ) {
        if ( $name === 'my-plugin/hello-world' ) {
            $result .= ' (modified)';
        }
        return $result;
    }, 10, 4 );

    /**
     * EXAMPLE 12: Use in AI Agent Integration
     * 
     * Get all abilities as function definitions for Claude/OpenAI:
     */
    $abilities_for_ai = snn_get_abilities_for_ai();
    
    // Format for Claude tools
    $claude_tools = array_map( function( $ability ) {
        return array(
            'name'        => str_replace( '/', '_', $ability['name'] ),
            'description' => $ability['description'],
            'input_schema' => $ability['parameters'],
        );
    }, $abilities_for_ai );

    // When AI wants to call a function, execute it
    $ai_function_call = 'my-plugin/calculate';
    $ai_parameters    = array( 'a' => 10, 'b' => 5 );
    $result           = snn_execute_ability( $ai_function_call, $ai_parameters );
}