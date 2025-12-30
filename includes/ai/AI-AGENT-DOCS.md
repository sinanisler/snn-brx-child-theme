# AI Agent System Documentation

## Overview

The AI Agent system provides a scalable foundation for managing AI-powered features in the SNN-BRX child theme. It leverages WordPress capabilities and is designed to integrate with the WordPress 6.9+ Abilities API and future WordPress 7.0 AI enhancements.

## Architecture

### Components

1. **Agent Registry** (`SNN_AI_Agent_Registry` class)
   - Manages registered AI agents
   - Handles capability registration
   - Provides admin interface
   - Processes agent execution requests

2. **Default Agents**
   - **Content Generator**: Generates and refines content
   - **SEO Optimizer**: Optimizes content for search engines
   - **Design Assistant**: Helps with CSS and design
   - **Code Assistant**: Assists with code generation

3. **Capabilities System**
   - WordPress capability integration
   - Role-based access control
   - Per-agent capability tracking

## Usage

### Registering a Custom Agent

```php
// Register a custom AI agent
snn_register_ai_agent('custom_agent', [
    'name' => __('Custom Agent', 'snn'),
    'description' => __('My custom AI agent description', 'snn'),
    'capabilities' => ['custom_capability_1', 'custom_capability_2'],
    'handler' => 'my_custom_agent_handler',
    'enabled' => true,
    'version' => '1.0.0',
]);

// Handler function
function my_custom_agent_handler($action, $data) {
    // Process the action and data
    // Return result
    return [
        'success' => true,
        'message' => 'Action completed',
        'result' => $data
    ];
}
```

### Checking Agent Permissions

```php
// Check if current user can use an agent
if (snn_user_can_use_ai_agent('content_generator')) {
    // User has permission to use the content generator
}

// Check for specific user
if (snn_user_can_use_ai_agent('seo_optimizer', $user_id)) {
    // Specific user has permission
}
```

### Getting Agent Information

```php
// Get all registered agents
$agents = snn_get_ai_agents();

// Get specific agent
$agent = snn_get_ai_agent('content_generator');
if ($agent) {
    echo $agent['name'];
    echo $agent['description'];
    print_r($agent['capabilities']);
}
```

### AJAX Execution

```javascript
// Execute an agent action via AJAX
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'snn_ai_agent_execute',
        nonce: snnConfig.nonce,
        agent_id: 'content_generator',
        action_type: 'generate_content',
        data: {
            content: 'Original content...',
            prompt: 'Make it more engaging'
        }
    },
    success: function(response) {
        if (response.success) {
            console.log('Agent result:', response.data);
        }
    }
});
```

## Admin Interface

Navigate to **SNN Settings > AI Agents** to:
- Enable/disable individual agents
- View agent capabilities
- See WordPress Abilities API integration information

## Integration with Existing AI Features

The AI Agent system integrates with:
- **ai-settings.php**: Uses AI configuration settings
- **ai-api.php**: Leverages AI API configuration
- **ai-seo-generation.php**: SEO optimizer agent integration
- **ai-design.php**: Design assistant agent integration

## WordPress Abilities API

The system is designed to leverage the WordPress Abilities API introduced in WordPress 6.9:
- Capability-based access control
- Extensible agent registration
- Future-proof architecture

## Customization

### Adding Capabilities

Custom capabilities can be added through filters:

```php
add_filter('snn_ai_agent_user_can', function($can_use, $agent_id, $user_id) {
    // Custom logic to determine if user can use agent
    if ($agent_id === 'my_custom_agent') {
        return current_user_can('custom_capability');
    }
    return $can_use;
}, 10, 3);
```

### Extending Agent Handlers

Agent handlers can be extended or replaced:

```php
// Replace default handler
function my_content_generation_handler($action, $data) {
    // Custom implementation
    return ['result' => 'custom result'];
}

// Register agent with custom handler
snn_register_ai_agent('content_generator', [
    'handler' => 'my_content_generation_handler',
    // ... other options
]);
```

## Security

- All AJAX requests are nonce-protected
- Capability checks on every agent execution
- User permissions verified before action processing
- Sanitization of all input data

## Future Enhancements

Ready for WordPress 7.0 features:
- Enhanced AI capabilities API
- Native WordPress AI integration
- Advanced agent orchestration
- Multi-agent collaboration

## Support

For issues or questions:
- GitHub Issues: [Report a bug](https://github.com/sinanisler/snn-brx-child-theme/issues)
- Discussions: [Ask a question](https://github.com/sinanisler/snn-brx-child-theme/discussions)
