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

            /**
             * Separate statement at-rules (@import, @charset, @namespace) from the rest of a
             * stylesheet.
             *
             * Every rule walker in this file finds a selector by stepping back to the
             * previous "}". A statement has no braces, so it was glued onto the NEXT rule's
             * selector, which then started with "@" and was skipped as an at-rule: the first
             * class or :root after a font @import silently vanished from the page.
             *
             * A statement ends at a ";" outside quotes and parentheses. Google Fonts URLs
             * carry ";" between weights ("wght@400;600"), which a plain [^;]+ cut in half.
             */
            function splitStatementAtRules(css) {
                const statements = [];
                let rest = '', depth = 0, i = 0;
                while (i < css.length) {
                    if (depth === 0 && css[i] === '@' && /^@(import|charset|namespace)\b/i.test(css.slice(i, i + 12))) {
                        let j = i, quote = null, paren = 0;
                        for (; j < css.length; j++) {
                            const ch = css[j];
                            if (quote) { if (ch === quote && css[j - 1] !== '\\') quote = null; continue; }
                            if (ch === '"' || ch === "'") { quote = ch; continue; }
                            if (ch === '(') paren++;
                            else if (ch === ')') paren = Math.max(0, paren - 1);
                            else if (paren === 0 && (ch === ';' || ch === '{' || ch === '}')) break;
                        }
                        const end  = css[j] === ';' ? j + 1 : j;
                        const stmt = css.slice(i, end).trim();
                        if (stmt && !statements.includes(stmt)) statements.push(stmt);
                        i = end;
                        continue;
                    }
                    const ch = css[i];
                    if (ch === '{') depth++;
                    else if (ch === '}') depth = Math.max(0, depth - 1);
                    rest += ch;
                    i++;
                }
                return { statements, rest };
            }

            function parseCSSRules(css) {
                const classes = {};

                // Remove comments
                css = css.replace(/\/\*[\s\S]*?\*\//g, '');
                // Statement at-rules carry no classes and would corrupt the selector walk.
                css = splitStatementAtRules(css).rest;

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
            function collectRules(css, into, orderStart, stateInto = null) {
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
                                if (!sel) return;
                                if (STATE_SELECTOR.test(sel)) {
                                    // Kept aside: they must not drive base settings, but pinned
                                    // icon colours need them to stay reachable (see iconStateCss).
                                    if (stateInto && !sel.includes('::')) {
                                        stateInto.push({ selector: sel, decls: decls, spec: computeSpecificity(sel), order: order++ });
                                    }
                                    return;
                                }
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
                // A font @import glued onto the next selector hid that rule from the cascade.
                css = splitStatementAtRules(css).rest;
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

                const states = [];
                let order = collectRules(cssCleaned, base, 0, states);

                mediaBlocks.forEach(({ query, content }) => {
                    const mapped = mediaQueryToBreakpoint(query);
                    if (mapped.appliesAtDesktop) {
                        order = collectRules(content, base, order, states);
                    } else if (mapped.bp) {
                        if (!breakpoints[mapped.bp]) breakpoints[mapped.bp] = [];
                        collectRules(content, breakpoints[mapped.bp], 0);
                    }
                });

                return { base: base, breakpoints: breakpoints, states: states };
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
                } else if (bricksName === 'container') {
                    const pDisplay = ((parentDecls && parentDecls['display']) || '').toLowerCase();
                    const pDir     = ((parentDecls && parentDecls['flex-direction']) || 'row').toLowerCase();
                    const parentIsRowFlex = (pDisplay === 'flex' || pDisplay === 'inline-flex') && !pDir.startsWith('column');
                    out._width = parentIsRowFlex ? 'auto' : '100%';
                } else if (bricksName === 'block' || bricksName === 'div') {
                    // auto is what a plain <div> has: it fills block flow and grid cells,
                    // stretches in a stretch column, and shrinks in a row or a centred
                    // column. Only auto reproduces all of those — Bricks' width:100%
                    // blew up pills, badges and centred groups.
                    out._width = 'auto';
                }

                // [class*=brxe-]{max-width:100%} caps EVERY element; HTML has no such cap.
                // Pin a declared max-width natively, and lift the cap when a declared width
                // is allowed to outgrow its parent (ticker tracks, fixed-px cards).
                if (decls['max-width']) {
                    out._widthMax = decls['max-width'].trim();
                } else if (decls['width'] && canExceedParent(decls['width'])) {
                    out._widthMax = 'none';
                }

                return out;
            }

            // ================================================================
            // DECORATIVE LINES → BRICKS DIVIDER
            // A Bricks divider draws its line as border-top on an inner .line child,
            // sized by its own settings: height = thickness, width = length (the two
            // swap for vertical). So a line has to be recognised by what its CSS
            // DRAWS, not by its class name — "hero-eyebrow-line" with
            // data-bricks="block" slipped past the old name list and came out as a
            // plain block with the line painted as a background.
            // ================================================================

            const MAX_LINE_THICKNESS_PX = 8;   // thicker than this is a bar or shape, not a rule
            const BORDER_STYLES = new Set(['solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset', 'none', 'hidden']);

            /** "1px" → 1, "0.125rem" → 2, "0" → 0; relative or computed values → null. */
            function cssLengthToPx(v) {
                const m = String(v || '').trim().match(/^(-?\d*\.?\d+)(px|rem)?$/i);
                if (!m) return null;
                const n = parseFloat(m[1]);
                if (!m[2]) return n === 0 ? 0 : null;
                return m[2].toLowerCase() === 'rem' ? n * 16 : n;
            }

            /** Split a CSS value on whitespace, keeping var()/rgb() groups intact. */
            function splitCssTokens(v) {
                const out = [];
                let buf = '', depth = 0;
                for (const ch of String(v || '').trim()) {
                    if (ch === '(') depth++;
                    if (ch === ')') depth--;
                    if (/\s/.test(ch) && depth === 0) { if (buf) out.push(buf); buf = ''; continue; }
                    buf += ch;
                }
                if (buf) out.push(buf);
                return out;
            }

            function isColorToken(t) {
                return /^(#[0-9a-f]{3,8}|(rgb|hsl|hwb|lab|lch|oklab|oklch|color)a?\(.*\)|var\(.*\)|[a-z]+)$/i.test(t)
                    && !/^(none|transparent|inherit|initial|unset|auto)$/i.test(t);
            }

            function borderWidthPx(w) {
                if (!w) return 3;                          // initial border-width is "medium"
                const k = { thin: 1, medium: 3, thick: 5 }[String(w).toLowerCase()];
                return k !== undefined ? k : cssLengthToPx(w);
            }

            /** One side's border as { width, style, color }; the most specific declaration wins. */
            function resolveBorderSide(decls, side) {
                const out = { width: null, style: null, color: null };
                const idx = { top: 0, right: 1, bottom: 2, left: 3 }[side];
                const boxPart = (v) => {
                    const p = splitCssTokens(v);
                    if (p.length === 1) return p[0];
                    if (p.length === 2) return p[idx % 2];
                    if (p.length === 3) return idx === 3 ? p[1] : p[idx];
                    return p[idx];
                };
                // A shorthand resets all three parts, exactly as the browser does.
                const take = (shorthand) => {
                    if (!shorthand) return;
                    out.width = null; out.style = null; out.color = null;
                    splitCssTokens(shorthand).forEach(t => {
                        const lower = t.toLowerCase();
                        if (BORDER_STYLES.has(lower)) out.style = lower;
                        else if (/^(thin|medium|thick)$/i.test(t) || /^-?\d*\.?\d+(px|rem|em)?$/i.test(t)) out.width = t;
                        else if (isColorToken(t)) out.color = t;
                    });
                };
                take(decls['border']);
                if (decls['border-width']) out.width = boxPart(decls['border-width']);
                if (decls['border-style']) out.style = boxPart(decls['border-style']).toLowerCase();
                if (decls['border-color']) out.color = boxPart(decls['border-color']);
                take(decls['border-' + side]);
                if (decls['border-' + side + '-width']) out.width = decls['border-' + side + '-width'].trim();
                if (decls['border-' + side + '-style']) out.style = decls['border-' + side + '-style'].trim().toLowerCase();
                if (decls['border-' + side + '-color']) out.color = decls['border-' + side + '-color'].trim();
                return out;
            }

            function isVisibleBorder(b) {
                if (!b.style || b.style === 'none' || b.style === 'hidden') return false;
                const px = borderWidthPx(b.width);
                return px === null || px > 0;
            }

            /** Solid background colour: { color }, { gradient: true } for images/gradients, or null. */
            function resolveBackgroundPaint(decls) {
                const image = decls['background-image'];
                if (image && !/^none$/i.test(image.trim())) return { gradient: true };
                const shorthand = decls['background'];
                if (shorthand && /(url|gradient)\s*\(/i.test(shorthand)) return { gradient: true };
                const value = decls['background-color'] || shorthand;
                if (!value) return null;
                const tokens = splitCssTokens(value);
                // Anything beyond a single colour (position, repeat, ...) is not a plain line.
                if (tokens.length !== 1 || !isColorToken(tokens[0])) return null;
                return { color: tokens[0] };
            }

            /**
             * Does this element's resolved CSS draw a straight line? If so, describe it
             * in divider terms. Returns null for everything else — gradients, dots,
             * bars, boxes — so those keep their exact CSS on a block.
             */
            function analyzeLine(decls) {
                const w = (decls['width']  || '').trim();
                const h = (decls['height'] || '').trim();
                const wPx = cssLengthToPx(w), hPx = cssLengthToPx(h);
                const hasLength = (v) => !!v && !/^(auto|0|0px)$/i.test(v);

                const paint = resolveBackgroundPaint(decls);
                if (paint && paint.gradient) return null;   // fade lines need their real CSS

                if (paint && paint.color) {
                    // Horizontal: thin, and longer than it is thick
                    if (hPx !== null && hPx > 0 && hPx <= MAX_LINE_THICKNESS_PX && !(wPx !== null && wPx <= hPx)) {
                        return { direction: 'horizontal', thickness: hPx + 'px', length: hasLength(w) ? w : '100%', color: paint.color, style: 'solid' };
                    }
                    // Vertical: thin, and taller than it is wide
                    if (wPx !== null && wPx > 0 && wPx <= MAX_LINE_THICKNESS_PX && hasLength(h) && !(hPx !== null && hPx <= wPx)) {
                        return { direction: 'vertical', thickness: wPx + 'px', length: h, color: paint.color, style: 'solid' };
                    }
                    return null;
                }

                // Border-drawn: exactly one visible side, on an otherwise empty box.
                const sides = ['top', 'right', 'bottom', 'left']
                    .map(s => ({ side: s, b: resolveBorderSide(decls, s) }))
                    .filter(x => isVisibleBorder(x.b));
                if (sides.length !== 1) return null;
                const { side, b } = sides[0];
                const thickness = borderWidthPx(b.width);
                if (thickness === null || thickness > MAX_LINE_THICKNESS_PX) return null;
                const style = BORDER_STYLES.has(b.style) ? b.style : 'solid';
                const color = b.color && !/^currentcolor$/i.test(b.color) ? b.color : null;
                if (side === 'top' || side === 'bottom') {
                    if (hPx !== null && hPx > 0) return null;   // a box with a rule under it, not a bare line
                    return { direction: 'horizontal', thickness: thickness + 'px', length: hasLength(w) ? w : '100%', color: color, style: style };
                }
                if (!hasLength(h) || (wPx !== null && wPx > 0)) return null;
                return { direction: 'vertical', thickness: thickness + 'px', length: h, color: color, style: style };
            }

            /** An empty div/span/hr the markup did not pin to some unrelated element type. */
            function isLineCandidate(el) {
                const tag = el.tagName.toLowerCase();
                if (!['div', 'span', 'hr'].includes(tag)) return false;
                const explicit = el.getAttribute('data-bricks');
                if (explicit && !['block', 'div', 'text-basic', 'divider'].includes(explicit)) return false;
                if (el.children.length || el.textContent.trim()) return false;
                if (parseFaIcon(el.getAttribute('class') || '')) return false;   // an icon, not a line
                return true;
            }

            /**
             * Class names worn ONLY by elements that compile to dividers, mapped to the
             * line direction. Their line paint moves into the divider's own settings, so
             * it is stripped from the class — otherwise a border-drawn rule renders twice
             * (once on the divider root, once on Bricks' inner .line).
             */
            function findLineOnlyClasses(doc, ruleSet) {
                const lineClasses = {};
                const otherClasses = new Set();
                const rules = (ruleSet && ruleSet.base) || [];
                doc.body.querySelectorAll('*').forEach(el => {
                    const classes = (el.getAttribute('class') || '').split(/\s+/).filter(c => c && !isFaToken(c));
                    if (!classes.length) return;
                    const line = isLineCandidate(el) ? analyzeLine(resolveDeclsFor(el, rules)) : null;
                    classes.forEach(c => {
                        if (!line) { otherClasses.add(c); return; }
                        if (!lineClasses[c]) lineClasses[c] = line.direction;
                    });
                });
                otherClasses.forEach(c => { delete lineClasses[c]; });
                return lineClasses;
            }

            function isLinePaintProp(prop, direction) {
                if (/^background(-color|-image)?$/.test(prop)) return true;
                if (/^border(-(top|right|bottom|left))?(-(width|style|color))?$/.test(prop)) return true;
                return direction === 'vertical' ? prop === 'width' : prop === 'height';
            }

            /**
             * Remove line-drawing declarations from a class's base ".name {}" rule.
             * Margins, positioning, width, @media blocks and keyframes stay untouched.
             */
            function stripLinePaintFromCss(css, className, direction) {
                const target = '.' + className;
                let out = '', i = 0;
                while (i < css.length) {
                    const brace = css.indexOf('{', i);
                    if (brace === -1) { out += css.slice(i); break; }
                    const selector = css.slice(i, brace);
                    let depth = 1, end = brace + 1;
                    while (depth > 0 && end < css.length) {
                        if (css[end] === '{') depth++;
                        else if (css[end] === '}') depth--;
                        end++;
                    }
                    if (selector.trim() === target) {
                        const kept = splitDeclarations(css.slice(brace + 1, end - 1)).filter(d => {
                            const at = d.indexOf(':');
                            return at === -1 || !isLinePaintProp(d.slice(0, at).trim().toLowerCase(), direction);
                        });
                        out += selector + '{\n' + kept.map(d => '  ' + d + ';').join('\n') + (kept.length ? '\n' : '') + '}';
                    } else {
                        out += css.slice(i, end);
                    }
                    i = end;
                }
                return out;
            }

            /**
             * CSS colour → Bricks colour object. A var() naming a palette colour links
             * to that swatch ({id, raw, light} — the shape Bricks itself saves), so the
             * colour picker shows it selected rather than a loose value.
             */
            function toBricksColor(value) {
                const v = String(value || '').trim();
                const vm = v.match(/^var\(\s*(--[\w-]+)\s*(?:,[^)]*)?\)$/);
                if (vm) {
                    const swatch = findPaletteColor(vm[1]);
                    return swatch ? { id: swatch.id, raw: swatch.raw, light: swatch.light } : { raw: v };
                }
                if (/^#[0-9a-f]{3,8}$/i.test(v)) return { hex: v };
                if (/^rgba?\(/i.test(v)) return { rgb: v };
                return { raw: v };
            }

            function findPaletteColor(cssVar) {
                try {
                    if (typeof BricksHelper === 'undefined') return null;
                    const s = BricksHelper.getState();
                    const raw = 'var(' + cssVar + ')';
                    for (const palette of Array.from((s && s.colorPalette) || [])) {
                        for (const c of Array.from(palette.colors || [])) {
                            if (c.raw === raw) return c;
                        }
                    }
                } catch (e) { /* palette unavailable — fall back to a raw value */ }
                return null;
            }

            // ================================================================
            // ICON SIZE
            // Bricks ships .brxe-icon{font-size:60px}. The usual markup is
            //   <div class="feature-icon"><i class="fas fa-leaf"></i></div>
            // with the size on the WRAPPER. In HTML the <i> inherits it; in Bricks
            // the direct 60px rule cuts inheritance, so every such icon came out at
            // 60px. Resolve the size the icon really renders at and pin it as iconSize.
            // ================================================================

            /** Declarations for a node at a breakpoint: base, then every breakpoint at least as wide, widest first. */
            function declsAtBreakpoint(node, ruleSet, bpKey) {
                const out = Object.assign({}, resolveDeclsFor(node, ruleSet.base));
                if (!bpKey) return out;
                const limit = (BRICKS_BREAKPOINTS.find(b => b.key === bpKey) || {}).width;
                BRICKS_BREAKPOINTS
                    .filter(b => b.width >= limit)
                    .sort((a, b) => b.width - a.width)
                    .forEach(b => Object.assign(out, resolveDeclsFor(node, (ruleSet.breakpoints || {})[b.key])));
                return out;
            }

            function roundCss(n) { return String(Math.round(n * 1000) / 1000); }

            function scaleFontSize(value, factor) {
                if (Math.abs(factor - 1) < 1e-9) return value;
                const abs = value.match(/^(-?\d*\.?\d+)(px|rem)$/i);
                if (abs) return roundCss(parseFloat(abs[1]) * factor) + abs[2];
                if (/^[a-z-]+$/i.test(value)) return value;        // keyword: keep as written
                return 'calc(' + value + ' * ' + roundCss(factor) + ')';
            }

            /**
             * The font-size an element actually renders at: its own declaration or the
             * nearest ancestor's, compounding em/% steps on the way up.
             */
            function resolveEffectiveFontSize(el, ruleSet, bpKey) {
                let factor = 1;
                for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
                    const fs = (declsAtBreakpoint(node, ruleSet, bpKey)['font-size'] || '').trim();
                    const lower = fs.toLowerCase();
                    if (!fs || lower === 'inherit' || lower === 'unset') continue;
                    const rel = lower.match(/^(-?\d*\.?\d+)(em|%)$/);
                    if (rel) { factor *= parseFloat(rel[1]) / (rel[2] === '%' ? 100 : 1); continue; }
                    return scaleFontSize(fs, factor);
                }
                return scaleFontSize('16px', factor);   // browser default
            }

            // ================================================================
            // ICON COLOUR + INTERACTION STATES
            // Colour is pinned natively (iconColor), the same way as size: an icon's
            // colour usually comes from a wrapper or a ".badge i" rule, and it did not
            // survive into Bricks. Because iconColor is ID-level, it would also beat
            // ".card:hover .card-icon { color: #fff }" — so those state rules are
            // re-expressed as element custom CSS anchored on %root%.
            // ================================================================

            /** The colour an element really renders in: its own, or the nearest ancestor's. */
            function resolveEffectiveColor(el, ruleSet, bpKey) {
                for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
                    const c = (declsAtBreakpoint(node, ruleSet, bpKey)['color'] || '').trim();
                    if (!c || /^(inherit|unset|currentcolor)$/i.test(c)) continue;
                    if (/^initial$/i.test(c)) return null;
                    return c;
                }
                return null;
            }

            /** A declared width that may be wider than its parent, so max-width:100% would clip it. */
            function canExceedParent(width) {
                const w = String(width).trim().toLowerCase();
                if (/^(auto|inherit|initial|unset)$/.test(w)) return false;
                const pct = w.match(/^(\d*\.?\d+)%$/);
                return !(pct && parseFloat(pct[1]) <= 100);
            }

            // Longer names first, so ":focus" never half-matches ":focus-within".
            const STATE_PSEUDO = /:(hover|focus-within|focus-visible|focus|active|visited|target|checked|disabled)\b/gi;

            /**
             * Every state rule that reaches this icon's colour (or its own font-size),
             * rewritten against %root% so it outranks the pinned base value:
             *   .card:hover .card-icon { color }  →  :is(.card:hover .card-icon) %root% { color; fill }
             *   .solo i:hover { color }           →  %root%:is(.solo i:hover) { color; fill }
             * A rule on an ancestor is skipped when something closer sets its own colour,
             * because in HTML it would never have reached the icon either.
             */
            function iconStateCss(el, ruleSet) {
                const states = (ruleSet && ruleSet.states) || [];
                if (!states.length) return '';
                const chain = [];
                for (let n = el; n && n.nodeType === 1; n = n.parentElement) chain.push(n);
                const baseDecls = chain.map(n => resolveDeclsFor(n, ruleSet.base));
                const rules = [];
                let transition = null;
                states.slice().sort((a, b) => (a.spec - b.spec) || (a.order - b.order)).forEach(r => {
                    const structural = r.selector.replace(STATE_PSEUDO, '').trim() || '*';
                    let depth = -1;
                    for (let i = 0; i < chain.length; i++) {
                        let hit = false;
                        try { hit = chain[i].matches(structural); } catch (e) { hit = false; }
                        if (hit) { depth = i; break; }
                    }
                    if (depth === -1) return;
                    const decls = [];
                    if (r.decls['color'] && !baseDecls.slice(0, depth).some(d => d['color'])) {
                        decls.push('color: ' + r.decls['color'], 'fill: ' + r.decls['color']);
                    }
                    if (r.decls['font-size'] && depth === 0) decls.push('font-size: ' + r.decls['font-size']);
                    if (!decls.length) return;
                    const sel = depth === 0 ? '%root%:is(' + r.selector + ')' : ':is(' + r.selector + ') %root%';
                    rules.push(sel + ' {\n  ' + decls.join(';\n  ') + ';\n}');
                    // In HTML an inherited colour fades with the wrapper's transition; the
                    // icon now switches its own colour, so it needs that transition too.
                    const t = baseDecls[depth]['transition'];
                    if (!transition && t && /color|all/i.test(t)) transition = t.trim();
                });
                if (!rules.length) return '';
                return (transition ? '%root% {\n  transition: ' + transition + ';\n}\n' : '') + rules.join('\n');
            }

            /** Pin a font icon's size, colour and state styling as native settings. */
            function pinIconAppearance(el, settings, ruleSet) {
                const perBreakpoint = (key, resolve, convert) => {
                    const base = resolve(null);
                    if (base) settings[key] = convert(base);
                    let prev = base;
                    BRICKS_BREAKPOINTS.forEach(bp => {   // widest first, like Bricks' own cascade
                        const v = resolve(bp.key);
                        if (v && v !== prev) settings[key + ':' + bp.key] = convert(v);
                        prev = v;
                    });
                };
                if (el.getAttribute('data-icon-size')) {
                    settings.iconSize = el.getAttribute('data-icon-size');
                } else {
                    perBreakpoint('iconSize', bp => resolveEffectiveFontSize(el, ruleSet, bp), v => v);
                }
                perBreakpoint('iconColor', bp => resolveEffectiveColor(el, ruleSet, bp), toBricksColor);
                const stateCss = iconStateCss(el, ruleSet);
                if (stateCss) settings._cssCustom = stateCss;
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
                // Statements are taken out whole BEFORE the block walk and put back first,
                // where @import has to be. Walked in place, an @import hid the :root after
                // it, and a regex pull cut the font URL at its first ";".
                const split = splitStatementAtRules(css.replace(/\/\*[\s\S]*?\*\//g, ''));
                const blocks = extractGlobalBlocks(split.rest);
                return ((split.statements.length ? split.statements.join('\n') + '\n' : '') + blocks).trim();
            }

            /** The non-class blocks of a stylesheet that no longer contains statement at-rules. */
            function extractGlobalBlocks(css) {
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
                    // Same as the main build: line paint moves into divider settings.
                    const lineOnly = findLineOnlyClasses(doc, ruleSet);
                    Object.keys(lineOnly).forEach(cn => {
                        if (classMap[cn]) classMap[cn].css = stripLinePaintFromCss(classMap[cn].css, cn, lineOnly[cn]);
                    });
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
                    const explicitName = el.getAttribute('data-bricks');

                    // Resolve the cascade once, up front — element-type detection needs it.
                    const ownDecls = resolveDeclsFor(el, ruleSet.base);

                    // ── Decorative lines → Bricks divider, judged by what the CSS draws ──
                    let lineInfo = null;
                    if (isLineCandidate(el)) {
                        lineInfo = analyzeLine(ownDecls);
                        if (lineInfo) bricksName = 'divider';
                    }
                    // An <hr> or explicit divider painted with a gradient cannot be expressed
                    // through divider settings — keep its exact CSS on a block instead.
                    if (bricksName === 'divider' && !lineInfo && (resolveBackgroundPaint(ownDecls) || {}).gradient) {
                        bricksName = 'block';
                    }

                    // ── A bare <span class="fas fa-..."> is an icon, not empty text ──
                    if (bricksName === 'text-basic' && (!explicitName || explicitName === 'text-basic')
                        && !el.children.length && !el.textContent.trim()
                        && parseFaIcon(el.getAttribute('class') || '')) {
                        bricksName = 'icon';
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

                    // ── Translate the layout-critical part of the resolved cascade into
                    //    native Bricks settings so it wins against Bricks' own .brxe-*
                    //    defaults (ID beats class).
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
                            if (element.name === 'icon') {
                                // Pin what the icon really renders as. Size and colour usually
                                // come from a wrapper in HTML; Bricks' .brxe-icon{font-size:60px}
                                // cuts that inheritance, and the colour did not survive either.
                                pinIconAppearance(el, element.settings, ruleSet);
                            } else if (el.getAttribute('data-icon-size')) {
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
                            // The line lives in the divider's own settings: height = thickness,
                            // width = length (the two swap for vertical). The root's display
                            // is Bricks' business — it must stay flex for .line to lay out.
                            Object.keys(element.settings).forEach(k => {
                                if (k === '_display' || k.startsWith('_display:')) delete element.settings[k];
                            });
                            const line = lineInfo || analyzeLine(ownDecls);
                            const vertical = !!line && line.direction === 'vertical';
                            if (line) {
                                if (vertical) element.settings.direction = 'vertical';
                                element.settings.height = vertical ? line.length : line.thickness;
                                element.settings.width  = vertical ? line.thickness : line.length;
                                element.settings.style  = line.style;
                                if (line.color) element.settings.color = toBricksColor(line.color);
                            } else {
                                element.settings.style = 'solid';
                            }
                            // Never clobber a width the stylesheet declared — a short decorative
                            // rule is usually 40-80px, not full width.
                            if (!element.settings._width && !vertical) element.settings._width = '100%';
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
