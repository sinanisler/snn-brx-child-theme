# AI Agent Architecture Upgrade: CSS Classes → Bricks Global Classes

> **Status:** Plan Finalized — Decisions Locked  
> **Impact:** ~85% reduction in compiler code + ~84% reduction in system prompt  
> **Goal:** Let CSS be CSS. Eliminate the CSS→Bricks translation layer entirely.  
> **Method:** Direct reactive state manipulation (`bricksState.globalClasses`) — no PHP, no AJAX, no file regeneration.

---

## 1. Current Architecture — The Problem

The system currently translates every CSS property through **three heavy layers** before it reaches Bricks:

```
┌──────────────────────────────────────────────────────────────┐
│ LAYER 1: System Prompt (~500 lines)                         │
│                                                              │
│ Teaches AI Bricks-specific quirks:                           │
│   "ALWAYS write display:flex AND flex-direction together"   │
│   "NEVER use display:inline-flex — Bricks doesn't support"  │
│   "Bricks defaults width to 100%, not auto"                 │
│   "NO max-width/margin on container elements"               │
│   "Use padding-top/padding-bottom not padding shorthand"    │
│   "Use data-hover-background for hover (no :hover in CSS)"  │
│   Hundreds of rules...                                       │
└────────────────────────────┬─────────────────────────────────┘
                             ↓
┌──────────────────────────────────────────────────────────────┐
│ LAYER 2: CSS-to-Bricks Mapping (~380 lines)                 │
│                                                              │
│ CSS_TO_BRICKS_MAP — 100+ entries mapping every CSS property  │
│ to Bricks internal setting keys:                             │
│   "padding"       → _padding: {top,right,bottom,left}       │
│   "font-size"     → _typography: {"font-size": "48"}        │
│   "display: flex" → _display: "flex"                        │
│   "border"        → _border: {width, style, color}          │
│   "box-shadow"    → _boxShadow: {values, color}             │
│                                                              │
│ stylesToBricksSettings() — 200-line switch statement         │
│ parseBorder(), parseBoxShadow(), parseBorderRadius()         │
│ parseBoxModelValue(), extractNumeric()                       │
└────────────────────────────┬─────────────────────────────────┘
                             ↓
┌──────────────────────────────────────────────────────────────┐
│ LAYER 3: Validation & Responsive (~230 lines)               │
│                                                              │
│ validateAndFixBricksJSON():                                  │
│   - Rewrite all IDs to 6-letter Bricks format               │
│   - Fix nesting (container inside block → convert to block) │
│   - Clean orphaned settings                                 │
│   - Remap parent references                                 │
│                                                              │
│ applyResponsiveRules():                                      │
│   - "fontSize >= 72 → tablet *0.7, mobile *0.5"            │
│   - "grid 3 columns → tablet 2fr, mobile 1fr"              │
│   - "flex rows → stack to column on mobile"                 │
│   - "padding >= 80 → reduce on tablet/mobile"              │
│                                                              │
│ reviewDesign() — extra AI call to fix structural issues     │
└──────────────────────────────────────────────────────────────┘
```

**Root cause of all this complexity:** We're translating native CSS into Bricks' proprietary settings format. Every CSS property needs a mapping. Every shorthand needs parsing. Every Bricks quirk needs a rule.

---

## 2. The Insight

**Bricks already supports native CSS through its Global Classes system — and the reactive state gives us direct access.**

A Bricks Global Class is a CSS class with raw custom CSS stored in `settings._cssCustom`. Elements reference global classes via `_cssGlobalClasses` (an array of 6-letter global class IDs). The CSS is rendered as native CSS on the frontend. **No translation needed.**

Even better: the entire global classes array is directly accessible and writable through Bricks' Vue reactive state:

```javascript
const bricksState = document.querySelector('[data-v-app]').__vue_app__.config.globalProperties.$_state;

// Read existing global classes:
bricksState.globalClasses  // Array of { id, name, settings: { _cssCustom, ... } }

// Elements reference global classes by ID:
element.settings._cssGlobalClasses = ["qwfwpn", "abcxyz"]
```

This means we write directly to the reactive state and Bricks picks up the changes instantly.

Instead of:
```
AI → Inline styles → Compiler parses CSS → Maps to Bricks _settings → Bricks renders
```

We can do:
```
AI → CSS classes → Compiler writes to bricksState.globalClasses → Elements reference class IDs → Bricks renders
```

The CSS is **never translated**. It flows from AI → `<style>` block → `bricksState.globalClasses[i].settings._cssCustom` → browser. Exactly as written.

---

## 3. Proposed Architecture

