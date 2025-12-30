# Why not just use…?

DocDraw is not trying to replace everything. It exists to make “simple business docs” **reliably correct** when formatting matters.

## Why not PDF editors?
PDF is a final layout format. Editing text and lists in PDFs is fragile:
- list structure isn’t semantic
- text reflow breaks easily
- “small edits” often become layout surgery

DocDraw avoids editing PDFs; it generates them deterministically from structure.

## Why not Google Docs / Word?
They’re great for collaborative drafting, but the pain shows up at the boundary:
- copy/paste from AI often breaks nested bullets
- consistent deterministic output is not guaranteed across environments and export paths

DocDraw is the “final-output contract” layer.

## Why not plain Markdown?
Markdown is excellent for drafts, but it’s not a single contract:
- multiple dialects and renderer differences
- whitespace and list nesting are ambiguous

DocDraw can accept Markdown via **DMP-1**, but guarantees begin at DocDraw (canonical).

## Where DocDraw fits
Best workflow:
- AI generates drafts → DocDraw (or strict DMP-1) → validate → compile to PDF

DocDraw is for the last mile: **determinism, validation, trust**.


