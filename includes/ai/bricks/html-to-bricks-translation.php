<?php if ( ! defined( 'ABSPATH' ) ) {     exit; } ?>
// ================================================================
            // CSS CLASS → BRICKS GLOBAL CLASS COMPILER
            // Architecture: AI writes native CSS classes → compiler registers them
            // as Bricks Global Classes → elements reference via _cssGlobalClasses.
            // CSS flows verbatim: AI → <style> → _cssCustom → browser. No translation.
            //
            // On top of that, a real cascade resolver runs over the parsed DOM and
            // translates the LAYOUT-critical properties into Bricks' own element
            // settings (_display, _direction, _alignItems, ...). Bricks renders those
            // as #brxe-{id}{...} — ID specificity — which is the only reliable way to
            // beat its own .brxe-* defaults:
            //
            //   .brxe-section  { display:flex; flex-direction:column; align-items:center;     flex-wrap:wrap; width:100% }
            //   .brxe-container{ display:flex; flex-direction:column; align-items:flex-start; flex-wrap:wrap; width:1100px }
            //   .brxe-block    { display:flex; flex-direction:column; align-items:flex-start; flex-wrap:wrap; width:100% }
            //
            // Those defaults are why generated pages came out column-when-it-should-be-
            // row (and vice versa): a plain .my-row{display:flex} class ties .brxe-block
            // on specificity, so the winner depended on stylesheet order.
            // ================================================================

            // Bricks' own breakpoints (includes/breakpoints.php). A native setting for a
            // non-base breakpoint is stored as "<key>:<breakpoint>", e.g. "_direction:mobile_portrait".
            const BRICKS_BREAKPOINTS = [
                { key: 'tablet_portrait',  width: 991 },
                { key: 'mobile_landscape', width: 767 },
                { key: 'mobile_portrait',  width: 478 }
            ];
            const DESKTOP_CANVAS_WIDTH = 1279;

            // Element names Bricks treats as layout elements (Element::is_layout_element()).
            // These use _direction / _flexWrap / _columnGap; everything else uses
            // _flexDirection / _gap — Bricks defines the two sets in different files.
            const LAYOUT_ELEMENT_NAMES = new Set(['section', 'container', 'block', 'div']);

            // FontAwesome utility tokens that are NOT the glyph name.
            const FA_MODIFIER = /^fa-(fw|border|inverse|li|ul|pull-left|pull-right|spin|pulse|beat|fade|bounce|shake|flip|flip-horizontal|flip-vertical|flip-both|rotate-(90|180|270|by)|stack|stack-1x|stack-2x|xs|sm|lg|xl|2xl|[0-9]+x|sharp|duotone|solid|regular|brands|light|thin)$/;

            /**
             * Parse a FontAwesome icon class string into a Bricks icon object.
             *
             * The previous version put the ENTIRE class string into icon.icon, so
             * "fas fa-star hero-icon" leaked a layout class into the icon value and left
             * Bricks' icon picker showing nothing. It also mapped bare "fa" and "far" to
             * fontawesomeRegular — FA6 Free has almost no Regular glyphs, so those
             * rendered as empty boxes.
             */
            function parseFaIcon(classString) {
                if (!classString) return null;
                const STYLE = {
                    'fas': 'fontawesomeSolid',   'fa-solid':   'fontawesomeSolid',
                    'far': 'fontawesomeRegular', 'fa-regular': 'fontawesomeRegular',
                    'fab': 'fontawesomeBrands',  'fa-brands':  'fontawesomeBrands',
                    'fa':  'fontawesomeSolid'    // FA4/FA6 alias — Solid, never Regular
                };
                let library = null, glyph = null;
                for (const token of classString.trim().split(/\s+/)) {
                    if (Object.prototype.hasOwnProperty.call(STYLE, token)) {
                        if (!library || library === 'fontawesomeSolid') library = STYLE[token];
                    } else if (!glyph && /^fa-/.test(token) && !FA_MODIFIER.test(token)) {
                        glyph = token;
                    }
                }
                if (!glyph) return null;
                if (!library) library = 'fontawesomeSolid';
                const prefix = library === 'fontawesomeBrands' ? 'fab'
                             : library === 'fontawesomeRegular' ? 'far'
                             : 'fas';
                return { library: library, icon: prefix + ' ' + glyph };
            }

            /** True for class tokens that belong to FontAwesome rather than the design system. */
            function isFaToken(c) {
                return c === 'fa' || c === 'fas' || c === 'far' || c === 'fab'
                    || c === 'fal' || c === 'fad' || c === 'fat' || /^fa-/.test(c);
            }

            /**
             * Find an icon on an element: an explicit data-icon attribute, or a nested
             * <i>/<span> carrying FontAwesome classes.
             *
             * Buttons and links compile as leaf elements, so a nested <i> used to be
             * dropped silently — <button><i class="fas fa-arrow-right"></i> Go</button>
             * lost its icon entirely. The AI writes nested <i> because that is ordinary
             * HTML; expecting it to use data-icon every time never worked.
             */
            function extractInlineIcon(el) {
                const dataIcon = el.getAttribute('data-icon');
                if (dataIcon) {
                    const obj = parseFaIcon(dataIcon);
                    if (obj) return { icon: obj, position: el.getAttribute('data-icon-position') || 'left', node: null };
                }
                let node = null;
                for (const cand of el.querySelectorAll('i, span')) {
                    if (parseFaIcon(cand.getAttribute('class') || '')) { node = cand; break; }
                }
                if (!node) return null;
                const obj = parseFaIcon(node.getAttribute('class') || '');
                if (!obj) return null;

                // Position: is there any real text before the icon?
                let position = 'left';
                const html = el.innerHTML;
                const at   = html.indexOf(node.outerHTML);
                if (at > 0) {
                    const before = html.slice(0, at).replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
                    if (before) position = 'right';
                }
                return { icon: obj, position: position, node: node };
            }

            /** innerHTML of an element with its icon node removed, for button/link text. */
            function textWithoutIcon(el, found) {
                const clone = el.cloneNode(true);
                if (found && found.node) {
                    const originals = Array.from(el.querySelectorAll('*'));
                    const clones    = Array.from(clone.querySelectorAll('*'));
                    const at        = originals.indexOf(found.node);
                    if (at >= 0 && clones[at]) clones[at].remove();
                }
                return clone.innerHTML.replace(/\s+/g, ' ').trim();
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
                const rel = el.getAttribute('rel');
                if (rel) link.rel = rel;
                return link;
            }

            // ================================================================
            // STEP 1: Parse CSS class definitions from <style> blocks
            // Returns { className: fullCssBlock } — each class's CSS is
            // self-contained: base rules + @media blocks + @keyframes + :hover etc.
            // ================================================================

            /**
             * Split a CSS rule body into individual "prop: value" declarations without
             * breaking on semicolons that live inside url(), quotes or parentheses
             * (e.g. url(data:image/svg+xml;base64,...) or content: ";").
             */
            function splitDeclarations(body) {
                const out = [];
                let buf = '', depth = 0, quote = null;
                for (let i = 0; i < body.length; i++) {
                    const ch = body[i];
                    if (quote) {
                        buf += ch;
                        if (ch === quote && body[i - 1] !== '\\') quote = null;
                        continue;
                    }
                    if (ch === '"' || ch === "'") { quote = ch; buf += ch; continue; }
                    if (ch === '(') depth++;
                    if (ch === ')') depth--;
                    if (ch === ';' && depth === 0) { if (buf.trim()) out.push(buf.trim()); buf = ''; continue; }
                    buf += ch;
                }
                if (buf.trim()) out.push(buf.trim());
                return out;
            }

            /**
             * Format CSS rule body: split properties to separate lines with indentation.
             * "font-size: 60px; color: #fff;" → "  font-size: 60px;\n  color: #fff;"
             */
            function formatCSSBody(body) {
                if (!body) return '';
                return splitDeclarations(body).map(p => '  ' + p + ';').join('\n');
            }

            /** "color:red;gap:2px" → { color: "red", gap: "2px" } */
            function parseDeclarations(body) {
                const out = {};
                splitDeclarations(body || '').forEach(decl => {
                    const at = decl.indexOf(':');
                    if (at === -1) return;
                    const prop = decl.slice(0, at).trim().toLowerCase();
                    const val  = decl.slice(at + 1).trim();
                    if (prop) out[prop] = val;
                });
                return out;
            }

            function parseCSSRules(css) {
                const classes = {};

                // Remove comments
                css = css.replace(/\/\*[\s\S]*?\*\//g, '');

                // ── STEP 0: Identify all @media blocks first ──
                // We MUST extract and remove @media blocks BEFORE parsing class rules,
                // otherwise rules inside @media get double-matched (once by ruleRegex,
                // once by the @media recursion).
                const mediaBlocks = [];
                const mediaRegex = /@media\s*[^{]+\{/g;
                let m;
                while ((m = mediaRegex.exec(css)) !== null) {
                    const startIdx = m.index + m[0].length - 1;
                    let depth = 1, endIdx = startIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }
                    mediaBlocks.push({
                        query: m[0].substring(0, m[0].length - 1).trim(),
                        content: css.substring(startIdx + 1, endIdx - 1),
                        start: m.index,
                        end: endIdx
                    });
                }

                // Build a clean CSS string with @media blocks removed
                mediaBlocks.sort((a, b) => b.start - a.start); // descending: remove from end
                let cssCleaned = css;
                mediaBlocks.forEach(b => {
                    cssCleaned = cssCleaned.substring(0, b.start) + cssCleaned.substring(b.end);
                });

                // ── STEP 1: Parse class rules from non-@media CSS ──
                // parseRuleBlocks handles compound selectors (comma-separated)
                // and only captures rules whose selector contains '.'
                parseRuleBlocks(cssCleaned, classes);

                // ── STEP 2: Process @media blocks recursively ──
                // Restore original order for consistent CSS output
                mediaBlocks.reverse();
                mediaBlocks.forEach(({ query, content }) => {
                    const innerRules = parseCSSRules(content);
                    for (const [name, cssBlock] of Object.entries(innerRules)) {
                        if (!classes[name]) classes[name] = '';
                        classes[name] += query + ' {\n' + cssBlock + '\n}\n\n';
                    }
                });

                // ── STEP 3: Handle @keyframes blocks ──
                const keyframeRegex = /@keyframes\s+([a-zA-Z0-9_-]+)\s*\{/g;
                while ((m = keyframeRegex.exec(css)) !== null) {
                    const animName = m[1];
                    const startIdx = m.index + m[0].length - 1;
                    let depth = 1, endIdx = startIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }
                    const keyframeBlock = css.substring(m.index, endIdx);
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
             * Parse CSS rule blocks from a string that has NO @media blocks.
             * Handles compound selectors (comma-separated) by splitting and
             * storing the rule body under each base class name found.
             *
             * Example: ".hero, .banner { color: red; }"
             *   → classes["hero"] += ".hero { color: red; }"
             *   → classes["banner"] += ".banner { color: red; }"
             */
            function parseRuleBlocks(css, classes) {
                let i = 0;
                while (i < css.length) {
                    const braceIdx = css.indexOf('{', i);
                    if (braceIdx === -1) break;

                    // Walk back to find where the selector block starts
                    // (after previous '}' or from beginning of string)
                    let selStart = braceIdx - 1;
                    while (selStart >= 0 && css[selStart] !== '}') selStart--;
                    selStart++;

                    const selectorText = css.substring(selStart, braceIdx).trim();

                    // Count braces to find matching closing brace
                    let depth = 1, endIdx = braceIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }

                    const rawBody = css.substring(braceIdx + 1, endIdx - 1);

                    // Only process class-based rules (selectors containing '.').
                    // Skip at-rules (@supports, @layer, @font-face) — their bodies are
                    // nested rule blocks, not declarations, and would corrupt the output.
                    if (selectorText && selectorText.includes('.') && !selectorText.startsWith('@')) {
                        // Split compound/comma-separated selectors
                        const selectors = selectorText.split(',').map(s => s.trim());

                        for (const sel of selectors) {
                            // Extract the first .className from the selector
                            const classMatch = sel.match(/\.([a-zA-Z0-9_-]+)/);
                            if (classMatch) {
                                const className = classMatch[1];
                                if (!classes[className]) classes[className] = '';
                                const body = formatCSSBody(rawBody);
                                classes[className] += sel + ' {\n' + body + '\n}\n\n';
                            }
                        }
                    }

                    i = endIdx;
                }
            }

            // ================================================================
            // CASCADE RESOLVER
            // Collect every style rule into a flat list, then let the browser's own
            // selector engine (Element.matches) decide what applies to each element.
            // This is what makes ".features-grid > div { display:flex }" and
            // ".card p { margin:0 }" resolve exactly the way they do in the preview.
            // ================================================================

            /** Approximate CSS specificity as a single sortable integer. */
            function computeSpecificity(sel) {
                let s = sel.replace(/\[[^\]]*\]/g, '§A§');
                const ids     = (s.match(/#[\w-]+/g) || []).length;
                const classes = (s.match(/\.[\w-]+/g) || []).length
                              + (s.match(/§A§/g) || []).length
                              + (s.match(/:(?!:)[\w-]+/g) || []).length;
                const types   = (s.replace(/[.#][\w-]+/g, '')
                                  .replace(/§A§/g, '')
                                  .match(/(^|[\s>+~(,])\s*[a-zA-Z][\w-]*/g) || []).length;
                return ids * 10000 + classes * 100 + types;
            }

            /**
             * Map an @media query onto a Bricks breakpoint.
             * Returns { bp, appliesAtDesktop }. A min-width query at or below the desktop
             * canvas width folds into the base cascade, because that is what the designer
             * sees in the preview at desktop size.
             */
            function mediaQueryToBreakpoint(query) {
                const max = query.match(/max-width\s*:\s*(\d+(?:\.\d+)?)\s*px/i);
                if (max) {
                    const w = parseFloat(max[1]);
                    let best = null, bestDiff = Infinity;
                    BRICKS_BREAKPOINTS.forEach(bp => {
                        const d = Math.abs(bp.width - w);
                        if (d < bestDiff) { bestDiff = d; best = bp.key; }
                    });
                    return { bp: best, appliesAtDesktop: w >= DESKTOP_CANVAS_WIDTH };
                }
                const min = query.match(/min-width\s*:\s*(\d+(?:\.\d+)?)\s*px/i);
                if (min) {
                    return { bp: null, appliesAtDesktop: parseFloat(min[1]) <= DESKTOP_CANVAS_WIDTH };
                }
                return { bp: null, appliesAtDesktop: false };
            }

            /** Selectors carrying interaction/state pseudos must not drive base layout settings. */
            const STATE_SELECTOR = /::|:(hover|focus|focus-within|focus-visible|active|visited|target|checked|disabled|placeholder)\b/i;

            /** Flatten one CSS string into { selector, decls, spec, order } rules. */
            function collectRules(css, into, orderStart) {
                let order = orderStart;
                let i = 0;
                while (i < css.length) {
                    const braceIdx = css.indexOf('{', i);
                    if (braceIdx === -1) break;
                    let selStart = braceIdx - 1;
                    while (selStart >= 0 && css[selStart] !== '}') selStart--;
                    selStart++;
                    const selectorText = css.substring(selStart, braceIdx).trim();
                    let depth = 1, endIdx = braceIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }
                    const rawBody = css.substring(braceIdx + 1, endIdx - 1);
                    if (selectorText && !selectorText.startsWith('@')) {
                        const decls = parseDeclarations(rawBody);
                        if (Object.keys(decls).length) {
                            selectorText.split(',').map(s => s.trim()).forEach(sel => {
                                if (!sel || STATE_SELECTOR.test(sel)) return;
                                into.push({ selector: sel, decls: decls, spec: computeSpecificity(sel), order: order++ });
                            });
                        }
                    }
                    i = endIdx;
                }
                return order;
            }

            /**
             * Build { base: [...rules], breakpoints: { tablet_portrait: [...], ... } }
             * from raw CSS text.
             */
            function buildCssRuleSet(css) {
                css = (css || '').replace(/\/\*[\s\S]*?\*\//g, '');
                const base = [];
                const breakpoints = {};

                // Pull @media blocks out first so their rules are not read as base rules.
                const mediaBlocks = [];
                const mediaRegex = /@media\s*[^{]+\{/g;
                let m;
                while ((m = mediaRegex.exec(css)) !== null) {
                    const startIdx = m.index + m[0].length - 1;
                    let depth = 1, endIdx = startIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }
                    mediaBlocks.push({
                        query: m[0].substring(0, m[0].length - 1).trim(),
                        content: css.substring(startIdx + 1, endIdx - 1),
                        start: m.index,
                        end: endIdx
                    });
                }
                let cssCleaned = css;
                mediaBlocks.slice().sort((a, b) => b.start - a.start).forEach(b => {
                    cssCleaned = cssCleaned.substring(0, b.start) + cssCleaned.substring(b.end);
                });

                let order = collectRules(cssCleaned, base, 0);

                mediaBlocks.forEach(({ query, content }) => {
                    const mapped = mediaQueryToBreakpoint(query);
                    if (mapped.appliesAtDesktop) {
                        order = collectRules(content, base, order);
                    } else if (mapped.bp) {
                        if (!breakpoints[mapped.bp]) breakpoints[mapped.bp] = [];
                        collectRules(content, breakpoints[mapped.bp], 0);
                    }
                });

                return { base: base, breakpoints: breakpoints };
            }

            /** Resolve the declarations that actually apply to one element. */
            function resolveDeclsFor(el, rules) {
                if (!rules || !rules.length) return {};
                const matched = [];
                for (const r of rules) {
                    let hit = false;
                    try { hit = el.matches(r.selector); } catch (e) { hit = false; }
                    if (hit) matched.push(r);
                }
                matched.sort((a, b) => (a.spec - b.spec) || (a.order - b.order));
                const out = {};
                matched.forEach(r => Object.assign(out, r.decls));
                // A stray inline style="" wins over any stylesheet rule.
                const inline = el.getAttribute('style');
                if (inline) Object.assign(out, parseDeclarations(inline));
                return out;
            }

            // ================================================================
            // NATIVE BRICKS LAYOUT SETTINGS
            // ================================================================

            function applyFlexGaps(out, decls) {
                const gap = decls['gap'] || decls['grid-gap'];
                let row = decls['row-gap'], col = decls['column-gap'];
                if (gap) {
                    const parts = gap.trim().split(/\s+/);
                    if (!row) row = parts[0];
                    if (!col) col = parts[1] || parts[0];
                }
                if (row) out._rowGap = row.trim();
                if (col) out._columnGap = col.trim();
            }

            /**
             * Translate resolved CSS into Bricks element settings.
             *
             * For LAYOUT elements every layout property is emitted even when the CSS
             * omits it, using the CSS-standard default. That is deliberate: Bricks forces
             * flex/column/wrap/centre on these elements, so staying silent means
             * inheriting a layout the HTML never asked for. Writing the standard default
             * back is what makes the canvas match the preview one-to-one.
             */
            function deriveNativeSettings(decls, bricksName, parentDecls) {
                const out = {};
                const isLayout = LAYOUT_ELEMENT_NAMES.has(bricksName);
                const dirKey   = isLayout ? '_direction' : '_flexDirection';
                let display    = (decls['display'] || '').trim().toLowerCase();

                if (isLayout) {
                    if (!display) display = 'block'; // a plain <div> is block flow, not flex-column
                    if (display === 'flex' || display === 'inline-flex') {
                        out._display        = display;
                        out[dirKey]         = (decls['flex-direction']  || 'row').trim();
                        out._flexWrap       = (decls['flex-wrap']       || 'nowrap').trim();
                        out._alignItems     = (decls['align-items']     || 'stretch').trim();
                        out._justifyContent = (decls['justify-content'] || 'flex-start').trim();
                        applyFlexGaps(out, decls);
                    } else if (display === 'grid' || display === 'inline-grid') {
                        out._display = 'grid';
                        if (decls['grid-template-columns']) out._gridTemplateColumns = decls['grid-template-columns'].trim();
                        if (decls['grid-template-rows'])    out._gridTemplateRows    = decls['grid-template-rows'].trim();
                        if (decls['grid-auto-columns'])     out._gridAutoColumns     = decls['grid-auto-columns'].trim();
                        if (decls['grid-auto-rows'])        out._gridAutoRows        = decls['grid-auto-rows'].trim();
                        if (decls['grid-auto-flow'])        out._gridAutoFlow        = decls['grid-auto-flow'].trim();
                        if (decls['justify-items'])         out._justifyItemsGrid    = decls['justify-items'].trim();
                        if (decls['align-items'])           out._alignItemsGrid      = decls['align-items'].trim();
                        if (decls['justify-content'])       out._justifyContentGrid  = decls['justify-content'].trim();
                        if (decls['align-content'])         out._alignContentGrid    = decls['align-content'].trim();
                        const g = decls['gap'] || decls['grid-gap'];
                        if (g) out._gridGap = g.trim();
                    } else {
                        out._display = display;
                    }
                } else if (display) {
                    out._display = display;
                    if (display === 'flex' || display === 'inline-flex') {
                        if (decls['flex-direction'])  out[dirKey]         = decls['flex-direction'].trim();
                        if (decls['align-items'])     out._alignItems     = decls['align-items'].trim();
                        if (decls['justify-content']) out._justifyContent = decls['justify-content'].trim();
                        const g = decls['gap'];
                        if (g) out._gap = g.trim();
                    }
                }

                // ── Width ──
                // .brxe-container is hard-coded to 1100px and .brxe-block to 100%.
                // Neither matches what a plain <div> does, so restore the HTML behaviour.
                if (decls['width']) {
                    out._width = decls['width'].trim();
                } else if (isLayout) {
                    const pDisplay = ((parentDecls && parentDecls['display']) || '').toLowerCase();
                    const pDir     = ((parentDecls && parentDecls['flex-direction']) || 'row').toLowerCase();
                    const parentIsRowFlex = (pDisplay === 'flex' || pDisplay === 'inline-flex') && !pDir.startsWith('column');
                    if (bricksName === 'container')   out._width = parentIsRowFlex ? 'auto' : '100%';
                    else if (parentIsRowFlex)         out._width = 'auto';
                }

                return out;
            }

            /**
             * Extract CSS custom properties from :root { ... } block.
             * Uses brace counting (not regex [^}]*) to correctly handle
             * nested braces and multi-line content.
             * Returns { variables: [{name, value}], raw: "full :root block" }
             */
            function extractRootVariables(css) {
                const rootStart = css.search(/:root\s*\{/);
                if (rootStart === -1) return { variables: [], raw: '' };

                const braceIdx = css.indexOf('{', rootStart);
                let depth = 1, endIdx = braceIdx + 1;
                while (depth > 0 && endIdx < css.length) {
                    if (css[endIdx] === '{') depth++;
                    else if (css[endIdx] === '}') depth--;
                    endIdx++;
                }

                const fullBlock = css.substring(rootStart, endIdx);
                const body = css.substring(braceIdx + 1, endIdx - 1);

                const variables = [];
                const propRegex = /--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g;
                let m;
                while ((m = propRegex.exec(body)) !== null) {
                    variables.push({ name: m[1], value: m[2].trim() });
                }
                return { variables, raw: fullBlock };
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

            /**
             * Extract non-class CSS blocks (body, html, *, @font-face, etc.)
             * that parseCSSRules does NOT capture. These must be injected
             * directly into the page so tag-level styles survive.
             *
             * Captures: body{ }, html{ }, *{ }, @font-face{ }, @import statements,
             * and any @-rule that is NOT @media or @keyframes.
             *
             * Does NOT capture @media (handled by parseCSSRules) or .class rules.
             */
            function extractGlobalCSS(css) {
                css = css.replace(/\/\*[\s\S]*?\*\//g, '');
                let result = '';
                let i = 0;

                while (i < css.length) {
                    const braceIdx = css.indexOf('{', i);
                    if (braceIdx === -1) {
                        // Capture remaining text after last brace
                        // (e.g., @import statements at the end, trailing whitespace)
                        const tail = css.substring(i).trim();
                        if (tail) result += tail + '\n';
                        break;
                    }

                    // Walk back to find selector start
                    let selStart = braceIdx - 1;
                    while (selStart >= 0 && css[selStart] !== '}') selStart--;
                    selStart++;

                    const selectorText = css.substring(selStart, braceIdx).trim();

                    // Count braces to find matching closing brace
                    let depth = 1, endIdx = braceIdx + 1;
                    while (depth > 0 && endIdx < css.length) {
                        if (css[endIdx] === '{') depth++;
                        else if (css[endIdx] === '}') depth--;
                        endIdx++;
                    }

                    const fullBlock = css.substring(selStart, endIdx);

                    // Capture non-class selectors: body, html, *, :root, @font-face, etc.
                    // Skip @media (handled by parseCSSRules), skip .class rules (in global classes)
                    if (selectorText && !selectorText.includes('.') && !selectorText.startsWith('@media')) {
                        result += fullBlock + '\n';
                    }

                    i = endIdx;
                }

                // Also capture @import / @charset statements BEFORE the first brace block
                // These don't have { } so the brace loop above won't find them
                const firstBrace = css.indexOf('{');
                if (firstBrace !== -1) {
                    const preamble = css.substring(0, firstBrace).trim();
                    if (preamble) {
                        // Extract @import and other at-rules
                        const atRules = preamble.match(/@(import|charset|namespace)[^;]+;/gi);
                        if (atRules) {
                            atRules.forEach(r => {
                                if (!result.includes(r)) result = r + '\n' + result;
                            });
                        }
                    }
                }

                return result.trim();
            }

            /**
             * Register every class that appears on an element but has no CSS rule of its
             * own. Without this the compiler dropped such names entirely (classNameToId
             * lookup → undefined → filtered out), so the class never reached the rendered
             * HTML and any ".parent .child" rule written against it stopped matching.
             */
            function registerBareClasses(doc, classNameToId, genId, classMap) {
                let added = 0;
                doc.querySelectorAll('[class]').forEach(el => {
                    (el.getAttribute('class') || '').split(/\s+/).forEach(cn => {
                        if (!cn || isFaToken(cn)) return;
                        if (classNameToId[cn]) return;
                        const gid = genId();
                        classNameToId[cn] = gid;
                        if (classMap) classMap[cn] = { id: gid, css: '' };
                        added++;
                    });
                });
                return added;
            }

            // ================================================================
            // STEP 2: Core Class-Based Compiler — HTML to Bricks JSON
            // Returns { content: [...], globalClasses: [...], classNameToId: {...} }
            // ================================================================

            function compileHtmlToBricksJson(html, preComputedClassNameToId = null, preComputedRuleSet = null) {
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
                let ruleSet = preComputedRuleSet;
                if (!preComputedClassNameToId) {
                    let localCss = '';
                    doc.querySelectorAll('style').forEach(styleEl => {
                        const css = styleEl.textContent;
                        localCss += css + '\n';
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
                    registerBareClasses(doc, classNameToId, genId, classMap);
                    if (!ruleSet) ruleSet = buildCssRuleSet(localCss);
                }
                if (!ruleSet) ruleSet = { base: [], breakpoints: {} };
                const breakpointKeys = Object.keys(ruleSet.breakpoints || {});

                // ── STEP 2b: Tag → Bricks element type fallback ──────────────
                // Anything not listed here falls through to 'block', which is how forms,
                // tables, videos and inputs used to come out as empty nested boxes.
                const tagMap = {
                    'section': 'section', 'header': 'section', 'footer': 'section',
                    'nav': 'block', 'article': 'block', 'aside': 'block', 'main': 'block',
                    'div': 'block', 'figure': 'block',
                    'h1': 'heading', 'h2': 'heading', 'h3': 'heading',
                    'h4': 'heading', 'h5': 'heading', 'h6': 'heading',
                    'p': 'text-basic', 'span': 'text-basic', 'strong': 'text-basic',
                    'em': 'text-basic', 'small': 'text-basic', 'blockquote': 'text-basic',
                    'figcaption': 'text-basic', 'address': 'text-basic', 'time': 'text-basic',
                    'label': 'text-basic', 'ul': 'text-basic', 'ol': 'text-basic',
                    'dl': 'text-basic', 'pre': 'text-basic',
                    'a': 'text-link', 'button': 'button', 'img': 'image',
                    'i': 'icon',
                    'hr': 'divider',
                    // Rendered verbatim so nothing is lost in translation.
                    'svg': 'custom-html-css-script', 'canvas': 'custom-html-css-script',
                    'iframe': 'custom-html-css-script', 'video': 'custom-html-css-script',
                    'audio': 'custom-html-css-script', 'picture': 'custom-html-css-script',
                    'table': 'custom-html-css-script', 'form': 'custom-html-css-script',
                    'details': 'custom-html-css-script', 'input': 'custom-html-css-script',
                    'textarea': 'custom-html-css-script', 'select': 'custom-html-css-script',
                    'object': 'custom-html-css-script', 'embed': 'custom-html-css-script',
                };

                // Bricks' text-basic tag control only offers these; anything else needs customTag.
                const TEXT_TAG_OPTIONS = ['div', 'p', 'span', 'figcaption', 'address', 'figure'];

                // ── STEP 2c: Walk DOM, create minimal Bricks elements ─────────
                function walkElement(el, parentId = 0, parentDecls = null) {
                    if (el.nodeType !== 1) return null;
                    const tag = el.tagName.toLowerCase();
                    if (['script', 'style', 'meta', 'link', 'title', 'br', 'wbr'].includes(tag)) return null;

                    let bricksName = el.getAttribute('data-bricks') || tagMap[tag] || 'block';

                    // ── Auto-detect divider elements from class names ─────
                    // If a block element has a divider-related class but no explicit
                    // data-bricks or <hr> tag, override to 'divider' so decorative
                    // lines don't incorrectly become 'block'.
                    if (bricksName === 'block' && !el.getAttribute('data-bricks')) {
                        const cls = el.getAttribute('class');
                        if (cls && /(?:^|\s)(?:short-?line|long-?line|divider|separator|hr|line-?decorative|decorative-?line)(?:\s|$)/i.test(cls)) {
                            bricksName = 'divider';
                        }
                    }

                    const id = genId();  // Every element MUST have a unique 6-letter ID

                    const element = {
                        id: id,
                        name: bricksName,
                        parent: parentId,
                        children: [],
                        settings: {},
                        themeStyles: []
                    };

                    // ── Styling: _cssGlobalClasses (array of global class IDs) ──
                    // Visual styling stays in the global class CSS, untranslated.
                    const htmlClass = el.getAttribute('class');
                    if (htmlClass) {
                        const classNames = htmlClass.split(/\s+/).filter(c => c && !isFaToken(c));
                        const globalClassIds = classNames
                            .map(cn => classNameToId[cn])
                            .filter(Boolean);
                        if (globalClassIds.length) {
                            element.settings._cssGlobalClasses = globalClassIds;
                        }
                    }

                    // ── Resolve the cascade for this element, then translate the
                    //    layout-critical part into native Bricks settings so it wins
                    //    against Bricks' own .brxe-* defaults (ID beats class).
                    const ownDecls = resolveDeclsFor(el, ruleSet.base);
                    const baseSettings = deriveNativeSettings(ownDecls, bricksName, parentDecls);
                    Object.assign(element.settings, baseSettings);

                    // Responsive: emit the same properties per Bricks breakpoint, so the
                    // ID-level desktop rule never strands the @media rules written in CSS.
                    breakpointKeys.forEach(bpKey => {
                        const bpDecls = Object.assign({}, ownDecls, resolveDeclsFor(el, ruleSet.breakpoints[bpKey]));
                        const bpSettings = deriveNativeSettings(bpDecls, bricksName, parentDecls);
                        Object.keys(bpSettings).forEach(k => {
                            if (bpSettings[k] !== baseSettings[k]) {
                                element.settings[k + ':' + bpKey] = bpSettings[k];
                            }
                        });
                    });

                    // ── Semantic HTML tag override for layout elements ──────
                    if (['block', 'container', 'section'].includes(bricksName)) {
                        if (['header', 'footer', 'nav', 'article', 'aside', 'main', 'section',
                             'figure', 'figcaption'].includes(tag)) {
                            element.settings.tag = tag;
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
                            if (['ul','ol','dl','table','blockquote','pre'].includes(tag)) {
                                element.settings.text = el.outerHTML.trim();
                                return element;
                            }
                            element.settings.text = el.innerHTML.trim();
                            // Bricks renders text-basic as <div> by default. Without this,
                            // every "<p class=...>" became a <div> and any ".card p" rule
                            // the AI wrote matched in the preview but not on the canvas.
                            if (TEXT_TAG_OPTIONS.includes(tag)) {
                                element.settings.tag = tag;
                            } else {
                                element.settings.tag = 'custom';
                                element.settings.customTag = tag;
                            }
                            isLeaf = true;
                            break;

                        case 'text':
                            element.settings.text = el.innerHTML.trim();
                            isLeaf = true;
                            break;

                        case 'icon': {
                            const iconObj = parseFaIcon(el.getAttribute('class') || '');
                            if (iconObj) {
                                element.settings.icon = iconObj;
                            } else {
                                // Not a font icon (inline <svg>, sprite, ...) — keep it verbatim
                                element.name = 'custom-html-css-script';
                                element.settings.content = el.outerHTML;
                            }
                            if (el.getAttribute('data-icon-size')) {
                                element.settings.iconSize = el.getAttribute('data-icon-size');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'text-link': {
                            const found = extractInlineIcon(el);
                            // innerHTML (not textContent) so <a>Read <strong>more</strong></a>
                            // keeps its formatting.
                            element.settings.text = textWithoutIcon(el, found);
                            element.settings.link = parseLink(el);
                            if (found) {
                                element.settings.icon = found.icon;
                                element.settings.iconPosition = el.getAttribute('data-icon-position') || found.position;
                            }
                            if (el.getAttribute('data-icon-gap')) {
                                element.settings.iconGap = el.getAttribute('data-icon-gap');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'button': {
                            const found = extractInlineIcon(el);
                            element.settings.text = textWithoutIcon(el, found);
                            const href = el.getAttribute('href') || el.getAttribute('data-href');
                            if (href) element.settings.link = parseLink(el);
                            if (found) {
                                element.settings.icon = found.icon;
                                element.settings.iconPosition = el.getAttribute('data-icon-position') || found.position;
                            }
                            if (el.getAttribute('data-icon-gap')) {
                                element.settings.iconGap = el.getAttribute('data-icon-gap');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'image': {
                            const src = el.getAttribute('src') || el.getAttribute('data-src');
                            if (src) element.settings.image = { url: src, size: 'full', external: true };
                            const alt = el.getAttribute('alt');
                            if (alt) element.settings.alt = alt;
                            isLeaf = true;
                            break;
                        }

                        case 'divider': {
                            // Minimal defaults — the CSS class handles all styling.
                            // Never clobber a width the stylesheet actually declared
                            // (a short decorative rule is usually 40-80px, not full width).
                            element.settings.height = '2';
                            element.settings.style = 'solid';
                            if (!element.settings._width) element.settings._width = '100%';
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
                                        children: [], settings: { text: text, tag: 'span' }, themeStyles: []
                                    });
                                    element.children.push(textId);
                                }
                            } else {
                                const childEl = walkElement(child, id, ownDecls);
                                if (childEl) element.children.push(childEl.id);
                            }
                        });
                    }

                    return element;
                }

                // Start compilation from body
                Array.from(doc.body.children).forEach(el => walkElement(el, 0, null));

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
