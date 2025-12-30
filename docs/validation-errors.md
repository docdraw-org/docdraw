# Validation errors

DocDraw tools should **fail invalid structure loudly**, with errors that are easy to fix.

## Error design goals
- include line number (and column if available)
- include a stable error code
- include a short message and a “how to fix”
- never “guess” structure to make validation pass

## Example: invalid DocDraw list level jump

Input:

```text
-1: Top level
-3: Skips level 2
```

Suggested validator output:

```json
{
  "line": 2,
  "code": "LIST_LEVEL_JUMP",
  "message": "List level jumped from 1 to 3; did you mean -2?"
}
```

## Example: Markdown (DMP-1) ambiguous indent

Input:

```text
- Item
  - Sub item
```

Suggested converter output:

```json
{
  "line": 2,
  "code": "AMBIGUOUS_LIST_INDENT",
  "message": "List indentation must increase by exactly 4 spaces per level."
}
```

## Next
- Define the full error code list (v1) and add “bad → fix → good” examples.
- Add a small conformance test suite of pass/fail fixtures.