```
┌──────────────────────────────────────────────────────────────┐
│ LAYER 1: System Prompt (~80 lines)                           │
│                                                              │
│ "Write standard CSS classes. Reference them on elements.    │
│  Use data-bricks for element type. That's it."              │
└────────────────────────────┬─────────────────────────────────┘
                             ↓
┌──────────────────────────────────────────────────────────────┐
│ LAYER 2: Minimal Compiler (~200 lines)                       │
│                                                              │
│ 1. Extract <style> block → parse CSS class definitions      │
│ 2. Generate 6-letter IDs for each class                     │
│ 3. Write global classes → bricksState.globalClasses (Vue)   │
│ 4. Walk DOM → create Bricks elements with _cssGlobalClasses │
│ 5. Handle element content: text, src, href, icons           │
└────────────────────────────┬─────────────────────────────────┘
                             ↓
                     (No Layer 3 needed)
              CSS media queries handle responsive
              Native :hover, @keyframes, etc.
```

**Key difference from the original proposal:** Global classes are registered **directly in the reactive state** (`bricksState.globalClasses`). The Vue reactivity system propagates changes instantly. Elements reference global classes via `_cssGlobalClasses` (array of 6-letter IDs), matching the native Bricks copy/paste format.

---

## 4. What Gets Eliminated

### From `html-to-bricks-translation.php`:

| Section | Lines | Why Gone |
|---------|-------|----------|
| `CSS_TO_BRICKS_MAP` dictionary | ~150 | No CSS→Bricks mapping needed |
| `parseInlineCSS()` | ~12 | No inline styles to parse |
| `extractNumericValue()` | ~6 | No value extraction |
| `parseBoxModel()` | ~10 | No box model parsing |
| `parseBoxModelValue()` | ~10 | No box parsing |
| `extractNumeric()` / `cleanFontFamily()` | ~15 | No numeric/unit handling |
| `stylesToBricksSettings()` | ~200 | **The big one** — entire switch statement |
| `parseBoxShadow()` | ~30 | No shadow parsing |
| `parseBorder()` | ~30 | No border parsing |
| `parseBorderRadius()` | ~10 | No radius parsing |
| `parseFaIcon()` | ~15 | Keep — still needed for icon elements |
| Flex property inference block | ~15 | CSS handles flex natively |
| `convertStyleIdCss()` | ~12 | No style-id mapping needed |
| `applyResponsiveRules()` | ~80 | CSS `@media` queries instead |
| Hover attribute handling (`data-hover-background`, `data-hover-transform`) | ~25 | CSS `:hover` instead |
| Unified CSS finalization block | ~30 | Simplified to just class registration |
| `validateAndFixBricksJSON()` — settings cleaning | ~60 | No settings to clean, IDs only |
| `reviewDesign()` agent | ~25 | Fewer structural issues |

**Total eliminated from compiler: ~700+ lines**

### From `ai-agent-and-chat-bricks.php` System Prompt (`buildDesigningPrompt`):

| Section | Lines | Why Gone |
|---------|-------|----------|
| "STYLING RULES — CRITICAL" | ~50 | No inline style rules needed |
| "FLEX RULE FOR BLOCKS" | ~10 | CSS handles flex |
| "NEVER use display:inline-flex" | ~5 | Not relevant |
| "BRICKS WIDTH DEFAULT IS 100%, NOT AUTO" | ~8 | CSS width works normally |
| "EXPLICIT WIDTH ON NON-FULL-WIDTH BLOCKS" | ~6 | CSS handles this |
| "HOVER & TRANSITIONS — data-hover attributes" | ~15 | CSS `:hover` |
| "CUSTOM CSS — STYLE TAGS" section | ~80 | Classes replace style-id blocks |
| "COMMON STYLES — ALL BRICKS ELEMENTS" | ~25 | No need to document Bricks settings |
| "LAYOUT PATTERNS" section | ~50 | CSS classes, not Bricks patterns |
| "RESPONSIVE BREAKPOINTS" | ~15 | CSS `@media` |
| "NO max-width/margin/padding on container" | ~6 | CSS overrides work naturally |
| "NO LEFT/RIGHT PADDING on section" | ~6 | CSS shorthand works |
| Example code blocks (4 large examples) | ~120 | Simplified to one short example |
| "CRITICAL REMINDERS" section | ~8 | Not needed |

**Total eliminated from system prompt: ~400+ lines**

---

## 5. What Stays (Element Semantics, Not Styling)

These parts handle the **meaning** of elements, not their appearance:

| Component | Purpose |
|-----------|---------|
| `data-bricks` attribute → element type mapping | `section`, `container`, `block`, `heading`, `text-basic`, `button`, `image`, `icon`, `divider`, `text-link`, `text`, `custom-html-css-script` |
| Tag fallback map | `<h1>`→heading, `<p>`→text-basic, `<img>`→image, `<i>`→icon, `<hr>`→divider, `<a>`→text-link |
| Text content extraction | `element.innerHTML` → `settings.text` |
| Heading tag extraction | `h1`→`settings.tag: "h1"` |
| Image src/alt extraction | `element.getAttribute('src')` |
| Link/href handling | `element.getAttribute('href')` → `settings.link` |
| FontAwesome icon parsing | `fas fa-star` → `{library, icon}` |
| Button text + icon extraction | Text content + icon child or `data-icon` |
| Query loop attributes | `data-loop`, `data-loop-posts-per-page`, `{post_title}`, `{post_link}`, `{post_excerpt}`, `{cf_...}` |
| ID generation | 6-letter Bricks-native IDs |
| Tree building | parent/children array consistency |
| Intent classification | `new_design`, `add_section`, `edit_patch`, `question`, `refine_preview`, `use_abilities` |
| Planning agent | Section plan overview |
| Theming agent | Color/font/spacing spec (guides AI, not compiled) |
| Chat UI, AI proxy, image handling, abilities | Infrastructure |

