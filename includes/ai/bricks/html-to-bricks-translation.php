<?php if ( ! defined( 'ABSPATH' ) ) {     exit; } ?>
// ================================================================
            // Helpers
            // ================================================================

            // ================================================================
            // STEP 1: CSS-to-Bricks Mapping Dictionary
            // ================================================================
            
            /**
             * The "Rosetta Stone" — Maps CSS properties to Bricks settings paths
             * Type: 'direct' (simple 1:1), 'boxModel' (padding/margin), 'typography', 'raw' (wrap in {raw:...})
             */
            const CSS_TO_BRICKS_MAP = {
                // Box Model
                'padding':          { type: 'boxModel', target: '_padding' },
                'padding-top':      { type: 'directBox', target: '_padding', side: 'top' },
                'padding-right':    { type: 'directBox', target: '_padding', side: 'right' },
                'padding-bottom':   { type: 'directBox', target: '_padding', side: 'bottom' },
                'padding-left':     { type: 'directBox', target: '_padding', side: 'left' },
                'margin':           { type: 'boxModel', target: '_margin' },
                'margin-top':       { type: 'directBox', target: '_margin', side: 'top' },
                'margin-right':     { type: 'directBox', target: '_margin', side: 'right' },
                'margin-bottom':    { type: 'directBox', target: '_margin', side: 'bottom' },
                'margin-left':      { type: 'directBox', target: '_margin', side: 'left' },
                
                // Layout & Flexbox
                'display':          { type: 'direct', target: '_display' },
                'flex-direction':   { type: 'direct', target: '_direction', target2: '_flexDirection', map: {'row': 'row', 'column': 'column', 'row-reverse': 'row-reverse', 'column-reverse': 'column-reverse'} },
                'justify-content':  { type: 'direct', target: '_justifyContent', target2: '_justifyContentGrid' },
                'justify-items':    { type: 'direct', target: '_justifyItemsGrid' },
                'align-items':      { type: 'direct', target: '_alignItems', target2: '_alignItemsGrid' },
                'align-content':    { type: 'direct', target: '_alignContent', target2: '_alignContentGrid' },
                'align-self':       { type: 'direct', target: '_alignSelf' },
                'justify-self':     { type: 'direct', target: '_gridItemJustifySelf' },
                'flex-wrap':        { type: 'direct', target: '_flexWrap' },
                'flex-grow':        { type: 'direct', target: '_flexGrow' },
                'flex-shrink':      { type: 'direct', target: '_flexShrink' },
                'flex-basis':       { type: 'direct', target: '_flexBasis' },
                'flex':             { type: 'flexHandler' },
                'order':            { type: 'numeric', target: '_order' },
                'gap':              { type: 'gapHandler' }, // Special: distributes to _columnGap/_rowGap/_gap/_gridGap
                'column-gap':       { type: 'numeric', target: '_columnGap' },
                'row-gap':          { type: 'numeric', target: '_rowGap' },

                // Grid
                'grid-template-columns': { type: 'direct', target: '_gridTemplateColumns' },
                'grid-template-rows':    { type: 'direct', target: '_gridTemplateRows' },
                'grid-template-areas':   { type: 'direct', target: '_gridTemplateAreas' },
                'grid-gap':              { type: 'numeric', target: '_gridGap' },
                'grid-column':           { type: 'direct', target: '_gridItemColumnSpan' },
                'grid-row':              { type: 'direct', target: '_gridItemRowSpan' },
                'grid-area':             { type: 'direct', target: '_gridArea' },
                'grid-auto-flow':        { type: 'direct', target: '_direction' }, // maps to same _direction as flex-direction
                'grid-auto-columns':     { type: 'direct', target: '_gridAutoColumns' },
                'grid-auto-rows':        { type: 'direct', target: '_gridAutoRows' },
                'grid-column-start':     { type: 'direct', target: '_gridColumnStart' },
                'grid-column-end':       { type: 'direct', target: '_gridColumnEnd' },
                'grid-row-start':        { type: 'direct', target: '_gridRowStart' },
                'grid-row-end':          { type: 'direct', target: '_gridRowEnd' },
                
                // Sizing
                'width':            { type: 'direct', target: '_width' },
                'max-width':        { type: 'direct', target: '_widthMax' },
                'min-width':        { type: 'direct', target: '_widthMin' },
                'height':           { type: 'direct', target: '_height' },
                'min-height':       { type: 'direct', target: '_heightMin' },
                'max-height':       { type: 'direct', target: '_heightMax' },
                
                // Background (will use raw format)
                'background':            { type: 'backgroundHandler' },
                'background-color':      { type: 'backgroundColor' },
                'background-image':      { type: 'backgroundImage' },
                'background-size':       { type: 'backgroundSize' },
                'background-position':   { type: 'backgroundPosition' },
                'background-repeat':     { type: 'backgroundRepeat' },
                'background-attachment': { type: 'backgroundAttachment' },
                'background-blend-mode': { type: 'backgroundBlendMode' },
                
                // Typography (goes into _typography object)
                'font-family':      { type: 'typography', target: 'font-family', transform: 'cleanFontFamily' },
                'font-size':        { type: 'typography', target: 'font-size', transform: 'numeric' },
                'font-weight':      { type: 'typography', target: 'font-weight' },
                'font-style':       { type: 'typography', target: 'font-style' },
                'line-height':      { type: 'typography', target: 'line-height' },
                'letter-spacing':   { type: 'typography', target: 'letter-spacing' },
                'text-align':       { type: 'typography', target: 'text-align' },
                'text-transform':   { type: 'typography', target: 'text-transform' },
                'color':            { type: 'typography', target: 'color', transform: 'raw' },
                
                // Border
                'border-radius':             { type: 'borderRadius' },
                'border-top-left-radius':    { type: 'borderRadiusCorner', corner: 'top' },
                'border-top-right-radius':   { type: 'borderRadiusCorner', corner: 'right' },
                'border-bottom-right-radius':{ type: 'borderRadiusCorner', corner: 'bottom' },
                'border-bottom-left-radius': { type: 'borderRadiusCorner', corner: 'left' },
                'border':           { type: 'borderHandler' },
                'border-top':       { type: 'borderSide', side: 'top' },
                'border-right':     { type: 'borderSide', side: 'right' },
                'border-bottom':    { type: 'borderSide', side: 'bottom' },
                'border-left':      { type: 'borderSide', side: 'left' },
                'border-width':     { type: 'borderWidth' },
                'border-style':     { type: 'borderStyle' },
                'border-color':     { type: 'borderColor' },
                
                // Box Shadow
                'box-shadow':       { type: 'boxShadow' },
                
                // Position
                'position':         { type: 'direct', target: '_position' },
                'top':              { type: 'direct', target: '_top' },
                'right':            { type: 'direct', target: '_right' },
                'bottom':           { type: 'direct', target: '_bottom' },
                'left':             { type: 'direct', target: '_left' },
                'z-index':          { type: 'direct', target: '_zIndex' },
                
                // Misc
                'opacity':          { type: 'direct', target: '_opacity' },
                'overflow':         { type: 'direct', target: '_overflow' },
                'overflow-x':       { type: 'direct', target: '_overflowX' },
                'overflow-y':       { type: 'direct', target: '_overflowY' },
                'object-fit':       { type: 'direct', target: '_objectFit' },
                'object-position':  { type: 'direct', target: '_objectPosition' },
                'aspect-ratio':     { type: 'direct', target: '_aspectRatio' },
                'cursor':           { type: 'direct', target: '_cursor' },
                'transition':       { type: 'direct', target: '_cssTransition' },
                'transform':        { type: 'cssGlobal' }, // Use _cssCustom for transforms unless we parse it
                'visibility':       { type: 'direct', target: '_visibility' },
                'pointer-events':   { type: 'direct', target: '_pointerEvents' },
                'isolation':        { type: 'direct', target: '_isolation' },
                'mix-blend-mode':   { type: 'direct', target: '_mixBlendMode' },
                'filter':           { type: 'cssGlobal' }, // Complex value, use custom CSS
                'backdrop-filter':  { type: 'cssGlobal' }, // Complex value, use custom CSS

                // Text extras — goes into _cssCustom (Bricks has no native mapping for these)
                'text-decoration':  { type: 'typography', target: 'text-decoration' },
                'white-space':      { type: 'cssGlobal' },
                'word-break':       { type: 'cssGlobal' },
                'text-overflow':    { type: 'cssGlobal' },
                'line-clamp':       { type: 'cssGlobal' },
                '-webkit-line-clamp': { type: 'cssGlobal' },
                'text-shadow':      { type: 'cssGlobal' },

                // Ignored
                'outline':          { type: 'ignore' },
                'box-sizing':       { type: 'ignore' },
                'vertical-align':   { type: 'ignore' },
            };

            /**
             * Parse inline CSS from style attribute into structured object
             * Example: "padding: 20px 10px; color: #fff" → {padding: "20px 10px", color: "#fff"}
             */
            function parseInlineCSS(styleString) {
                if (!styleString || typeof styleString !== 'string') return {};
                const styles = {};
                styleString.split(';').forEach(rule => {
                    const colonIndex = rule.indexOf(':');
                    if (colonIndex === -1) return;
                    const prop = rule.substring(0, colonIndex).trim();
                    const value = rule.substring(colonIndex + 1).trim();
                    if (prop && value) styles[prop] = value;
                });
                return styles;
            }

            /**
             * Extract CSS value and convert to Bricks format
             * Example: "48px" → "48", "1.5em" → "1.5", "#ffffff" → "#ffffff"
             */
            function extractNumericValue(cssValue) {
                if (!cssValue) return '';
                const match = cssValue.match(/^([\d.]+)(?:px|em|rem|%)?$/);
                return match ? match[1] : cssValue;
            }

            /**
             * Parse padding/margin shorthand into object
             * Example: "20px 10px" → {top:"20",right:"10",bottom:"20",left:"10"}
             */
            function parseBoxModel(value) {
                if (!value) return {};
                const parts = value.trim().split(/\s+/).map(extractNumericValue);
                if (parts.length === 1) return {top:parts[0],right:parts[0],bottom:parts[0],left:parts[0]};
                if (parts.length === 2) return {top:parts[0],right:parts[1],bottom:parts[0],left:parts[1]};
                if (parts.length === 3) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[1]};
                if (parts.length === 4) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[3]};
                return {};
            }

            // ================================================================
            // STEP 2: Core JavaScript Compiler — HTML to Bricks JSON
            // ================================================================

            /**
             * Main compiler function: converts an HTML string to Bricks Builder JSON
             * This replaces the AI-based Phase 2 compilation with 100% JavaScript
             * 
             * @param {string} html - The HTML string to compile (one section)
             * @param {string} googleFonts - Optional Google Fonts URL from @import
             * @return {object} - Bricks JSON structure {content: [...]}
             */
            function compileHtmlToBricksJson(html, googleFonts = '') {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = [];
                const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
                const usedIds = new Set();

                // Build style-id map from <style data-style-id="..."> tags.
                // These link a CSS block to an element by its HTML id attribute.
                const styleIdMap = {};
                
                // 1. Get from full preview if available (resolves issue where sibling <style> tags get stripped when splitting sections)
                if (typeof ChatState !== 'undefined' && ChatState.currentHTMLPreview) {
                    const fullDoc = new DOMParser().parseFromString(ChatState.currentHTMLPreview, 'text/html');
                    fullDoc.querySelectorAll('style[data-style-id]').forEach(styleEl => {
                        const sid = styleEl.getAttribute('data-style-id');
                        if (sid) styleIdMap[sid] = styleEl.textContent.trim();
                    });
                }
                
                // 2. Also get from the current chunk (doc)
                doc.querySelectorAll('style[data-style-id]').forEach(styleEl => {
                    const sid = styleEl.getAttribute('data-style-id');
                    if (sid) styleIdMap[sid] = styleEl.textContent.trim();
                });

                /**
                 * Convert raw CSS from a <style data-style-id> block into Bricks-ready CSS.
                 * Replaces the original HTML id selector (#htmlId) with %root% so the CSS
                 * maps to the compiled Bricks element. Child selectors are preserved:
                 *   #snn-foo { color: red }             →  %root% { color: red }
                 *   #snn-foo .bar { font-size: 12px }   →  %root% .bar { font-size: 12px }
                 */
                function convertStyleIdCss(rawCss, htmlId) {
                    const escaped = htmlId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const replaced = rawCss.replace(new RegExp('#' + escaped + '(?=[\\s,{.:#\\[>~+]|$)', 'g'), '%root%');
                    // Strip leading spaces from each line, and remove \n to prevent issues with custom CSS
                    return replaced.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
                }

                // Generate 6-letter Bricks ID
                function genId() {
                    let id;
                    do {
                        id = Array.from({ length: 6 }, () => LETTERS[Math.floor(Math.random() * 26)]).join('');
                    } while (usedIds.has(id) || ChatState.globalUsedIds.has(id));
                    usedIds.add(id);
                    ChatState.globalUsedIds.add(id);
                    return id;
                }
                
                // Extract numeric/unit value from CSS (e.g., "-48px" -> "-48px", "50%" -> "50%", "auto" -> "auto")
                // Preserves the unit and negative signs so Bricks can use variables or correct units.
                function extractNumeric(cssValue) {
                    if (!cssValue) return '';
                    const str = String(cssValue).trim();
                    if (str === 'auto' || str === 'none') return str;
                    
                    // Match a number with optional sign, decimal, and optional unit/variable support
                    // Examples: "100%", "-20px", "1.5rem", "var(--spacing)", "calc(100% - 20px)"
                    // If it starts with var, calc, clamp, min, max, just return it
                    if (str.match(/^(var|calc|clamp|min|max)\(/)) return str;
                    
                    const match = str.match(/^([+-]?[\d.]+)(.*)$/);
                    if (!match) return str;
                    
                    const num = match[1];
                    const unit = match[2];
                    
                    return (!isNaN(parseFloat(num)) && isFinite(parseFloat(num))) ? num + unit : '';
                }

                // Clean font family (remove quotes)
                function cleanFontFamily(fontFamily) {
                    if (!fontFamily) return '';
                    return fontFamily.replace(/['"]/g, '').split(',')[0].trim();
                }

                // Parse FontAwesome icon class string into Bricks icon object
                // Supports: fas fa-icon (solid), far fa-icon / fa fa-icon (regular), fab fa-icon (brands)
                function parseFaIcon(classString) {
                    if (!classString) return null;
                    const cls = classString.trim();
                    // Must contain an 'fa-' icon name
                    if (!cls.includes('fa-')) return null;
                    let library = 'fontawesomeSolid';
                    if (cls.includes('fab ') || cls.startsWith('fab')) {
                        library = 'fontawesomeBrands';
                    } else if (cls.includes('far ') || cls.startsWith('far')) {
                        library = 'fontawesomeRegular';
                    } else if ((cls.includes('fa ') || cls.startsWith('fa ')) && !cls.includes('fas ') && !cls.startsWith('fas')) {
                        library = 'fontawesomeRegular';
                    }
                    return { library, icon: cls };
                }
                
                // Parse box model (padding/margin) — handles shorthand
                // robust handling of values including 'auto', units, and negatives.
                function parseBoxModelValue(value) {
                    if (!value) return {};
                    const parts = value.trim().split(/\s+/)
                        .map(p => extractNumeric(p))
                        .filter(p => p && p !== ''); 
                    
                    if (parts.length === 0) return {};
                    if (parts.length === 1) return {top:parts[0],right:parts[0],bottom:parts[0],left:parts[0]};
                    if (parts.length === 2) return {top:parts[0],right:parts[1],bottom:parts[0],left:parts[1]};
                    if (parts.length === 3) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[1]};
                    if (parts.length >= 4) return {top:parts[0],right:parts[1],bottom:parts[2],left:parts[3]};
                    return {};
                }
                
                // Parse box shadow
                function parseBoxShadow(value) {
                    if (!value || value === 'none') return null;
                    // Parse box-shadow: offsetX offsetY blur spread color
                    // Bricks format: { values: {offsetX, offsetY, blur, spread}, color: {raw: color} }
                    const parts = value.split(/\s+/);
                    let offsetX = '0', offsetY = '0', blur = '0', spread = '0', color = 'rgba(0,0,0,0.1)';
                    
                    if (parts.length >= 2) {
                        offsetX = extractNumeric(parts[0]);
                        offsetY = extractNumeric(parts[1]);
                    }
                    if (parts.length >= 3) blur = extractNumeric(parts[2]);
                    if (parts.length >= 4) {
                        // Check if parts[3] is a color or spread
                        if (parts[3].startsWith('#') || parts[3].startsWith('rgba') || parts[3].startsWith('rgb') || parts[3].startsWith('hsl')) {
                            color = parts[3];
                            // If rgba/rgb/hsl with spaces, join the rest
                            if (parts[3].includes('(') && !parts[3].includes(')')) {
                                color = parts.slice(3).join(' ');
                            }
                        } else {
                            spread = extractNumeric(parts[3]);
                            if (parts.length >= 5) {
                                // Get the color (might have spaces in rgba)
                                color = parts.slice(4).join(' ');
                            }
                        }
                    }
                    
                    // Return Bricks format: values object + separate color object
                    return {
                        values: {
                            offsetX: offsetX,
                            offsetY: offsetY,
                            blur: blur,
                            spread: spread
                        },
                        color: { raw: color }
                    };
                }
                
                // Parse border — handles rgba/rgb with spaces correctly
                function parseBorder(value) {
                    if (!value) return null;
                    if (value === 'none') {
                        return { width: { top: '0', right: '0', bottom: '0', left: '0' }, style: 'none' };
                    }
                    let width = '1', style = 'solid', color = '#000000';
                    
                    // Extract rgba/rgb color first (before splitting by spaces)
                    const rgbaMatch = value.match(/rgba?\([^)]+\)/);
                    if (rgbaMatch) {
                        color = rgbaMatch[0];
                        value = value.replace(rgbaMatch[0], '').trim(); // Remove color from value
                    }
                    
                    // Now split remaining parts and filter valid ones
                    const parts = value.split(/\s+/).filter(p => p);
                    
                    parts.forEach(part => {
                        if (part.match(/^[0-9.]/)) {
                            const w = extractNumeric(part);
                            if (w !== '') width = w; // Only set if valid
                        }
                        else if (['solid', 'dashed', 'dotted', 'double', 'none'].includes(part)) style = part;
                        else if (part.startsWith('#')) color = part;
                    });
                    
                    // Validate width is a valid number
                    if (width === '' || isNaN(parseFloat(width))) width = '1';
                    
                    return {
                        width: { top: width, right: width, bottom: width, left: width },
                        style: style,
                        color: { raw: color }
                    };
                }
                
                // Parse border-radius — handles 1/2/3/4 value shorthand
                // Bricks uses radius corners as: top=top-left, right=top-right, bottom=bottom-right, left=bottom-left
                function parseBorderRadius(value) {
                    if (!value) return null;
                    // Handle "50%" or "100px" (single value) or shorthand
                    const parts = value.trim().split(/\s+/).map(v => extractNumeric(v));
                    let tl, tr, br, bl;
                    if (parts.length === 1)      { tl = tr = br = bl = parts[0]; }
                    else if (parts.length === 2)  { tl = br = parts[0]; tr = bl = parts[1]; }
                    else if (parts.length === 3)  { tl = parts[0]; tr = bl = parts[1]; br = parts[2]; }
                    else                          { tl = parts[0]; tr = parts[1]; br = parts[2]; bl = parts[3]; }
                    return { radius: { top: tl, right: tr, bottom: br, left: bl } };
                }
                
                // Parse gradient from CSS removed: gradients now placed in custom CSS for reliability.
                
                /**
                 * Convert CSS styles object to Bricks settings object
                 */
                function stylesToBricksSettings(cssStyles) {
                    const settings = {};
                    
                    // Process each CSS property using the mapping dictionary
                    Object.keys(cssStyles).forEach(prop => {
                        const value = cssStyles[prop];
                        const mapping = CSS_TO_BRICKS_MAP[prop];
                        
                        // If property not in map, add to _cssCustom (for unsupported CSS properties)
                        if (!mapping) {
                            if (!settings._cssCustom) settings._cssCustom = '';
                            settings._cssCustom += ` ${prop}: ${value};`;
                            return;
                        }
                        
                        // Explicitly ignored properties (not needed in Bricks)
                        if (mapping.type === 'ignore') return;
                        
                        switch (mapping.type) {
                            case 'direct':
                                settings[mapping.target] = mapping.map ? mapping.map[value] || value : value;
                                if (mapping.target2) {
                                    settings[mapping.target2] = mapping.map ? mapping.map[value] || value : value;
                                }
                                break;
                                
                            case 'numeric':
                                settings[mapping.target] = extractNumeric(value);
                                if (mapping.target2) settings[mapping.target2] = settings[mapping.target];
                                break;
                                
                            case 'boxModel':
                                settings[mapping.target] = parseBoxModelValue(value);
                                break;
                                
                            case 'directBox':
                                if (!settings[mapping.target]) settings[mapping.target] = {};
                                settings[mapping.target][mapping.side] = extractNumeric(value);
                                break;
                                
                            case 'gapHandler':
                                // Distribute gap to columnGap, rowGap, gridGap and base gap
                                const gapVal = extractNumeric(value);
                                settings._columnGap = gapVal;
                                settings._rowGap = gapVal;
                                settings._gap = gapVal;
                                settings._gridGap = gapVal;
                                break;
                                
                            case 'flexHandler':
                                const flexParts = value.trim().split(/\s+/);
                                if (flexParts.length >= 1) settings._flexGrow = extractNumeric(flexParts[0]);
                                if (flexParts.length >= 2) settings._flexShrink = extractNumeric(flexParts[1]);
                                if (flexParts.length >= 3) settings._flexBasis = extractNumeric(flexParts.slice(2).join(' '));
                                break;
                                
                            case 'typography':
                                if (!settings._typography) settings._typography = {};
                                let typoValue = value;
                                if (mapping.transform === 'numeric') {
                                    // Handle clamp() — extract the max (last) value as the desktop size
                                    if (value.includes('clamp(')) {
                                        const clampMatch = value.match(/clamp\(\s*[^,]+,\s*[^,]+,\s*([^)]+)\s*\)/);
                                        typoValue = clampMatch ? extractNumeric(clampMatch[1].trim()) : extractNumeric(value);
                                    } else {
                                        typoValue = extractNumeric(value);
                                    }
                                } else if (mapping.transform === 'cleanFontFamily') {
                                    const fontParts = value.replace(/['"]/g, '').split(',');
                                    typoValue = fontParts[0].trim();
                                    if (fontParts.length > 1) {
                                        settings._typography['fallback'] = fontParts.slice(1).join(',').trim();
                                    }
                                } else if (mapping.transform === 'raw') {
                                    typoValue = { raw: value };
                                }
                                settings._typography[mapping.target] = typoValue;
                                break;
                                
                            case 'backgroundColor':
                                if (value.includes('gradient')) {
                                    if (!settings._cssCustom) settings._cssCustom = '';
                                    settings._cssCustom += ` background: ${value};`;
                                } else {
                                    if (!settings._background) settings._background = {};
                                    settings._background.color = { raw: value };
                                }
                                break;
                                
                            case 'backgroundHandler':
                                // Parse complex background property
                                if (value.includes('linear-gradient') || value.includes('radial-gradient') || value.includes('conic-gradient')) {
                                    if (!settings._cssCustom) settings._cssCustom = '';
                                    settings._cssCustom += ` background: ${value};`;
                                } else if (value.includes('url(')) {
                                    // Background image
                                    const urlMatch = value.match(/url\(['"]?([^'"]+)['"]?\)/);
                                    if (urlMatch) {
                                        if (!settings._background) settings._background = {};
                                        settings._background.image = { url: urlMatch[1] };
                                        
                                        // Shorthand simple parsing
                                        if (value.includes('no-repeat')) settings._background.repeat = 'no-repeat';
                                        else if (value.includes('repeat-x')) settings._background.repeat = 'repeat-x';
                                        else if (value.includes('repeat-y')) settings._background.repeat = 'repeat-y';
                                        
                                        if (value.includes('fixed')) settings._background.attachment = 'fixed';
                                        
                                        if (value.includes('cover')) {
                                            settings._background.size = 'cover';
                                        } else if (value.includes('contain')) {
                                            settings._background.size = 'contain';
                                        }
                                        
                                        if (value.includes('center')) {
                                            settings._background.position = 'center center';
                                        }

                                        // extract any color before/after url
                                        const colorMatch = value.replace(/url\([^)]+\)/, '').replace(/(no-repeat|repeat-[xy]|fixed|cover|contain|center|center center|center top|center bottom|left|right|top|bottom)/g, '').trim();
                                        if (colorMatch && colorMatch !== '') {
                                            settings._background.color = { raw: colorMatch };
                                        }
                                    }
                                } else {
                                    if (!settings._background) settings._background = {};
                                    settings._background.color = { raw: value };
                                }
                                break;
                                
                            case 'backgroundImage':
                                const urlMatchImg = value.match(/url\(['"]?([^'"]+)['"]?\)/);
                                if (urlMatchImg) {
                                    if (!settings._background) settings._background = {};
                                    settings._background.image = { url: urlMatchImg[1] };
                                } else {
                                    if (!settings._cssCustom) settings._cssCustom = '';
                                    settings._cssCustom += ` background-image: ${value};`;
                                }
                                break;
                                
                            case 'boxShadow':
                                const shadow = parseBoxShadow(value);
                                if (shadow) settings._boxShadow = shadow;
                                break;
                                
                            case 'borderRadius':
                                const radius = parseBorderRadius(value);
                                if (radius) {
                                    if (!settings._border) settings._border = {};
                                    Object.assign(settings._border, radius);
                                }
                                break;

                            case 'borderRadiusCorner':
                                if (!settings._border) settings._border = {};
                                if (!settings._border.radius) settings._border.radius = {};
                                settings._border.radius[mapping.corner] = extractNumeric(value);
                                break;

                            case 'borderStyle':
                                if (!settings._border) settings._border = {};
                                settings._border.style = value;
                                break;

                            case 'borderWidth':
                                const borderWidthVal = extractNumeric(value);
                                if (!settings._border) settings._border = {};
                                settings._border.width = {
                                    top: borderWidthVal, right: borderWidthVal,
                                    bottom: borderWidthVal, left: borderWidthVal
                                };
                                break;

                            case 'borderColor':
                                if (!settings._border) settings._border = {};
                                settings._border.color = { raw: value };
                                break;

                            case 'borderHandler':
                                const border = parseBorder(value);
                                if (border) {
                                    if (!settings._border) settings._border = {};
                                    Object.assign(settings._border, border);
                                }
                                break;

                            case 'borderSide': {
                                // e.g. border-top: 2px solid #000
                                const sideResult = parseBorder(value);
                                if (sideResult) {
                                    if (!settings._border) settings._border = {};
                                    if (sideResult.width) {
                                        if (!settings._border.width) settings._border.width = { top:'0', right:'0', bottom:'0', left:'0' };
                                        settings._border.width[mapping.side] = sideResult.width.top;
                                    }
                                    if (!settings._border.style && sideResult.style) settings._border.style = sideResult.style;
                                    if (!settings._border.color && sideResult.color) settings._border.color = sideResult.color;
                                }
                                break;
                            }

                            case 'backgroundSize': {
                                if (!settings._background) settings._background = {};
                                if (value === 'cover' || value === 'contain') {
                                    settings._background.size = value;
                                } else {
                                    settings._background.size = 'custom';
                                    settings._background.custom = value;
                                }
                                break;
                            }

                            case 'backgroundPosition':
                                if (!settings._background) settings._background = {};
                                settings._background.position = value;
                                break;

                            case 'backgroundRepeat':
                                if (!settings._background) settings._background = {};
                                settings._background.repeat = value;
                                break;

                            case 'backgroundAttachment':
                                if (!settings._background) settings._background = {};
                                settings._background.attachment = value;
                                break;

                            case 'backgroundBlendMode':
                                if (!settings._background) settings._background = {};
                                settings._background.blendMode = value;
                                break;

                            case 'cssGlobal':
                                // For transforms, text-decoration, and complex CSS without native Bricks mapping
                                if (!settings._cssCustom) settings._cssCustom = '';
                                settings._cssCustom += ` ${prop}: ${value};`;
                                break;
                        }
                    });
                    
                    return settings;
                }
                
                /**
                 * Recursively walk DOM element and convert to Bricks JSON
                 */
                function elementToBricks(element, parentId = 0) {
                    // Skip text nodes, comments, scripts, styles
                    if (element.nodeType !== 1) return null;
                    const tagName = element.tagName.toLowerCase();
                    if (['script', 'style', 'meta', 'link', 'title'].includes(tagName)) return null;
                    
                    // Determine Bricks element type from data-bricks attribute or tag name
                    let bricksName = element.getAttribute('data-bricks');
                    if (!bricksName) {
                        // Fallback tag → Bricks element mapping
                        // Note: all basic Bricks elements share the same style settings
                        // (padding, margin, typography, background, border, shadow, position, sizing, flex/grid)
                        // so the CSS → settings conversion applies uniformly to all element types.
                        const tagMap = {
                            'section': 'section',
                            'header': 'section',
                            'footer': 'section',
                            'nav': 'block',       // nav as block (section has strict Bricks constraints)
                            'article': 'block',
                            'aside': 'block',
                            'main': 'block',
                            'div': 'block',
                            'figure': 'block',
                            'figcaption': 'text-basic',
                            'h1': 'heading', 'h2': 'heading', 'h3': 'heading',
                            'h4': 'heading', 'h5': 'heading', 'h6': 'heading',
                            'p': 'text-basic',
                            'span': 'text-basic',
                            'strong': 'text-basic',
                            'em': 'text-basic',
                            'small': 'text-basic',
                            'blockquote': 'text-basic',
                            'button': 'button',
                            'a': 'text-link',     // anchors → text-link (has link + icon support)
                            'img': 'image',
                            'i': 'icon',          // <i class="fas fa-..."> → Bricks icon element
                            'ul': 'text-basic',   // lists rendered as HTML in text-basic
                            'ol': 'text-basic',
                            'table': 'text-basic',
                            'hr': 'divider',       // horizontal rule → Bricks divider element
                            'svg': 'custom-html-css-script',
                            'canvas': 'custom-html-css-script',
                            'iframe': 'custom-html-css-script',
                        };
                        bricksName = tagMap[tagName] || 'block';
                    }
                    
                    // Generate element ID: reuse brxe-XXXXXX HTML id if present, otherwise generate new
                    const htmlId = element.getAttribute('id');
                    let id;
                    if (htmlId && /^brxe-[a-z]{6}$/.test(htmlId)) {
                        id = htmlId.slice(5); // extract the 6-letter Bricks ID
                        usedIds.add(id);
                        ChatState.globalUsedIds.add(id);
                    } else {
                        id = genId();
                    }
                    const bricksElement = {
                        id: id,
                        name: bricksName,
                        parent: parentId,
                        children: [],
                        settings: {},
                        themeStyles: []
                    };
                    
                    // Parse inline styles
                    // Skip general CSS conversion for divider elements — they have native controls
                    const styleAttr = element.getAttribute('style');
                    if (styleAttr && bricksName !== 'divider') {
                        const cssStyles = parseInlineCSS(styleAttr);
                        const bricksSettings = stylesToBricksSettings(cssStyles);
                        Object.assign(bricksElement.settings, bricksSettings);
                    }
                    // Note: brxe-XXXXXX HTML ids are used directly as Bricks element ids — no _cssId needed

                    // Map standard HTML classes to Bricks custom classes
                    const htmlClass = element.getAttribute('class');
                    if (htmlClass) {
                        const classes = htmlClass.split(/\s+/).filter(c => c && !c.startsWith('fa-') && !['fas','far','fab','fa'].includes(c)); // filter out font-awesome icons
                        if (classes.length) {
                            bricksElement.settings._cssClasses = classes.join(' ');
                        }
                    }

                    // Map other HTML/data/aria attributes to Bricks custom attributes
                    const customAttributes = [];
                    const ignoredAttrs = new Set(['id', 'class', 'style', 'data-bricks', 'data-hover-background', 'data-hover-transform', 'data-icon', 'data-icon-position', 'data-icon-size', 'data-icon-gap', 'href', 'target', 'rel', 'src', 'alt', 'width', 'height']);
                    for (const attr of element.attributes) {
                        const name = attr.name;
                        if (!ignoredAttrs.has(name) && !name.startsWith('snn-')) {
                            customAttributes.push({
                                _id: genId(),
                                name: name,
                                value: attr.value
                            });
                        }
                    }
                    if (customAttributes.length > 0) {
                        bricksElement.settings._attributes = customAttributes;
                    }

                    // ── Flex property inference for layout elements ──
                    // If block/container/section has flex-specific properties (direction, justify-content,
                    // align-items, align-content, flex-wrap) but no explicit _display, infer display:flex.
                    // Bricks block defaults to display:block so these properties are silently ignored
                    // unless _display is explicitly set. Container/section are always-flex in Bricks,
                    // but setting _display here is harmless and keeps the JSON self-documenting.
                    if (['block', 'container', 'section'].includes(bricksName)) {
                        const flexTriggerProps = ['_direction', '_justifyContent', '_alignItems', '_alignContent', '_flexWrap'];
                        const hasFlexTrigger = flexTriggerProps.some(p => bricksElement.settings[p]);
                        if (hasFlexTrigger && !bricksElement.settings._display) {
                            bricksElement.settings._display = 'flex';
                        }
                    }

                    // Apply CSS default: flex-direction row for flex containers without explicit direction.
                    // This creates the intended layout from HTML since Bricks Block natively defaults to column.
                    // Also convert inline-flex → flex (Bricks does not support inline-flex properly).
                    if (bricksElement.settings._display === 'inline-flex') {
                        bricksElement.settings._display = 'flex';
                        // Preserve shrink-wrap intent: default to max-content width if no width set
                        if (!bricksElement.settings._width) bricksElement.settings._width = 'max-content';
                    }
                    if (bricksElement.settings._display === 'flex' && !bricksElement.settings._direction) {
                        bricksElement.settings._direction = 'row';
                    }

                    // Preserve semantic HTML tags for layout elements
                    if (['block', 'div', 'container', 'section'].includes(bricksName)) {
                        if (['main', 'article', 'header', 'footer', 'aside', 'nav', 'section', 'details', 'figure', 'figcaption', 'address', 'hgroup'].includes(tagName)) {
                            bricksElement.settings.tag = tagName;
                            // Bricks sections with a custom semantic tag (footer/header/etc.) collapse to
                            // display:none unless _display is explicitly set. Ensure it has a value.
                            if (bricksName === 'section' && !bricksElement.settings._display) {
                                bricksElement.settings._display = 'flex';
                            }
                        } else if (tagName === 'a') {
                            bricksElement.settings.tag = 'a';
                            bricksElement.settings.link = parseLink(element);
                        } else if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span'].includes(tagName) && bricksName === 'block') {
                             bricksElement.settings.tag = tagName;
                        }
                    }

                    // Helper: parse href to Bricks link object
                    function parseLink(el) {
                        const href = el.getAttribute('href') || el.getAttribute('data-href') || '#';
                        const linkObj = {
                            type: (href.startsWith('#') || href.startsWith('/')) ? 'internal' : 'external',
                            url: href
                        };
                        if (el.getAttribute('target') === '_blank') {
                            linkObj.blank = true;
                        }
                        if (el.getAttribute('rel') === 'nofollow' || el.getAttribute('rel') === 'noopener') {
                            linkObj.rel = el.getAttribute('rel');
                        }
                        return linkObj;
                    }

                    // Handle specific element types
                    // All elements also share common style settings (_padding, _margin, _typography,
                    // _background, _border, _boxShadow, _display, flex/grid props, _position, sizing)
                    // handled above via stylesToBricksSettings. Here we set element-specific content fields.
                    let isLeaf = false;
                    switch (bricksName) {
                        case 'heading':
                            bricksElement.settings.text = element.innerHTML.trim(); // allow inner <span> bold/italic
                            bricksElement.settings.tag  = ['h1','h2','h3','h4','h5','h6'].includes(tagName) ? tagName : 'h2';
                            isLeaf = true; // heading content is text — never separate Bricks elements
                            break;

                        case 'text-basic':
                            // Lists/tables: outerHTML needed so Bricks renders the full markup
                            if (['ul','ol','table','blockquote'].includes(tagName)) {
                                bricksElement.settings.text = element.outerHTML.trim();
                                return bricksElement; // leaf — no children
                            }
                            // p / span / strong / em / small: innerHTML only; styling comes from native _typography
                            bricksElement.settings.text = element.innerHTML.trim();
                            isLeaf = true; // don't recurse into inline children
                            break;

                        case 'text':
                            // Use innerHTML so all formatting (bold, links, multiple p tags) stays intact
                            bricksElement.settings.text = element.innerHTML.trim();
                            isLeaf = true; // Tell the compiler not to try and turn inner tags into separate Bricks elements
                            break;

                        case 'icon': {
                            // Standalone FA icon element: <i class="fas fa-star" data-bricks="icon">
                            // or just <i class="fas fa-star"> via tagMap
                            const iClass = element.getAttribute('class') || '';
                            const iconObj = parseFaIcon(iClass);
                            if (iconObj) bricksElement.settings.icon = iconObj;
                            // iconSize from font-size style (already parsed into _typography['font-size'])
                            // Move it to iconSize (Bricks icon-specific field) if present
                            if (bricksElement.settings._typography && bricksElement.settings._typography['font-size']) {
                                bricksElement.settings.iconSize = bricksElement.settings._typography['font-size'];
                                delete bricksElement.settings._typography['font-size'];
                                if (!Object.keys(bricksElement.settings._typography).length) delete bricksElement.settings._typography;
                            }
                            // data-icon-size override
                            if (element.getAttribute('data-icon-size')) bricksElement.settings.iconSize = element.getAttribute('data-icon-size');
                            // iconColor from color style → move to iconColor
                            if (bricksElement.settings._typography && bricksElement.settings._typography.color) {
                                bricksElement.settings.iconColor = bricksElement.settings._typography.color;
                                delete bricksElement.settings._typography.color;
                                if (!Object.keys(bricksElement.settings._typography).length) delete bricksElement.settings._typography;
                            }
                            isLeaf = true; // icons have no meaningful children
                            break;
                        }

                        case 'text-link': {
                            // Collect text, stripping any <i> icon children from the text content
                            const linkIconEl = element.querySelector('i[class*="fa-"]');
                            bricksElement.settings.text = element.textContent.trim();
                            bricksElement.settings.link = parseLink(element);
                            // Extract icon from <i> child or data-icon attribute
                            const linkIconClass = element.getAttribute('data-icon') || (linkIconEl ? linkIconEl.getAttribute('class') : '');
                            const linkIcon = parseFaIcon(linkIconClass);
                            if (linkIcon) {
                                bricksElement.settings.icon = linkIcon;
                                if (element.getAttribute('data-icon-position')) bricksElement.settings.iconPosition = element.getAttribute('data-icon-position');
                                if (element.getAttribute('data-icon-gap')) bricksElement.settings.iconGap = element.getAttribute('data-icon-gap');
                            }
                            isLeaf = true; // text-link content is text; don't recurse into <i> children
                            break;
                        }

                        case 'button': {
                            // Collect text, stripping any <i> icon children from the text content
                            const btnIconEl = element.querySelector('i[class*="fa-"]');
                            bricksElement.settings.text = element.textContent.trim();
                            // data-href on <button>, href on <a>
                            const btnHref = element.getAttribute('href') || element.getAttribute('data-href');
                            if (btnHref) bricksElement.settings.link = parseLink(element);
                            // Extract icon from <i> child or data-icon attribute
                            const btnIconClass = element.getAttribute('data-icon') || (btnIconEl ? btnIconEl.getAttribute('class') : '');
                            const btnIcon = parseFaIcon(btnIconClass);
                            if (btnIcon) {
                                bricksElement.settings.icon = btnIcon;
                                if (element.getAttribute('data-icon-position')) bricksElement.settings.iconPosition = element.getAttribute('data-icon-position');
                                if (element.getAttribute('data-icon-gap')) bricksElement.settings.iconGap = element.getAttribute('data-icon-gap');
                            }
                            isLeaf = true; // don't recurse into button children (text/icon already captured)
                            break;
                        }

                        case 'image': {
                            const src = element.getAttribute('src') || element.getAttribute('data-src');
                            if (src) bricksElement.settings.image = { url: src, size: 'full' };
                            const alt = element.getAttribute('alt');
                            if (alt) bricksElement.settings.alt = alt;
                            isLeaf = true; // img is a void element — no children
                            break;
                        }

                        case 'divider': {
                            // HR element → Bricks divider with height, width, style, color, alignment
                            // Extract from border styles and computed styles
                            const styleAttr = element.getAttribute('style');
                            const cssStyles = styleAttr ? parseInlineCSS(styleAttr) : {};
                            
                            // Height: from border-width, border-top-width, or height (default: 2)
                            let height = '2';
                            if (cssStyles['border-width']) {
                                height = extractNumeric(cssStyles['border-width']) || '2';
                            } else if (cssStyles['border-top-width']) {
                                height = extractNumeric(cssStyles['border-top-width']) || '2';
                            } else if (cssStyles['height']) {
                                height = extractNumeric(cssStyles['height']) || '2';
                            }
                            bricksElement.settings.height = height;
                            
                            // Width: from width style (default: 100% or full container)
                            // Also set _width (element layout width) to prevent the divider from
                            // stretching full-width as a block and pushing adjacent DOM elements.
                            if (cssStyles['width']) {
                                const rawWidth = cssStyles['width'];
                                const numWidth = extractNumeric(rawWidth);
                                bricksElement.settings.width = rawWidth.includes('%') ? rawWidth : (numWidth + 'px');
                                // _width keeps the element box constrained to the same size
                                bricksElement.settings._width = numWidth;
                            } else {
                                // No explicit width → default to 100% element width (safe default)
                                bricksElement.settings._width = '100';
                            }
                            
                            // Style: from border-style (solid, dashed, dotted, double, groove, ridge, inset, outset)
                            // Bricks supports: solid, dashed, dotted, double, groove, ridge, inset, outset
                            let dividerStyle = 'solid';
                            if (cssStyles['border-style']) {
                                dividerStyle = cssStyles['border-style'];
                            } else if (cssStyles['border-top-style']) {
                                dividerStyle = cssStyles['border-top-style'];
                            }
                            bricksElement.settings.style = dividerStyle;
                            
                            // Color: from border-color, border-top-color, or color
                            let dividerColor = null;
                            if (cssStyles['border-color']) {
                                dividerColor = { raw: cssStyles['border-color'] };
                            } else if (cssStyles['border-top-color']) {
                                dividerColor = { raw: cssStyles['border-top-color'] };
                            } else if (cssStyles['color']) {
                                dividerColor = { raw: cssStyles['color'] };
                            }
                            if (dividerColor) {
                                bricksElement.settings.color = dividerColor;
                            }
                            
                            // Alignment: from text-align or margin-left/right
                            // Maps to justifyContent: flex-start (left), center, flex-end (right)
                            let justifyContent = 'flex-start';
                            if (cssStyles['text-align']) {
                                const align = cssStyles['text-align'];
                                if (align === 'center') justifyContent = 'center';
                                else if (align === 'right') justifyContent = 'flex-end';
                            } else if (cssStyles['margin-left'] === 'auto' && cssStyles['margin-right'] === 'auto') {
                                justifyContent = 'center';
                            } else if (cssStyles['margin-left'] === 'auto') {
                                justifyContent = 'flex-end';
                            }
                            bricksElement.settings.justifyContent = justifyContent;
                            
                            isLeaf = true; // hr is a void element — no children
                            break;
                        }

                        case 'custom-html-css-script':
                            bricksElement.settings.content = element.outerHTML;
                            isLeaf = true; // Leaf element — no children walked
                            break;

                        case 'section':
                            // section/container/block — no content fields, children handled below
                            break;

                        default:
                            // block, div, etc. — no content fields, children handled below
                            break;
                    }
                    
                    // Handle data-hover attributes
                    const hoverBg = element.getAttribute('data-hover-background');
                    if (hoverBg) {
                        if (!bricksElement.settings._background) bricksElement.settings._background = {};
                        bricksElement.settings['_background:hover'] = { color: { raw: hoverBg } };
                        // Add transition if not present
                        if (!bricksElement.settings._cssTransition) {
                            bricksElement.settings._cssTransition = 'all 0.3s ease';
                        }
                    }
                    
                    const hoverTransform = element.getAttribute('data-hover-transform');
                    if (hoverTransform) {
                        bricksElement.settings['_transform:hover'] = hoverTransform;
                        if (!bricksElement.settings._cssTransition) {
                            bricksElement.settings._cssTransition = 'all 0.3s ease';
                        }
                    }
                    
                    // === Unified CSS Finalization ===
                    // Combines three sources of custom CSS into a single _cssCustom string:
                    //  1. Unknown inline CSS props accumulated by stylesToBricksSettings (raw, wrapped in %root%{})
                    //  2. custom-css attribute (raw props, wrapped in %root%{})
                    //  3. <style data-style-id="..."> linked CSS (already uses %root% selectors)
                    {
                        const cssParts = [];

                        // Helper to clean up custom CSS indentation without breaking \n structure
                        // It removes leading whitespace from each line individually
                        const cleanCss = (cssStr) => cssStr.split('\n').map(line => line.trim()).filter(line => line).join('\n');

                        // Source 1: inline unknown CSS props (raw props → %root%{} block)
                        if (bricksElement.settings._cssCustom) {
                            const raw = cleanCss(bricksElement.settings._cssCustom);
                            if (raw) {
                                if (!raw.includes('%root%') && !raw.includes('@keyframes')) {
                                    cssParts.push('%root% {\n' + raw + '\n}');
                                } else {
                                    cssParts.push(raw);
                                }
                            }
                            delete bricksElement.settings._cssCustom;
                        }

                        // Source 2: custom-css attribute (raw props → %root%{} block)
                        const customCssAttr = element.getAttribute('custom-css');
                        if (customCssAttr && customCssAttr.trim()) {
                            const rawAttr = cleanCss(customCssAttr);
                            if (!rawAttr.includes('%root%') && !rawAttr.includes('@keyframes')) {
                                cssParts.push('%root% {\n' + rawAttr + '\n}');
                            } else {
                                cssParts.push(rawAttr);
                            }
                        }

                        const elemHtmlId = element.getAttribute('id');
                        if (elemHtmlId && styleIdMap[elemHtmlId]) {
                            const converted = convertStyleIdCss(styleIdMap[elemHtmlId], elemHtmlId);
                            if (converted) cssParts.push(converted);
                        }

                        if (cssParts.length) {
                            bricksElement.settings._cssCustom = cssParts.join(' ').replace(/\s+/g, ' ').trim();
                        }
                    }

                    // Preserve HTML class names as Bricks _cssClasses (enables parent CSS targeting children by class)
                    const elemClass = element.getAttribute('class');
                    if (elemClass) {
                        const classes = elemClass.trim().split(/\s+/)
                            .filter(c => c && !c.startsWith('brxe-') && !c.startsWith('snn-'));
                        if (classes.length) {
                            bricksElement.settings._cssClasses = classes.join(' ');
                        }
                    }

                    // Add to content array
                    content.push(bricksElement);

                    // Process children recursively (skip for leaf elements like img, svg)
                    if (!isLeaf) {
                        Array.from(element.childNodes).forEach(child => {
                            if (child.nodeType === 3) { // Text node
                                const text = child.textContent.trim();
                                if (text) {
                                    const textId = genId();
                                    const textElement = {
                                        id: textId,
                                        name: 'text-basic',
                                        parent: id,
                                        children: [],
                                        settings: { text: text },
                                        themeStyles: []
                                    };
                                    content.push(textElement);
                                    bricksElement.children.push(textId);
                                }
                            } else if (child.nodeType === 1) { // Element node
                                const childElement = elementToBricks(child, id);
                                if (childElement) {
                                    bricksElement.children.push(childElement.id);
                                }
                            }
                        });
                    }
                    
                    return bricksElement;
                }
                
                // Start compilation from body
                const bodyElements = Array.from(doc.body.children);
                bodyElements.forEach(element => {
                    elementToBricks(element, 0);
                });
                
                // Apply responsive rules automatically
                applyResponsiveRules(content);
                
                return { content };
            }
            
            /**
             * STEP 3: Apply automatic responsive adjustments
             * - Large typography (60+) gets tablet and mobile variants
             * - Multi-column grids get responsive breakpoints
             * - Flex rows get mobile column stacking (ALL flex rows, not just large-gap ones)
             * - Padding/margin reduced on mobile
             * Note: breakpoint suffix pattern is :tablet_portrait and :mobile_landscape
             * This same pattern works for ALL Bricks element style settings since all
             * basic elements share the same style tab (padding, margin, typography, background, border, etc.)
             */
            function applyResponsiveRules(contentArray) {
                contentArray.forEach(element => {
                    const settings = element.settings;

                    // ── Typography: scale down large font sizes on smaller screens ──
                    if (settings._typography && settings._typography['font-size']) {
                        const fontSize = parseInt(settings._typography['font-size']);
                        if (fontSize >= 72) {
                            if (!settings['_typography:tablet_portrait'])
                                settings['_typography:tablet_portrait'] = { 'font-size': String(Math.round(fontSize * 0.7)) };
                            if (!settings['_typography:mobile_landscape'])
                                settings['_typography:mobile_landscape'] = { 'font-size': String(Math.round(fontSize * 0.5)) };
                        } else if (fontSize >= 48) {
                            if (!settings['_typography:tablet_portrait'])
                                settings['_typography:tablet_portrait'] = { 'font-size': String(Math.round(fontSize * 0.75)) };
                            if (!settings['_typography:mobile_landscape'])
                                settings['_typography:mobile_landscape'] = { 'font-size': String(Math.round(fontSize * 0.6)) };
                        } else if (fontSize >= 32) {
                            if (!settings['_typography:mobile_landscape'])
                                settings['_typography:mobile_landscape'] = { 'font-size': String(Math.round(fontSize * 0.75)) };
                        }
                    }

                    // ── Grid: responsive column layouts ──
                    if (settings._gridTemplateColumns) {
                        const colMatch = settings._gridTemplateColumns.match(/repeat\((\d+),/);
                        // Count fractions in value (e.g. "1fr 2fr 1fr" = 3 columns)
                        const frCount = (settings._gridTemplateColumns.match(/\d*fr/g) || []).length;
                        const colCount = colMatch ? parseInt(colMatch[1]) : frCount;

                        if (colCount >= 4) {
                            if (!settings['_gridTemplateColumns:tablet_portrait'])
                                settings['_gridTemplateColumns:tablet_portrait'] = 'repeat(2, 1fr)';
                            if (!settings['_gridTemplateColumns:mobile_landscape'])
                                settings['_gridTemplateColumns:mobile_landscape'] = '1fr';
                        } else if (colCount === 3) {
                            if (!settings['_gridTemplateColumns:tablet_portrait'])
                                settings['_gridTemplateColumns:tablet_portrait'] = 'repeat(2, 1fr)';
                            if (!settings['_gridTemplateColumns:mobile_landscape'])
                                settings['_gridTemplateColumns:mobile_landscape'] = '1fr';
                        } else if (colCount === 2) {
                            if (!settings['_gridTemplateColumns:mobile_landscape'])
                                settings['_gridTemplateColumns:mobile_landscape'] = '1fr';
                        }

                        // Reduce grid gap on mobile
                        const gridGap = parseInt(settings._columnGap || settings._gridGap || 0);
                        if (gridGap > 16) {
                            if (!settings['_columnGap:mobile_landscape'])
                                settings['_columnGap:mobile_landscape'] = String(Math.round(gridGap * 0.5));
                            if (!settings['_rowGap:mobile_landscape'])
                                settings['_rowGap:mobile_landscape'] = String(Math.round(gridGap * 0.5));
                        }
                    }

                    // ── Flex rows: stack to column on mobile ──
                    // Skip fixed-size containers (e.g. icon wells) — they have explicit _width AND _height
                    // set to small px values and must never be stacked or have justify-content reset.
                    const isFixedSizeBox = settings._width && settings._height &&
                        /^\d+(px|rem|em)$/.test(settings._width) && /^\d+(px|rem|em)$/.test(settings._height) &&
                        parseInt(settings._width) <= 120 && parseInt(settings._height) <= 120;

                    if (settings._display === 'flex' && settings._direction === 'row' && !isFixedSizeBox) {
                        if (!settings['_direction:mobile_landscape'])
                            settings['_direction:mobile_landscape'] = 'column';

                        // Reduce column gap (becomes vertical gap after stacking)
                        const gap = parseInt(settings._columnGap || 0);
                        if (gap > 16 && !settings['_columnGap:mobile_landscape'])
                            settings['_columnGap:mobile_landscape'] = String(Math.round(gap * 0.5));

                        // Reset justify-content so stacked items fill width
                        if (settings._justifyContent && settings._justifyContent !== 'flex-start' && !settings['_justifyContent:mobile_landscape'])
                            settings['_justifyContent:mobile_landscape'] = 'flex-start';
                    }

                    // ── Section padding: reduce on tablet and mobile ──
                    if (settings._padding) {
                        const topPad    = parseInt(settings._padding.top    || 0);
                        const botPad    = parseInt(settings._padding.bottom || topPad);
                        const leftPad   = parseInt(settings._padding.left   || 0);
                        const rightPad  = parseInt(settings._padding.right  || leftPad);

                        if (topPad >= 80) {
                            if (!settings['_padding:tablet_portrait'])
                                settings['_padding:tablet_portrait'] = {
                                    top: String(Math.round(topPad * 0.7)),
                                    bottom: String(Math.round(botPad * 0.7)),
                                    left: settings._padding.left, right: settings._padding.right
                                };
                            if (!settings['_padding:mobile_landscape'])
                                settings['_padding:mobile_landscape'] = {
                                    top: String(Math.round(topPad * 0.5)),
                                    bottom: String(Math.round(botPad * 0.5)),
                                    left: leftPad > 20 ? String(Math.round(leftPad * 0.6)) : settings._padding.left,
                                    right: rightPad > 20 ? String(Math.round(rightPad * 0.6)) : settings._padding.right
                                };
                        } else if (topPad >= 40) {
                            if (!settings['_padding:mobile_landscape'])
                                settings['_padding:mobile_landscape'] = {
                                    top: String(Math.round(topPad * 0.6)),
                                    bottom: String(Math.round(botPad * 0.6)),
                                    left: settings._padding.left, right: settings._padding.right
                                };
                        }
                    }

                    // ── Width/max-width: full width on mobile ──
                    if (settings._widthMax && settings._widthMax !== '100%') {
                        if (!settings['_widthMax:mobile_landscape'])
                            settings['_widthMax:mobile_landscape'] = '100%';
                    }
                });
            }