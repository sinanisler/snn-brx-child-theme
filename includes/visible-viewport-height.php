<?php

function add_inline_viewport_script() {
    $script = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Get the height of the window
    let visibleViewportHeight = window.innerHeight;

    // Create a new style tag
    let styleTag = document.createElement('style');

    // Create CSS content using the visible viewport height
    let cssContent = `
      :root {
        --visible-viewport-height: ${visibleViewportHeight}px;
      }
    `;

    // Append the CSS content to the style tag
    styleTag.innerHTML = cssContent;

    // Append the style tag to the document head
    document.head.appendChild(styleTag);
});
</script>
SCRIPT;

    echo $script;
}

// Hook into WordPress to add the script to the footer of every page on the frontend
add_action('wp_footer', 'add_inline_viewport_script');

// Hook into WordPress to add the script to the footer of every admin page on the backend
add_action('admin_footer', 'add_inline_viewport_script');


?>