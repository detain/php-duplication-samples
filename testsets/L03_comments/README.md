# L3 — Comment Variation (Type-1)

**What it tests:** comment-stripping only — layout is held identical to L1; only comments differ.

Planned families: `cm_line_added`, `cm_block_added`, `cm_docblock`, `cm_inline_trailing`,
`cm_removed`, `cm_text_changed`, `cm_commented_code`, `cm_mid_statement`, `cm_license_header`,
`cm_annotations`, `cm_combined`.

`requires`: `comment_stripping` (+ `whitespace_normalization` for `cm_combined`).
`detection_expectation`: token (with comment strip)/AST/metric/semantic pass; raw-text fails.

_No sets built yet (transform `CM-03` is implemented and ready)._
