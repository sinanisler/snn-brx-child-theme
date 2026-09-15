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
            // The cascade is resolved once per Bricks breakpoint, at that breakpoint's
            // own viewport width, so min-width (mobile-first) and max-width
            // (desktop-first) media queries both land on the breakpoints they really
            // cover.
            // ================================================================

            // Bricks' default breakpoints (includes/breakpoints.php), used only when the
            // builder does not expose the site's own list through bricksData.
            const DEFAULT_BREAKPOINTS = [
                { key: 'desktop',          width: 1279, base: true },
                { key: 'tablet_portrait',  width: 991 },
                { key: 'mobile_landscape', width: 767 },
                { key: 'mobile_portrait',  width: 478 }
            ];

            // Element names Bricks treats as layout elements (Element::is_layout_element()).
            // These use _direction / _flexWrap / _columnGap; everything else uses
            // _flexDirection / _gap — Bricks defines the two sets in different files.
            const LAYOUT_ELEMENT_NAMES = new Set(['section', 'container', 'block', 'div']);

            // Every setting the cascade resolver owns. Kept in one place so a re-sync can
            // clear exactly these (and their breakpoint variants) and nothing else.
            const NATIVE_LAYOUT_KEYS = [
                '_display', '_direction', '_flexDirection', '_flexWrap', '_alignItems', '_justifyContent',
                '_rowGap', '_columnGap', '_gap', '_gridGap', '_gridTemplateColumns', '_gridTemplateRows',
                '_gridAutoColumns', '_gridAutoRows', '_gridAutoFlow', '_justifyItemsGrid', '_alignItemsGrid',
                '_justifyContentGrid', '_alignContentGrid', '_width', '_widthMax'
            ];

            // HTML tag options of Bricks' layout elements (section.php / container.php).
            // Anything else is written as tag:"custom" + customTag.
            const SECTION_TAG_OPTIONS = ['section', 'header', 'footer', 'article', 'aside', 'div'];
            const BLOCK_TAG_OPTIONS   = ['div', 'section', 'a', 'article', 'nav', 'ol', 'ul', 'li', 'aside', 'address', 'figure'];

            // FontAwesome utility tokens that are NOT the glyph name.
            const FA_MODIFIER = /^fa-(fw|border|inverse|li|ul|pull-left|pull-right|spin|pulse|beat|fade|bounce|shake|flip|flip-horizontal|flip-vertical|flip-both|rotate-(90|180|270|by)|stack|stack-1x|stack-2x|xs|sm|lg|xl|2xl|[0-9]+x|sharp|duotone|solid|regular|brands|light|thin)$/;

            /**
             * The site's breakpoints, arranged the way Bricks' own cascade inherits them.
             *
             * base.width / steps[].width are the viewport widths the CSS cascade is
             * resolved at. Desktop-first: base at its width, then each narrower
             * breakpoint at its max-width. Mobile-first: base just below the first
             * breakpoint, then each wider breakpoint at its min-width.
             */
            function getBreakpointPlan() {
                let list = DEFAULT_BREAKPOINTS;
                try {
                    const live = window.bricksData && window.bricksData.breakpoints;
                    const parsed = (live ? Array.from(Object.values(live)) : [])
                        .map(b => ({ key: b && b.key, width: parseInt(b && b.width, 10), base: !!(b && b.base), paused: !!(b && b.paused) }))
                        .filter(b => b.key && b.width > 0 && !b.paused);
                    if (parsed.length) list = parsed;
                } catch (e) { /* builder data unavailable — defaults */ }

                const base   = list.find(b => b.base) || list.reduce((a, b) => (b.width > a.width ? b : a));
                const others = list.filter(b => b !== base);
                const mobileFirst = others.length > 0 && others.every(b => b.width > base.width);
                others.sort((a, b) => mobileFirst ? a.width - b.width : b.width - a.width);
                const baseWidth = mobileFirst ? Math.min(base.width, others[0].width - 1) : base.width;
                return {
                    mobileFirst: mobileFirst,
                    base:  { key: base.key, width: baseWidth },
                    steps: others.map(b => ({ key: b.key, width: b.width }))
                };
            }

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
             * Parse href → Bricks link object.
             *
             * Bricks renders an "internal" link only from a postId, so "#pricing" and
             * "/contact" stored as internal came out with no href at all. Every written
             * URL is an external link — exactly what Bricks' own HTML converter stores —
             * and a new tab is "newTab", the key Bricks' link control reads.
             */
            function parseLink(el) {
                const href = (el.getAttribute('href') || el.getAttribute('data-href') || '#').trim();
                const link = { type: 'external', url: href };
                if (el.getAttribute('target') === '_blank') link.newTab = true;
                const rel = el.getAttribute('rel');
                if (rel) link.rel = rel;
                return link;
            }

            function escapeRegExp(s) {
                return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            // ================================================================
            // CSS TOKENIZING
            // ================================================================

            /**
             * Split CSS into its top-level parts: { prelude, body } for blocks and
             * { prelude, body: null } for statements such as @import. Quotes are
             * respected, so content:"{" does not unbalance the braces.
             *
             * Walking back from a "{" to the previous "}" (the old approach) glued a
             * leading "@import url(...);" onto the first selector, and that rule was then
             * discarded as an at-rule — its class silently lost all of its CSS.
             */
            function cssTopLevelBlocks(css) {
                const out = [];
                const text = String(css || '');
                let depth = 0, start = 0, braceAt = -1, quote = null;
                for (let i = 0; i < text.length; i++) {
                    const ch = text[i];
                    if (quote) { if (ch === quote && text[i - 1] !== '\\') quote = null; continue; }
                    if (ch === '"' || ch === "'") { quote = ch; continue; }
                    if (ch === '{') { if (depth === 0) braceAt = i; depth++; continue; }
                    if (ch === '}') {
                        depth--;
                        if (depth === 0 && braceAt > -1) {
                            out.push({ prelude: text.slice(start, braceAt).trim(), body: text.slice(braceAt + 1, i) });
                            start = i + 1;
                            braceAt = -1;
                        }
                        if (depth < 0) depth = 0;
                        continue;
                    }
                    if (ch === ';' && depth === 0) {
                        const stmt = text.slice(start, i + 1).trim();
                        if (stmt) out.push({ prelude: stmt, body: null });
                        start = i + 1;
                    }
                }
                return out;
            }

            /** "a, :is(b, c)" → ["a", ":is(b, c)"] — commas inside parentheses or brackets stay put. */
            function splitSelectorList(text) {
                const out = [];
                let depth = 0, buf = '', quote = null;
                for (const ch of String(text || '')) {
                    if (quote) { buf += ch; if (ch === quote) quote = null; continue; }
                    if (ch === '"' || ch === "'") { quote = ch; buf += ch; continue; }
                    if (ch === '(' || ch === '[') depth++;
                    if (ch === ')' || ch === ']') depth--;
                    if (ch === ',' && depth === 0) { if (buf.trim()) out.push(buf.trim()); buf = ''; continue; }
                    buf += ch;
                }
                if (buf.trim()) out.push(buf.trim());
                return out;
            }

            /**
             * Visit every class token in a selector, skipping attribute selectors and
             * quoted strings ([href$=".pdf"] is not a class). The callback may return a
             * replacement name.
             */
            function mapSelectorClasses(selector, fn) {
                const s = String(selector || '');
                let out = '', i = 0, bracket = 0, quote = null;
                while (i < s.length) {
                    const ch = s[i];
                    if (quote) { out += ch; if (ch === quote) quote = null; i++; continue; }
                    if (ch === '"' || ch === "'") { quote = ch; out += ch; i++; continue; }
                    if (ch === '[') bracket++;
                    if (ch === ']') bracket--;
                    if (ch === '.' && bracket === 0) {
                        const m = s.slice(i + 1).match(/^-?[_a-zA-Z][\w-]*/);
                        if (m) {
                            const next = fn(m[0]);
                            out += '.' + (typeof next === 'string' ? next : m[0]);
                            i += 1 + m[0].length;
                            continue;
                        }
                    }
                    out += ch;
                    i++;
                }
                return out;
            }

            /** Rename class selectors everywhere in a stylesheet: nested @media/@supports included, declarations untouched. */
            function renameClassesInCss(css, renames) {
                if (!css || !renames || !Object.keys(renames).length) return css;
                const has = (n) => Object.prototype.hasOwnProperty.call(renames, n);
                return cssTopLevelBlocks(css).map(block => {
                    if (block.body === null) return block.prelude;
                    const prelude = block.prelude;
                    if (/^@(media|supports|container|layer|document)\b/i.test(prelude)) {
                        return prelude + ' {\n' + renameClassesInCss(block.body, renames) + '\n}';
                    }
                    if (prelude.startsWith('@')) return prelude + ' {' + block.body + '}';
                    return mapSelectorClasses(prelude, n => has(n) ? renames[n] : null) + ' {' + block.body + '}';
                }).join('\n');
            }

            /** Every class name a stylesheet's selectors mention. */
            function classNamesInCss(css) {
                const names = new Set();
                const walk = (text) => cssTopLevelBlocks(text).forEach(block => {
                    if (block.body === null) return;
                    if (/^@(media|supports|container|layer|document)\b/i.test(block.prelude)) { walk(block.body); return; }
                    if (block.prelude.startsWith('@')) return;
                    mapSelectorClasses(block.prelude, n => { names.add(n); return null; });
                });
                walk(String(css || '').replace(/\/\*[\s\S]*?\*\//g, ''));
                return names;
            }

            /** Rename class="" tokens (and <style> selectors) inside raw HTML a setting carries. */
            function renameClassAttrsInHtml(html, renames) {
                if (!html || !renames || !Object.keys(renames).length) return html;
                const has = (n) => Object.prototype.hasOwnProperty.call(renames, n);
                return String(html)
                    .replace(/(<style[^>]*>)([\s\S]*?)(<\/style>)/gi, (m, open, css, close) => open + renameClassesInCss(css, renames) + close)
                    .replace(/(\sclass\s*=\s*)(["'])([^"']*)\2/gi, (m, lead, q, value) =>
                        lead + q + value.split(/(\s+)/).map(tok => has(tok) ? renames[tok] : tok).join('') + q);
            }

            /** Apply class renames to everything a compiled element carries as text: raw HTML and element custom CSS. */
            function applyClassRenamesToElements(content, renames) {
                if (!renames || !Object.keys(renames).length) return;
                (content || []).forEach(el => {
                    const st = el.settings || {};
                    ['text', 'content'].forEach(k => {
                        if (typeof st[k] === 'string') st[k] = renameClassAttrsInHtml(st[k], renames);
                    });
                    Object.keys(st).forEach(k => {
                        if ((k === '_cssCustom' || k.indexOf('_cssCustom:') === 0) && typeof st[k] === 'string') {
                            st[k] = renameClassesInCss(st[k], renames);
                        }
                    });
                });
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

            /** Like parseDeclarations, but keeps !important values apart so they can win the cascade. */
            function parseDeclarationsWithPriority(body) {
                const normal = {}, important = {};
                let count = 0;
                splitDeclarations(body || '').forEach(decl => {
                    const at = decl.indexOf(':');
                    if (at === -1) return;
                    const prop = decl.slice(0, at).trim().toLowerCase();
                    const val  = decl.slice(at + 1).trim();
                    if (!prop) return;
                    count++;
                    if (/!\s*important\s*$/i.test(val)) important[prop] = val.replace(/!\s*important\s*$/i, '').trim();
                    else normal[prop] = val;
                });
                return { normal: normal, important: important, count: count };
            }

            /** The class a rule is filed under: the first class in the selector (attribute selectors ignored). */
            function firstClassInSelector(sel) {
                let first = null;
                mapSelectorClasses(sel, n => { if (!first) first = n; return null; });
                return first;
            }

            function parseCSSRules(css) {
                const classes = {};
                const keyframes = [];
                css = String(css || '').replace(/\/\*[\s\S]*?\*\//g, '');

                // Rules are filed under their first class in source order; a rule inside
                // @media keeps its query wrapper, so every class's CSS stays self-contained.
                const walk = (text, mediaStack) => {
                    cssTopLevelBlocks(text).forEach(block => {
                        if (block.body === null) return;                          // @import, @charset ...
                        const prelude = block.prelude;
                        if (/^@media/i.test(prelude)) { walk(block.body, mediaStack.concat(prelude)); return; }
                        if (/^@(-webkit-)?keyframes/i.test(prelude)) { if (!mediaStack.length) keyframes.push(block); return; }
                        // @supports, @layer, @font-face ... are not class rules — extractGlobalCSS keeps them.
                        if (prelude.startsWith('@')) return;
                        splitSelectorList(prelude).forEach(sel => {
                            const className = firstClassInSelector(sel);
                            if (!className) return;
                            let rule = sel + ' {\n' + formatCSSBody(block.body) + '\n}';
                            for (let i = mediaStack.length - 1; i >= 0; i--) rule = mediaStack[i] + ' {\n' + rule + '\n}';
                            classes[className] = (classes[className] || '') + rule + '\n\n';
                        });
                    });
                };
                walk(css, []);

                // @keyframes travel with every class whose animation actually names them.
                // Matching on a bare substring used to attach "fade" to whichever class
                // happened to contain the text first — often one no element carries.
                keyframes.forEach(block => {
                    const nameMatch = block.prelude.match(/keyframes\s+([\w-]+)/i);
                    if (!nameMatch) return;
                    const uses = new RegExp('animation(?:-name)?\\s*:[^;{}]*?(?:^|[\\s,:])' + escapeRegExp(nameMatch[1]) + '(?![\\w-])', 'i');
                    const text = block.prelude + ' {' + block.body + '}\n\n';
                    Object.keys(classes).forEach(name => { if (uses.test(classes[name])) classes[name] += text; });
                });

                for (const name of Object.keys(classes)) {
                    classes[name] = classes[name].replace(/\n{3,}/g, '\n\n').trim();
                }
                return classes;
            }

            // ================================================================
            // MEDIA QUERIES
            // A query is evaluated against a concrete viewport width — the width of the
            // Bricks breakpoint being resolved — instead of being snapped onto the
            // nearest breakpoint key.
            // ================================================================

            const mediaMatchCache = new Map();

            function mediaLengthPx(num, unit) {
                const n = parseFloat(num);
                return (unit === 'em' || unit === 'rem') ? n * 16 : n;
            }

            function compareWidth(width, op, px) {
                switch (op) {
                    case '<':  return width < px;
                    case '<=': return width <= px;
                    case '>':  return width > px;
                    case '>=': return width >= px;
                    default:   return width === px;
                }
            }

            function flipOp(op) {
                return { '<': '>', '<=': '>=', '>': '<', '>=': '<=', '=': '=' }[op];
            }

            /** Split on a keyword (and / or) outside parentheses. */
            function splitTopLevelWord(str, re) {
                const parts = [];
                let depth = 0, start = 0;
                for (let i = 0; i < str.length; i++) {
                    const ch = str[i];
                    if (ch === '(') depth++;
                    else if (ch === ')') depth--;
                    else if (depth === 0 && /\s/.test(ch)) {
                        const m = str.slice(i).match(re);
                        if (m) { parts.push(str.slice(start, i)); i += m[0].length - 1; start = i + 1; }
                    }
                }
                parts.push(str.slice(start));
                return parts.map(s => s.trim()).filter(Boolean);
            }

            /** "(a) and (b)" is not wrapped; "((a) and (b))" is. */
            function isWrappedInParens(str) {
                if (!str.startsWith('(') || !str.endsWith(')')) return false;
                let depth = 0;
                for (let i = 0; i < str.length; i++) {
                    if (str[i] === '(') depth++;
                    else if (str[i] === ')') { depth--; if (depth === 0 && i < str.length - 1) return false; }
                }
                return true;
            }

            function mediaFeatureMatches(feature, width) {
                const f = feature.replace(/\s+/g, ' ').trim();
                let m = f.match(/^(min|max)-width\s*:\s*(-?\d*\.?\d+)(px|em|rem)?$/);
                if (m) {
                    const px = mediaLengthPx(m[2], m[3]);
                    return m[1] === 'min' ? width >= px : width <= px;
                }
                m = f.match(/^(-?\d*\.?\d+)(px|em|rem)?\s*(<=|<)\s*width\s*(<=|<)\s*(-?\d*\.?\d+)(px|em|rem)?$/);
                if (m) {
                    return compareWidth(width, flipOp(m[3]), mediaLengthPx(m[1], m[2]))
                        && compareWidth(width, m[4], mediaLengthPx(m[5], m[6]));
                }
                m = f.match(/^width\s*(<=|>=|<|>|=)\s*(-?\d*\.?\d+)(px|em|rem)?$/);
                if (m) return compareWidth(width, m[1], mediaLengthPx(m[2], m[3]));
                m = f.match(/^(-?\d*\.?\d+)(px|em|rem)?\s*(<=|>=|<|>|=)\s*width$/);
                if (m) return compareWidth(width, flipOp(m[3]), mediaLengthPx(m[1], m[2]));
                // orientation, hover, prefers-*, height, resolution ... cannot be decided from a width.
                return false;
            }

            function mediaConditionMatches(cond, width) {
                let c = cond.trim();
                if (c === 'all' || c === 'screen') return true;
                if (/^(print|speech|tty|tv|projection|handheld|braille|embossed|aural)$/.test(c)) return false;
                while (isWrappedInParens(c)) c = c.slice(1, -1).trim();
                if (/^not\s+/.test(c)) return !mediaConditionMatches(c.replace(/^not\s+/, ''), width);
                const ands = splitTopLevelWord(c, /^\s+and\s+/);
                if (ands.length > 1) return ands.every(x => mediaConditionMatches(x, width));
                const ors = splitTopLevelWord(c, /^\s+or\s+/);
                if (ors.length > 1) return ors.some(x => mediaConditionMatches(x, width));
                return mediaFeatureMatches(c, width);
            }

            /** Does "@media <query>" apply at this viewport width? */
            function mediaMatchesWidth(query, width) {
                const key = query + '|' + width;
                if (mediaMatchCache.has(key)) return mediaMatchCache.get(key);
                const q = String(query || '').replace(/^@media\s*/i, '').trim().toLowerCase();
                const result = !q || splitSelectorList(q).some(part => {
                    let p = part.trim(), negate = false;
                    if (/^not\s+/.test(p)) { negate = true; p = p.replace(/^not\s+/, ''); }
                    p = p.replace(/^only\s+/, '');
                    const ok = mediaConditionMatches(p, width);
                    return negate ? !ok : ok;
                });
                mediaMatchCache.set(key, result);
                return result;
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

            /** Selectors carrying interaction/state pseudos must not drive base layout settings. */
            const STATE_SELECTOR = /::|:(hover|focus|focus-within|focus-visible|active|visited|target|checked|disabled|placeholder)\b/i;

            // Longer names first, so ":focus" never half-matches ":focus-within".
            const STATE_PSEUDO = /:(hover|focus-within|focus-visible|focus|active|visited|target|checked|disabled)\b/gi;
            const PSEUDO_ELEMENT = /::?(before|after|first-letter|first-line|placeholder|marker|selection)\b/gi;

            /**
             * Flatten a stylesheet into rules:
             *   rules  — structural rules that drive native settings
             *   states — :hover/:focus/... rules, kept for icon state styling
             *   all    — every rule, pseudo-elements included, for selector rewriting
             * Each rule keeps its @media queries (all must match) and its raw body.
             */
            function buildCssRuleSet(css) {
                const ruleSet = { rules: [], states: [], all: [], matchCache: new WeakMap() };
                let order = 0;
                const walk = (text, media) => {
                    cssTopLevelBlocks(text).forEach(block => {
                        if (block.body === null) return;
                        const prelude = block.prelude;
                        if (/^@media/i.test(prelude)) {
                            walk(block.body, media.concat(prelude.replace(/^@media\s*/i, '').trim()));
                            return;
                        }
                        if (prelude.startsWith('@')) return;
                        const decls = parseDeclarationsWithPriority(block.body);
                        if (!decls.count) return;
                        splitSelectorList(prelude).forEach(sel => {
                            const rule = {
                                selector: sel, decls: decls.normal, important: decls.important,
                                raw: block.body, spec: computeSpecificity(sel), order: order++,
                                media: media.length ? media : null
                            };
                            ruleSet.all.push(rule);
                            if (STATE_SELECTOR.test(sel)) {
                                if (!sel.includes('::')) ruleSet.states.push(rule);
                                return;
                            }
                            ruleSet.rules.push(rule);
                        });
                    });
                };
                walk(String(css || '').replace(/\/\*[\s\S]*?\*\//g, ''), []);
                return ruleSet;
            }

            function ruleAppliesAt(rule, width) {
                return !rule.media || rule.media.every(q => mediaMatchesWidth(q, width));
            }

            /** Structural rules matching an element, sorted by cascade order. Width-independent, so cached per element. */
            function matchedRulesFor(el, ruleSet) {
                if (!ruleSet || !el || el.nodeType !== 1) return [];
                if (!ruleSet.matchCache) ruleSet.matchCache = new WeakMap();
                let hit = ruleSet.matchCache.get(el);
                if (!hit) {
                    hit = (ruleSet.rules || []).filter(r => {
                        try { return el.matches(r.selector); } catch (e) { return false; }
                    });
                    hit.sort((a, b) => (a.spec - b.spec) || (a.order - b.order));
                    ruleSet.matchCache.set(el, hit);
                }
                return hit;
            }

            /** The declarations that apply to one element at one viewport width. */
            function resolveDeclsAt(el, ruleSet, width) {
                const out = {}, important = {};
                matchedRulesFor(el, ruleSet).forEach(r => {
                    if (!ruleAppliesAt(r, width)) return;
                    Object.assign(out, r.decls);
                    Object.assign(important, r.important);
                });
                // A stray inline style="" wins over any stylesheet rule.
                const inline = el && el.getAttribute ? el.getAttribute('style') : null;
                if (inline) Object.assign(out, parseDeclarations(inline));
                return Object.assign(out, important);
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

            /**
             * Resolve an element's layout at every Bricks breakpoint and store it the way
             * Bricks does: base values unsuffixed, then only what changes, suffixed with
             * the breakpoint key, in the order Bricks' cascade inherits values.
             *
             * A property the CSS stops declaring at a breakpoint is reset to
             * revert-layer: that hands it back to Bricks' layered element defaults, which
             * is exactly where the preview gets it from when no class rule applies.
             */
            function computeNativeLayout(el, bricksName, ctx) {
                const plan   = ctx.plan;
                const parent = el.parentElement && el.parentElement.tagName.toLowerCase() !== 'body' ? el.parentElement : null;
                const at = (width) => deriveNativeSettings(
                    resolveDeclsAt(el, ctx.ruleSet, width),
                    bricksName,
                    parent ? resolveDeclsAt(parent, ctx.ruleSet, width) : null
                );

                const out = at(plan.base.width);
                const inherited = Object.assign({}, out);
                plan.steps.forEach(step => {
                    const next = at(step.width);
                    new Set([...Object.keys(inherited), ...Object.keys(next)]).forEach(k => {
                        const value = Object.prototype.hasOwnProperty.call(next, k) ? next[k] : 'revert-layer';
                        if (value === inherited[k]) return;
                        if (value === 'revert-layer' && inherited[k] === undefined) return;
                        out[k + ':' + step.key] = value;
                        inherited[k] = value;
                    });
                });
                return out;
            }

            /** Remove every setting the cascade resolver owns, including breakpoint variants. */
            function clearNativeLayout(settings) {
                Object.keys(settings || {}).forEach(k => {
                    if (NATIVE_LAYOUT_KEYS.includes(k.split(':')[0])) delete settings[k];
                });
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
                const baseWidth = getBreakpointPlan().base.width;
                doc.body.querySelectorAll('*').forEach(el => {
                    const classes = (el.getAttribute('class') || '').split(/\s+/).filter(c => c && !isFaToken(c));
                    if (!classes.length) return;
                    const line = isLineCandidate(el) ? analyzeLine(resolveDeclsAt(el, ruleSet, baseWidth)) : null;
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

            /** A declared colour that really paints — not inherit/currentColor/none. */
            function explicitPaint(value) {
                const v = String(value || '').trim();
                if (!v || /^(inherit|initial|unset|revert|currentcolor|none|transparent)$/i.test(v)) return null;
                return v;
            }

            /** A declared length/size worth pinning — not inherit/auto. */
            function explicitLength(value) {
                const v = String(value || '').trim();
                if (!v || /^(inherit|initial|unset|revert|auto)$/i.test(v)) return null;
                return v;
            }

            /**
             * Write a per-breakpoint setting: base unsuffixed, then "key:breakpoint"
             * wherever the resolved value changes along Bricks' cascade.
             */
            function perBreakpointSetting(settings, key, ctx, resolve, convert) {
                const plan = ctx.plan;
                const base = resolve(plan.base.width);
                if (base) settings[key] = convert(base);
                let prev = base;
                plan.steps.forEach(step => {
                    const v = resolve(step.width);
                    if (v && v !== prev) settings[key + ':' + step.key] = convert(v);
                    if (v) prev = v;
                });
            }

            // ================================================================
            // ICON SIZE
            // Bricks ships .brxe-icon{font-size:60px}. The usual markup is
            //   <div class="feature-icon"><i class="fas fa-leaf"></i></div>
            // with the size on the WRAPPER. In HTML the <i> inherits it; in Bricks
            // the direct 60px rule cuts inheritance, so every such icon came out at
            // 60px. Resolve the size the icon really renders at and pin it as iconSize.
            // ================================================================

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
            function resolveEffectiveFontSize(el, ruleSet, width) {
                let factor = 1;
                for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
                    const fs = (resolveDeclsAt(node, ruleSet, width)['font-size'] || '').trim();
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
            function resolveEffectiveColor(el, ruleSet, width) {
                for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
                    const c = (resolveDeclsAt(node, ruleSet, width)['color'] || '').trim();
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

            /**
             * Every state rule that reaches an icon's colour (or its own font-size),
             * rewritten against %root% so it outranks the pinned base value.
             *
             * Standalone icon element (%root% is the icon):
             *   .card:hover .card-icon { color }  →  :is(.card:hover .card-icon) %root% { color; fill }
             *   .solo i:hover { color }           →  %root%:is(.solo i:hover) { color; fill }
             * Icon inside a button / text link (%root% is the holder, the icon a child):
             *   .btn:hover i { color }            →  %root% i:is(.btn:hover i) { ... }
             *   .btn:hover { color }              →  %root%:is(.btn:hover) i { ... }
             * A rule on an ancestor is skipped when something closer sets its own colour,
             * because in HTML it would never have reached the icon either.
             */
            function iconStateCss(node, ctx, holder = null, nodeTarget = '', colorTarget = '') {
                const states = (ctx.ruleSet && ctx.ruleSet.states) || [];
                if (!states.length) return '';
                const baseWidth = ctx.plan.base.width;
                const chain = [];
                for (let n = node; n && n.nodeType === 1; n = n.parentElement) chain.push(n);
                const holderDepth = holder ? chain.indexOf(holder) : 0;
                const baseDecls = chain.map(n => resolveDeclsAt(n, ctx.ruleSet, baseWidth));
                const rules = [];
                let transition = null;

                states
                    .filter(r => ruleAppliesAt(r, baseWidth))
                    .sort((a, b) => (a.spec - b.spec) || (a.order - b.order))
                    .forEach(r => {
                        const structural = r.selector.replace(STATE_PSEUDO, '').trim() || '*';
                        let depth = -1;
                        for (let i = 0; i < chain.length; i++) {
                            let hit = false;
                            try { hit = chain[i].matches(structural); } catch (e) { hit = false; }
                            if (hit) { depth = i; break; }
                        }
                        if (depth === -1) return;
                        // A wrapper removed together with the icon: its class rules are rewritten separately.
                        if (holder && depth > 0 && depth < holderDepth) return;

                        const rd = Object.assign({}, r.decls, r.important);
                        const decls = [];
                        if (rd['color'] && !baseDecls.slice(0, depth).some(d => d['color'])) {
                            decls.push('color: ' + rd['color'], 'fill: ' + rd['color']);
                        }
                        if (rd['font-size'] && depth === 0) decls.push('font-size: ' + rd['font-size']);
                        if (!decls.length) return;

                        let sel;
                        if (!holder) {
                            sel = depth === 0 ? '%root%:is(' + r.selector + ')' : ':is(' + r.selector + ') %root%';
                        } else if (depth === 0) {
                            sel = '%root% ' + nodeTarget + ':is(' + r.selector + ')';
                        } else if (depth === holderDepth) {
                            sel = '%root%:is(' + r.selector + ') ' + colorTarget;
                        } else {
                            sel = ':is(' + r.selector + ') %root% ' + colorTarget;
                        }
                        rules.push(sel + ' {\n  ' + decls.join(';\n  ') + ';\n}');
                        // In HTML an inherited colour fades with the wrapper's transition; the
                        // icon now switches its own colour, so it needs that transition too.
                        const t = baseDecls[depth]['transition'];
                        if (!transition && t && /color|all/i.test(t)) transition = t.trim();
                    });
                if (!rules.length) return '';
                const self = holder ? '%root% ' + colorTarget : '%root%';
                return (transition ? self + ' {\n  transition: ' + transition + ';\n}\n' : '') + rules.join('\n');
            }

            /** Pin a font icon's size, colour and state styling as native settings. */
            function pinIconAppearance(el, settings, ctx) {
                if (el.getAttribute('data-icon-size')) {
                    settings.iconSize = el.getAttribute('data-icon-size');
                } else {
                    perBreakpointSetting(settings, 'iconSize', ctx, w => resolveEffectiveFontSize(el, ctx.ruleSet, w), v => v);
                }
                perBreakpointSetting(settings, 'iconColor', ctx, w => resolveEffectiveColor(el, ctx.ruleSet, w), toBricksColor);
                const stateCss = iconStateCss(el, ctx);
                if (stateCss) settings._cssCustom = stateCss;
            }

            // ================================================================
            // SVG → MEDIA LIBRARY
            // Bricks renders SVG only from an attachment: the svg element's "file"
            // source and an icon control's {library:"svg"} both need an ID, and the
            // "code" source needs a server signature. So the chat uploads every liftable
            // inline <svg> before the build (Bricks' own SVG sanitizer runs on the way
            // in) and hands the compiler a map of markup → attachment.
            // ================================================================

            const svgKeyCache = new WeakMap();

            /** Stable identity of an inline SVG: its XML serialization, minus the builder hint. */
            function svgKey(svg) {
                if (svgKeyCache.has(svg)) return svgKeyCache.get(svg);
                const clone = svg.cloneNode(true);
                clone.removeAttribute('data-bricks');
                const key = new XMLSerializer().serializeToString(clone);
                svgKeyCache.set(svg, key);
                return key;
            }

            function svgAssetFor(svg, ctx) {
                if (!svg || !ctx || !(ctx.svgAssets instanceof Map)) return null;
                return ctx.svgAssets.get(svgKey(svg)) || null;
            }

            /** <use href="#x"> pointing outside the SVG (a page sprite) breaks once the SVG is a file. */
            function svgIsSelfContained(svg) {
                for (const use of svg.querySelectorAll('use')) {
                    const ref = use.getAttribute('href') || use.getAttribute('xlink:href') || '';
                    if (!ref.startsWith('#')) return false;
                    let target = null;
                    try { target = svg.querySelector('[id="' + ref.slice(1).replace(/"/g, '\\"') + '"]'); } catch (e) { target = null; }
                    if (!target) return false;
                }
                return true;
            }

            /** An element whose only content is one <svg> (a wrapper the AI put around it). */
            function soleSvgChild(el) {
                if (!el || el.children.length !== 1) return null;
                const child = el.children[0];
                if (child.tagName.toLowerCase() !== 'svg') return null;
                const clean = Array.from(el.childNodes).every(n => n === child || n.nodeType === 8 || (n.nodeType === 3 && !n.textContent.trim()));
                return clean ? child : null;
            }

            /**
             * The inline SVGs a build can turn into Bricks elements or icon settings:
             * standalone SVGs in layout, and SVGs inside a button or text link. SVGs
             * inside a heading, rich text or custom HTML stay raw markup.
             */
            function svgUploadCandidates(root) {
                const out = [];
                root.querySelectorAll('svg').forEach(svg => {
                    if (svg.parentElement && svg.parentElement.closest('svg')) return;   // nested
                    if (!svgIsSelfContained(svg)) return;
                    let child = svg, node = svg.parentElement, holder = null;
                    while (node && node.tagName && node.tagName.toLowerCase() !== 'body') {
                        const name = bricksNameFor(node);
                        const transparentWrapper = child === svg && soleSvgChild(node) === svg
                            && (name === 'custom-html-css-script' || name === 'text-basic' || name === 'icon');
                        if (LAYOUT_ELEMENT_NAMES.has(name) || transparentWrapper) { child = node; node = node.parentElement; continue; }
                        if ((name === 'button' || name === 'text-link') && !holder) { holder = node; child = node; node = node.parentElement; continue; }
                        if (holder && (name === 'text-basic' || name === 'icon')) { child = node; node = node.parentElement; continue; }   // span wrapper inside the button
                        return;
                    }
                    out.push(svg);
                });
                return out;
            }

            // ================================================================
            // ICONS IN BUTTONS AND TEXT LINKS
            // Both elements have their own icon control. What Bricks stores:
            //   button    — icon, iconPosition, iconGap, iconTypography {font-size, color}
            //   text-link — icon, iconPosition, gap,     iconSize, iconColor
            //   svg icon  — icon {library:"svg", svg:{id,filename,url}, width, height, fill, stroke}
            // Anything left inside "text" as markup shows up as raw tags in the builder
            // and loses the icon's styling, so the icon is lifted into those settings.
            // ================================================================

            const ICON_WRAPPER_TAGS = new Set(['span', 'i', 'div', 'em', 'b', 'strong', 'small']);

            /** Climb single-child wrappers (<span class="btn-icon"><i ...></i></span>) so they leave with the icon. */
            function iconRemovalNode(node, root) {
                let cur = node;
                while (cur.parentElement && cur.parentElement !== root) {
                    const p = cur.parentElement;
                    const hint = p.getAttribute('data-bricks');
                    if (p.children.length !== 1) break;
                    if (p.textContent.trim() !== cur.textContent.trim()) break;
                    if (!ICON_WRAPPER_TAGS.has(p.tagName.toLowerCase()) && hint !== 'custom-html-css-script' && hint !== 'icon') break;
                    cur = p;
                }
                return cur;
            }

            /** Visible text before and after a node inside root (text inside SVGs ignored). */
            function textAround(root, node) {
                let before = '', after = '';
                const walker = root.ownerDocument.createTreeWalker(root, NodeFilter.SHOW_TEXT);
                let t;
                while ((t = walker.nextNode())) {
                    if (node.contains(t)) continue;
                    if (t.parentElement && t.parentElement.closest('svg')) continue;
                    if (node.compareDocumentPosition(t) & Node.DOCUMENT_POSITION_PRECEDING) before += t.textContent;
                    else after += t.textContent;
                }
                return { before: before, after: after };
            }

            /**
             * The icon a button or text link should carry natively: a data-icon
             * attribute, a FontAwesome <i>/<span>, or an uploaded inline <svg>. One at
             * the start or end of the label is preferred; Bricks holds a single icon.
             */
            function findControlIcon(el, ctx) {
                const dataIcon = el.getAttribute('data-icon');
                if (dataIcon) {
                    const obj = parseFaIcon(dataIcon);
                    if (obj) return { kind: 'font', icon: obj, node: null, removal: null, before: '', after: el.textContent, position: el.getAttribute('data-icon-position') || 'left' };
                }
                const candidates = [];
                el.querySelectorAll('i, span, svg').forEach(node => {
                    if (node.parentElement && node.parentElement.closest('svg')) return;
                    if (node.tagName.toLowerCase() === 'svg') {
                        const asset = svgAssetFor(node, ctx);
                        if (asset) candidates.push({ kind: 'svg', node: node, asset: asset });
                        return;
                    }
                    const obj = parseFaIcon(node.getAttribute('class') || '');
                    if (obj && !node.textContent.trim() && !node.children.length) candidates.push({ kind: 'font', node: node, icon: obj });
                });
                candidates.forEach(c => {
                    c.removal = iconRemovalNode(c.node, el);
                    const around = textAround(el, c.removal);
                    c.before = around.before;
                    c.after  = around.after;
                });
                const pick = candidates.find(c => !c.before.trim() || !c.after.trim()) || candidates[0];
                if (!pick) return null;
                pick.position = el.getAttribute('data-icon-position') || (pick.before.trim() ? 'right' : 'left');
                return pick;
            }

            /** innerHTML of an element with one descendant removed, whitespace collapsed. */
            function innerHtmlWithout(el, node) {
                const clone = el.cloneNode(true);
                if (node) {
                    const originals = Array.from(el.querySelectorAll('*'));
                    const clones    = Array.from(clone.querySelectorAll('*'));
                    const at        = originals.indexOf(node);
                    if (at >= 0 && clones[at]) clones[at].remove();
                }
                return clone.innerHTML.replace(/\s+/g, ' ').trim();
            }

            /** Space between label and icon, as the HTML laid it out. */
            function iconGapFromCss(decls, found) {
                const display = (decls['display'] || '').toLowerCase();
                if (/flex|grid/.test(display)) {
                    let col = decls['column-gap'];
                    if (!col && decls['gap']) {
                        const parts = splitCssTokens(decls['gap']);
                        col = parts[1] || parts[0];
                    }
                    return col ? col.trim() : '0';
                }
                // Inline flow: the icon was spaced only by the whitespace next to it.
                const spaced = found.position === 'right' ? /\s$/.test(found.before) : /^\s/.test(found.after);
                return spaced ? '0.25em' : '0';
            }

            /** Top-level combinator split: ".card:hover > .btn-arrow" → { prefix: ".card:hover", last: ".btn-arrow" }. */
            function splitLastCompound(selector) {
                const sel = String(selector || '').trim();
                let depth = 0, quote = null, cut = -1;
                for (let i = 0; i < sel.length; i++) {
                    const ch = sel[i];
                    if (quote) { if (ch === quote) quote = null; continue; }
                    if (ch === '"' || ch === "'") { quote = ch; continue; }
                    if (ch === '(' || ch === '[') depth++;
                    else if (ch === ')' || ch === ']') depth--;
                    else if (depth === 0 && (ch === ' ' || ch === '>' || ch === '+' || ch === '~')) cut = i;
                }
                if (cut === -1) return { prefix: '', last: sel };
                return { prefix: sel.slice(0, cut).replace(/[\s>+~]+$/, '').trim(), last: sel.slice(cut + 1).trim() };
            }

            /**
             * Class rules that styled the icon node or its removed wrapper
             * (".btn-arrow { transition }", ".btn:hover .btn-arrow { transform }").
             * The rendered Bricks icon does not carry those classes, so each rule is
             * re-anchored on %root% and the icon selector, @media wrappers kept.
             */
            function rewriteIconClassRules(holder, found, ctx, iconSelector) {
                if (!found.node) return '';
                const tokens = new Set();
                const chain  = [];
                const collect = (node) => (node.getAttribute('class') || '').split(/\s+/).forEach(c => { if (c && !isFaToken(c)) tokens.add(c); });
                // An uploaded SVG keeps its own classes: they are part of the file's markup.
                if (found.kind === 'font') { collect(found.node); chain.push(found.node); }
                for (let n = found.node.parentElement; n && n !== holder && found.removal && found.removal.contains(n); n = n.parentElement) {
                    collect(n);
                    chain.push(n);
                }
                if (!tokens.size) return '';

                const out = [];
                (ctx.ruleSet.all || []).slice().sort((a, b) => a.order - b.order).forEach(r => {
                    const parts = splitLastCompound(r.selector);
                    let mentions = false;
                    mapSelectorClasses(parts.last, n => { if (tokens.has(n)) mentions = true; return null; });
                    if (!mentions) return;
                    const structural = r.selector.replace(STATE_PSEUDO, '').replace(PSEUDO_ELEMENT, '').trim() || '*';
                    const reaches = chain.some(n => { try { return n.matches(structural); } catch (e) { return false; } });
                    if (!reaches) return;

                    const pseudo = (parts.last.match(/::?[\w-]+(\([^)]*\))?/g) || []).join('');
                    let selector;
                    if (!parts.prefix) {
                        selector = '%root% ' + iconSelector + pseudo;
                    } else {
                        const prefixStructural = parts.prefix.replace(STATE_PSEUDO, '').trim() || '*';
                        let onRoot = false;
                        try { onRoot = holder.matches(prefixStructural); } catch (e) { onRoot = false; }
                        selector = onRoot
                            ? '%root%:is(' + parts.prefix + ') ' + iconSelector + pseudo
                            : ':is(' + parts.prefix + ') %root% ' + iconSelector + pseudo;
                    }
                    let rule = selector + ' {\n' + formatCSSBody(r.raw) + '\n}';
                    (r.media || []).slice().reverse().forEach(q => { rule = '@media ' + q + ' {\n' + rule + '\n}'; });
                    out.push(rule);
                });
                return out.join('\n');
            }

            /** Write a button's iconTypography per breakpoint: font-size and colour the icon itself declares. */
            function iconTypographyPerBreakpoint(settings, ctx, size, color) {
                const plan = ctx.plan;
                const baseSize = size(plan.base.width), baseColor = color(plan.base.width);
                const base = {};
                if (baseSize)  base['font-size'] = baseSize;
                if (baseColor) base.color = toBricksColor(baseColor);
                if (Object.keys(base).length) settings.iconTypography = base;
                let prevSize = baseSize, prevColor = baseColor;
                plan.steps.forEach(step => {
                    const fs = size(step.width), c = color(step.width);
                    const t = {};
                    if (fs && fs !== prevSize) t['font-size'] = fs;
                    if (c && c !== prevColor)  t.color = toBricksColor(c);
                    if (Object.keys(t).length) settings['iconTypography:' + step.key] = t;
                    if (fs) prevSize = fs;
                    if (c) prevColor = c;
                });
            }

            /** Fill a button / text link's native icon settings from the lifted icon. */
            function applyControlIcon(element, el, found, ownDecls, ctx) {
                const s        = element.settings;
                const isButton = element.name === 'button';
                const tagSel   = found.kind === 'svg' ? 'svg' : 'i';
                const nodeSel  = isButton ? tagSel : '.icon > ' + tagSel;

                if (found.kind === 'svg') {
                    s.icon = { library: 'svg', svg: { id: found.asset.id, filename: found.asset.filename, url: found.asset.url } };
                    const d = resolveDeclsAt(found.node, ctx.ruleSet, ctx.plan.base.width);
                    const w = explicitLength(d['width'])  || explicitLength(found.node.getAttribute('width'));
                    const h = explicitLength(d['height']) || explicitLength(found.node.getAttribute('height'));
                    if (w) s.icon.width  = w;
                    if (h) s.icon.height = h;
                    const fill   = explicitPaint(d['fill']);
                    const stroke = explicitPaint(d['stroke']);
                    if (fill)   s.icon.fill   = toBricksColor(fill);
                    if (stroke) s.icon.stroke = toBricksColor(stroke);
                } else {
                    s.icon = found.icon;
                    if (found.node) {
                        // Only what the icon declares itself: an inherited size or colour keeps
                        // inheriting in Bricks too, and pinning it would freeze hover colours.
                        const own   = (w) => resolveDeclsAt(found.node, ctx.ruleSet, w);
                        const size  = (w) => explicitLength(own(w)['font-size']);
                        const color = (w) => explicitPaint(own(w)['color']);
                        if (isButton) {
                            iconTypographyPerBreakpoint(s, ctx, size, color);
                        } else {
                            perBreakpointSetting(s, 'iconSize', ctx, size, v => v);
                            perBreakpointSetting(s, 'iconColor', ctx, color, toBricksColor);
                        }
                        if (color(ctx.plan.base.width)) {
                            const stateCss = iconStateCss(found.node, ctx, el, nodeSel, isButton ? 'i' : '.icon');
                            if (stateCss) s._cssCustom = (s._cssCustom ? s._cssCustom + '\n' : '') + stateCss;
                        }
                    }
                }
                s.iconPosition = found.position;

                const gapKey = isButton ? 'iconGap' : 'gap';
                s[gapKey] = el.getAttribute('data-icon-gap') || iconGapFromCss(ownDecls, found);

                const classCss = rewriteIconClassRules(el, found, ctx, nodeSel);
                if (classCss) s._cssCustom = (s._cssCustom ? s._cssCustom + '\n' : '') + classCss;
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
             * Register every class that appears on an element but has no CSS rule of
             * its own. Without this the compiler dropped such names entirely (classNameToId
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

            // Tag → Bricks element type fallback. Anything not listed here falls through
            // to 'block', which is how forms, tables, videos and inputs used to come out
            // as empty nested boxes.
            const TAG_TO_ELEMENT = {
                'section': 'section', 'header': 'section', 'footer': 'section',
                'nav': 'block', 'article': 'block', 'aside': 'block', 'main': 'block',
                'div': 'block', 'figure': 'block', 'li': 'block',
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
                // Rendered verbatim so nothing is lost in translation. Inline <svg> becomes
                // Bricks' native SVG element once it has been uploaded (see svgAssets).
                'svg': 'custom-html-css-script', 'canvas': 'custom-html-css-script',
                'iframe': 'custom-html-css-script', 'video': 'custom-html-css-script',
                'audio': 'custom-html-css-script', 'picture': 'custom-html-css-script',
                'table': 'custom-html-css-script', 'form': 'custom-html-css-script',
                'details': 'custom-html-css-script', 'input': 'custom-html-css-script',
                'textarea': 'custom-html-css-script', 'select': 'custom-html-css-script',
                'object': 'custom-html-css-script', 'embed': 'custom-html-css-script',
            };

            // Inline content an <li> can hold and still be plain list text.
            const INLINE_LIST_TAGS = new Set(['a', 'abbr', 'b', 'br', 'code', 'em', 'i', 'kbd', 'mark', 's', 'small', 'span', 'strong', 'sub', 'sup', 'svg', 'time', 'u', 'wbr']);

            /** A list whose items hold layout (cards, nested blocks) rather than inline text. */
            function isStructuralList(el) {
                if (el.querySelector('[data-bricks]')) return true;
                return Array.from(el.children).some(li =>
                    Array.from(li.children).some(c => !INLINE_LIST_TAGS.has(c.tagName.toLowerCase())));
            }

            /** The Bricks element an HTML node compiles to. */
            function bricksNameFor(el) {
                const explicit = el.getAttribute('data-bricks');
                if (explicit) return explicit;
                const tag = el.tagName.toLowerCase();
                if ((tag === 'ul' || tag === 'ol') && isStructuralList(el)) return 'block';
                return TAG_TO_ELEMENT[tag] || 'block';
            }

            /** Keep a layout element's real HTML tag — <ul>, <li>, <a>, <header> — so tag selectors and semantics survive. */
            function applyLayoutTag(element, el, bricksName, tag) {
                const isSection = bricksName === 'section';
                const fallback  = isSection ? 'section' : 'div';
                if (tag === fallback) return;
                if ((isSection ? SECTION_TAG_OPTIONS : BLOCK_TAG_OPTIONS).includes(tag)) {
                    element.settings.tag = tag;
                } else {
                    element.settings.tag = 'custom';
                    element.settings.customTag = tag;
                }
                // A linked card: Bricks puts the link on a layout element whose tag is "a".
                if (tag === 'a' && (el.getAttribute('href') || el.getAttribute('data-href'))) {
                    element.settings.link = parseLink(el);
                }
            }

            /**
             * @param {string} html
             * @param {object|null} preComputedClassNameToId  className → global class id
             * @param {object|null} preComputedRuleSet        buildCssRuleSet() of the full design CSS
             * @param {object} options                        { svgAssets: Map<svgKey, {id,url,filename}> }
             */
            function compileHtmlToBricksJson(html, preComputedClassNameToId = null, preComputedRuleSet = null, options = {}) {
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
                if (!ruleSet) ruleSet = { rules: [], states: [], all: [] };

                const ctx = { ruleSet: ruleSet, plan: getBreakpointPlan(), svgAssets: options.svgAssets || null };
                const baseWidth = ctx.plan.base.width;

                // Bricks' text-basic tag control only offers these; anything else needs customTag.
                const TEXT_TAG_OPTIONS = ['div', 'p', 'span', 'figcaption', 'address', 'figure'];

                // ── STEP 2c: Walk DOM, create minimal Bricks elements ─────────
                function walkElement(el, parentId = 0) {
                    if (el.nodeType !== 1) return null;
                    const tag = el.tagName.toLowerCase();
                    if (['script', 'style', 'meta', 'link', 'title', 'br', 'wbr'].includes(tag)) return null;

                    const explicitName = el.getAttribute('data-bricks');
                    let bricksName = bricksNameFor(el);

                    // Resolve the cascade once, up front — element-type detection needs it.
                    const ownDecls = resolveDeclsAt(el, ruleSet, baseWidth);

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

                    // ── Inline SVG → Bricks' native SVG element (from its uploaded file) ──
                    let svgNode = null;
                    if (bricksName === 'custom-html-css-script' || bricksName === 'svg') {
                        const candidate = tag === 'svg' ? el : soleSvgChild(el);
                        if (candidate && svgAssetFor(candidate, ctx)) {
                            bricksName = 'svg';
                            svgNode = candidate;
                        } else if (bricksName === 'svg') {
                            bricksName = 'custom-html-css-script';
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
                    // Visual styling stays in the global class CSS, untranslated. Custom HTML
                    // keeps its classes on its own markup: its wrapper wearing them too would
                    // apply every padding, border and background twice.
                    const htmlClass = el.getAttribute('class');
                    if (htmlClass && bricksName !== 'custom-html-css-script') {
                        const classNames = htmlClass.split(/\s+/).filter(c => c && !isFaToken(c));
                        const globalClassIds = classNames
                            .map(cn => classNameToId[cn])
                            .filter(Boolean);
                        if (globalClassIds.length) {
                            element.settings._cssGlobalClasses = globalClassIds;
                        }
                    }

                    // ── Layout-critical part of the cascade → native Bricks settings, per
                    //    breakpoint, so it wins against Bricks' own .brxe-* defaults.
                    if (bricksName === 'custom-html-css-script') {
                        // The wrapper div must not exist as a box: its markup lays out
                        // directly in the parent, exactly as in the preview.
                        element.settings._display = 'contents';
                    } else {
                        Object.assign(element.settings, computeNativeLayout(el, bricksName, ctx));
                    }

                    // ── Real HTML tag for layout elements ───────────────────
                    if (LAYOUT_ELEMENT_NAMES.has(bricksName)) {
                        applyLayoutTag(element, el, bricksName, tag);
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
                            // <ul>, <ol>, <blockquote>, <pre> keep their own tag and their
                            // inner markup. These used to return before being added to the
                            // output, so every simple list and quote silently vanished.
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
                                // Not a font icon (sprite, unknown icon font, ...) — keep it verbatim
                                element.name = 'custom-html-css-script';
                                element.settings = { content: el.outerHTML, _display: 'contents' };
                            }
                            if (element.name === 'icon') {
                                // Pin what the icon really renders as. Size and colour usually
                                // come from a wrapper in HTML; Bricks' .brxe-icon{font-size:60px}
                                // cuts that inheritance, and the colour did not survive either.
                                pinIconAppearance(el, element.settings, ctx);
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'text-link':
                        case 'button': {
                            const found = findControlIcon(el, ctx);
                            // innerHTML (not textContent) so <a>Read <strong>more</strong></a>
                            // keeps its formatting.
                            element.settings.text = innerHtmlWithout(el, found ? found.removal : null);
                            const href = el.getAttribute('href') || el.getAttribute('data-href');
                            if (bricksName === 'text-link' || href) {
                                element.settings.link = parseLink(el);
                            } else if (tag === 'button') {
                                // Bricks' button supports the real tag; <span> dropped every
                                // ".card button" rule and the button semantics with it.
                                element.settings.tag = 'button';
                            }
                            if (found) {
                                applyControlIcon(element, el, found, ownDecls, ctx);
                            } else if (el.getAttribute('data-icon-gap')) {
                                element.settings[bricksName === 'button' ? 'iconGap' : 'gap'] = el.getAttribute('data-icon-gap');
                            }
                            isLeaf = true;
                            break;
                        }

                        case 'image': {
                            const src = el.getAttribute('src') || el.getAttribute('data-src');
                            if (src) element.settings.image = { url: src, size: 'full', external: true };
                            const alt = el.getAttribute('alt');
                            if (alt) element.settings.altText = alt;   // Bricks' image control reads altText
                            isLeaf = true;
                            break;
                        }

                        case 'svg': {
                            const node  = svgNode || el;
                            const asset = svgAssetFor(node, ctx);
                            element.settings.file = { id: asset.id, filename: asset.filename, url: asset.url };
                            const d = node === el ? ownDecls : resolveDeclsAt(node, ruleSet, baseWidth);
                            const w = explicitLength(d['width'])  || explicitLength(node.getAttribute('width'));
                            const h = explicitLength(d['height']) || explicitLength(node.getAttribute('height'));
                            // The svg element's own width/height controls size the root; a
                            // duplicate _width would only compete with them.
                            Object.keys(element.settings).forEach(k => { if (k.split(':')[0] === '_width') delete element.settings[k]; });
                            if (w) element.settings.width  = w;
                            if (h) element.settings.height = h;
                            const fill   = explicitPaint(d['fill']);
                            const stroke = explicitPaint(d['stroke']);
                            if (fill)   element.settings.fill   = toBricksColor(fill);
                            if (stroke) element.settings.stroke = toBricksColor(stroke);
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
                    // Custom HTML and SVG files already carry their attributes in their markup.
                    if (element.name !== 'custom-html-css-script' && element.name !== 'svg') {
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
                    }

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