---

## 6. AI Output Format — Before vs After

### Before (Current)

The AI must write every CSS property as an inline `style="..."` attribute, memorizing Bricks-specific rules:

```html
<section data-bricks="section" style="padding-top: 80px; padding-bottom: 80px; background: #0f172a;">
  <div data-bricks="container" style="display: flex; flex-direction: column; gap: 32px; align-items: center;">
    <h1 data-bricks="heading" style="font-family: 'Playfair Display', serif; font-size: 60px; font-weight: 900; color: #ffffff; line-height: 1.1; text-align: center; letter-spacing: -1px; margin: 0;">Premium Heading</h1>
    <hr style="border-top: 2px solid rgba(255,255,255,0.2); width: 60px; text-align: center;">
    <p data-bricks="text-basic" style="font-family: 'Inter', sans-serif; font-size: 20px; font-weight: 400; color: rgba(203,213,225,1); line-height: 1.7; text-align: center; max-width: 700px; margin: 0;">Supporting description with readable line height.</p>
    <button data-bricks="button" data-hover-background="#1d4ed8" data-hover-transform="translateY(-2px)" style="background: #2563eb; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 600; padding: 14px 32px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(37,99,235,0.3); transition: all 0.2s;">Call to Action</button>
  </div>
</section>
```

⚠️ The AI had to remember: don't use `padding` shorthand (use `padding-top`/`padding-bottom`), don't use `:hover` (use `data-hover-background`), don't use `display:inline-flex`, always pair `display:flex` with `flex-direction`, etc.

### After (Proposed)

The AI writes standard HTML with CSS classes — which AI models already excel at:

```html
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap');

  .hero {
    padding: 80px 0;
    background: #0f172a;
  }
  .hero-container {
    display: flex;
    flex-direction: column;
    gap: 32px;
    align-items: center;
  }
  .hero-heading {
    font-family: 'Playfair Display', serif;
    font-size: clamp(36px, 8vw, 60px);
    font-weight: 900;
    color: #ffffff;
    line-height: 1.1;
    text-align: center;
    letter-spacing: -1px;
  }
  .hero-divider {
    border: none;
    border-top: 2px solid rgba(255, 255, 255, 0.2);
    width: 60px;
  }
  .hero-text {
    font-family: 'Inter', sans-serif;
    font-size: 20px;
    color: #cbd5e1;
    line-height: 1.7;
    text-align: center;
    max-width: 700px;
  }
  .hero-button {
    background: #2563eb;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    font-weight: 600;
    padding: 14px 32px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    transition: all 0.2s ease;
  }
  .hero-button:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
  }
  @media (max-width: 768px) {
    .hero { padding: 40px 0; }
    .hero-heading { font-size: 36px; }
  }
</style>

<section data-bricks="section" class="hero">
  <div data-bricks="container" class="hero-container">
    <h1 data-bricks="heading" class="hero-heading">Premium Heading</h1>
    <hr class="hero-divider">
    <p data-bricks="text-basic" class="hero-text">Supporting description with readable line height.</p>
    <button data-bricks="button" class="hero-button">Call to Action</button>
  </div>
</section>
```

✅ The AI can use ANY CSS feature: `clamp()`, `@media`, `:hover`, `@keyframes`, `::before`, `::after`, `backdrop-filter`, `@container`, CSS variables — everything works because it's native CSS.

---

## 7. Class Naming Convention

**Natural, semantic, BEM-like naming. No prefix needed.**

Each section gets a short, descriptive base name. Child elements are hyphenated from the parent:

```
Hero section:      .hero, .hero-container, .hero-heading, .hero-text, .hero-button
Features section:  .features, .features-grid, .features-card, .features-icon, .features-title
Testimonials:      .testimonials, .testimonials-grid, .testimonials-card, .testimonials-quote
Pricing:           .pricing, .pricing-grid, .pricing-card, .pricing-featured, .pricing-price
CTA section:       .cta, .cta-container, .cta-heading, .cta-button
Footer:            .footer, .footer-grid, .footer-column, .footer-link
Stats band:        .stats, .stats-bar, .stats-item, .stats-number, .stats-label
Navbar:            .navbar, .navbar-container, .navbar-brand, .navbar-links
```

**Rules:**
- One word per section: `hero`, `features`, `testimonials`, `pricing`, `cta`, `footer`
- Children: `{section}-{element}` like `hero-heading`, `features-card`, `pricing-price`
- Use BEM modifiers for variants: `pricing-card--featured`, `features-card--dark`
- These are injected into a Bricks page — conflicts with existing theme classes are unlikely since page sections are unique. If a class name happens to clash with an existing global class, a new global class entry with a different 6-letter ID is created — existing site classes are **never** overwritten or deleted.

