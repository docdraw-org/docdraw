# DD-IR-1: DocDraw Intermediate Representation (JSON)

DD-IR-1 is a **JSON intermediate representation** for DocDraw documents.

- **DocDraw v1 (text)** remains the canonical authoring format.
- **DD-IR-1 (JSON)** is a compiler/editor-friendly tree for determinism, tooling, and stable round-tripping.

This document specifies the **minimum required** DD-IR-1 surface area for:
- DocEdit-style structured editing (Editor mode)
- deterministic compilation targets (e.g. DD-PDF-1)
- precise diagnostics (source mapping)

## 1) Goals / non-goals

### Goals
- **Deterministic structure**: identical DocDraw input parses to an identical IR (modulo stable IDs; see below).
- **Unambiguous lists**: list type and nesting are explicit (no “indent guessing”).
- **Minimal inline model**: inline spans reflect DocDraw v1 emphasis + code.
- **Source mapping**: nodes can refer back to specific source line ranges.

### Non-goals
- DD-IR-1 is **not** intended to be human-authored.
- DD-IR-1 is **not** a rich-text format (no fonts, sizes, colors, arbitrary styling).

## 2) Top-level shape

### Required fields

```json
{
  "schema": "DD-IR-1",
  "version": "1.0",
  "doc": {
    "blocks": []
  }
}
```

- **`schema`**: MUST be `"DD-IR-1"`.
- **`version`**: MUST be `"1.0"` for this spec.
- **`doc.blocks`**: array of block nodes in document order.

### Optional fields
- **`doc.meta`**: arbitrary metadata map for future use (MUST NOT affect rendering).

## 3) IDs and source mapping

### `id`
- Nodes MAY include **`id`** (string).
- If present, IDs MUST be stable for a given input and parse (recommended: deterministic hash of `(kind, source span, content)`).
- IDs are intended for editor diffing and diagnostics; renderers MUST NOT depend on specific ID values.

### `src`
Nodes MAY include a **`src`** object describing where the node came from in the original DocDraw text.

```json
{
  "src": { "path": "optional/path.docdraw", "line_start": 12, "line_end": 12 }
}
```

- **`line_start` / `line_end`** are 1-based and inclusive.
- `path` is optional (useful when the source was loaded from a file).

## 4) Block model

DD-IR-1 defines a small set of block kinds corresponding to DocDraw v1.

### 4.1 Heading

```json
{ "type": "heading", "level": 1, "inlines": [ { "type": "text", "text": "Title" } ] }
```

- **`level`**: integer 1–6 (matches `#1:`..`#6:`).

### 4.2 Paragraph

```json
{ "type": "paragraph", "inlines": [ { "type": "text", "text": "Hello" } ] }
```

### 4.3 Divider

```json
{ "type": "divider" }
```

### 4.4 Blockquote

```json
{ "type": "blockquote", "blocks": [ { "type": "paragraph", "inlines": [ { "type": "text", "text": "Quote" } ] } ] }
```

### 4.5 Code block

```json
{ "type": "code_block", "lang": null, "text": "const x = 1;\n" }
```

- **`lang`** optional (null if unknown).
- `text` is raw text (no inline parsing).

### 4.6 List

Lists are explicit containers. Nesting is represented via `level` on items.

```json
{
  "type": "list",
  "style": "bullet",
  "items": [
    { "type": "list_item", "level": 1, "blocks": [ { "type": "paragraph", "inlines": [ { "type": "text", "text": "Item" } ] } ] },
    { "type": "list_item", "level": 2, "blocks": [ { "type": "paragraph", "inlines": [ { "type": "text", "text": "Nested" } ] } ] }
  ]
}
```

#### `style`
`style` MUST be one of:
- `"bullet"` (DocDraw `-L:`)
- `"number"` (DocDraw `1-L:`)
- `"alpha_lower"` (DocDraw `a-L:`)
- `"alpha_upper"` (DocDraw `A-L:`)

#### `level`
- `level` MUST be a positive integer (1 = top-level).
- `level` corresponds to the `L` in DocDraw list tags.

## 5) Inline model

Inline nodes reflect DocDraw v1 inline spans (`**bold**`, `*italic*`, `++underline++`, `` `code` ``).

### 5.1 Text

```json
{ "type": "text", "text": "hello" }
```

### 5.2 Bold / Italic / Underline

```json
{ "type": "strong", "inlines": [ { "type": "text", "text": "bold" } ] }
{ "type": "em", "inlines": [ { "type": "text", "text": "italic" } ] }
{ "type": "underline", "inlines": [ { "type": "text", "text": "underlined" } ] }
```

### 5.3 Inline code

```json
{ "type": "code", "text": "x + 1" }
```

### Inline constraints (to match DocDraw v1)
- Inline spans MUST NOT overlap.
- Nesting SHOULD be rejected or normalized away (DocDraw v1 forbids nesting).
- For `code`, inner content is raw text (no nested inlines).

## 6) Determinism requirements

An implementation claiming DD-IR-1 MUST:
- parse valid DocDraw v1 into the same block structure every time
- preserve list `style` and `level` exactly (no “Markdown-style” reflow)
- preserve inline span boundaries as specified by DocDraw v1

If the input contains unknown/unhandled lines, an implementation SHOULD either:
- reject during validation, OR
- emit a raw/passthrough block type (not specified here; optional extension)

## 7) Example (DocDraw → DD-IR-1)

DocDraw:

```text
#1: Example
p: This is **bold** and *italic* and ++underlined++ and `code`.

-1: First
-2: Nested
a-1: Alpha item
```

DD-IR-1 (abridged):

```json
{
  "schema": "DD-IR-1",
  "version": "1.0",
  "doc": {
    "blocks": [
      { "type": "heading", "level": 1, "inlines": [ { "type": "text", "text": "Example" } ] },
      {
        "type": "paragraph",
        "inlines": [
          { "type": "text", "text": "This is " },
          { "type": "strong", "inlines": [ { "type": "text", "text": "bold" } ] },
          { "type": "text", "text": " and " },
          { "type": "em", "inlines": [ { "type": "text", "text": "italic" } ] },
          { "type": "text", "text": " and " },
          { "type": "underline", "inlines": [ { "type": "text", "text": "underlined" } ] },
          { "type": "text", "text": " and " },
          { "type": "code", "text": "code" },
          { "type": "text", "text": "." }
        ]
      },
      {
        "type": "list",
        "style": "bullet",
        "items": [
          { "type": "list_item", "level": 1, "blocks": [ { "type": "paragraph", "inlines": [ { "type": "text", "text": "First" } ] } ] },
          { "type": "list_item", "level": 2, "blocks": [ { "type": "paragraph", "inlines": [ { "type": "text", "text": "Nested" } ] } ] }
        ]
      },
      {
        "type": "list",
        "style": "alpha_lower",
        "items": [
          { "type": "list_item", "level": 1, "blocks": [ { "type": "paragraph", "inlines": [ { "type": "text", "text": "Alpha item" } ] } ] }
        ]
      }
    ]
  }
}
```

## 8) Relationship to DD-CLI-1

- **DD-CLI-1** is an *engine execution contract* (validate/normalize/render) and includes diagnostics and output paths/hashes.
- **DD-IR-1** is a *document structure contract* suitable for editors and compilers.

They are complementary: DD-CLI-1 can embed DD-IR-1 in the future, or the CLI can add a separate `parse` command that outputs DD-IR-1.

