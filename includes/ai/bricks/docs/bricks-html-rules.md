# Building Bricks pages from HTML (SNN-BRX)

You write HTML + CSS. The server compiles it into native Bricks elements with the same compiler the Bricks AI agent uses in the builder:

- every CSS class becomes a Bricks **global class**, its CSS kept verbatim;
- the layout part of the cascade (display, flex, grid, gap, width, max-width) is resolved per breakpoint and written as **native element settings**, so the result matches a browser preview of your HTML;
- headings, text, buttons, links, images, icons, dividers and SVGs become their **native Bricks elements**.

## Recommended call order

1. `snn-bricks-start-here` (this) — once per session.
2. `snn-bricks-get-page-tree` with `post_id` — read the page, note `documentDigest` and `lockedBy`.
3. Optional: `snn-bricks-get-global-classes` — reuse existing class names and palette.
4. Images: send public URLs directly in `src`, or upload first with `snn-bricks-upload-asset` and use the returned URL. A design-export `.zip` goes to `snn-bricks-import-zip` (see "Design exports" below).
5. `snn-bricks-html-to-bricks` with `html`, `css`, `mode`, `expected_digest` (from step 2) and an `idempotencyKey`. Use `dry_run: true` to inspect the compiled elements first.
6. Read `feedback` in the reply (see "The feedback loop"), fix what it reports, and use the returned `documentDigest` for the next write.
7. Small changes later: `snn-bricks-find-elements` → `snn-bricks-edit-elements`. Rebuild one section: `snn-bricks-html-to-bricks` with `mode: "replace-section"` and `section_id`.
8. Verify: open `permalink` in a browser (desktop and 390px) when you can; `feedback.render` and `snn-bricks-render-summary` are the server-side check.

Modes: `append` (default), `prepend`, `replace` (whole page), `insert-after` / `insert-before` (+ `anchor_id`, a top-level id), `replace-section` (+ `section_id`).

## The feedback loop

Every write — and every `dry_run` — of `snn-bricks-html-to-bricks` and `snn-bricks-edit-elements` returns `feedback`:

- `changes` — elements added, removed and changed, with the changed setting keys. Check that it matches what you meant to change and nothing else.
- `outline` — the affected sections as `[id] type .classes "text"`. Use these ids for follow-up edits.
- `pageSections` — the whole page's top-level order; affected sections are flagged. Check the new sections landed where they belong.
- `checks` — heading outline problems, empty text, images without a source, buttons without a link, and canvas-export leftovers (`position:absolute`, 4-digit pixel widths) in the classes used.
- `render` — Bricks' own server render: landmarks, responsive CSS present, CSS syntax errors (builds by default; edits with `verify: true`).
- `next` — what to do next.

Work in small steps: build or change one thing, read `feedback`, correct, continue. Do not report a page as finished while `checks.issues` lists problems you can fix.

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
  - `data-bricks="icon"` — FontAwesome `<i class="fas fa-star">` (fas, fab; avoid far)
  - `data-bricks="divider"` — `<hr>` and decorative line divs
  - `data-bricks="custom-html-css-script"` — raw HTML kept verbatim: iframes, video embeds, forms, tables
- Any tag the list does not name (form, table, video, input, details …) is preserved verbatim.
- Icons in buttons and links: ONE FontAwesome `<i>` or ONE inline `<svg>` directly inside the button/link, at the start or end of the label. It becomes the button's native icon (side, size, colour, gap). No other icon fonts (Lucide, Material Symbols, Bootstrap Icons).
- Standalone SVG graphics (logos, illustrations): write the `<svg>` directly with no data-bricks wrapper — it is uploaded and becomes a native Bricks SVG element (needs Bricks SVG upload permission; otherwise it stays as code).
- Links keep their real `href` (`#pricing`, `/contact`, `https://…`); `target="_blank"` opens a new tab.
- Use `class="…"` only. No inline `style=""`.
- Real content. No Lorem Ipsum.

## CSS rules

- All styling as CSS classes, in `css` or a `<style>` block. Any property, `:hover`, `@keyframes`, `@media` works.
- Write plain standard CSS and assume browser behaviour. Descendant and child selectors (`.card p`, `.grid > div`) are resolved for real; elements keep their real tags.
- One property per line.
- `:root` custom properties with concrete values (hex / rgb / px), never `var()` as the value. Colour and size variables are added to the Bricks palette / global variables when the names are new.
- Google Fonts via `@import url(...)` at the top of the CSS.
- Class naming: one word per section, hyphenated children: `.hero`, `.hero-container`, `.hero-title`, `.features-grid`, `.features-card`. Prefix with a page or section word when the site already has generic names. A class that already exists site-wide with identical CSS is reused; with different CSS it is renamed (`-1`, `-2`) automatically and the response lists the renames.
- Responsive: use the exact `@media` queries listed under "This site's breakpoints" below, so they map onto Bricks breakpoints. Stack multi-column rows on mobile.
- Keep primary content in normal flow: no absolute positioning, fixed canvas widths or negative offsets for layout.

## Query loops (listing posts)

1. Grid wrapper block (`display: grid`, no data-loop)
2. Loop block: `data-loop="post_type_slug" data-loop-posts-per-page="6"` (optional `data-loop-orderby`, `data-loop-order`)
3. One template card inside — Bricks repeats it. Dynamic tags: `{post_title}`, `{post_excerpt}`, `{post_date}`, `{post_link}`, `{featured_image}`, `{cf_FIELDNAME}`.

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
