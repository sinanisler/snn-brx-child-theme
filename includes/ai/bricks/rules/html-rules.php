<?php
/**
 * The HTML authoring rules for Bricks pages, returned by snn-bricks-start-here.
 * Kept in PHP (not a .md file) so every MCP client gets them in the tool reply.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'BRICKS_HTML_RULES'
# Building Bricks pages from HTML (SNN-BRX)

You write HTML + CSS. The server compiles it into native Bricks elements with the same compiler the Bricks AI agent uses in the builder:

- every CSS class becomes a Bricks **global class**, its CSS kept verbatim;
- the layout part of the cascade (display, flex, grid, gap, width, max-width) is resolved per breakpoint and written as **native element settings**, so the result matches a browser preview of your HTML;
- headings, text, buttons, links, images, icons, dividers, SVGs, videos and lists become their **native Bricks elements**; any other Bricks element (icon boxes, counters, alerts, pricing tables, testimonials ...) is placed with a `data-bricks` hint and its settings;
- interactions (click to show or toggle, hover, scroll and viewport triggers) and entrance animations become Bricks' own interactions, so they stay editable in the builder.

## Recommended call order

1. `snn-bricks-start-here` (this) — once per session.
2. `snn-bricks-get-page-tree` with `post_id` — read the page, note `documentDigest` and `lockedBy`.
3. Optional: `snn-bricks-get-global-classes` — reuse existing class names and palette.
4. Optional: `snn-bricks-element-schema` with `names` — the settings of an element type you are not sure about (its keys, types and allowed values). Use `search` to find an element type.
5. Images: send public URLs directly in `src`, or upload first with `snn-bricks-upload-asset` and use the returned URL. A design-export `.zip` goes to `snn-bricks-import-zip` (see "Design exports" below).
6. `snn-bricks-html-to-bricks` with `html`, `css`, `mode`, `expected_digest` (from step 2) and an `idempotencyKey`. Use `dry_run: true` to inspect the compiled elements first.
7. Read `feedback` in the reply (see "The feedback loop"), fix what it reports, and use the returned `documentDigest` for the next write.
8. Small changes later: `snn-bricks-find-elements` (by text, class, type or `css_id`) → `snn-bricks-edit-elements`. Rebuild one section: `snn-bricks-html-to-bricks` with `mode: "replace-section"` and `section_id`.
9. Verify: open `permalink` in a browser (desktop and 390px) when you can; `feedback.render` and `snn-bricks-render-summary` are the server-side check.

Modes: `append` (default), `prepend`, `replace` (whole page), `insert-after` / `insert-before` (+ `anchor_id`, a top-level id), `replace-section` (+ `section_id`).

## The feedback loop

Every write — and every `dry_run` — of `snn-bricks-html-to-bricks` and `snn-bricks-edit-elements` returns `feedback`:

- `changes` — elements added, removed and changed, with the changed setting keys. Check that it matches what you meant to change and nothing else.
- `outline` — the affected sections as `[id] type #css-id .classes "text"`. Use these ids (or the `#css-id`) for follow-up edits.
- `pageSections` — the whole page's top-level order; affected sections are flagged. Check the new sections landed where they belong.
- `checks` — heading outline problems, empty text, images without a source, buttons without a link, canvas-export leftovers (`position:absolute`, 4-digit pixel widths) in the classes used.
- `warnings` (top level of the reply) — everything the converter could not keep as you wrote it, with the reason: unknown element types or setting keys (with the right name when one is close), duplicate CSS IDs, interaction targets that name no element, icon styles Bricks cannot draw, content an element cannot hold, scripts kept as code. Read every warning; each one is a decision you should make on purpose.
- `render` — Bricks' own server render: landmarks, responsive CSS present, CSS syntax errors (builds by default; edits with `verify: true`).
- `next` — what to do next.

Work in small steps: build or change one thing, read `feedback` and `warnings`, correct, continue. Do not report a page as finished while `checks.issues` lists problems you can fix.

If a write returns `snn_post_locked`, the page is open in the Bricks builder; saving there later would overwrite your write. Ask the user to close the builder tab, then retry. Only pass `force: true` when the user says so. If it returns `snn_document_digest_mismatch`, read the page again before retrying. Never retry with a reused `idempotencyKey` for a different request.

## HTML rules

- Output sibling top-level `<section>` elements (or `<header>` / `<footer>` regions). Do **not** wrap the page in `<main>`, `<html>` or `<body>` — the theme supplies them.
- Structure: `section > container > content`. Exactly one container per section, as its direct child. Never a container inside a block.
- Put a `data-bricks` hint on every structural element:
  - `data-bricks="section"` — top-level section / header / footer
  - `data-bricks="container"` — one per section, direct child of the section
  - `data-bricks="block"` — every inner layout div (rows, grids, cards, columns)
  - `data-bricks="heading"` — h1 … h6
  - `data-bricks="text-basic"` — p, span, li text
  - `data-bricks="text"` — rich text with mixed inline formatting
  - `data-bricks="button"` — buttons / CTAs (`href` for the link)
  - `data-bricks="text-link"` — inline `<a>`
  - `data-bricks="image"` — `<img>` (keep `src` exactly as given)
  - `data-bricks="icon"` — `<i class="fas fa-star">` (fas, fab, far; `ion-ios-star` for Ionicons; `ti-star` for Themify)
  - `data-bricks="divider"` — `<hr>` and decorative line divs
  - `data-bricks="accordion"` — Bricks' accordion. Its items are `<details>` children, each with a `<summary>` title; the rest of the details is the content. A container made only of `<details>` becomes an accordion without a hint. Add `open` on the first details to expand it.
  - `data-bricks="tabs"` — Bricks' tabs. Its children are `<div data-bricks="tab" data-title="Label">`, one per tab, holding the content. Do not build tabs from radio inputs or scripts.
  - `data-bricks="slider"` — Bricks' slider. Its children are the slides (any element). Options: `data-per-page="2"`, `data-arrows="false"`, `data-pagination="false"`, `data-gap="20"`. Slides per view can differ by breakpoint: `data-per-page="3 2 1"` sets desktop, tablet (991px) and mobile (767px) values. Do not build sliders from scroll-snap or scripts.
  - `data-bricks="video"` — a video. Also automatic for YouTube and Vimeo `<iframe>` embeds and `<video src>` files (see "Video").
  - `data-bricks="<any Bricks element>"` — any element type Bricks has (icon-box, counter, alert, pricing-tables, testimonials, social-icons, progress-bar, team-members, logo, carousel, toggle, offcanvas, nav-menu ...). Use `snn-bricks-element-schema` to list the names and each element's settings. An unknown name is reported in `warnings` and the plain tag is used instead.
  - `data-bricks="custom-html-css-script"` — raw HTML kept verbatim: forms, tables, iframes that are not videos.
- **Settings of any element** as attributes: `data-bricks-<setting>="value"`. The name is matched to Bricks' schema whatever its case or dashes (`data-bricks-iconsize` is `iconSize`). Values `true` / `false` become booleans; a value starting with `[` or `{` is JSON, for repeaters such as `data-bricks-items='[{"title":"Fast"}]'`. A name the element does not have is reported with the right name when one is close.
- **Text-only content** of an element that can hold no children (icon-box, counter ...) goes to its `text` setting when it has one; otherwise it is reported.
- **HTML ids** are kept: `id="pricing"` becomes the Bricks CSS ID, so `href="#pricing"` jumps to it and interactions can target `#pricing`. Use letters, digits, `-` and `_`. Each id is used once per page; a repeat is reported.
- Scripts: a `<script>` is kept as embedded code, in place, and runs on the page. Bricks cannot edit it as an element, so the build reports every kept script in its warnings. Use Bricks' native elements, interactions and `data-animate` first; use a script only for behaviour none of them can do.
- **Interactions** (Bricks' own; any number per element):
  - shorthand: `data-interact="click" data-interact-action="toggleAttribute" data-interact-key="data-open" data-interact-target="#menu"`
  - JSON: `data-interactions='[{"trigger":"click","action":"show","target":"#menu"},{"trigger":"enterView","action":"startAnimation","animation":"fadeInUp","delay":"0.2s"}]'`
  - Triggers: click, mouseover (hover), mouseenter, mouseleave, focus, blur, enterView, leaveView, contentLoaded, scroll, animationEnd.
  - Actions: show, hide, click (of the target), setAttribute / removeAttribute / toggleAttribute (with `data-interact-key`, optional `data-interact-value`), scrollTo, startAnimation (with `data-interact-animation`), toggleOffCanvas, clearForm.
  - Target: `self` (default), `popup`, or a CSS selector such as `#menu`. Toggling a panel: put `id="menu"` on the panel, and on the button `data-interact="click" data-interact-action="toggleAttribute" data-interact-key="data-open" data-interact-target="#menu"`; style `[data-open]` in CSS.
  - Interaction timing: `data-interact-duration="0.6s"`, `data-interact-delay="0.2s"`, scroll triggers take `data-interact-offset="120px"`, viewport triggers take `data-interact-root-margin`.
- **Entrance animations** (`data-animate`): `data-animate="fadeInUp"` on any element (any animate.css name: fadeIn, fadeInDown, fadeInUp, fadeInLeft, zoomIn, bounceIn …). Optional `data-animate-delay="0.2s"` and `data-animate-duration="0.9s"`. Triggers: the default is "enter viewport"; `data-animate-trigger="contentLoaded"` plays on page load (use it for the hero); `data-animate-trigger="scroll"` plays when the page passes the element, with `data-animate-offset="120px"` (px, % or vh); `data-animate-trigger="hover"` plays on hover.
- **Video**: a YouTube or Vimeo `<iframe>` (`src` with youtube.com/embed/, youtu.be, or player.vimeo.com/video/) becomes a native Bricks video; `<video src="…">` or `<video><source src="…"></video>` becomes a file video. `title` on the iframe is kept. `?autoplay=1` on YouTube is kept. Any other iframe stays raw HTML.
- **Lists**: a `ul` or `ol` of text items becomes a Bricks list block whose items are text elements (each item stays editable). A list whose items hold layout (cards, blocks) keeps those blocks.
- Any tag the list does not name (form, table, input, textarea, select, details outside an accordion, canvas, audio, picture …) is preserved verbatim.
- Icons in buttons and links: ONE icon directly inside the button/link, at the start or end of the label. It becomes the button's native icon (side, size, colour, gap). Font Awesome (fas, fab, far), Ionicons (`ion-*`) and Themify (`ti-*`) are native. Light, duotone and thin Font Awesome styles are drawn solid, and the converter says so. Other icon fonts (Lucide, Material Symbols, Bootstrap Icons) stay as HTML and are reported.
- Standalone SVG graphics (logos, illustrations): write the `<svg>` directly with no data-bricks wrapper — it is uploaded and becomes a native Bricks SVG element (needs Bricks SVG upload permission; otherwise it stays as code). Keep the SVG's own capitals (viewBox, linearGradient); they are kept.
- Links keep their real `href` (`#pricing`, `/contact`, `https://…`); `target="_blank"` opens a new tab.
- Styles: prefer `class="…"` with CSS. An inline `style=""` is also kept: its declarations are written on that element, and its layout (display, flex, grid, gap, width) becomes native settings.
- Real content. No Lorem Ipsum.

## CSS rules

- All styling as CSS classes, in `css` or a `<style>` block. Any property, `:hover`, `:focus`, `::before`, `@keyframes`, `@font-face`, `@media` works.
- Write plain standard CSS and assume browser behaviour. Descendant and child selectors (`.card p`, `.grid > div`) are resolved for real: a rule whose subject is an element applies to that element, with every declaration except the layout the native settings carry.
- One property per line.
- `:root` custom properties with concrete values (hex / rgb / px), never `var()` as the value. Colour and size variables are added to the Bricks palette / global variables when the names are new.
- Google Fonts via `@import url(...)` at the top of the CSS.
- Class naming: one word per section, hyphenated children: `.hero`, `.hero-container`, `.hero-title`, `.features-grid`, `.features-card`. Prefix with a page or section word when the site already has generic names. A class that already exists site-wide with identical CSS is reused; with different CSS it is renamed (`-1`, `-2`) automatically and the response lists the renames.
- Renaming or deleting an existing global class is done with `snn-bricks-edit-elements` (`rename_class`, `delete_class`), not by rebuilding the page.
- Responsive: use the exact `@media` queries listed under "This site's breakpoints" below, so they map onto Bricks breakpoints. Stack multi-column rows on mobile.
- Keep primary content in normal flow: no absolute positioning, fixed canvas widths or negative offsets for layout.

## Query loops (listing posts)

1. Grid wrapper block (`display: grid`, no data-loop)
2. Loop block: `data-loop="post_type_slug" data-loop-posts-per-page="6"` (optional `data-loop-orderby`, `data-loop-order`)
3. One template card inside — Bricks repeats it. Dynamic tags: `{post_title}`, `{post_excerpt}`, `{post_date}`, `{post_link}`, `{featured_image}`, `{cf_FIELDNAME}`.

## Editing a built page

`snn-bricks-edit-elements` takes these operations, all applied together or not at all:

- element ops: `set_text`, `set_image`, `set_icon`, `set_settings` (any Bricks setting; unknown keys are reported with the right name), `delete`, `move`, `duplicate`, `insert` (an element type from `snn-bricks-element-schema`), `wrap`, `unwrap`.
- style ops: `update_class` (declarations or the whole class CSS), `rename_class` (`from`, `to`: the class, its CSS and every class or page style that names it follow; elements keep working), `delete_class` (refused while elements use it; `"detach": true` removes it from them first).
- Target an element by `{"id":"abcdef"}` (Bricks id), `{"cssId":"pricing"}` (its HTML id), `{"text":"…"}`, `{"class":"x"}`, `{"type":"heading","index":0}` or `{"within":{…}}`.

## Design exports (zip from Figma, Penpot, Webflow …)

This is the same pipeline the Bricks AI agent runs in the builder, with the server holding the state.

**Never stop at an empty page or wait for an upload before building.** If the user gave you a zip you can read but cannot send (hosted chats such as claude.ai: a tool call carries only text you type, never a file), build the page now:

1. Read the export's HTML and CSS yourself (in your sandbox).
2. Convert it section by section following the conversion rules below and build each part with `snn-bricks-html-to-bricks` (`mode: "append"`, in design order). Keep every image path exactly as in the export (`images/hero.png`, `url(assets/bg.jpg)`).
3. The build replies' `feedback.assets` lists those paths and gives an `uploadUrl`. When the page is built, give the user the page link and that upload link: they drop the zip once, and every image is uploaded and placed by file name. Builds after that get the real URLs automatically.

Never type file bytes (base64) out yourself.

**1. Or send the zip to the server** when you can (the server then also uploads images, orders sections and cuts the passes for you):

- The user already uploaded it (SNN Settings > MCP > Design zip, or the media library) → `attachment_id`.
- The zip is online → `zip_url`.
- You can read the file with a tool and send it (Claude Code, a script) → `snn-bricks-upload-zip-part` in 2 MB parts, then `upload_id`; or `zip_base64` up to 25 MB.

**2. Start the import.** `snn-bricks-import-zip` with the source and `post_id`. Images go to the media library, SVGs and stylesheets are inlined, absolutely placed sections are put in render order (`orderNote` says what was checked), and the page is cut into small passes. You get `importId`, a `plan` (one row per pass) and `firstChunk`.

**3. Convert pass by pass.** For each pass: rewrite `markup` + `css` following `rules` (they come with the first pass), then call `snn-bricks-html-to-bricks` with `post_id`, `import_id`, `chunk`, `html`, `css`. The server places each pass in design order, whatever order you send them in. The reply's `feedback.next` names the next pass; fetch it with `snn-bricks-import-chunk`.

- Reply getting too long? `snn-bricks-import-chunk` with `split: true` halves the pass (`"3"` → `"3.0"`, `"3.1"`).
- A pass came out wrong? Send it again with `rebuild: true`: it replaces the earlier build in place.
- Progress at any time: `snn-bricks-import-chunk` with only `import_id`.

**Conversion rules** (also in `rules`): reproduce the design faithfully — same text, colours, fonts, order and images; never drop a section or invent content. Absolute positioning and fixed canvas sizes (`width: 1440px`, `position: absolute`, `left/top`) become normal flow (flex/grid, max-width containers). Drop generated wrappers and class names (`box-1`, `frame-1206`) and use the given class prefix. Remove canvas-only heights and spacer divs. Add responsive `@media` queries. Keep every image URL exactly as given.

**4. Finish.** When `feedback.next` says the import is complete, compare `pageSections` with the design, check `report.skipped` (fonts and videos are not imported: tell the user), and verify the page at desktop and 390px.

`compile: true` builds the export directly without conversion — only for exports that are already clean semantic HTML.

## SVG and Bricks settings

- A standalone `<svg>` (no `data-bricks` wrapper) becomes a native Bricks SVG element when the user may upload SVG. The upload is sanitised by Bricks. Without that permission the SVG stays as code.
- `snn-bricks-settings-get` reads Bricks global settings and the SVG upload permission of a user. `snn-bricks-settings-set` changes them: `settings` (Bricks keys, validated by Bricks) and `svg_upload: { enabled: true }`. Both need an admin key. Check the permission first when an SVG stays as code.
BRICKS_HTML_RULES;
