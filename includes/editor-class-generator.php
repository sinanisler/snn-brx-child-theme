<?php 


if (
    isset($_GET['bricks']) &&
    $_GET['bricks'] === 'run' &&
    current_user_can('manage_options')
) {
?>




<script>
// Enhanced BEM Class Generator wrapped in DOMContentLoaded listener
document.addEventListener('DOMContentLoaded', (event) => {
    'use strict';
    
    // Core BEM generator function (Keep this logic the same)
    function generateBEMClasses(blockName) {
        // Attempt to safely access the Vue application state
        const vueAppRoot = document.querySelector("[data-v-app]");
        if (!vueAppRoot || !vueAppRoot.__vue_app__) {
             // console.error('Bricks Vue application root not found.');
             return;
        }

        const bricksState = vueAppRoot.__vue_app__.config.globalProperties.$_state;
        const activeId = bricksState.activeId;
        const content = bricksState.content;
        const globalClasses = bricksState.globalClasses;
        
        if (!activeId) {
            // console.error('No active element');
            return;
        }
        
        function generateId() {
            return Math.random().toString(36).substring(2, 8);
        }
        
        function processElement(id) {
            const element = content.find(el => el.id === id);
            if (!element) return;
            
            const bemClassName = `${blockName}-${element.name}`;
            let existingClass = globalClasses.find(cls => cls.name === bemClassName);
            
            if (!existingClass) {
                const classId = generateId();
                const newClass = {
                    id: classId,
                    name: bemClassName,
                    settings: {}
                };
                
                globalClasses.push(newClass);
                console.log(`✓ Created global class "${bemClassName}" (${classId})`);
                existingClass = newClass;
            }
            
            if (!element.settings._cssGlobalClasses || Array.isArray(element.settings)) {
                element.settings._cssGlobalClasses = [];
            }
            
            if (!element.settings._cssGlobalClasses.includes(existingClass.id)) {
                element.settings._cssGlobalClasses.push(existingClass.id);
                console.log(`✓ Added "${bemClassName}" to ${element.name} (${id})`);
            } else {
                console.log(`⊘ "${bemClassName}" already on ${element.name} (${id})`);
            }
            
            if (element.children && element.children.length > 0) {
                element.children.forEach(childId => processElement(childId));
            }
        }
        
        processElement(activeId);
        // console.log('✓ BEM classes generated successfully!');
    }
    
    // Context menu integration (Keep this logic the same)
    function addContextMenuButton() {
        const contextMenu = document.querySelector('#bricks-builder-context-menu');
        if (!contextMenu) return;
        
        const ul = contextMenu.querySelector('ul');
        if (!ul) return;
        
        // Check if button already exists
        if (ul.querySelector('.class-generator')) return;
        
        // Create the menu item INSIDE the ul
        const menuItem = document.createElement('li');
        menuItem.className = 'class-generator sep';
        menuItem.innerHTML = '<span class="label">Class Generator</span>';
        
        // Click handler
        menuItem.addEventListener('click', (e) => {
            e.stopPropagation();
            
            const blockName = prompt('Enter block name (e.g., hero):');
            
            if (blockName && blockName.trim()) {
                generateBEMClasses(blockName.trim());
                
                // Close context menu
                contextMenu.style.display = 'none';
            }
        });
        
        // Add as last item in ul
        ul.appendChild(menuItem);
    }
    
    // Observer to watch for context menu
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.id === 'bricks-builder-context-menu' || 
                    (node.nodeType === 1 && node.querySelector('#bricks-builder-context-menu'))) {
                    addContextMenuButton();
                }
            });
        });
    });
    
    // --- THIS LINE IS NOW SAFE ---
    // Start observing document.body for the context menu to appear
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Initial check if menu already exists 
    addContextMenuButton();
    
    // console.log('✓ BEM Class Generator initialized');

}); // Closes the DOMContentLoaded listener
</script>






<?php 
}
?>