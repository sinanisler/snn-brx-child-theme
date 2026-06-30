<?php if ( ! defined( 'ABSPATH' ) ) {     exit; } ?>
// ================================================================
            // CSS CLASS → BRICKS GLOBAL CLASS COMPILER
            // Architecture: AI writes native CSS classes → compiler registers them
            // as Bricks Global Classes → elements reference via _cssGlobalClasses.
            // CSS flows verbatim: AI → <style> → _cssCustom → browser. No translation.
            // ================================================================

            /**
             * Parse FontAwesome icon class string into Bricks icon object.
             * Supports: fas fa-icon (solid), far fa-icon / fa fa-icon (regular), fab fa-icon (brands)
             */
            function parseFaIcon(classString) {
                if (!classString) return null;
                const cls = classString.trim();
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

            /**
             * Parse href → Bricks link object
             */
            function parseLink(el) {
                const href = el.getAttribute('href') || el.getAttribute('data-href') || '#';
                const link = {
                    type: (href.startsWith('#') || href.startsWith('/')) ? 'internal' : 'external',
                    url: href
                };
                if (el.getAttribute('target') === '_blank') link.blank = true;
                if (el.getAttribute('rel') === 'nofollow' || el.getAttribute('rel') === 'noopener') {
                    link.rel = el.getAttribute('rel');
                }
                return link;
            }

            // ================================================================
            // STEP 1: Parse CSS class definitions from <style> blocks
            // Returns { className: fullCssBlock } — each class's CSS is
            // self-contained: base rules + @media blocks + @keyframes + :hover etc.
            // ================================================================

            /**
             * Format CSS rule body: split properties to separate lines with indentation.
             * "font-size: 60px; color: #fff;" → "  font-size: 60px;\n  color: #fff;"
             */
            function formatCSSBody(body) {
                if (!body) return '';
                const props = body.split(';')
                    .map(p => p.trim())
                    .filter(p => p.length > 0)
                    .map(p => '  ' + p + ';');
                return props.join('\n');
            }

            function parseCSSRules(css) {
                const classes = {};

                // Remove comments
                css = css.replace(/\/\*[\s\S]*?\*\//g, '');

                // Match top-level .classname { ... } blocks including pseudo-classes/elements.
                // Examples: .hero {...}, .hero-heading {...}, .hero-button:hover {...}, .card::before {...}
                // Captures the base class name (before any : or ::), and preserves the full selector.
                const ruleRegex = /\.([a-zA-Z0-9_-]+)((?:::[a-zA-Z0-9_-]+|:[a-zA-Z0-9_-]+)*)\s*\{/g;
                let match;
                while ((match = ruleRegex.exec(css)) !== null) {
                    const baseName = match[1];           // "hero-button"
                    const pseudoPart = match[2] || '';    // ":hover" or "::before" or ""
                    const fullSelector = '.' + baseName + pseudoPart;
                    const startIndex = match.index + match[0].length - 1;
                    let depth = 1;
                    let endIndex = startIndex + 1;
                    while (depth > 0 && endIndex < css.length) {
                        if (css[endIndex] === '{') depth++;
                        else if (css[endIndex] === '}') depth--;
                        endIndex++;
                    }
                    // Preserve original formatting of the rule body
                    const rawBody = css.substring(startIndex + 1, endIndex - 1);
                    const body = formatCSSBody(rawBody);
                    // Store FULL rule with selector preserved and clean formatting
                    if (!classes[baseName]) classes[baseName] = '';
                    classes[baseName] += fullSelector + ' {\n' + body + '\n}\n\n';
                }

                // Match @media blocks and associate their inner class rules
                const mediaRegex = /@media\s*[^{]+\{/g;
                while ((match = mediaRegex.exec(css)) !== null) {
                    const startIdx = match.index + match[0].length - 1;
                    let depth = 1;
                    let endIdx = startIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }
                    const mediaContent = css.substring(startIdx + 1, endIdx - 1);
                    const mediaQuery = css.substring(match.index, match.index + match[0].length - 1).trim();
                    // Extract class rules inside the media block
                    const innerRules = parseCSSRules(mediaContent);
                    for (const [name, cssBlock] of Object.entries(innerRules)) {
                        if (!classes[name]) classes[name] = '';
                        classes[name] += mediaQuery + ' {\n' + cssBlock + '\n}\n\n';
                    }
                }

                // Match @keyframes blocks and associate with the class that uses them
                const keyframeRegex = /@keyframes\s+([a-zA-Z0-9_-]+)\s*\{/g;
                while ((match = keyframeRegex.exec(css)) !== null) {
                    const animName = match[1];
                    const startIdx = match.index + match[0].length - 1;
                    let depth = 1;
                    let endIdx = startIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }
                    const keyframeBlock = css.substring(match.index, endIdx);
                    // Find which class uses this animation
                    for (const [className, classCss] of Object.entries(classes)) {
                        if (classCss.includes(animName)) {
                            classes[className] += keyframeBlock + '\n\n';
                            break;
                        }
                    }
                }

                // Remove excessive blank lines but keep readability
                for (const name of Object.keys(classes)) {
                    classes[name] = classes[name].replace(/\n{3,}/g, '\n\n').trim();
                }

                return classes;
            }

            /**
             * Extract CSS custom properties from :root { ... } block.
             * Returns { variables: [{name, value}], raw: "full :root block" }
             */
            function extractRootVariables(css) {
                const rootMatch = css.match(/:root\s*\{([^}]*)\}/s);
                if (!rootMatch) return { variables: [], raw: '' };
                const body = rootMatch[1];
                const variables = [];
                const propRegex = /--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g;
                let m;
                while ((m = propRegex.exec(body)) !== null) {
                    variables.push({ name: m[1], value: m[2].trim() });
                }
                return { variables, raw: rootMatch[0] };
            }

            /**
             * Extract Google Fonts @import URLs from CSS.
             * Returns array of font URL strings.
             */
            function extractGoogleFonts(css) {
                const fonts = [];
                const importRegex = /@import\s+url\(['"]?([^'")\s]+)['"]?\)/gi;
                let m;
                while ((m = importRegex.exec(css)) !== null) {
                    fonts.push(m[1]);
                }
                return fonts;
            }

            // ================================================================
            // STEP 2: Core Class-Based Compiler — HTML to Bricks JSON
            // Returns { content: [...], globalClasses: [...], classNameToId: {...} }
            // ================================================================

            function compileHtmlToBricksJson(html, preComputedClassNameToId = null) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = [];
                const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
                const usedIds = new Set();

                // className → 6-letter ID mapping for _cssGlobalClasses references
                // If pre-computed from full HTML (Phase 0), use it. Otherwise parse from section HTML.
                const classNameToId = preComputedClassNameToId || {};
                const classMap = {};  // Only used if we need to extract CSS locally

                // Generate unique 6-letter element ID (separate from global class IDs)
                function genId() {
                    let id;
                    do {
                        id = Array.from({ length: 6 }, () => LETTERS[Math.floor(Math.random() * 26)]).join('');
                    } while (usedIds.has(id) || ChatState.globalUsedIds.has(id));
                    usedIds.add(id);
                    ChatState.globalUsedIds.add(id);
                    return id;
                }

                // ── STEP 2a: Extract CSS class definitions ──
                // If classNameToId is pre-computed (from full HTML), skip local extraction.
                // Otherwise, parse <style> blocks in the section HTML (fallback for direct calls).
                if (!preComputedClassNameToId) {
                    doc.querySelectorAll('style').forEach(styleEl => {
                        const css = styleEl.textContent;
                        const rules = parseCSSRules(css);
                        for (const [className, cssBody] of Object.entries(rules)) {
                            if (!classMap[className]) {
                                const gid = genId();
                                classMap[className] = { id: gid, css: '' };
                                classNameToId[className] = gid;
                            }
                            classMap[className].css += cssBody + ' ';
                        }
                    });
                }

                // ── STEP 2b: Tag → Bricks element type fallback ──────────────
                const tagMap = {
                    'section': 'section', 'header': 'section', 'footer': 'section',
                    'nav': 'block', 'article': 'block', 'aside': 'block', 'main': 'block',
                    'div': 'block', 'figure': 'block',
                    'h1': 'heading', 'h2': 'heading', 'h3': 'heading',
                    'h4': 'heading', 'h5': 'heading', 'h6': 'heading',
                    'p': 'text-basic', 'span': 'text-basic', 'strong': 'text-basic',
                    'em': 'text-basic', 'small': 'text-basic', 'blockquote': 'text-basic',
                    'a': 'text-link', 'button': 'button', 'img': 'image',
                    'i': 'icon', 'ul': 'text-basic', 'ol': 'text-basic',
                    'hr': 'divider',
                    'svg': 'custom-html-css-script', 'canvas': 'custom-html-css-script',
                    'iframe': 'custom-html-css-script',
                };

                // ── STEP 2c: Walk DOM, create minimal Bricks elements ─────────
                function walkElement(el, parentId = 0) {
                    if (el.nodeType !== 1) return null;
                    const tag = el.tagName.toLowerCase();
                    if (['script', 'style', 'meta', 'link', 'title'].includes(tag)) return null;

                    const bricksName = el.getAttribute('data-bricks') || tagMap[tag] || 'block';
                    const id = genId();  // Every element MUST have a unique 6-letter ID

                    const element = {
                        id: id,
                        name: bricksName,
                        parent: parentId,
                        children: [],
                        settings: {},
                        themeStyles: []
                    };

                    // ── Styling: ONLY _cssGlobalClasses (array of global class IDs) ──
                    // NO _padding, _margin, _typography, _background, _border, _display, etc.
                    // All visual styling is in the global class CSS — never translated.
                    const htmlClass = el.getAttribute('class');
                    if (htmlClass) {
                        const classNames = htmlClass.split(/\s+/)
                            .filter(c => c && !c.startsWith('fa-')
                                && !['fas', 'far', 'fab', 'fa'].includes(c));
                        const globalClassIds = classNames
                            .map(cn => classNameToId[cn])
                            .filter(Boolean);
                        if (globalClassIds.length) {
                            element.settings._cssGlobalClasses = globalClassIds;
                        }
                    }

                    // ── Semantic HTML tag override for layout elements ──────
                    if (['block', 'container', 'section'].includes(bricksName)) {
                        if (['header', 'footer', 'nav', 'article', 'aside', 'main', 'section',
                             'figure', 'figcaption'].includes(tag)) {
                            element.settings.tag = tag;
                            // Bricks sections with custom semantic tag need explicit _display
                            if (bricksName === 'section' && !element.settings._display) {
                                element.settings._display = 'flex';
                            }
                        }
                    }

                    // ── Element-specific content handling ───────────────────
                    let isLeaf = false;

                    switch (bricksName) {
                        case 'heading':
                            element.settings.text = el.innerHTML.trim();
                            element.settings.tag = ['h1','h2','h3','h4','h5','h6'].includes(tag) ? tag : 'h2';
                            isLeaf = true;
                            break;

                        case 'text-basic':
                            if (['ul','ol','table','blockquote'].includes(tag)) {
                                element.settings.text = el.outerHTML.trim();
                                return element;
                            }
                            element.settings.text = el.innerHTML.trim();
                            isLeaf = true;
                            break;

                        case 'text':
                            element.settings.text = el.innerHTML.trim();
                            isLeaf = true;
                            break;

                        case 'icon': {
                            const iClass = el.getAttribute('class') || '';
                            const iconObj = parseFaIcon(iClass);
                            if (iconObj) element.settings.icon = iconObj;
                            // iconSize from data-icon-size attribute
                            if (el.getAttribute('data-icon-size')) {
                                element.settings.iconSize = el.getAttribute('data-icon-size');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'text-link': {
                            element.settings.text = el.textContent.trim();
                            element.settings.link = parseLink(el);
                            const iconClass = el.getAttribute('data-icon');
                            if (iconClass) {
                                const iconObj = parseFaIcon(iconClass);
                                if (iconObj) element.settings.icon = iconObj;
                            }
                            if (el.getAttribute('data-icon-position')) {
                                element.settings.iconPosition = el.getAttribute('data-icon-position');
                            }
                            if (el.getAttribute('data-icon-gap')) {
                                element.settings.iconGap = el.getAttribute('data-icon-gap');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'button': {
                            element.settings.text = el.textContent.trim();
                            const href = el.getAttribute('href') || el.getAttribute('data-href');
                            if (href) element.settings.link = parseLink(el);
                            const btnIcon = el.getAttribute('data-icon');
                            if (btnIcon) {
                                const iconObj = parseFaIcon(btnIcon);
                                if (iconObj) element.settings.icon = iconObj;
                            }
                            if (el.getAttribute('data-icon-position')) {
                                element.settings.iconPosition = el.getAttribute('data-icon-position');
                            }
                            if (el.getAttribute('data-icon-gap')) {
                                element.settings.iconGap = el.getAttribute('data-icon-gap');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'image': {
                            const src = el.getAttribute('src') || el.getAttribute('data-src');
                            if (src) element.settings.image = { url: src, size: 'full' };
                            const alt = el.getAttribute('alt');
                            if (alt) element.settings.alt = alt;
                            isLeaf = true;
                            break;
                        }

                        case 'divider': {
                            // Minimal defaults — CSS class handles all styling
                            element.settings.height = '2';
                            element.settings.style = 'solid';
                            element.settings._width = '100';
                            isLeaf = true;
                            break;
                        }

                        case 'custom-html-css-script':
                            element.settings.content = el.outerHTML;
                            isLeaf = true;
                            break;
                    }

                    // ── Query loop support ──────────────────────────────────
                    const loopType = el.getAttribute('data-loop');
                    if (loopType) {
                        element.settings.hasLoop = true;
                        element.settings.query = {
                            post_type: [loopType],
                            posts_per_page: el.getAttribute('data-loop-posts-per-page') || '6',
                            orderby: [el.getAttribute('data-loop-orderby') || 'date'],
                            order: el.getAttribute('data-loop-order') || 'DESC'
                        };
                    }

                    // ── HTML data attributes → Bricks custom attributes ────
                    const ignoredAttrs = new Set([
                        'id', 'class', 'style', 'data-bricks',
                        'data-loop', 'data-loop-posts-per-page', 'data-loop-orderby', 'data-loop-order',
                        'data-icon', 'data-icon-position', 'data-icon-gap', 'data-icon-size',
                        'data-href', 'href', 'src', 'alt', 'target', 'rel'
                    ]);
                    const customAttrs = [];
                    for (const attr of el.attributes) {
                        if (!ignoredAttrs.has(attr.name)) {
                            customAttrs.push({ _id: genId(), name: attr.name, value: attr.value });
                        }
                    }
                    if (customAttrs.length) element.settings._attributes = customAttrs;

                    // ── Add to content array ────────────────────────────────
                    content.push(element);

                    // ── Recurse children ────────────────────────────────────
                    if (!isLeaf) {
                        Array.from(el.childNodes).forEach(child => {
                            if (child.nodeType === 3) { // Text node
                                const text = child.textContent.trim();
                                if (text) {
                                    const textId = genId();
                                    content.push({
                                        id: textId, name: 'text-basic', parent: id,
                                        children: [], settings: { text: text }, themeStyles: []
                                    });
                                    element.children.push(textId);
                                }
                            } else {
                                const childEl = walkElement(child, id);
                                if (childEl) element.children.push(childEl.id);
                            }
                        });
                    }

                    return element;
                }

                // Start compilation from body
                Array.from(doc.body.children).forEach(el => walkElement(el, 0));

                // ── STEP 2d: Build globalClasses array in Bricks-native format ──
                // CRITICAL: name === className (e.g. "hero-heading") — Bricks uses
                // the 'name' field as the actual CSS class added to the HTML element.
                // The 'id' field is the 6-letter internal reference for _cssGlobalClasses.
                const globalClasses = Object.entries(classMap).map(([className, gc]) => ({
                    id: gc.id,
                    name: className,           // ← CORRECT: Bricks applies this as the HTML class
                    settings: {
                        _cssCustom: gc.css     // Full raw CSS including .class-name{...}, @media, @keyframes
                    }
                }));

                return { content, globalClasses, classNameToId };
            }
