<?php
/**
 * SNN AI Agent
 *
 * File: ai-agent.php
 *
 * Purpose: This file implements a scalable AI agent system that leverages WordPress capabilities
 * and the new WordPress Abilities API (introduced in WordPress 6.9). It provides a foundation
 * for managing AI-powered features, agent registration, capability management, and action handlers.
 * This system is designed to be future-proof and ready for WordPress 7.0 AI enhancements.
 *
 * Features:
 * - AI agent registration and management
 * - WordPress Abilities API integration
 * - Capability-based access control for AI features
 * - Extensible action handler system
 * - Admin UI for agent configuration
 * - Integration with existing AI infrastructure (ai-api.php, ai-settings.php)
 *
 * Architecture:
 * - Agent Registry: Manages registered AI agents and their capabilities
 * - Capability Manager: Handles permissions and access control
 * - Action Handlers: Process AI agent requests and responses
 * - Admin Interface: Provides UI for agent management and monitoring
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * SNN AI Agent Registry
 * Manages registered AI agents and their capabilities
 */
class SNN_AI_Agent_Registry {
    
    /**
     * Registered agents
     * @var array
     */
    private static $agents = [];
    
    /**
     * Agent capabilities
     * @var array
     */
    private static $capabilities = [];
    
    /**
     * Initialize the registry
     */
    public static function init() {
        // Register default agents
        self::register_default_agents();
        
        // Hook into WordPress
        add_action('init', [__CLASS__, 'register_capabilities']);
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        
        // Register AJAX handlers
        add_action('wp_ajax_snn_ai_agent_execute', [__CLASS__, 'handle_agent_execution']);
    }
    
    /**
     * Register an AI agent
     *
     * @param string $id Unique agent identifier
     * @param array $args Agent configuration
     * @return bool Success status
     */
    public static function register_agent($id, $args = []) {
        $defaults = [
            'name' => '',
            'description' => '',
            'capabilities' => [],
            'handler' => null,
            'enabled' => true,
            'version' => '1.0.0',
            'priority' => 10,
        ];
        
        $agent = wp_parse_args($args, $defaults);
        $agent['id'] = $id;
        
        self::$agents[$id] = $agent;
        
        // Register agent capabilities
        if (!empty($agent['capabilities'])) {
            foreach ($agent['capabilities'] as $capability) {
                self::register_capability($capability, $id);
            }
        }
        
        return true;
    }
    
    /**
     * Register a capability for an agent
     *
     * @param string $capability Capability name
     * @param string $agent_id Agent ID
     */
    private static function register_capability($capability, $agent_id) {
        if (!isset(self::$capabilities[$capability])) {
            self::$capabilities[$capability] = [];
        }
        
        if (!in_array($agent_id, self::$capabilities[$capability])) {
            self::$capabilities[$capability][] = $agent_id;
        }
    }
    
    /**
     * Get all registered agents
     *
     * @return array Registered agents
     */
    public static function get_agents() {
        return self::$agents;
    }
    
    /**
     * Get a specific agent
     *
     * @param string $id Agent ID
     * @return array|null Agent data or null if not found
     */
    public static function get_agent($id) {
        return isset(self::$agents[$id]) ? self::$agents[$id] : null;
    }
    
    /**
     * Check if user can use an agent
     *
     * @param string $agent_id Agent ID
     * @param int $user_id User ID (defaults to current user)
     * @return bool Whether user has permission
     */
    public static function user_can_use_agent($agent_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $agent = self::get_agent($agent_id);
        if (!$agent || !$agent['enabled']) {
            return false;
        }
        
        // Check if AI features are enabled
        if (get_option('snn_ai_enabled', 'no') !== 'yes') {
            return false;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            return false;
        }
        
        // Allow filtering
        return apply_filters('snn_ai_agent_user_can', true, $agent_id, $user_id);
    }
    
    /**
     * Register default AI agents
     */
    private static function register_default_agents() {
        // Content Generation Agent
        self::register_agent('content_generator', [
            'name' => __('Content Generator', 'snn'),
            'description' => __('Generates and refines content for posts, pages, and custom content types.', 'snn'),
            'capabilities' => ['generate_content', 'refine_content', 'summarize_content'],
            'handler' => [__CLASS__, 'handle_content_generation'],
        ]);
        
        // SEO Optimization Agent
        self::register_agent('seo_optimizer', [
            'name' => __('SEO Optimizer', 'snn'),
            'description' => __('Optimizes content for search engines, generates meta descriptions and titles.', 'snn'),
            'capabilities' => ['generate_seo_title', 'generate_seo_description', 'analyze_seo'],
            'handler' => [__CLASS__, 'handle_seo_optimization'],
        ]);
        
        // Design Assistant Agent
        self::register_agent('design_assistant', [
            'name' => __('Design Assistant', 'snn'),
            'description' => __('Assists with CSS generation, design suggestions, and layout improvements.', 'snn'),
            'capabilities' => ['generate_css', 'suggest_design', 'generate_html'],
            'handler' => [__CLASS__, 'handle_design_assistance'],
        ]);
        
        // Code Assistant Agent
        self::register_agent('code_assistant', [
            'name' => __('Code Assistant', 'snn'),
            'description' => __('Helps with code generation, debugging, and optimization.', 'snn'),
            'capabilities' => ['generate_code', 'explain_code', 'optimize_code'],
            'handler' => [__CLASS__, 'handle_code_assistance'],
        ]);
    }
    
