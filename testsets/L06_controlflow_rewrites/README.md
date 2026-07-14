# L6 — Control-flow & Expression Rewrites (Type-3/4)

**What it tests:** control-flow normalization — the same computation expressed with a different
control shape, verified behaviorally equivalent.

Families (all 10 built): `cf_if_ternary`, `cf_match_switch`, `cf_guard_nested`, `cf_loop_forms`,
`cf_early_return`, `bl_demorgan`, `bl_split_combined`, `bl_commutative`, `ex_arith`,
`nu_null_styles`.

`requires`: `controlflow_normalization`, `commutative_reordering`. `detection_expectation`:
`token_based: false` from here on — AST-canonicalizing and semantic detectors are expected;
token tools are not, and the matrix shows that honestly.

_This level is complete: all 10 families built (50 sets, 2026-07-13)._
