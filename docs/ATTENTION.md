# DocDraw v1 attention summary

DocDraw v1 is **pre-release (not frozen yet)**. This page highlights important design decisions that affect implementers.

## Naming
- Confirmed canonical name: **DocDraw v1**

## PDF defaults
DocDraw v1 defines structure; PDF rendering defaults are part of **DD-PDF-1** (renderer profile) and the reference compiler implementation.

Items that were explicitly deferred to DD-PDF-1:
- paper size defaults (Letter vs A4 setting)
- default font family (e.g. Source Serif 4 vs Inter)
- ordered list marker format
- bullet glyph selection for deep nesting

## Inline styling
DocDraw v1 supports minimal, deterministic inline styling:
- `**bold**`
- `*italic*`
- `++underline++`
- `` `code` ``

## Scope confirmation
Confirmed for DocDraw v1:
- excludes tables and images as first-class constructs
- focuses on deterministic structure for paragraphs + lists + sections

Notes:
- Blockquotes (`q:`) and code blocks (`code{}`) exist as optional blocks in the docs, but are not the MVP focus. Implementations may support them, but the primary conformance emphasis in v1 is lists/paragraphs determinism.


