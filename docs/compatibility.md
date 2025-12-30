# Compatibility & Stability Policy

DocDraw is a standard. Standards are trusted when implementers know **what can change** and **what will not**.

This page defines the compatibility policy for:
- **DocDraw language versions** (e.g. DocDraw v1)
- **Renderer profiles** (e.g. `DD-PDF-1`)
- **Markdown input profiles** (e.g. `DMP-1`)

## Versioning model
DocDraw uses a SemVer-like policy for compatibility:

- **DocDraw language version**: `DD-<major>.<minor>` (published as “DocDraw v<major>.<minor>”)
- **Renderer profile**: `DD-PDF-<major>` (and future profiles like `DD-PPTX-<major>`)
- **Markdown profile**: `DMP-<major>`

## What counts as breaking
### DocDraw language (breaking)
Any change that changes the meaning of previously valid DocDraw input is breaking, including:
- a grammar change that makes previously valid input invalid (without a major bump)
- changing how list levels are interpreted
- changing the meaning of a token or block type

### Renderer profile (breaking)
Any change that changes the rendered output for the same valid DocDraw input in a way not explicitly permitted is breaking, including:
- different indentation behavior for lists
- different continuation line alignment
- different pagination rules that change layout deterministically
- different font metrics / bundling choices that change layout (unless introduced as a new profile)

### DMP-1 (breaking)
Any change that changes the meaning of Markdown that previously converted successfully is breaking, including:
- changing the conversion result for the same Markdown input
- accepting previously rejected ambiguous structures without an explicit profile bump

## What is non-breaking
These changes are non-breaking:
- adding new documentation, clarifications, and examples
- adding new examples/fixtures that do not change semantics
- adding new optional features guarded by a new DocDraw **minor** version (and not changing v1 meaning)
- tightening validation only where behavior was previously **undefined or ambiguous** (and documenting the rule)

## Renderer profiles and language versions
Renderer profiles are intentionally decoupled from the language version:
- DocDraw v1 defines **structure**
- `DD-PDF-1` defines **default PDF output contract** for DocDraw v1

Future evolution examples:
- DocDraw v1.1 may clarify edge cases while staying compatible with v1.0 meaning
- `DD-PDF-2` may introduce a new rendering contract (fonts/spacing/pagination changes) without changing DocDraw v1

## Deprecation & transition policy
When a breaking change is necessary:
- **announce** intent in the changelog
- provide an **overlap** window where both old and new versions/profiles can be targeted
- **remove** old behavior only in a major version bump

## Determinism as a compatibility requirement
DocDraw’s credibility depends on determinism:
- the same input must produce the same normalized output
- the same input + same renderer profile must produce the same PDF

If determinism changes, that is compatibility-significant and must be versioned explicitly.


