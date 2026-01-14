<?php
/**
 * Debug Abilities API
 * 
 * This file adds a debug panel to help verify that the WordPress Abilities API
 * is properly configured and abilities are registered correctly.
 * 
 * Access it at: /wp-admin/admin.php?page=snn-abilities-debug
 */

// Add debug page to admin menu
add_action( 'admin_menu', 'snn_abilities_debug_menu' );
function snn_abilities_debug_menu() {
    add_submenu_page(
        'tools.php',
        'Abilities API Debug',
        'Abilities Debug',
        'manage_options',
        'snn-abilities-debug',
        'snn_abilities_debug_page'
    );
}

/**
 * Render the debug page
 */
function snn_abilities_debug_page() {
    ?>
    <div class="wrap">
        <h1>WordPress Abilities API Debug</h1>
        
        <div class="card">
            <h2>System Check</h2>
            <?php
            // Check if WordPress 6.9+ is installed
            $wp_version = get_bloginfo( 'version' );
            $has_abilities_api = function_exists( 'wp_register_ability' );
            ?>
            <table class="widefat">
                <tr>
                    <th>WordPress Version</th>
                    <td><?php echo esc_html( $wp_version ); ?></td>
                    <td><?php echo version_compare( $wp_version, '6.9', '>=' ) ? '✅ OK' : '❌ Requires 6.9+'; ?></td>
                </tr>
                <tr>
                    <th>Abilities API Available</th>
                    <td><?php echo $has_abilities_api ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $has_abilities_api ? '✅ OK' : '❌ Not Available'; ?></td>
                </tr>
            </table>
        </div>

        <?php if ( $has_abilities_api ) : ?>
            <div class="card">
                <h2>Registered Categories</h2>
                <?php
                $categories = wp_get_ability_categories();
                if ( ! empty( $categories ) ) :
                    ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Slug</th>
                                <th>Label</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $categories as $slug => $category ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $slug ); ?></code></td>
                                    <td><?php echo esc_html( $category['label'] ?? '' ); ?></td>
                                    <td><?php echo esc_html( $category['description'] ?? '' ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No categories registered.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Registered Abilities</h2>
                <?php
                $abilities = wp_get_abilities();
                if ( ! empty( $abilities ) ) :
                    ?>
                    <p><strong>Total Abilities:</strong> <?php echo count( $abilities ); ?></p>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Label</th>
                                <th>Category</th>
                                <th>REST API</th>
                                <th>Permission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $abilities as $ability ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $ability->get_name() ); ?></code></td>
                                    <td><?php echo esc_html( $ability->get_label() ); ?></td>
                                    <td><?php echo esc_html( $ability->get_category() ); ?></td>
                                    <td><?php echo $ability->get_meta( 'show_in_rest' ) ? '✅ Enabled' : '❌ Disabled'; ?></td>
                                    <td>
                                        <?php
                                        $perm = call_user_func( $ability->get_permission_callback() );
                                        echo $perm ? '✅ Allowed' : '❌ Denied';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><strong>⚠️ No abilities registered!</strong></p>
                    <p>Make sure your ability files are loaded and use the proper hooks:</p>
                    <pre>add_action( 'wp_abilities_api_init', 'your_register_function' );</pre>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $abilities ) ) : ?>
                <div class="card">
                    <h2>Detailed Ability Information</h2>
                    <?php foreach ( $abilities as $ability ) : ?>
                        <div style="margin-bottom: 30px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                            <h3><?php echo esc_html( $ability->get_name() ); ?></h3>
                            <p><strong>Description:</strong> <?php echo esc_html( $ability->get_description() ); ?></p>
                            <p><strong>Category:</strong> <?php echo esc_html( $ability->get_category() ); ?></p>
                            
                            <h4>Input Schema</h4>
                            <pre><?php echo esc_html( json_encode( $ability->get_input_schema(), JSON_PRETTY_PRINT ) ); ?></pre>
                            
                            <h4>Output Schema</h4>
                            <pre><?php echo esc_html( json_encode( $ability->get_output_schema(), JSON_PRETTY_PRINT ) ); ?></pre>
                            
                            <h4>Meta</h4>
                            <pre><?php echo esc_html( json_encode( $ability->get_all_meta(), JSON_PRETTY_PRINT ) ); ?></pre>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>REST API Test</h2>
                <p>Test the REST API endpoints directly:</p>
                <ul>
                    <li><strong>List Categories:</strong> <code><?php echo esc_url( rest_url( 'wp-abilities/v1/categories' ) ); ?></code></li>
                    <li><strong>List Abilities:</strong> <code><?php echo esc_url( rest_url( 'wp-abilities/v1/abilities' ) ); ?></code></li>
                    <?php if ( ! empty( $abilities ) ) : ?>
                        <?php $first_ability = reset( $abilities ); ?>
                        <li><strong>Get Single Ability:</strong> <code><?php echo esc_url( rest_url( 'wp-abilities/v1/abilities/' . $first_ability->get_name() ) ); ?></code></li>
                        <li><strong>Execute Ability:</strong> <code><?php echo esc_url( rest_url( 'wp-abilities/v1/abilities/' . $first_ability->get_name() . '/run' ) ); ?></code> (POST)</li>
                    <?php endif; ?>
                </ul>
                
                <button type="button" class="button button-primary" onclick="testRestAPI()">Test REST API Connection</button>
                <div id="rest-api-test-result" style="margin-top: 10px;"></div>
                
                <script>
                async function testRestAPI() {
                    const resultDiv = document.getElementById('rest-api-test-result');
                    resultDiv.innerHTML = '<p>Testing...</p>';
                    
                    try {
                        const response = await fetch('<?php echo esc_url( rest_url( 'wp-abilities/v1/abilities' ) ); ?>', {
                            headers: {
                                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                            }
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            resultDiv.innerHTML = `
                                <div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px;">
                                    <strong>✅ Success!</strong><br>
                                    Found ${data.length} abilities<br>
                                    <pre style="background: white; padding: 10px; margin-top: 10px; max-height: 300px; overflow: auto;">${JSON.stringify(data, null, 2)}</pre>
                                </div>
                            `;
                        } else {
                            const errorText = await response.text();
                            resultDiv.innerHTML = `
                                <div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                                    <strong>❌ Error ${response.status}</strong><br>
                                    ${errorText}
                                </div>
                            `;
                        }
                    } catch (error) {
                        resultDiv.innerHTML = `
                            <div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                                <strong>❌ Error</strong><br>
                                ${error.message}
                            </div>
                        `;
                    }
                }
                </script>
            </div>
        <?php else : ?>
            <div class="notice notice-error">
                <p><strong>WordPress Abilities API is not available!</strong></p>
                <p>Make sure you have WordPress 6.9 or higher installed.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
        }
        .widefat th {
            font-weight: 600;
        }
        code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
        }
        pre {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
    <?php
}
