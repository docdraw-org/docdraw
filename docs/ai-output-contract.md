# AI output contract

This page defines the practical contract for AI-generated output targeting DocDraw.org.

DocDraw supports two “AI output targets”:
- **DocDraw v1** (canonical; preferred when correctness matters)
- **DMP-1** Markdown (input-only convenience; strict and failure-prone if indentation is wrong)

## Target A: DocDraw v1 (preferred)
### Rules (MUST / MUST NOT)
- MUST use only defined DocDraw v1 blocks (see: `docdraw-v1.md`)
- MUST use explicit list levels (`-1:`, `-2:`, `1-1:`, etc.)
- MUST NOT infer nesting via spaces/indentation in DocDraw (DocDraw nesting is explicit via levels)
- MUST ensure continuation lines (`..:`) follow a list item
- MUST keep the document deterministic (no “best effort” structure)

### Good example
```text
#1: Title
p: Short paragraph.

-1: Top bullet
-2: Sub bullet
-1: Next top bullet
..: Continuation line for the prior list item
```

### Bad examples
Indentation-based nesting (not allowed in canonical DocDraw):
```text
-1: Top bullet
    -2: Wrong (leading spaces are meaningless in DocDraw)
```

Continuation without a list item:
```text
..: This is invalid because it has no preceding list item
```

## Target B: DMP-1 Markdown (allowed, strict)
### Rules (MUST / MUST NOT)
- MUST follow CommonMark baseline, with DMP-1 overrides (see: `dmp-1.md`)
- MUST indent nested list items by **exactly 4 spaces per level**
- MUST NOT use tabs for indentation
- MUST NOT include unsupported constructs (tables, HTML, task lists, etc.)
- MUST fail rather than guess when indentation is ambiguous

### Good example (DMP-1)
```text
# Title

- Item
    - Sub item
        - Sub sub item
```

### Bad example (FAIL)
```text
- Item
  - Sub item
```

Expected: FAIL with `AMBIGUOUS_LIST_INDENT`.

## Recommended prompt snippets (practical)
### Emit DocDraw v1
```text
Output MUST be valid DocDraw v1 only.
Use explicit list levels (-1:, -2:, 1-1:, etc.). Do not use indentation to imply nesting.
If unsure, stop and ask a clarifying question rather than guessing structure.
```

### Emit DMP-1 Markdown
```text
Output MUST be valid DMP-1 Markdown only.
All nested list items MUST be indented by exactly 4 spaces per level. Never use tabs.
Do not use tables, HTML, or task lists. If something can’t be expressed, ask a question.
```