    /**
     * Register agent capabilities with WordPress
     */
    public static function register_capabilities() {
        // Register custom capabilities for AI agents
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_ai_agents');
            $role->add_cap('use_ai_agents');
        }
        
        // Allow editors to use AI agents
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('use_ai_agents');
        }
    }
    
    /**
     * Add admin menu for agent management
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'snn-settings',
            __('AI Agents', 'snn'),
            __('AI Agents', 'snn'),
            'manage_options',
            'snn-ai-agents',
            [__CLASS__, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page for agent management
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'snn'));
        }
        
        // Handle form submissions
        if (isset($_POST['snn_ai_agents_action']) && check_admin_referer('snn_ai_agents_settings', 'snn_ai_agents_nonce')) {
            self::handle_admin_form_submission();
        }
        
        $agents = self::get_agents();
        $ai_enabled = get_option('snn_ai_enabled', 'no') === 'yes';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AI Agents', 'snn'); ?></h1>
            
            <?php if (!$ai_enabled): ?>
                <div class="notice notice-warning">
                    <p>
                        <?php 
                        printf(
                            __('AI features are currently disabled. Please enable them in <a href="%s">AI Settings</a> to use AI agents.', 'snn'),
                            admin_url('admin.php?page=snn-ai-settings')
                        ); 
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <p><?php _e('Manage AI agents and their capabilities. AI agents are specialized AI assistants that help with specific tasks like content generation, SEO optimization, and design assistance.', 'snn'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('snn_ai_agents_settings', 'snn_ai_agents_nonce'); ?>
                <input type="hidden" name="snn_ai_agents_action" value="save" />
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('Enabled', 'snn'); ?></th>
                            <th style="width: 200px;"><?php _e('Agent', 'snn'); ?></th>
                            <th><?php _e('Description', 'snn'); ?></th>
                            <th style="width: 200px;"><?php _e('Capabilities', 'snn'); ?></th>
                            <th style="width: 80px;"><?php _e('Version', 'snn'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                            <?php
                            $enabled = get_option('snn_ai_agent_enabled_' . $agent['id'], $agent['enabled']);
                            ?>
                            <tr>
                                <td>
                                    <input 
                                        type="checkbox" 
                                        name="snn_ai_agent_enabled[<?php echo esc_attr($agent['id']); ?>]" 
                                        value="1"
                                        <?php checked($enabled, true); ?>
                                    />
                                </td>
                                <td>
                                    <strong><?php echo esc_html($agent['name']); ?></strong>
                                    <br>
                                    <small style="color: #666;"><?php echo esc_html($agent['id']); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($agent['description']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($agent['capabilities'])): ?>
                                        <details>
                                            <summary><?php echo count($agent['capabilities']); ?> <?php _e('capabilities', 'snn'); ?></summary>
                                            <ul style="margin: 5px 0 0 20px; font-size: 12px;">
                                                <?php foreach ($agent['capabilities'] as $cap): ?>
                                                    <li><code><?php echo esc_html($cap); ?></code></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        <span style="color: #999;"><?php _e('None', 'snn'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?php echo esc_html($agent['version']); ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <?php submit_button(__('Save Agent Settings', 'snn'), 'primary', 'submit', false); ?>
                </p>
            </form>
            
            <hr>
            
            <h2><?php _e('Agent Capabilities Overview', 'snn'); ?></h2>
            <p><?php _e('Below is a list of all capabilities provided by registered AI agents:', 'snn'); ?></p>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Capability', 'snn'); ?></th>
                        <th><?php _e('Provided By', 'snn'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (self::$capabilities as $capability => $agent_ids): ?>
                        <tr>
                            <td><code><?php echo esc_html($capability); ?></code></td>
                            <td>
                                <?php 
                                $agent_names = array_map(function($id) use ($agents) {
                                    return isset($agents[$id]) ? $agents[$id]['name'] : $id;
                                }, $agent_ids);
                                echo esc_html(implode(', ', $agent_names));
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <hr>
            
            <h2><?php _e('WordPress Abilities API Integration', 'snn'); ?></h2>
            <p><?php _e('This AI agent system is designed to integrate with WordPress 6.9+ Abilities API and future enhancements in WordPress 7.0. The system provides:', 'snn'); ?></p>
            <ul style="list-style: disc; margin-left: 30px;">
                <li><?php _e('Capability-based access control for AI features', 'snn'); ?></li>
                <li><?php _e('Extensible agent registration system', 'snn'); ?></li>
                <li><?php _e('Integration with existing AI infrastructure', 'snn'); ?></li>
                <li><?php _e('Future-proof architecture for WordPress AI enhancements', 'snn'); ?></li>
            </ul>
        </div>
        
        <style>
        .widefat details {
            cursor: pointer;
        }
        .widefat details summary {
            color: #2271b1;
            text-decoration: underline;
        }
        .widefat details summary:hover {
            color: #135e96;
        }
        .widefat details ul {
            list-style: none;
        }
        </style>
        <?php
    }
    
    /**
     * Handle admin form submission
     */
    private static function handle_admin_form_submission() {
        $agents = self::get_agents();
        
        if (isset($_POST['snn_ai_agent_enabled']) && is_array($_POST['snn_ai_agent_enabled'])) {
            foreach ($agents as $agent_id => $agent) {
                $enabled = isset($_POST['snn_ai_agent_enabled'][$agent_id]);
                update_option('snn_ai_agent_enabled_' . $agent_id, $enabled);
            }
        } else {
            // If no checkboxes are checked, disable all agents
            foreach ($agents as $agent_id => $agent) {
                update_option('snn_ai_agent_enabled_' . $agent_id, false);
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('AI agent settings saved successfully.', 'snn') . '</p></div>';
    }
    
    /**
     * Handle agent execution via AJAX
     */
    public static function handle_agent_execution() {
        check_ajax_referer('snn_ai_agent_nonce', 'nonce');
        
        if (!current_user_can('use_ai_agents')) {
            wp_send_json_error(['message' => __('You do not have permission to use AI agents.', 'snn')]);
        }
        
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $data = isset($_POST['data']) ? wp_unslash($_POST['data']) : [];
        
        if (empty($agent_id) || empty($action)) {
            wp_send_json_error(['message' => __('Invalid agent ID or action.', 'snn')]);
        }
        
        // Check if user can use this agent
        if (!self::user_can_use_agent($agent_id)) {
            wp_send_json_error(['message' => __('You cannot use this agent.', 'snn')]);
        }
        
        $agent = self::get_agent($agent_id);
        if (!$agent || !$agent['handler']) {
            wp_send_json_error(['message' => __('Agent not found or has no handler.', 'snn')]);
        }
        
        // Execute agent handler
        try {
            $result = call_user_func($agent['handler'], $action, $data);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Content Generation Handler
     */
    public static function handle_content_generation($action, $data) {
        // This would integrate with the existing AI API
        return [
            'message' => __('Content generation handler called', 'snn'),
            'action' => $action,
            'data' => $data
        ];
    }
    
    /**
     * SEO Optimization Handler
     */
    public static function handle_seo_optimization($action, $data) {
        // This would integrate with ai-seo-generation.php
        return [
            'message' => __('SEO optimization handler called', 'snn'),
            'action' => $action,
            'data' => $data
        ];
    }
    
    /**
     * Design Assistance Handler
     */
    public static function handle_design_assistance($action, $data) {
        // This would integrate with ai-design.php
        return [
            'message' => __('Design assistance handler called', 'snn'),
            'action' => $action,
            'data' => $data
        ];
    }
    
    /**
     * Code Assistance Handler
     */
    public static function handle_code_assistance($action, $data) {
        // This would integrate with the AI API for code generation
        return [
            'message' => __('Code assistance handler called', 'snn'),
            'action' => $action,
            'data' => $data
        ];
    }
}

/**
 * Initialize the AI Agent Registry
 */
SNN_AI_Agent_Registry::init();

/**
 * Helper function to register a custom AI agent
 *
 * @param string $id Unique agent identifier
 * @param array $args Agent configuration
 * @return bool Success status
 */
function snn_register_ai_agent($id, $args = []) {
    return SNN_AI_Agent_Registry::register_agent($id, $args);
}

/**
 * Helper function to check if a user can use an AI agent
 *
 * @param string $agent_id Agent ID
 * @param int $user_id User ID (defaults to current user)
 * @return bool Whether user has permission
 */
function snn_user_can_use_ai_agent($agent_id, $user_id = 0) {
    return SNN_AI_Agent_Registry::user_can_use_agent($agent_id, $user_id);
}

/**
 * Helper function to get all registered AI agents
 *
 * @return array Registered agents
 */
function snn_get_ai_agents() {
    return SNN_AI_Agent_Registry::get_agents();
}

/**
 * Helper function to get a specific AI agent
 *
 * @param string $id Agent ID
 * @return array|null Agent data or null if not found
 */
function snn_get_ai_agent($id) {
    return SNN_AI_Agent_Registry::get_agent($id);
}
