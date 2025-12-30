# Error codes registry

DocDraw tools MUST use stable error codes so humans and tools (including LLM workflows) can fix issues deterministically.

## Naming conventions
- Prefer a stable prefix that indicates scope/version.
  - `DDV1_...` for DocDraw v1 validation errors
  - `DMP1_...` for DMP-1 conversion errors
- Codes should be:
  - UPPER_SNAKE_CASE
  - stable once published

## Stages
Error codes are grouped by the stage that emits them:
- `docdraw_parse`
- `docdraw_validation`
- `dmp1_parse`
- `dmp1_translation`

## docdraw_validation
| Code | Meaning | How to fix |
|---|---|---|
| `DDV1_LIST_LEVEL_JUMP` | List level jumped by more than 1 (e.g. `-1` → `-3`). | Change the item to `-2` (or add an intervening `-2` item) so nesting is explicit and valid. |

## dmp1_translation
| Code | Meaning | How to fix |
|---|---|---|
| `AMBIGUOUS_LIST_INDENT` | List indentation does not increase by exactly 4 spaces per level. | Use exactly 4 spaces for each nesting level, and never use tabs. |

## Reserved / TBD
The following namespaces are reserved for future use:
- `DDV1_...` (DocDraw v1 parse/validation)
- `DMP1_...` (DMP-1 parse/translation)
- `DDPDF1_...` (DD-PDF-1 rendering issues, if needed)

## Notes
- Error codes are part of the “spec surface.” Changing meanings is compatibility-significant.
- Prefer **fail, don’t guess**: ambiguity should produce an error, not a best-effort interpretation.


