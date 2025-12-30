# SNN AI Agent System - Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                     WordPress Admin Interface                        │
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │              SNN Settings > AI Agents                         │  │
│  │                                                                │  │
│  │  ┌────────────────────────────────────────────────────────┐  │  │
│  │  │  Enable/Disable Agents                                  │  │  │
│  │  │  ☑ Content Generator                                    │  │  │
│  │  │  ☑ SEO Optimizer                                        │  │  │
│  │  │  ☑ Design Assistant                                     │  │  │
│  │  │  ☑ Code Assistant                                       │  │  │
│  │  └────────────────────────────────────────────────────────┘  │  │
│  │                                                                │  │
│  │  View Capabilities | WordPress Abilities API Integration     │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SNN_AI_Agent_Registry Class                       │
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  Agent Management                                              │ │
│  │  • register_agent()     - Register new agents                 │ │
│  │  • get_agents()         - Get all agents                      │ │
│  │  • get_agent($id)       - Get specific agent                  │ │
│  │  • user_can_use_agent() - Check permissions                   │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  WordPress Integration                                         │ │
│  │  • register_capabilities() - Add WP capabilities              │ │
│  │  • add_admin_menu()       - Register admin menu               │ │
│  │  • handle_agent_execution() - AJAX handler                    │ │
│  └───────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
        ┌──────────────────┬──────────────────┬──────────────────┐
        │                  │                  │                  │
        ▼                  ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   Content    │  │     SEO      │  │   Design     │  │     Code     │
│  Generator   │  │  Optimizer   │  │  Assistant   │  │  Assistant   │
├──────────────┤  ├──────────────┤  ├──────────────┤  ├──────────────┤
│Capabilities: │  │Capabilities: │  │Capabilities: │  │Capabilities: │
│• generate_   │  │• generate_   │  │• generate_   │  │• generate_   │
│  content     │  │  seo_title   │  │  css         │  │  code        │
│• refine_     │  │• generate_   │  │• suggest_    │  │• explain_    │
│  content     │  │  seo_desc    │  │  design      │  │  code        │
│• summarize_  │  │• analyze_    │  │• generate_   │  │• optimize_   │
│  content     │  │  seo         │  │  html        │  │  code        │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘
        │                  │                  │                  │
        └──────────────────┴──────────────────┴──────────────────┘
                                    │
                                    ▼
            ┌──────────────────────────────────────────┐
            │    Integration with Existing AI Stack    │
            ├──────────────────────────────────────────┤
            │  • ai-settings.php  - Configuration      │
            │  • ai-api.php       - API Management     │
            │  • ai-overlay.php   - UI Layer          │
            │  • ai-seo-generation.php - SEO Features  │
            │  • ai-design.php    - Design Features    │
            └──────────────────────────────────────────┘
                                    │
                                    ▼
            ┌──────────────────────────────────────────┐
            │     WordPress Abilities API (6.9+)       │
            ├──────────────────────────────────────────┤
            │  • Capability-based access control       │
            │  • Role management integration           │
            │  • Future WordPress 7.0 ready            │
            └──────────────────────────────────────────┘
```

## Agent Lifecycle

```
┌────────────────┐
│ Agent Created  │
│ via register_  │
│ agent()        │
└───────┬────────┘
        │
        ▼
┌────────────────┐         ┌─────────────────┐
│  Capabilities  │────────▶│  WordPress      │
│  Registered    │         │  Role System    │
└───────┬────────┘         └─────────────────┘
        │
        ▼
┌────────────────┐
│  Agent Enabled │
│  by Admin      │
└───────┬────────┘
        │
        ▼
┌────────────────┐         ┌─────────────────┐
│  Permission    │────────▶│  User Can Use   │
│  Check         │         │  Agent?         │
└───────┬────────┘         └─────────────────┘
        │                            │
        │ YES                        │ NO
        ▼                            ▼
