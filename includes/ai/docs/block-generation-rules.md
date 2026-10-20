# Block content rules (snn/create-post, snn/update-post)

Post content is stored as HTML and converted to block editor blocks. Broken HTML becomes "This block contains unexpected or invalid content" errors, so every write must follow these rules.

## Structure

- Close every tag: `<p>…</p>`, `<h2>…</h2>`, `<li>…</li>`.
- Top-level content is a flat list of block elements: `h2`–`h6`, `p`, `ul`, `ol`, `blockquote`, `figure`, `table`, `pre`, `hr`.
- Never put text directly at the top level. Every run of text sits in a `<p>`.
- Never nest block elements inside `<p>` (no `<p><h2>…</h2></p>`, no `<p><ul>…</ul></p>`).
- Lists: `<ul><li>Item</li></ul>` or `<ol><li>Item</li></ol>`. Only `<li>` directly inside a list; nested lists go inside an `<li>`.
- No empty elements: remove `<p></p>`, `<p>&nbsp;</p>`, `<li></li>`.
- Headings: start at `<h2>` (the post title is the `<h1>`) and do not skip levels.

## Inline formatting

- Allowed inside `p`, `li`, headings: `strong`, `em`, `a`, `code`, `br`, `sup`, `sub`, `mark`.
- Links: `<a href="https://…">text</a>`; real URLs only, no `javascript:`.

## Media

- Images: `<figure><img src="URL" alt="Description"></figure>`, optionally with `<figcaption>`. Always write `alt`.
- Use media library URLs (upload with snn-bricks-upload-asset first) or stable public URLs.

## Not allowed

- `<script>`, `<style>`, `<iframe>` and inline `style=""`: they are stripped (content passes through `wp_kses_post`).
- Raw block comments (`<!-- wp:… -->`) unless you write complete, valid block markup.

## Bricks pages

Pages built with Bricks keep their layout in Bricks data, not in post content. Use the `snn-bricks-*` tools for those (start with `snn-bricks-start-here`).