### ID Generation Strategy

**Two independent 6-letter ID spaces** — one for elements, one for global classes:

| Entity | ID Format | Example | Stored In |
|--------|-----------|---------|-----------|
| Bricks Element | 6 lowercase letters | `"winums"` | `element.id` |
| Global Class | 6 lowercase letters | `"qwfwpn"` | `globalClass.id` |

**Element IDs** are assigned during DOM walk. Every element created in Bricks MUST have a unique 6-letter ID — without unique IDs, Bricks elements break (missing context menus, drag-and-drop failures, canvas rendering issues). The compiler generates these automatically; the AI does NOT need to write `id` attributes in the HTML.

**Global Class IDs** map CSS class names to Bricks global class entries. The compiler:
1. Extracts each `.class-name` from the `<style>` block
2. Generates a unique 6-letter ID for each class
3. Creates a global class entry: `{ id: "abcxyz", name: "class-name", settings: { _cssCustom: "raw CSS" } }`
4. Maintains a `classNameToId` map for element assignment

**On elements**, the class reference uses `_cssGlobalClasses` (array of global class IDs):
```json
{
  "id": "winums",
  "name": "section",
  "settings": {
    "_cssGlobalClasses": ["qwfwpn"]
  }
}
```

This matches the native Bricks copy/paste format exactly. The `_cssCustom` in the global class contains the **full raw CSS** including `.class-name{...}` selectors, `@media` blocks, `@keyframes`, pseudo-classes — everything the AI wrote, preserved verbatim.

---

## 8. The New Compiler (`compileHtmlToBricksJson`)

### Overview

The compiler shrinks from ~800 lines to ~150 lines. It does exactly three things:

1. **Extract CSS classes** from the `<style>` block
2. **Walk the DOM** and create minimal Bricks elements
3. **Return the data** — content array + global classes map

### Pseudocode

```javascript
function compileHtmlToBricksJson(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const content = [];
    const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
    const usedIds = new Set();
    
    // className → { id, css } mapping. The ID is a 6-letter Bricks-native string.
    // CSS is the FULL raw block including .class-name{...} selectors, @media, @keyframes.
    const classMap = {};       // { "hero": { id: "abcxyz", css: ".hero { ... }" } }
    const classNameToId = {};  // { "hero": "abcxyz" } — fast lookup for element assignment

    function genId() {
        let id;
        do {
            id = Array.from({ length: 6 }, () => LETTERS[Math.floor(Math.random() * 26)]).join('');
        } while (usedIds.has(id) || ChatState.globalUsedIds.has(id));
        usedIds.add(id);
        ChatState.globalUsedIds.add(id);
        return id;
    }

    // ── STEP 1: Extract CSS class definitions ────────────────────────
    doc.querySelectorAll('style').forEach(styleEl => {
        const css = styleEl.textContent;

        // Parse .classname { ... } blocks and @media blocks
        const rules = parseCSSRules(css);
        for (const [className, cssBlock] of Object.entries(rules)) {
            if (!classMap[className]) {
                const gid = genId();
                classMap[className] = { id: gid, css: '' };
                classNameToId[className] = gid;
            }
            // Accumulate CSS — preserve full selector syntax (.class-name{...})
            classMap[className].css += cssBlock;
        }
    });

    // ── STEP 2: Tag → Bricks element type fallback ──────────────────
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

    // ── STEP 3: Walk DOM, create minimal Bricks elements ────────────
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

        // ── Semantic HTML tag override for layout elements ──────────
        if (['block', 'container', 'section'].includes(bricksName)) {
            if (['header', 'footer', 'nav', 'article', 'aside', 'main', 'section',
                 'figure', 'figcaption'].includes(tag)) {
                element.settings.tag = tag;
                if (bricksName === 'section' && !element.settings._display)
                    element.settings._display = 'flex';
            }
        }

        // ── Element-specific content handling ───────────────────────
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

        // ── Query loop support ──────────────────────────────────────
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

        // ── HTML data attributes → Bricks custom attributes ────────
        const ignoredAttrs = new Set([
            'id', 'class', 'style', 'data-bricks',
            'data-loop', 'data-loop-posts-per-page', 'data-loop-orderby', 'data-loop-order',
            'data-icon', 'data-icon-position', 'data-icon-gap',
            'data-href', 'href', 'src', 'alt', 'target', 'rel'
        ]);
        const customAttrs = [];
        for (const attr of el.attributes) {
            if (!ignoredAttrs.has(attr.name)) {
                customAttrs.push({ _id: genId(), name: attr.name, value: attr.value });
            }
        }
        if (customAttrs.length) element.settings._attributes = customAttrs;

        // ── Add to content array ────────────────────────────────────
        content.push(element);

        // ── Recurse children ────────────────────────────────────────
        if (!isLeaf) {
            Array.from(el.childNodes).forEach(child => {
                if (child.nodeType === 3) {
                    const text = child.textContent.trim();
                    if (text) {
                        const textId = genId();
                        content.push({
                            id: textId, name: 'text-basic', parent: id,
                            children: [], settings: { text }, themeStyles: []
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

    // Start from body
    Array.from(doc.body.children).forEach(el => walkElement(el, 0));

    // ── Build globalClasses array in Bricks-native format ──────────
    const globalClasses = Object.values(classMap).map(gc => ({
        id: gc.id,
        name: gc.id,           // The global class "name" field is the 6-letter ID
        settings: {
            _cssCustom: gc.css  // Full raw CSS: .class-name{...} + @media + @keyframes
        }
    }));

    return { content, globalClasses, classNameToId };
}

// ── Helper: Parse FontAwesome icon class ────────────────────────────────
function parseFaIcon(classString) {
    if (!classString || !classString.includes('fa-')) return null;
    let library = 'fontawesomeSolid';
    if (classString.includes('fab ')) library = 'fontawesomeBrands';
    else if (classString.includes('far ') || (classString.includes('fa ') && !classString.includes('fas ')))
        library = 'fontawesomeRegular';
    return { library, icon: classString.trim() };
}

// ── Helper: Parse href → Bricks link object ─────────────────────────────
function parseLink(el) {
    const href = el.getAttribute('href') || el.getAttribute('data-href') || '#';
    const link = {
        type: (href.startsWith('#') || href.startsWith('/')) ? 'internal' : 'external',
        url: href
    };
    if (el.getAttribute('target') === '_blank') link.blank = true;
    return link;
}

// ── Helper: Parse CSS rules into { className: cssBlock } ───────────────
function parseCSSRules(css) {
    const classes = {};

    // Remove comments
    css = css.replace(/\/\*[\s\S]*?\*\//g, '');

    // Match top-level class rules: .classname { ... }
    // Handles nested {} by counting braces
    const ruleRegex = /\.([a-zA-Z0-9_-]+)\s*\{/g;
    let match;
    while ((match = ruleRegex.exec(css)) !== null) {
        const name = match[1];
        const startIndex = match.index + match[0].length - 1;
        let depth = 1;
        let endIndex = startIndex + 1;
        while (depth > 0 && endIndex < css.length) {
            if (css[endIndex] === '{') depth++;
            else if (css[endIndex] === '}') depth--;
            endIndex++;
        }
        const body = css.substring(startIndex + 1, endIndex - 1).trim();
        if (!classes[name]) classes[name] = '';
        classes[name] += body + ' ';
    }

    // Match @media blocks and associate their class rules
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
            classes[name] += mediaQuery + ' { ' + cssBlock + ' } ';
        }
    }

    return classes;
}
```
```

---

## 9. Global Class Registration — Direct Reactive State

Bricks' Vue reactive state (`$_state`) exposes `globalClasses` as a directly writable array. We write to it and Vue propagates changes instantly.

### The Reactive State Structure

```javascript
// Access Bricks reactive state
const bricksState = document.querySelector('[data-v-app]').__vue_app__.config.globalProperties.$_state;

