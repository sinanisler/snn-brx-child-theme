/*

SNN Abilities API Development PlanOverview
A simplified, self-contained implementation of the WordPress Abilities API concept for AI agent integrations. This will provide a standardized way to register, discover, and execute capabilities within WordPress.Phase 1: Core Architecture1.1 Class Structure

SNN_Ability - Object representing a single ability with properties and execute method
SNN_Abilities_Registry - Singleton registry to store and manage all abilities
1.2 Core Functions
FunctionPurposesnn_register_ability()Register a new abilitysnn_unregister_ability()Remove an abilitysnn_get_ability()Get single ability by IDsnn_get_abilities()Get all registered abilitiessnn_has_ability()Check if ability existssnn_execute_ability()Execute ability by ID with input1.3 Hooks

snn_abilities_api_init - Action for registering abilities
snn_before_ability_execute - Filter before execution
snn_after_ability_execute - Filter after execution
Phase 2: REST API2.1 Endpoints
MethodEndpointDescriptionGET/snn-abilities/v1/abilitiesList all abilitiesGET/snn-abilities/v1/abilities/{id}Get single abilityPOST/snn-abilities/v1/abilities/{id}/runExecute ability2.2 Authentication

Use WordPress REST API authentication
Respect per-ability permission callbacks
Phase 3: Validation & Security3.1 Input Validation

Schema-based validation for input parameters
Type checking (string, integer, boolean, array, object)
3.2 Permission System

Per-ability permission callbacks
Default to admin-only if not specified

*/