# L5 — Statement Edits (Type-3)

**What it tests:** gap/edit tolerance — copies have drifted: statements added, removed, moved, or
the clone is split by a foreign block.

Families (all 10 built): `st_insert_logging`, `st_insert_step`, `st_insert_dead`, `st_delete_stmt`,
`st_gap_split`, `st_expr_tweak`, `st_param_reorder`, `st_reorder`, `st_partial`, `st_combined`.

`requires`: `ast_canonicalization` (+ gap tolerance for `st_gap_split`). `detection_expectation`:
AST (gap-tolerant)/metric/semantic pass; strict token matchers fail.

_This level is complete: **50 sets across 10 families** (2026-07-13)._