// bricksState.globalClasses is a reactive array
// Each entry: { id: "6letter", name: "6letter", settings: { _cssCustom: "raw CSS" } }
```

### Writing Global Classes to Reactive State

```javascript
/**
 * Write AI-generated global classes directly into Bricks reactive state.
 * EXISTING classes are NEVER deleted or overwritten — data integrity first.
 * Only ADD new classes (or update classes the AI previously created for the same section).
 * 
 * @param {Array} newClasses - Array of { id, name, settings: { _cssCustom } }
 * @param {Object} classNameToId - Map from CSS class name → global class ID
 */
function writeGlobalClassesToState(newClasses, classNameToId) {
    const s = BricksHelper.getState();
    if (!s) { debugLog('Bricks state not available'); return false; }

    // Ensure globalClasses array exists
    if (!Array.isArray(s.globalClasses)) {
        s.globalClasses = [];
    }

    // Build set of existing global class IDs for O(1) lookup
    const existingIds = new Set(s.globalClasses.map(gc => gc.id));

    let addedCount = 0;
    newClasses.forEach(gc => {
        if (!existingIds.has(gc.id)) {
            // NEW class — append to reactive array
            s.globalClasses.push({
                id: gc.id,
                name: gc.name,
                user_id: '1',
                modified: Math.floor(Date.now() / 1000),
                _exists: true,
                settings: {
                    _cssCustom: gc.settings._cssCustom
                }
            });
            existingIds.add(gc.id);
            addedCount++;
        }
        // If ID already exists, do NOT overwrite — it's a pre-existing site class.
        // The AI should never mutate classes it didn't create.
    });

    debugLog('✓ Added', addedCount, 'global classes to reactive state (skipped', 
             newClasses.length - addedCount, 'existing)');
    return addedCount > 0;
}

/**
 * Write compiled elements into Bricks reactive state.
 * Replaces or appends to bricksState.content.
 */
