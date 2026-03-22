<?php
/**
 * SNN AI API Call Templates
 *
 * File: api-call-templates.php
 *
 * Purpose: Provides centralized, reusable JavaScript helper functions for making AI API calls.
 * This eliminates code duplication across multiple AI feature files and ensures consistent
 * API call handling, provider routing, and error management.
 *
 * Features:
 * - Unified API call wrapper with automatic provider routing
 * - Consistent error handling and retry logic
 * - Support for both text and image generation
 * - OpenRouter provider selection via request body (CORS-safe)
 *
 * Usage: This file is enqueued as a dependency for all AI features, making the helper
 * functions available globally in the admin area via window.SNN_AI_Helpers.
 *
 * Example Usage (Text Completion):
 * 
 *     const data = await SNN_AI_Helpers.makeTextCompletion({
 *         apiEndpoint: config.apiEndpoint,
 *         apiKey: config.apiKey,
 *         model: config.model,
 *         messages: messages,
 *         provider: config.modelProvider, // Optional
 *         temperature: 0.7,
 *         maxTokens: 4000
 *     });
 *     const content = SNN_AI_Helpers.extractContent(data);
 *
 * Example Usage (Image Generation):
 * 
 *     const data = await SNN_AI_Helpers.makeImageGeneration({
 *         apiEndpoint: 'https://openrouter.ai/api/v1/chat/completions',
 *         apiKey: config.apiKey,
 *         model: config.imageConfig.image_model,
 *         messages: messages,
 *         provider: config.imageConfig.image_model_provider, // Optional
 *         aspectRatio: config.imageConfig.aspect_ratio,
 *         imageSize: config.imageConfig.image_size
 *     });
 *     const imageUrl = SNN_AI_Helpers.extractImageUrl(data);
 *
 * Benefits:
 * - Single source of truth for API call logic
 * - Consistent provider routing across all features
 * - Easier to update and maintain (change once, apply everywhere)
 * - Reduced code duplication (DRY principle)
 * - Centralized error handling and formatting
 *
 * Migration Example (Before → After):
 *
 * BEFORE (Duplicated in each file):
 * 
 *     const requestBody = { model: config.model, messages };
 *     if (config.modelProvider) {
 *         requestBody.provider = {
 *             order: [config.modelProvider],
 *             allow_fallbacks: false
 *         };
 *     }
 *     const response = await fetch(config.apiEndpoint, {
 *         method: 'POST',
 *         headers: {
 *             'Content-Type': 'application/json',
 *             'Authorization': `Bearer ${config.apiKey}`
 *         },
 *         body: JSON.stringify(requestBody)
 *     });
 *     if (!response.ok) {
 *         throw new Error(`API Error: ${response.status}`);
 *     }
 *     const data = await response.json();
 *     const content = data.choices[0].message.content.trim();
 *
 * AFTER (Using helpers):
 * 
 *     const data = await SNN_AI_Helpers.makeTextCompletion({
 *         apiEndpoint: config.apiEndpoint,
 *         apiKey: config.apiKey,
 *         model: config.model,
 *         messages: messages,
 *         provider: config.modelProvider
 *     });
 *     const content = SNN_AI_Helpers.extractContent(data);
 *
 * Result: Reduced from ~20 lines to 2 lines, with better error handling!
 *
 * Files Ready to Migrate:
 * - ai-overlay.php (2 locations)
 * - ai-block-editor.php (2 locations - text + image)
 * - ai-seo-generation.php (1 location)
 * - ai-agent-and-chat.php (1 location in callAI function)
 * - ai-agent-and-chat-bricks.php (1 location in callAI function)
 *
 * When migrating, simply replace the fetch() call and body building logic with
 * SNN_AI_Helpers.makeTextCompletion() or makeImageGeneration() and the response
 * parsing with extractContent() or extractImageUrl().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue AI API helper functions
 * These are loaded before other AI scripts so they can use the helpers
 */
function snn_enqueue_ai_api_helpers() {
    // Only load in admin area
    if ( ! is_admin() ) {
        return;
    }

    wp_add_inline_script( 'jquery', snn_get_ai_api_helpers_script(), 'after' );
}
add_action( 'admin_enqueue_scripts', 'snn_enqueue_ai_api_helpers', 5 );

/**
 * Enqueue AI API helper functions on frontend for Bricks builder
 * Bricks builder runs on frontend (?bricks=run) so we need to load helpers there too
 */
