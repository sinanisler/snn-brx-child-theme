<?php  
/**
 * SNN AI Chat Overlay
 *
 * File: snn-chat-overlay.php
 *
 * Purpose: Provides an AI-powered chat interface accessible ONLY in wp-admin area for logged-in users.
 * Adds a button to the admin bar and displays a floating overlay that can execute WordPress abilities
 * through AI agent conversations. Uses the existing AI API configuration and integrates with the
 * WordPress Core Abilities API for autonomous task execution.
 *
 * Features:
 * - Admin bar button for quick access
 * - Floating chat overlay with conversation history
 * - AI agent integration using existing API config
 * - WordPress abilities discovery and execution
 * - Client-side context and state management
 * - Draggable, resizable interface
 *
 * Abilities Management:
 * - Uses inverted storage logic: stores DISABLED abilities instead of enabled
 * - This makes it future-proof: newly added abilities are automatically enabled by default
 * - Includes automatic migration from old 'enabled' format to new 'disabled' format
 * - Database option: snn_ai_agent_disabled_abilities (associative array: ['ability-name' => true])
 * - Associative array format avoids JSON encoding issues with array indexes
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Chat Overlay Class
 */
class SNN_Chat_Overlay {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register custom post type for chat history
        add_action( 'init', array( $this, 'register_history_post_type' ) );
        
        // Add admin menu page
        add_action( 'admin_menu', array( $this, 'add_settings_submenu' ) );
        
        // Add settings save handler
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Add AJAX handler for history management
        add_action( 'wp_ajax_snn_delete_old_chats', array( $this, 'ajax_delete_old_chats' ) );
        add_action( 'wp_ajax_snn_save_chat_history', array( $this, 'ajax_save_chat_history' ) );
        add_action( 'wp_ajax_snn_load_chat_history', array( $this, 'ajax_load_chat_history' ) );
        add_action( 'wp_ajax_snn_get_chat_histories', array( $this, 'ajax_get_chat_histories' ) );
        add_action( 'wp_ajax_snn_delete_chat_history', array( $this, 'ajax_delete_chat_history' ) );
        
        // Check if feature is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }
        
        // Add admin bar button (wp-admin only)
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_button' ), 999 );
        