function writeElementsToState(contentArray, actionType = 'append') {
    const s = BricksHelper.getState();
    if (!s) { debugLog('Bricks state not available'); return false; }

    if (!Array.isArray(s.content)) {
        s.content = [];
    }

    if (actionType === 'replace') {
        s.content = [...contentArray];
    } else {
        s.content = [...s.content, ...contentArray];
    }

    // Trigger canvas re-render
    setTimeout(() => {
        if (window.bricksCore?.builder?.canvas?.render) {
            window.bricksCore.builder.canvas.render();
            requestAnimationFrame(() => window.bricksCore.builder.canvas.render());
        }
    }, 200);

    return true;
}
```

### Compilation Flow (End to End)

```javascript
async function compileSectionBySection(actionType) {
    // ... section parsing ...

    for (let i = 0; i < sections.length; i++) {
        const { label, html } = sections[i];

        // 1. Compile HTML → { content, globalClasses, classNameToId }
        const bricksData = compileHtmlToBricksJson(html);

        // 2. Write global classes to reactive state FIRST
        //    (elements reference them by ID, so classes must exist before elements)
        writeGlobalClassesToState(bricksData.globalClasses, bricksData.classNameToId);

        // 3. Validate element IDs and tree structure
        const { data } = validateAndFixBricksJSON(bricksData);

        // 4. Write elements to reactive state
        const result = (i === 0 && actionType === 'replace')
            ? writeElementsToState(data.content, 'replace')
            : writeElementsToState(data.content, 'append');
    }
}
```

**Why this approach:**
- **Instant** — Vue reactivity propagates changes in microseconds
- **Data integrity** — Existing global classes are never touched
- **Bricks-native format** — Matches the exact copy/paste JSON structure

---

## 10. Updated System Prompt (`buildDesigningPrompt`)

The system prompt shrinks from ~500 lines to ~80 lines:

```
You are a Bricks Builder design agent. Generate complete, beautiful HTML with CSS classes.

OUTPUT FORMAT:
1. One sentence describing the design approach and color palette
2. A ```html code block containing:
   - A <style> tag with ALL CSS class definitions AND Google Fonts @import
   - Section elements with data-bricks attributes and class references

CSS RULES:
- Define ALL styles as CSS classes in the <style> block at the top.
- Use class="..." on elements. NO inline style="" attributes.
- Write standard CSS — any property, pseudo-class (:hover), @keyframes, @media queries.
- Class naming: one word per section, hyphenated children. Examples:
  .hero, .hero-container, .hero-heading, .hero-text, .hero-button
  .features, .features-grid, .features-card, .features-icon
  .testimonials, .testimonials-grid, .testimonials-card, .testimonials-quote
- Include responsive @media queries for mobile.
- Google Fonts: @import in the <style> tag.

HTML RULES:
- Use data-bricks attributes on every structural element:
  data-bricks="section"  — top-level section/header/footer
  data-bricks="container" — one per section, DIRECT child of section
  data-bricks="block"    — all inner layout divs
  data-bricks="heading"  — h1 through h6
  data-bricks="text-basic" — p, span, li text
  data-bricks="text"     — rich text with complex formatting
  data-bricks="button"   — buttons/CTAs (use href for link)
  data-bricks="text-link" — inline text links (<a> tags)
  data-bricks="image"    — img elements (use src for URL)
  data-bricks="icon"     — FontAwesome <i class="fas fa-icon"> (also fab, far)
  data-bricks="custom-html-css-script" — raw HTML/SVG/iframes
- Icons: <i class="fas fa-star"> or <i class="fab fa-twitter"> — style with CSS class
- Structure: section > container > content elements
- Real images via Pixabay proxy: {ajaxUrl}?action=snn_pixabay_image&q=KEYWORDS

QUERY LOOPS (when listing posts):
- Three-layer structure:
  1. Grid wrapper block (display:grid, NO data-loop)
  2. Loop block (data-loop="post_type_slug", data-loop-posts-per-page="6")
  3. Template card (one card — Bricks repeats it)
- Dynamic tags inside template: {post_title}, {post_excerpt}, {post_date}, {post_link}, {cf_POSTTYPE_FIELDNAME}

DESIGN QUALITY:
- Real content, no Lorem Ipsum
- Professional color palettes, strong typography hierarchy
- Modern aesthetics: rounded corners, subtle shadows, generous whitespace
- Production-ready design — not a wireframe

OUTPUT THE HTML ONLY. No patch blocks, no JSON.
```

---

## 11. Migration Path

### Phase 1: Build the New Compiler (Side by Side)

1. Create new `compileHtmlToBricksJson()` function that returns `{ content, globalClasses, classNameToId }`
2. Add `writeGlobalClassesToState()` and `writeElementsToState()` helpers (write directly to `bricksState`)
3. Add a feature flag in settings: `snn_use_class_based_compilation`
4. When flag is ON: use new system prompt + new compiler
5. When flag is OFF: use the existing inline-style pipeline

### Phase 2: Test & Compare

1. Run the same design prompts through both pipelines
2. Compare: output quality, compilation speed, error rate, prompt token cost
3. Verify: Global classes appear in `bricksState.globalClasses` correctly
4. Verify: `_cssGlobalClasses` references resolve and CSS renders on frontend
5. Verify: Responsive works via `@media` in `_cssCustom`, hover/animations via native CSS
6. Verify: Existing site global classes remain untouched

