# Contributing

Thanks for helping improve the DocDraw standard and its conformance materials.

## Ground rules
- Keep changes spec-like: clear, deterministic, and testable.
- Avoid “best effort” behavior. Prefer **fail, don’t guess**.
- Compatibility matters. See: `docs/compatibility.md`.

## Adding or updating examples
Examples are driven by the manifest:
- `examples/golden-manifest.json` (source of truth)
- `examples/source/` (inputs)
- `assets/examples/` (goldens: normalized today; PDFs later)

Workflow:
1) Add source files under `examples/source/`
2) Add/modify the entry in `examples/golden-manifest.json`
3) Run:

```bash
make examples-update
make examples-check
```

Notes:
- PASS examples should include a DocDraw source (`source.docdraw`).
- FAIL examples must include `expected_result.expected_error_codes[]`.
- Do not hand-edit `docs/examples.md` (it is generated).

## Proposing spec changes
- Make a focused change in `docs/`
- Update or add examples as needed
- Keep backwards compatibility unless you are explicitly bumping versions/profiles

## Adding new error codes
- Add the code to `docs/error-codes.md`
- Use stable naming (`DDV1_...`, `DMP1_...`)
- Add at least one FAIL example that exercises the code


