# How DocDraw works (user journey)

This page answers: **“What do I do here?”** in the order a real user would do it.

## 1) Understand what DocDraw is (30 seconds)
DocDraw is:
- a **document standard** (not an editor)
- designed for **AI drafts → deterministic final output**
- intended to work **locally** (no required cloud)

## 2) Download official artifacts (optional but recommended)
Go to [Downloads](downloads.md) and choose:
- **Spec release bundle v1.0** (snapshot of the standard)
- **Conformance bundle v1** (fixtures + goldens + harness)

Both are checksummed (SHA256) and verifiable offline.

## 3) Install the CLI (Phase 1 shim)
Follow [Installation](installation.md).

Verify:
```text
./bin/docdraw --help
```

## 4) Bring input (three common entry points)
You start from one of:
1) **DocDraw v1** source (hand-written or AI-generated)
2) **Strict Markdown (DMP-1)** output from AI
3) Existing Markdown that you adapt to DMP-1 rules

## 5) Convert (if you started from Markdown)
```text
./bin/docdraw convert --from dmp1 draft.md -o draft.docdraw
```

Ambiguous Markdown fails with explicit errors (no guessing).

## 6) Validate structure
```text
./bin/docdraw validate draft.docdraw
```

PASS means the document is structurally valid DocDraw v1.

## 7) Normalize (canonicalize)
```text
./bin/docdraw normalize draft.docdraw -o draft.normalized.docdraw
sha256sum draft.normalized.docdraw
```

This is a deterministic intermediate artifact that can already be used for:
- CI checks
- conformance verification
- reproducible builds

## 8) (Later) Render PDF (reference compiler)
Once the reference compiler exists:
```text
docdraw render draft.normalized.docdraw -o draft.pdf
```

The PDF will be deterministic and its `pdf_sha256` will be recorded in the manifest for golden outputs.

## The pipeline (mental model)
```text
AI / Markdown / Human Draft
        ↓
   DocDraw (canonical)
        ↓
 Validation + Normalization
        ↓
  Deterministic Compiler
        ↓
    Final Artifact (PDF)
```