### Phase 3: Switch & Clean Up

1. Make class-based compilation the default
2. **Remove** the old `CSS_TO_BRICKS_MAP`, `stylesToBricksSettings()`, ALL CSS parsing helpers (`parseBorder`, `parseBoxShadow`, `parseBorderRadius`, `parseBoxModelValue`, `extractNumeric`, `cleanFontFamily`, `parseInlineCSS`, `extractNumericValue`, `parseBoxModel`)
3. **Remove** the old system prompt (`buildDesigningPrompt` — the ~400 line version) and `reviewDesign()` agent
4. **Remove** `applyResponsiveRules()` — CSS `@media` handles responsive
5. **Remove** hover data-attribute handling (`data-hover-background`, `data-hover-transform`) — CSS `:hover` handles it
6. **Remove** `convertStyleIdCss()`, `styleIdMap`, `data-style-id` support — classes replace all of this
7. Archive old code with a comment for reference

### Phase 4: Patch/Edit Support (Class-Based)

Once the class-based system is stable, enhance patching:
- Instead of patching individual Bricks `_settings`, patch the CSS class definition in `_cssCustom`
- "Make the hero heading red" → find the `.hero-heading` global class → update `_cssCustom` to add `color: #ff0000;`
- This is more powerful because one change affects ALL elements using that class
- **No hybrid mode** — the old inline-style pipeline is fully removed. Pages built with it will be regenerated with the class system if edited.

---

## 12. Benefits Summary

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Compiler JS lines | ~1,000 | ~200 | **80% reduction** |
| System prompt lines | ~500 | ~80 | **84% reduction** |
| CSS property support | Limited to Bricks settings panel | **All CSS properties** | Unlimited |
| Pseudo-class support | `data-hover-*` hacks only | **All**: `:hover`, `:focus`, `:nth-child`, `:has()`, etc. | Full CSS |
| Responsive design | Auto-generated approximations | **Exact `@media` queries** | Precise |
| Animations | Complex `style data-style-id` blocks | **Standard `@keyframes`** | Native |
| AI prompt tokens | High (verbose rules) | Low (simple rules) | **~60% fewer tokens** |
| Debugging | Hard (CSS→Bricks mapping mismatch) | Easy (CSS is CSS) | Obvious |
| AI creativity | Constrained by Bricks limitations | **Unconstrained — full CSS** | Maximum |
| Reliability | Translation bugs possible | No translation = no bugs | 100% |
| Server cost | PHP AJAX + file regeneration | **Zero** — direct reactive state | No server round-trips |
| Data integrity | Risk of overwriting | **Never deletes** existing classes | Safe |
| Registration speed | Async HTTP round-trip | **Instant** — Vue reactivity | Microseconds |

---

## 13. Decisions Record

| # | Question | Decision | Rationale |
|---|----------|----------|-----------|
| 1 | **Global class ID format** | 6-letter Bricks-native IDs, mapped from class names | Matches Bricks' native format; `bricksState.globalClasses` uses 6-letter IDs internally. A `classNameToId` map bridges CSS class names to IDs. |
| 2 | **CSS storage format** | Full raw CSS as-is, including `.class-name{...}` selectors, `@media`, `@keyframes` | No parsing, no transformation. The AI's CSS is preserved verbatim in `_cssCustom`. |
| 3 | **Global class cleanup** | **NEVER delete.** Only ADD new classes. Existing site classes are immutable. | Data integrity is paramount. The AI must not disrupt the site's existing global class system. |
| 4 | **Element ID strategy** | Every element gets a unique 6-letter ID during compilation | Non-negotiable — Bricks elements break without unique IDs (missing context menus, drag-and-drop failures, canvas rendering issues). |
| 5 | **Hybrid mode (old + new)** | **NO hybrid mode.** Old inline system is fully removed. | Complexity must die for LLM creativity and freedom. One clean path: CSS classes → Global Classes. Old pages get regenerated. |
| 6 | **Class conflicts** | AI classes ADD alongside existing ones. Same class name → new 6-letter ID. | The `classNameToId` map ensures uniqueness even with name collisions. No overwriting. |
| 7 | **Preview iframe** | Inject the AI's `<style>` block directly into iframe `<head>` | Already works with `buildPreviewHTML()`. Classes render natively — simpler than inline styles. |
| 8 | **Registration mechanism** | Direct write to `bricksState.globalClasses` (Vue reactivity) | No PHP AJAX, no file regeneration, no server round-trips. Instant propagation. |
| 9 | **What stays** | Element semantics only: `data-bricks` type mapping, text/content extraction, icon parsing, link handling, query loop attributes, ID generation, tree building | These handle element *meaning*, not appearance. They're minimal and stable. |
| 10 | **:root CSS variables** | Preserve `:root { }` block via direct `<style>` injection (custom-html-css-script). Supplement with Bricks palette/variables for resolvable values only. | `parseCSSRules()` only captures class rules. `:root` block must be preserved verbatim because Bricks color palette cannot store `var()` references or font-family values. |
| 11 | **Font loading** | Combine `@import` + `:root` into a single `<style>` tag, always injected BEFORE elements (independent of replace/append actionType). | Previous `isFirst` logic was broken — `actionType` always `'append'` meant fonts were NEVER loaded. |

