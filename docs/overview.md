# What is DocDraw?

DocDraw exists because “simple documents” (paragraphs + bullets + nested bullets) are deceptively hard to make **reliably correct** across tools — and in the AI era, drafts are easy but **final output** still fails in frustrating ways.

## The workflow (high level)
```text
Draft → Validate → Normalize → Compile → Final PDF
```

Start here:
- [How DocDraw works](how-it-works.md)
- [Quickstart (5 minutes)](quickstart.md)
- [Installation](installation.md)
- [Downloads](downloads.md)

## The problem (what users actually experience)
- **PDF is a final layout format**: editing it directly is fragile (reflow, broken text runs, broken lists).
- **AI drafts aren’t reliably portable**: copy/paste into Word/Docs often breaks structure (especially sub-bullets and indentation).
- **Markdown is not a contract**: different tools interpret indentation, wrapping, and list nesting differently.
- **Trust matters**: people don’t want to upload important docs to random websites, and enterprise PDF suites are overkill for occasional-but-important edits.

## The idea (treat documents like build artifacts)
DocDraw treats documents like software artifacts:

- **Markdown (optional input)** → strict conversion (**DMP-1**) → **DocDraw (canonical structure)** → deterministic **PDF rendering**

Key idea: **Markdown is input convenience; DocDraw is the guarantee.**

## What DocDraw guarantees (v1)
If a document is valid DocDraw v1:
- list nesting is explicit and cannot “accidentally change”
- rendering is deterministic (same input → same output)
- validators can fail loudly with actionable errors (no guessing)

In other words:
- **Draft generation** can be probabilistic (AI)
- **Final output** must be deterministic (DocDraw + compiler)

## What DocDraw is *not* (v1)
- not a full Word/Docs replacement
- not a PDF editor
- not “import arbitrary PDFs and edit them perfectly”

## Next
- Start with the quickstart: [Quickstart (5 minutes)](quickstart.md)
- Read the canonical format: [DocDraw v1](docdraw-v1.md)
- If your input is Markdown, use: [DMP-1](dmp-1.md)


