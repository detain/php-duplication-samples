# L2 — Whitespace & Layout Variation (Type-1)

**What it tests:** whitespace-normalization only — the token stream is identical to L1; only
layout differs.

Planned families: `ws_blank_before/after/inside`, `ws_operator_spacing`, `ws_indent_width`,
`ws_line_wrap`, `ws_line_join`, `ws_tabs`, `ws_trailing`, `ws_newline`, `ws_brace_style`,
`ws_alignment`, `ws_combined`.

`requires`: `whitespace_normalization`. `detection_expectation`: token/AST/metric/semantic pass;
raw-text matchers fail — that gap is the measurement.

_Pilot present: `ws_blank_inside/001`._
