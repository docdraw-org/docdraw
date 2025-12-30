# DocDraw v1 (Canonical Document Format)

This document defines the **canonical, deterministic document format** used by DocDraw/DocEdit.

Note: The original drafting conversation used a different working name early on. **DocDraw v1** is the canonical name; the syntax below matches those original tokens.

## Status: feature-complete (frozen)
**DocDraw v1 is feature-complete.** The goal now is to build and harden the **reference PDF compiler** against the conformance suite.

Any future changes to the language (beyond clarifications/examples) should go to:
- **v1.1+** (compatible clarifications / extensions), or
- **v2 proposals** (breaking changes)

## 1. Design Goals
- Deterministic: same input → same output
- Explicit structure: no “indentation implies nesting” in the canonical format
- Validatable: invalid structure fails loudly with actionable errors

## 2. Document Model
A document is a sequence of **blocks**, one per line (except for `{}` blocks).

Anything that does not match a valid block is an error.

## 3. Block Types
### 3.1 Headings
Syntax:
- `#N: Heading text` where `N` is `1..6`

Examples:

```text
#1: Service Agreement
#2: Scope
```

### 3.2 Paragraphs
Single-line paragraph:
- `p: text`

Multi-line paragraph:
- `p{` then content lines then `}`

Example:

```text
p: This agreement is made between Acme LLC and Customer Inc.

p{
This agreement is made between Acme LLC and Customer Inc.
Payment is due within 15 days of invoice date.
}
```

### 3.3 Explicit line breaks (only inside `p{}`)
Inside `p{}` content you may use:
- `br`

Example:

```text
p{
Line one
br
Line two
}
```

### 3.4 Bullet list items (explicit nesting)
Syntax:
- `-L: text` where `L` is `1..9`

Validation rule:
- List level MUST increase by at most 1 between adjacent list items (e.g. `-1` → `-2` is valid; `-1` → `-3` is invalid).

Example:

```text
-1: Primary bullet
-2: Sub bullet A
-2: Sub bullet B
-1: Another primary bullet
```

### 3.5 Numbered list items (explicit nesting)
Syntax:
- `1-L: text` where `L` is `1..9`

Example:

```text
1-1: First item
1-2: Sub item
1-1: Second item
```

### 3.6 Continuation lines (for list items)
Syntax:
- `..: text`

Rule:
- A continuation MUST follow a list item and belongs to that prior list item.

Example:

```text
-1: This is a long bullet that starts here
..: and continues here without starting a new bullet.
..: and continues again.
```

### 3.7 Divider / section break
Syntax:
- `---`

### 3.8 Blockquote (optional in v1)
Syntax:
- `q: text`

### 3.9 Code block (optional in v1)
Syntax:
- `code{` then content lines then `}`

## 4. Minimal Grammar (Validation Baseline)
These rules are intended to catch the majority of structure errors:

```text
#N: where N is 1–6
p: single-line paragraph
p{ ... } multi-line paragraph
-L: bullet item, L is 1–9
1-L: numbered item, L is 1–9
..: continuation (must follow a list item)
--- divider
br only allowed inside p{}
Everything else = error with a clear message.
```

## 5. Relationship to Markdown (DMP-1)
- Markdown is an input convenience.
- DocDraw is the canonical, validated structure.
- Markdown conversion MUST be strict (see `docs/dmp-1.md`).


