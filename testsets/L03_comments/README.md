# L03 — Comment Variation (Type-1)

**What it tests:** comment stripping — the semantic token stream is identical to L1 but comments
have been added, removed, or altered in various ways.

This level probes a fundamental weakness in naive token-based detectors: they treat comments as
significant content. Families cover the full spectrum of comment manipulation — from simple line
comments added/removed to docblocks, inline trailing comments, mid-statement comments, license
headers, and annotations. The `cm_combined` family stacks whitespace variation on top of comment
changes for extra interference.

Token-based detectors that strip comments will handle most of these correctly. The challenge is
deciding what counts as a comment worth stripping vs. structural content, and handling the
`cm_combined` case where whitespace normalization must be layered on top of comment handling.

## Families

| Family | Interference | Description |
|--------|-------------|-------------|
| cm_line_added | code | Lines of comment added in one carrier but not the other |
| cm_block_added | code | Multi-line block comments added in one carrier |
| cm_docblock | code | PHPDoc-style docblocks added/removed/altered |
| cm_inline_trailing | code | Trailing inline comments on code lines |
| cm_removed | code | Comment lines present in original but stripped from clone |
| cm_text_changed | code | Comment text content differs without structural change |
| cm_commented_code | code | Code that was commented out in one carrier |
| cm_mid_statement | code | Comments inserted mid-statement (splitting tokens) |
| cm_license_header | code | License/copyright headers added or removed |
| cm_annotations | code | Docblock annotations (@param, @return, etc.) varied |
| cm_combined | code+whitespace | Comment changes combined with whitespace variation |

## Difficulty

`requires`: comment_stripping (+ whitespace_normalization for cm_combined)
`difficulty_band`: token-based

## Current status

_This level has 55 sets across 11 families (2026-07-13)._

_Pilot present: `cm_line_added/001-005`, `cm_block_added/001-005`, `cm_docblock/001-005`,
`cm_inline_trailing/001-005`, `cm_removed/001-005`, `cm_text_changed/001-005`,
`cm_commented_code/001-005`, `cm_mid_statement/001-005`, `cm_license_header/001-005`,
`cm_annotations/001-005`, `cm_combined/001-005`._