function snn_enqueue_ai_api_helpers_frontend() {
    // Only load when Bricks builder is active on frontend
    if ( is_admin() || ! isset( $_GET['bricks'] ) || $_GET['bricks'] !== 'run' ) {
        return;
    }

    wp_add_inline_script( 'jquery', snn_get_ai_api_helpers_script(), 'after' );
}
add_action( 'wp_enqueue_scripts', 'snn_enqueue_ai_api_helpers_frontend', 5 );

/**
 * Generate the JavaScript helper functions
 *
 * @return string JavaScript code (without script tags)
 */
function snn_get_ai_api_helpers_script() {
    // Note: wp_add_inline_script() adds <script> tags automatically, so we return only the JS code
    ob_start();
    ?>
/**
 * SNN AI API Helper Functions
 * Centralized utilities for making AI API calls with proper provider routing
 */
window.SNN_AI_Helpers = window.SNN_AI_Helpers || {};

(function(helpers) {
    'use strict';

    /**
     * Build request body with proper provider routing
     * 
     * @param {Object} config - AI configuration object
     * @param {Object} baseBody - Base request body (model, messages, etc.)
     * @param {string} providerKey - Optional key to access provider in config (e.g., 'modelProvider' or 'imageConfig.image_model_provider')
     * @returns {Object} Request body with provider routing if applicable
     */
    helpers.buildRequestBody = function(config, baseBody, providerKey = 'modelProvider') {
        const body = { ...baseBody };
        
        // Get provider value - support nested keys like 'imageConfig.image_model_provider'
            let provider = config;
            const keys = providerKey.split('.');
            for (let key of keys) {
                provider = provider?.[key];
            }
            
            // Add provider routing if a specific provider is selected
            if (provider && typeof provider === 'string' && provider.trim() !== '') {
                body.provider = {
                    order: [provider],
                    allow_fallbacks: false
                };
            }
            
            return body;
        };

        /**
         * Build request headers for API calls
         * 
         * @param {string} apiKey - API key for authorization
         * @returns {Object} Headers object
         */
        helpers.buildHeaders = function(apiKey) {
            return {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            };
        };

        /**
         * Make an AI API call with automatic provider routing
         * 
         * @param {Object} options - Call options
         * @param {string} options.apiEndpoint - API endpoint URL
         * @param {string} options.apiKey - API key
         * @param {string} options.model - Model ID
         * @param {Array} options.messages - Messages array
         * @param {string} options.provider - Optional provider name
         * @param {number} options.temperature - Temperature (default: 0.7)
         * @param {number} options.maxTokens - Max tokens (default: 4000)
         * @param {Object} options.additionalParams - Additional request body params
         * @param {AbortSignal} options.signal - Optional abort signal
         * @returns {Promise<Object>} API response data
         */
        helpers.makeTextCompletion = async function(options) {
            const {
                apiEndpoint,
                apiKey,
                model,
                messages,
                provider = null,
                temperature = 0.7,
                maxTokens = 4000,
                additionalParams = {},
                signal = null
            } = options;

            if (!apiKey || !apiEndpoint) {
                throw new Error('AI API not configured. Please check settings.');
            }

            const baseBody = {
                model: model,
                messages: messages,
                temperature: temperature,
                max_tokens: maxTokens,
                ...additionalParams
            };

            // Add provider routing if specified
            const body = provider ? 
                helpers.buildRequestBody({ modelProvider: provider }, baseBody) : 
                baseBody;

            const fetchOptions = {
                method: 'POST',
                headers: helpers.buildHeaders(apiKey),
                body: JSON.stringify(body)
            };

            if (signal) {
                fetchOptions.signal = signal;
            }

            const response = await fetch(apiEndpoint, fetchOptions);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.error?.message || `API Error: ${response.status} ${response.statusText}`;
                throw new Error(errorMessage);
            }

            return await response.json();
        };

        /**
         * Make an image generation API call with provider routing
         * 
         * @param {Object} options - Call options
         * @param {string} options.apiEndpoint - API endpoint URL
         * @param {string} options.apiKey - API key
         * @param {string} options.model - Model ID
         * @param {Array} options.messages - Messages array
         * @param {string} options.provider - Optional provider name
         * @param {string} options.aspectRatio - Image aspect ratio (e.g., '16:9')
         * @param {string} options.imageSize - Image size (e.g., '1K')
         * @param {AbortSignal} options.signal - Optional abort signal
         * @returns {Promise<Object>} API response data
         */
        helpers.makeImageGeneration = async function(options) {
            const {
                apiEndpoint,
                apiKey,
                model,
                messages,
                provider = null,
                aspectRatio = '16:9',
                imageSize = '1K',
                signal = null
            } = options;

            if (!apiKey || !apiEndpoint) {
                throw new Error('AI API not configured. Please check settings.');
            }

            const baseBody = {
                model: model,
                messages: messages,
                modalities: ['image', 'text'],
                image_config: {
                    aspect_ratio: aspectRatio,
                    image_size: imageSize
                }
            };

            // Add provider routing if specified
            const body = provider ? 
                helpers.buildRequestBody({ modelProvider: provider }, baseBody) : 
                baseBody;

            const fetchOptions = {
                method: 'POST',
                headers: helpers.buildHeaders(apiKey),
                body: JSON.stringify(body)
            };

            if (signal) {
                fetchOptions.signal = signal;
            }

            const response = await fetch(apiEndpoint, fetchOptions);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.error?.message || `API Error: ${response.status} ${response.statusText}`;
                throw new Error(errorMessage);
            }

            return await response.json();
        };

        /**
         * Extract content from API response
         * 
         * @param {Object} data - API response data
         * @returns {string} Extracted content
         */
        helpers.extractContent = function(data) {
            if (data?.choices?.[0]?.message?.content) {
                return data.choices[0].message.content.trim();
            }
            throw new Error('Invalid API response: No content found');
        };

        /**
         * Extract image URL from API response
         * 
         * @param {Object} data - API response data
         * @returns {string} Image URL
         */
        helpers.extractImageUrl = function(data) {
            let imageUrl = null;
            
            // Try primary method: message.images array (OpenRouter, X-AI, etc.)
            if (data?.choices?.[0]?.message?.images && data.choices[0].message.images.length > 0) {
                const image = data.choices[0].message.images[0];
                // Handle both {url: "..."} and {image_url: {url: "..."}} structures
                if (image.image_url && image.image_url.url) {
                    imageUrl = image.image_url.url;
                } else if (image.url) {
                    imageUrl = image.url;
                }
            }
            
            // Fallback 1: Check content field
            if (!imageUrl && data?.choices?.[0]?.message?.content) {
                const content = data.choices[0].message.content;
                
                // If content is a direct URL string
                if (typeof content === 'string' && content.match(/^https?:\/\//)) {
                    imageUrl = content;
                }
                // If content has image_url property
                else if (content.image_url) {
                    imageUrl = content.image_url;
                }
                // Try to extract URL from markdown format
                else if (typeof content === 'string') {
                    // Try markdown format: ![alt](url)
                    const markdownMatch = content.match(/!\[.*?\]\(((?:https?:\/\/|data:image\/).*?)\)/);
                    if (markdownMatch && markdownMatch[1]) {
                        imageUrl = markdownMatch[1];
                    } else {
                        // Try plain URL or data URL
                        const urlMatch = content.match(/((?:https?:\/\/|data:image\/)[^\s]+)/);
                        if (urlMatch && urlMatch[1]) {
                            imageUrl = urlMatch[1];
                        }
                    }
                }
            }
            
            // Fallback 2: Check data.data array format (some providers use this)
            if (!imageUrl && data?.data?.[0]?.url) {
                imageUrl = data.data[0].url;
            }
            
            if (imageUrl) {
                return imageUrl;
            }
            
            throw new Error('Invalid API response: No image URL found');
        };

        /**
         * Handle common API errors with user-friendly messages
         * 
         * @param {Error} error - Error object
         * @returns {string} User-friendly error message
         */
        helpers.formatError = function(error) {
            const message = error.message || error.toString();
            
            if (message.includes('401')) {
                return 'Authentication failed. Please check your API key.';
            }
            if (message.includes('429')) {
                return 'Rate limit exceeded. Please wait a moment and try again.';
            }
            if (message.includes('500') || message.includes('502') || message.includes('503')) {
                return 'Server error. Please try again in a moment.';
            }
            if (message.includes('Failed to fetch') || message.includes('network')) {
                return 'Network error. Please check your connection and try again.';
            }
            
            return message;
        };

    // Log helper availability (in debug mode only)
    if (typeof console !== 'undefined' && window.location.search.includes('debug')) {
        console.log('✅ SNN AI Helpers loaded. Available functions:', Object.keys(helpers));
    }

})(window.SNN_AI_Helpers);
<?php
    return ob_get_clean();
}
