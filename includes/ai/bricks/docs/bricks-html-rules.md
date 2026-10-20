# Building Bricks pages from HTML (SNN-BRX)

You write HTML + CSS. The server compiles it into native Bricks elements with the same compiler the Bricks AI agent uses in the builder:

- every CSS class becomes a Bricks **global class**, its CSS kept verbatim;
- the layout part of the cascade (display, flex, grid, gap, width, max-width) is resolved per breakpoint and written as **native element settings**, so the result matches a browser preview of your HTML;
- headings, text, buttons, links, images, icons, dividers and SVGs become their **native Bricks elements**.

## Recommended call order

1. `snn-bricks-start-here` (this) — once per session.
2. `snn-bricks-get-page-tree` with `post_id` — read the page, note `documentDigest` and `lockedBy`.
3. Optional: `snn-bricks-get-global-classes` — reuse existing class names and palette.
4. Images: send public URLs directly in `src`, or upload first with `snn-bricks-upload-asset` and use the returned URL. A design-export `.zip` goes to `snn-bricks-import-zip`.
5. `snn-bricks-html-to-bricks` with `html`, `css`, `mode`, `expected_digest` (from step 2) and an `idempotencyKey`. Use `dry_run: true` to inspect the compiled elements first.
6. Small changes later: `snn-bricks-find-elements` → `snn-bricks-edit-elements`. Rebuild one section: `snn-bricks-html-to-bricks` with `mode: "replace-section"` and `section_id`.
7. Verify: open `permalink` in a browser (desktop and 390px) when you can; otherwise `snn-bricks-render-summary`.

Modes: `append` (default), `prepend`, `replace` (whole page), `insert-after` (+ `anchor_id`, a top-level id), `replace-section` (+ `section_id`).

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

## Cleaning design exports (zip from Figma, Penpot, Webflow …)

`snn-bricks-import-zip` returns the export's HTML and CSS with images already in the media library. Exports are canvas markup; before building, rewrite it:

- Absolute positioning and fixed pixel canvas widths (`width: 1440px`, `position: absolute`, `left/top`) become normal flow: flex/grid, max-width containers, percentage or auto widths.
- Drop meaningless wrapper divs and generated class names (`box-1`, `frame-1206`); rename classes semantically.
- Remove fixed heights that only fitted the canvas, and empty spacer divs.
- Add responsive `@media` queries — exports have none.
- Keep every text and every image URL exactly as given. Never drop a section. Check the section order: it is not re-sorted by visual position.
- Large exports: convert and send a few sections per call with `mode: "append"`.