┌────────────────┐         ┌─────────────────┐
│  AJAX Request  │         │  Access Denied  │
│  to Execute    │         └─────────────────┘
└───────┬────────┘
        │
        ▼
┌────────────────┐
│  Handler       │
│  Executes      │
└───────┬────────┘
        │
        ▼
┌────────────────┐
│  Result        │
│  Returned      │
└────────────────┘
```

## Permission Flow

```
User Action
    │
    ▼
┌─────────────────────────────┐
│ Is AI Enabled?              │──NO──▶ Access Denied
└─────────────┬───────────────┘
              │ YES
              ▼
┌─────────────────────────────┐
│ User has 'use_ai_agents'?   │──NO──▶ Access Denied
└─────────────┬───────────────┘
              │ YES
              ▼
┌─────────────────────────────┐
│ Is Agent Enabled?           │──NO──▶ Access Denied
└─────────────┬───────────────┘
              │ YES
              ▼
┌─────────────────────────────┐
│ Custom Filter Check?        │──NO──▶ Access Denied
└─────────────┬───────────────┘
              │ YES
              ▼
         Access Granted
              │
              ▼
      Execute Agent Action
```

## Helper Functions API

```php
// Register Custom Agent
snn_register_ai_agent($id, $args)
    │
    ├─▶ Validates arguments
    ├─▶ Registers capabilities
    ├─▶ Stores in registry
    └─▶ Returns success status

// Check Permissions
snn_user_can_use_ai_agent($agent_id, $user_id)
    │
    ├─▶ Check AI enabled
    ├─▶ Check agent exists
    ├─▶ Check user capabilities
    ├─▶ Apply filters
    └─▶ Returns boolean

// Get Agent Information
snn_get_ai_agent($id)
    │
    ├─▶ Lookup in registry
    └─▶ Returns agent array or null

// Get All Agents
snn_get_ai_agents()
    │
    └─▶ Returns array of all agents
```

## Data Flow Example

```
Frontend Request
    │
    │ AJAX: snn_ai_agent_execute
    ▼
┌──────────────────────┐
│ Nonce Verification   │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ Permission Check     │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ Get Agent Handler    │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ Execute Handler      │
│ with Action & Data   │
└─────────┬────────────┘
          │
          ▼
┌──────────────────────┐
│ Return Result        │
│ (JSON Response)      │
└──────────────────────┘
```

## Integration Points

```
ai-agent.php
    │
    ├─▶ ai-settings.php
    │   └─▶ Check if AI enabled
    │   └─▶ Get API configuration
    │
    ├─▶ ai-api.php
    │   └─▶ Get API endpoint
    │   └─▶ Get model settings
    │
    ├─▶ ai-overlay.php
    │   └─▶ UI for agent actions
    │
    ├─▶ ai-seo-generation.php
    │   └─▶ SEO Optimizer integration
    │
    └─▶ ai-design.php
        └─▶ Design Assistant integration
```

## WordPress Capabilities Structure

```
Administrator
    │
    ├─▶ manage_ai_agents (Full Control)
    ├─▶ use_ai_agents (Can Use)
    └─▶ All agent capabilities

Editor
    │
    └─▶ use_ai_agents (Can Use)

Custom Roles
    │
    └─▶ Filter: snn_ai_agent_user_can
        └─▶ Custom logic for permissions
```

## Future Enhancements (WordPress 7.0+)

```
Current System (v1.0.0)
    │
    ├─▶ Agent Registry
    ├─▶ Basic Capabilities
    ├─▶ Simple Handlers
    └─▶ Admin Interface
         │
         ▼
Future Enhancements
    │
    ├─▶ Multi-Agent Orchestration
    │   └─▶ Agents work together
    │
    ├─▶ Advanced Abilities API
    │   └─▶ Native WP integration
    │
    ├─▶ Agent Learning
    │   └─▶ Improve over time
    │
    └─▶ Extended Capabilities
        └─▶ More sophisticated tasks
```
