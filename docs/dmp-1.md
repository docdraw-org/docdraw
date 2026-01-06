# DocDraw Markdown Profile v1 (DMP-1)

**Status:** Stable Draft  
**Applies to:** Markdown → DocDraw conversion only  
**Canonical output:** DocDraw v1  

## 1. Purpose
The DocDraw Markdown Profile (DMP-1) defines a strict, deterministic interpretation of Markdown for the sole purpose of converting Markdown into valid DocDraw.

DMP-1 exists to:
- accept common Markdown as input
- eliminate ambiguity during conversion
- prevent silent structural guessing
- produce auditable, correct DocDraw output

DMP-1 does not attempt to define Markdown itself.

## 2. Scope and Non-Goals
### 2.1 In scope
- Headings
- Paragraphs
- Bullet lists
- Numbered lists
- Blockquotes
- Fenced code blocks

### 2.2 Explicit non-goals
DMP-1 does not attempt to:
- support all Markdown extensions
- preserve visual formatting fidelity
- accept HTML blocks
- guess list nesting
- act as a general Markdown renderer

Markdown is treated as **source input**, not a canonical format.

## 3. Base Specification
DMP-1 is based on **CommonMark**, with the constraints and overrides defined in this document.

Where DMP-1 rules conflict with CommonMark behavior, **DMP-1 rules take precedence**.

## 4. Accepted Markdown Constructs
### 4.1 Headings
Accepted:

```text
# Heading 1
## Heading 2
### Heading 3
```

Rules:
- Only ATX headings (`#`) are accepted
- Heading level maps directly:
  - `#` → `#1`
  - `##` → `#2`
  - etc.
- Heading text must be on a single line
- Trailing `#` characters are ignored

Rejected:
- Setext headings (`===`, `---`)
- Headings created via HTML

### 4.2 Paragraphs
Accepted:

```text
This is a paragraph.

This is another paragraph.
```

Rules:
- Paragraph boundaries are defined by one or more blank lines
- Soft line wrapping is ignored during conversion
- Paragraphs convert to:
  - `p:` for single-line
  - `p{}` for multi-line (if line breaks are intentional)

Rejected:
- Inline HTML affecting paragraph structure

### 4.3 Bullet Lists
Accepted:

```text
- Item
* Item
```

Rules:
- `-` and `*` are accepted as bullet markers
- Bullet marker choice is ignored during conversion
- All bullets convert to DocDraw `-L:` form

Nesting rules (critical): a nested list level MUST satisfy all of the following:
- Indented by **exactly 4 spaces per level**
- Immediately follows a parent list item
- Does not mix indentation widths within the same list

Example (valid):

```text
- Item
    - Sub item
        - Sub sub item
```

Example (invalid / ambiguous):

```text
- Item
  - Sub item   ← invalid (2 spaces)
```

Invalid nesting MUST produce a conversion error, not a guess.

### 4.4 Numbered Lists
Accepted:

```text
1. Item
2. Item
```

Rules:
- The numeric value is ignored during conversion
- Order is determined by position, not numbering
- All ordered lists convert to DocDraw `1-L:` form
- Nesting rules are identical to bullet lists:
  - Exactly 4 spaces per level
  - No mixed indentation
  - No inference

Rejected:
- Alphabetic or roman list markers
- Mixed bullet + ordered markers without clear structure

### 4.5 Mixed Lists
Allowed:

```text
- Item
    1. Sub item
    2. Sub item
```

Rules:
- Mixed list types are allowed only when nesting is unambiguous
- Each nested block must independently satisfy indentation rules
- Conversion preserves list type at each level

### 4.6 Blockquotes
Accepted:

```text
> Quoted text
```

Rules:
- Only single-level blockquotes supported in v1
- Nested blockquotes are rejected
- Blockquotes convert to `q:` blocks in DocDraw

Rejected:
- Multi-level `>>` nesting
- Blockquotes mixed with lists

### 4.7 Code Blocks
Accepted:

```text
````
code here
````
```

Rules:
- Only fenced code blocks are accepted
- Language identifiers (e.g. ```js) are ignored
- Code content is preserved verbatim
- Converts to `code{}` blocks

Rejected:
- Indented code blocks
- Inline code affecting structure

## 5. Explicitly Rejected Constructs
The following Markdown features are not supported in DMP-1:
- Tables
- HTML blocks or inline HTML
- Task lists (`- [ ]`)
- Footnotes
- Definition lists
- Emojis as structure
- Automatic link reference definitions
- Inline HTML (including inline HTML used for styling)

Note: DocDraw v1 supports minimal inline emphasis markers (`**` / `*` / `++` / `` ` ``). DMP-1 treats these as text-level semantics and passes them through during conversion.

If encountered:
- Conversion MUST fail
- Error MUST cite exact line number and reason

## 6. Whitespace Rules
- Tabs are treated as invalid indentation
- Only spaces are allowed
- Trailing whitespace is ignored
- Blank lines inside lists are not permitted
- Mixed indentation styles within a list are invalid

## 6.1 Common edge cases (with outcomes)
### Ambiguous list nesting (FAIL)

```text
- Item
  - Sub item
```

Reason: indent increases by 2 spaces, not 4.

### List nesting with 4 spaces (PASS)

```text
- Item
    - Sub item
        - Sub sub item
```

### Tabs used for indentation (FAIL)

```text
- Item
	- Sub item
```

Reason: tabs are invalid indentation in DMP-1.

### Blockquote mixed with lists (FAIL in v1)

```text
> Quote
> - Item
```

Reason: v1 rejects blockquotes mixed with lists (avoid ambiguous structure).

## 7. Conversion Behavior
### 7.1 No Silent Guessing
The converter MUST never:
- infer nesting
- adjust indentation
- reinterpret structure

If structure is ambiguous:
- Conversion fails
- User must resolve explicitly

### 7.2 Error Reporting
Errors MUST include:
- line number
- error code
- human-readable explanation

Example:

```json
{
  "line": 14,
  "code": "AMBIGUOUS_LIST_INDENT",
  "message": "List indentation must increase by exactly 4 spaces per level."
}
```

## 8. Conversion Output Guarantees
If Markdown successfully converts under DMP-1:
- Output DocDraw is valid
- Output DocDraw is deterministic
- Rendering via a compliant renderer is guaranteed correct

If conversion succeeds, correctness is guaranteed downstream.

## 9. Relationship to DocDraw
- Markdown is input only
- DocDraw is the canonical format
- All guarantees begin at DocDraw, not Markdown
- Markdown compatibility does not imply rendering guarantees

## 10. Versioning
DMP-1 applies only to DocDraw v1.

Future profiles may:
- expand accepted syntax
- tighten rules
- add explicit extensions

Profiles are versioned and immutable once published.

## 11. Design Rationale (Non-Normative)
Markdown is intentionally flexible.  
DocDraw is intentionally strict.  

DMP-1 exists to bridge that gap without compromising correctness.

**One-sentence summary (public-facing):** DocDraw accepts Markdown as input, but only guarantees correctness after it has been compiled into DocDraw.

## Appendix A: Guidance for AI output (practical)
If you want GPT (or any generator) to reliably target DMP-1:
- Prefer **ATX headings** (`#`, `##`, `###`)
- Use **exactly 4 spaces** per list nesting level
- Avoid unsupported constructs (tables, HTML, task lists)
- Keep lists tight (no blank lines inside list blocks)
- When in doubt, generate **DocDraw v1 directly** (canonical), not Markdown


