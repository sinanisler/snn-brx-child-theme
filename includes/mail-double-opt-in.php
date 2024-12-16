<?php

//  [verify_email]
//  Register the shortcode
// 
//  Confirmation Mail Setup : 	/example-page/?verify-my-mail={{f12de2}}&hash=y2u6ı8b2d6js3
//
//  SETUP
//
//  Put your form wherevre you want.
//  Create a page for the shortcode when user comes from the email link 
//  [verify_email] shortcode will check the mail and verify it.
//  
//  Setup your confirmation mail for the form and send user a link for the shortcode verification page 
//  example: /example-page/?verify-my-mail={{f12de2}}
//
//
// 
add_shortcode('verify_email', 'verify_user_email_via_shortcode');

function verify_user_email_via_shortcode() {
    // Initialize an empty output variable
    $output = '';

    // Check if the 'verify-my-mail' GET parameter is set
    if ( isset($_GET['verify-my-mail']) ) {
        // Sanitize the email input
        $email = sanitize_email( $_GET['verify-my-mail'] );

        // Validate the email format
        if ( ! is_email( $email ) ) {
            $output .= '<div class="email-verification-error">Invalid email address provided for verification.</div>';
            return $output;
        }

        global $wpdb;

        // Define the table name with the WordPress prefix
        $table_name = $wpdb->prefix . 'bricks_form_submissions';

        /**
         * Use JSON_SEARCH to look for the email across all fields in the JSON.
         * JSON_SEARCH returns the path to the value if found, otherwise NULL.
         */
        $query = $wpdb->prepare(
            "SELECT id FROM $table_name WHERE JSON_SEARCH(form_data, 'all', %s) IS NOT NULL",
            $email
        );

        // Execute the query to get matching submission IDs
        $submission_ids = $wpdb->get_col( $query );

        if ( ! empty( $submission_ids ) ) {
            /**
             * Update the 'browser' column to 'Verified' for all matching submissions.
             * Using a prepared statement to ensure safety.
             */
            $update_query = $wpdb->prepare(
                "UPDATE $table_name SET browser = %s WHERE JSON_SEARCH(form_data, 'all', %s) IS NOT NULL",
                'Verified',
                $email
            );

            $updated = $wpdb->query( $update_query );

            if ( false !== $updated ) {
                // Success message
                $output .= '<div class="email-verification-success"><h2>Your email has been successfully verified.<br> Thank you!</h2></div>';
            } else {
                // If the update failed for some reason
                $output .= '<div class="email-verification-error">An error occurred while verifying your email. Please try again later.</div>';
            }
        } else {
            // No matching email found in the database
            $output .= '<div class="email-verification-error">No matching email address found for verification.</div>';
        }
    } else {
        // Optional: Display a message or nothing if 'verify-my-mail' parameter is not set
        $output .= '<div class="email-verification-info">No email verification request detected.</div>';
    }

    return $output;
}










function custom_admin_footer_script() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Change the header text from "Browser" to "Verification"
            var browserHeader = document.querySelector('th#browser');
            if (browserHeader) {
                browserHeader.textContent = 'Verification';
            }

            // 2. Modify the styling of the table cells based on their content
            var theList = document.getElementById('the-list');
            if (theList) {
                // Select all <td> elements with class 'browser' within '#the-list'
                var browserTds = theList.querySelectorAll('td.browser');
                
                browserTds.forEach(function(td) {
                    // Trim the text content to avoid issues with leading/trailing whitespace
                    var content = td.textContent.trim();
                    
                    // Check if the text content includes 'Verified'
                    if (content.includes('Verified')) {
                        td.style.color = 'green';
                        td.style.fontWeight = 'bold';
                    } else {
                        td.style.color = 'white';
                        td.style.fontWeight = 'normal'; // Reset font weight if not verified
                    }
                });
            }
        });
    </script>
    <?php
}
add_action('admin_footer', 'custom_admin_footer_script');