---

## 14. Production Bugs Found & Fixed (2026-06-30)

Real-world test with "Noir Properties" luxury real estate landing page revealed these bugs.

### 🔴 C1+C2: `parseCSSRules()` — compound selectors + @media double-match

**Problem:** The `ruleRegex` `/\.([a-zA-Z0-9_-]+)...\s*\{/g` ran on the ENTIRE CSS string including content inside `@media` blocks. It also failed on comma-separated selectors (e.g., `.hero-container, .stats-grid { ... }`).

**Symptoms:**
- `@media (max-width: 991px) { .hero-content { padding: 80px 5%; } }` → the `padding: 80px 5%` appeared BOTH inside @media AND as a standalone rule (double-match)
- `.hero-container, .stats-grid, .listings-grid, .reviews-grid { grid-template-columns: 1fr; }` → only `.reviews-grid` captured; the other three classes lost their mobile responsive override

**Fix:** Two-part fix:
1. @media blocks are now extracted and REMOVED from the CSS string BEFORE base rule parsing, eliminating double-matching
2. New `parseRuleBlocks()` helper uses brace-counting to find `{ }` pairs, splits selectors by comma, and extracts the first `.className` from each selector

### 🔴 C3: `:root` CSS variables never persisted

**Problem:** `parseCSSRules()` only captures class-based rules (`.className { }`). The `:root { }` block was never stored in any `_cssCustom`. The `extractRootVariables()` extraction wrote variables to Bricks color palette/variables, but:
- `var()` references (e.g., `--primary: var(--bricks-color-grey-900)`) couldn't be resolved → `light: ""`
- Font variables (`--font-header`, `--font-body`) were explicitly skipped via `if (name.includes('font')) return;`

**Symptoms:** All CSS custom properties (`--font-header`, `--primary`, `--secondary`, `--accent`, `--surface`, `--text-muted`) were undefined on the frontend. The entire design fell apart because global class CSS referenced `var(--primary)`, `var(--font-header)` etc. which resolved to `initial`/`invalid`.

**Fix:** Combined `@import` + `:root{...}` into a single `<style>` tag injected as a `custom-html-css-script` element (PHASE 1.5). Bricks palette/variables are still written for resolvable values (hex colors, pixel sizes) as a best-effort supplement.

### 🔴 C4: Font loading element never injected

**Problem:** `fontLoadElement` was only prepended when `isFirst = (index === 0 && actionType === 'replace')`. But the approve bar button always calls `compileSectionBySection('append')`, so `isFirst` was ALWAYS false.

**Symptoms:** Google Fonts `<link>` tags never added to the page. Fonts fell back to browser defaults.

**Fix:** The CSS injection element (containing both `@import` and `:root`) is now written independently via `BricksHelper.writeElementsToState([cssInjectionElement], 'append')` in PHASE 1.5, BEFORE the section loop. It always runs regardless of `actionType`.

### 🔴 C5: AI using Bricks internal color tokens — unreadable designs

**Problem:** Three interacting system prompt issues:

1. **Theming agent prompt** explicitly said: *"When using a var() value in the spec, write it as the var() string so the designer can use it directly"* — this directly instructed the AI to output `var(--bricks-color-grey-900)` instead of `#212121`
2. **Designing prompt** displayed Bricks tokens with *"use these in color/background styles when user wants existing theme colors"* — encouraged AI to reference them
3. **Circular reference**: The AI generated `--secondary: var(--secondary)` which is a CSS circular reference. Per CSS spec, circular `var()` resolves to `initial` (usually black), so `color: var(--secondary)` → black text on dark gray background — completely unreadable.

**Symptoms:**
```
:root {
  --primary: var(--bricks-color-grey-900);   /* resolves to #212121 (ok) */
  --secondary: var(--secondary);              /* CIRCULAR — resolves to initial (black!) */
  --accent: var(--bricks-color-amber);        /* #ffc107 (yellow, not gold) */
  --surface: var(--bricks-color-grey-800);    /* #424242 */
  --text-muted: var(--bricks-color-grey-500); /* #9e9e9e */
}
```
Result: Black text on `#212121`/`#424242` backgrounds — unreadable. The AI had zero creative freedom because it was bound to Bricks' default palette.

**Fix:** Three changes:
1. **Theming agent**: MUST output concrete hex (#rrggbb) — "`var()` references are unusable and will break the design"
2. **Designing prompt**: Bricks token display REMOVED entirely. Added 🔴 CRITICAL rule: *"NEVER use var(--bricks-*) or var(--secondary). ALWAYS use concrete hex values"*
3. **Theming spec display**: Marks any `var()` refs with ⚠️ IGNORE flag so the designer knows to use its own hex