        // Enqueue scripts and styles (wp-admin only)
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // Render overlay HTML (wp-admin only)
        add_action( 'admin_footer', array( $this, 'render_overlay' ), 999 );
    }

    /**
     * Check if AI Agent is enabled
     */
    public function is_enabled() {
        return get_option( 'snn_ai_agent_enabled', false );
    }

    /**
     * Check if global AI Features are enabled (from AI Settings page)
     */
    public function is_ai_globally_enabled() {
        return get_option( 'snn_ai_enabled', 'no' ) === 'yes';
    }

    /**
     * Get custom system prompt
     */
    public function get_system_prompt() {
        return get_option( 'snn_ai_agent_system_prompt', 'You are a helpful WordPress assistant. Be proactive and execute actions directly when the user\'s intent is clear. Only ask clarifying questions when genuinely necessary - prefer using sensible defaults instead.' );
    }

    /**
     * Get token count setting
     */
    public function get_token_count() {
        return absint( get_option( 'snn_ai_agent_token_count', 4000 ) );
    }

    /**
     * Get enabled abilities
     *
     * Future-proof approach: Stores DISABLED abilities as associative array (ability_name => true).
     * This way, newly added abilities are automatically enabled by default.
     * No array index issues - ability name is the key!
     */
    public function get_enabled_abilities() {
        $all_abilities = $this->get_abilities();
        $all_ability_names = wp_list_pluck( $all_abilities, 'name' );

        // Get disabled abilities (stored as ['ability-name' => true])
        $disabled = get_option( 'snn_ai_agent_disabled_abilities', array() );

        // Backward compatibility: migrate from old formats
        $old_enabled = get_option( 'snn_ai_agent_enabled_abilities', null );
        if ( $old_enabled !== null ) {
            // Convert old format to new associative array format
            $disabled = array();
            foreach ( $all_ability_names as $ability_name ) {
                if ( ! in_array( $ability_name, (array) $old_enabled, true ) ) {
                    $disabled[ $ability_name ] = true; // Mark as disabled
                }
            }
            update_option( 'snn_ai_agent_disabled_abilities', $disabled );
            delete_option( 'snn_ai_agent_enabled_abilities' );
        }

        // Ensure disabled is always an array
        if ( ! is_array( $disabled ) ) {
            $disabled = array();
        }

        // Return all abilities that are NOT in the disabled list
        $enabled = array();
        foreach ( $all_ability_names as $ability_name ) {
            if ( ! isset( $disabled[ $ability_name ] ) ) {
                $enabled[] = $ability_name;
            }
        }

        return $enabled;
    }

    /**
     * Check if a specific ability is enabled
     */
    public function is_ability_enabled( $ability_name ) {
        $disabled = get_option( 'snn_ai_agent_disabled_abilities', array() );
        return ! isset( $disabled[ $ability_name ] );
    }

    /**
     * Check if debug mode is enabled
     */
    public function is_debug_enabled() {
        return get_option( 'snn_ai_agent_debug_mode', false );
    }

    /**
     * Get max retry attempts
     */
    public function get_max_retries() {
        return absint( get_option( 'snn_ai_agent_max_retries', 3 ) );
    }

    /**
     * Get max conversation history
     */
    public function get_max_history() {
        return absint( get_option( 'snn_ai_agent_max_history', 20 ) );
    }

    /**
     * Get chat overlay width
     */
    public function get_chat_width() {
        return absint( get_option( 'snn_ai_agent_chat_width', 400 ) );
    }

    /**
     * Register custom post type for chat history
     */
    public function register_history_post_type() {
        register_post_type( 'snn-agent-history', array(
            'labels' => array(
                'name' => __( 'AI Chat History', 'snn' ),
                'singular_name' => __( 'Chat History', 'snn' ),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array( 'title', 'custom-fields' ),
            'rewrite' => false,
        ) );
    }

    /**
     * Save chat history to database
     */
    public function save_chat_history( $messages, $session_id = null ) {
        if ( ! $session_id ) {
            $session_id = uniqid( 'chat_', true );
        }

        $post_id = null;
        
        // Check if session already exists
        $existing = get_posts( array(
            'post_type' => 'snn-agent-history',
            'meta_key' => 'session_id',
            'meta_value' => $session_id,
            'posts_per_page' => 1,
            'post_status' => 'private',
        ) );

        if ( ! empty( $existing ) ) {
            $post_id = $existing[0]->ID;
        }

        $title = date( 'Y-m-d H:i:s' ) . ' - ' . wp_get_current_user()->display_name;
        if ( ! empty( $messages ) && isset( $messages[0]['content'] ) ) {
            $first_message = wp_trim_words( $messages[0]['content'], 5, '...' );
            $title = $first_message;
        }

        $post_data = array(
            'post_title' => $title,
            'post_type' => 'snn-agent-history',
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
        );

        if ( $post_id ) {
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( $post_id ) {
            update_post_meta( $post_id, 'session_id', $session_id );
            update_post_meta( $post_id, 'messages', $messages );
            update_post_meta( $post_id, 'last_updated', current_time( 'mysql' ) );
        }

        return $session_id;
    }

    /**
     * Load chat history from database
     */
    public function load_chat_history( $session_id ) {
        $posts = get_posts( array(
            'post_type' => 'snn-agent-history',
            'meta_key' => 'session_id',
            'meta_value' => $session_id,
            'posts_per_page' => 1,
            'post_status' => 'private',
        ) );

        if ( empty( $posts ) ) {
            return array();
        }

        return get_post_meta( $posts[0]->ID, 'messages', true );
    }

    /**
     * Get all chat histories for current user
     */
    public function get_chat_histories() {
        $posts = get_posts( array(
            'post_type' => 'snn-agent-history',
            'author' => get_current_user_id(),
            'posts_per_page' => 50,
            'post_status' => 'private',
            'orderby' => 'modified',
            'order' => 'DESC',
        ) );

        $histories = array();
        foreach ( $posts as $post ) {
            $session_id = get_post_meta( $post->ID, 'session_id', true );
            $messages = get_post_meta( $post->ID, 'messages', true );
            $message_count = is_array( $messages ) ? count( $messages ) : 0;
            
            $histories[] = array(
                'session_id' => $session_id,
                'title' => $post->post_title,
                'date' => $post->post_modified,
                'message_count' => $message_count,
            );
        }

        return $histories;
    }

    /**
     * Delete all chat histories
     */
    public function delete_all_histories() {
        $posts = get_posts( array(
            'post_type' => 'snn-agent-history',
            'author' => get_current_user_id(),
            'posts_per_page' => -1,
            'post_status' => 'private',
        ) );

        foreach ( $posts as $post ) {
            wp_delete_post( $post->ID, true );
        }

        return count( $posts );
    }

    /**
     * AJAX: Delete old chats
     */
    public function ajax_delete_old_chats() {
        check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $deleted = $this->delete_all_histories();
        wp_send_json_success( array( 'deleted' => $deleted ) );
    }

    /**
     * AJAX: Save chat history
     */
    public function ajax_save_chat_history() {
        check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $messages = isset( $_POST['messages'] ) ? json_decode( stripslashes( $_POST['messages'] ), true ) : array();
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : null;

        $session_id = $this->save_chat_history( $messages, $session_id );
        wp_send_json_success( array( 'session_id' => $session_id ) );
    }

    /**
     * AJAX: Load chat history
     */
    public function ajax_load_chat_history() {
        check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';
        $messages = $this->load_chat_history( $session_id );
        
        wp_send_json_success( array( 'messages' => $messages ) );
    }

    /**
     * AJAX: Get all chat histories
     */
    public function ajax_get_chat_histories() {
        check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $histories = $this->get_chat_histories();
        wp_send_json_success( array( 'histories' => $histories ) );
    }

    /**
     * AJAX: Delete individual chat history
     */
    public function ajax_delete_chat_history() {
        check_ajax_referer( 'snn_ai_agent_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';
        
        if ( empty( $session_id ) ) {
            wp_send_json_error( 'Invalid session ID' );
        }

        $posts = get_posts( array(
            'post_type' => 'snn-agent-history',
            'meta_key' => 'session_id',
            'meta_value' => $session_id,
            'author' => get_current_user_id(),
            'posts_per_page' => 1,
            'post_status' => 'private',
        ) );

        if ( ! empty( $posts ) ) {
            wp_delete_post( $posts[0]->ID, true );
            wp_send_json_success( array( 'deleted' => true ) );
        } else {
            wp_send_json_error( 'Chat history not found' );
        }
    }

    /**
     * Add AI Agent Settings submenu page
     */
    public function add_settings_submenu() {
        add_submenu_page(
            'snn-settings',
            __('AI Agent Settings', 'snn'),
            __('AI Agent Settings', 'snn'),
            'manage_options',
            'snn-ai-agent-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_enabled' );
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_system_prompt' );
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_token_count' );
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_disabled_abilities' ); // New: stores disabled, not enabled
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_debug_mode' );
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_max_retries' );
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_max_history' );
        register_setting( 'snn_ai_agent_settings', 'snn_ai_agent_chat_width' );
    }

    /**
     * Render AI Agent Settings page
     */
    public function render_settings_page() {
        // Handle form submission
        if ( isset( $_POST['snn_ai_agent_settings_submit'] ) && check_admin_referer( 'snn_ai_agent_settings_action', 'snn_ai_agent_settings_nonce' ) ) {
            update_option( 'snn_ai_agent_enabled', isset( $_POST['snn_ai_agent_enabled'] ) ? true : false );
            update_option( 'snn_ai_agent_system_prompt', sanitize_textarea_field( wp_unslash( $_POST['snn_ai_agent_system_prompt'] ) ) );
            update_option( 'snn_ai_agent_token_count', absint( $_POST['snn_ai_agent_token_count'] ) );

            // New approach: Save DISABLED abilities as associative array (ability_name => true)
            $all_abilities = $this->get_abilities();
            $all_ability_names = wp_list_pluck( $all_abilities, 'name' );
            $enabled_from_form = isset( $_POST['snn_ai_agent_enabled_abilities'] ) ? array_map( 'sanitize_text_field', $_POST['snn_ai_agent_enabled_abilities'] ) : array();

            // Build disabled array with ability names as keys
            $disabled_abilities = array();
            foreach ( $all_ability_names as $ability_name ) {
                if ( ! in_array( $ability_name, $enabled_from_form, true ) ) {
                    $disabled_abilities[ $ability_name ] = true;
                }
            }
            update_option( 'snn_ai_agent_disabled_abilities', $disabled_abilities );

            update_option( 'snn_ai_agent_debug_mode', isset( $_POST['snn_ai_agent_debug_mode'] ) ? true : false );
            update_option( 'snn_ai_agent_max_retries', absint( $_POST['snn_ai_agent_max_retries'] ) );
            update_option( 'snn_ai_agent_max_history', absint( $_POST['snn_ai_agent_max_history'] ) );
            update_option( 'snn_ai_agent_chat_width', absint( $_POST['snn_ai_agent_chat_width'] ) );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'snn' ) . '</p></div>';
        }

        $enabled = $this->is_enabled();
        $system_prompt = $this->get_system_prompt();
        $default_prompt = 'You are a helpful WordPress assistant. Be proactive and execute actions directly when the user\'s intent is clear. Only ask clarifying questions when genuinely necessary - prefer using sensible defaults instead.';
        $token_count = $this->get_token_count();
        $enabled_abilities = $this->get_enabled_abilities();
        $debug_mode = $this->is_debug_enabled();
        $max_retries = $this->get_max_retries();
        $max_history = $this->get_max_history();
        $chat_width = $this->get_chat_width();

        // Try to fetch abilities
        $abilities = $this->get_abilities();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AI Agent & Chat Overlay Settings', 'snn'); ?></h1>
            <p><?php echo esc_html__('Configure the AI-powered chat assistant that helps you manage WordPress through natural conversation.', 'snn'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'snn_ai_agent_settings_action', 'snn_ai_agent_settings_nonce' ); ?>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <!-- Enable/Disable Toggle -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_enabled"><?php echo esc_html__('Enable AI Agent & Chat Overlay', 'snn'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" 
                                       id="snn_ai_agent_enabled" 
                                       name="snn_ai_agent_enabled" 
                                       value="1" 
                                       <?php checked( $enabled, true ); ?>>
                                <p class="description">
                                    <?php echo esc_html__('Enable the AI chat assistant accessible from the admin bar.', 'snn'); ?><br>
                                    <?php 
                                    printf(
                                        __('To set up AI API key and model selection, go to <a href="%s">AI Settings</a>.', 'snn'),
                                        admin_url('admin.php?page=snn-ai-settings')
                                    );
                                    ?>
                                </p>
                            </td>
                        </tr>

                        <!-- System Prompt -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_system_prompt"><?php echo esc_html__('System Prompt', 'snn'); ?></label>
                            </th>
                            <td>
                                <textarea 
                                    id="snn_ai_agent_system_prompt" 
                                    name="snn_ai_agent_system_prompt" 
                                    rows="6" 
                                    class="large-text code"
                                    style="max-width: 660px;"
                                    placeholder="<?php echo esc_attr( $default_prompt ); ?>"
                                ><?php echo esc_textarea( $system_prompt ); ?></textarea>
                                <p class="description">
                                    <?php echo esc_html__('Customize the AI assistant\'s behavior and personality. This is the base instruction that guides how the AI responds.', 'snn'); ?><br>
                                    <strong><?php echo esc_html__('Default:', 'snn'); ?></strong> <?php echo esc_html( $default_prompt ); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Token Count -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_token_count"><?php echo esc_html__('Max Token Count', 'snn'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="snn_ai_agent_token_count" 
                                       name="snn_ai_agent_token_count" 
                                       value="<?php echo esc_attr( $token_count ); ?>"
                                       min="100"
                                       max="128000"
                                       step="100"
                                       class="regular-text">
                                <p class="description">
                                    <?php echo esc_html__('Maximum number of tokens for AI responses. Higher values allow longer responses but cost more.', 'snn'); ?><br>
                                    <strong><?php echo esc_html__('Default:', 'snn'); ?></strong> 4000 | <strong><?php echo esc_html__('Recommended range:', 'snn'); ?></strong> 1000-8000
                                </p>
                            </td>
                        </tr>

                        <!-- Max Retry Attempts -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_max_retries"><?php echo esc_html__('Max Retry Attempts', 'snn'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="snn_ai_agent_max_retries" 
                                       name="snn_ai_agent_max_retries" 
                                       value="<?php echo esc_attr( $max_retries ); ?>"
                                       min="0"
                                       max="10"
                                       step="1"
                                       class="small-text">
                                <p class="description">
                                    <?php echo esc_html__('Number of times the AI will retry a failed ability execution with corrected input.', 'snn'); ?><br>
                                    <strong><?php echo esc_html__('Default:', 'snn'); ?></strong> 3
                                </p>
                            </td>
                        </tr>

                        <!-- Max Conversation History -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_max_history"><?php echo esc_html__('Max Conversation History', 'snn'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="snn_ai_agent_max_history" 
                                       name="snn_ai_agent_max_history" 
                                       value="<?php echo esc_attr( $max_history ); ?>"
                                       min="5"
                                       max="100"
                                       step="5"
                                       class="small-text">
                                <p class="description">
                                    <?php echo esc_html__('Number of recent messages to include in AI context. Higher values provide more context but use more tokens.', 'snn'); ?><br>
                                    <strong><?php echo esc_html__('Default:', 'snn'); ?></strong> 20
                                </p>
                            </td>
                        </tr>

                        <!-- Chat Overlay Width -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_chat_width"><?php echo esc_html__('Chat Overlay Width', 'snn'); ?></label>
                            </th>
                            <td>
                                <input type="number"
                                       id="snn_ai_agent_chat_width"
                                       name="snn_ai_agent_chat_width"
                                       value="<?php echo esc_attr( $chat_width ); ?>"
                                       min="300"
                                       max="800"
                                       step="10"
                                       class="small-text">
                                <span style="margin-left: 5px;">px</span>
                                <p class="description">
                                    <?php echo esc_html__('Width of the chat overlay panel in pixels.', 'snn'); ?><br>
                                    <strong><?php echo esc_html__('Default:', 'snn'); ?></strong> 400px | <strong><?php echo esc_html__('Range:', 'snn'); ?></strong> 300-800px
                                </p>
                            </td>
                        </tr>

                        <!-- Debug Mode -->
                        <tr>
                            <th scope="row">
                                <label for="snn_ai_agent_debug_mode"><?php echo esc_html__('Enable Debug Mode', 'snn'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox"
                                       id="snn_ai_agent_debug_mode"
                                       name="snn_ai_agent_debug_mode"
                                       value="1"
                                       <?php checked( $debug_mode, true ); ?>>
                                <p class="description">
                                    <?php echo esc_html__('Enable console.log debugging messages in browser console. Useful for troubleshooting.', 'snn'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button( __('Save Settings', 'snn'), 'primary', 'snn_ai_agent_settings_submit' ); ?>
            </form>

            <hr style="margin: 40px 0;">

            <!-- Available Abilities -->
            <h2><?php echo esc_html__('Available WordPress Abilities', 'snn'); ?></h2>
            <p><?php echo esc_html__('Select which abilities the AI agent can use. Newly added abilities are automatically enabled by default.', 'snn'); ?></p>

            <?php if ( ! empty( $abilities ) && is_array( $abilities ) ) : ?>
                <?php
                // Check for abilities with unregistered categories
                $unregistered_categories = array();
                foreach ( $abilities as $ability ) {
                    $category = isset( $ability['category'] ) ? $ability['category'] : 'uncategorized';
                    if ( $category !== 'uncategorized' && ! in_array( $category, $unregistered_categories, true ) ) {
                        // Add to list if category appears to be custom (not core)
                        if ( ! in_array( $category, array( 'content', 'users', 'media', 'settings', 'plugins', 'themes' ), true ) ) {
                            $unregistered_categories[] = $category;
                        }
                    }
                }
                if ( ! empty( $unregistered_categories ) ) : ?>
                    <div class="notice notice-warning inline" style="margin: 15px 0;">
                        <p>
                            <strong><?php echo esc_html__('Note:', 'snn'); ?></strong>
                            <?php
                            printf(
                                esc_html__('Some abilities use custom categories (%s). If you see WordPress notices about unregistered categories, register them using wp_register_ability_category() before registering your abilities.', 'snn'),
                                '<code>' . esc_html( implode( ', ', $unregistered_categories ) ) . '</code>'
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field( 'snn_ai_agent_settings_action', 'snn_ai_agent_settings_nonce' ); ?>
                    
                    <!-- Hidden fields to preserve other settings -->
                    <input type="hidden" name="snn_ai_agent_enabled" value="<?php echo $enabled ? '1' : '0'; ?>">
                    <input type="hidden" name="snn_ai_agent_system_prompt" value="<?php echo esc_attr( $system_prompt ); ?>">
                    <input type="hidden" name="snn_ai_agent_token_count" value="<?php echo esc_attr( $token_count ); ?>">
                    <input type="hidden" name="snn_ai_agent_debug_mode" value="<?php echo $debug_mode ? '1' : '0'; ?>">
                    <input type="hidden" name="snn_ai_agent_max_retries" value="<?php echo esc_attr( $max_retries ); ?>">
                    <input type="hidden" name="snn_ai_agent_max_history" value="<?php echo esc_attr( $max_history ); ?>">
                    <input type="hidden" name="snn_ai_agent_chat_width" value="<?php echo esc_attr( $chat_width ); ?>">
                    
                    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; max-width: 800px;">
                        <p style="margin-top: 0;">
                            <button type="button" id="snn-select-all-abilities" class="button" style="margin-right: 10px;">
                                <?php echo esc_html__('Select All', 'snn'); ?>
                            </button>
                            <button type="button" id="snn-deselect-all-abilities" class="button">
                                <?php echo esc_html__('Deselect All', 'snn'); ?>
                            </button>
                        </p>
                        
                        <div style="max-height: 400px; overflow-y: auto; padding: 10px; border: 1px solid #e0e0e0; border-radius: 3px;">
                            <?php foreach ( $abilities as $ability ) : 
                                $ability_name = $ability['name'];
                                $is_checked = in_array( $ability_name, $enabled_abilities );
                                $category = isset( $ability['category'] ) ? $ability['category'] : 'uncategorized';
                            ?>
                                <label style="display: block; padding: 8px 10px; margin: 0; border-bottom: 1px solid #f0f0f0;">
                                    <input type="checkbox" 
                                           name="snn_ai_agent_enabled_abilities[]" 
                                           value="<?php echo esc_attr( $ability_name ); ?>"
                                           <?php checked( $is_checked, true ); ?>
                                           class="snn-ability-checkbox">
                                    <strong><?php echo esc_html( $ability_name ); ?></strong>
                                    <span style="color: #666; font-size: 12px; margin-left: 8px;">(<?php echo esc_html( $category ); ?>)</span>
                                    <br>
                                    <span style="color: #666; font-size: 13px; margin-left: 24px;">
                                        <?php echo esc_html( $ability['description'] ?? $ability['label'] ?? 'No description available' ); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php submit_button( __('Save Ability Settings', 'snn'), 'primary', 'snn_ai_agent_settings_submit', true, array( 'style' => 'margin-top: 20px;' ) ); ?>
                </form>

                <script>
                jQuery(document).ready(function($) {
                    $('#snn-select-all-abilities').on('click', function() {
                        $('.snn-ability-checkbox').prop('checked', true);
                    });
                    
                    $('#snn-deselect-all-abilities').on('click', function() {
                        $('.snn-ability-checkbox').prop('checked', false);
                    });
                });
                </script>
            <?php else : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php echo esc_html__('No abilities found.', 'snn'); ?></strong><br>
                        <?php echo esc_html__('Make sure WordPress 6.9+ is installed and abilities are registered with show_in_rest enabled.', 'snn'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <hr style="margin: 40px 0;">

            <!-- Chat History Management -->
            <h2><?php echo esc_html__('Chat History Management', 'snn'); ?></h2>
            <p><?php echo esc_html__('Manage saved chat conversations. Chat history is automatically saved and can be accessed from the chat interface.', 'snn'); ?></p>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; max-width: 800px;">
                <?php
                $histories = $this->get_chat_histories();
                $history_count = count( $histories );
                ?>
                
                <p>
                    <strong><?php echo esc_html__('Total saved conversations:', 'snn'); ?></strong> <?php echo esc_html( $history_count ); ?>
                </p>
                
                <button type="button" id="snn-delete-old-chats" class="button button-secondary" 
                        <?php echo $history_count === 0 ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                    <?php echo esc_html__('Delete All Chat History', 'snn'); ?>
                </button>
                
                <p class="description" style="margin-top: 10px;">
                    <?php echo esc_html__('This will permanently delete all saved chat conversations for your account. This action cannot be undone.', 'snn'); ?>
                </p>

                <?php if ( $history_count > 0 ) : ?>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;overflow: scroll; max-height: 400px;">
                        <h4 style="margin-top: 0;"><?php echo esc_html__('Recent Conversations', 'snn'); ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ( array_slice( $histories, 0, 10 ) as $history ) : ?>
                                <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                    <strong><?php echo esc_html( $history['title'] ); ?></strong>
                                    <br>
                                    <small style="color: #666;">
                                        <?php echo esc_html( $history['message_count'] ); ?> messages • 
                                        <?php echo esc_html( human_time_diff( strtotime( $history['date'] ), current_time( 'timestamp' ) ) ); ?> ago
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#snn-delete-old-chats').on('click', function() {
                    if (!confirm('<?php echo esc_js( __('Are you sure you want to delete all chat history? This action cannot be undone.', 'snn') ); ?>')) {
                        return;
                    }
                    
                    var $button = $(this);
                    $button.prop('disabled', true).text('<?php echo esc_js( __('Deleting...', 'snn') ); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'snn_delete_old_chats',
                            nonce: '<?php echo wp_create_nonce( 'snn_ai_agent_nonce' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php echo esc_js( __('Successfully deleted', 'snn') ); ?> ' + response.data.deleted + ' <?php echo esc_js( __('chat histories.', 'snn') ); ?>');
                                location.reload();
                            } else {
                                alert('<?php echo esc_js( __('Error:', 'snn') ); ?> ' + response.data);
                                $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php echo esc_js( __('Delete All Chat History', 'snn') ); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php echo esc_js( __('An error occurred. Please try again.', 'snn') ); ?>');
                            $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php echo esc_js( __('Delete All Chat History', 'snn') ); ?>');
                        }
                    });
                });
            });
            </script>
        </div>

        <style>
            /* Removed fancy toggle and abilities styling - using native elements */
        </style>
        <?php
    }

    /**
     * Get available abilities from WordPress Core Abilities API
     */
    private function get_abilities() {
        $abilities = array();
        
        try {
            // Make internal REST API request
            $request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities' );
            $response = rest_do_request( $request );
            
            if ( ! is_wp_error( $response ) && $response->get_status() === 200 ) {
                $abilities = $response->get_data();
            }
        } catch ( Exception $e ) {
            // Silently fail
        }
        
        return $abilities;
    }

    /**
     * Get current page context for AI agent
     * Detects what page/screen the user is currently on
     */
    private function get_page_context() {
        global $pagenow, $post, $typenow;
        
        $context = array(
            'type' => 'unknown',
            'details' => array()
        );

        // Dashboard
        if ( $pagenow === 'index.php' ) {
            $context['type'] = 'dashboard';
            $context['details']['description'] = 'WordPress Dashboard home page';
            return $context;
        }

        // Post Editor - Single post being edited
        if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
            $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
            $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

            if ( $pagenow === 'post-new.php' || $action === 'edit' ) {
                $context['type'] = 'post_editor';
                $context['details']['has_block_editor'] = true; // Flag for client-side enhancement

                if ( $post_id && $action === 'edit' ) {
                    // Editing existing post
                    $the_post = get_post( $post_id );
                    if ( $the_post ) {
                        $context['details'] = array_merge( $context['details'], array(
                            'post_id' => $post_id,
                            'post_type' => $the_post->post_type,
                            'post_title' => $the_post->post_title,
                            'post_status' => $the_post->post_status,
                            'post_author' => get_the_author_meta( 'display_name', $the_post->post_author ),
                            'post_date' => $the_post->post_date,
                            'post_modified' => $the_post->post_modified,
                            'edit_url' => admin_url( 'post.php?action=edit&post=' . $post_id ),
                            'description' => sprintf( 'Editing %s: "%s" (ID: %d)', $the_post->post_type, $the_post->post_title, $post_id ),
                            'post_excerpt' => $the_post->post_excerpt,
                            'has_blocks' => has_blocks( $the_post->post_content ),
                            'word_count' => str_word_count( wp_strip_all_tags( $the_post->post_content ) ),
                        ) );
                    }
                } else {
                    // Creating new post
                    $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post';
                    $context['details'] = array_merge( $context['details'], array(
                        'post_type' => $post_type,
                        'description' => sprintf( 'Creating new %s', $post_type )
                    ) );
                }

                return $context;
            }
        }

        // Post List - Viewing list of posts
        if ( $pagenow === 'edit.php' ) {
            $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post';
            $post_type_object = get_post_type_object( $post_type );
            
            $context['type'] = 'post_list';
            $context['details'] = array(
                'post_type' => $post_type,
                'post_type_label' => $post_type_object ? $post_type_object->labels->name : $post_type,
                'post_type_singular' => $post_type_object ? $post_type_object->labels->singular_name : $post_type,
                'total_posts' => wp_count_posts( $post_type )->publish,
                'description' => sprintf( 'Viewing list of %s', $post_type_object ? $post_type_object->labels->name : $post_type )
            );
            
            // Add filter information if any
            if ( isset( $_GET['post_status'] ) ) {
                $context['details']['filtered_by_status'] = sanitize_text_field( $_GET['post_status'] );
            }
            if ( isset( $_GET['author'] ) ) {
                $author_id = absint( $_GET['author'] );
                $context['details']['filtered_by_author'] = get_the_author_meta( 'display_name', $author_id );
                $context['details']['filtered_by_author_id'] = $author_id;
            }
            
            return $context;
        }

        // Pages List
        if ( $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'page' ) {
            $context['type'] = 'page_list';
            $context['details'] = array(
                'post_type' => 'page',
                'total_pages' => wp_count_posts( 'page' )->publish,
                'description' => 'Viewing list of Pages'
            );
            return $context;
        }

        // Media Library
        if ( $pagenow === 'upload.php' ) {
            $context['type'] = 'media_library';
            $context['details'] = array(
                'description' => 'Media Library - managing files and images'
            );
            return $context;
        }

        // Comments
        if ( $pagenow === 'edit-comments.php' ) {
            $context['type'] = 'comments_list';
            $context['details'] = array(
                'description' => 'Comments management page'
            );
            return $context;
        }

        // Users
        if ( $pagenow === 'users.php' ) {
            $context['type'] = 'users_list';
            $context['details'] = array(
                'description' => 'Users management page'
            );
            return $context;
        }

        // User Profile
        if ( in_array( $pagenow, array( 'profile.php', 'user-edit.php' ) ) ) {
            $user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : get_current_user_id();
            $user = get_userdata( $user_id );
            
            $context['type'] = 'user_profile';
            $context['details'] = array(
                'user_id' => $user_id,
                'user_login' => $user ? $user->user_login : '',
                'display_name' => $user ? $user->display_name : '',
                'description' => $user ? sprintf( 'Editing user profile: %s (ID: %d)', $user->display_name, $user_id ) : 'Editing user profile'
            );
            return $context;
        }

        // Themes
        if ( $pagenow === 'themes.php' ) {
            $context['type'] = 'themes';
            $context['details'] = array(
                'description' => 'Themes management page'
            );
            return $context;
        }

        // Plugins
        if ( $pagenow === 'plugins.php' ) {
            $context['type'] = 'plugins';
            $context['details'] = array(
                'description' => 'Plugins management page'
            );
            return $context;
        }

        // Settings Pages
        if ( $pagenow === 'options-general.php' ) {
            $context['type'] = 'settings_general';
            $context['details'] = array(
                'description' => 'General Settings page'
            );
            return $context;
        }

        // Taxonomies (Categories, Tags, etc.)
        if ( in_array( $pagenow, array( 'edit-tags.php', 'term.php' ) ) ) {
            $taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( $_GET['taxonomy'] ) : 'category';
            $tax_object = get_taxonomy( $taxonomy );
            
            $context['type'] = 'taxonomy_list';
            $context['details'] = array(
                'taxonomy' => $taxonomy,
                'taxonomy_label' => $tax_object ? $tax_object->labels->name : $taxonomy,
                'description' => sprintf( 'Managing %s', $tax_object ? $tax_object->labels->name : $taxonomy )
            );
            return $context;
        }

        // Custom admin pages (SNN Settings, etc.)
        if ( isset( $_GET['page'] ) ) {
            $page_slug = sanitize_text_field( $_GET['page'] );
            $context['type'] = 'admin_page';
            $context['details'] = array(
                'page_slug' => $page_slug,
                'description' => sprintf( 'Admin page: %s', $page_slug )
            );
            return $context;
        }

        // Default fallback
        $context['details']['description'] = 'WordPress Admin Area';
        $context['details']['page'] = $pagenow;
        
        return $context;
    }

    /**
     * Add button to WordPress admin bar (wp-admin only)
     */
    public function add_admin_bar_button( $wp_admin_bar ) {
        // Only show in wp-admin area for logged-in users with edit_posts capability
        if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id'     => 'snn-ai-chat',
            'title'  => '<span style="font-size: 25px; background: linear-gradient(45deg, #2271b1, #ffffff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; position: relative;  line-height: 1.2;">✦</span>',
            'href'   => '#',
            'parent' => 'top-secondary',
            'meta'   => array(
                'class' => 'snn-chat-toggle',
                'title' => 'Open AI Assistant',
            ),
        ) );
    }

    /**
     * Enqueue styles and scripts (wp-admin only)
     */
    public function enqueue_assets() {
        // Double check we're in admin area with proper permissions
        if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Load markdown.js library for chat message rendering
        wp_enqueue_script(
            'markdown-js',
            get_stylesheet_directory_uri() . '/assets/js/markdown.min.js',
            array(),
            '0.5.0',
            true
        );

        // Inline styles
        wp_add_inline_style( 'dashicons', $this->get_inline_css() );

        // Pass configuration to JavaScript
        $ai_config = function_exists( 'snn_get_ai_api_config' ) ? snn_get_ai_api_config() : array();
        
        // Add custom system prompt and token count to config
        $ai_config['systemPrompt'] = $this->get_system_prompt();
        $ai_config['maxTokens'] = $this->get_token_count();
        
        // Get current page context
        $page_context = $this->get_page_context();
        
        wp_localize_script( 'jquery', 'snnChatConfig', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'restUrl'       => rest_url( 'wp-abilities/v1/' ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'agentNonce'    => wp_create_nonce( 'snn_ai_agent_nonce' ),
            'currentUserId' => get_current_user_id(),
            'userName'      => wp_get_current_user()->display_name,
            'pageContext'   => $page_context,
            'ai'            => $ai_config,
            'settings'      => array(
                'enabledAbilities'  => $this->get_enabled_abilities(),
                'debugMode'         => $this->is_debug_enabled(),
                'maxRetries'        => $this->get_max_retries(),
                'maxHistory'        => $this->get_max_history(),
            ),
        ) );
    }

    /**
     * Render overlay HTML (wp-admin only)
     */
    public function render_overlay() {
        // Double check we're in admin area with proper permissions
        if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
            return;
        }
        ?>
        <div id="snn-chat-overlay" class="snn-chat-overlay" style="display: none;">
            <div class="snn-chat-container">
                <!-- Header -->
                <div class="snn-chat-header">
                    <div class="snn-chat-title">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <span>SNN AI Agent</span>
                        <span class="snn-agent-state-badge" id="snn-agent-state-badge"></span>
                    </div>
                    <div class="snn-chat-controls">
                        <button class="snn-chat-btn snn-chat-new" title="New chat" id="snn-chat-new-btn">
                            <span class="snn-chat-plus">+</span>
                        </button>
                        <button class="snn-chat-btn snn-chat-history" title="Chat history" id="snn-chat-history-btn">
                            <span class="dashicons dashicons-backup"></span>
                        </button>
                        <button class="snn-chat-btn snn-chat-expand" title="Toggle width" id="snn-chat-expand-btn">
                            <span class="snn-expand-icon">&#x27F7;</span>
                        </button>
                        <button class="snn-chat-btn snn-chat-close" title="Close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>

                <!-- History Dropdown -->
                <div class="snn-chat-history-dropdown" id="snn-chat-history-dropdown" style="display: none;">
                    <div class="snn-history-header">
                        <strong><?php echo esc_html__('Chat History', 'snn'); ?></strong>
                        <button class="snn-history-close" id="snn-history-close">×</button>
                    </div>
                    <div class="snn-history-list" id="snn-history-list">
                        <div class="snn-history-loading"><?php echo esc_html__('Loading...', 'snn'); ?></div>
                    </div>
                </div>

                <?php if ( ! $this->is_ai_globally_enabled() ) : ?>
                <!-- AI Features Disabled Warning -->
                <div class="snn-chat-messages" id="snn-chat-messages">
                    <div class="snn-chat-ai-disabled-warning">
                        <div class="snn-warning-icon">⚠️</div>
                        <h3><?php echo esc_html__( 'AI Features Disabled', 'snn' ); ?></h3>
                        <p><?php echo esc_html__( 'The global AI Features setting is currently disabled. Please enable it to use the AI chat assistant.', 'snn' ); ?></p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=snn-ai-settings' ) ); ?>" class="snn-enable-ai-btn">
                            <?php echo esc_html__( 'Go to AI Settings', 'snn' ); ?> →
                        </a>
                    </div>
                </div>

                <!-- Input (disabled) -->
                <div class="snn-chat-input-container">
                    <textarea
                        id="snn-chat-input"
                        class="snn-chat-input"
                        placeholder="<?php echo esc_attr__( 'AI features are disabled...', 'snn' ); ?>"
                        rows="1"
                        disabled
                    ></textarea>
                    <button id="snn-chat-send" class="snn-chat-send" title="Send message" disabled>
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                <?php else : ?>
                <!-- Messages -->
                <div class="snn-chat-messages" id="snn-chat-messages">
                    <div class="snn-chat-welcome">
                        <h3>Hello, <?php echo esc_html( wp_get_current_user()->display_name ); ?>!</h3>
                        <br><p><small>Type a message to get started.</small></p>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="snn-chat-typing" style="display: none;">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>

                <!-- State Indicator -->
                <div class="snn-chat-state-text" id="snn-chat-state-text"></div>

                <!-- Quick Actions -->
                <div class="snn-chat-quick-actions">
                    <button class="snn-quick-action-btn" data-message="List all available abilities">List Abilities</button>
                    <button class="snn-quick-action-btn" data-message="List all users">List Users</button>
                    <button class="snn-quick-action-btn" data-message="Show detailed site health report including PHP, server, database, and security info">Site Health Report</button>
                </div>

                <!-- Input -->
                <div class="snn-chat-input-container">
                    <textarea
                        id="snn-chat-input"
                        class="snn-chat-input"
                        placeholder="Ask me anything..."
                        rows="1"
                    ></textarea>
                    <button id="snn-chat-send" class="snn-chat-send" title="Send message">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function($) {
            'use strict';

            // Agent states enum
            const AgentState = {
                IDLE: 'idle',
                THINKING: 'thinking',
                EXECUTING: 'executing',
                INTERPRETING: 'interpreting',
                RETRYING: 'retrying',
                RECOVERING: 'recovering',
                DONE: 'done',
                ERROR: 'error'
            };

            // Configuration from settings
            const MAX_RETRIES = snnChatConfig.settings.maxRetries || 3;
            const MAX_HISTORY = snnChatConfig.settings.maxHistory || 20;
            const DEBUG_MODE = snnChatConfig.settings.debugMode || false;
            const ENABLED_ABILITIES = snnChatConfig.settings.enabledAbilities || [];
            
            // Error recovery configuration
            const RECOVERY_CONFIG = {
                maxRecoveryAttempts: 3,
                baseDelay: 2000, // 2 seconds base delay
                maxDelay: 30000, // 30 seconds max delay
                rateLimitDelay: 5000 // 5 seconds for 429 errors
            };

            // Debug console wrapper
            const debugLog = function(...args) {
                if (DEBUG_MODE) {
                    console.log(...args);
                }
            };

            // Chat state
            const ChatState = {
                messages: [],
                abilities: [],
                isOpen: false,
                isExpanded: false,
                isProcessing: false,
                abortController: null,
                currentState: AgentState.IDLE,
                currentAbility: null,
                currentSessionId: null,
                autoSaveTimer: null,
                pageContext: snnChatConfig && snnChatConfig.pageContext ? snnChatConfig.pageContext : { type: 'unknown', details: {} },
                recoveryAttempts: 0,
                lastError: null,
                pendingOperation: null
            };

            // Block Editor Integration
            const BlockEditorHelper = {
                /**
                 * Check if block editor is available
                 */
                isAvailable() {
                    return typeof wp !== 'undefined' &&
                           typeof wp.data !== 'undefined' &&
                           typeof wp.blocks !== 'undefined';
                },

                /**
                 * Get current post content from block editor
                 */
                getCurrentContent() {
                    if (!this.isAvailable()) return null;

                    try {
                        const editor = wp.data.select('core/editor');
                        if (!editor) return null;

                        const content = editor.getEditedPostContent();
                        // Calculate word count manually since getDocumentInfo() doesn't exist
                        const textContent = content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                        const wordCount = textContent ? textContent.split(/\s+/).length : 0;

                        return {
                            raw: content,
                            blocks: wp.data.select('core/block-editor').getBlocks(),
                            title: editor.getEditedPostAttribute('title'),
                            excerpt: editor.getEditedPostAttribute('excerpt'),
                            wordCount: wordCount
                        };
                    } catch (e) {
                        console.error('Failed to get editor content:', e);
                        return null;
                    }
                },

                /**
                 * Get selected block info
                 */
                getSelectedBlock() {
                    if (!this.isAvailable()) return null;

                    try {
                        return wp.data.select('core/block-editor').getSelectedBlock();
                    } catch (e) {
                        console.error('Failed to get selected block:', e);
                        return null;
                    }
                },

                /**
                 * Insert blocks into editor
                 */
                insertBlocks(blockData, position = 'end') {
                    if (!this.isAvailable()) {
                        throw new Error('Block editor not available');
                    }

                    try {
                        const { insertBlocks } = wp.data.dispatch('core/block-editor');
                        const { createBlock } = wp.blocks;

                        const blocks = Array.isArray(blockData) ? blockData : [blockData];
                        const createdBlocks = blocks.map(data => {
                            return createBlock(data.name || 'core/paragraph', data.attributes || {}, data.innerBlocks || []);
                        });

                        if (position === 'end') {
                            insertBlocks(createdBlocks);
                        } else {
                            insertBlocks(createdBlocks, position);
                        }

                        return { success: true, message: `Inserted ${createdBlocks.length} block(s)` };
                    } catch (e) {
                        console.error('Failed to insert blocks:', e);
                        throw new Error('Failed to insert blocks: ' + e.message);
                    }
                },

                /**
                 * Replace selected block
                 */
                replaceSelectedBlock(blockData) {
                    if (!this.isAvailable()) {
                        throw new Error('Block editor not available');
                    }

                    try {
                        const selectedBlock = this.getSelectedBlock();
                        if (!selectedBlock) {
                            throw new Error('No block selected');
                        }

                        const { replaceBlocks } = wp.data.dispatch('core/block-editor');
                        const { createBlock } = wp.blocks;

                        const newBlock = createBlock(
                            blockData.name || 'core/paragraph',
                            blockData.attributes || {},
                            blockData.innerBlocks || []
                        );

                        replaceBlocks(selectedBlock.clientId, newBlock);

                        return { success: true, message: 'Block replaced successfully' };
                    } catch (e) {
                        console.error('Failed to replace block:', e);
                        throw new Error('Failed to replace block: ' + e.message);
                    }
                },

                /**
                 * Append content to current post
                 */
                appendContent(content, blockType = 'core/paragraph') {
                    return this.insertBlocks({
                        name: blockType,
                        attributes: { content: content }
                    }, 'end');
                },

                /**
                 * Save post
                 */
                savePost() {
                    if (!this.isAvailable()) {
                        throw new Error('Block editor not available');
                    }

                    try {
                        const { savePost } = wp.data.dispatch('core/editor');
                        savePost();
                        return { success: true, message: 'Post save initiated' };
                    } catch (e) {
                        console.error('Failed to save post:', e);
                        throw new Error('Failed to save post: ' + e.message);
                    }
                }
            };

            // Enhance page context with block editor info
            function enhancePageContext() {
                if (snnChatConfig.pageContext && snnChatConfig.pageContext.details && snnChatConfig.pageContext.details.has_block_editor) {
                    const content = BlockEditorHelper.getCurrentContent();
                    if (content) {
                        snnChatConfig.pageContext.details.current_content = content.raw.substring(0, 500); // Preview
                        snnChatConfig.pageContext.details.current_title = content.title;
                        snnChatConfig.pageContext.details.current_excerpt = content.excerpt;
                        snnChatConfig.pageContext.details.current_word_count = content.wordCount;
                        snnChatConfig.pageContext.details.block_count = content.blocks ? content.blocks.length : 0;

                        const selectedBlock = BlockEditorHelper.getSelectedBlock();
                        if (selectedBlock) {
                            snnChatConfig.pageContext.details.selected_block = {
                                name: selectedBlock.name,
                                attributes: selectedBlock.attributes
                            };
                        }
                    }
                }
            }

            // Initialize
            $(document).ready(function() {
                initChat();
                loadAbilities();

                // Enhance context if in block editor
                if (BlockEditorHelper.isAvailable()) {
                    setTimeout(enhancePageContext, 1000); // Wait for editor to load
                }
            });

            /**
             * Initialize chat interface
             */
            function initChat() {
                // Toggle overlay
                $('.snn-chat-toggle, .snn-chat-close').on('click', function(e) {
                    e.preventDefault();
                    toggleChat();
                });

                // New chat button
                $('#snn-chat-new-btn').on('click', function() {
                    clearChat();
                });

                // History button
                $('#snn-chat-history-btn').on('click', function() {
                    toggleHistoryDropdown();
                });

                $('#snn-history-close').on('click', function() {
                    $('#snn-chat-history-dropdown').hide();
                });

                // Expand/collapse width toggle button
                $('#snn-chat-expand-btn').on('click', function() {
                    toggleChatWidth();
                });

                // Send message
                $('#snn-chat-send').on('click', sendMessage);
                
                // Send on Enter (Shift+Enter for newline)
                $('#snn-chat-input').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });

                // Auto-resize textarea
                $('#snn-chat-input').on('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                // Quick action buttons
                $('.snn-quick-action-btn').on('click', function() {
                    const message = $(this).data('message');
                    $('#snn-chat-input').val(message);
                    sendMessage();
                });

                // Auto-save conversation periodically
                setInterval(autoSaveConversation, 30000); // Every 30 seconds
            }

            /**
             * Toggle chat overlay
             */
            function toggleChat() {
                ChatState.isOpen = !ChatState.isOpen;
                $('#snn-chat-overlay').toggle();

                if (ChatState.isOpen) {
                    $('#snn-chat-input').focus();
                }
            }

            /**
             * Toggle chat width between normal and expanded
             */
            function toggleChatWidth() {
                ChatState.isExpanded = !ChatState.isExpanded;
                const $container = $('.snn-chat-container');

                if (ChatState.isExpanded) {
                    $container.addClass('snn-chat-expanded');
                } else {
                    $container.removeClass('snn-chat-expanded');
                }
            }

            /**
             * Load available abilities from API
             */
            async function loadAbilities() {
                try {
                    const response = await fetch(snnChatConfig.restUrl + 'abilities', {
                        headers: {
                            'X-WP-Nonce': snnChatConfig.nonce
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        // WordPress Abilities API returns an array of ability objects
                        // Filter by enabled abilities from settings
                        const allAbilities = Array.isArray(data) ? data : [];
                        ChatState.abilities = allAbilities.filter(a => ENABLED_ABILITIES.includes(a.name));
                        
                        debugLog('✓ Loaded abilities:', ChatState.abilities.length);
                        if (ChatState.abilities.length > 0) {
                            debugLog('Abilities:', ChatState.abilities.map(a => a.name).join(', '));
                            debugLog('Full abilities data:', ChatState.abilities);
                        } else {
                            console.warn('No abilities enabled or found. Check AI Agent settings.');
                        }
                    } else {
                        console.error('Failed to load abilities:', response.status, await response.text());
                    }
                } catch (error) {
                    console.error('Failed to load abilities:', error);
                    console.error('Make sure WordPress 6.9+ is installed and Abilities API is available');
                }
            }

            /**
             * Send user message
             */
            async function sendMessage() {
                const input = $('#snn-chat-input');
                const message = input.val().trim();

                if (!message || ChatState.isProcessing) {
                    return;
                }

                // Add user message
                addMessage('user', message);
                input.val('').css('height', 'auto');

                // Process with AI
                await processWithAI(message);
            }

            /**
             * Process message with AI agent with error recovery
             */
            async function processWithAI(userMessage) {
                ChatState.isProcessing = true;
                ChatState.recoveryAttempts = 0;
                showTyping();
                setAgentState(AgentState.THINKING);

                try {
                    // Store pending operation for recovery
                    ChatState.pendingOperation = {
                        type: 'processMessage',
                        message: userMessage,
                        timestamp: Date.now()
                    };
                    
                    // Prepare conversation context (use MAX_HISTORY setting)
                    const context = ChatState.messages.slice(-MAX_HISTORY).map(m => {
                        let content = m.content;

                        // If this message had ability executions, include results in context
                        if (m.metadata && m.metadata.length > 0) {
                            // Check if this is an interpretation message
                            const isInterpretation = m.metadata.some(md => md.type === 'interpretation');
                            
                            if (!isInterpretation) {
                                // This message has actual execution results
                                const resultsText = m.metadata.map(r => {
                                    if (r.result && r.result.success && r.result.data) {
                                        return `[Executed ${r.ability}: ${JSON.stringify(r.result.data).substring(0, 200)}]`;
                                    } else if (r.result && !r.result.success) {
                                        return `[Failed ${r.ability}: ${r.result.error || 'Unknown error'}]`;
                                    }
                                    return '';
                                }).filter(Boolean).join(' ');

                                if (resultsText) {
                                    content = content + '\n\nExecution results: ' + resultsText;
                                }
                            } else {
                                // Keep final summaries (they provide valuable context about what was done)
                                // Skip only individual task interpretations
                                const isFinalSummary = m.metadata.some(md => md.phase === 'final_summary');
                                if (!isFinalSummary) {
                                    // Skip individual interpretation messages
                                    return null;
                                }
                                // Final summaries are kept as-is for context
                            }
                        }

                        return {
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: content
                        };
                    }).filter(msg => msg !== null); // Remove null entries (skipped interpretations)

                    // Build AI prompt with abilities
                    const systemPrompt = buildSystemPrompt();
                    const messages = [
                        { role: 'system', content: systemPrompt },
                        ...context
                    ];

                    // Call AI API for initial planning
                    const aiResponse = await callAI(messages);

                    hideTyping();

                    // Extract abilities from response
                    const abilities = extractAbilitiesFromResponse(aiResponse);

                    if (abilities.length > 0) {
                        // Show initial AI message (without JSON block)
                        let initialMessage = aiResponse.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                        if (initialMessage) {
                            addMessage('assistant', initialMessage);
                        }

                        // Execute abilities sequentially with AI interpretation after each
                        await executeAbilitiesSequentially(messages, abilities);

                        // After all tasks complete, get final summary from AI
                        await provideFinalSummary(messages, abilities);
                    } else {
                        // No abilities to execute, just show AI response
                        addMessage('assistant', aiResponse);
                    }

                    // Mark as done
                    setAgentState(AgentState.DONE);
                    
                    // Clear pending operation on success
                    ChatState.pendingOperation = null;
                    
                    // Auto-save conversation
                    autoSaveConversation();
                } catch (error) {
                    hideTyping();
                    
                    // Try to recover from the error
                    const recovered = await attemptRecovery(error, userMessage);
                    
                    if (!recovered) {
                        // Recovery failed, show error to user
                        let errorMessage = 'Sorry, something went wrong: ' + error.message;
                        
                        // Add helpful suggestions based on error type
                        if (error.message.includes('429') || error.message.includes('Rate limit')) {
                            errorMessage += '\n\n💡 **Tip:** The AI service is currently rate-limited. Please wait a moment before trying again.';
                        } else if (error.message.includes('network') || error.message.includes('fetch')) {
                            errorMessage += '\n\n💡 **Tip:** Check your internet connection and try again.';
                        } else if (error.message.includes('API not configured')) {
                            errorMessage += '\n\n💡 **Tip:** Please configure your AI API settings first.';
                        }
                        
                        addMessage('error', errorMessage);
                        setAgentState(AgentState.ERROR, { error: error.message });
                    }
                } finally {
                    ChatState.isProcessing = false;
                }
            }
            
            /**
             * Attempt to recover from an error
             */
            async function attemptRecovery(error, userMessage) {
                ChatState.recoveryAttempts++;
                
                if (ChatState.recoveryAttempts > RECOVERY_CONFIG.maxRecoveryAttempts) {
                    debugLog('❌ Max recovery attempts reached, giving up');
                    return false;
                }
                
                debugLog(`🔄 Attempting recovery (${ChatState.recoveryAttempts}/${RECOVERY_CONFIG.maxRecoveryAttempts})...`);
                
                // Calculate delay based on error type
                let delay = RECOVERY_CONFIG.baseDelay;
                
                if (error.message.includes('429') || error.message.includes('Rate limit')) {
                    delay = RECOVERY_CONFIG.rateLimitDelay * Math.pow(2, ChatState.recoveryAttempts - 1);
                } else if (error.message.includes('500') || error.message.includes('503')) {
                    delay = RECOVERY_CONFIG.baseDelay * Math.pow(2, ChatState.recoveryAttempts - 1);
                }
                
                delay = Math.min(delay, RECOVERY_CONFIG.maxDelay);
                
                setAgentState(AgentState.RECOVERING, {
                    reason: error.message,
                    delay: delay,
                    attempt: ChatState.recoveryAttempts,
                    maxAttempts: RECOVERY_CONFIG.maxRecoveryAttempts
                });
                
                showTyping();
                await sleep(delay);
                
                try {
                    // Retry the operation
                    if (ChatState.pendingOperation && ChatState.pendingOperation.type === 'processMessage') {
                        debugLog('🔄 Retrying message processing...');
                        
                        // Don't call processWithAI recursively, just retry the core logic
                        setAgentState(AgentState.THINKING);
                        
                        const context = ChatState.messages.slice(-MAX_HISTORY).map(m => {
                            let content = m.content;
                            if (m.metadata && m.metadata.length > 0) {
                                // Check if this is an interpretation message
                                const isInterpretation = m.metadata.some(md => md.type === 'interpretation');
                                
                                if (!isInterpretation) {
                                    const resultsText = m.metadata.map(r => {
                                        if (r.result && r.result.success && r.result.data) {
                                            return `[Executed ${r.ability}: ${JSON.stringify(r.result.data).substring(0, 200)}]`;
                                        } else if (r.result && !r.result.success) {
                                            return `[Failed ${r.ability}: ${r.result.error || 'Unknown error'}]`;
                                        }
                                        return '';
                                    }).filter(Boolean).join(' ');
                                    if (resultsText) {
                                        content = content + '\n\nExecution results: ' + resultsText;
                                    }
                                } else {
                                    // Keep final summaries, skip individual interpretations
                                    const isFinalSummary = m.metadata.some(md => md.phase === 'final_summary');
                                    if (!isFinalSummary) {
                                        return null;
                                    }
                                }
                            }
                            return {
                                role: m.role === 'user' ? 'user' : 'assistant',
                                content: content
                            };
                        }).filter(msg => msg !== null);
                        
                        const systemPrompt = buildSystemPrompt();
                        const messages = [
                            { role: 'system', content: systemPrompt },
                            ...context
                        ];
                        
                        const aiResponse = await callAI(messages);
                        hideTyping();
                        
                        const abilities = extractAbilitiesFromResponse(aiResponse);
                        
                        if (abilities.length > 0) {
                            let initialMessage = aiResponse.replace(/```json\n?[\s\S]*?\n?```/g, '').trim();
                            if (initialMessage) {
                                addMessage('assistant', initialMessage);
                            }
                            await executeAbilitiesSequentially(messages, abilities);
                            await provideFinalSummary(messages, abilities);
                        } else {
                            addMessage('assistant', aiResponse);
                        }
                        
                        setAgentState(AgentState.DONE);
                        ChatState.pendingOperation = null;
                        ChatState.recoveryAttempts = 0;
                        autoSaveConversation();
                        
                        debugLog('✅ Recovery successful!');
                        return true;
                    }
                } catch (retryError) {
                    debugLog('❌ Recovery attempt failed:', retryError.message);
                    ChatState.lastError = retryError;
                    
                    // Recursive recovery attempt
                    return await attemptRecovery(retryError, userMessage);
                }
                
                return false;
            }

            /**
             * Build system prompt with available abilities
             */
            function buildSystemPrompt() {
                const basePrompt = snnChatConfig.ai.systemPrompt || 'You are a helpful WordPress assistant.';
                
                // Build page context information
                let pageContextInfo = '';
                if (snnChatConfig && snnChatConfig.pageContext && snnChatConfig.pageContext.type && snnChatConfig.pageContext.type !== 'unknown') {
                    const ctx = snnChatConfig.pageContext;
                    pageContextInfo = `\n\n=== CURRENT PAGE CONTEXT ===\n\n`;
                    pageContextInfo += `The user is currently on: ${ctx.details.description || ctx.type}\n`;
                    pageContextInfo += `Page Type: ${ctx.type}\n`;
                    
                    // Add specific details based on context type
                    if (ctx.type === 'post_editor' && ctx.details.post_id) {
                        pageContextInfo += `\n**Currently Editing Post:**\n`;
                        pageContextInfo += `- Post ID: ${ctx.details.post_id}\n`;
                        pageContextInfo += `- Post Type: ${ctx.details.post_type}\n`;
                        pageContextInfo += `- Title: "${ctx.details.post_title}"\n`;
                        pageContextInfo += `- Status: ${ctx.details.post_status}\n`;
                        pageContextInfo += `- Author: ${ctx.details.post_author}\n`;

                        // Add block editor info if available
                        if (ctx.details.has_block_editor) {
                            pageContextInfo += `- Using Block Editor: Yes\n`;
                            if (ctx.details.has_blocks) {
                                pageContextInfo += `- Has Blocks: Yes\n`;
                            }
                            if (ctx.details.word_count) {
                                pageContextInfo += `- Word Count: ${ctx.details.word_count}\n`;
                            }
                            if (ctx.details.current_word_count) {
                                pageContextInfo += `- Current Word Count (live): ${ctx.details.current_word_count}\n`;
                            }
                            if (ctx.details.block_count) {
                                pageContextInfo += `- Block Count: ${ctx.details.block_count}\n`;
                            }
                            if (ctx.details.selected_block) {
                                pageContextInfo += `- Selected Block: ${ctx.details.selected_block.name}\n`;
                            }

                            pageContextInfo += `\n**BLOCK EDITOR CAPABILITIES:**\n`;
                            pageContextInfo += `You can interact with the block editor using these abilities:\n`;
                            pageContextInfo += `- snn/generate-block-pattern: Insert new block patterns into the post\n`;
                            pageContextInfo += `- snn/generate-block-pattern: Add content to the end of the post\n`;
                            pageContextInfo += `- snn/get-post-content: Read the current post content\n`;
                            pageContextInfo += `- snn/update-post-metadata: Update title, excerpt, status, categories, tags\n`;
                            pageContextInfo += `- snn/analyze-post-seo: Analyze SEO quality of the current post\n`;
                            pageContextInfo += `\nWhen the user asks to "add content", "insert text", "write about", etc., use the insert or append abilities with the post_id ${ctx.details.post_id}.\n`;
                        }

                        pageContextInfo += `\n**IMPORTANT:** When the user asks about "this post", "the post", "current post", "my content", etc., they are referring to Post ID ${ctx.details.post_id} ("${ctx.details.post_title}"). Use this exact Post ID without asking for clarification.\n`;
                    } else if (ctx.type === 'post_list' && ctx.details.post_type) {
                        pageContextInfo += `\n**Currently Viewing Post List:**\n`;
                        pageContextInfo += `- Post Type: ${ctx.details.post_type}\n`;
                        pageContextInfo += `- Post Type Label: ${ctx.details.post_type_label}\n`;
                        pageContextInfo += `- Total Posts: ${ctx.details.total_posts}\n`;
                        if (ctx.details.filtered_by_status) {
                            pageContextInfo += `- Filtered by Status: ${ctx.details.filtered_by_status}\n`;
                        }
                        if (ctx.details.filtered_by_author) {
                            pageContextInfo += `- Filtered by Author: ${ctx.details.filtered_by_author} (ID: ${ctx.details.filtered_by_author_id})\n`;
                        }
                        pageContextInfo += `\n**IMPORTANT:** When the user asks about "posts", "these posts", "my posts", "SEO", "content", etc., they are referring to the "${ctx.details.post_type}" post type. Use this exact post type without asking for clarification. Do NOT analyze all post types - focus only on "${ctx.details.post_type}".\n`;
                    } else if (ctx.type === 'user_profile' && ctx.details.user_id) {
                        pageContextInfo += `\n**Currently Editing User:**\n`;
                        pageContextInfo += `- User ID: ${ctx.details.user_id}\n`;
                        pageContextInfo += `- Username: ${ctx.details.user_login}\n`;
                        pageContextInfo += `- Display Name: ${ctx.details.display_name}\n`;
                        pageContextInfo += `\n**IMPORTANT:** When the user asks about "this user", "the user", "their profile", etc., they are referring to User ID ${ctx.details.user_id} (${ctx.details.display_name}).\n`;
                    } else if (ctx.details.post_type) {
                        pageContextInfo += `- Related Post Type: ${ctx.details.post_type}\n`;
                    }
                    
                    pageContextInfo += `\nUse this context to provide more relevant and specific assistance without asking unnecessary questions. If the user's request is clearly related to the current page context, use that information directly.\n`;
                }
                
                if (ChatState.abilities.length === 0) {
                    return `${basePrompt}${pageContextInfo}\n\nNote: No WordPress abilities are currently available. Make sure abilities are registered with show_in_rest enabled.`;
                }

                // Generate a list of abilities with descriptions
                const abilitiesList = ChatState.abilities.map(ability => {
                    return `- **${ability.name}**: ${ability.description || ability.label || 'No description'} (Category: ${ability.category || 'uncategorized'})`;
                }).join('\n');

                // Generate detailed ability descriptions with parameters
                const abilitiesDesc = ChatState.abilities.map(ability => {
                    let params = '    (No parameters)';
                    
                    if (ability.input_schema) {
                        if (ability.input_schema.properties) {
                            // Object type with properties
                            params = Object.entries(ability.input_schema.properties).map(([key, val]) => {
                                const isRequired = ability.input_schema.required?.includes(key) ? ' (required)' : '';
                                const defaultVal = val.default !== undefined ? ` [default: ${JSON.stringify(val.default)}]` : '';
                                const enumVals = val.enum ? ` [options: ${val.enum.join(', ')}]` : '';
                                return `    - ${key} (${val.type}${isRequired}): ${val.description || ''}${defaultVal}${enumVals}`;
                            }).join('\n');
                        } else if (ability.input_schema.type) {
                            // Simple type (string, integer, etc.)
                            params = `    Type: ${ability.input_schema.type}${ability.input_schema.description ? ' - ' + ability.input_schema.description : ''}`;
                        }
                    }
                    
                    return `**${ability.name}** - ${ability.description || ability.label || 'No description'}
  Category: ${ability.category || 'uncategorized'}
  Parameters:
${params}`;
                }).join('\n\n');

                return `${basePrompt}${pageContextInfo}

IMPORTANT: You are an AI assistant with the ability to execute WordPress actions through the WordPress Core Abilities API.

=== YOUR CAPABILITIES ===

You have ${ChatState.abilities.length} WordPress Core abilities available:

${abilitiesList}

When users ask "what can you do" or "what are your capabilities", list these abilities and explain what each one does.

=== AVAILABLE ABILITIES (DETAILED) ===

${abilitiesDesc}

=== CONTENT EDITING: BLOCK EDITOR VS DATABASE ===

**CRITICAL: Choose the RIGHT ability based on context:**

${ChatState.pageContext && ChatState.pageContext.type === 'post_editor' ? `
🟢 **YOU ARE CURRENTLY IN THE BLOCK EDITOR**
- User is editing: "${ChatState.pageContext.details.description || 'a post'}"
- Post ID: ${ChatState.pageContext.details.post_id || 'unknown'}
- For content updates, USE "snn/edit-block-content" - it updates the editor in REAL-TIME
- Benefits: User sees changes immediately, can iterate, no page refresh needed
- NEVER use "snn/replace-post-content" when user is actively editing in the block editor

**When to use each ability:**
- "snn/edit-block-content" → For FULL content operations (replace all, append, prepend)
- "snn/edit-block-content" → For SURGICAL edits (update specific blocks, insert between blocks, delete specific sections)
- "snn/generate-block-pattern" → For CREATING complete new sections and designs from scratch (hero, services, CTA, etc.)
- "snn/replace-post-content" → When editing posts NOT currently open in editor

**CRITICAL: Choose the RIGHT content editing ability:**

1. **"snn/generate-block-pattern"** - Use when CREATING NEW complete sections:
   - User asks: "add a hero section", "create a services grid", "generate a testimonials section"
   - Creates from scratch with styling, spacing, and structure
   - Actions: replace (all content), append (to end), prepend (to start)
   - **CRITICAL USAGE:** ONE ability call generates ONE complete section/pattern
     * "create a homepage" → Execute 3-5 SEPARATE calls (hero, services, about, testimonials, CTA) with action_type:"append"
     * "add more sections" → Execute 2-3 SEPARATE calls for additional sections with action_type:"append"
     * Each pattern_type (hero, services, cta, etc.) should be a SEPARATE ability execution
     * NEVER try to describe multiple sections in one content_description

2. **"snn/edit-block-content"** - Use for SPECIFIC/SURGICAL modifications:
   - User asks: "update the About Us section", "change the pricing table", "remove the third FAQ"
   - User asks: "insert testimonial after services", "add a CTA between hero and about"
   - User asks: "replace all Read More with Learn More", "delete blocks 5-7"
   - Actions: insert_at_index, replace_block_range, delete_blocks, find_and_replace_section, find_and_replace_text
   - Examples:
     * "update the Who We Are section" → use find_and_replace_section action
     * "add testimonial after hero" → use insert_at_index action
     * "remove third item" → use delete_blocks action
     * "change button text from X to Y" → use find_and_replace_text action

3. **"snn/edit-block-content"** - Use for FULL content operations:
   - User asks: "replace all content", "clear everything and add...", "add to the end"
   - Actions: replace (all), append (to end), prepend (to start), update_section (legacy - prefer snn/edit-block-content)
   - Note: update_section action still works but prefer snn/edit-block-content for surgical edits

**Decision Tree:**
- Creating NEW complete section (hero/services/CTA)? → snn/generate-block-pattern
- Modifying EXISTING specific content? → snn/edit-block-content
- Replacing/appending ALL content? → snn/edit-block-content

**IMPORTANT: Real-Time Metadata Updates**
When using "snn/update-post-metadata" to update title, excerpt, or status:
- The ability should return a client_command to update the editor state in real-time
- Example client_command format:
  {
    "type": "update_post_metadata",
    "post_id": ${ChatState.pageContext.details.post_id || 'POST_ID'},
    "title": "New Title",
    "excerpt": "New excerpt",
    "status": "publish"
  }
- This ensures the user sees the changes immediately without needing to save/refresh
- Include ONLY the fields that were updated (title, excerpt, status, featured_media)
` : `
⚪ **YOU ARE NOT IN THE BLOCK EDITOR**
- Use "snn/replace-post-content" for editing posts (updates database directly)
- Use "snn/edit-block-content" ONLY when user is actively editing a post
`}

=== BLOCK GENERATION RULES: PREVENT BROKEN BLOCKS ===

**CRITICAL: Always Generate Valid HTML to Prevent Block Recovery Errors**

When generating content for ANY block editor ability (update-editor-content, insert-block-content, append-content-to-post, create-post, update-post), follow these rules:

**Golden Rules:**
1. ✅ ALL HTML tags MUST be properly closed: <p>Text</p> NOT <p>Text
2. ✅ Use proper nesting: <p><strong>Bold</strong></p> NOT <strong><p>Bold</p></strong>
3. ✅ Lists MUST use structure: <ul><li>Item 1</li><li>Item 2</li></ul>
4. ✅ NO empty blocks: <p></p> (remove these)
5. ✅ NO loose text - wrap everything in block elements
6. ✅ Images need alt: <img src="url.jpg" alt="Description">
7. ✅ Encode special characters: &amp; for &, &lt; for <

**Good Examples:**
- Section: <h2>About Us</h2><p>We innovate.</p><ul><li>Quality</li><li>Service</li></ul>
- Multiple paragraphs: <p>First.</p><p>Second with <strong>bold</strong>.</p>
- Quote: <blockquote><p>Quote text here</p></blockquote>

**Bad Examples (will cause broken blocks):**
- ❌ <p>Unclosed tag <p>Another paragraph
- ❌ <ul>Item 1 Item 2</ul>
- ❌ Some text <p>A paragraph</p> More text
- ❌ <p></p>

WordPress uses wp.blocks.parse() to convert your HTML to blocks. Invalid HTML = broken blocks = "attempt recovery" button.

=== EXECUTION PHILOSOPHY: DO EXACTLY WHAT IS ASKED ===

**CRITICAL BEHAVIOR RULES:**

1. **DO EXACTLY WHAT THE USER ASKS - NO MORE, NO LESS**
   - If user asks to "list abilities" or "what can you do" → Just describe them in text, DO NOT execute any abilities
   - If user asks to "get site info" → Execute ONE appropriate ability, not multiple
   - If user asks to "do X and Y" → Do both X and Y, but nothing extra
   - NEVER chain additional abilities beyond what was explicitly requested
   - NEVER "demonstrate" abilities by executing them when user just asked about them

2. **DISTINGUISH between INFORMATIONAL requests and ACTION requests:**
   - "What can you do?" / "List abilities" / "Show capabilities" → INFORMATIONAL - respond with text description only, NO ability execution
   - "Get site info" / "List users" / "Create a post" → ACTION - execute the appropriate ability
   - When in doubt, treat it as informational

3. **NEVER ASSUME WORK IS ALREADY DONE - ALWAYS EXECUTE WHEN ASKED:**
   - Even if previous messages mention completing similar tasks, ALWAYS execute new requests
   - "Add more sections" → EXECUTE snn/generate-block-pattern with NEW content, don't just acknowledge
   - "Create another post" → EXECUTE the ability, don't assume it's done
   - Your previous acknowledgments are NOT the same as actual execution
   - CRITICAL: If the user asks you to DO something, you MUST include a JSON code block with abilities to execute
   - A conversational response without a JSON block means NOTHING WAS ACTUALLY DONE

4. **NEVER ASK CLARIFYING QUESTIONS FOR CONTENT GENERATION - JUST DO IT:**
   - User: "create a homepage" → Execute immediately with hero, services, about, testimonials, CTA sections
   - User: "add more sections" → Execute immediately with 2-3 additional relevant sections (gallery, team, faq, stats, etc.)
   - User: "create a blog post about X" → Execute immediately with reasonable content
   - User: "make it better" → Execute improvements immediately
   - ONLY ask questions if literally impossible to proceed (e.g., "create a post" with no topic given)
   - For content/design work, USE YOUR CREATIVITY - don't ask permission
   - Default to comprehensive solutions: if asked for "a homepage", include 4-6 sections automatically

5. **Use sensible defaults when executing:**
   - Content generation: Create rich, complete content automatically
   - Multiple sections: Default to 3-5 sections for homepages, 2-3 for "add more"
   - Styling: Use modern, professional defaults
   - When listing items, default to reasonable limits (e.g., 100 for users)
   - When filtering is optional, assume "all" unless specifically asked to filter

6. **Examples of CORRECT behavior:**
   - User: "create a homepage for flower shop" → Execute 4-5 section patterns immediately (hero, services, about, testimonials, cta)
   - User: "add more sections" → Execute 2-3 additional patterns immediately (gallery, team, faq)
   - User: "list all abilities" → Respond with text list, NO execution
   - User: "get site info and list users" → Execute both abilities

7. **Examples of INCORRECT behavior (DON'T DO THIS):**
   - User: "create a homepage" → ❌ Asking "what sections would you like?" (WRONG - just do it!)
   - User: "add more sections" → ❌ Asking "what kind of sections?" (WRONG - pick good ones and execute!)
   - User: "make a blog post" → ❌ Asking "what should it be about?" (WRONG - only if topic completely unclear)
   - User: "list abilities" → ❌ Executing abilities to demonstrate (WRONG)

=== HOW TO USE ABILITIES ===

When the user asks you to perform a task that matches one of these abilities:

1. FIRST: Brief single-line acknowledgment (e.g., "I'll get the users for you.")
2. THEN: Include a JSON code block with the abilities to execute
3. AFTER: I will execute the abilities and show you the results

Example response format (using the first available ability as example):
"I'll get the site information for you.

\`\`\`json
{
  "abilities": [
    {"name": "${ChatState.abilities[0]?.name || 'ability-name'}", "input": {}}
  ]
}
\`\`\`"

For abilities with parameters, include them in the input object. ALWAYS use the exact name from the list above.

You can chain multiple abilities (use exact names from the list):
\`\`\`json
{
  "abilities": [
    {"name": "exact-ability-name-from-list", "input": {}},
    {"name": "another-exact-ability-name", "input": {"param": "value"}}
  ]
}
\`\`\`

IMPORTANT RULES:
- Keep pre-execution acknowledgments brief (one line)
- CRITICAL: You MUST use the EXACT ability names as listed above - copy them exactly character by character
- The ability names include their namespace prefix (like "snn/" or "core/") - NEVER change or guess the prefix
- If you see "snn/get-site-info" in the list, use EXACTLY "snn/get-site-info" - NOT "core/get-site-info"
- Match parameter types exactly (string, integer, boolean, array, etc.)
- Use sensible defaults for optional parameters rather than asking
- After execution, I'll provide results - interpret them for the user in a friendly way
- ONLY ask clarifying questions when absolutely necessary
- ONLY use abilities that are listed above - NEVER make up or modify ability names

VALIDATION REQUIREMENTS:
- For any create-post or update-post abilities: The "content" field MUST contain at least 1 character. If the user doesn't specify content, use a placeholder like " " (single space) or "Draft content" instead of empty string ""
- Never send empty strings ("") for required text fields - always provide at least a minimal value`;
            }

            /**
             * Call AI API with automatic recovery
             */
            async function callAI(messages, retryCount = 0) {
                const config = snnChatConfig.ai;

                if (!config.apiKey || !config.apiEndpoint) {
                    throw new Error('AI API not configured. Please check settings.');
                }

                try {
                    ChatState.abortController = new AbortController();

                    const response = await fetch(config.apiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${config.apiKey}`
                        },
                        body: JSON.stringify({
                            model: config.model,
                            messages: messages,
                            temperature: 0.7,
                            max_tokens: config.maxTokens || 4000
                        }),
                        signal: ChatState.abortController.signal
                    });

                    // Handle rate limiting (429) with exponential backoff
                    if (response.status === 429) {
                        if (retryCount < RECOVERY_CONFIG.maxRecoveryAttempts) {
                            const delay = Math.min(
                                RECOVERY_CONFIG.rateLimitDelay * Math.pow(2, retryCount),
                                RECOVERY_CONFIG.maxDelay
                            );
                            
                            debugLog(`⚠️ Rate limited (429), retrying in ${delay/1000}s... (attempt ${retryCount + 1}/${RECOVERY_CONFIG.maxRecoveryAttempts})`);
                            
                            setAgentState(AgentState.RECOVERING, {
                                reason: 'Rate limit exceeded',
                                delay: delay,
                                attempt: retryCount + 1,
                                maxAttempts: RECOVERY_CONFIG.maxRecoveryAttempts
                            });
                            
                            await sleep(delay);
                            return await callAI(messages, retryCount + 1);
                        } else {
                            throw new Error('Rate limit exceeded. Please wait a moment and try again.');
                        }
                    }

                    // Handle other HTTP errors
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: { message: `HTTP ${response.status}` } }));
                        const errorMessage = errorData.error?.message || `AI API error: ${response.status}`;
                        
                        // For 5xx errors, retry with exponential backoff
                        if (response.status >= 500 && response.status < 600 && retryCount < RECOVERY_CONFIG.maxRecoveryAttempts) {
                            const delay = Math.min(
                                RECOVERY_CONFIG.baseDelay * Math.pow(2, retryCount),
                                RECOVERY_CONFIG.maxDelay
                            );
                            
                            debugLog(`⚠️ Server error (${response.status}), retrying in ${delay/1000}s...`);
                            
                            setAgentState(AgentState.RECOVERING, {
                                reason: `Server error (${response.status})`,
                                delay: delay,
                                attempt: retryCount + 1,
                                maxAttempts: RECOVERY_CONFIG.maxRecoveryAttempts
                            });
                            
                            await sleep(delay);
                            return await callAI(messages, retryCount + 1);
                        }
                        
                        throw new Error(errorMessage);
                    }

                    const data = await response.json();
                    
                    // Reset recovery attempts on success
                    ChatState.recoveryAttempts = 0;
                    ChatState.lastError = null;
                    
                    return data.choices[0].message.content;
                    
                } catch (error) {
                    // Don't retry on abort
                    if (error.name === 'AbortError') {
                        throw error;
                    }
                    
                    // Store error for potential recovery
                    ChatState.lastError = error;
                    
                    // Network errors - retry with exponential backoff
                    if ((error.message.includes('fetch') || error.message.includes('network')) && 
                        retryCount < RECOVERY_CONFIG.maxRecoveryAttempts) {
                        const delay = Math.min(
                            RECOVERY_CONFIG.baseDelay * Math.pow(2, retryCount),
                            RECOVERY_CONFIG.maxDelay
                        );
                        
                        debugLog(`⚠️ Network error, retrying in ${delay/1000}s...`);
                        
                        setAgentState(AgentState.RECOVERING, {
                            reason: 'Network error',
                            delay: delay,
                            attempt: retryCount + 1,
                            maxAttempts: RECOVERY_CONFIG.maxRecoveryAttempts
                        });
                        
                        await sleep(delay);
                        return await callAI(messages, retryCount + 1);
                    }
                    
                    throw error;
                }
            }

            /**
             * Extract abilities from AI response (without executing)
             */
            function extractAbilitiesFromResponse(response) {
                const abilities = [];

                // Look for JSON code blocks
                const jsonMatch = response.match(/```json\n?([\s\S]*?)\n?```/);
                if (!jsonMatch) {
                    debugLog('No JSON block found in response');
                    return abilities;
                }

                debugLog('Found JSON block:', jsonMatch[1]);

                try {
                    const parsed = JSON.parse(jsonMatch[1]);
                    debugLog('Parsed JSON:', parsed);

                    if (parsed.abilities && Array.isArray(parsed.abilities)) {
                        return parsed.abilities;
                    } else {
                        console.warn('JSON does not contain abilities array');
                    }
                } catch (error) {
                    console.error('Failed to parse abilities:', error);
                    addMessage('error', 'Failed to parse abilities: ' + error.message);
                    setAgentState(AgentState.ERROR, { error: error.message });
                }

                return abilities;
            }

            /**
             * Execute abilities one by one with AI interpretation after each
             */
            async function executeAbilitiesSequentially(conversationMessages, abilities) {
                const totalAbilities = abilities.length;

                for (let i = 0; i < abilities.length; i++) {
                    let ability = abilities[i];
                    const current = i + 1;
                    let retryCount = 0;
                    let result = null;

                    // Retry loop for this ability
                    while (retryCount <= MAX_RETRIES) {
                        // Show thinking state before execution
                        showTyping();
                        setAgentState(retryCount > 0 ? AgentState.RETRYING : AgentState.THINKING,
                            retryCount > 0 ? { attempt: retryCount + 1, maxAttempts: MAX_RETRIES + 1 } : null
                        );
                        await sleep(300); // Brief pause for UX

                        // Update state to executing
                        setAgentState(AgentState.EXECUTING, {
                            abilityName: ability.name,
                            current: current,
                            total: totalAbilities,
                            retry: retryCount > 0 ? retryCount : null
                        });

                        debugLog(`Executing: ${ability.name} (${current}/${totalAbilities})${retryCount > 0 ? ` [Retry ${retryCount}]` : ''}`, ability.input);
                        result = await executeAbility(ability.name, ability.input || {});
                        debugLog(`Result for ${ability.name}:`, result);

                        hideTyping();

                        // If successful, break out of retry loop
                        if (result.success) {
                            break;
                        }

                        // If failed and we have retries left, ask AI to fix
                        if (retryCount < MAX_RETRIES) {
                            debugLog(`Ability ${ability.name} failed, attempting retry ${retryCount + 1}/${MAX_RETRIES}`);

                            // Ask AI to correct the input
                            const correctedAbility = await retryWithAI(conversationMessages, ability, result.error);

                            if (correctedAbility) {
                                ability = correctedAbility;
                                retryCount++;
                                continue;
                            } else {
                                // AI couldn't provide correction, break out
                                debugLog('AI could not provide a corrected input, giving up');
                                break;
                            }
                        } else {
                            // Max retries reached
                            break;
                        }
                    }

                    // Check if this ability requires client-side execution
                    // Handle both result.client_command and result.data.client_command
                    const clientCommand = result.client_command || (result.data && result.data.client_command);
                    if (result.success && clientCommand && typeof clientCommand === 'object' && clientCommand !== null) {
                        debugLog('Executing client-side command:', clientCommand);
                        try {
                            const clientResult = await executeClientCommand(clientCommand);
                            if (!clientResult.success) {
                                result.success = false;
                                result.error = clientResult.error;
                            }
                        } catch (error) {
                            console.error('Client command execution failed:', error);
                            result.success = false;
                            result.error = 'Client-side execution failed: ' + error.message;
                        }
                    }

                    // Format and display this task's result
                    const resultHtml = formatSingleAbilityResult({
                        ability: ability.name,
                        result: result
                    });
                    addMessage('assistant', resultHtml, [{ ability: ability.name, result: result }]);

                    // Get AI interpretation for this specific result
                    if (result.success) {
                        await interpretSingleResult(conversationMessages, ability.name, result, current, totalAbilities);
                    } else {
                        // Show error interpretation after all retries exhausted
                        const errorMsg = retryCount > 0
                            ? `Task ${current}/${totalAbilities} (${ability.name}) failed after ${retryCount + 1} attempts: ${result.error || 'Unknown error'}`
                            : `Task ${current}/${totalAbilities} (${ability.name}) failed: ${result.error || 'Unknown error'}`;
                        addMessage('assistant', errorMsg);
                    }

                    // Small delay between tasks for better UX
                    if (i < abilities.length - 1) {
                        await sleep(500);
                    }
                }
            }

            /**
             * Ask AI to retry with corrected input after an error
             */
            async function retryWithAI(conversationMessages, failedAbility, errorMessage) {
                try {
                    showTyping();
                    setAgentState(AgentState.RETRYING, { abilityName: failedAbility.name });

                    // Find ability info for schema details
                    const abilityInfo = ChatState.abilities.find(a => a.name === failedAbility.name);
                    let schemaHint = '';
                    if (abilityInfo && abilityInfo.input_schema && abilityInfo.input_schema.properties) {
                        schemaHint = '\n\nValid parameters for this ability:\n' +
                            Object.entries(abilityInfo.input_schema.properties).map(([key, val]) => {
                                let hint = `- ${key} (${val.type})`;
                                if (val.minimum !== undefined) hint += `, min: ${val.minimum}`;
                                if (val.maximum !== undefined) hint += `, max: ${val.maximum}`;
                                if (val.default !== undefined) hint += `, default: ${val.default}`;
                                if (val.description) hint += ` - ${val.description}`;
                                return hint;
                            }).join('\n');
                    }

                    const retryMessages = [
                        ...conversationMessages,
                        {
                            role: 'user',
                            content: `The ability "${failedAbility.name}" failed with error: "${errorMessage}"

The input you provided was: ${JSON.stringify(failedAbility.input)}
${schemaHint}

Please provide a CORRECTED input that fixes this error. Respond ONLY with a JSON code block containing the corrected ability call, like this:
\`\`\`json
{
  "abilities": [
    {"name": "${failedAbility.name}", "input": {<corrected parameters>}}
  ]
}
\`\`\`

If you cannot fix the error, respond with "CANNOT_FIX" and explain why.`
                        }
                    ];

                    const aiResponse = await callAI(retryMessages);
                    hideTyping();

                    // Check if AI gave up
                    if (aiResponse.includes('CANNOT_FIX')) {
                        debugLog('AI indicated it cannot fix the error:', aiResponse);
                        return null;
                    }

                    // Extract corrected ability from response
                    const correctedAbilities = extractAbilitiesFromResponse(aiResponse);

                    if (correctedAbilities.length > 0) {
                        debugLog('AI provided corrected input:', correctedAbilities[0]);
                        return correctedAbilities[0];
                    }

                    return null;
                } catch (error) {
                    console.error('Failed to get retry correction from AI:', error);
                    hideTyping();
                    return null;
                }
            }

            /**
             * Interpret a single ability result with AI
             */
            async function interpretSingleResult(conversationMessages, abilityName, result, current, total) {
                try {
                    // Skip individual interpretations when executing multiple tasks
                    // Only provide final summary to reduce noise and prevent context pollution
                    if (total > 1) {
                        debugLog(`Skipping interpretation for task ${current}/${total} - will provide final summary instead`);
                        return;
                    }

                    showTyping();
                    setAgentState(AgentState.INTERPRETING);

                    const resultText = `Ability: ${abilityName}\nSuccess: ${result.success}\nData: ${JSON.stringify(result.data, null, 2)}`;

                    const interpretMessages = [
                        ...conversationMessages,
                        ...ChatState.messages.slice(-5).map(m => ({
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: m.content
                        })),
                        {
                            role: 'user',
                            content: `Task completed successfully.\n\nResult:\n${resultText}\n\nProvide a brief, natural response about this result. CRITICAL: Keep it conversational and factual.`
                        }
                    ];

                    const interpretation = await callAI(interpretMessages);
                    hideTyping();

                    // Strip any JSON blocks and execution results from interpretation
                    const cleanInterpretation = interpretation
                        .replace(/```json\n?[\s\S]*?\n?```/g, '')
                        .replace(/\n*Execution results?:\s*\[Executed[\s\S]*?\]\]\n*/gi, '')
                        .trim();

                    // Add interpretation as a follow-up message with special metadata to distinguish it from actual execution
                    addMessage('assistant', cleanInterpretation, [{ type: 'interpretation', ability: abilityName }]);

                } catch (error) {
                    console.error('Failed to interpret result:', error);
                    hideTyping();
                }
            }

            /**
             * Provide final summary after all tasks complete
             */
            async function provideFinalSummary(conversationMessages, abilities) {
                // Always provide summary for multi-task executions
                if (abilities.length <= 1) {
                    return; // No need for summary if only one task
                }

                try {
                    showTyping();
                    setAgentState(AgentState.THINKING);

                    // Build a clear list of what was executed
                    const executedList = abilities.map((a, i) => {
                        const desc = a.input?.pattern_type || a.input?.content_description?.substring(0, 50) || a.name;
                        return `${i + 1}. ${a.name} (${desc})`;
                    }).join('\n');

                    const summaryMessages = [
                        ...conversationMessages,
                        ...ChatState.messages.slice(-10).map(m => ({
                            role: m.role === 'user' ? 'user' : 'assistant',
                            content: m.content
                        })),
                        {
                            role: 'user',
                            content: `All ${abilities.length} tasks have been completed successfully:\n\n${executedList}\n\nProvide a brief, enthusiastic final summary (2-3 sentences max) of what was accomplished. Focus on the outcome, not the individual steps. Be conversational and encouraging.`
                        }
                    ];

                    const summary = await callAI(summaryMessages);
                    hideTyping();

                    // Strip any JSON blocks and execution results from summary
                    const cleanSummary = summary
                        .replace(/```json\n?[\s\S]*?\n?```/g, '')
                        .replace(/\n*Execution results?:\s*\[Executed[\s\S]*?\]\]\n*/gi, '')
                        .trim();

                    // Add summary message with interpretation metadata
                    addMessage('assistant', '✅ ' + cleanSummary, [{ type: 'interpretation', phase: 'final_summary' }]);

                } catch (error) {
                    console.error('Failed to provide final summary:', error);
                    hideTyping();
                }
            }

            /**
             * Sleep utility for UX timing
             */
            function sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            /**
             * Execute client-side command (e.g., update block editor)
             */
            async function executeClientCommand(command) {
                try {
                    // Validate command object
                    if (!command || typeof command !== 'object' || command === null) {
                        return {
                            success: false,
                            error: 'Invalid client command: command is not an object'
                        };
                    }

                    if (!command.type) {
                        return {
                            success: false,
                            error: 'Invalid client command: missing type property'
                        };
                    }

                    debugLog('Client command type:', command.type);

                    if (command.type === 'update_editor_content') {
                        return await updateBlockEditorContent(command);
                    }

                    if (command.type === 'update_post_metadata') {
                        return await updatePostMetadataInEditor(command);
                    }

                    if (command.type === 'edit_block_content') {
                        return await editBlockEditorContent(command);
                    }

                    return {
                        success: false,
                        error: `Unknown client command type: ${command.type}`
                    };
                } catch (error) {
                    console.error('Client command execution error:', error);
                    return {
                        success: false,
                        error: error.message || 'Unknown error in client command execution'
                    };
                }
            }

            /**
             * Update post metadata in the editor (title, excerpt, status, etc.) in real-time
             */
            async function updatePostMetadataInEditor(command) {
                try {
                    // Check if we're in the block editor
                    if (typeof wp === 'undefined' || !wp.data || !wp.data.select('core/editor')) {
                        return {
                            success: false,
                            error: 'Block editor not detected. This command only works when editing a post.'
                        };
                    }

                    const { select, dispatch } = wp.data;
                    const editor = select('core/editor');
                    const editorDispatch = dispatch('core/editor');

                    // Get current post ID
                    const currentPostId = editor.getCurrentPostId();

                    // Verify post ID if provided
                    if (command.post_id && command.post_id !== currentPostId) {
                        return {
                            success: false,
                            error: `Post ID mismatch. Expected ${command.post_id}, but currently editing ${currentPostId}`
                        };
                    }

                    // Build the updates object
                    const updates = {};
                    const updatedFields = [];

                    if (command.title !== undefined) {
                        updates.title = command.title;
                        updatedFields.push('title');
                    }

                    if (command.excerpt !== undefined) {
                        updates.excerpt = command.excerpt;
                        updatedFields.push('excerpt');
                    }

                    if (command.status !== undefined) {
                        updates.status = command.status;
                        updatedFields.push('status');
                    }

                    if (command.featured_media !== undefined) {
                        updates.featured_media = command.featured_media;
                        updatedFields.push('featured image');
                    }

                    // Apply the updates to the editor
                    if (Object.keys(updates).length > 0) {
                        editorDispatch.editPost(updates);

                        return {
                            success: true,
                            message: `Updated ${updatedFields.join(', ')} in editor. Remember to save your changes.`
                        };
                    } else {
                        return {
                            success: false,
                            error: 'No metadata fields to update'
                        };
                    }

                } catch (error) {
                    console.error('Post metadata update error:', error);
                    return {
                        success: false,
                        error: `Failed to update metadata: ${error.message}`
                    };
                }
            }

            /**
             * Update block editor content in real-time
             */
            async function updateBlockEditorContent(command) {
                try {
                    // Check if we're in the block editor
                    if (typeof wp === 'undefined' || !wp.data || !wp.data.select('core/editor')) {
                        return {
                            success: false,
                            error: 'Block editor not detected. This command only works when editing a post.'
                        };
                    }

                    const { select, dispatch } = wp.data;
                    const editor = select('core/editor');
                    const editorDispatch = dispatch('core/editor');
                    const blockEditor = select('core/block-editor');
                    const blockEditorDispatch = dispatch('core/block-editor');

                    // Get current post ID
                    const currentPostId = editor.getCurrentPostId();

                    // Verify post ID if provided
                    if (command.post_id && command.post_id !== currentPostId) {
                        return {
                            success: false,
                            error: `Post ID mismatch. Expected ${command.post_id}, but currently editing ${currentPostId}`
                        };
                    }

                    // Handle update_section action - find and replace existing sections
                    if (command.action === 'update_section' && command.section_identifier) {
                        const blocks = blockEditor.getBlocks();
                        let sectionFound = false;
                        let blocksToReplace = [];
                        let startIndex = -1;

                        // Find the section by searching for heading blocks matching the identifier
                        for (let i = 0; i < blocks.length; i++) {
                            const block = blocks[i];

                            // Check if this is a heading block that matches our section identifier
                            if (block.name === 'core/heading' || block.name === 'core/paragraph') {
                                const blockContent = block.attributes.content || '';
                                const plainText = blockContent.replace(/<[^>]*>/g, '').trim().toLowerCase();
                                const identifier = command.section_identifier.toLowerCase();

                                // Check if this heading matches our section
                                if (plainText.includes(identifier) || identifier.includes(plainText)) {
                                    sectionFound = true;
                                    startIndex = i;
                                    blocksToReplace.push(block.clientId);

                                    // Find all blocks that belong to this section (until next heading or end)
                                    for (let j = i + 1; j < blocks.length; j++) {
                                        const nextBlock = blocks[j];
                                        // Stop at next heading of same or higher level
                                        if (nextBlock.name === 'core/heading') {
                                            if (block.name === 'core/heading' &&
                                                nextBlock.attributes.level <= block.attributes.level) {
                                                break;
                                            }
                                        }
                                        blocksToReplace.push(nextBlock.clientId);
                                    }
                                    break;
                                }
                            }
                        }

                        if (sectionFound && blocksToReplace.length > 0) {
                            // Parse the new content as blocks
                            const newBlocks = wp.blocks.parse(command.content);

                            // Replace the old section blocks with new ones
                            blockEditorDispatch.replaceBlocks(blocksToReplace, newBlocks);

                            return {
                                success: true,
                                message: `Updated "${command.section_identifier}" section with ${newBlocks.length} block(s). Remember to save your changes.`
                            };
                        } else {
                            // Section not found, append to end instead
                            const newBlocks = wp.blocks.parse(command.content);
                            blockEditorDispatch.insertBlocks(newBlocks);

                            return {
                                success: true,
                                message: `Section "${command.section_identifier}" not found. Added new content to the end. Remember to save your changes.`
                            };
                        }
                    }

                    // For preview mode, don't actually update
                    if (command.action === 'preview') {
                        console.log('Preview content:', command.content);
                        return {
                            success: true,
                            message: 'Preview mode - content not applied',
                            preview: command.content.substring(0, 200)
                        };
                    }

                    // Parse content as blocks
                    debugLog('Parsing content as blocks...');
                    const newBlocks = wp.blocks.parse(command.content);
                    debugLog(`Parsed ${newBlocks.length} blocks from content`);

                    // Handle different actions
                    if (command.action === 'replace') {
                        // Replace all content
                        debugLog('Replacing all blocks...');
                        const allBlocks = blockEditor.getBlocks();
                        const allBlockIds = allBlocks.map(b => b.clientId);

                        if (allBlockIds.length > 0) {
                            blockEditorDispatch.replaceBlocks(allBlockIds, newBlocks);
                        } else {
                            blockEditorDispatch.insertBlocks(newBlocks);
                        }

                        debugLog('✅ Blocks replaced successfully');

                    } else if (command.action === 'append') {
                        // Append to end
                        debugLog('Appending blocks to end...');
                        blockEditorDispatch.insertBlocks(newBlocks);
                        debugLog('✅ Blocks appended successfully');

                    } else if (command.action === 'prepend') {
                        // Insert at beginning
                        debugLog('Prepending blocks to beginning...');
                        blockEditorDispatch.insertBlocks(newBlocks, 0);
                        debugLog('✅ Blocks prepended successfully');
                    }

                    // Auto-save if requested
                    if (command.save_immediately) {
                        await editorDispatch.savePost();
                        return {
                            success: true,
                            message: `Content ${command.action}ed and saved (${newBlocks.length} blocks)`
                        };
                    }

                    return {
                        success: true,
                        message: `Content ${command.action}ed in editor (${newBlocks.length} blocks). Remember to save your changes.`
                    };

                } catch (error) {
                    console.error('Block editor update error:', error);
                    return {
                        success: false,
                        error: `Failed to update editor: ${error.message}`
                    };
                }
            }

            /**
             * Recursively find a block and its parent list
             * Returns: { block, index, parentList } or null
             */
            function findBlockRecursively(blocks, predicate, depth = 0) {
                const indent = '  '.repeat(depth);
                for (let i = 0; i < blocks.length; i++) {
                    debugLog(`${indent}Checking block [${i}]: ${blocks[i].name}`);
                    if (predicate(blocks[i])) {
                        debugLog(`${indent}✅ Found matching block at depth ${depth}, index ${i}`);
                        return { block: blocks[i], index: i, parentList: blocks };
                    }
                    if (blocks[i].innerBlocks && blocks[i].innerBlocks.length > 0) {
                        debugLog(`${indent}↳ Diving into ${blocks[i].innerBlocks.length} innerBlocks...`);
                        const found = findBlockRecursively(blocks[i].innerBlocks, predicate, depth + 1);
                        if (found) return found;
                    }
                }
                return null;
            }

            /**
             * Edit block content with surgical precision
             */
            async function editBlockEditorContent(command) {
                try {
                    // Check if we're in the block editor
                    if (typeof wp === 'undefined' || !wp.data || !wp.data.select('core/editor')) {
                        return {
                            success: false,
                            error: 'Block editor not detected. This command only works when editing a post.'
                        };
                    }

                    const { select, dispatch } = wp.data;
                    const editor = select('core/editor');
                    const editorDispatch = dispatch('core/editor');
                    const blockEditor = select('core/block-editor');
                    const blockEditorDispatch = dispatch('core/block-editor');

                    // Get current post ID
                    const currentPostId = editor.getCurrentPostId();

                    // Verify post ID if provided
                    if (command.post_id && command.post_id !== currentPostId) {
                        return {
                            success: false,
                            error: `Post ID mismatch. Expected ${command.post_id}, but currently editing ${currentPostId}`
                        };
                    }

                    const blocks = blockEditor.getBlocks();
                    debugLog(`Current block count: ${blocks.length}`);
                    debugLog(`Edit action: ${command.action}`);

                    // Handle different edit actions
                    switch (command.action) {
                        case 'insert_at_index': {
                            if (!command.content) {
                                return { success: false, error: 'content is required for insert_at_index' };
                            }
                            if (command.insert_index === undefined) {
                                return { success: false, error: 'insert_index is required for insert_at_index' };
                            }

                            const newBlocks = wp.blocks.parse(command.content);
                            let insertIndex = command.insert_index;

                            // Handle -1 as "end of document"
                            if (insertIndex === -1) {
                                insertIndex = blocks.length;
                            }

                            // Validate index
                            if (insertIndex < 0 || insertIndex > blocks.length) {
                                return {
                                    success: false,
                                    error: `Invalid insert_index: ${command.insert_index}. Valid range: 0-${blocks.length} or -1 for end`
                                };
                            }

                            blockEditorDispatch.insertBlocks(newBlocks, insertIndex);
                            debugLog(`✅ Inserted ${newBlocks.length} blocks at index ${insertIndex}`);

                            return {
                                success: true,
                                message: `Inserted ${newBlocks.length} block(s) at position ${insertIndex}. Remember to save your changes.`
                            };
                        }

                        case 'replace_block_range': {
                            if (!command.content) {
                                return { success: false, error: 'content is required for replace_block_range' };
                            }
                            if (command.start_index === undefined || command.end_index === undefined) {
                                return { success: false, error: 'start_index and end_index are required for replace_block_range' };
                            }

                            const startIndex = command.start_index;
                            const endIndex = command.end_index;

                            // Validate indices
                            if (startIndex < 0 || startIndex >= blocks.length) {
                                return {
                                    success: false,
                                    error: `Invalid start_index: ${startIndex}. Valid range: 0-${blocks.length - 1}`
                                };
                            }
                            if (endIndex < startIndex || endIndex >= blocks.length) {
                                return {
                                    success: false,
                                    error: `Invalid end_index: ${endIndex}. Must be >= start_index and < ${blocks.length}`
                                };
                            }

                            // Get block IDs to replace
                            const blocksToReplace = [];
                            for (let i = startIndex; i <= endIndex; i++) {
                                blocksToReplace.push(blocks[i].clientId);
                            }

                            const newBlocks = wp.blocks.parse(command.content);
                            blockEditorDispatch.replaceBlocks(blocksToReplace, newBlocks);
                            debugLog(`✅ Replaced blocks ${startIndex}-${endIndex} with ${newBlocks.length} new blocks`);

                            return {
                                success: true,
                                message: `Replaced blocks ${startIndex}-${endIndex} with ${newBlocks.length} new block(s). Remember to save your changes.`
                            };
                        }

                        case 'delete_blocks': {
                            if (command.start_index === undefined) {
                                return { success: false, error: 'start_index is required for delete_blocks' };
                            }

                            const startIndex = command.start_index;
                            const endIndex = command.end_index !== undefined ? command.end_index : startIndex;

                            // Validate indices
                            if (startIndex < 0 || startIndex >= blocks.length) {
                                return {
                                    success: false,
                                    error: `Invalid start_index: ${startIndex}. Valid range: 0-${blocks.length - 1}`
                                };
                            }
                            if (endIndex < startIndex || endIndex >= blocks.length) {
                                return {
                                    success: false,
                                    error: `Invalid end_index: ${endIndex}. Must be >= start_index and < ${blocks.length}`
                                };
                            }

                            // Get block IDs to delete
                            const blocksToDelete = [];
                            for (let i = startIndex; i <= endIndex; i++) {
                                blocksToDelete.push(blocks[i].clientId);
                            }

                            blockEditorDispatch.removeBlocks(blocksToDelete);
                            const deletedCount = blocksToDelete.length;
                            debugLog(`✅ Deleted ${deletedCount} block(s) from index ${startIndex} to ${endIndex}`);

                            return {
                                success: true,
                                message: `Deleted ${deletedCount} block(s). Remember to save your changes.`
                            };
                        }

                        case 'find_and_replace_section': {
                            if (!command.content || !command.section_identifier) {
                                return { success: false, error: 'content and section_identifier are required for find_and_replace_section' };
                            }

                            const identifier = command.section_identifier.toLowerCase();
                            debugLog(`🔍 Searching for section: "${identifier}"`);
                            debugLog(`📄 Total top-level blocks: ${blocks.length}`);

                            // Use recursive finder to search all blocks, including nested ones
                            const foundData = findBlockRecursively(blocks, (block) => {
                                if (block.name === 'core/heading' || block.name === 'core/paragraph') {
                                    const blockContent = block.attributes.content || '';
                                    const plainText = blockContent.replace(/<[^>]*>/g, '').trim().toLowerCase();
                                    const isMatch = plainText.includes(identifier) || identifier.includes(plainText);
                                    
                                    if (isMatch) {
                                        debugLog(`✅ MATCH FOUND: "${plainText}" matches "${identifier}"`);
                                    } else {
                                        debugLog(`❌ No match: "${plainText}" vs "${identifier}"`);
                                    }
                                    
                                    return isMatch;
                                }
                                return false;
                            });

                            if (foundData) {
                                const { block, index, parentList } = foundData;
                                const blocksToReplace = [block.clientId];

                                // Find all sibling blocks that belong to this section (until next heading or end)
                                for (let j = index + 1; j < parentList.length; j++) {
                                    const nextBlock = parentList[j];
                                    // Stop at next heading of same or higher level
                                    if (nextBlock.name === 'core/heading') {
                                        if (block.name === 'core/heading' &&
                                            nextBlock.attributes.level <= block.attributes.level) {
                                            break;
                                        }
                                    }
                                    blocksToReplace.push(nextBlock.clientId);
                                }

                                // Parse the new content as blocks
                                const newBlocks = wp.blocks.parse(command.content);

                                // Replace the old section blocks with new ones
                                blockEditorDispatch.replaceBlocks(blocksToReplace, newBlocks);
                                debugLog(`✅ Replaced section "${command.section_identifier}" (${blocksToReplace.length} blocks) with ${newBlocks.length} new blocks`);

                                return {
                                    success: true,
                                    message: `Updated "${command.section_identifier}" section with ${newBlocks.length} block(s). Remember to save your changes.`
                                };
                            } else {
                                // Section not found, append to end instead
                                const newBlocks = wp.blocks.parse(command.content);
                                blockEditorDispatch.insertBlocks(newBlocks);
                                debugLog(`⚠️ Section "${command.section_identifier}" not found. Appended ${newBlocks.length} blocks to end.`);

                                return {
                                    success: false,
                                    message: `Section "${command.section_identifier}" not found. Added new content to the end instead. Remember to save your changes.`,
                                    warning: 'Section identifier not matched - content appended to end'
                                };
                            }
                        }

                        case 'find_and_replace_text': {
                            if (!command.find_text || !command.replace_text) {
                                return { success: false, error: 'find_text and replace_text are required for find_and_replace_text' };
                            }

                            const findText = command.find_text;
                            const replaceText = command.replace_text;
                            const blockTypes = command.block_types || null; // null means all blocks
                            let replacementCount = 0;

                            // Iterate through all blocks and replace text
                            blocks.forEach(block => {
                                // Filter by block type if specified
                                if (blockTypes && !blockTypes.includes(block.name)) {
                                    return;
                                }

                                // Check if block has content attribute
                                if (block.attributes && block.attributes.content) {
                                    const originalContent = block.attributes.content;
                                    const newContent = originalContent.replace(new RegExp(findText, 'g'), replaceText);

                                    if (newContent !== originalContent) {
                                        blockEditorDispatch.updateBlockAttributes(block.clientId, {
                                            content: newContent
                                        });
                                        replacementCount++;
                                    }
                                }

                                // Check other common text attributes
                                if (block.attributes && block.attributes.text) {
                                    const originalText = block.attributes.text;
                                    const newText = originalText.replace(new RegExp(findText, 'g'), replaceText);

                                    if (newText !== originalText) {
                                        blockEditorDispatch.updateBlockAttributes(block.clientId, {
                                            text: newText
                                        });
                                        replacementCount++;
                                    }
                                }
                            });

                            debugLog(`✅ Replaced "${findText}" with "${replaceText}" in ${replacementCount} block(s)`);

                            return {
                                success: true,
                                message: `Replaced "${findText}" with "${replaceText}" in ${replacementCount} block(s). Remember to save your changes.`
                            };
                        }

                        default:
                            return {
                                success: false,
                                error: `Unknown edit action: ${command.action}`
                            };
                    }

                } catch (error) {
                    console.error('Block editor edit error:', error);
                    return {
                        success: false,
                        error: `Failed to edit blocks: ${error.message}`
                    };
                }
            }

            /**
             * Execute a single ability
             */
            async function executeAbility(abilityName, input) {
                try {
                    // Try to find the correct ability name (AI might use wrong prefix)
                    let actualAbilityName = abilityName;
                    let abilityInfo = ChatState.abilities.find(a => a.name === abilityName);

                    // If not found, try to match by suffix (e.g., "core/get-site-info" -> "snn/get-site-info")
                    if (!abilityInfo) {
                        const suffix = abilityName.split('/').pop(); // Get part after last /
                        abilityInfo = ChatState.abilities.find(a => a.name.endsWith('/' + suffix));
                        if (abilityInfo) {
                            debugLog(`Correcting ability name: ${abilityName} -> ${abilityInfo.name}`);
                            actualAbilityName = abilityInfo.name;
                        }
                    }

                    // Encode the ability name but keep forward slashes as-is for WordPress REST API
                    const encodedName = actualAbilityName.split('/').map(part => encodeURIComponent(part)).join('/');

                    // Check if this ability is read-only
                    const isReadOnly = abilityInfo?.meta?.readonly === true;

                    let apiUrl = snnChatConfig.restUrl + 'abilities/' + encodedName + '/run';

                    // Helper function to make the actual request
                    const makeRequest = async (method) => {
                        let fetchOptions = {
                            headers: {
                                'X-WP-Nonce': snnChatConfig.nonce
                            }
                        };
                        let url = apiUrl;

                        if (method === 'GET') {
                            fetchOptions.method = 'GET';
                            if (input && Object.keys(input).length > 0) {
                                const params = new URLSearchParams();
                                params.append('input', JSON.stringify(input));
                                url += '?' + params.toString();
                            }
                            debugLog(`Calling API (GET): ${url}`);
                        } else {
                            fetchOptions.method = 'POST';
                            fetchOptions.headers['Content-Type'] = 'application/json';
                            fetchOptions.body = JSON.stringify({ input: input });
                            debugLog(`Calling API (POST): ${url}`);
                        }

                        debugLog('Input:', input);
                        return fetch(url, fetchOptions);
                    };

                    // Try with the appropriate method based on readonly flag
                    let response = await makeRequest(isReadOnly ? 'GET' : 'POST');

                    // If we get 405 (Method Not Allowed), retry with the opposite method
                    if (response.status === 405) {
                        const retryMethod = isReadOnly ? 'POST' : 'GET';
                        debugLog(`Got 405, retrying with ${retryMethod}...`);
                        response = await makeRequest(retryMethod);
                    }

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error(`API error ${response.status}:`, errorText);

                        let error;
                        try {
                            error = JSON.parse(errorText);
                        } catch (e) {
                            error = { message: errorText };
                        }

                        return { success: false, error: error.message || `HTTP ${response.status}` };
                    }

                    const result = await response.json();
                    debugLog('API response:', result);
                    
                    // Normalize the response - WordPress Abilities API might return different formats
                    // Check if it already has success property
                    if (typeof result.success !== 'undefined') {
                        // Already in expected format
                        return result;
                    }
                    
                    // If it has data property, it's likely successful
                    if (result.data !== undefined) {
                        return {
                            success: true,
                            data: result.data
                        };
                    }
                    
                    // If it has an error or message property indicating failure
                    if (result.error || result.message) {
                        return {
                            success: false,
                            error: result.error || result.message
                        };
                    }
                    
                    // Otherwise, treat the entire result as data (successful)
                    return {
                        success: true,
                        data: result
                    };
                } catch (error) {
                    console.error('Execution error:', error);
                    return { success: false, error: error.message };
                }
            }

            /**
             * Summarize ability execution results
             */
            function summarizeAbilityResults(results) {
                const summary = results.map(r => {
                    const status = r.result.success ? '✓' : '✗';
                    return `${status} ${r.ability}`;
                }).join('\n');

                return `**Executed:**\n${summary}`;
            }

            /**
             * Format ability execution results as HTML
             */
            function formatAbilityResults(results) {
                let html = '<div class="ability-results">';
                
                results.forEach(r => {
                    const success = r.result.success === true || (r.result.success !== false && !r.result.error);
                    const status = success ? '✅' : '❌';
                    const statusClass = success ? 'success' : 'error';
                    
                    html += `<div class="ability-result ${statusClass}">`;
                    html += `<strong>${status} ${r.ability}</strong>`;
                    
                    if (success) {
                        if (r.result.data) {
                            // Show a preview of the data
                            const preview = formatDataPreview(r.result.data);
                            html += `<div class="result-data">${preview}</div>`;
                        } else {
                            html += `<div class="result-data">Completed successfully</div>`;
                        }
                    } else {
                        const errorMsg = r.result.error || r.result.message || 'Unknown error';
                        html += `<div class="result-error">${errorMsg}</div>`;
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
                return html;
            }

            /**
             * Format single ability result as HTML
             */
            function formatSingleAbilityResult(r) {
                const success = r.result.success === true || (r.result.success !== false && !r.result.error);
                const status = success ? '✅' : '❌';
                const statusClass = success ? 'success' : 'error';
                
                let html = '<div class="ability-results">';
                html += `<div class="ability-result ${statusClass}">`;
                html += `<strong>${status} ${r.ability}</strong>`;
                
                if (success) {
                    if (r.result.data) {
                        // Show a preview of the data
                        const preview = formatDataPreview(r.result.data);
                        html += `<div class="result-data">${preview}</div>`;
                    } else {
                        html += `<div class="result-data">Completed successfully</div>`;
                    }
                } else {
                    const errorMsg = r.result.error || r.result.message || 'Unknown error';
                    html += `<div class="result-error">${errorMsg}</div>`;
                }
                
                html += '</div>';
                html += '</div>';
                return html;
            }

            /**
             * Format data preview for display
             */
            function formatDataPreview(data) {
                if (Array.isArray(data)) {
                    if (data.length === 0) return 'Empty array';
                    
                    // Show count and formatted JSON
                    const countText = `Found ${data.length} item${data.length !== 1 ? 's' : ''}`;
                    const jsonHtml = formatJsonWithSyntaxHighlighting(data);
                    return `${countText}<div class="json-result-container"><pre class="json-result">${jsonHtml}</pre></div>`;
                    
                } else if (typeof data === 'object' && data !== null) {
                    const keys = Object.keys(data);
                    if (keys.length === 0) return 'Empty object';
                    
                    // Special handling for WordPress post objects
                    if (data.ID || data.id) {
                        const id = data.ID || data.id;
                        const title = data.post_title || data.title || 'Untitled';
                        const status = data.post_status || data.status || 'unknown';
                        const editUrl = `<?php echo admin_url('post.php?action=edit&post='); ?>${id}`;
                        
                        // Compact inline format
                        return `<strong>ID:</strong> ${id} | <strong>Title:</strong> ${title} | <strong>Status:</strong> ${status} | <a href="${editUrl}" target="_blank" style="color: #667eea;">Edit →</a>`;
                    }
                    
                    // For objects, show formatted JSON
                    const jsonHtml = formatJsonWithSyntaxHighlighting(data);
                    return `<div class="json-result-container"><pre class="json-result">${jsonHtml}</pre></div>`;
                }
                return String(data).substring(0, 100);
            }

            /**
             * Format JSON with syntax highlighting
             */
            function formatJsonWithSyntaxHighlighting(data) {
                try {
                    const jsonStr = JSON.stringify(data, null, 2);
                    
                    // Simple syntax highlighting
                    return jsonStr
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"([^"]+)":/g, '<span class="json-key">"$1"</span>:') // Keys
                        .replace(/: "([^"]*)"/g, ': <span class="json-string">"$1"</span>') // String values
                        .replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>') // Booleans
                        .replace(/: (null)/g, ': <span class="json-null">$1</span>') // Null
                        .replace(/: (\d+)/g, ': <span class="json-number">$1</span>'); // Numbers
                } catch(e) {
                    return String(data);
                }
            }



            /**
             * Add message to chat
             */
            function addMessage(role, content, metadata = null) {
                const message = {
                    role: role,
                    content: content,
                    metadata: metadata,
                    timestamp: Date.now()
                };

                ChatState.messages.push(message);

                const $messages = $('#snn-chat-messages');
                const $welcome = $messages.find('.snn-chat-welcome');
                
                if ($welcome.length) {
                    $welcome.remove();
                    // Hide quick actions when chat starts
                    $('.snn-chat-quick-actions').hide();
                }

                const $message = $('<div>')
                    .addClass('snn-chat-message')
                    .addClass('snn-chat-message-' + role)
                    .html(formatMessage(content));

                $messages.append($message);
                scrollToBottom();
            }

            /**
             * Format message content using markdown library
             */
            function formatMessage(content) {
                // Check if content contains HTML (ability results) - don't process with markdown
                if (content.includes('<div class="ability-results">') || content.includes('<div class="ability-result')) {
                    return content;
                }

                // Use markdown.js library if available
                if (typeof markdown !== 'undefined' && markdown.toHTML) {
                    try {
                        return markdown.toHTML(content);
                    } catch (e) {
                        console.error('Markdown parsing error:', e);
                        // Fall back to basic formatting
                        return basicFormatMessage(content);
                    }
                }

                // Fallback to basic formatting if markdown library not loaded
                return basicFormatMessage(content);
            }

            /**
             * Basic message formatting fallback
             */
            function basicFormatMessage(content) {
                return content
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/`(.*?)`/g, '<code>$1</code>')
                    .replace(/\n/g, '<br>');
            }

            /**
             * Show/hide typing indicator
             */
            function showTyping() {
                $('.snn-chat-typing').show();
                scrollToBottom();
            }

            function hideTyping() {
                $('.snn-chat-typing').hide();
            }

            /**
             * Set agent state and update UI
             */
            function setAgentState(state, metadata = null) {
                ChatState.currentState = state;

                // Log state transition
                debugLog('🔄 Agent State:', state, metadata || '');

                const $stateText = $('#snn-chat-state-text');
                let stateMessage = '';

                switch(state) {
                    case AgentState.IDLE:
                        stateMessage = '';
                        break;

                    case AgentState.THINKING:
                        stateMessage = 'Thinking...';
                        break;

                    case AgentState.EXECUTING:
                        if (metadata && metadata.abilityName) {
                            stateMessage = `Executing ${metadata.abilityName}...`;
                            if (metadata.current && metadata.total) {
                                stateMessage = `Executing ${metadata.abilityName} (${metadata.current}/${metadata.total})...`;
                            }
                        } else {
                            stateMessage = 'Executing...';
                        }
                        break;

                    case AgentState.INTERPRETING:
                        stateMessage = 'Interpreting results...';
                        break;

                    case AgentState.RETRYING:
                        if (metadata && metadata.abilityName) {
                            stateMessage = `Retrying ${metadata.abilityName}...`;
                        } else if (metadata && metadata.attempt) {
                            stateMessage = `Retrying (attempt ${metadata.attempt}/${metadata.maxAttempts})...`;
                        } else {
                            stateMessage = 'Retrying with corrected input...';
                        }
                        break;
                    
                    case AgentState.RECOVERING:
                        if (metadata) {
                            const waitTime = metadata.delay ? Math.ceil(metadata.delay / 1000) : 0;
                            const attemptInfo = metadata.attempt ? ` (${metadata.attempt}/${metadata.maxAttempts})` : '';
                            if (metadata.reason) {
                                stateMessage = `⚠️ ${metadata.reason} - Recovering${attemptInfo}...`;
                                if (waitTime > 0) {
                                    stateMessage += ` (waiting ${waitTime}s)`;
                                }
                            } else {
                                stateMessage = `Recovering${attemptInfo}...`;
                            }
                        } else {
                            stateMessage = 'Recovering from error...';
                        }
                        break;

                    case AgentState.DONE:
                        stateMessage = '';
                        break;

                    case AgentState.ERROR:
                        stateMessage = metadata && metadata.error ? `Error: ${metadata.error}` : 'Error occurred';
                        // Auto-clear after 3 seconds
                        setTimeout(() => {
                            if (ChatState.currentState === AgentState.ERROR) {
                                setAgentState(AgentState.IDLE);
                            }
                        }, 3000);
                        break;
                }

                // Update state text display
                if (stateMessage) {
                    $stateText.text(stateMessage).show();
                } else {
                    $stateText.hide();
                }
            }

            /**
             * Scroll to bottom
             */
            function scrollToBottom() {
                const $messages = $('#snn-chat-messages');
                $messages.scrollTop($messages[0].scrollHeight);
            }

            /**
             * Clear chat
             */
            function clearChat() {
                ChatState.messages = [];
                ChatState.currentSessionId = null;
                $('#snn-chat-messages').html(`
                    <div class="snn-chat-welcome">
                        <h3>Conversation cleared</h3>
                        <p>Start a new conversation by typing a message.</p>
                    </div>
                `);
                // Show quick actions again
                $('.snn-chat-quick-actions').show();
            }

            /**
             * Auto-save conversation to database
             */
            function autoSaveConversation() {
                if (ChatState.messages.length === 0) {
                    return;
                }

                $.ajax({
                    url: snnChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_save_chat_history',
                        nonce: snnChatConfig.agentNonce,
                        messages: JSON.stringify(ChatState.messages),
                        session_id: ChatState.currentSessionId
                    },
                    success: function(response) {
                        if (response.success) {
                            ChatState.currentSessionId = response.data.session_id;
                            debugLog('✓ Chat history saved:', ChatState.currentSessionId);
                        }
                    }
                });
            }

            /**
             * Toggle history dropdown
             */
            function toggleHistoryDropdown() {
                const $dropdown = $('#snn-chat-history-dropdown');
                
                if ($dropdown.is(':visible')) {
                    $dropdown.hide();
                    return;
                }

                // Load histories
                loadChatHistories();
                $dropdown.show();
            }

            /**
             * Load chat histories from server
             */
            function loadChatHistories() {
                const $list = $('#snn-history-list');
                $list.html('<div class="snn-history-loading">Loading...</div>');

                $.ajax({
                    url: snnChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_get_chat_histories',
                        nonce: snnChatConfig.agentNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderHistoryList(response.data.histories);
                        } else {
                            $list.html('<div class="snn-history-empty">Failed to load histories</div>');
                        }
                    },
                    error: function() {
                        $list.html('<div class="snn-history-empty">Error loading histories</div>');
                    }
                });
            }

            /**
             * Render history list
             */
            function renderHistoryList(histories) {
                const $list = $('#snn-history-list');
                
                if (histories.length === 0) {
                    $list.html('<div class="snn-history-empty">No chat history yet</div>');
                    return;
                }

                let html = '';
                histories.forEach(function(history) {
                    const date = new Date(history.date);
                    const dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    const isActive = history.session_id === ChatState.currentSessionId;
                    
                    html += `<div class="snn-history-item ${isActive ? 'active' : ''}" data-session-id="${history.session_id}">
                        <div class="snn-history-content">
                            <div class="snn-history-title">${history.title}</div>
                            <div class="snn-history-meta">${history.message_count} messages • ${dateStr}</div>
                        </div>
                        <button class="snn-history-delete" data-session-id="${history.session_id}" title="Delete this chat">×</button>
                    </div>`;
                });

                $list.html(html);

                // Add click handlers
                $('.snn-history-item').on('click', function(e) {
                    // Don't load chat if clicking delete button
                    if ($(e.target).hasClass('snn-history-delete')) {
                        return;
                    }
                    const sessionId = $(this).data('session-id');
                    loadChatSession(sessionId);
                    $('#snn-chat-history-dropdown').hide();
                });

                // Delete button handler
                $('.snn-history-delete').on('click', function(e) {
                    e.stopPropagation();
                    const sessionId = $(this).data('session-id');
                    if (confirm('Delete this chat history?')) {
                        deleteChatSession(sessionId);
                    }
                });
            }

            /**
             * Delete a specific chat session
             */
            function deleteChatSession(sessionId) {
                $.ajax({
                    url: snnChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_delete_chat_history',
                        nonce: snnChatConfig.agentNonce,
                        session_id: sessionId
                    },
                    success: function(response) {
                        if (response.success) {
                            debugLog('✓ Deleted chat session:', sessionId);
                            // If the deleted session is the current one, clear the chat
                            if (ChatState.currentSessionId === sessionId) {
                                clearChat();
                            }
                            // Reload the history list
                            loadChatHistories();
                        } else {
                            alert('Failed to delete chat history.');
                        }
                    },
                    error: function() {
                        alert('Error deleting chat history.');
                    }
                });
            }

            /**
             * Load a specific chat session
             */
            function loadChatSession(sessionId) {
                $.ajax({
                    url: snnChatConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'snn_load_chat_history',
                        nonce: snnChatConfig.agentNonce,
                        session_id: sessionId
                    },
                    success: function(response) {
                        if (response.success && response.data.messages) {
                            // Clear current chat
                            $('#snn-chat-messages').empty();
                            $('.snn-chat-quick-actions').hide();
                            
                            // Load messages
                            ChatState.messages = response.data.messages;
                            ChatState.currentSessionId = sessionId;
                            
                            // Render all messages
                            response.data.messages.forEach(function(msg) {
                                const $message = $('<div>')
                                    .addClass('snn-chat-message')
                                    .addClass('snn-chat-message-' + msg.role)
                                    .html(formatMessage(msg.content));
                                $('#snn-chat-messages').append($message);
                            });
                            
                            scrollToBottom();
                            debugLog('✓ Loaded chat session:', sessionId);
                        }
                    }
                });
            }

        })(jQuery);
        </script>
        <?php
    }

    /**
     * Get inline CSS
     */
    private function get_inline_css() {
        $chat_width = $this->get_chat_width();
        return '
.snn-chat-overlay { position: fixed; top: 32px; right: 0; bottom: 0; z-index: 999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.snn-chat-container { width: ' . intval( $chat_width ) . 'px; height: 100%; background: #fff; box-shadow: -2px 0 16px rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; overflow: hidden; }
.snn-chat-header { background: #1d2327; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; user-select: none; }
.snn-chat-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 600; }
.snn-chat-title .dashicons { font-size: 20px; width: 20px; height: 20px; }
.snn-agent-state-badge { display: none; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(255, 255, 255, 0.3); transition: all 0.3s ease; }
.snn-agent-state-badge.badge-thinking { background: rgba(255, 255, 255, 0.95); color: #667eea; animation: badgePulse 1.5s ease-in-out infinite; }
.snn-agent-state-badge.badge-executing { background: rgba(255, 255, 255, 0.95); color: #f57c00; animation: badgePulse 1.2s ease-in-out infinite; }
.snn-agent-state-badge.badge-interpreting { background: rgba(255, 255, 255, 0.95); color: #388e3c; animation: badgePulse 1.5s ease-in-out infinite; }
.snn-agent-state-badge.badge-done { background: rgba(255, 255, 255, 0.95); color: #2e7d32; }
.snn-agent-state-badge.badge-error { background: rgba(255, 255, 255, 0.95); color: #c62828; animation: badgeShake 0.5s ease-in-out; }
.snn-agent-state-badge.badge-retrying { background: rgba(255, 255, 255, 0.95); color: #ff9800; animation: badgePulse 1s ease-in-out infinite; }
.snn-agent-state-badge.badge-recovering { background: rgba(255, 255, 255, 0.95); color: #ff6f00; animation: badgePulse 1.3s ease-in-out infinite; }
@keyframes badgePulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.05); opacity: 0.9; } }
@keyframes badgeShake { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-3deg); } 75% { transform: rotate(3deg); } }
.snn-chat-controls { display: flex; gap: 4px; }
.snn-chat-btn { background: rgba(255, 255, 255, 0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.snn-chat-btn:hover { background: rgba(255, 255, 255, 0.3); }
.snn-chat-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
.snn-chat-plus { font-size: 24px; line-height: 1; font-weight: 300; position:relative; top:-3px; }
.snn-expand-icon { font-size: 18px; line-height: 1; }
.snn-chat-container.snn-chat-expanded { width: calc(100vw - 175px); transition: width 0.3s ease; }
.snn-chat-container { transition: width 0.3s ease; }
.snn-chat-history-dropdown { position: absolute; top: 60px; left: 0; right: 0; background: #fff; border-bottom: 1px solid #ddd; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); z-index: 10; }
.snn-history-header { padding: 12px 16px; background: #f5f5f5; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
.snn-history-header strong { font-size: 14px; color: #333; }
.snn-history-close { background: none; border: none; font-size: 24px; color: #666; cursor: pointer; padding: 0; line-height: 1; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; }
.snn-history-close:hover { color: #000; }
.snn-history-list { padding: 8px 0; }
.snn-history-item { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.2s; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.snn-history-item:hover { background: #f9f9f9; }
.snn-history-item.active { background: #e3f2fd; border-left: 3px solid #2196f3; }
.snn-history-content { flex: 1; min-width: 0; }
.snn-history-title { font-weight: 600; color: #333; font-size: 14px; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.snn-history-meta { font-size: 12px; color: #666; }
.snn-history-delete { background: none; border: none; font-size: 20px; color: #999; cursor: pointer; padding: 0 4px; line-height: 1; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: all 0.2s; flex-shrink: 0; }
.snn-history-delete:hover { background: #fee; color: #c33; }
.snn-history-loading, .snn-history-empty { padding: 20px; text-align: center; color: #999; font-size: 14px; }
.snn-chat-messages { flex: 1; overflow-y: auto; padding: 10px; background: #f9f9f9; }
.snn-chat-welcome { text-align: center; padding: 40px 20px; color: #666; }
.snn-chat-welcome-icon { width: 64px; height: 64px; margin: 0 auto 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.snn-chat-welcome-icon .dashicons { color: #fff; font-size: 32px; width: 32px; height: 32px; }
.snn-chat-welcome h3 { margin: 0 0 12px; font-size: 20px; color: #333; }
.snn-chat-welcome p { margin: 12px 0; line-height: 1.6; }
.snn-chat-welcome ul { text-align: left; max-width: 280px; margin: 16px auto; padding-left: 20px; }
.snn-chat-welcome li { margin: 8px 0; line-height: 1.5; text-align: center; }
.snn-chat-ai-disabled-warning { text-align: center; padding: 60px 30px; color: #666; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; }
.snn-chat-ai-disabled-warning .snn-warning-icon { font-size: 48px; margin-bottom: 30px; }
.snn-chat-ai-disabled-warning h3 { margin: 0 0 12px; font-size: 18px; color: #d63638; font-weight: 600; }
.snn-chat-ai-disabled-warning p { margin: 0 0 20px; line-height: 1.6; color: #666; font-size: 14px; }
.snn-enable-ai-btn { display: inline-block; padding: 10px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; transition: background 0.2s; }
.snn-enable-ai-btn:hover { background: #135e96; color: #fff; text-decoration: none; }
.snn-chat-input:disabled, .snn-chat-send:disabled { opacity: 0.5; cursor: not-allowed; }
.snn-chat-message { margin-bottom: 5px; padding: 8px; border-radius: 12px; line-height: 1.5; max-width: 95%; word-wrap: break-word; }
.snn-chat-message-user { background: #1d2327; color: #fff; margin-left: auto; border-bottom-right-radius: 4px; }
.snn-chat-message-user code { background: rgba(255,255,255,0.2); color: #fff; }
.snn-chat-message-user pre { background: rgba(0,0,0,0.3); }
.snn-chat-message-user a { color: #a8d4ff; }
.snn-chat-message-user blockquote { border-left-color: rgba(255,255,255,0.5); background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); }
.snn-chat-message-assistant { background: #fff; color: #333; border: 1px solid #e0e0e0; margin-right: auto; border-bottom-left-radius: 4px; }
.snn-chat-message-error { background: #fee; color: #c33; border: 1px solid #fcc; margin-right: auto; }
.snn-chat-state-message { padding: 8px 14px; margin: 8px auto; border-radius: 16px; font-size: 12px; font-weight: 500; text-align: center; max-width: 80%; animation: fadeInScale 0.3s ease-out; }
.snn-chat-state-message.state-thinking { background: linear-gradient(90deg, #e3f2fd, #f3e5f5); color: #667eea; border: 1px solid #bbdefb; }
.snn-chat-state-message.state-executing { background: linear-gradient(90deg, #fff3e0, #ffe0b2); color: #f57c00; border: 1px solid #ffcc80; }
.snn-chat-state-message.state-interpreting { background: linear-gradient(90deg, #e8f5e9, #c8e6c9); color: #388e3c; border: 1px solid #a5d6a7; }
.snn-chat-state-message.state-done { background: linear-gradient(90deg, #e8f5e9, #c8e6c9); color: #2e7d32; border: 1px solid #81c784; }
.snn-chat-state-message.state-error { background: linear-gradient(90deg, #ffebee, #ffcdd2); color: #c62828; border: 1px solid #ef9a9a; }
.snn-chat-state-message.state-retrying { background: linear-gradient(90deg, #fff8e1, #ffecb3); color: #ff8f00; border: 1px solid #ffd54f; }
@keyframes fadeInScale { from { opacity: 0; transform: scale(0.9) translateY(-10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.ability-results { margin-top: 0px; padding-top: 0px; }
.ability-result { padding: 6px 10px; margin: 4px 0; border-radius: 6px; font-size: 14px; line-height: 1.4; }
.ability-result.success { background: #f0f9ff; }
.ability-result.error { background: #fef2f2; border: 1px solid #fecaca; }
.ability-result strong { display: inline; margin-right: 6px; }
.result-data { color: #666; font-size: 14px; margin-top: 3px; line-height: 1.5; display: inline; }
.result-data strong { color: #444; font-weight: 600; margin-right: 2px; }
.result-error { color: #dc2626; font-size: 12px; }
.json-result-container { margin-top: 8px; max-height: 120px; overflow-y: auto; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px; }
.json-result { margin: 0; padding: 10px; font-family: Courier, monospace; font-size: 14px; line-height: 1.2; white-space: pre; overflow-x: auto; color: #333; }
.json-key { color: #0066cc; font-weight: 600; }
.json-string { color: #22863a; }
.json-number { color: #005cc5; }
.json-boolean { color: #d73a49; font-weight: 600; }
.json-null { color: #6f42c1; font-style: italic; }
.snn-chat-message code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-size: 12px; font-family: Consolas, Monaco, "Courier New", monospace; }
.snn-chat-message pre { background: #1e1e1e; color: #d4d4d4; padding: 12px; border-radius: 6px; overflow-x: auto; margin: 8px 0; font-family: Consolas, Monaco, "Courier New", monospace; font-size: 13px; line-height: 1.4; }
.snn-chat-message pre code { background: transparent; padding: 0; color: inherit; font-size: inherit; }
.snn-chat-message h1, .snn-chat-message h2, .snn-chat-message h3, .snn-chat-message h4, .snn-chat-message h5, .snn-chat-message h6 { margin: 12px 0 8px 0; font-weight: 600; line-height: 1.3; }
.snn-chat-message h1 { font-size: 1.4em; }
.snn-chat-message h2 { font-size: 1.25em; }
.snn-chat-message h3 { font-size: 1.1em; }
.snn-chat-message h4, .snn-chat-message h5, .snn-chat-message h6 { font-size: 1em; }
.snn-chat-message ul, .snn-chat-message ol { margin: 8px 0; padding-left: 20px; }
.snn-chat-message li { margin: 4px 0; line-height: 1.5; }
.snn-chat-message blockquote { margin: 8px 0; padding: 8px 12px; border-left: 3px solid #667eea; background: #f8f9fa; color: #555; }
.snn-chat-message a { color: #667eea; text-decoration: none; }
.snn-chat-message a:hover { text-decoration: underline; }
.snn-chat-message p { margin: 8px 0; }
.snn-chat-message p:first-child { margin-top: 0; }
.snn-chat-message p:last-child { margin-bottom: 0; }
.snn-chat-message hr { border: none; border-top: 1px solid #e0e0e0; margin: 12px 0; }
.snn-chat-message table { border-collapse: collapse; width: 100%; margin: 8px 0; font-size: 13px; }
.snn-chat-message th, .snn-chat-message td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
.snn-chat-message th { background: #f5f5f5; font-weight: 600; }
.snn-chat-message img { max-width: 100%; height: auto; border-radius: 4px; }
.snn-chat-typing { padding: 5px 20px; background: #f9f9f900; display: flex; align-items: center; gap: 8px; }
.typing-dots { display: flex; gap: 4px; }
.typing-dots span { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #999; animation: typing 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.5; } 30% { transform: translateY(-8px); opacity: 1; } }
.snn-chat-state-text { display: none; padding: 2px 16px; background: #ffffff73; font-size: 14px; color: #000; text-align: left; }
.snn-chat-quick-actions { padding: 8px 10px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 6px; flex-wrap: wrap; }
.snn-quick-action-btn { padding: 6px 12px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; color: #333; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.snn-quick-action-btn:hover { background: #1d2327; color: #fff; border-color: #1d2327; }
.snn-chat-input-container { padding: 10px; background: #fff; border-top: 1px solid #e0e0e0; display: flex; gap: 12px; align-items: flex-end; }
.snn-chat-input { flex: 1; border: 1px solid #ddd; border-radius: 8px; padding: 10px 12px; font-size: 14px; resize: none; outline: none; font-family: inherit; min-height: 42px; max-height: 120px; }
.snn-chat-input:focus { border-color: #667eea; }
.snn-chat-send { width: 42px; height: 42px; background: #1d2327; border: none; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; flex-shrink: 0; }
.snn-chat-send:hover { transform: scale(1.05); }
.snn-chat-send:active { transform: scale(0.95); }
.snn-chat-send .dashicons { font-size: 20px; width: 20px; height: 20px; rotate: 90deg; }
#wpadminbar #wp-admin-bar-snn-ai-chat .ab-icon:before { content: "\f125"; top: 2px; }
@media (max-width: 768px) { .snn-chat-container { width: 100vw; height: 100%; } .snn-chat-overlay { top: 0; right: 0; } }
        ';
    }
}

// Initialize
SNN_Chat_Overlay::get_instance();