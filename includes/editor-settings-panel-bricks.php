<?php 

// Include color sync functionality
require_once SNN_PATH . '/includes/editor-settings-panel-color-sync.php';

function snn_panel_custom_inline_styles_and_scripts_improved() {
    $options = get_option('snn_editor_settings');

    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run' &&
        current_user_can('manage_options')
    ) {
        ?>
 
        <script>
            document.addEventListener("DOMContentLoaded", function() {

                // Insert SNN button into the Bricks toolbar.
                function insertSnnListItem(toolbar) {
                    const ul = (toolbar.tagName.toLowerCase() === "ul") ? toolbar : toolbar.querySelector("ul");
                    if (!ul) return;
                    if (ul.querySelector(".snn-enhance-li")) return;

                    const li = document.createElement("li");
                    li.className = "snn-enhance-li";
                    li.tabIndex = 0;
                    li.setAttribute("data-balloon", "<?php echo esc_js(__('SNN-BRX', 'snn')); ?>");
                    li.setAttribute("data-balloon-pos", "bottom");
                    li.innerHTML = '<?php echo esc_js(__('S', 'snn')); ?>';
                    li.addEventListener("click", function() {
                        const popup = document.querySelector("#snn-popup");
                        if (popup) popup.classList.add("active");
                    });

                    const waitForChildren = setInterval(() => {
                        if (ul.children.length >= 6) {
                            ul.insertBefore(li, ul.children[5]); // Between 5th and 6th
                            clearInterval(waitForChildren);
                        }
                    }, 300);
                }

                function findAndInsertSnn() {
                    var toolbar = document.querySelector("#bricks-toolbar");
                    if (toolbar) {
                        insertSnnListItem(toolbar);
                        return true;
                    }
                    return false;
                }

                var intervalCheck = setInterval(function() {
                    if (findAndInsertSnn()) {
                        clearInterval(intervalCheck);
                    }
                }, 500);

                var observer = new MutationObserver(function(mutationsList) {
                    mutationsList.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                if (node.matches("#bricks-toolbar")) {
                                    insertSnnListItem(node);
                                    clearInterval(intervalCheck);
                                } else {
                                    var toolbarInside = node.querySelector("#bricks-toolbar");
                                    if (toolbarInside) {
                                        insertSnnListItem(toolbarInside);
                                        clearInterval(intervalCheck);
                                    }
                                }
                            }
                        });
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });

                document.addEventListener("click", function(e) {
                    if (e.target && e.target.classList.contains("snn-close-button")) {
                        var popup = document.querySelector("#snn-popup");
                        if (popup) popup.classList.remove("active");
                    }
                });

                document.addEventListener("click", function(e) {
                    if (e.target.closest('.snn-enhance-li')) {
                        return;
                    }
                    var popup = document.getElementById("snn-popup");
                    var popupInner = document.getElementById("snn-popup-inner");
                    if (popup && popup.classList.contains("active") && popupInner && !popupInner.contains(e.target)) {
                        popup.classList.remove("active");
                    }
                });

                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape") {
                        var popup = document.getElementById("snn-popup");
                        if (popup && popup.classList.contains("active")) {
                            popup.classList.remove("active");
                        }
                    }
                });

            });
        </script>

        <style>
        #snn-popup{align-items:center;background-color:#ffffffdd;bottom:0;color:#fff;display:none;font-size:14px;justify-content:center;left:0;padding:60px;position:fixed;right:0;top:0;z-index:10001;}
        #snn-popup.active{display:flex;}
        #snn-popup h1{font-size:2em;font-weight:600;}
        .snn-enhance-li{width:26px !important;padding-left:3px;font-size:21px;letter-spacing:-0.3px;padding-top:0px;color:#b0b4b7;}
        .snn-enhance-li i{font-size:19px;opacity:0.8;}
        #snn-popup-inner{background-color:var(--builder-bg);border-radius:var(--builder-border-radius);box-shadow:0 6px 24px 0 rgba(0, 0, 0, 0.25);display:flex;flex-direction:column;height:100%;max-width:1200px;overflow-y:auto;position:relative;width:100%;color:#fff;padding:20px;}
        #snn-popup .snn-filters li.active{background-color:var(--builder-bg-accent);border-radius:var(--builder-border-radius);color:#fff;}
        #snn-popup .snn-title-wrapper{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;position:relative;}
        .snn-close-button{cursor:pointer;font-size:28px;background:transparent;border:none;color:var(--builder-color-accent);transform:scaleX(1.3);}
        .snn-close-button:hover{color:white;}
        .snn-toolbar-container{background-color:#f5f5f5 !important;border:1px solid #ddd !important;padding:10px !important;border-radius:5px !important;}
        .snn-toolbar-container:hover{background-color:#e0e0e0 !important;}
        .snn-toolbar-container li{font-size:12px;}
        .snn-settings-content-wrapper{height:calc(100% - 50px);}
        .snn-panel-button{align-items:center;background-color:var(--builder-bg-3);border-radius:var(--builder-border-radius);cursor:pointer;display:flex;height:var(--builder-popup-input-height);width:157px;padding:12px;font-size:16px;letter-spacing:0.3px;}
        .snn-panel-button:hover{background-color:var(--builder-color-accent);color:black;}
        .snn-panel-button svg{margin-right:10px;}
        </style>
 
        <?php
    }
}
add_action('wp_head', 'snn_panel_custom_inline_styles_and_scripts_improved');

function snn_popup_container_improved() {
    $options = get_option('snn_editor_settings');
    if (
        isset($options['snn_bricks_builder_color_fix']) &&
        $options['snn_bricks_builder_color_fix'] &&
        isset($_GET['bricks']) &&
        $_GET['bricks'] === 'run' &&
        current_user_can('manage_options')
    ) {
        ?>
        <div id="snn-popup" class="snn-popup docs">
            <div id="snn-popup-inner" class="snn-popup-inner">
                <div class="snn-title-wrapper">
                    <h1><?php _e('Settings', 'snn'); ?></h1>
                    <button class="snn-close-button"><?php _e('X', 'snn'); ?></button>
                </div>
                <div class="snn-settings-content-wrapper">
                    <?php 
                    // Include color section from the color sync file
                    if (function_exists('snn_render_color_section')) {
                        snn_render_color_section();
                    }
                    ?>
                </div>
                
                <div class="snn-panel-button" data-balloon="<?php esc_attr_e('Refresh Editor After Save', 'snn'); ?>" data-balloon-pos="top">
                    <span class="bricks-svg-wrapper" data-name="save">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="bricks-svg">
                            <path d="M21.75 23.25H2.25a1.5 1.5 0 0 1 -1.5 -1.5V7.243a3 3 0 0 1 0.879 -2.121l3.492 -3.493A3 3 0 0 1 7.243 0.75H21.75a1.5 1.5 0 0 1 1.5 1.5v19.5a1.5 1.5 0 0 1 -1.5 1.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M9.75 12.75a3 3 0 1 0 6 0 3 3 0 1 0 -6 0Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="m12.75 20.25 6.75 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M8.25 0.75v3a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5 -1.5v-3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <?php _e('Save Settings', 'snn'); ?>
                    </span>
                </div>
                <div class="snn-feedback-after-save"></div>
            </div>
        </div>
        <?php
    }
}
add_action('wp_footer', 'snn_popup_container_improved');

?>
